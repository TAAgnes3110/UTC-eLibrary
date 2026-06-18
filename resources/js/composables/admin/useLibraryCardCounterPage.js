import { ref, computed, watch, reactive } from 'vue';
import { libraryCardsApi } from '@/api/libraryCards';
import { usersApi } from '@/api/users';
import { toast } from '@/store/toast';
import { LibraryCard } from '@/config/libraryCardConstants';
import {
    buildCounterPaymentPayload,
    canTogglePaidAtCounter,
    counterFeeAmountForHolder,
    defaultPaidAtCounter,
    isExternalHolder,
    isTeacherHolder,
    resolveCounterWorkflowPreview,
} from '@/config/libraryCardCounterRules';
import { resetFileInput } from '@/utils/resetFileInput';

function emptyForm(flowMode = 'without_account') {
    const holderType = flowMode === 'without_account'
        ? LibraryCard.HOLDER_EXTERNAL
        : LibraryCard.HOLDER_STUDENT;

    return {
        holder_type: holderType,
        user_id: '',
        code: '',
        full_name: '',
        email: '',
        phone: '',
        address: '',
        date_of_birth: '',
        faculty_id: '',
        period_id: '',
        class_code: '',
        external_organization: '',
        paid_at_counter: defaultPaidAtCounter(holderType, flowMode),
        payment_amount: counterFeeAmountForHolder(holderType),
        payment_method: 'walk_in',
        receipt_number: '',
        photoFile: null,
    };
}

/** Map RoleType từ API user → loại thẻ */
function mapUserTypeToHolder(userType) {
    const t = String(userType || '').toUpperCase();
    if (t === 'STUDENT') {
        return LibraryCard.HOLDER_STUDENT;
    }
    if (t === 'TEACHER') {
        return LibraryCard.HOLDER_TEACHER;
    }
    return LibraryCard.HOLDER_EXTERNAL;
}

function userTypeDisplay(userType) {
    const t = String(userType || '').toUpperCase();
    if (t === 'STUDENT') {
        return 'Sinh viên';
    }
    if (t === 'TEACHER') {
        return 'Giảng viên';
    }
    return 'Bạn đọc ngoài';
}

/** Địa chỉ bắt buộc trên API — tạo chuỗi duy nhất khi để trống */
function resolveAddressForApi(f) {
    const a = String(f.address || '').trim();
    if (a) {
        return a;
    }
    const key = String(f.email || f.code || '').trim() || `t-${Date.now()}`;
    return `(Chưa cung cấp) — ${key}`;
}

function firstErrorText(errors, field) {
    const arr = errors?.[field];
    return Array.isArray(arr) && arr.length ? String(arr[0]) : '';
}

function buildDuplicateFriendlyMessage(error) {
    const status = error?.response?.status;
    const data = error?.response?.data || {};
    if (status !== 422) {
        return String(data?.messages || data?.message || 'Không tạo được thẻ.');
    }

    const errors = data?.errors || {};
    const codeErr = firstErrorText(errors, 'code');
    const emailErr = firstErrorText(errors, 'email');
    const phoneErr = firstErrorText(errors, 'phone');

    const duplicated = [];
    if (codeErr.toLowerCase().includes('đã')) duplicated.push('mã định danh');
    if (emailErr.toLowerCase().includes('đã')) duplicated.push('email');
    if (phoneErr.toLowerCase().includes('đã')) duplicated.push('số điện thoại');

    if (duplicated.length === 1) {
        return `${duplicated[0].charAt(0).toUpperCase() + duplicated[0].slice(1)} đã được sử dụng.`;
    }
    if (duplicated.length > 1) {
        const msg = `${duplicated.join(', ')} đã được sử dụng.`;
        return msg.charAt(0).toUpperCase() + msg.slice(1);
    }

    const firstFieldError = Object.values(errors).flat().find(Boolean);
    if (firstFieldError) {
        return String(firstFieldError);
    }

    return String(data?.messages || data?.message || 'Dữ liệu không hợp lệ.');
}

function successMessageForWorkflow(preview) {
    if (preview.key === 'active') {
        return 'Đã tạo và kích hoạt thẻ bạn đọc ngoài.';
    }
    if (preview.key === 'pending_pickup') {
        return 'Đã tạo hồ sơ — trạng thái « Chờ lấy thẻ ». Xác nhận giao thẻ khi bạn đọc đến quầy.';
    }

    return 'Đã tạo hồ sơ — trạng thái « Chờ duyệt ». Duyệt tại mục Duyệt yêu cầu.';
}

function applyHolderTypeSideEffects(form, holderType, flowMode) {
    form.payment_amount = counterFeeAmountForHolder(holderType);
    form.paid_at_counter = defaultPaidAtCounter(holderType, flowMode);

    if (holderType === LibraryCard.HOLDER_TEACHER) {
        form.period_id = '';
        form.class_code = '';
    } else if (holderType === LibraryCard.HOLDER_EXTERNAL) {
        form.faculty_id = '';
        form.period_id = '';
        form.class_code = '';
    }
}

/**
 * Cấp thẻ tại quầy — 2 luồng: đã có tài khoản / chưa có tài khoản.
 * @param {object} props faculties, periods
 */
export function useLibraryCardCounterPage(props) {
    const faculties = computed(() => props.faculties ?? []);
    const periods = computed(() => props.periods ?? []);

    /** @type {import('vue').Ref<'with_account'|'without_account'>} */
    const flowMode = ref('without_account');

    const userSearch = ref('');
    const userHits = ref([]);
    const userSearchLoading = ref(false);
    const selectedUser = ref(null);

    const form = ref(emptyForm(flowMode.value));

    const submitLoading = ref(false);

    const canTogglePaid = computed(() => canTogglePaidAtCounter(form.value.holder_type));

    const showFeeSection = computed(() => true);

    const isPaymentAmountReadonly = computed(() => {
        const ht = form.value.holder_type;
        return isTeacherHolder(ht) || isExternalHolder(ht);
    });

    function resetCounterPhotoInput() {
        const el = typeof document !== 'undefined' ? document.getElementById('counter-card-photo-input') : null;
        resetFileInput(el);
    }

    function setFlowMode(mode) {
        flowMode.value = mode;
        selectedUser.value = null;
        userSearch.value = '';
        userHits.value = [];
        form.value = emptyForm(mode);
        resetCounterPhotoInput();
    }

    watch(
        () => form.value.holder_type,
        (ht) => {
            applyHolderTypeSideEffects(form.value, ht, flowMode.value);
        },
    );

    let searchTimer = null;
    watch(userSearch, (q) => {
        clearTimeout(searchTimer);
        if (!String(q || '').trim()) {
            userHits.value = [];
            return;
        }
        searchTimer = setTimeout(async () => {
            userSearchLoading.value = true;
            try {
                const payload = await usersApi.list({ keyword: q.trim(), type: 'reader', per_page: 20 });
                const body = payload?.data ?? payload;
                const pag = body?.data ?? body;
                const rows = Array.isArray(pag?.data) ? pag.data : Array.isArray(pag) ? pag : [];
                userHits.value = rows;
            } catch {
                userHits.value = [];
            } finally {
                userSearchLoading.value = false;
            }
        }, 350);
    });

    function pickUser(u) {
        selectedUser.value = u;
        form.value.user_id = u.id;
        const holderType = mapUserTypeToHolder(u.user_type);
        form.value.holder_type = holderType;
        applyHolderTypeSideEffects(form.value, holderType, flowMode.value);
        form.value.code = u.code || '';
        form.value.full_name = u.name || '';
        form.value.email = u.email || '';
        form.value.phone = u.phone || '';
        form.value.address = u.address || '';
        if (u.date_of_birth) {
            form.value.date_of_birth = String(u.date_of_birth).slice(0, 10);
        } else {
            form.value.date_of_birth = '';
        }
        form.value.faculty_id = u.faculty_id != null && u.faculty_id !== '' ? String(u.faculty_id) : '';
        form.value.period_id = u.period_id != null && u.period_id !== '' ? String(u.period_id) : '';
        form.value.class_code = u.class_code || '';
        form.value.external_organization = '';
        userHits.value = [];
        userSearch.value = '';
    }

    function clearPickedUser() {
        selectedUser.value = null;
        form.value = emptyForm(flowMode.value);
        resetCounterPhotoInput();
    }

    async function submit() {
        const f = form.value;
        const withAccount = flowMode.value === 'with_account';

        if (withAccount && !selectedUser.value) {
            toast.error('Vui lòng tìm và chọn bạn đọc theo mã định danh.', { title: 'Thiếu thông tin' });
            return;
        }
        if (!String(f.full_name || '').trim() || !String(f.email || '').trim()) {
            toast.error('Họ tên và email là bắt buộc.', { title: 'Thiếu thông tin' });
            return;
        }
        if (!String(f.phone || '').trim()) {
            toast.error('Số điện thoại là bắt buộc.', { title: 'Thiếu thông tin' });
            return;
        }
        if (!String(f.code || '').trim()) {
            toast.error('Mã định danh không được để trống.', { title: 'Thiếu thông tin' });
            return;
        }
        if (f.holder_type === LibraryCard.HOLDER_STUDENT) {
            if (!f.faculty_id || !f.period_id || !String(f.class_code || '').trim()) {
                toast.error('Sinh viên: cần khoa, niên khóa và lớp.', { title: 'Thiếu thông tin' });
                return;
            }
        }
        if (f.holder_type === LibraryCard.HOLDER_TEACHER && !f.faculty_id) {
            toast.error('Giảng viên: cần chọn khoa.', { title: 'Thiếu thông tin' });
            return;
        }
        if (!(f.photoFile instanceof File)) {
            toast.error('Vui lòng chọn ảnh thẻ (file ảnh).', { title: 'Thiếu ảnh' });
            return;
        }
        if (!f.date_of_birth) {
            toast.error('Vui lòng nhập ngày sinh.', { title: 'Thiếu thông tin' });
            return;
        }

        const payment = buildCounterPaymentPayload(f.holder_type, f.paid_at_counter, f.payment_amount);
        const preview = resolveCounterWorkflowPreview(f.holder_type, payment.paid_at_counter);

        submitLoading.value = true;
        try {
            const fd = new FormData();
            fd.append('holder_type', f.holder_type);
            if (withAccount && f.user_id) {
                fd.append('user_id', String(f.user_id));
            }
            fd.append('code', String(f.code || '').trim());
            fd.append('full_name', String(f.full_name).trim());
            fd.append('email', String(f.email).trim());
            fd.append('phone', String(f.phone || '').trim());
            fd.append('address', resolveAddressForApi(f));
            fd.append('date_of_birth', f.date_of_birth || '');
            if (f.holder_type === LibraryCard.HOLDER_STUDENT) {
                fd.append('faculty_id', String(f.faculty_id));
                fd.append('period_id', String(f.period_id));
                fd.append('class_code', String(f.class_code).trim());
            } else if (f.holder_type === LibraryCard.HOLDER_TEACHER) {
                fd.append('faculty_id', String(f.faculty_id));
            } else if (f.external_organization) {
                fd.append('external_organization', String(f.external_organization).trim());
            }
            fd.append('paid_at_counter', payment.paid_at_counter ? '1' : '0');
            fd.append('payment_amount', String(payment.payment_amount));
            if (f.payment_method) {
                fd.append('payment_method', f.payment_method);
            }
            if (f.receipt_number) {
                fd.append('receipt_number', f.receipt_number);
            }

            fd.append('photo', f.photoFile);

            await libraryCardsApi.create(fd);
            toast.success(successMessageForWorkflow(preview), { title: 'Thành công' });
            setFlowMode(flowMode.value);
        } catch (e) {
            toast.error(buildDuplicateFriendlyMessage(e), { title: 'Lỗi' });
        } finally {
            submitLoading.value = false;
        }
    }

    const isWithAccount = computed(() => flowMode.value === 'with_account');

    const formReady = computed(() => {
        if (flowMode.value === 'with_account') {
            return selectedUser.value !== null;
        }

        return true;
    });

    return reactive({
        faculties,
        periods,
        flowMode,
        setFlowMode,
        isWithAccount,
        formReady,
        userSearch,
        userHits,
        userSearchLoading,
        selectedUser,
        pickUser,
        clearPickedUser,
        userTypeDisplay,
        form,
        submit,
        submitLoading,
        canTogglePaid,
        showFeeSection,
        isPaymentAmountReadonly,
        LibraryCard,
    });
}
