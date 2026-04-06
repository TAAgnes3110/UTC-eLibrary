import { ref, computed, watch, reactive } from 'vue';
import { libraryCardsApi } from '@/api/libraryCards';
import { usersApi } from '@/api/users';
import { toast } from '@/store/toast';
import { LibraryCard } from '@/config/libraryCardConstants';

/** Mặc định phí cấp thẻ tại quầy (VNĐ) */
const DEFAULT_COUNTER_PAYMENT_AMOUNT = 40000;

function emptyForm() {
    return {
        holder_type: LibraryCard.HOLDER_EXTERNAL,
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
        paid_at_counter: true,
        payment_amount: DEFAULT_COUNTER_PAYMENT_AMOUNT,
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

    const form = ref(emptyForm());

    const submitLoading = ref(false);

    function setFlowMode(mode) {
        flowMode.value = mode;
        selectedUser.value = null;
        userSearch.value = '';
        userHits.value = [];
        form.value = emptyForm();
        const el = typeof document !== 'undefined' ? document.getElementById('counter-card-photo-input') : null;
        if (el) {
            el.value = '';
        }
    }

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
        form.value.holder_type = mapUserTypeToHolder(u.user_type);
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
        form.value = emptyForm();
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
                if (f.period_id) {
                    fd.append('period_id', String(f.period_id));
                }
                if (f.class_code) {
                    fd.append('class_code', String(f.class_code));
                }
            } else {
                if (f.external_organization) {
                    fd.append('external_organization', String(f.external_organization).trim());
                }
            }
            fd.append('paid_at_counter', f.paid_at_counter ? '1' : '0');
            fd.append('payment_amount', String(f.payment_amount ?? 0));
            if (f.payment_method) {
                fd.append('payment_method', f.payment_method);
            }
            if (f.receipt_number) {
                fd.append('receipt_number', f.receipt_number);
            }

            fd.append('photo', f.photoFile);

            await libraryCardsApi.create(fd);
            toast.success('Đã tạo hồ sơ thẻ.', { title: 'Thành công' });
            setFlowMode(flowMode.value);
        } catch (e) {
            const msg = e?.response?.data?.messages || e?.response?.data?.message || 'Không tạo được thẻ.';
            toast.error(msg, { title: 'Lỗi' });
        } finally {
            submitLoading.value = false;
        }
    }

    const isWithAccount = computed(() => flowMode.value === 'with_account');

    return reactive({
        faculties,
        periods,
        flowMode,
        setFlowMode,
        isWithAccount,
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
        LibraryCard,
    });
}
