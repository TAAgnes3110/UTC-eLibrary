<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Label } from '@/Components/ui/label'
import { Mail, ArrowRight } from 'lucide-vue-next'
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
        <div class="font-sans w-full flex flex-col items-center">
            <Head title="Quên mật khẩu" />

            <!-- Brand Header -->
            <div class="mb-2 flex flex-col items-center animate-in fade-in slide-in-from-top-4 duration-700">
                 <div class="group relative flex items-center justify-center gap-4 transition-transform hover:scale-105">
                     <div class="absolute -inset-2 bg-yellow-400/20 rounded-full blur-xl group-hover:bg-yellow-400/30 transition-all duration-300 opacity-0 group-hover:opacity-100"></div>
                     <div class="relative bg-white/10 backdrop-blur-md rounded-xl p-1.5 border border-white/20 shadow-2xl">
                        <img src="/Image/logoUTC.png" alt="UTC Logo" class="h-12 w-12 object-contain" />
                     </div>
                     <div class="flex flex-col">
                        <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-white/70 leading-none mb-1">Trường Đại Học</span>
                        <h1 class="font-display text-lg font-black uppercase tracking-tight text-white leading-tight drop-shadow-lg">
                            Giao Thông <span class="text-yellow-400">Vận Tải</span>
                        </h1>
                    </div>
                 </div>
            </div>

            <!-- Forgot Password Card -->
            <div class="w-full max-w-[440px] bg-slate-900/60 backdrop-blur-3xl shadow-[0_32px_64px_-16px_rgba(0,0,0,0.5)] rounded-[2rem] border border-white/10 overflow-hidden animate-in zoom-in-95 fade-in duration-700">
                <div class="px-8 py-6">
                    <!-- Title Section -->
                    <div class="mb-4 relative flex items-center min-h-[50px]">
                        <!-- Icon on the Left -->
                        <div class="absolute left-0 group">
                            <div class="absolute -inset-4 bg-blue-500/20 rounded-full blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            <div class="relative w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center border border-white/10 text-blue-400 shadow-2xl ring-1 ring-white/5">
                                <Mail :size="24" class="animate-pulse" />
                            </div>
                        </div>

                        <!-- Centered Text -->
                        <div class="flex-1 text-center pl-10 space-y-1">
                            <h2 class="font-display text-2xl font-black text-white tracking-tight uppercase leading-loose">
                                QUÊN MẬT KHẨU
                            </h2>
                            <div class="flex items-center justify-center gap-2">
                                <div class="h-px w-6 bg-blue-500/20"></div>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.3em]">Khôi phục tài khoản</p>
                                <div class="h-px w-6 bg-blue-500/20"></div>
                            </div>
                        </div>
                    </div>

                    <p class="text-center text-[13px] text-slate-500 mb-6 leading-relaxed">
                        Nhập email đăng ký tài khoản của bạn. Hệ thống sẽ gửi mã xác thực OTP để quyền đặt lại mật khẩu.
                    </p>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2 group">
                            <Label for="email" class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1 transition-colors group-focus-within:text-blue-400">
                                Email đăng ký
                            </Label>
                            <div class="relative group-focus-within:ring-2 ring-blue-500/20 rounded-xl transition-all duration-300">
                                <Mail class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 transition-colors group-focus-within:text-blue-400" :size="18" />
                                <input
                                    id="email"
                                    type="email"
                                    placeholder="example@gmail.com"
                                    v-model="form.email"
                                    class="h-12 w-full border border-white/5 bg-white/5 pl-11 pr-4 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300 outline-none"
                                    required
                                    autofocus
                                />
                            </div>
                            <p v-if="form.errors.email" class="text-[11px] text-red-500 font-medium pl-1">{{ form.errors.email }}</p>
                        </div>

                        <button
                            type="submit"
                            class="group relative w-full h-14 overflow-hidden rounded-2xl bg-blue-600 text-base font-bold text-white shadow-[0_0_20px_rgba(59,130,246,0.5)] transition-all hover:bg-blue-500 hover:shadow-[0_0_30px_rgba(59,130,246,0.7)] active:scale-[0.98] disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing" class="flex items-center gap-2 justify-center">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Đang xử lý...
                            </span>
                            <span v-else class="flex items-center justify-center gap-2 uppercase tracking-widest">
                                Gửi mã xác thực <ArrowRight :size="18" class="group-hover:translate-x-1 transition-transform" />
                            </span>
                        </button>
                    </form>

                    <div class="text-center mt-6 pt-6 border-t border-white/10">
                        <Link
                            :href="route('login')"
                            class="flex items-center justify-center gap-2 w-full h-12 rounded-xl bg-white/5 border border-white/10 text-slate-400 font-bold text-sm hover:bg-white/10 hover:text-white transition-all active:scale-[0.98]"
                        >
                            Quay lại trang Đăng nhập
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-6 text-center">
                <p class="text-white/90 text-xs font-bold tracking-widest uppercase drop-shadow-md">
                    &copy; 2026 UTC eLibrary System &bull; Version 2.0
                </p>
            </div>
        </div>
    </AuthLayout>
</template>
