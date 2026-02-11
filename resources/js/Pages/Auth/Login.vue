<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import BrandHeader from '@/Components/Auth/BrandHeader.vue'
import AuthCardTitle from '@/Components/Auth/AuthCardTitle.vue'
import AuthFooter from '@/Components/Auth/AuthFooter.vue'
import SubmitButton from '@/Components/Auth/SubmitButton.vue'
import FormField from '@/Components/Auth/FormField.vue'
import { User, Lock, Eye, EyeOff, ArrowRight, LogIn } from 'lucide-vue-next'
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
    form.processing = true;
    axios.post(route('login'), {
        login: form.login,
        password: form.password,
        remember: form.remember
    })
    .then(response => {
        if (response.data.token) {
            localStorage.setItem('token', response.data.token);
            localStorage.setItem('user', JSON.stringify(response.data.user));
            form.reset('password');
            window.location.href = route('dashboard');
        } else {
            form.errors.login = "Lỗi hệ thống: Không nhận được Token xác thực.";
            form.processing = false;
        }
    })
    .catch(error => {
        form.processing = false;
        const status = error.response?.status;
        if (status === 401) {
            form.errors.login = error.response.data.messages || 'Thông tin đăng nhập không chính xác.';
            form.reset('password');
        } else if (status === 422) {
            const errors = error.response.data.errors;
            if (errors?.login) form.errors.login = errors.login[0];
            if (errors?.password) form.errors.password = errors.password[0];
        } else {
            form.errors.login = 'Đã có lỗi xảy ra. Vui lòng thử lại sau.';
        }
    });
}
</script>

<template>
    <AuthLayout>
        <div class="font-sans w-full flex flex-col items-center">
            <Head title="Đăng nhập" />

            <BrandHeader />

            <!-- Login Card -->
            <div class="w-full max-w-[440px] bg-slate-900/60 backdrop-blur-3xl shadow-[0_32px_64px_-16px_rgba(0,0,0,0.5)] rounded-[2rem] border border-white/10 overflow-hidden animate-in zoom-in-95 fade-in duration-700">
                <div class="px-5 py-6 sm:px-8">
                    <AuthCardTitle title="Đăng nhập" subtitle="Cổng thông tin UTC eLibrary">
                        <template #icon><LogIn :size="24" class="animate-pulse" /></template>
                    </AuthCardTitle>

                    <!-- Status Alert -->
                    <div v-if="status" class="mb-6 p-4 rounded-xl bg-emerald-950/30 border border-emerald-500/20 backdrop-blur-md flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        </div>
                        <p class="text-[12px] text-emerald-400/90 font-bold tracking-wide leading-tight">{{ status }}</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Login Field -->
                        <FormField id="login" label="Tài khoản" :error="form.errors.login">
                            <template #icon><User :size="18" /></template>
                            <input
                                id="login" type="text"
                                placeholder="Email hoặc Mã định danh"
                                v-model="form.login"
                                class="h-12 w-full border border-white/5 bg-white/5 pl-11 pr-4 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300 outline-none"
                                required autofocus
                            />
                        </FormField>

                        <!-- Password Field -->
                        <FormField id="password" label="Mật khẩu" :error="form.errors.password">
                            <template #icon><Lock :size="18" /></template>
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="••••••••"
                                v-model="form.password"
                                class="h-12 w-full border border-white/5 bg-white/5 pl-11 pr-12 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300 outline-none"
                                required
                            />
                            <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white transition-colors p-1.5">
                                <Eye v-if="!showPassword" :size="18" />
                                <EyeOff v-else :size="18" />
                            </button>
                        </FormField>

                        <!-- Remember & Forgot -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer group select-none">
                                <input type="checkbox" v-model="form.remember" class="hidden">
                                <div class="w-5 h-5 rounded-md border-2 border-white/10 bg-white/5 flex items-center justify-center transition-all group-hover:border-blue-500/50" :class="{ 'bg-blue-600 border-blue-600 shadow-[0_0_10px_rgba(59,130,246,0.5)]': form.remember }">
                                    <svg v-if="form.remember" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </div>
                                <span class="text-xs font-bold text-slate-500 group-hover:text-slate-400 transition-colors uppercase tracking-wider">Ghi nhớ</span>
                            </label>
                            <Link :href="route('password.request')" class="text-xs font-bold text-blue-400 hover:text-blue-300 transition-colors uppercase tracking-wider">
                                Quên mật khẩu?
                            </Link>
                        </div>

                        <SubmitButton :loading="form.processing" label="Đăng nhập">
                            Đăng nhập <ArrowRight :size="20" class="group-hover:translate-x-1 transition-transform" />
                        </SubmitButton>
                    </form>

                    <!-- Microsoft Login -->
                    <a
                        :href="route('auth.microsoft')"
                        class="mt-4 flex items-center justify-center gap-3 w-full min-h-[56px] h-auto py-3 rounded-2xl bg-white/5 border border-white/10 text-white font-bold text-xs sm:text-sm hover:bg-white/10 hover:border-white/20 transition-all active:scale-[0.98] shadow-lg"
                    >
                        <svg class="shrink-0" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 21 21">
                            <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                            <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                            <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                            <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
                        </svg>
                        <span class="uppercase tracking-wide leading-tight text-center">Đăng nhập với Microsoft 365</span>
                    </a>

                    <!-- Register Link -->
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

            <AuthFooter />
        </div>
    </AuthLayout>
</template>
