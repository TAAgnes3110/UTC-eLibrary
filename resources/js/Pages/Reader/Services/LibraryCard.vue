<script setup>
import { computed, reactive, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import { libraryCardsApi } from '@/api/libraryCards'
import { profileApi } from '@/api/profile'
import { extractLaravelValidationErrors } from '@/utils/laravelApiError'
import { toast } from '@/store/toast'
import { useImageFallback } from '@/composables/useImageFallback'

const props = defineProps({
    auth_required: { type: Boolean, default: false },
    card: { type: Object, default: null },
    profile: { type: Object, default: null },
    faculties: { type: Array, default: () => [] },
    periods: { type: Array, default: () => [] },
})

const form = reactive({
    avatar_file: null,
    full_name: String(props.profile?.name || '').trim(),
})
const errors = reactive({})
const state = reactive({
    submitting: false,
    avatarPreview: props.profile?.avatar ?? '',
    avatarFileName: '',
})
const avatarInput = ref(null)
const showAvatarPreviewModal = ref(false)
const { withFallback } = useImageFallback()

const role = computed(() => String(props.profile?.user_type || '').trim().toUpperCase())
const isStudent = computed(() => role.value === 'STUDENT')
const isTeacher = computed(() => role.value === 'TEACHER')
const isExternalReader = computed(() => !isStudent.value && !isTeacher.value)
const hasCard = computed(() => props.card !== null)
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

        await libraryCardsApi.createForMe(payload)
        toast.success('Đã gửi yêu cầu cấp thẻ thư viện.', { title: 'Thẻ thư viện' })
        router.reload({ only: ['card'], preserveScroll: true })
    } catch (err) {
        mapApiErrors(err)
    } finally {
        state.submitting = false
    }
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
                    <div v-if="hasCard" class="mt-6">
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
                    </div>

                    <div v-else class="mt-6 rounded-xl border border-dashed border-slate-300 px-4 py-4 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                        Bạn chưa có thẻ thư viện. Vui lòng kiểm tra đầy đủ thông tin bên dưới (Mã định danh, Tên hiển thị, Khoa/Niên khóa/Lớp nếu có) và ảnh 3x4, sau đó bấm
                        <strong>Cấp thẻ thư viện</strong> để gửi yêu cầu.
                    </div>

                    <div v-if="!hasCard" class="mt-6 space-y-4">
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
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ state.avatarFileName || 'Chọn ảnh rõ mặt, tỷ lệ 3x4. Ảnh này sẽ được dùng ngay khi bấm Cấp thẻ thư viện.' }}</p>
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
                                Cấp thẻ thư viện
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
