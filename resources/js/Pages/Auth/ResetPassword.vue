<script setup>
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    email: String,
    status: String,
})

const form = useForm({
    email: props.email || '',
    otp: '',
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <AuthLayout>
        <Head title="Đặt lại mật khẩu" />

        <div class="flex flex-col items-center justify-center space-y-2 mb-6 text-center animate-in slide-in-from-top-5 duration-500">
            <div class="flex items-center gap-3">
                <div class="bg-white rounded-full p-1 shadow-lg">
                    <img src="/Image/logoUTC.png" alt="UTC Logo" class="h-16 w-16 object-contain" />
                </div>
                <div class="flex flex-col items-start text-white">
                    <h2 class="text-sm font-bold uppercase tracking-wider opacity-90">Trường Đại Học</h2>
                    <h1 class="text-xl font-extrabold uppercase tracking-wide text-yellow-400 drop-shadow-sm">
                        Giao Thông Vận Tải
                    </h1>
                </div>
            </div>
        </div>

        <div class="w-full max-w-[420px] bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl shadow-2xl rounded-2xl border border-slate-200/50 dark:border-slate-700/50 overflow-hidden animate-in zoom-in-95 duration-500">
            <div class="relative pt-10 pb-6 px-10 flex flex-col items-center text-center">
                <div class="w-14 h-14 rounded-xl bg-[#1e3a8a]/10 dark:bg-blue-500/20 flex items-center justify-center mb-4 text-[#1e3a8a] dark:text-blue-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="16" r="1"/><rect width="18" height="12" x="3" y="10" rx="2"/><path d="M7 10V7a5 5 0 0 1 9.9-1"/></svg>
                </div>
                <h3 class="text-xl font-bold text-[#1e3a8a] dark:text-white tracking-tight">Đặt lại mật khẩu</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">
                    Nhập mã OTP và mật khẩu mới cho <strong class="text-[#1e3a8a] dark:text-blue-400">{{ email }}</strong>
                </p>
            </div>

            <div class="px-10 pb-10">
                <p v-if="status" class="mb-5 text-sm text-green-700 dark:text-green-300 bg-green-50 dark:bg-green-900/30 p-4 rounded-xl border border-green-200 dark:border-green-800/50">
                    {{ status }}
                </p>

                <form @submit.prevent="submit" class="space-y-4">
                    <input type="hidden" v-model="form.email" />

                    <div class="space-y-2">
                        <Input
                            id="otp"
                            type="text"
                            placeholder="Mã OTP (6 số)"
                            v-model="form.otp"
                            maxlength="6"
                            inputmode="numeric"
                            class="h-12 rounded-xl border-slate-200 dark:border-slate-600 bg-slate-50/50 dark:bg-slate-800/50 focus-visible:ring-2 focus-visible:ring-[#1e3a8a]/20"
                            :class="{ 'border-red-500 ring-2 ring-red-500/20': form.errors.otp }"
                            required
                        />
                        <p v-if="form.errors.otp" class="text-xs text-red-600 dark:text-red-400 pl-1">{{ form.errors.otp }}</p>
                    </div>

                    <div class="space-y-2">
                        <Input
                            id="password"
                            type="password"
                            placeholder="Mật khẩu mới (8+ ký tự)"
                            v-model="form.password"
                            class="h-12 rounded-xl border-slate-200 dark:border-slate-600 bg-slate-50/50 dark:bg-slate-800/50 focus-visible:ring-2 focus-visible:ring-[#1e3a8a]/20"
                            :class="{ 'border-red-500 ring-2 ring-red-500/20': form.errors.password }"
                            required
                        />
                        <p v-if="form.errors.password" class="text-xs text-red-600 dark:text-red-400 pl-1">{{ form.errors.password }}</p>
                    </div>

                    <div class="space-y-2">
                        <Input
                            id="password_confirmation"
                            type="password"
                            placeholder="Xác nhận mật khẩu mới"
                            v-model="form.password_confirmation"
                            class="h-12 rounded-xl border-slate-200 dark:border-slate-600 bg-slate-50/50 dark:bg-slate-800/50 focus-visible:ring-2 focus-visible:ring-[#1e3a8a]/20"
                            :class="{ 'border-red-500 ring-2 ring-red-500/20': form.errors.password_confirmation }"
                            required
                        />
                        <p v-if="form.errors.password_confirmation" class="text-xs text-red-600 dark:text-red-400 pl-1">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <Button
                        type="submit"
                        class="w-full h-12 text-base font-semibold bg-[#1e3a8a] hover:bg-[#172554] text-white shadow-lg shadow-[#1e3a8a]/25 rounded-xl transition-all duration-200"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">Đang xử lý...</span>
                        <span v-else>Đặt lại mật khẩu</span>
                    </Button>
                </form>

                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center"><span class="w-full border-t border-slate-200 dark:border-slate-700" /></div>
                    <div class="relative flex justify-center">
                        <span class="bg-white dark:bg-slate-900 px-4 text-sm text-slate-500 dark:text-slate-400">Quay lại</span>
                    </div>
                </div>

                <Link
                    :href="route('login')"
                    class="flex justify-center w-full h-12 items-center rounded-xl font-semibold text-[#1e3a8a] dark:text-blue-400 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-600"
                >
                    Đăng nhập
                </Link>
            </div>
        </div>

        <div class="mt-8 text-center text-white/60 text-xs">
            &copy; {{ new Date().getFullYear() }} UTC eLibrary System.
        </div>
    </AuthLayout>
</template>
