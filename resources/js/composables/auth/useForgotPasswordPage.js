import { useForm } from '@inertiajs/vue3';
import { applyLaravelErrorsToInertiaForm } from '@/utils/inertiaFormErrors';

export function useForgotPasswordPage() {
    const form = useForm({
        email: '',
    });

    const submit = () => {
        form.processing = true;
        form.clearErrors();
        window.axios
            .post('/auth/resend-otp', { email: form.email, name: 'Người dùng' })
            .then(() => {
                form.processing = false;
                window.location.href = window.route('password.reset') + '?email=' + form.email;
            })
            .catch((error) => {
                form.processing = false;
                if (error.response?.status === 422) {
                    applyLaravelErrorsToInertiaForm(form, error);
                } else if (error.response?.data?.messages) {
                    form.setError('email', error.response.data.messages);
                } else {
                    form.setError('email', 'Có lỗi xảy ra. Vui lòng thử lại sau.');
                }
            });
    };

    return {
        form,
        submit,
    };
}
