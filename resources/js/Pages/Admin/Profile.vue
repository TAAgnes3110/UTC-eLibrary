<script setup>
import { Head, usePage, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Icon } from '@iconify/vue'
import { computed, onMounted, ref } from 'vue'
import { profileApi } from '@/api/profile'
import { applyLaravelErrorsToInertiaForm } from '@/utils/inertiaFormErrors'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const profile = ref(null)
const effectiveUser = computed(() => ({ ...(user.value || {}), ...(profile.value || {}) }))

const activeTab = ref('info')
const tabs = [
    { key: 'info', label: 'Thông tin', icon: 'lucide:user' },
    { key: 'security', label: 'Bảo mật', icon: 'lucide:shield' },
    { key: 'activity', label: 'Hoạt động', icon: 'lucide:activity' },
]

const profileForm = useForm({
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
        const response = await profileApi.update({
            name: profileForm.name,
            email: profileForm.email,
            phone: profileForm.phone || null,
            date_of_birth: profileForm.date_of_birth || null,
            gender: profileForm.gender || null,
            address: profileForm.address || null,
        })
        profile.value = response?.data || null
        syncProfileForm(profile.value || user.value)
        profileSaved.value = true
        profileEditing.value = false
        setTimeout(() => (profileSaved.value = false), 3000)
        router.reload({ only: ['auth'] })
    } catch (error) {
        applyLaravelErrorsToInertiaForm(profileForm, error)
    } finally {
        profileSaving.value = false
    }
}

const cancelEdit = () => {
    profileEditing.value = false
    syncProfileForm(profile.value || user.value)
}

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const passwordSaved = ref(false)
const passwordSaving = ref(false)
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

const savePassword = async () => {
    passwordSaving.value = true
    passwordForm.clearErrors()
    try {
        await profileApi.updatePassword({
            current_password: passwordForm.current_password,
            password: passwordForm.password,
            password_confirmation: passwordForm.password_confirmation,
        })
        passwordSaved.value = true
        passwordForm.reset()
        showCurrentPassword.value = false
        showNewPassword.value = false
        showConfirmPassword.value = false
        setTimeout(() => (passwordSaved.value = false), 3000)
    } catch (error) {
        applyLaravelErrorsToInertiaForm(passwordForm, error)
    } finally {
        passwordSaving.value = false
    }
}

const avatarInput = ref(null)
const avatarPreview = ref(null)
const avatarLoadFailed = ref(false)
const displayAvatar = computed(() => {
    if (avatarPreview.value) return avatarPreview.value
    if (avatarLoadFailed.value) return null
    return effectiveUser.value?.avatar || null
})

const triggerAvatarUpload = () => {
    avatarInput.value?.click()
}

const handleAvatarChange = (e) => {
    const file = e.target.files[0]
    if (!file) return
    avatarLoadFailed.value = false
    const reader = new FileReader()
    reader.onload = (ev) => {
        avatarPreview.value = ev.target.result
    }
    reader.readAsDataURL(file)
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

const infoItems = computed(() => [
    { icon: 'lucide:hash', label: 'Mã định danh', value: profile.value?.id || user.value?.id || 'Chưa cập nhật' },
    { icon: 'lucide:mail', label: 'Email', value: profile.value?.email || user.value?.email || 'Chưa cập nhật' },
    { icon: 'lucide:phone', label: 'Số điện thoại', value: profile.value?.phone || user.value?.phone || 'Chưa cập nhật' },
    { icon: 'lucide:calendar', label: 'Ngày sinh', value: profile.value?.date_of_birth || user.value?.date_of_birth || 'Chưa cập nhật' },
    { icon: 'lucide:user', label: 'Giới tính', value: genderLabel(profile.value?.gender || user.value?.gender) },
    { icon: 'lucide:map-pin', label: 'Địa chỉ', value: profile.value?.address || user.value?.address || 'Chưa cập nhật' },
])

const activities = [
    { icon: 'lucide:log-in', label: 'Đăng nhập', time: 'Vừa xong', color: 'text-emerald-500' },
    { icon: 'lucide:book-open', label: 'Mượn sách "Lập trình Python"', time: '2 giờ trước', color: 'text-blue-500' },
    { icon: 'lucide:key', label: 'Đổi mật khẩu', time: '3 ngày trước', color: 'text-amber-500' },
    { icon: 'lucide:user-pen', label: 'Cập nhật hồ sơ', time: '1 tuần trước', color: 'text-purple-500' },
]

onMounted(() => {
    syncProfileForm(user.value)
    loadProfile()
})
</script>

<template>
    <AdminLayout
        title="Hồ sơ cá nhân"
        :breadcrumbs="[
            { label: 'Hệ thống' },
            { label: 'Hồ sơ cá nhân' },
        ]"
    >
        <Head title="Hồ sơ cá nhân - Admin" />

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

            <section class="rounded-2xl border border-slate-200/80 bg-white p-2 shadow-lg shadow-slate-200/40 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/25">
                <div class="grid grid-cols-3 gap-1">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        @click="activeTab = tab.key"
                        :class="[
                            'flex h-11 items-center justify-center gap-2 rounded-xl text-sm font-semibold transition-all duration-200',
                            activeTab === tab.key
                                ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-700/30'
                                : 'text-slate-700 hover:-translate-y-0.5 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800'
                        ]"
                    >
                        <Icon :icon="tab.icon" class="h-4 w-4" />
                        <span>{{ tab.label }}</span>
                    </button>
                </div>
            </section>

            <section v-if="activeTab === 'info'" class="grid gap-6 lg:grid-cols-5">
                <div class="space-y-6 lg:col-span-2">
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

                <div class="space-y-4 lg:col-span-3">
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
                        <div class="border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ profileEditing ? 'Chỉnh sửa hồ sơ cá nhân' : 'Thông tin chi tiết' }}</h3>
                            <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-300">Giữ thông tin chính xác để sử dụng các chức năng hệ thống ổn định.</p>
                        </div>

                        <form v-if="profileEditing" class="space-y-5 p-6" @submit.prevent="saveProfile">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                </div>
            </section>

            <section v-if="activeTab === 'security'" class="grid gap-6 lg:grid-cols-5">
                <div class="lg:col-span-3">
                    <div class="rounded-2xl border border-slate-200 bg-white shadow-md shadow-slate-200/30 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/25">
                        <div class="border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Đổi mật khẩu</h3>
                            <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-300">Dùng mật khẩu mạnh và không trùng với mật khẩu cũ.</p>
                        </div>
                        <form class="space-y-4 p-6" @submit.prevent="savePassword">
                            <div v-if="passwordSaved" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400">
                                Đổi mật khẩu thành công.
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Mật khẩu hiện tại</label>
                                <div class="relative">
                                    <input v-model="passwordForm.current_password" :type="showCurrentPassword ? 'text' : 'password'" required class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 pr-11 text-sm font-medium text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
                                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" @click="showCurrentPassword = !showCurrentPassword">
                                        <Icon :icon="showCurrentPassword ? 'lucide:eye-off' : 'lucide:eye'" class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="passwordForm.errors.current_password" class="mt-1 text-xs font-medium text-red-500">{{ passwordForm.errors.current_password }}</p>
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Mật khẩu mới</label>
                                    <div class="relative">
                                        <input v-model="passwordForm.password" :type="showNewPassword ? 'text' : 'password'" required class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 pr-11 text-sm font-medium text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
                                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" @click="showNewPassword = !showNewPassword">
                                            <Icon :icon="showNewPassword ? 'lucide:eye-off' : 'lucide:eye'" class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <p v-if="passwordForm.errors.password" class="mt-1 text-xs font-medium text-red-500">{{ passwordForm.errors.password }}</p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Xác nhận mật khẩu</label>
                                    <div class="relative">
                                        <input v-model="passwordForm.password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" required class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 pr-11 text-sm font-medium text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
                                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" @click="showConfirmPassword = !showConfirmPassword">
                                            <Icon :icon="showConfirmPassword ? 'lucide:eye-off' : 'lucide:eye'" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end border-t border-slate-100 pt-4 dark:border-slate-800">
                                <button type="submit" :disabled="passwordSaving" class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 px-5 text-sm font-semibold text-white shadow-md shadow-amber-700/20 hover:brightness-110 disabled:opacity-60">
                                    <Icon v-if="passwordSaving" icon="lucide:loader-2" class="h-4 w-4 animate-spin" />
                                    Cập nhật mật khẩu
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="space-y-4 lg:col-span-2">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-md shadow-slate-200/30 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/25">
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">Tình trạng tài khoản</h4>
                        <div class="mt-4 space-y-3">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800">
                                <p class="text-[11px] uppercase tracking-wide text-slate-600 dark:text-slate-300">Loại tài khoản</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ effectiveUser?.user_type_label || effectiveUser?.user_type || 'MEMBER' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800">
                                <p class="text-[11px] uppercase tracking-wide text-slate-600 dark:text-slate-300">Xác thực email</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ effectiveUser?.email_verified_at ? 'Đã xác thực' : 'Chưa xác thực' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800">
                                <p class="text-[11px] uppercase tracking-wide text-slate-600 dark:text-slate-300">Trạng thái</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ effectiveUser?.is_active === false ? 'Tạm ngưng' : 'Đang hoạt động' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="activeTab === 'activity'" class="rounded-2xl border border-slate-200 bg-white shadow-md shadow-slate-200/30 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/25">
                <div class="border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Lịch sử hoạt động</h3>
                    <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-300">Theo dõi hành động gần đây của tài khoản.</p>
                </div>
                <div class="p-6">
                    <div class="relative pl-8">
                        <div class="absolute bottom-1 left-2 top-2 w-px bg-slate-200 dark:bg-slate-700"></div>
                        <div v-for="(act, i) in activities" :key="i" class="relative mb-4 last:mb-0">
                            <div :class="['absolute -left-8 top-2 h-3.5 w-3.5 rounded-full border-2 border-white shadow-sm dark:border-slate-900', act.color.replace('text-', 'bg-')]"></div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                <div class="flex items-center gap-3">
                                    <Icon :icon="act.icon" :class="['h-4 w-4 shrink-0', act.color]" />
                                    <p class="flex-1 text-sm font-semibold text-slate-900 dark:text-white">{{ act.label }}</p>
                                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ act.time }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="mt-6 text-center text-xs italic text-slate-400 dark:text-slate-500">Dữ liệu hoạt động sẽ được mở rộng ở các phiên bản tiếp theo.</p>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
