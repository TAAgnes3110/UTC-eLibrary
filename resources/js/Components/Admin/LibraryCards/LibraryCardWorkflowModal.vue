<script setup>
import { ref, watch, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Icon } from '@iconify/vue';

const holderTypeLabel = (holderType) => {
    if (holderType === 'external') return 'Bạn đọc ngoài';
    if (holderType === 'student') return 'Sinh viên';
    if (holderType === 'teacher') return 'Giáo viên';
    if (holderType === 'member') return 'Nội bộ (cũ)';
    return holderType || '—';
};

const photoUrl = (path) => {
    if (!path || typeof path !== 'string') return '/images/default-avatar.png';
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    if (path.startsWith('blob:')) return path;
    if (path.startsWith('/')) return path;
    return `/storage/${path.replace(/^\/+/, '')}`;
};

const props = defineProps({
    show: { type: Boolean, required: true },
    card: { type: Object, default: null },
    operation: { type: String, default: 'manage' }, // manage | approve | quick
    /** true = nhúng trong trang (màn chỉ thêm), không overlay modal */
    embedded: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    fieldErrors: { type: Object, default: () => ({}) },
    /** Niên khóa (từ server) — chọn period_id cho thẻ sinh viên */
    periods: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'save']);

const form = ref({});
const quickPhotoFileName = ref('');

const resetForm = () => {
    const c = props.card || {};
    form.value = {
        quick_mode: 'has_account', // has_account | no_account

        // For "quick issue" UI: choose recipient category (student/teacher/external)
        recipient_kind:
            c.holder_type === 'external'
                ? 'external'
                : c.holder_type === 'teacher'
                  ? 'teacher'
                  : 'student',

        // Existing-account flow
        account_code: c.user?.code ?? '',
        account_email: c.user?.email ?? c.email ?? '',
        teacher_major_or_faculty: c.faculty?.name ?? '',
        quick_full_name: c.full_name ?? c.user?.name ?? '',
        quick_date_of_birth: c.date_of_birth ? String(c.date_of_birth).slice(0, 10) : '',
        quick_faculty: c.faculty?.name ?? '',

        // Counter quick issue: email for 3-day notification window
        notification_email: c.email ?? '',

        // Quick issue: extra inputs by recipient kind
        student_class: c.params?.student_class ?? '',
        code: c.code ?? c.user?.code ?? '',
        external_address: c.address ?? '',
        period_id: c.period_id ?? null,

        params: c.params ?? {},

        workflow_status: c.workflow_status ?? 'draft',
        payment_status: c.payment_status ?? '',
        payment_amount: c.payment_amount ?? '',
        payment_method: c.payment_method ?? '',
        receipt_number: c.receipt_number ?? '',
        paid_at: c.paid_at ? c.paid_at.slice(0, 16) : '',
        reviewed_by: c.reviewed_by ?? undefined,
        reviewed_at: c.reviewed_at ? c.reviewed_at.slice(0, 16) : '',
        notes: c.notes ?? '',
        issue_date: c.issue_date ? String(c.issue_date).slice(0, 10) : '',
        expiry_date: c.expiry_date ? String(c.expiry_date).slice(0, 10) : '',
        is_active: c.is_active ?? true,
        status: c.status ?? undefined,
        // keeper for payload keys; submit will pick from form
    };
    quickPhotoFileName.value = '';

    // For quick issue at counter: default to issuing & paid.
    if (props.operation === 'quick') {
        const now = new Date();
        form.value.workflow_status = 'active';
        form.value.payment_status = 'paid';
        form.value.paid_at = now.toISOString().slice(0, 16);
        form.value.issue_date = now.toISOString().slice(0, 10);
        form.value.is_active = true;
    }
};

const shouldDisplay = computed(() => props.embedded || props.show);

watch(
    () => [props.show, props.embedded, props.card?.id, props.operation],
    () => {
        if (shouldDisplay.value) resetForm();
    },
    { immediate: true },
);

watch(
    () => [form.value.quick_mode, form.value.recipient_kind, props.operation],
    () => {
        if (props.operation !== 'quick') return;
        if (form.value.quick_mode !== 'no_account') return;

        // Quy tắc thanh toán tại quầy:
        // - member/student: bắt buộc thanh toán
        // - teacher: không bắt buộc thanh toán
        if (form.value.recipient_kind === 'teacher') {
            form.value.payment_status = '';
            form.value.payment_amount = '';
            form.value.payment_method = '';
            form.value.receipt_number = '';
            form.value.paid_at = '';
        } else if (!form.value.payment_status) {
            form.value.payment_status = 'paid';
        }
    },
    { deep: true },
);

function buildPayload() {
    const nextParams = { ...(form.value.params || {}) };
    if (props.operation === 'quick' && form.value.quick_mode === 'no_account') {
        nextParams.recipient_kind = form.value.recipient_kind;
    }
    if (form.value.recipient_kind === 'student') {
        nextParams.student_class = form.value.student_class || undefined;
        nextParams.student_faculty = form.value.quick_faculty || undefined;
    } else if (form.value.recipient_kind === 'teacher') {
        nextParams.teacher_major_or_faculty = form.value.teacher_major_or_faculty || undefined;
    }

    const payload = {
        workflow_status: form.value.workflow_status || undefined,
        payment_status: form.value.payment_status || undefined,
        payment_amount: form.value.payment_amount !== '' ? Number(form.value.payment_amount) : undefined,
        payment_method: form.value.payment_method || undefined,
        receipt_number: form.value.receipt_number || undefined,
        paid_at: form.value.paid_at ? new Date(form.value.paid_at).toISOString() : undefined,
        reviewed_by: form.value.reviewed_by ?? undefined,
        reviewed_at: form.value.reviewed_at ? new Date(form.value.reviewed_at).toISOString() : undefined,
        notes: form.value.notes || undefined,
        period_id: form.value.period_id != null && form.value.period_id !== '' ? Number(form.value.period_id) : undefined,
        issue_date: form.value.issue_date || undefined,
        expiry_date: form.value.expiry_date || undefined,
        is_active: Boolean(form.value.is_active),
        status: form.value.status ?? undefined,

        // Quick issue: store counter-entered fields
        email: form.value.notification_email || undefined,
        code: form.value.code || undefined,
        address: form.value.external_address || undefined,
        full_name: form.value.quick_full_name || undefined,
        date_of_birth: form.value.quick_date_of_birth || undefined,
        params: Object.keys(nextParams).length > 0 ? nextParams : undefined,
    };

    if (props.operation === 'quick') {
        if (form.value.quick_mode === 'has_account') {
            payload.holder_type = 'student';
        } else {
            const rk = form.value.recipient_kind;
            if (rk === 'external') payload.holder_type = 'external';
            else if (rk === 'teacher') payload.holder_type = 'teacher';
            else payload.holder_type = 'student';
        }
    }

    // Remove undefined/null keys
    Object.keys(payload).forEach((k) => {
        if (payload[k] === undefined || payload[k] === null || payload[k] === '') delete payload[k];
    });

    return payload;
}

const onQuickPhotoSelect = (e) => {
    const f = e?.target?.files?.[0];
    quickPhotoFileName.value = f?.name || '';
};

const requiresPaymentInQuick = () => {
    if (props.operation !== 'quick') return true;
    if (form.value.quick_mode !== 'no_account') return true;
    return form.value.recipient_kind !== 'teacher';
};

const onSave = () => {
    emit('save', buildPayload());
};

const headerTitle = computed(() => {
    if (props.operation === 'quick' && !props.card?.id) {
        return 'Cấp thẻ thư viện nhanh';
    }
    return `Thẻ thư viện: ${props.card?.card_number || '—'}`;
});

const headerSubtitle = computed(() => {
    if (props.operation === 'quick' && !props.card?.id) {
        return 'Nhập thông tin cấp thẻ tại quầy (thêm mới)';
    }
    return props.card?.full_name || props.card?.user?.name || '';
});

/** Chỉ hiện khi thật sự từ chối hồ sơ (không dùng trên luồng cấp nhanh). */
const showRejectionField = computed(
    () => props.operation !== 'quick' && form.value.workflow_status === 'rejected',
);

const inputClass =
    'w-full h-11 px-3.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900/70 text-slate-900 dark:text-white text-sm shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500/35 focus:border-indigo-500 dark:focus:border-indigo-400 disabled:opacity-60';
</script>

<template>
    <Teleport to="body" :disabled="embedded">
        <div
            v-if="shouldDisplay"
            :class="
                embedded
                    ? 'w-full max-w-3xl mx-auto'
                    : 'fixed inset-0 z-[100] flex items-center justify-center p-4'
            "
        >
            <div v-if="!embedded" class="absolute inset-0 bg-slate-900/50" @click="emit('close')" />

            <div
                class="relative bg-white dark:bg-slate-900 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-xl border border-slate-200/90 dark:border-slate-700/80 ring-1 ring-black/5 dark:ring-white/5"
                :class="embedded ? 'max-h-none max-w-3xl' : ''"
            >
                <div
                    class="sticky top-0 px-6 py-4 border-b border-slate-200/90 dark:border-slate-700/80 flex justify-between items-center z-10 bg-gradient-to-r from-slate-50 to-white dark:from-slate-800/90 dark:to-slate-900"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center overflow-hidden shrink-0">
                            <img :src="photoUrl(card?.photo_path)" class="w-full h-full object-cover" :alt="card?.full_name || 'Avatar'" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white truncate">
                                {{ headerTitle }}
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                {{ headerSubtitle }}
                            </p>
                        </div>
                    </div>
                    <Link
                        v-if="embedded"
                        :href="route('admin.library-cards.manage')"
                        class="p-2 min-w-[44px] min-h-[44px] inline-flex items-center justify-center text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 rounded-lg"
                        title="Đóng và về danh sách"
                    >
                        <Icon icon="lucide:x" class="w-5 h-5" />
                    </Link>
                    <button
                        v-else
                        type="button"
                        class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                        @click="emit('close')"
                    >
                        <Icon icon="lucide:x" class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 sm:p-8 grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <template v-if="operation !== 'quick'">
                    <!-- Thông tin thẻ thư viện (read-only) -->
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mã thẻ</label>
                        <Input :modelValue="card?.card_number || '—'" disabled class="h-10 rounded-lg dark:bg-slate-800" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Họ tên</label>
                        <Input :modelValue="card?.full_name || card?.user?.name || '—'" disabled class="h-10 rounded-lg dark:bg-slate-800" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Ngày sinh</label>
                        <Input
                            :modelValue="card?.date_of_birth ? String(card.date_of_birth).slice(0, 10) : '—'"
                            disabled
                            class="h-10 rounded-lg dark:bg-slate-800"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Loại thẻ</label>
                        <Input
                            :modelValue="holderTypeLabel(card?.holder_type)"
                            disabled
                            class="h-10 rounded-lg dark:bg-slate-800"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mã định danh</label>
                        <Input
                            :modelValue="card?.code || '—'"
                            disabled
                            class="h-10 rounded-lg font-mono dark:bg-slate-800"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Niên khóa</label>
                        <Input
                            :modelValue="card?.period?.name ? `${card.period.name} (${card.period.code})` : '—'"
                            disabled
                            class="h-10 rounded-lg dark:bg-slate-800"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Khoa / Tổ chức</label>
                        <Input
                            :modelValue="
                                card?.holder_type === 'student' || card?.holder_type === 'teacher' || card?.holder_type === 'member'
                                    ? card?.faculty?.name || '—'
                                    : card?.external_organization || '—'
                            "
                            disabled
                            class="h-10 rounded-lg dark:bg-slate-800"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Email</label>
                        <Input :modelValue="card?.email || '—'" disabled class="h-10 rounded-lg dark:bg-slate-800" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Số điện thoại</label>
                        <Input :modelValue="card?.phone || '—'" disabled class="h-10 rounded-lg dark:bg-slate-800" />
                    </div>

                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Địa chỉ</label>
                        <textarea
                            :value="card?.address || ''"
                            disabled
                            rows="2"
                            class="w-full rounded-lg border bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 text-sm resize-y disabled:opacity-60"
                        />
                    </div>
                    </template>

                    <!-- Thông tin cấp thẻ tại quầy (Quick Issue) -->
                    <div
                        v-if="operation === 'quick'"
                        class="sm:col-span-2 rounded-2xl border border-indigo-200/90 dark:border-indigo-700/50 bg-gradient-to-br from-indigo-50/90 via-white to-violet-50/40 dark:from-indigo-950/40 dark:via-slate-900/40 dark:to-violet-950/30 p-5 sm:p-6 space-y-5 shadow-sm"
                    >
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5 sm:col-span-2">
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Nguồn cấp thẻ</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        class="h-10 rounded-lg border text-sm font-semibold transition-colors"
                                        :class="form.quick_mode === 'has_account' ? 'border-indigo-500 text-indigo-700 dark:text-indigo-300 bg-indigo-50/60 dark:bg-indigo-900/30' : 'border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300'"
                                        @click="form.quick_mode = 'has_account'"
                                    >
                                        Đã có tài khoản web
                                    </button>
                                    <button
                                        type="button"
                                        class="h-10 rounded-lg border text-sm font-semibold transition-colors"
                                        :class="form.quick_mode === 'no_account' ? 'border-indigo-500 text-indigo-700 dark:text-indigo-300 bg-indigo-50/60 dark:bg-indigo-900/30' : 'border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300'"
                                        @click="form.quick_mode = 'no_account'"
                                    >
                                        Chưa có tài khoản
                                    </button>
                                </div>
                            </div>

                            <template v-if="form.quick_mode === 'has_account'">
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Mã tài khoản</label>
                                    <Input v-model="form.account_code" class="h-10 rounded-lg font-mono" placeholder="vd: UTCSTU001" />
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Email tài khoản</label>
                                    <Input v-model="form.account_email" class="h-10 rounded-lg" placeholder="email@utc.edu.vn" />
                                </div>
                                <div class="space-y-1.5 sm:col-span-2">
                                    <p class="text-xs text-slate-600 dark:text-slate-400">
                                        Hồ sơ đã có tài khoản sẽ được đưa vào mục "Duyệt yêu cầu cấp thẻ". Sau khi duyệt và hoàn tất quy trình 3 ngày sẽ nhận thông báo.
                                    </p>
                                </div>
                            </template>

                            <template v-else>
                                <div class="space-y-1.5 sm:col-span-2">
                                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Đối tượng không tài khoản</label>
                                    <select
                                        v-model="form.recipient_kind"
                                        :class="inputClass"
                                    >
                                        <option value="external">Bạn đọc ngoài</option>
                                        <option value="student">Sinh viên</option>
                                        <option value="teacher">Giáo viên</option>
                                    </select>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Họ tên</label>
                                    <Input v-model="form.quick_full_name" class="h-10 rounded-lg" placeholder="Nhập họ tên" />
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Ngày sinh</label>
                                    <input
                                        v-model="form.quick_date_of_birth"
                                        type="date"
                                        class="w-full h-10 px-3 rounded-lg border bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                                    />
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {{ form.recipient_kind === 'student' ? 'Số CCCD / CMND' : 'Số định danh / Căn cước' }}
                                    </label>
                                    <Input v-model="form.code" class="h-10 rounded-lg font-mono" placeholder="Số CCCD / CMND" />
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Ảnh 3x4</label>
                                    <input
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.webp"
                                        class="w-full h-10 px-2 rounded-lg border bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                                        @change="onQuickPhotoSelect"
                                    />
                                    <p v-if="quickPhotoFileName" class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ quickPhotoFileName }}</p>
                                </div>

                                <div v-if="form.recipient_kind === 'student'" class="space-y-1.5">
                                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Khoa</label>
                                    <Input v-model="form.quick_faculty" class="h-10 rounded-lg" placeholder="Nhập khoa" />
                                </div>
                                <div v-if="form.recipient_kind === 'student'" class="space-y-1.5">
                                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Lớp</label>
                                    <Input v-model="form.student_class" class="h-10 rounded-lg" placeholder="vd: K60CNTT" />
                                </div>
                                <div v-if="form.recipient_kind === 'student'" class="space-y-1.5 sm:col-span-2">
                                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Niên khóa</label>
                                    <select
                                        v-model="form.period_id"
                                        class="w-full h-10 px-3 rounded-lg border bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                                    >
                                        <option :value="null">— Chọn niên khóa —</option>
                                        <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }} ({{ p.code }})</option>
                                    </select>
                                </div>
                                <div v-if="form.recipient_kind === 'student'" class="space-y-1.5">
                                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Email nhận thông báo</label>
                                    <Input v-model="form.notification_email" class="h-10 rounded-lg" placeholder="email@utc.edu.vn" />
                                </div>

                                <div v-if="form.recipient_kind === 'teacher'" class="space-y-1.5 sm:col-span-2">
                                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Chuyên ngành / Khoa</label>
                                    <Input v-model="form.teacher_major_or_faculty" class="h-10 rounded-lg" placeholder="Nhập chuyên ngành hoặc khoa" />
                                </div>

                                <div v-if="form.recipient_kind === 'external'" class="space-y-1.5 sm:col-span-2">
                                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Địa chỉ</label>
                                    <textarea
                                        v-model="form.external_address"
                                        rows="2"
                                        class="w-full rounded-lg border bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 text-sm resize-y"
                                    />
                                </div>

                                <div class="space-y-1.5 sm:col-span-2">
                                    <p class="text-xs text-slate-600 dark:text-slate-400">
                                        Quy tắc: Bạn đọc/Sinh viên bắt buộc thanh toán. Giáo viên không bắt buộc thanh toán.
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Sửa thẻ / duyệt: đầy đủ trạng thái & lý do từ chối (chỉ khi từ chối) -->
                    <template v-if="operation !== 'quick'">
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Trạng thái thẻ</label>
                            <select v-model="form.workflow_status" :class="inputClass">
                                <option value="draft">Nháp</option>
                                <option value="pending_payment">Chờ thanh toán</option>
                                <option value="pending_review">Chờ duyệt</option>
                                <option value="active">Đang hoạt động</option>
                                <option value="rejected">Từ chối</option>
                                <option value="cancelled">Đã hủy</option>
                                <option value="expired">Hết hạn</option>
                                <option value="revoked">Bị thu hồi</option>
                            </select>
                            <p v-if="fieldErrors.workflow_status" class="text-xs text-red-500 font-medium mt-1">
                                {{ fieldErrors.workflow_status }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Niên khóa (sinh viên)</label>
                            <select v-model="form.period_id" :class="inputClass">
                                <option :value="null">— Không chọn —</option>
                                <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }} ({{ p.code }})</option>
                            </select>
                            <p v-if="fieldErrors.period_id" class="text-xs text-red-500 font-medium mt-1">
                                {{ fieldErrors.period_id }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Trạng thái thanh toán</label>
                            <select v-model="form.payment_status" :class="inputClass">
                                <option value="">(Không)</option>
                                <option value="pending">Chờ</option>
                                <option value="paid">Đã thanh toán</option>
                                <option value="failed">Thất bại</option>
                                <option value="refunded">Đã hoàn</option>
                            </select>
                            <p v-if="fieldErrors.payment_status" class="text-xs text-red-500 font-medium mt-1">
                                {{ fieldErrors.payment_status }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Số tiền (VND)</label>
                            <Input v-model="form.payment_amount" class="h-11 rounded-xl border-slate-200 dark:border-slate-600" type="number" min="0" />
                            <p v-if="fieldErrors.payment_amount" class="text-xs text-red-500 font-medium mt-1">
                                {{ fieldErrors.payment_amount }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Phương thức</label>
                            <Input
                                v-model="form.payment_method"
                                class="h-11 rounded-xl border-slate-200 dark:border-slate-600"
                                placeholder="vd: chuyển khoản"
                            />
                            <p v-if="fieldErrors.payment_method" class="text-xs text-red-500 font-medium mt-1">
                                {{ fieldErrors.payment_method }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Số biên lai</label>
                            <Input
                                v-model="form.receipt_number"
                                class="h-11 rounded-xl border-slate-200 dark:border-slate-600 font-mono text-sm"
                                placeholder="vd: RCPT-2026..."
                            />
                            <p v-if="fieldErrors.receipt_number" class="text-xs text-red-500 font-medium mt-1">
                                {{ fieldErrors.receipt_number }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Ngày thanh toán</label>
                            <input v-model="form.paid_at" type="datetime-local" :class="inputClass" />
                            <p v-if="fieldErrors.paid_at" class="text-xs text-red-500 font-medium mt-1">
                                {{ fieldErrors.paid_at }}
                            </p>
                        </div>

                        <div v-if="showRejectionField" class="space-y-1.5 sm:col-span-2 rounded-xl border border-rose-200/80 dark:border-rose-800/60 bg-rose-50/50 dark:bg-rose-950/20 p-4">
                            <label class="text-sm font-semibold text-rose-800 dark:text-rose-200">Lý do từ chối / ghi chú</label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="w-full rounded-xl border border-rose-200/90 dark:border-rose-800/50 bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-3 py-2.5 text-sm resize-y min-h-[5rem] focus:outline-none focus:ring-2 focus:ring-rose-500/30"
                            />
                            <p v-if="fieldErrors.notes" class="text-xs text-red-500 font-medium mt-1">
                                {{ fieldErrors.notes }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Ngày cấp</label>
                            <input v-model="form.issue_date" type="date" :class="inputClass" />
                            <p v-if="fieldErrors.issue_date" class="text-xs text-red-500 font-medium mt-1">
                                {{ fieldErrors.issue_date }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Ngày hết hạn</label>
                            <input v-model="form.expiry_date" type="date" :class="inputClass" />
                            <p v-if="fieldErrors.expiry_date" class="text-xs text-red-500 font-medium mt-1">
                                {{ fieldErrors.expiry_date }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3 sm:col-span-2 rounded-xl border border-slate-200 dark:border-slate-600 px-4 py-3 bg-slate-50/80 dark:bg-slate-800/40">
                            <input
                                id="is_active"
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500"
                            />
                            <label for="is_active" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Kích hoạt thẻ
                            </label>
                        </div>
                    </template>

                    <!-- Cấp nhanh: gọn — không chọn workflow; không hiện lý do từ chối -->
                    <template v-else>
                        <div class="sm:col-span-2 space-y-5">
                            <div
                                v-if="requiresPaymentInQuick()"
                                class="rounded-2xl border border-slate-200/90 dark:border-slate-600/80 bg-slate-50/50 dark:bg-slate-800/30 p-5 sm:p-6 space-y-4"
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300"
                                    >
                                        <Icon icon="lucide:banknote" class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Thanh toán tại quầy</h4>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ghi nhận biên lai và thời điểm thu phí.</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Trạng thái thanh toán</label>
                                        <select v-model="form.payment_status" :class="inputClass">
                                            <option value="">(Không)</option>
                                            <option value="pending">Chờ</option>
                                            <option value="paid">Đã thanh toán</option>
                                            <option value="failed">Thất bại</option>
                                            <option value="refunded">Đã hoàn</option>
                                        </select>
                                        <p v-if="fieldErrors.payment_status" class="text-xs text-red-500 font-medium mt-1">{{ fieldErrors.payment_status }}</p>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Số tiền (VND)</label>
                                        <Input v-model="form.payment_amount" class="h-11 rounded-xl border-slate-200 dark:border-slate-600" type="number" min="0" />
                                        <p v-if="fieldErrors.payment_amount" class="text-xs text-red-500 font-medium mt-1">{{ fieldErrors.payment_amount }}</p>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Phương thức</label>
                                        <Input
                                            v-model="form.payment_method"
                                            class="h-11 rounded-xl border-slate-200 dark:border-slate-600"
                                            placeholder="Tiền mặt, chuyển khoản…"
                                        />
                                        <p v-if="fieldErrors.payment_method" class="text-xs text-red-500 font-medium mt-1">{{ fieldErrors.payment_method }}</p>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Số biên lai</label>
                                        <Input
                                            v-model="form.receipt_number"
                                            class="h-11 rounded-xl border-slate-200 dark:border-slate-600 font-mono text-sm"
                                            placeholder="RCPT-2026…"
                                        />
                                        <p v-if="fieldErrors.receipt_number" class="text-xs text-red-500 font-medium mt-1">{{ fieldErrors.receipt_number }}</p>
                                    </div>
                                    <div class="space-y-1.5 sm:col-span-2">
                                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Thời điểm thanh toán</label>
                                        <input v-model="form.paid_at" type="datetime-local" :class="inputClass" />
                                        <p v-if="fieldErrors.paid_at" class="text-xs text-red-500 font-medium mt-1">{{ fieldErrors.paid_at }}</p>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else
                                class="flex gap-3 rounded-2xl border border-emerald-200/90 dark:border-emerald-800/50 bg-emerald-50/40 dark:bg-emerald-950/25 px-4 py-3.5"
                            >
                                <Icon icon="lucide:badge-check" class="w-5 h-5 shrink-0 text-emerald-600 dark:text-emerald-400 mt-0.5" />
                                <p class="text-sm text-emerald-900 dark:text-emerald-100/90 leading-relaxed">
                                    <span class="font-semibold">Miễn phí cấp thẻ</span> — đối tượng giáo viên không bắt buộc thanh toán tại quầy.
                                </p>
                            </div>

                            <div class="rounded-2xl border border-slate-200/90 dark:border-slate-600/80 bg-white/60 dark:bg-slate-900/40 p-5 sm:p-6 space-y-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200"
                                    >
                                        <Icon icon="lucide:calendar-range" class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Thời hạn thẻ</h4>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ngày cấp và hết hạn ghi trên hệ thống.</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ngày cấp</label>
                                        <input v-model="form.issue_date" type="date" :class="inputClass" />
                                        <p v-if="fieldErrors.issue_date" class="text-xs text-red-500 font-medium mt-1">{{ fieldErrors.issue_date }}</p>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ngày hết hạn</label>
                                        <input v-model="form.expiry_date" type="date" :class="inputClass" />
                                        <p v-if="fieldErrors.expiry_date" class="text-xs text-red-500 font-medium mt-1">{{ fieldErrors.expiry_date }}</p>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200/90 dark:border-slate-600/80 px-4 py-4 sm:px-5 bg-slate-50/80 dark:bg-slate-800/35"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Kích hoạt thẻ ngay</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Bật để thẻ ở trạng thái hoạt động sau khi lưu.</p>
                                </div>
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="form.is_active"
                                    class="relative inline-flex h-11 w-[3.75rem] shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 px-1"
                                    :class="form.is_active ? 'bg-indigo-600' : 'bg-slate-300 dark:bg-slate-600'"
                                    @click="form.is_active = !form.is_active"
                                >
                                    <span
                                        class="pointer-events-none inline-block h-9 w-9 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-out"
                                        :class="form.is_active ? 'translate-x-4' : 'translate-x-0'"
                                    />
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <div
                    class="px-6 sm:px-8 py-4 border-t border-slate-200/90 dark:border-slate-700/80 flex flex-wrap justify-end gap-2 sm:gap-3 bg-slate-50/80 dark:bg-slate-800/40 rounded-b-2xl"
                >
                    <Button v-if="!embedded" variant="outline" :disabled="loading" @click="emit('close')">
                        Hủy
                    </Button>
                    <Link
                        v-else
                        :href="route('admin.library-cards.manage')"
                        class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 min-h-[44px]"
                    >
                        Hủy
                    </Link>
                    <Button class="bg-blue-600 hover:bg-blue-700 text-white min-h-[44px]" :disabled="loading" @click="onSave">
                        {{ loading ? 'Đang lưu...' : operation === 'quick' ? 'Lưu thông tin cấp thẻ' : 'Lưu thay đổi' }}
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

