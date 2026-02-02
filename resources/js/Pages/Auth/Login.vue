<script setup>
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Checkbox } from '@/Components/ui/checkbox'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
// import { User, Key, HelpCircle, GraduationCap } from 'lucide-vue-next'

const { status } = defineProps({
    status: String,
})

const form = useForm({
    login: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <AuthLayout>
        <Head title="Đăng nhập" />

        <div class="flex flex-col items-center justify-center space-y-2 mb-8 text-center animate-in slide-in-from-top-5 duration-500">
            <div class="flex items-center gap-3">
                <!-- Removed ring and semi-transparent bg, using simple solid white backlight if needed, or just the image -->
                <div class="bg-white rounded-full p-1 shadow-lg">
                    <img src="/Image/logoUTC.png" alt="UTC Logo" class="h-16 w-16 object-contain" />
                </div>
                <div class="flex flex-col items-start text-white">
                    <h2 class="text-sm font-bold uppercase tracking-wider opacity-90">
                        Trường Đại Học
                    </h2>
                    <h1 class="text-xl font-extrabold uppercase tracking-wide text-yellow-400 drop-shadow-sm">
                        Giao Thông Vận Tải
                    </h1>
                </div>
            </div>
        </div>

        <!-- Login Card -->
        <div class="w-full max-w-[420px] bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl shadow-2xl rounded-2xl border border-slate-200/50 dark:border-slate-700/50 overflow-hidden animate-in zoom-in-95 duration-500">
            <div class="relative pt-10 pb-6 px-10 flex flex-col items-center text-center">
                <div class="w-14 h-14 rounded-xl bg-[#1e3a8a]/10 dark:bg-blue-500/20 flex items-center justify-center mb-4 text-[#1e3a8a] dark:text-blue-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                </div>
                <h3 class="text-xl font-bold text-[#1e3a8a] dark:text-white tracking-tight">
                    Đăng nhập
                </h3>
            </div>

            <div class="px-10 pb-10">
                <p v-if="status" class="mb-5 text-sm text-green-700 dark:text-green-300 bg-green-50 dark:bg-green-900/30 p-4 rounded-xl border border-green-200 dark:border-green-800/50">
                    {{ status }}
                </p>
                <form @submit.prevent="submit" class="space-y-5">
                    <div class="space-y-2">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                            <Input
                                id="login"
                                type="text"
                                placeholder="Mã sinh viên / Email"
                                v-model="form.login"
                                class="pl-12 h-12 rounded-xl border-slate-200 dark:border-slate-600 bg-slate-50/50 dark:bg-slate-800/50 focus-visible:ring-2 focus-visible:ring-[#1e3a8a]/20 focus-visible:border-[#1e3a8a] transition-all"
                                :class="{ 'border-red-500 ring-2 ring-red-500/20': form.errors.login }"
                                required
                                autofocus
                            />
                        </div>
                        <p v-if="form.errors.login" class="text-xs text-red-600 dark:text-red-400 pl-1">{{ form.errors.login }}</p>
                    </div>

                    <div class="space-y-2">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg>
                            </div>
                            <Input
                                id="password"
                                type="password"
                                placeholder="Mật khẩu"
                                v-model="form.password"
                                class="pl-12 h-12 rounded-xl border-slate-200 dark:border-slate-600 bg-slate-50/50 dark:bg-slate-800/50 focus-visible:ring-2 focus-visible:ring-[#1e3a8a]/20 focus-visible:border-[#1e3a8a] transition-all"
                                :class="{ 'border-red-500 ring-2 ring-red-500/20': form.errors.password }"
                                required
                            />
                        </div>
                        <p v-if="form.errors.password" class="text-xs text-red-600 dark:text-red-400 pl-1">{{ form.errors.password }}</p>
                    </div>

                    <!-- Helper Links -->
                    <div class="flex items-center justify-between text-xs sm:text-sm text-[#1e3a8a] dark:text-blue-300">
                        <Link
                            :href="route('password.request')"
                            class="hover:underline opacity-80 hover:opacity-100"
                        >
                            Quên mật khẩu
                        </Link>
                        <a href="#" class="flex items-center hover:underline opacity-80 hover:opacity-100 gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
                            <span>Trợ giúp!</span>
                        </a>
                    </div>

                    <Button
                        type="submit"
                        class="w-full h-12 text-base font-semibold bg-[#1e3a8a] hover:bg-[#172554] text-white shadow-lg shadow-[#1e3a8a]/25 rounded-xl transition-all duration-200"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">Đang xử lý...</span>
                        <span v-else>Đăng nhập</span>
                    </Button>
                </form>

                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center"><span class="w-full border-t border-slate-200 dark:border-slate-700" /></div>
                    <div class="relative flex justify-center">
                        <span class="bg-white dark:bg-slate-900 px-4 text-sm text-slate-500 dark:text-slate-400">Bạn chưa có tài khoản?</span>
                    </div>
                </div>
                <Link
                    :href="route('register')"
                    class="flex justify-center w-full h-12 items-center rounded-xl font-semibold text-[#1e3a8a] dark:text-blue-400 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-600"
                >
                    Đăng ký
                </Link>
            </div>
        </div>

        <!-- Footer (Outside Card) -->
        <div class="mt-12 text-center text-white/60 text-xs">
            &copy; {{ new Date().getFullYear() }} UTC eLibrary System. All rights reserved.
        </div>
    </AuthLayout>
</template>
