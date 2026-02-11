<script setup>
import { Head, usePage, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Icon } from '@iconify/vue'
import { computed, ref, watch } from 'vue'

const page = usePage()
const user = computed(() => page.props.auth?.user)

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

const saveProfile = () => {
    // TODO: Kết nối API khi có route update profile
    profileSaved.value = true
    profileEditing.value = false
    setTimeout(() => profileSaved.value = false, 3000)
}

const cancelEdit = () => {
    profileEditing.value = false
    profileForm.reset()
}

// === Password Form ===
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const passwordSaved = ref(false)
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

const savePassword = () => {
    // TODO: Kết nối API khi có route update password
    passwordSaved.value = true
    passwordForm.reset()
    showCurrentPassword.value = false
    showNewPassword.value = false
    showConfirmPassword.value = false
    setTimeout(() => passwordSaved.value = false, 3000)
}

// === Avatar ===
const avatarInput = ref(null)
const avatarPreview = ref(null)

const triggerAvatarUpload = () => {
    avatarInput.value?.click()
}

const handleAvatarChange = (e) => {
    const file = e.target.files[0]
    if (!file) return
    const reader = new FileReader()
    reader.onload = (ev) => {
        avatarPreview.value = ev.target.result
    }
    reader.readAsDataURL(file)
    // TODO: Upload avatar via API
}

// === Helpers ===
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
        'MEMBER': 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
        'GUEST': 'bg-slate-100 dark:bg-slate-700/30 text-slate-700 dark:text-slate-400',
    }
    return map[r] || map['MEMBER']
}

const roleLabel = (role) => {
    const r = typeof role === 'string' ? role : role?.name
    const map = {
        'SUPER_ADMIN': 'Quản trị viên cấp cao',
        'ADMIN': 'Quản trị viên',
        'LIBRARIAN': 'Thủ thư',
        'MEMBER': 'Thành viên',
        'GUEST': 'Khách',
    }
    return map[r] || r
}

// === Info Items (readonly display) ===
const infoItems = computed(() => [
    { icon: 'lucide:hash', label: 'Mã định danh', value: user.value?.code },
    { icon: 'lucide:mail', label: 'Email', value: user.value?.email },
    { icon: 'lucide:phone', label: 'Số điện thoại', value: user.value?.phone || 'Chưa cập nhật' },
    { icon: 'lucide:calendar', label: 'Ngày sinh', value: user.value?.date_of_birth || 'Chưa cập nhật' },
    { icon: 'lucide:user', label: 'Giới tính', value: genderLabel(user.value?.gender) },
    { icon: 'lucide:map-pin', label: 'Địa chỉ', value: user.value?.address || 'Chưa cập nhật' },
])

// === Mock activity data ===
const activities = [
    { icon: 'lucide:log-in', label: 'Đăng nhập', time: 'Vừa xong', color: 'text-emerald-500' },
    { icon: 'lucide:book-open', label: 'Mượn sách "Lập trình Python"', time: '2 giờ trước', color: 'text-blue-500' },
    { icon: 'lucide:key', label: 'Đổi mật khẩu', time: '3 ngày trước', color: 'text-amber-500' },
    { icon: 'lucide:user-pen', label: 'Cập nhật hồ sơ', time: '1 tuần trước', color: 'text-purple-500' },
]
</script>

<template>
    <AdminLayout title="Hồ sơ cá nhân">
        <Head title="Hồ sơ cá nhân" />

        <div class="max-w-4xl mx-auto space-y-6">

            <!-- ═══════════════════════════════ PROFILE HEADER ═══════════════════════════════ -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <!-- Banner Gradient -->
                <div class="h-36 sm:h-44 bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-700 relative overflow-hidden">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PHBhdGggZD0iTTM2IDM0djZoLTJ2LTZoLTZ2LTJoNnYtNmgydjZoNnYyaC02eiIvPjwvZz48L2c+PC9zdmc+')] opacity-50"></div>
                    <!-- Decorative circles -->
                    <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-white/10 blur-xl"></div>
                    <div class="absolute -bottom-16 -left-8 w-48 h-48 rounded-full bg-white/5 blur-2xl"></div>
                </div>

                <!-- Avatar + Info -->
                <div class="px-6 sm:px-8 pb-6 -mt-12 sm:-mt-14 relative">
                    <div class="flex flex-col sm:flex-row sm:items-end gap-4 sm:gap-6">
                        <!-- Avatar -->
                        <div class="relative group cursor-pointer shrink-0" @click="triggerAvatarUpload">
                            <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl border-4 border-white dark:border-slate-900 shadow-xl overflow-hidden bg-gradient-to-br from-blue-800 to-indigo-900 flex items-center justify-center transition-transform group-hover:scale-105">
                                <img v-if="avatarPreview || user?.avatar" :src="avatarPreview || user?.avatar"
                                    alt="Avatar" class="w-full h-full object-cover" />
                                <span v-else class="text-white font-black text-3xl sm:text-4xl select-none">
                                    {{ user?.name?.charAt(0)?.toUpperCase() || 'A' }}
                                </span>
                            </div>
                            <!-- Upload overlay -->
                            <div class="absolute inset-0 rounded-2xl bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center border-4 border-transparent">
                                <Icon icon="lucide:camera" class="w-6 h-6 text-white" />
                            </div>
                            <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="handleAvatarChange" />
                        </div>

                        <!-- Name + Meta -->
                        <div class="flex-1 min-w-0 pb-1">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white leading-tight truncate">
                                {{ user?.name || 'Người dùng' }}
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 truncate">{{ user?.email }}</p>
                            <div class="flex flex-wrap gap-2 mt-2.5">
                                <span v-for="role in (user?.roles || [])" :key="typeof role === 'string' ? role : role?.name"
                                    :class="[roleColor(role), 'px-3 py-1 text-[11px] font-black rounded-lg uppercase tracking-wider']">
                                    {{ roleLabel(role) }}
                                </span>
                                <span v-if="user?.is_active !== false"
                                    class="px-3 py-1 text-[11px] font-black rounded-lg uppercase tracking-wider bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Đang hoạt động
                                </span>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="flex gap-2 shrink-0 sm:pb-1">
                            <button v-if="!profileEditing" @click="profileEditing = true; activeTab = 'info'"
                                class="h-10 px-5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition-all active:scale-[0.97] shadow-lg shadow-blue-600/20 flex items-center gap-2">
                                <Icon icon="lucide:pen-line" class="w-4 h-4" />
                                Chỉnh sửa
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════ TABS ═══════════════════════════════ -->
            <div class="flex gap-1 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-1.5 shadow-sm">
                <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
                    :class="[
                        'flex-1 flex items-center justify-center gap-2 h-10 rounded-lg text-sm font-bold transition-all duration-200',
                        activeTab === tab.key
                            ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20'
                            : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-300'
                    ]">
                    <Icon :icon="tab.icon" class="w-4 h-4" />
                    <span class="hidden sm:inline">{{ tab.label }}</span>
                </button>
            </div>

            <!-- ═══════════════════════════════ TAB: INFO ═══════════════════════════════ -->
            <div v-if="activeTab === 'info'" class="space-y-6 animate-in fade-in duration-300">

                <!-- Success Alert -->
                <div v-if="profileSaved" class="flex items-center gap-3 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 animate-in slide-in-from-top-5 duration-300">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600">
                        <Icon icon="lucide:check-circle-2" class="w-5 h-5" />
                    </div>
                    <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Cập nhật hồ sơ thành công!</p>
                </div>

                <!-- READ-ONLY VIEW -->
                <div v-if="!profileEditing" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                            <Icon icon="lucide:contact" class="w-4.5 h-4.5" />
                        </div>
                        <h3 class="font-bold text-slate-900 dark:text-white">Thông tin cá nhân</h3>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        <div v-for="(item, i) in infoItems" :key="i"
                            class="flex items-center px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors gap-4">
                            <div class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 shrink-0">
                                <Icon :icon="item.icon" class="w-4.5 h-4.5" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">{{ item.label }}</p>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white mt-0.5 truncate"
                                    :class="{ 'text-slate-400 dark:text-slate-600 italic': item.value === 'Chưa cập nhật' }">
                                    {{ item.value }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- EDIT VIEW -->
                <div v-else class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center text-amber-600 dark:text-amber-400">
                            <Icon icon="lucide:pen-line" class="w-4.5 h-4.5" />
                        </div>
                        <h3 class="font-bold text-slate-900 dark:text-white">Chỉnh sửa thông tin</h3>
                    </div>
                    <form @submit.prevent="saveProfile" class="p-6 space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <!-- Name -->
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <Icon icon="lucide:user" class="w-3.5 h-3.5" /> Họ và tên
                                </label>
                                <input v-model="profileForm.name" type="text" required
                                    class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all" />
                                <p v-if="profileForm.errors.name" class="text-xs text-red-500 font-semibold">{{ profileForm.errors.name }}</p>
                            </div>
                            <!-- Email -->
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <Icon icon="lucide:mail" class="w-3.5 h-3.5" /> Email
                                </label>
                                <input v-model="profileForm.email" type="email" required
                                    class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all" />
                                <p v-if="profileForm.errors.email" class="text-xs text-red-500 font-semibold">{{ profileForm.errors.email }}</p>
                            </div>
                            <!-- Phone -->
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <Icon icon="lucide:phone" class="w-3.5 h-3.5" /> Số điện thoại
                                </label>
                                <input v-model="profileForm.phone" type="tel"
                                    class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all" />
                            </div>
                            <!-- Date of Birth -->
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <Icon icon="lucide:calendar" class="w-3.5 h-3.5" /> Ngày sinh
                                </label>
                                <input v-model="profileForm.date_of_birth" type="date"
                                    class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all [color-scheme:dark]" />
                            </div>
                            <!-- Gender -->
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <Icon icon="lucide:users" class="w-3.5 h-3.5" /> Giới tính
                                </label>
                                <select v-model="profileForm.gender"
                                    class="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all appearance-none cursor-pointer">
                                    <option value="" class="bg-white dark:bg-slate-900">Chưa chọn</option>
                                    <option value="male" class="bg-white dark:bg-slate-900">Nam</option>
                                    <option value="female" class="bg-white dark:bg-slate-900">Nữ</option>
                                    <option value="other" class="bg-white dark:bg-slate-900">Khác</option>
                                </select>
                            </div>
                        </div>
                        <!-- Address -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                <Icon icon="lucide:map-pin" class="w-3.5 h-3.5" /> Địa chỉ
                            </label>
                            <textarea v-model="profileForm.address" rows="2"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all resize-none"></textarea>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="cancelEdit"
                                class="h-11 px-6 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                                Hủy
                            </button>
                            <button type="submit" :disabled="profileForm.processing"
                                class="h-11 px-8 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold transition-all active:scale-[0.97] shadow-lg shadow-blue-600/20 disabled:opacity-50 flex items-center gap-2">
                                <Icon v-if="profileForm.processing" icon="lucide:loader-2" class="w-4 h-4 animate-spin" />
                                Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ═══════════════════════════════ TAB: SECURITY ═══════════════════════════════ -->
            <div v-if="activeTab === 'security'" class="space-y-6 animate-in fade-in duration-300">

                <!-- Password Saved Alert -->
                <div v-if="passwordSaved" class="flex items-center gap-3 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 animate-in slide-in-from-top-5 duration-300">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600">
                        <Icon icon="lucide:check-circle-2" class="w-5 h-5" />
                    </div>
                    <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Đổi mật khẩu thành công!</p>
                </div>

                <!-- Change Password -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center text-amber-600 dark:text-amber-400">
                            <Icon icon="lucide:key-round" class="w-4.5 h-4.5" />
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white">Đổi mật khẩu</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Thay đổi mật khẩu đăng nhập</p>
                        </div>
                    </div>
                    <form @submit.prevent="savePassword" class="p-6 space-y-5">
                        <!-- Current Password -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Mật khẩu hiện tại</label>
                            <div class="relative">
                                <input v-model="passwordForm.current_password" :type="showCurrentPassword ? 'text' : 'password'" required
                                    class="w-full h-11 px-4 pr-12 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all" />
                                <button type="button" @click="showCurrentPassword = !showCurrentPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 p-1 transition-colors">
                                    <Icon :icon="showCurrentPassword ? 'lucide:eye-off' : 'lucide:eye'" class="w-4 h-4" />
                                </button>
                            </div>
                            <p v-if="passwordForm.errors.current_password" class="text-xs text-red-500 font-semibold">{{ passwordForm.errors.current_password }}</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <!-- New Password -->
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Mật khẩu mới</label>
                                <div class="relative">
                                    <input v-model="passwordForm.password" :type="showNewPassword ? 'text' : 'password'" required
                                        class="w-full h-11 px-4 pr-12 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all" />
                                    <button type="button" @click="showNewPassword = !showNewPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 p-1 transition-colors">
                                        <Icon :icon="showNewPassword ? 'lucide:eye-off' : 'lucide:eye'" class="w-4 h-4" />
                                    </button>
                                </div>
                                <p v-if="passwordForm.errors.password" class="text-xs text-red-500 font-semibold">{{ passwordForm.errors.password }}</p>
                            </div>
                            <!-- Confirm Password -->
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Xác nhận mật khẩu</label>
                                <div class="relative">
                                    <input v-model="passwordForm.password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" required
                                        class="w-full h-11 px-4 pr-12 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all" />
                                    <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 p-1 transition-colors">
                                        <Icon :icon="showConfirmPassword ? 'lucide:eye-off' : 'lucide:eye'" class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button type="submit" :disabled="passwordForm.processing"
                                class="h-11 px-8 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold transition-all active:scale-[0.97] shadow-lg shadow-amber-600/20 disabled:opacity-50 flex items-center gap-2">
                                <Icon v-if="passwordForm.processing" icon="lucide:loader-2" class="w-4 h-4 animate-spin" />
                                Cập nhật mật khẩu
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Account Info (readonly) -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-400">
                            <Icon icon="lucide:shield-check" class="w-4.5 h-4.5" />
                        </div>
                        <h3 class="font-bold text-slate-900 dark:text-white">Thông tin tài khoản</h3>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        <div class="flex items-center justify-between px-6 py-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Loại tài khoản</p>
                                <p class="text-xs text-slate-500 mt-0.5">{{ user?.user_type_label || user?.user_type || 'MEMBER' }}</p>
                            </div>
                            <span class="px-3 py-1 text-[11px] font-bold rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 uppercase">
                                {{ user?.user_type || 'MEMBER' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between px-6 py-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Email xác thực</p>
                                <p class="text-xs text-slate-500 mt-0.5">{{ user?.email }}</p>
                            </div>
                            <span class="px-3 py-1 text-[11px] font-bold rounded-lg uppercase"
                                :class="user?.email_verified_at ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400'">
                                {{ user?.email_verified_at ? 'Đã xác thực' : 'Chưa xác thực' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between px-6 py-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Trạng thái</p>
                                <p class="text-xs text-slate-500 mt-0.5">Tài khoản đang hoạt động bình thường</p>
                            </div>
                            <span class="flex items-center gap-1.5 px-3 py-1 text-[11px] font-bold rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════ TAB: ACTIVITY ═══════════════════════════════ -->
            <div v-if="activeTab === 'activity'" class="space-y-6 animate-in fade-in duration-300">
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <Icon icon="lucide:activity" class="w-4.5 h-4.5" />
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white">Lịch sử hoạt động</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Các hoạt động gần đây trên tài khoản</p>
                        </div>
                    </div>
                    <!-- Timeline -->
                    <div class="p-6">
                        <div class="relative pl-8 space-y-6">
                            <!-- Timeline line -->
                            <div class="absolute left-[15px] top-2 bottom-2 w-px bg-slate-200 dark:bg-slate-700"></div>

                            <div v-for="(act, i) in activities" :key="i" class="relative">
                                <!-- Dot -->
                                <div :class="['absolute -left-8 top-0.5 w-[14px] h-[14px] rounded-full border-2 border-white dark:border-slate-900 shadow-sm z-10', act.color.replace('text-', 'bg-')]"></div>
                                <!-- Content -->
                                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <Icon :icon="act.icon" :class="['w-4 h-4 shrink-0', act.color]" />
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white flex-1">{{ act.label }}</p>
                                        <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider shrink-0">{{ act.time }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty state note -->
                        <p class="text-center text-xs text-slate-400 dark:text-slate-600 mt-6 italic">
                            Dữ liệu hoạt động sẽ được cập nhật khi hệ thống triển khai đầy đủ
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
