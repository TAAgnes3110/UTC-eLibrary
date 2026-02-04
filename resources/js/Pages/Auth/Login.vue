<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { User, Lock, Eye, EyeOff, LayoutDashboard, HelpCircle, ArrowRight, LogIn } from 'lucide-vue-next'
import { ref } from 'vue'

const { status } = defineProps({
    status: String,
})

const showPassword = ref(false)

const form = useForm({
    login: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <AuthLayout>
        <div class="font-sans w-full flex flex-col items-center">
            <Head title="Đăng nhập" />

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

            <!-- Login Card -->
            <div class="w-full max-w-[440px] bg-slate-900/60 backdrop-blur-3xl shadow-[0_32px_64px_-16px_rgba(0,0,0,0.5)] rounded-[2rem] border border-white/10 overflow-hidden animate-in zoom-in-95 fade-in duration-700">
                <div class="px-8 py-6">
                    <!-- Title Section -->
                    <div class="mb-4 relative flex items-center min-h-[50px]">
                        <!-- Icon on the Left -->
                        <div class="absolute left-0 group">
                            <div class="absolute -inset-4 bg-blue-500/20 rounded-full blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            <div class="relative w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center border border-white/10 text-blue-400 shadow-2xl ring-1 ring-white/5">
                                <LogIn :size="24" class="animate-pulse" />
                            </div>
                        </div>

                        <!-- Centered Text -->
                        <div class="flex-1 text-center pl-10 space-y-1">
                            <h2 class="font-display text-2xl font-black text-white tracking-tight bg-clip-text text-transparent bg-gradient-to-b from-white to-white/70 uppercase tracking-widest leading-loose">
                                Đăng nhập
                            </h2>
                            <div class="flex items-center justify-center gap-2">
                                <div class="h-px w-6 bg-blue-500/20"></div>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.3em]">Cổng thông tin UTC eLibrary</p>
                                <div class="h-px w-6 bg-blue-500/20"></div>
                            </div>
                        </div>
                    </div>

                    <div v-if="status" class="mb-6 p-4 rounded-xl bg-emerald-950/30 border border-emerald-500/20 backdrop-blur-md flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        </div>
                        <p class="text-[12px] text-emerald-400/90 font-bold tracking-wide leading-tight">
                            {{ status }}
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div class="space-y-2 group">
                            <Label for="login" class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1 transition-colors group-focus-within:text-blue-400">
                                Tài khoản
                            </Label>
                            <div class="relative group-focus-within:ring-2 ring-blue-500/20 rounded-xl transition-all duration-300">
                                <User class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 transition-colors group-focus-within:text-blue-400" :size="18" />
                                <input
                                    id="login"
                                    type="text"
                                    placeholder="Email hoặc Mã định danh"
                                    v-model="form.login"
                                    class="h-12 w-full border border-white/5 bg-white/5 pl-11 pr-4 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300 outline-none"
                                    required
                                    autofocus
                                />
                            </div>
                            <p v-if="form.errors.login" class="text-[11px] text-red-500 font-medium pl-1">{{ form.errors.login }}</p>
                        </div>

                        <div class="space-y-2 group">
                            <Label for="password" class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1 transition-colors group-focus-within:text-blue-400">
                                Mật khẩu
                            </Label>
                            <div class="relative group">
                                <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 transition-colors group-focus-within:text-blue-400" :size="18" />
                                <input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="••••••••"
                                    v-model="form.password"
                                    class="h-12 w-full border border-white/5 bg-white/5 pl-11 pr-12 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300 outline-none"
                                    required
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white transition-colors p-1.5"
                                >
                                    <Eye v-if="!showPassword" :size="18" />
                                    <EyeOff v-else :size="18" />
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="text-[11px] text-red-500 font-medium pl-1">{{ form.errors.password }}</p>
                        </div>

                        <div class="flex items-center justify-between">
                             <label class="flex items-center gap-2 cursor-pointer group select-none">
                                <input type="checkbox" v-model="form.remember" class="hidden">
                                <div class="w-5 h-5 rounded-md border-2 border-white/10 bg-white/5 flex items-center justify-center transition-all group-hover:border-blue-500/50" :class="{ 'bg-blue-600 border-blue-600 shadow-[0_0_10px_rgba(59,130,246,0.5)]': form.remember }">
                                    <svg v-if="form.remember" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </div>
                                <span class="text-xs font-bold text-slate-500 group-hover:text-slate-400 transition-colors uppercase tracking-wider">Ghi nhớ</span>
                            </label>
                            <Link
                                :href="route('password.request')"
                                class="text-xs font-bold text-blue-400 hover:text-blue-300 transition-colors uppercase tracking-wider"
                            >
                                Quên mật khẩu?
                            </Link>
                        </div>

                        <button
                            type="submit"
                            class="group relative w-full h-14 overflow-hidden rounded-2xl bg-blue-600 text-base font-bold text-white shadow-[0_0_20px_rgba(59,130,246,0.5)] transition-all hover:bg-blue-500 hover:shadow-[0_0_30px_rgba(59,130,246,0.7)] active:scale-[0.98] disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            <div class="absolute inset-0 flex items-center justify-center transition-all duration-300 group-hover:translate-x-1">
                                 <span v-if="form.processing" class="flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Đang xử lý...
                                </span>
                                <span v-else class="flex items-center gap-2 uppercase tracking-widest leading-none">
                                    Đăng nhập <ArrowRight :size="20" class="group-hover:translate-x-1 transition-transform" />
                                </span>
                            </div>
                        </button>
                    </form>



                    <a
                        :href="route('auth.microsoft')"
                        class="flex items-center justify-center gap-3 w-full h-14 rounded-2xl bg-white/5 border border-white/10 text-white font-bold text-sm hover:bg-white/10 hover:border-white/20 transition-all active:scale-[0.98] shadow-lg"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 21 21">
                            <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                            <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                            <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                            <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
                        </svg>
                        <span class="uppercase tracking-widest leading-none">Đăng nhập với Microsoft 365</span>
                    </a>

                    <div class="text-center mt-4 pt-4 border-t border-white/10 space-y-2">
                        <div class="p-3.5 rounded-xl bg-blue-500/5 border border-blue-500/10 space-y-2 shadow-inner">
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest leading-none">Bạn chưa có tài khoản?</p>
                            <Link
                                :href="route('register')"
                                class="inline-flex items-center justify-center gap-2 w-full h-11 rounded-xl border-2 border-blue-500/30 bg-blue-500/10 text-sm font-black text-white hover:bg-blue-500/20 hover:border-blue-500/50 transition-all duration-300 active:scale-[0.98] shadow-[0_0_15px_rgba(59,130,246,0.1)]"
                            >
                                <span class="leading-none uppercase tracking-widest">Tạo tài khoản ngay</span>
                                <ArrowRight :size="16" class="text-blue-400" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-4 text-center animate-in fade-in slide-in-from-bottom-4 duration-1000 delay-300">
                <p class="text-white/90 text-xs font-bold tracking-widest uppercase drop-shadow-md">
                    &copy; 2026 UTC eLibrary System &bull; Version 2.0
                </p>
            </div>
        </div>
    </AuthLayout>
</template>
