import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { applyLaravelErrorsToInertiaForm } from '@/utils/inertiaFormErrors';

export function useRegisterPage() {
    const form = useForm({
        code: '',
        name: '',
        email: '',
        phone: '',
        date_of_birth: '',
        gender: 'male',
        address: '',
        password: '',
        password_confirmation: '',
    });

    const dateInputRef = ref(null);
    const showPassword = ref(false);
    const showPasswordConfirmation = ref(false);

    const submit = () => {
        form.processing = true;
        form.clearErrors();
        window.axios
            .post('/auth/register', {
                code: form.code,
                name: form.name,
                email: form.email,
                phone: form.phone,
                date_of_birth: form.date_of_birth,
                gender: form.gender,
                address: form.address,
                password: form.password,
                password_confirmation: form.password_confirmation,
            })
            .then((response) => {
                form.processing = false;
                if (response.data.status === 'success' || response.status === 200) {
                    window.location.href = window.route('verify-otp') + '?email=' + form.email;
                }
            })
            .catch((error) => {
                form.processing = false;
                form.password = '';
                form.password_confirmation = '';
                if (error.response?.status === 422) {
                    applyLaravelErrorsToInertiaForm(form, error);
                } else if (error.response?.data?.messages) {
                    form.setError('email', error.response.data.messages);
                } else {
                    form.setError('email', 'Đã có lỗi xảy ra. Vui lòng thử lại sau.');
                }
            });
    };

    return {
        form,
        dateInputRef,
        showPassword,
        showPasswordConfirmation,
        submit,
    };
}
