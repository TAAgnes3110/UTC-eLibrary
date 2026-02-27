<script setup>
import AuthLayout from "@/Layouts/AuthLayout.vue";
import BrandHeader from "@/Components/Auth/BrandHeader.vue";
import AuthCardTitle from "@/Components/Auth/AuthCardTitle.vue";
import AuthFooter from "@/Components/Auth/AuthFooter.vue";
import SubmitButton from "@/Components/Auth/SubmitButton.vue";
import FormField from "@/Components/Auth/FormField.vue";
import StatusAlert from "@/Components/Auth/StatusAlert.vue";
import OtpInput from "@/Components/Auth/OtpInput.vue";
import ResendOtp from "@/Components/Auth/ResendOtp.vue";
import { Lock, KeyRound, ArrowRight, Eye, EyeOff } from "lucide-vue-next";
import { ref } from "vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";

const showPassword = ref(false);
const showConfirmPassword = ref(false);
const step = ref("otp");
const otpInputRef = ref(null);

const props = defineProps({
    email: String,
    status: String,
});

const form = useForm({
    email: props.email || "",
    otp: "",
    password: "",
    password_confirmation: "",
});

const localStatus = ref(props.status || '')

const resendOtp = () => {
    form.processing = true;
    form.clearErrors();
    localStatus.value = '';
    window.axios
        .post("/auth/resend-otp", { email: form.email, name: 'Người dùng' })
        .then((response) => {
            form.processing = false;
            otpInputRef.value?.reset();
            form.reset("otp");
            localStatus.value = response.data.messages || 'Đã gửi lại mã OTP. Vui lòng kiểm tra email.';
        })
        .catch((error) => {
            form.processing = false;
            if (error.response?.data?.messages) {
                form.setError("otp", error.response.data.messages);
            } else {
                form.setError("otp", "Không thể gửi lại OTP.");
            }
        });
};

const submit = () => {
    if (step.value === "otp") {
        if (!form.otp || form.otp.length !== 6) {
            form.setError('otp', "Vui lòng nhập mã OTP 6 chữ số.");
            return;
        }
        form.clearErrors('otp');
        step.value = "password";
        return;
    }

    form.processing = true;
    form.clearErrors();
    localStatus.value = '';
    window.axios
        .post("/auth/reset-password", {
            email: form.email,
            otp: form.otp,
            password: form.password,
            password_confirmation: form.password_confirmation,
        })
        .then((response) => {
            form.processing = false;
            window.location.href = window.route("login");
        })
        .catch((error) => {
            form.processing = false;
            form.reset("password", "password_confirmation");
            if (error.response?.status === 422) {
                const errs = error.response.data.errors;
                for (let key in errs) {
                    form.setError(key, errs[key][0]);
                }
            } else if (error.response?.status === 400) {
                form.setError("otp", error.response.data.messages || "Mã OTP không hợp lệ.");
                step.value = "otp";
            } else if (error.response?.data?.messages) {
                form.setError("password", error.response.data.messages);
            } else {
                form.setError("password", "Đã có lỗi xảy ra.");
            }
        });
};
</script>

<template>
    <AuthLayout>
        <div class="font-sans w-full flex flex-col items-center">
            <Head title="Đặt lại mật khẩu" />

            <BrandHeader />

            <!-- Reset Password Card -->
            <div
                class="w-full max-w-[450px] bg-slate-900/60 backdrop-blur-3xl shadow-[0_32px_64px_-16px_rgba(0,0,0,0.5)] rounded-[2rem] border border-white/10 overflow-hidden animate-in zoom-in-95 fade-in duration-700"
            >
                <div class="px-5 py-5 sm:px-8">
                    <AuthCardTitle
                        :title="
                            step === 'otp' ? 'XÁC THỰC OTP' : 'ĐẶT MẬT KHẨU'
                        "
                        :subtitle="
                            step === 'otp'
                                ? 'Bước 1: Xác minh'
                                : 'Bước 2: Bảo mật'
                        "
                    >
                        <template #icon>
                            <KeyRound
                                v-if="step === 'otp'"
                                :size="24"
                                class="animate-pulse"
                            />
                            <Lock v-else :size="24" class="animate-bounce" />
                        </template>
                    </AuthCardTitle>

                    <p
                        class="text-center text-[13px] text-slate-500 mb-4 leading-relaxed"
                    >
                        Thiết lập mật khẩu mới cho tài khoản
                        <span class="text-slate-300 font-bold ml-1">{{
                            email
                        }}</span>
                    </p>

                    <StatusAlert :message="localStatus" />

                    <form @submit.prevent="submit" class="space-y-6">
                        <input type="hidden" v-model="form.email" />

                        <!-- Step 1: OTP -->
                        <div
                            v-if="step === 'otp'"
                            class="space-y-6 animate-in slide-in-from-right-10 duration-500"
                        >
                            <div class="space-y-4">
                                <label
                                    class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] ml-1 block text-center"
                                >
                                    Mã xác thực 6 số
                                </label>
                                <OtpInput
                                    ref="otpInputRef"
                                    v-model="form.otp"
                                    :error="form.errors.otp"
                                    size="sm"
                                />
                                <ResendOtp
                                    :loading="form.processing"
                                    @resend="resendOtp"
                                />
                            </div>

                            <SubmitButton :loading="false">
                                Xác thực & Tiếp tục
                                <ArrowRight
                                    :size="18"
                                    class="group-hover:translate-x-1 transition-transform"
                                />
                            </SubmitButton>
                        </div>

                        <!-- Step 2: Password -->
                        <div
                            v-if="step === 'password'"
                            class="space-y-5 animate-in slide-in-from-right-10 duration-500"
                        >
                            <FormField
                                id="password"
                                label="Mật khẩu mới"
                                :error="form.errors.password"
                            >
                                <template #icon><Lock :size="18" /></template>
                                <input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="••••••••"
                                    v-model="form.password"
                                    class="h-12 w-full border border-white/5 bg-white/5 pl-11 pr-12 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300 outline-none"
                                    required
                                    autofocus
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white transition-colors p-1.5"
                                >
                                    <Eye
                                        v-if="!showPassword"
                                        :size="18"
                                    /><EyeOff v-else :size="18" />
                                </button>
                            </FormField>

                            <FormField
                                id="password_confirmation"
                                label="Xác nhận mật khẩu mới"
                                :error="form.errors.password_confirmation"
                            >
                                <template #icon><Lock :size="18" /></template>
                                <input
                                    id="password_confirmation"
                                    :type="
                                        showConfirmPassword
                                            ? 'text'
                                            : 'password'
                                    "
                                    placeholder="••••••••"
                                    v-model="form.password_confirmation"
                                    class="h-12 w-full border border-white/5 bg-white/5 pl-11 pr-12 rounded-xl text-white placeholder:text-slate-600 focus:bg-blue-600/10 focus:border-blue-500/50 shadow-inner transition-all duration-300 outline-none"
                                    required
                                />
                                <button
                                    type="button"
                                    @click="
                                        showConfirmPassword =
                                            !showConfirmPassword
                                    "
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white transition-colors p-1.5"
                                >
                                    <Eye
                                        v-if="!showConfirmPassword"
                                        :size="18"
                                    /><EyeOff v-else :size="18" />
                                </button>
                            </FormField>

                            <div class="flex gap-3 pt-2">
                                <button
                                    type="button"
                                    @click="step = 'otp'"
                                    class="h-14 px-6 rounded-2xl bg-white/10 text-white font-bold hover:bg-white/20 transition-all uppercase tracking-widest text-xs"
                                >
                                    Quay lại
                                </button>
                                <SubmitButton
                                    :loading="form.processing"
                                    class="flex-1"
                                >
                                    Hoàn tất
                                    <ArrowRight
                                        :size="18"
                                        class="group-hover:translate-x-1 transition-transform"
                                    />
                                </SubmitButton>
                            </div>
                        </div>
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

            <AuthFooter />
        </div>
    </AuthLayout>
</template>
