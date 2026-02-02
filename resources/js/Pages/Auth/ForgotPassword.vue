<script setup>
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({
    email: '',
})

const submit = () => {
    form.post(route('password.email'))
}
</script>

<template>
    <AuthLayout>
        <Head title="Quên mật khẩu" />

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
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 17v3c0 .6.4 1 1 1h4"/><path d="M16 21h2c.6 0 1-.4 1-1v-3c0-.6-.4-1-1-1h-2"/><path d="M3 11a7 7 0 0 1 13.6-2.9"/><path d="M7 21v-2a3 3 0 0 1 3-3"/><circle cx="16" cy="12" r="1"/></svg>
                </div>
                <h3 class="text-xl font-bold text-[#1e3a8a] dark:text-white tracking-tight">Quên mật khẩu</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-2 max-w-xs">
                    Nhập email đăng ký. Chúng tôi sẽ gửi mã OTP để đặt lại mật khẩu.
                </p>
            </div>

            <div class="px-10 pb-10">
                <form @submit.prevent="submit" class="space-y-5">
                    <div class="space-y-2">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            </div>
                            <Input
                                id="email"
                                type="email"
                                placeholder="Email đăng ký"
                                v-model="form.email"
                                class="pl-12 h-12 rounded-xl border-slate-200 dark:border-slate-600 bg-slate-50/50 dark:bg-slate-800/50 focus-visible:ring-2 focus-visible:ring-[#1e3a8a]/20 focus-visible:border-[#1e3a8a] transition-all"
                                :class="{ 'border-red-500 ring-2 ring-red-500/20': form.errors.email }"
                                required
                                autofocus
                            />
                        </div>
                        <p v-if="form.errors.email" class="text-xs text-red-600 dark:text-red-400 pl-1">{{ form.errors.email }}</p>
                    </div>

                    <Button
                        type="submit"
                        class="w-full h-12 text-base font-semibold bg-[#1e3a8a] hover:bg-[#172554] text-white shadow-lg shadow-[#1e3a8a]/25 rounded-xl transition-all duration-200"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">Đang gửi...</span>
                        <span v-else>Gửi mã OTP</span>
                    </Button>
                </form>

                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center"><span class="w-full border-t border-slate-200 dark:border-slate-700" /></div>
                    <div class="relative flex justify-center">
                        <span class="bg-white dark:bg-slate-900 px-4 text-sm text-slate-500 dark:text-slate-400">Nhớ mật khẩu rồi?</span>
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
