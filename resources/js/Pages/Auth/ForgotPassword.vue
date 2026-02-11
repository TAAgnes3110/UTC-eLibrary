<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import BrandHeader from '@/Components/Auth/BrandHeader.vue'
import AuthCardTitle from '@/Components/Auth/AuthCardTitle.vue'
import AuthFooter from '@/Components/Auth/AuthFooter.vue'
import SubmitButton from '@/Components/Auth/SubmitButton.vue'
import FormField from '@/Components/Auth/FormField.vue'
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

            <BrandHeader />

            <!-- Forgot Password Card -->
            <div class="w-full max-w-[440px] bg-slate-900/60 backdrop-blur-3xl shadow-[0_32px_64px_-16px_rgba(0,0,0,0.5)] rounded-[2rem] border border-white/10 overflow-hidden animate-in zoom-in-95 fade-in duration-700">
                <div class="px-5 py-6 sm:px-8">
                    <AuthCardTitle title="QUÊN MẬT KHẨU" subtitle="Khôi phục tài khoản">
                        <template #icon><Mail :size="24" class="animate-pulse" /></template>
                    </AuthCardTitle>

                    <p class="text-center text-[13px] text-slate-500 mb-6 leading-relaxed">
                        Nhập email đăng ký tài khoản của bạn. Hệ thống sẽ gửi mã xác thực OTP để quyền đặt lại mật khẩu.
                    </p>

                    <form @submit.prevent="submit" class="space-y-6">
                        <FormField id="email" label="Email đăng ký" :error="form.errors.email">
                            <template #icon><Mail :size="18" /></template>
                            <input
                                id="email" type="email" placeholder="example@gmail.com"
                                v-model="form.email"
                                class="h-12 w-full border border-white/5 bg-white/5 pl-11 pr-4 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300 outline-none"
                                required autofocus
                            />
                        </FormField>

                        <SubmitButton :loading="form.processing">
                            Gửi mã xác thực <ArrowRight :size="18" class="group-hover:translate-x-1 transition-transform" />
                        </SubmitButton>
                    </form>

                    <div class="text-center mt-6 pt-6 border-t border-white/10">
                        <Link :href="route('login')"
                            class="flex items-center justify-center gap-2 w-full h-12 rounded-xl bg-white/5 border border-white/10 text-slate-400 font-bold text-sm hover:bg-white/10 hover:text-white transition-all active:scale-[0.98]">
                            Quay lại trang Đăng nhập
                        </Link>
                    </div>
                </div>
            </div>

            <AuthFooter />
        </div>
    </AuthLayout>
</template>
