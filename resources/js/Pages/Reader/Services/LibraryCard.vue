<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import { libraryCardsApi } from '@/api/libraryCards'
import { extractLaravelValidationErrors } from '@/utils/laravelApiError'
import { toast } from '@/store/toast'
import { useImageFallback } from '@/composables/useImageFallback'

const props = defineProps({
    auth_required: { type: Boolean, default: false },
    card: { type: Object, default: null },
    profile: { type: Object, default: null },
    faculties: { type: Array, default: () => [] },
    periods: { type: Array, default: () => [] },
    /** Đã từng có hồ sơ thẻ bị gỡ (soft delete) — được phép gửi lại từ biểu mẫu */
    library_card_can_reapply: { type: Boolean, default: false },
})

const form = reactive({
    avatar_file: null,
    full_name: String(props.profile?.name || '').trim(),
})
const errors = reactive({})
const state = reactive({
    submitting: false,
    cancelSubmitting: false,
    avatarPreview: props.profile?.avatar ?? '',
    avatarFileName: '',
})
const avatarInput = ref(null)
const showAvatarPreviewModal = ref(false)
const showCancelConfirmModal = ref(false)
const { withFallback } = useImageFallback()

const role = computed(() => String(props.profile?.user_type || '').trim().toUpperCase())
const isStudent = computed(() => role.value === 'STUDENT')
const isTeacher = computed(() => role.value === 'TEACHER')
const isExternalReader = computed(() => !isStudent.value && !isTeacher.value)
const hasCard = computed(() => props.card !== null)
/** Chờ duyệt: cho phép sửa biểu mẫu và gửi lại (API thay thế atomic). */
const isPendingReviewResubmit = computed(
    () => hasCard.value && props.card?.workflow_status === 'pending_review',
)
/** Thẻ đã được duyệt / đang chờ lấy — hiện nút hướng dẫn «cấp lại» (thủ tục tại quầy). */
const showReissueLibraryCardCta = computed(() => {
    if (!hasCard.value) return false
    const ws = props.card?.workflow_status

    return ws === 'active' || ws === 'pending_pickup'
})
const showApplyForm = computed(() => {
    if (!hasCard.value) {
        return true
    }

    return props.card?.workflow_status === 'pending_review'
})
const profileCode = computed(() => String(props.profile?.code || '').trim())
const profileName = computed(() => String(props.profile?.name || '').trim())
const profileFacultyId = computed(() => {
    const value = props.profile?.faculty_id
    return value === null || value === undefined || value === '' ? null : Number(value)
})
const profilePeriodId = computed(() => {
    const value = props.profile?.period_id
    return value === null || value === undefined || value === '' ? null : Number(value)
})
const profileClassCode = computed(() => String(props.profile?.class_code || '').trim())
const facultyLabel = computed(() => {
    if (!profileFacultyId.value) return 'Chưa cập nhật'
    const found = (props.faculties || []).find((f) => Number(f?.id) === profileFacultyId.value)
    return found ? `${found.code} - ${found.name}` : 'Chưa cập nhật'
})
const periodLabel = computed(() => {
    if (!profilePeriodId.value) return 'Chưa cập nhật'
    const found = (props.periods || []).find((p) => Number(p?.id) === profilePeriodId.value)
    return found ? `${found.code} - ${found.name}` : 'Chưa cập nhật'
})
const missingProfileFields = computed(() => {
    const missing = []
    if (!profileCode.value) missing.push('Mã định danh')
    if (isStudent.value || isTeacher.value) {
        if (!profileFacultyId.value) missing.push('Khoa')
    }
    if (isStudent.value) {
        if (!profilePeriodId.value) missing.push('Niên khóa')
        if (!profileClassCode.value) missing.push('Lớp')
    }
    return missing
})
const canSubmitApply = computed(() => missingProfileFields.value.length === 0)
const applyPrimaryLabel = computed(() => {
    if (isPendingReviewResubmit.value) {
        return 'Cấp lại thẻ thư viện'
    }
    if (!hasCard.value && props.library_card_can_reapply) {
        return 'Cấp lại thẻ thư viện'
    }

    return 'Cấp thẻ thư viện'
})

const canCancelCardRequest = computed(() => {
    if (!hasCard.value || !props.card) return false
    const ws = props.card.workflow_status
    return ws === 'pending_review' || ws === 'pending_payment'
})

const workflowLabel = computed(() => {
    const v = props.card?.workflow_status
    if (v === 'pending_review') return 'Chờ duyệt'
    if (v === 'pending_pickup') return 'Chờ lấy thẻ'
    if (v === 'active') return 'Đang hoạt động'
    if (v === 'rejected') return 'Đã từ chối'
    if (v === 'pending_payment') return 'Chờ thanh toán'
    return v || '—'
})

const cardStatusLabel = computed(() => {
    const v = Number(props.card?.status)
    if (v === 1) return 'Hoạt động'
    if (v === 2) return 'Hết hạn'
    if (v === 3) return 'Khóa'
    if (v === 4) return 'Chờ xử lý'
    return '—'
})
const cardOwnerName = computed(() => String(props.card?.full_name || profileName.value || 'Bạn đọc'))
const cardPhotoUrl = computed(() => props.card?.photo_url || state.avatarPreview || null)
const cardHolderType = computed(() => String(props.card?.holder_type || '').trim().toLowerCase())
const cardHolderTypeLabel = computed(() => {
    if (cardHolderType.value === 'student') return 'Thẻ sinh viên'
    if (cardHolderType.value === 'teacher') return 'Thẻ giáo viên'
    if (cardHolderType.value === 'external') return 'Thẻ bạn đọc'
    return 'Thẻ thư viện'
})
const cardDetailItems = computed(() => {
    const card = props.card || {}
    const items = [
        { label: 'Họ tên', value: cardOwnerName.value },
        { label: 'Loại thẻ', value: cardHolderTypeLabel.value },
        { label: 'Mã định danh', value: card.code || profileCode.value || '—' },
    ]

    if (cardHolderType.value === 'student') {
        items.push(
            { label: 'Khoa', value: card.faculty?.name || '—' },
            { label: 'Niên khóa', value: card.period?.name || '—' },
            { label: 'Lớp', value: card.class_code || '—' },
        )
    } else if (cardHolderType.value === 'teacher') {
        items.push({ label: 'Khoa', value: card.faculty?.name || '—' })
    } else if (cardHolderType.value === 'external') {
        items.push({ label: 'Đơn vị', value: card.external_organization || 'Bạn đọc ngoài' })
    }

    items.push(
        { label: 'Trạng thái', value: cardStatusLabel.value },
        { label: 'Quy trình', value: workflowLabel.value },
        { label: 'Ngày gửi yêu cầu', value: formatDateTime(card.created_at) },
        { label: 'Hiệu lực', value: `${formatDate(card.issue_date)} — ${formatDate(card.expiry_date)}` },
    )

    return items
})

function formatDate(value) {
    if (!value) return '—'
    const d = new Date(value)
    if (Number.isNaN(d.getTime())) return '—'
    return d.toLocaleDateString('vi-VN')
}

function formatDateTime(value) {
    if (!value) return '—'
    const d = new Date(value)
    if (Number.isNaN(d.getTime())) return '—'
    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(d)
}

watch(
    () => [props.card?.id, props.card?.workflow_status, props.profile?.name, props.profile?.avatar],
    () => {
        if (isPendingReviewResubmit.value) {
            form.full_name = String(props.card?.full_name || props.profile?.name || '').trim()
            form.avatar_file = null
            state.avatarFileName = ''
            state.avatarPreview = props.card?.photo_url || props.profile?.avatar || ''
        } else if (!hasCard.value) {
            form.full_name = String(props.profile?.name || '').trim()
            form.avatar_file = null
            state.avatarFileName = ''
            state.avatarPreview = props.profile?.avatar ?? ''
        }
    },
    { immediate: true },
)

function clearErrors() {
    for (const key of Object.keys(errors)) delete errors[key]
}

function onAvatarChange(event) {
    const file = event?.target?.files?.[0] || null
    // Nếu người dùng đóng hộp chọn file mà không chọn gì, giữ nguyên file/preview hiện tại.
    if (!file) return
    form.avatar_file = file
    delete errors.avatar
    state.avatarFileName = file?.name || ''
    const reader = new FileReader()
    reader.onload = (ev) => {
        state.avatarPreview = String(ev?.target?.result || '')
    }
    reader.readAsDataURL(file)
}

function triggerAvatarPicker() {
    avatarInput.value?.click()
}

function openAvatarPreviewModal() {
    if (!state.avatarPreview) return
    showAvatarPreviewModal.value = true
}

function closeAvatarPreviewModal() {
    showAvatarPreviewModal.value = false
}

function mapApiErrors(err) {
    clearErrors()
    const e = extractLaravelValidationErrors(err) || {}
    for (const [k, v] of Object.entries(e)) {
        errors[k] = Array.isArray(v) ? v[0] : String(v || '')
    }
    if (!Object.keys(errors).length) {
        errors.general = err?.response?.data?.messages || err?.response?.data?.message || 'Không thể gửi yêu cầu cấp thẻ.'
    }
    if (errors.library_card) {
        errors.general = errors.general || errors.library_card
        delete errors.library_card
    }
}

async function submitApply() {
    clearErrors()
    form.full_name = String(form.full_name || '').trim()
    if (!form.full_name) {
        errors.full_name = 'Vui lòng nhập tên hiển thị trên thẻ thư viện.'
        return
    }
    if (!canSubmitApply.value) {
        errors.general = `Thông tin tài khoản còn thiếu: ${missingProfileFields.value.join(', ')}. Vui lòng cập nhật ở trang Tài khoản trước khi cấp thẻ.`
        return
    }
    state.submitting = true
    try {
        let payload = {
            code: profileCode.value,
            full_name: form.full_name,
        }

        if (isStudent.value) {
            payload.faculty_id = profileFacultyId.value
            payload.period_id = profilePeriodId.value
            payload.class_code = profileClassCode.value
        } else if (isTeacher.value) {
            payload.faculty_id = profileFacultyId.value
        }

        if (isPendingReviewResubmit.value && !form.avatar_file && props.card?.photo_path) {
            payload.photo_path = props.card.photo_path
        }

        const useReplace = isPendingReviewResubmit.value

        if (form.avatar_file) {
            const formData = new FormData()
            for (const [key, value] of Object.entries(payload)) {
                if (value !== null && value !== undefined && value !== '') {
                    formData.append(key, String(value))
                }
            }
            formData.append('photo_file', form.avatar_file)
            payload = formData
        }

        if (useReplace) {
            await libraryCardsApi.replacePendingReviewForMe(payload)
            toast.success('Đã gửi lại hồ sơ cấp thẻ thư viện (yêu cầu trước đã được thay thế).', { title: 'Thẻ thư viện' })
        } else {
            await libraryCardsApi.createForMe(payload)
            const okMsg = props.library_card_can_reapply
                ? 'Đã gửi yêu cầu cấp lại thẻ thư viện.'
                : 'Đã gửi yêu cầu cấp thẻ thư viện.'
            toast.success(okMsg, { title: 'Thẻ thư viện' })
        }
        router.reload({ only: ['card', 'library_card_can_reapply'], preserveScroll: true })
    } catch (err) {
        mapApiErrors(err)
    } finally {
        state.submitting = false
    }
}

function closeCancelConfirmModal() {
    if (state.cancelSubmitting) return
    showCancelConfirmModal.value = false
}

function openCancelConfirmModal() {
    if (!canCancelCardRequest.value || state.cancelSubmitting) return
    showCancelConfirmModal.value = true
}

async function confirmCancelCardRequest() {
    if (!canCancelCardRequest.value || state.cancelSubmitting) return
    state.cancelSubmitting = true
    try {
        await libraryCardsApi.cancelForMe()
        closeCancelConfirmModal()
        toast.success('Đã hủy yêu cầu cấp thẻ thư viện.', { title: 'Thẻ thư viện' })
        router.reload({ only: ['card', 'library_card_can_reapply'], preserveScroll: true })
    } catch (err) {
        const msg = err?.response?.data?.messages || err?.response?.data?.message || 'Không hủy được yêu cầu.'
        toast.error(msg, { title: 'Thẻ thư viện' })
    } finally {
        state.cancelSubmitting = false
    }
}

function notifyReissueActiveLibraryCard() {
    const ws = props.card?.workflow_status
    const isPickup = ws === 'pending_pickup'
    toast.info(
        isPickup
            ? 'Hồ sơ của bạn đã được duyệt và đang chờ lấy thẻ tại quầy. Nếu cần đổi thông tin hoặc làm lại thủ tục, vui lòng liên hệ thư viện và xem mục «Thủ tục làm thẻ» trong Quy định.'
            : 'Thẻ của bạn đang hiệu lực trên hệ thống. Để đổi thẻ, làm lại khi mất thẻ hoặc khi hết hạn, vui lòng làm thủ tục tại quầy thư viện theo quy định. Xem thêm tại mục «Thủ tục làm thẻ» trong Quy định.',
        { title: 'Cấp lại thẻ thư viện', duration: 10000 },
    )
}
</script>

<template>
    <ReaderLayout>
        <Head title="Dịch vụ - Thẻ thư viện" />
        <div class="mx-auto max-w-5xl space-y-4 animate-in fade-in-50 duration-500">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white">Thẻ thư viện</h1>
                    <Link
                        :href="route('reader.services')"
                        class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-800 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-100 dark:hover:bg-slate-800"
                    >
                        <Icon icon="lucide:arrow-left" class="h-4 w-4" />
                        Quay lại dịch vụ
                    </Link>
                </div>

                <div v-if="auth_required" class="mt-6 rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center dark:border-slate-700">
                    <p class="text-sm text-slate-600 dark:text-slate-300">Vui lòng đăng nhập để xem và đăng ký thẻ thư viện.</p>
                    <div class="mt-4 flex items-center justify-center gap-2">
                        <Link :href="route('login')" class="inline-flex min-h-[44px] items-center rounded-xl bg-blue-900 px-5 text-sm font-semibold text-white hover:bg-blue-800">
                            Đăng nhập
                        </Link>
                    </div>
                </div>

                <template v-else>
                    <div v-if="hasCard" class="mt-6 space-y-3">
                        <div class="relative overflow-hidden rounded-2xl border border-indigo-300/30 bg-gradient-to-br from-indigo-700 via-blue-700 to-slate-900 p-5 text-white shadow-lg shadow-blue-900/30 sm:p-6">
                            <div class="pointer-events-none absolute -right-16 -top-20 h-48 w-48 rounded-full bg-white/10 blur-2xl" />
                            <div class="pointer-events-none absolute -bottom-16 -left-20 h-56 w-56 rounded-full bg-cyan-300/10 blur-3xl" />

                            <div class="relative flex items-start justify-between gap-5">
                                <div class="min-w-0 flex-1">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-blue-100/90">{{ cardHolderTypeLabel }}</p>
                                    <p class="mt-1 truncate text-xl font-black sm:text-2xl">{{ card.card_number || '—' }}</p>
                                    <div class="mt-5 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                                        <div v-for="item in cardDetailItems" :key="item.label" class="min-w-0">
                                            <p class="text-[11px] uppercase tracking-wider text-blue-100/80">{{ item.label }}</p>
                                            <p class="truncate font-semibold">{{ item.value }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="h-24 w-20 shrink-0 overflow-hidden rounded-lg border border-white/40 bg-white/10 sm:h-28 sm:w-24">
                                    <img v-if="cardPhotoUrl" :src="cardPhotoUrl" alt="Ảnh thẻ" class="h-full w-full object-cover" @error="withFallback('/images/default-avatar.png')($event)">
                                    <div v-else class="flex h-full w-full items-center justify-center text-[10px] text-blue-100/80">No photo</div>
                                </div>
                            </div>
                        </div>
                        <div v-if="canCancelCardRequest" class="flex flex-wrap justify-end gap-2">
                            <button
                                type="button"
                                :disabled="state.cancelSubmitting"
                                class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-rose-300/80 bg-white/95 px-4 text-sm font-semibold text-rose-700 shadow-sm transition hover:bg-rose-50 disabled:pointer-events-none disabled:opacity-60 dark:border-rose-800 dark:bg-slate-900 dark:text-rose-300 dark:hover:bg-rose-950/40"
                                @click="openCancelConfirmModal"
                            >
                                <Icon icon="lucide:x-circle" class="h-4 w-4 shrink-0" aria-hidden="true" />
                                Hủy yêu cầu cấp thẻ
                            </button>
                        </div>
                        <div v-if="showReissueLibraryCardCta" class="flex flex-wrap justify-end gap-2">
                            <Link
                                :href="route('reader.regulations.card')"
                                class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                            >
                                <Icon icon="lucide:book-open" class="h-4 w-4 shrink-0" aria-hidden="true" />
                                Thủ tục làm / đổi thẻ
                            </Link>
                            <button
                                type="button"
                                class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-emerald-300/90 bg-emerald-50 px-4 text-sm font-semibold text-emerald-900 shadow-sm transition hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-100 dark:hover:bg-emerald-900/40"
                                @click="notifyReissueActiveLibraryCard"
                            >
                                <Icon icon="lucide:refresh-ccw" class="h-4 w-4 shrink-0" aria-hidden="true" />
                                Cấp lại thẻ thư viện
                            </button>
                        </div>
                    </div>

                    <div v-if="!hasCard" class="mt-6 rounded-xl border border-dashed border-slate-300 px-4 py-4 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                        <template v-if="library_card_can_reapply">
                            Bạn chưa có thẻ thư viện đang hiệu lực nhưng đã từng có hồ sơ bị gỡ. Kiểm tra thông tin bên dưới và ảnh 3x4, sau đó bấm
                            <strong>Cấp lại thẻ thư viện</strong> để gửi yêu cầu mới.
                        </template>
                        <template v-else>
                            Bạn chưa có thẻ thư viện. Vui lòng kiểm tra đầy đủ thông tin bên dưới (Mã định danh, Tên hiển thị, Khoa/Niên khóa/Lớp nếu có) và ảnh 3x4, sau đó bấm
                            <strong>Cấp thẻ thư viện</strong> để gửi yêu cầu.
                        </template>
                    </div>

                    <div
                        v-if="isPendingReviewResubmit"
                        class="mt-6 rounded-xl border border-dashed border-indigo-300/80 bg-indigo-50/40 px-4 py-4 text-sm text-slate-700 dark:border-indigo-800/60 dark:bg-indigo-950/25 dark:text-slate-200"
                    >
                        Hồ sơ đang <strong>chờ duyệt</strong>. Bạn có thể chỉnh tên và ảnh 3×4 bên dưới, rồi bấm
                        <strong>Cấp lại thẻ thư viện</strong> — hệ thống sẽ hủy yêu cầu cũ và tạo yêu cầu mới trong một bước (tránh trùng với thủ thư đang duyệt cùng lúc).
                    </div>

                    <div v-if="showApplyForm" class="mt-6 space-y-4">
                        <div v-if="errors.general" class="rounded-lg border border-rose-300 bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:border-rose-800 dark:bg-rose-900/20 dark:text-rose-300">
                            {{ errors.general }}
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Mã định danh</label>
                                <input
                                    :value="profileCode || 'Chưa cập nhật'"
                                    type="text"
                                    readonly
                                    class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                                >
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tên</label>
                                <input
                                    v-model="form.full_name"
                                    type="text"
                                    maxlength="255"
                                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                                >
                                <p v-if="errors.full_name" class="mt-1 text-xs text-rose-600 dark:text-rose-300">{{ errors.full_name }}</p>
                            </div>

                            <div v-if="isStudent || isTeacher">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Khoa</label>
                                <input
                                    :value="facultyLabel"
                                    type="text"
                                    readonly
                                    class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                                >
                            </div>

                            <div v-if="isStudent">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Niên khóa</label>
                                <input
                                    :value="periodLabel"
                                    type="text"
                                    readonly
                                    class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                                >
                            </div>

                            <div v-if="isStudent">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Lớp</label>
                                <input
                                    :value="profileClassCode || 'Chưa cập nhật'"
                                    type="text"
                                    readonly
                                    class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                                >
                            </div>
                        </div>

                        <p v-if="isExternalReader" class="text-xs text-slate-500 dark:text-slate-400">
                            Tài khoản bạn đọc ngoài chỉ yêu cầu mã định danh và thông tin cá nhân hiện có trên tài khoản.
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Mã định danh và thông tin học thuật được đồng bộ từ tài khoản; riêng trường <span class="font-semibold">Tên</span> có thể chỉnh trực tiếp để hiển thị trên thẻ.
                        </p>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ảnh đại diện 3x4</p>
                            <div class="mt-3 flex flex-wrap items-start gap-4">
                                <div class="h-24 w-20 overflow-hidden rounded-lg border border-slate-300 bg-white dark:border-slate-600 dark:bg-slate-900">
                                    <img
                                        v-if="state.avatarPreview"
                                        :src="state.avatarPreview"
                                        alt="Ảnh đại diện"
                                        class="h-full w-full object-cover"
                                        @error="withFallback('/images/default-avatar.png')($event)"
                                    >
                                    <div v-else class="flex h-full w-full items-center justify-center text-[11px] text-slate-400">
                                        Chưa có ảnh
                                    </div>
                                </div>
                                <div class="min-w-[240px] flex-1 space-y-2">
                                    <input
                                        ref="avatarInput"
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.webp"
                                        class="hidden"
                                        @change="onAvatarChange"
                                    >
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex min-h-[40px] items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-800"
                                            @click="triggerAvatarPicker"
                                        >
                                            <Icon icon="lucide:camera" class="h-3.5 w-3.5" />
                                            {{ state.avatarPreview ? 'Đổi ảnh' : 'Chọn ảnh' }}
                                        </button>
                                        <button
                                            v-if="state.avatarPreview"
                                            type="button"
                                            class="inline-flex min-h-[40px] items-center gap-1.5 rounded-lg border border-blue-300 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/45"
                                            @click="openAvatarPreviewModal"
                                        >
                                            <Icon icon="lucide:image" class="h-3.5 w-3.5" />
                                            Xem ảnh
                                        </button>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{
                                            state.avatarFileName
                                                || (isPendingReviewResubmit
                                                    ? 'Có thể giữ ảnh hiện tại hoặc chọn ảnh mới. Bấm Cấp lại thẻ thư viện để gửi lại hồ sơ.'
                                                    : library_card_can_reapply
                                                        ? 'Chọn ảnh rõ mặt, tỷ lệ 3x4. Ảnh này sẽ được dùng khi bấm Cấp lại thẻ thư viện.'
                                                        : 'Chọn ảnh rõ mặt, tỷ lệ 3x4. Ảnh này sẽ được dùng ngay khi bấm Cấp thẻ thư viện.')
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-if="errors.avatar" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                            {{ errors.avatar }}
                        </div>
                        <div v-if="!canSubmitApply" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                            Thiếu thông tin tài khoản: {{ missingProfileFields.join(', ') }}. Vui lòng cập nhật trong mục Tài khoản trước khi cấp thẻ.
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                :disabled="state.submitting || !canSubmitApply"
                                class="inline-flex min-h-[44px] items-center rounded-xl bg-blue-900 px-5 text-sm font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60"
                                @click="submitApply"
                            >
                                <Icon v-if="state.submitting" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" />
                                {{ applyPrimaryLabel }}
                            </button>
                            <Link
                                :href="route('reader.profile')"
                                class="inline-flex min-h-[44px] items-center rounded-xl border border-slate-200 px-5 text-sm font-semibold text-slate-800 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-100 dark:hover:bg-slate-800"
                            >
                                Cập nhật thông tin cá nhân
                            </Link>
                        </div>
                    </div>
                </template>
            </article>
        </div>
        <!-- Xác nhận hủy yêu cầu cấp thẻ (không dùng window.confirm) -->
        <div
            v-if="showCancelConfirmModal"
            class="fixed inset-0 z-[121] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="cancel-card-request-title"
        >
            <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-[2px]" @click="closeCancelConfirmModal" />
            <div
                class="relative w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl ring-1 ring-slate-200/80 dark:border-slate-600 dark:bg-slate-900 dark:ring-slate-700/80"
            >
                <div class="border-b border-rose-200/80 bg-rose-50/90 px-5 py-4 dark:border-rose-900/50 dark:bg-rose-950/40">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-200">
                            <Icon icon="lucide:alert-circle" class="h-6 w-6" aria-hidden="true" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 id="cancel-card-request-title" class="text-base font-bold text-slate-900 dark:text-white">
                                Xác nhận hủy yêu cầu
                            </h2>
                            <p class="mt-1 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                                Bạn có chắc muốn hủy yêu cầu cấp thẻ thư viện? Hồ sơ sẽ được gỡ khỏi danh sách chờ và bạn có thể gửi yêu cầu mới sau này.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 rounded-lg p-2 text-slate-500 transition hover:bg-white/80 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                            :disabled="state.cancelSubmitting"
                            aria-label="Đóng"
                            @click="closeCancelConfirmModal"
                        >
                            <Icon icon="lucide:x" class="h-5 w-5" />
                        </button>
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-2 px-5 py-4 sm:flex-row sm:justify-end sm:gap-3">
                    <button
                        type="button"
                        class="inline-flex min-h-[44px] w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700 sm:w-auto"
                        :disabled="state.cancelSubmitting"
                        @click="closeCancelConfirmModal"
                    >
                        Giữ yêu cầu
                    </button>
                    <button
                        type="button"
                        class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-rose-500 disabled:opacity-60 dark:bg-rose-700 dark:hover:bg-rose-600 sm:w-auto"
                        :disabled="state.cancelSubmitting"
                        @click="confirmCancelCardRequest"
                    >
                        <Icon v-if="state.cancelSubmitting" icon="lucide:loader-2" class="h-4 w-4 shrink-0 animate-spin" aria-hidden="true" />
                        <Icon v-else icon="lucide:trash-2" class="h-4 w-4 shrink-0" aria-hidden="true" />
                        {{ state.cancelSubmitting ? 'Đang hủy…' : 'Xác nhận hủy' }}
                    </button>
                </div>
            </div>
        </div>
        <div v-if="showAvatarPreviewModal && state.avatarPreview" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60" @click="closeAvatarPreviewModal" />
            <div class="relative w-full max-w-md rounded-xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                <div class="mb-3 flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Ảnh đại diện 3x4</h4>
                    <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="closeAvatarPreviewModal">
                        <Icon icon="lucide:x" class="h-4 w-4" />
                    </button>
                </div>
                <div class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                    <img :src="state.avatarPreview" alt="Ảnh đại diện 3x4" class="h-[320px] w-full object-contain bg-slate-50 dark:bg-slate-800" @error="withFallback('/images/default-avatar.png')($event)" />
                </div>
                <div class="mt-3 flex justify-end">
                    <button
                        type="button"
                        class="inline-flex min-h-[36px] items-center gap-1.5 rounded-lg border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/35 dark:text-blue-300 dark:hover:bg-blue-900/50"
                        @click="closeAvatarPreviewModal(); triggerAvatarPicker()"
                    >
                        <Icon icon="lucide:camera" class="h-3.5 w-3.5" />
                        Đổi ảnh
                    </button>
                </div>
            </div>
        </div>
    </ReaderLayout>
</template>
