import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { applyLaravelErrorsToInertiaForm } from '@/utils/inertiaFormErrors';

function looksLikeEnglishValidationMessage(message) {
    if (typeof message !== 'string') return false;
    const lower = message.toLowerCase();
    return (
        lower.includes('field is required') ||
        lower.includes('is required') ||
        lower.includes('the ') && lower.includes('field') && lower.includes('required') ||
        lower.includes('given data was invalid') ||
        (lower.includes('must be') && lower.includes('valid')) ||
        (lower.includes('invalid') && /[a-z]/i.test(message))
    );
}

function translateEnglishRegisterError(key, message) {
    const requiredMap = {
        name: 'Họ và tên không được để trống.',
        code: 'Mã định danh không được để trống.',
        email: 'Email không được để trống.',
        phone: 'Số điện thoại không được để trống.',
        password: 'Mật khẩu không được để trống.',
        password_confirmation: 'Xác nhận mật khẩu không được để trống.',
        user_type: 'Loại tài khoản không hợp lệ.',
    };

    const lower = typeof message === 'string' ? message.toLowerCase() : '';
    const isRequired =
        lower.includes('field is required') ||
        lower.includes('is required') ||
        (lower.includes('required') && lower.includes('field'));

    const invalidMap = {
        email: 'Email không hợp lệ.',
        phone: 'Số điện thoại không hợp lệ.',
        code: 'Mã định danh không hợp lệ.',
        password_confirmation: 'Xác nhận mật khẩu không khớp.',
    };

    if (looksLikeEnglishValidationMessage(message)) {
        if (isRequired) return requiredMap[key] ?? 'Vui lòng điền đầy đủ thông tin.';
        return invalidMap[key] ?? 'Vui lòng kiểm tra lại thông tin.';
    }
    return message;
}

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
                    // Chuẩn hoá message để tránh hiển thị tiếng Anh trên UI.
                    for (const [key, val] of Object.entries(form.errors || {})) {
                        if (typeof val === 'string') {
                            form.setError(key, translateEnglishRegisterError(key, val));
                        }
                    }
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
