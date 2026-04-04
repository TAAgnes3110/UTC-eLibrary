import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { applyLaravelErrorsToInertiaForm } from '@/utils/inertiaFormErrors';

export function useVerifyOtpPage(props) {
    const form = useForm({
        email: props.email ?? '',
        otp: '',
    });

    const otpInputRef = ref(null);
    const localStatus = ref(props.status || '');

    watch(
        () => props.email,
        (v) => {
            if (v !== undefined) form.email = v ?? '';
        },
    );

    watch(
        () => props.status,
        (v) => {
            if (v !== undefined) localStatus.value = v || '';
        },
    );

    const submit = () => {
        form.processing = true;
        form.clearErrors();
        localStatus.value = '';
        window.axios
            .post('/auth/verify-otp', {
                email: form.email,
                otp: form.otp,
            })
            .then((response) => {
                form.processing = false;
                if (response.data.status === 'success' || response.status === 200) {
                    window.location.href = window.route('login');
                }
            })
            .catch((error) => {
                form.processing = false;
                if (error.response?.status === 422) {
                    applyLaravelErrorsToInertiaForm(form, error);
                } else if (error.response?.data?.messages) {
                    form.setError('otp', error.response.data.messages);
                } else {
                    form.setError('otp', 'Đã có lỗi xảy ra.');
                }
            });
    };

    const resendOtp = () => {
        form.processing = true;
        form.clearErrors();
        localStatus.value = '';
        window.axios
            .post('/auth/resend-otp', { email: form.email })
            .then((response) => {
                form.processing = false;
                otpInputRef.value?.reset();
                form.reset('otp');
                localStatus.value =
                    response.data.messages || 'Đã gửi lại mã OTP. Vui lòng kiểm tra email.';
            })
            .catch((error) => {
                form.processing = false;
                if (error.response?.status === 422) {
                    applyLaravelErrorsToInertiaForm(form, error);
                } else if (error.response?.data?.messages) {
                    form.setError('otp', error.response.data.messages);
                } else {
                    form.setError('otp', 'Lỗi không thể gửi lại mã OTP.');
                }
            });
    };

    const email = computed(() => form.email);

    return {
        form,
        otpInputRef,
        localStatus,
        email,
        submit,
        resendOtp,
    };
}
