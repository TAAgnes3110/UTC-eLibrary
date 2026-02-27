<script setup>
import { ref } from "vue";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import BrandHeader from "@/Components/Auth/BrandHeader.vue";
import AuthCardTitle from "@/Components/Auth/AuthCardTitle.vue";
import AuthFooter from "@/Components/Auth/AuthFooter.vue";
import SubmitButton from "@/Components/Auth/SubmitButton.vue";
import StatusAlert from "@/Components/Auth/StatusAlert.vue";
import OtpInput from "@/Components/Auth/OtpInput.vue";
import ResendOtp from "@/Components/Auth/ResendOtp.vue";
import { ShieldCheck, ArrowRight, Undo2 } from "lucide-vue-next";
import { Head, Link, useForm, router } from "@inertiajs/vue3";

const props = defineProps({
    email: String,
    status: String,
});

const form = useForm({
    email: props.email ?? "",
    otp: "",
});

const otpInputRef = ref(null);

const localStatus = ref(props.status || '')

const submit = () => {
    form.processing = true
    form.clearErrors()
    localStatus.value = ''
    window.axios.post('/auth/verify-otp', {
        email: form.email,
        otp: form.otp
    })
    .then(response => {
        form.processing = false;
        if (response.data.status === 'success' || response.status === 200) {
            window.location.href = window.route('login');
        }
    })
    .catch(error => {
        form.processing = false
        if (error.response?.status === 422) {
            const errs = error.response.data.errors
            if (errs?.otp) form.setError('otp', errs.otp[0])
            if (errs?.email) form.setError('email', errs.email[0])
        } else if (error.response?.data?.messages) {
            form.setError('otp', error.response.data.messages)
        } else {
            form.setError('otp', 'Đã có lỗi xảy ra.')
        }
    })
}

const resendOtp = () => {
    form.processing = true
    form.clearErrors()
    localStatus.value = ''
    window.axios.post('/auth/resend-otp', { email: form.email })
    .then(response => {
        form.processing = false
        otpInputRef.value?.reset()
        form.reset('otp')
        localStatus.value = response.data.messages || 'Đã gửi lại mã OTP. Vui lòng kiểm tra email.'
    })
    .catch(error => {
        form.processing = false
        if (error.response?.data?.messages) {
            form.setError('otp', error.response.data.messages)
        } else {
            form.setError('otp', 'Lỗi không thể gửi lại mã OTP.')
        }
    })
}
</script>

<template>
    <AuthLayout>
        <div class="font-sans w-full flex flex-col items-center">
            <Head title="Xác thực OTP" />

            <BrandHeader />

            <div
                class="w-full max-w-[480px] bg-slate-900/60 backdrop-blur-3xl shadow-[0_32px_64px_-16px_rgba(0,0,0,0.5)] rounded-[2.5rem] border border-white/10 overflow-hidden animate-in zoom-in-95 duration-700"
            >
                <div class="px-5 py-5 sm:px-7">
                    <AuthCardTitle title="XÁC THỰC OTP">
                        <template #icon><ShieldCheck :size="20" /></template>
                    </AuthCardTitle>

                    <StatusAlert :message="localStatus" />

                    <!-- Info Section -->
                    <div class="text-center mb-5 space-y-1">
                        <p
                            class="text-slate-600 text-[10px] font-bold uppercase tracking-widest opacity-80 leading-tight"
                        >
                            Mã xác minh đã được gửi tới
                        </p>
                        <p
                            class="text-base font-bold text-slate-300 tracking-wide leading-tight"
                        >
                            {{ email }}
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <OtpInput
                            ref="otpInputRef"
                            v-model="form.otp"
                            :error="form.errors.otp"
                        />

                        <div class="flex items-center justify-end gap-2 pr-1">
                            <ResendOtp
                                :loading="form.processing"
                                @resend="resendOtp"
                            />
                        </div>

                        <SubmitButton :loading="form.processing">
                            {{
                                form.processing
                                    ? "ĐANG XÁC THỰC..."
                                    : "XÁC NHẬN & HOÀN TẤT"
                            }}
                            <ArrowRight
                                v-if="!form.processing"
                                :size="18"
                                class="group-hover:translate-x-1 transition-transform"
                            />
                        </SubmitButton>
                    </form>

                    <div class="mt-4 flex flex-col items-center gap-4">
                        <div
                            class="flex flex-col items-center gap-3 w-full pt-3 border-t border-white/5"
                        >
                            <Link
                                :href="route('register')"
                                class="flex items-center justify-center gap-2 w-full h-12 rounded-xl bg-white/5 border border-white/10 text-slate-400 font-bold text-sm hover:bg-white/10 hover:text-white transition-all active:scale-[0.98]"
                            >
                                <Undo2 :size="16" />
                                Quay lại trang Đăng ký
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <AuthFooter />
        </div>
    </AuthLayout>
</template>
