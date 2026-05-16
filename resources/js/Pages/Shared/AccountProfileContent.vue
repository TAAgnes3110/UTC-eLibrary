<script setup>
import { Link, usePage, useForm, router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { profileApi } from '@/api/profile'
import { fetchMasterDataPayload } from '@/api/masterData'
import { applyLaravelErrorsToInertiaForm } from '@/utils/inertiaFormErrors'
import { getLaravelErrorMessage } from '@/utils/laravelApiError'
import { toast } from '@/store/toast'
import { useImageFallback } from '@/composables/useImageFallback'
import { resetFileInput } from '@/utils/resetFileInput'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const profile = ref(null)
const effectiveUser = computed(() => ({ ...(user.value || {}), ...(profile.value || {}) }))

const profileForm = useForm({
    code: '',
    name: user.value?.name || '',
    email: user.value?.email || '',
    phone: user.value?.phone || '',
    date_of_birth: user.value?.date_of_birth || '',
    gender: user.value?.gender || '',
    address: user.value?.address || '',
})

const profileSaved = ref(false)
const profileEditing = ref(false)
const profileSaving = ref(false)
const profileLoadError = ref('')

const syncProfileForm = (source) => {
    profileForm.code = source?.code != null && source?.code !== '' ? String(source.code) : ''
    profileForm.name = source?.name || ''
    profileForm.email = source?.email || ''
    profileForm.phone = source?.phone || ''
    profileForm.date_of_birth = source?.date_of_birth || ''
    profileForm.gender = source?.gender || ''
    profileForm.address = source?.address || ''
    profileForm.clearErrors()
}

const loadProfile = async () => {
    profileLoadError.value = ''
    try {
        const response = await profileApi.get()
        profile.value = response?.data || null
        avatarLoadFailed.value = false
        syncProfileForm(profile.value || user.value)
    } catch {
        profileLoadError.value = 'Không thể tải thông tin hồ sơ. Vui lòng thử lại.'
    }
}

const saveProfile = async () => {
    profileSaving.value = true
    profileForm.clearErrors()
    try {
        const payload = {
            name: profileForm.name,
            email: profileForm.email,
            phone: profileForm.phone || null,
            date_of_birth: profileForm.date_of_birth || null,
            gender: profileForm.gender || null,
            address: profileForm.address || null,
        }
        if (isStaff.value) {
            payload.code = profileForm.code.trim()
        }
        const response = await profileApi.update(payload)
        profile.value = response?.data || null
        syncProfileForm(profile.value || user.value)
        profileSaved.value = true
        profileEditing.value = false
        toast.success('Đã cập nhật hồ sơ cá nhân.', { title: 'Tài khoản' })
        setTimeout(() => (profileSaved.value = false), 3000)
        router.reload({ only: ['auth'] })
    } catch (error) {
        applyLaravelErrorsToInertiaForm(profileForm, error)
        toast.error(getLaravelErrorMessage(error, 'Không thể cập nhật hồ sơ. Vui lòng kiểm tra dữ liệu.'), { title: 'Tài khoản' })
    } finally {
        profileSaving.value = false
    }
}

const cancelEdit = () => {
    profileEditing.value = false
    syncProfileForm(profile.value || user.value)
}

const avatarInput = ref(null)
const avatarPreview = ref(null)
const avatarLoadFailed = ref(false)
const avatarUploading = ref(false)
const displayAvatar = computed(() => {
    if (avatarPreview.value) return avatarPreview.value
    if (avatarLoadFailed.value) return null
    return effectiveUser.value?.avatar || null
})

const triggerAvatarUpload = () => {
    avatarInput.value?.click()
}

const resetAvatarInput = (event) => {
    if (event?.target) {
        event.target.value = ''
    }
}

const handleAvatarChange = async (event) => {
    const file = event?.target?.files?.[0]
    if (!file || avatarUploading.value) {
        resetAvatarInput(event)
        return
    }

    if (!file.type?.startsWith('image/')) {
        toast.error('Vui lòng chọn file ảnh hợp lệ (JPG, PNG hoặc WEBP).', { title: 'Ảnh đại diện' })
        resetAvatarInput(event)
        return
    }

    if (file.size > 5 * 1024 * 1024) {
        toast.error('Dung lượng ảnh tối đa 5MB.', { title: 'Ảnh đại diện' })
        resetAvatarInput(event)
        return
    }

    avatarLoadFailed.value = false
    const reader = new FileReader()
    reader.onload = (ev) => {
        avatarPreview.value = ev.target.result
    }
    reader.readAsDataURL(file)

    avatarUploading.value = true
    try {
        const formData = new FormData()
        formData.append('avatar', file)
        const response = await profileApi.updateAvatar(formData)
        profile.value = response?.data || profile.value
        avatarPreview.value = null
        avatarLoadFailed.value = false
        toast.success('Đã cập nhật ảnh đại diện.', { title: 'Tài khoản' })
        router.reload({ only: ['auth'] })
    } catch (error) {
        avatarPreview.value = null
        toast.error(getLaravelErrorMessage(error, 'Không thể cập nhật ảnh đại diện. Vui lòng thử lại.'), { title: 'Tài khoản' })
    } finally {
        avatarUploading.value = false
        resetAvatarInput(event)
    }
}

const handleAvatarError = () => {
    avatarLoadFailed.value = true
}

const genderLabel = (g) => {
    const map = { male: 'Nam', female: 'Nữ', other: 'Khác' }
    return map[g] || 'Chưa cập nhật'
}

const roleColor = (role) => {
    const r = typeof role === 'string' ? role : role?.name
    const map = {
        'SUPER_ADMIN': 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
        'ADMIN': 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
        'LIBRARIAN': 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
        'TEACHER': 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
        'STUDENT': 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
        'MEMBER': 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
    }
    return map[r] || map['MEMBER']
}

const roleLabel = (role) => {
    const r = typeof role === 'string' ? role : role?.name
    const map = {
        'SUPER_ADMIN': 'Quản trị viên cấp cao',
        'ADMIN': 'Quản trị viên',
        'LIBRARIAN': 'Thủ thư',
        'TEACHER': 'Giáo viên',
        'STUDENT': 'Sinh viên',
        'MEMBER': 'Thành viên',
    }
    return map[r] || r
}

const isStaff = computed(() => page.props.auth?.is_staff === true)
/** Faculty/class in profile: STUDENT & TEACHER only (not staff or external MEMBER). */
const PROFILE_FACULTY_CLASS_USER_TYPES = new Set(['STUDENT', 'TEACHER'])
const showFacultyClassInProfile = computed(() => {
    if (isStaff.value) return false
    return PROFILE_FACULTY_CLASS_USER_TYPES.has(user.value?.user_type)
})
const isStudentProfile = computed(() => effectiveUser.value?.user_type === 'STUDENT')
/** Profile update request UI: readers only (see shared auth.is_staff). */
const showProfileUpdateRequestSection = computed(() => !isStaff.value)

const infoItems = computed(() => {
    const rows = [
    {
        icon: 'lucide:hash',
        label: 'Mã định danh',
        value: profile.value?.code || user.value?.code || 'Chưa cập nhật',
    },
    ]
    if (showFacultyClassInProfile.value) {
        rows.push({ icon: 'lucide:building-2', label: 'Khoa', value: profile.value?.faculty?.name || 'Chưa cập nhật' })
        if (isStudentProfile.value) {
            rows.push(
                { icon: 'lucide:calendar-range', label: 'Niên khóa', value: profile.value?.period?.name || 'Chưa cập nhật' },
                { icon: 'lucide:graduation-cap', label: 'Lớp', value: profile.value?.class_code || 'Chưa cập nhật' },
            )
        }
    }
    rows.push(
    { icon: 'lucide:mail', label: 'Email', value: profile.value?.email || user.value?.email || 'Chưa cập nhật' },
    { icon: 'lucide:phone', label: 'Số điện thoại', value: profile.value?.phone || user.value?.phone || 'Chưa cập nhật' },
    { icon: 'lucide:calendar', label: 'Ngày sinh', value: profile.value?.date_of_birth || user.value?.date_of_birth || 'Chưa cập nhật' },
    { icon: 'lucide:user', label: 'Giới tính', value: genderLabel(profile.value?.gender || user.value?.gender) },
    { icon: 'lucide:map-pin', label: 'Địa chỉ', value: profile.value?.address || user.value?.address || 'Chưa cập nhật' },
    )
    return rows
})


const profileUpdateRequestSaving = ref(false)
const profileUpdateRequestSubmitted = ref(false)
const faculties = ref([])
const periods = ref([])
const proofFileName = ref('')
const proofPreviewUrl = ref('')
const showProofPreviewModal = ref(false)
const proofImageInput = ref(null)
const { withFallback } = useImageFallback()

const profileUpdateRequestForm = useForm({
    requested_code: '',
    requested_user_type: '',
    requested_faculty_id: '',
    requested_period_id: '',
    requested_class_code: '',
    reason: '',
    proof_image: null,
})
const selectedRequestedUserType = computed(() => {
    const requested = normalizeString(profileUpdateRequestForm.requested_user_type)
    if (requested) return requested
    return normalizeString(effectiveUser.value?.user_type) || 'MEMBER'
})
const showRequestedFacultyField = computed(() => ['STUDENT', 'TEACHER'].includes(selectedRequestedUserType.value))
const showRequestedPeriodField = computed(() => selectedRequestedUserType.value === 'STUDENT')
const showRequestedClassField = computed(() => selectedRequestedUserType.value === 'STUDENT')
const READER_CONFIRM_TYPES = new Set(['STUDENT', 'TEACHER'])

const applyDefaultRequestedUserType = () => {
    const currentUserType = normalizeString(effectiveUser.value?.user_type)
    if (!currentUserType || !READER_CONFIRM_TYPES.has(currentUserType)) return
    const currentRequested = normalizeString(profileUpdateRequestForm.requested_user_type)
    if (!currentRequested) {
        profileUpdateRequestForm.requested_user_type = currentUserType
    }
}

const loadFaculties = async () => {
    try {
        const payload = await fetchMasterDataPayload()
        faculties.value = Array.isArray(payload?.faculties) ? payload.faculties : []
        periods.value = Array.isArray(payload?.periods) ? payload.periods : []
    } catch {
        faculties.value = []
        periods.value = []
    }
}

const onProofImageChange = (event) => {
    const file = event.target?.files?.[0] || null
    if (!file) {
        // Đồng bộ trạng thái với input file mặc định: Cancel => không còn file được chọn.
        if (proofPreviewUrl.value) {
            URL.revokeObjectURL(proofPreviewUrl.value)
            proofPreviewUrl.value = ''
        }
        profileUpdateRequestForm.proof_image = null
        proofFileName.value = ''
        showProofPreviewModal.value = false
        return
    }
    if (proofPreviewUrl.value) {
        URL.revokeObjectURL(proofPreviewUrl.value)
        proofPreviewUrl.value = ''
    }
    profileUpdateRequestForm.proof_image = file
    proofFileName.value = file?.name || ''
    if (file) {
        proofPreviewUrl.value = URL.createObjectURL(file)
    }
}

const openProofPreviewModal = () => {
    if (!proofPreviewUrl.value) return
    showProofPreviewModal.value = true
}

const closeProofPreviewModal = () => {
    showProofPreviewModal.value = false
}

const triggerProofImageChange = () => {
    proofImageInput.value?.click()
}

const normalizeString = (value) => {
    const v = value == null ? '' : String(value).trim()
    return v === '' ? null : v
}

const normalizeNumber = (value) => {
    if (value == null || value === '') return null
    const n = Number(value)
    return Number.isFinite(n) ? n : null
}

watch(
    () => profileUpdateRequestForm.requested_user_type,
    (nextType) => {
        const targetType = normalizeString(nextType)
        if (targetType === 'TEACHER') {
            profileUpdateRequestForm.requested_period_id = ''
            profileUpdateRequestForm.requested_class_code = ''
        }
        if (!targetType) {
            const currentType = normalizeString(effectiveUser.value?.user_type)
            if (currentType === 'TEACHER') {
                profileUpdateRequestForm.requested_period_id = ''
                profileUpdateRequestForm.requested_class_code = ''
            }
        }
    },
)

watch(
    () => effectiveUser.value?.user_type,
    () => {
        applyDefaultRequestedUserType()
    },
)

const submitProfileUpdateRequest = async () => {
    profileUpdateRequestSaving.value = true
    profileUpdateRequestForm.clearErrors()
    try {
        const currentCode = normalizeString(effectiveUser.value?.code)
        const currentUserType = normalizeString(effectiveUser.value?.user_type)
        const currentFacultyId = normalizeNumber(effectiveUser.value?.faculty_id)
        const currentPeriodId = normalizeNumber(effectiveUser.value?.period_id)
        const currentClassCode = normalizeString(effectiveUser.value?.class_code)
        const nextCode = normalizeString(profileUpdateRequestForm.requested_code)
        const nextUserType = normalizeString(profileUpdateRequestForm.requested_user_type)
        const nextFacultyId = normalizeNumber(profileUpdateRequestForm.requested_faculty_id)
        const nextPeriodId = normalizeNumber(profileUpdateRequestForm.requested_period_id)
        const nextClassCode = normalizeString(profileUpdateRequestForm.requested_class_code)
        const hasChange = (
            (nextUserType !== null && nextUserType !== currentUserType)
            || (nextCode !== null && nextCode !== currentCode)
            || (nextFacultyId !== null && nextFacultyId !== currentFacultyId)
            || (nextPeriodId !== null && nextPeriodId !== currentPeriodId)
            || (nextClassCode !== null && nextClassCode !== currentClassCode)
        )
        if (!hasChange) {
            const msg = 'Bạn chưa thay đổi thông tin nào (loại xác nhận/mã định danh/khoa/niên khóa/lớp).'
            profileUpdateRequestForm.setError('requested_code', msg)
            toast.error(msg, { title: 'Cập nhật hồ sơ' })
            return
        }

        const formData = new FormData()
        if (profileUpdateRequestForm.requested_code) {
            formData.append('requested_code', profileUpdateRequestForm.requested_code)
        }
        if (profileUpdateRequestForm.requested_user_type) {
            formData.append('requested_user_type', profileUpdateRequestForm.requested_user_type)
        }
        if (profileUpdateRequestForm.requested_faculty_id) {
            formData.append('requested_faculty_id', profileUpdateRequestForm.requested_faculty_id)
        }
        if (profileUpdateRequestForm.requested_period_id) {
            formData.append('requested_period_id', profileUpdateRequestForm.requested_period_id)
        }
        if (profileUpdateRequestForm.requested_class_code) {
            formData.append('requested_class_code', profileUpdateRequestForm.requested_class_code)
        }
        if (profileUpdateRequestForm.reason) {
            formData.append('reason', profileUpdateRequestForm.reason)
        }
        if (profileUpdateRequestForm.proof_image) {
            formData.append('proof_image', profileUpdateRequestForm.proof_image)
        }

        await profileApi.submitProfileUpdateRequest(formData)
        profileUpdateRequestForm.reset()
        applyDefaultRequestedUserType()
        proofFileName.value = ''
        if (proofPreviewUrl.value) {
            URL.revokeObjectURL(proofPreviewUrl.value)
            proofPreviewUrl.value = ''
        }
        resetFileInput(proofImageInput.value)
        profileUpdateRequestSubmitted.value = true
        toast.success('Đã gửi phiếu cập nhật thông tin. Vui lòng chờ duyệt.', { title: 'Cập nhật hồ sơ' })
        setTimeout(() => (profileUpdateRequestSubmitted.value = false), 3000)
    } catch (error) {
        applyLaravelErrorsToInertiaForm(profileUpdateRequestForm, error)
        toast.error(getLaravelErrorMessage(error, 'Gửi phiếu cập nhật thất bại. Vui lòng kiểm tra thông tin.'), { title: 'Cập nhật hồ sơ' })
    } finally {
        profileUpdateRequestSaving.value = false
    }
}

onMounted(() => {
    syncProfileForm(user.value)
    applyDefaultRequestedUserType()
    if (showProfileUpdateRequestSection.value) {
        loadFaculties()
    }
    loadProfile()
})

onBeforeUnmount(() => {
    if (proofPreviewUrl.value) {
        URL.revokeObjectURL(proofPreviewUrl.value)
        proofPreviewUrl.value = ''
    }
})
</script>

<template>
        <div class="mx-auto max-w-6xl space-y-6">
            <section class="relative overflow-hidden rounded-3xl border border-slate-200/70 bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 px-6 py-7 text-white shadow-2xl shadow-blue-900/30 ring-1 ring-white/20 dark:border-slate-700">
                <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-24 -left-16 h-72 w-72 rounded-full bg-black/20 blur-3xl"></div>
                <div class="pointer-events-none absolute inset-0 opacity-35 [background-image:radial-gradient(rgba(255,255,255,0.22)_1px,transparent_1px)] [background-size:18px_18px]"></div>
                <div class="relative flex flex-col gap-5">
                    <div class="flex items-center gap-4 sm:gap-5">
                        <button
                            type="button"
                            class="group relative block shrink-0"
                            @click="triggerAvatarUpload"
                        >
                            <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-2xl border-4 border-white/80 bg-slate-900 text-3xl font-black shadow-lg shadow-black/20 sm:h-28 sm:w-28 sm:text-4xl">
                                <img v-if="displayAvatar" :src="displayAvatar" alt="Avatar" class="h-full w-full object-cover" @error="handleAvatarError" />
                                <span v-else>{{ effectiveUser?.name?.charAt(0)?.toUpperCase() || 'A' }}</span>
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center rounded-2xl bg-black/0 transition group-hover:bg-black/35">
                                <Icon icon="lucide:camera" class="h-5 w-5 text-white opacity-0 transition group-hover:opacity-100" />
                            </div>
                            <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="handleAvatarChange" />
                        </button>
                        <div class="min-w-0 space-y-1">
                            <h1 class="truncate text-2xl font-black sm:text-3xl">{{ effectiveUser?.name || 'Người dùng' }}</h1>
                            <p class="truncate text-sm text-blue-100">{{ effectiveUser?.email || 'Chưa có email' }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span
                                    v-for="role in (effectiveUser?.roles || [])"
                                    :key="typeof role === 'string' ? role : role?.name"
                                    :class="[roleColor(role), 'rounded-lg px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider ring-1 ring-white/10']"
                                >
                                    {{ roleLabel(role) }}
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 rounded-lg bg-white/15 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                                    {{ effectiveUser?.is_active === false ? 'Tạm ngưng' : 'Đang hoạt động' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-5">
                <div v-if="!isStaff" class="space-y-6 lg:col-span-2">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-md shadow-slate-200/30 transition hover:-translate-y-0.5 hover:shadow-lg dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/25">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Thông tin tóm tắt</h3>
                            <button
                                v-if="!profileEditing"
                                type="button"
                                @click="profileEditing = true"
                                class="inline-flex items-center gap-1 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-md shadow-blue-700/20 hover:brightness-110"
                            >
                                <Icon icon="lucide:pen-line" class="h-3.5 w-3.5" />
                                Chỉnh sửa
                            </button>
                        </div>
                        <div class="space-y-3">
                            <div
                                v-for="(item, i) in infoItems"
                                :key="i"
                                class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 transition hover:shadow-sm dark:border-slate-700 dark:bg-slate-800"
                            >
                                <div class="rounded-lg bg-white p-2 text-slate-600 shadow-sm dark:bg-slate-900 dark:text-slate-300">
                                    <Icon :icon="item.icon" class="h-4 w-4" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ item.label }}</p>
                                    <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ item.value }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div :class="isStaff ? 'space-y-4 lg:col-span-5' : 'space-y-4 lg:col-span-3'">
                    <div
                        v-if="profileLoadError"
                        class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-400"
                    >
                        {{ profileLoadError }}
                    </div>
                    <div
                        v-if="profileSaved"
                        class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400"
                    >
                        Cập nhật hồ sơ thành công.
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white shadow-md shadow-slate-200/30 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/25">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ profileEditing ? 'Chỉnh sửa hồ sơ cá nhân' : 'Thông tin chi tiết' }}</h3>
                            <button
                                v-if="isStaff && !profileEditing"
                                type="button"
                                @click="profileEditing = true"
                                class="inline-flex min-h-[44px] min-w-[44px] items-center gap-1 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-md shadow-blue-700/20 hover:brightness-110 sm:min-h-0 sm:min-w-0 sm:py-1.5"
                            >
                                <Icon icon="lucide:pen-line" class="h-3.5 w-3.5" />
                                Chỉnh sửa
                            </button>
                        </div>

                        <form v-if="profileEditing" class="space-y-5 p-6" @submit.prevent="saveProfile">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div v-if="isStaff">
                                    <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Mã định danh</label>
                                    <input
                                        v-model="profileForm.code"
                                        type="text"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        required
                                        maxlength="12"
                                        class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-medium text-slate-900 outline-none transition focus:-translate-y-0.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                    <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">9–12 chữ số, không trùng mã tài khoản khác.</p>
                                    <p v-if="profileForm.errors.code" class="mt-1 text-xs font-medium text-red-500">{{ profileForm.errors.code }}</p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Họ và tên</label>
                                    <input v-model="profileForm.name" type="text" required class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-medium text-slate-900 outline-none transition focus:-translate-y-0.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
                                    <p v-if="profileForm.errors.name" class="mt-1 text-xs font-medium text-red-500">{{ profileForm.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Email</label>
                                    <input v-model="profileForm.email" type="email" required class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-medium text-slate-900 outline-none transition focus:-translate-y-0.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
                                    <p v-if="profileForm.errors.email" class="mt-1 text-xs font-medium text-red-500">{{ profileForm.errors.email }}</p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Số điện thoại</label>
                                    <input v-model="profileForm.phone" type="tel" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-medium text-slate-900 outline-none transition focus:-translate-y-0.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
                                    <p v-if="profileForm.errors.phone" class="mt-1 text-xs font-medium text-red-500">{{ profileForm.errors.phone }}</p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Ngày sinh</label>
                                    <input v-model="profileForm.date_of_birth" type="date" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-medium text-slate-900 outline-none transition focus:-translate-y-0.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 [color-scheme:dark]" />
                                    <p v-if="profileForm.errors.date_of_birth" class="mt-1 text-xs font-medium text-red-500">{{ profileForm.errors.date_of_birth }}</p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Giới tính</label>
                                    <select v-model="profileForm.gender" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-medium text-slate-900 outline-none transition focus:-translate-y-0.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                        <option value="">Chưa chọn</option>
                                        <option value="male">Nam</option>
                                        <option value="female">Nữ</option>
                                        <option value="other">Khác</option>
                                    </select>
                                    <p v-if="profileForm.errors.gender" class="mt-1 text-xs font-medium text-red-500">{{ profileForm.errors.gender }}</p>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Địa chỉ</label>
                                    <textarea v-model="profileForm.address" rows="3" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 outline-none transition focus:-translate-y-0.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"></textarea>
                                    <p v-if="profileForm.errors.address" class="mt-1 text-xs font-medium text-red-500">{{ profileForm.errors.address }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                                <button type="button" @click="cancelEdit" class="h-10 rounded-xl border border-slate-200 px-5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                    Hủy
                                </button>
                                <button type="submit" :disabled="profileSaving" class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-5 text-sm font-semibold text-white shadow-md shadow-blue-700/20 hover:brightness-110 disabled:opacity-60">
                                    <Icon v-if="profileSaving" icon="lucide:loader-2" class="h-4 w-4 animate-spin" />
                                    Lưu thay đổi
                                </button>
                            </div>
                        </form>

                        <div v-else class="grid gap-4 p-6 sm:grid-cols-2">
                            <div v-if="isStaff" class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Mã định danh</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ effectiveUser?.code || 'Chưa cập nhật' }}</p>
                            </div>
                            <div v-if="showFacultyClassInProfile" class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Khoa</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ effectiveUser?.faculty?.name || 'Chưa cập nhật' }}</p>
                            </div>
                            <div v-if="isStudentProfile" class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Niên khóa</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ effectiveUser?.period?.name || 'Chưa cập nhật' }}</p>
                            </div>
                            <div v-if="isStudentProfile" class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Lớp</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ effectiveUser?.class_code || 'Chưa cập nhật' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Họ và tên</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ effectiveUser?.name || 'Chưa cập nhật' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Email</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ effectiveUser?.email || 'Chưa cập nhật' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Số điện thoại</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ effectiveUser?.phone || 'Chưa cập nhật' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Ngày sinh</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ effectiveUser?.date_of_birth || 'Chưa cập nhật' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Giới tính</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ genderLabel(effectiveUser?.gender) }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Địa chỉ</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ effectiveUser?.address || 'Chưa cập nhật' }}</p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="showProfileUpdateRequestSection"
                        class="rounded-2xl border border-slate-200 bg-white shadow-md shadow-slate-200/30 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/25"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Yêu cầu thay đổi thông tin</h3>
                            </div>
                            <Link
                                :href="route('reader.profile-update-requests')"
                                class="inline-flex min-h-[44px] min-w-[44px] items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                            >
                                <Icon icon="lucide:history" class="h-3.5 w-3.5" />
                                Xem lịch sử ở trang riêng
                            </Link>
                        </div>
                        <div
                            v-if="profileUpdateRequestSubmitted"
                            class="mx-6 mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300"
                        >
                            Đã gửi yêu cầu cập nhật. Bạn có thể theo dõi trạng thái ở trang lịch sử.
                        </div>
                        <div class="mx-6 mt-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-200">
                            <p>
                                <span class="font-bold">Lưu ý:</span> Thông tin cá nhân cơ bản có thể tự sửa ở phần
                                <span class="font-semibold">Chỉnh sửa hồ sơ</span> phía trên; riêng loại bạn đọc, mã định danh, khoa/niên khóa/lớp cần gửi yêu cầu duyệt bằng biểu mẫu bên dưới.
                            </p>
                        </div>
                        <form class="space-y-4 p-6" @submit.prevent="submitProfileUpdateRequest">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Xác nhận loại bạn đọc</label>
                                    <select v-model="profileUpdateRequestForm.requested_user_type" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-medium text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                        <option value="">-- Chọn loại xác nhận --</option>
                                        <option value="STUDENT">Sinh viên</option>
                                        <option value="TEACHER">Giáo viên</option>
                                    </select>
                                    <p class="mt-1 text-[11px] text-slate-500">Hiện tại: {{ roleLabel(effectiveUser?.user_type) || '—' }}</p>
                                    <p v-if="profileUpdateRequestForm.errors.requested_user_type" class="mt-1 text-xs font-medium text-red-500">{{ profileUpdateRequestForm.errors.requested_user_type }}</p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Mã định danh mới</label>
                                    <input v-model="profileUpdateRequestForm.requested_code" type="text" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-medium text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
                                    <p class="mt-1 text-[11px] text-slate-500">Hiện tại: {{ effectiveUser?.code || '—' }}</p>
                                    <p v-if="profileUpdateRequestForm.errors.requested_code" class="mt-1 text-xs font-medium text-red-500">{{ profileUpdateRequestForm.errors.requested_code }}</p>
                                </div>
                                <div v-if="showRequestedFacultyField">
                                    <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Khoa mới</label>
                                    <select v-model="profileUpdateRequestForm.requested_faculty_id" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-medium text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                        <option value="">-- Chọn khoa --</option>
                                        <option v-for="f in faculties" :key="f.id" :value="String(f.id)">
                                            {{ f.name }}
                                        </option>
                                    </select>
                                    <p class="mt-1 text-[11px] text-slate-500">Hiện tại: {{ effectiveUser?.faculty?.name || '—' }}</p>
                                    <p v-if="profileUpdateRequestForm.errors.requested_faculty_id" class="mt-1 text-xs font-medium text-red-500">{{ profileUpdateRequestForm.errors.requested_faculty_id }}</p>
                                </div>
                                <div v-if="showRequestedPeriodField">
                                    <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Niên khóa mới</label>
                                    <select v-model="profileUpdateRequestForm.requested_period_id" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-medium text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                        <option value="">-- Chọn niên khóa --</option>
                                        <option v-for="p in periods" :key="p.id" :value="String(p.id)">
                                            {{ p.name }}
                                        </option>
                                    </select>
                                    <p class="mt-1 text-[11px] text-slate-500">Hiện tại: {{ effectiveUser?.period?.name || '—' }}</p>
                                    <p v-if="profileUpdateRequestForm.errors.requested_period_id" class="mt-1 text-xs font-medium text-red-500">{{ profileUpdateRequestForm.errors.requested_period_id }}</p>
                                </div>
                                <div v-if="showRequestedClassField">
                                    <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Lớp mới</label>
                                    <input v-model="profileUpdateRequestForm.requested_class_code" type="text" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-medium text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
                                    <p class="mt-1 text-[11px] text-slate-500">Hiện tại: {{ effectiveUser?.class_code || '—' }}</p>
                                    <p v-if="profileUpdateRequestForm.errors.requested_class_code" class="mt-1 text-xs font-medium text-red-500">{{ profileUpdateRequestForm.errors.requested_class_code }}</p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Ảnh minh chứng <span class="text-red-500">*</span></label>
                                    <input ref="proofImageInput" type="file" accept=".jpg,.jpeg,.png,.webp" class="block h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200" @change="onProofImageChange" />
                                    <p class="mt-1 truncate text-[11px] text-slate-500">{{ proofFileName || 'Chưa chọn file' }}</p>
                                    <button
                                        v-if="proofPreviewUrl"
                                        type="button"
                                        class="mt-1 inline-flex items-center gap-1 text-[11px] font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                        @click="openProofPreviewModal"
                                    >
                                        <Icon icon="lucide:image" class="h-3.5 w-3.5" />
                                        Nhấn để xem ảnh đã chọn
                                    </button>
                                    <p v-if="profileUpdateRequestForm.errors.proof_image" class="mt-1 text-xs font-medium text-red-500">{{ profileUpdateRequestForm.errors.proof_image }}</p>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Lý do (tuỳ chọn)</label>
                                    <textarea v-model="profileUpdateRequestForm.reason" rows="2" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-medium text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"></textarea>
                                </div>
                            </div>
                            <div class="flex justify-end border-t border-slate-100 pt-4 dark:border-slate-800">
                                <button type="submit" :disabled="profileUpdateRequestSaving" class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-blue-600 px-5 text-sm font-semibold text-white shadow-md shadow-blue-700/20 hover:brightness-110 disabled:opacity-60">
                                    <Icon v-if="profileUpdateRequestSaving" icon="lucide:loader-2" class="h-4 w-4 animate-spin" />
                                    Gửi yêu cầu duyệt
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

        </div>

        <div v-if="showProofPreviewModal && proofPreviewUrl" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60" @click="closeProofPreviewModal" />
            <div class="relative w-full max-w-md rounded-xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                <div class="mb-3 flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Ảnh minh chứng đã chọn</h4>
                    <button type="button" class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="closeProofPreviewModal">
                        <Icon icon="lucide:x" class="h-4 w-4" />
                    </button>
                </div>
                <div class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                    <img :src="proofPreviewUrl" alt="Ảnh minh chứng" class="h-[320px] w-full object-contain bg-slate-50 dark:bg-slate-800" @error="withFallback('/images/default-news-cover.jpg')($event)" />
                </div>
                <div class="mt-3 flex justify-end">
                    <button
                        type="button"
                        class="inline-flex min-h-[36px] items-center gap-1.5 rounded-lg border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/35 dark:text-blue-300 dark:hover:bg-blue-900/50"
                        @click="triggerProofImageChange"
                    >
                        <Icon icon="lucide:camera" class="h-3.5 w-3.5" />
                        Đổi ảnh
                    </button>
                </div>
            </div>
        </div>
</template>
