<script setup>
import { usePage, useForm, Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import { computed, ref } from 'vue'
import { profileApi } from '@/api/profile'
import { applyLaravelErrorsToInertiaForm } from '@/utils/inertiaFormErrors'

defineProps({
    /** Route Inertia ve trang ho so (admin.profile | reader.profile) */
    profileRouteName: { type: String, default: 'admin.profile' },
})

const page = usePage()
const user = computed(() => page.props.auth?.user)

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
</script>

<template>
    <div class="mx-auto max-w-6xl space-y-6">
        <div class="mb-2">
            <Link
                :href="route(profileRouteName)"
                class="inline-flex min-h-[44px] items-center gap-2 text-sm font-semibold text-blue-800 hover:underline dark:text-blue-400"
            >
                <Icon icon="lucide:arrow-left" class="h-4 w-4 shrink-0" aria-hidden="true" />
                Thông tin cá nhân
            </Link>
        </div>

        <section class="grid gap-6 lg:grid-cols-5">
            <div class="lg:col-span-3">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-md shadow-slate-200/30 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/25">
                    <div class="border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Đổi mật khẩu</h3>
                        <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-300">
                            Dùng mật khẩu mạnh và không trùng với mật khẩu cũ.
                        </p>
                    </div>
                    <form class="space-y-4 p-6" @submit.prevent="savePassword">
                        <div
                            v-if="passwordSaved"
                            class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400"
                        >
                            Đổi mật khẩu thành công.
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Mật khẩu hiện tại</label>
                            <div class="relative">
                                <input
                                    v-model="passwordForm.current_password"
                                    :type="showCurrentPassword ? 'text' : 'password'"
                                    required
                                    class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 pr-11 text-sm font-medium text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                />
                                <button
                                    type="button"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                    @click="showCurrentPassword = !showCurrentPassword"
                                >
                                    <Icon :icon="showCurrentPassword ? 'lucide:eye-off' : 'lucide:eye'" class="h-4 w-4" />
                                </button>
                            </div>
                            <p v-if="passwordForm.errors.current_password" class="mt-1 text-xs font-medium text-red-500">
                                {{ passwordForm.errors.current_password }}
                            </p>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Mật khẩu mới</label>
                                <div class="relative">
                                    <input
                                        v-model="passwordForm.password"
                                        :type="showNewPassword ? 'text' : 'password'"
                                        required
                                        class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 pr-11 text-sm font-medium text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                    <button
                                        type="button"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                        @click="showNewPassword = !showNewPassword"
                                    >
                                        <Icon :icon="showNewPassword ? 'lucide:eye-off' : 'lucide:eye'" class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="passwordForm.errors.password" class="mt-1 text-xs font-medium text-red-500">{{ passwordForm.errors.password }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">Xác nhận mật khẩu</label>
                                <div class="relative">
                                    <input
                                        v-model="passwordForm.password_confirmation"
                                        :type="showConfirmPassword ? 'text' : 'password'"
                                        required
                                        class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 pr-11 text-sm font-medium text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                    />
                                    <button
                                        type="button"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                        @click="showConfirmPassword = !showConfirmPassword"
                                    >
                                        <Icon :icon="showConfirmPassword ? 'lucide:eye-off' : 'lucide:eye'" class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end border-t border-slate-100 pt-4 dark:border-slate-800">
                            <button
                                type="submit"
                                :disabled="passwordSaving"
                                class="inline-flex h-10 min-h-[44px] items-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 px-5 text-sm font-semibold text-white shadow-md shadow-amber-700/20 hover:brightness-110 disabled:opacity-60"
                            >
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
                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
                                {{ user?.user_type_label || user?.user_type || 'MEMBER' }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800">
                            <p class="text-[11px] uppercase tracking-wide text-slate-600 dark:text-slate-300">Xác thực email</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
                                {{ user?.email_verified_at ? 'Đã xác thực' : 'Chưa xác thực' }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800">
                            <p class="text-[11px] uppercase tracking-wide text-slate-600 dark:text-slate-300">Trạng thái</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
                                {{ user?.is_active === false ? 'Tạm ngưng' : 'Đang hoạt động' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
