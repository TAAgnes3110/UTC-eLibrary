import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { applyLaravelErrorsToInertiaForm } from '@/utils/inertiaFormErrors';

export function useResetPasswordPage(props) {
    const showPassword = ref(false);
    const showConfirmPassword = ref(false);
    const step = ref('otp');
    const otpInputRef = ref(null);

    const form = useForm({
        email: props.email || '',
        otp: '',
        password: '',
        password_confirmation: '',
    });

    const localStatus = ref(props.status || '');

    watch(
        () => props.email,
        (v) => {
            if (v !== undefined) form.email = v || '';
        },
    );

    watch(
        () => props.status,
        (v) => {
            if (v !== undefined) localStatus.value = v || '';
        },
    );

    const email = computed(() => props.email ?? form.email);

    const resendOtp = () => {
        form.processing = true;
        form.clearErrors();
        localStatus.value = '';
        window.axios
            .post('/auth/resend-otp', { email: form.email, name: 'Người dùng' })
            .then((response) => {
                form.processing = false;
                otpInputRef.value?.reset();
                form.reset('otp');
                localStatus.value = response.data.messages || 'Đã gửi lại mã OTP. Vui lòng kiểm tra email.';
            })
            .catch((error) => {
                form.processing = false;
                if (error.response?.status === 422) {
                    applyLaravelErrorsToInertiaForm(form, error);
                } else if (error.response?.data?.messages) {
                    form.setError('otp', error.response.data.messages);
                } else {
                    form.setError('otp', 'Không thể gửi lại OTP.');
                }
            });
    };

    const submit = () => {
        if (step.value === 'otp') {
            if (!form.otp || form.otp.length !== 6) {
                form.setError('otp', 'Vui lòng nhập mã OTP 6 chữ số.');
                return;
            }
            form.clearErrors('otp');
            step.value = 'password';
            return;
        }

        form.processing = true;
        form.clearErrors();
        localStatus.value = '';
        window.axios
            .post('/auth/reset-password', {
                email: form.email,
                otp: form.otp,
                password: form.password,
                password_confirmation: form.password_confirmation,
            })
            .then(() => {
                form.processing = false;
                window.location.href = window.route('login');
            })
            .catch((error) => {
                form.processing = false;
                form.reset('password', 'password_confirmation');
                if (error.response?.status === 422) {
                    applyLaravelErrorsToInertiaForm(form, error);
                } else if (error.response?.status === 400) {
                    form.setError('otp', error.response.data.messages || 'Mã OTP không hợp lệ.');
                    step.value = 'otp';
                } else if (error.response?.data?.messages) {
                    form.setError('password', error.response.data.messages);
                } else {
                    form.setError('password', 'Đã có lỗi xảy ra.');
                }
            });
    };

    return {
        showPassword,
        showConfirmPassword,
        step,
        otpInputRef,
        form,
        localStatus,
        email,
        resendOtp,
        submit,
    };
}
