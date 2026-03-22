import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { postWebLogin } from '@/api/webSessionLogin';
import { applyLaravelErrorsToInertiaForm } from '@/utils/inertiaFormErrors';

export function useLoginPage() {
    const showPassword = ref(false);

    const form = useForm({
        login: '',
        password: '',
        remember: false,
    });

    const submit = () => {
        form.processing = true;
        postWebLogin({
            login: form.login,
            password: form.password,
            remember: form.remember,
        })
            .then((data) => {
                const token = data?.token;
                const userPayload = data?.user?.data ?? data?.user;
                if (token) {
                    localStorage.setItem('token', token);
                    if (userPayload) {
                        localStorage.setItem('user', JSON.stringify(userPayload));
                    }
                    form.reset('password');
                    router.visit(window.route('dashboard'), { replace: true });
                } else {
                    form.errors.login = 'Lỗi hệ thống: Không nhận được Token xác thực.';
                }
            })
            .catch((error) => {
                const status = error.response?.status;
                const body = error.response?.data;
                const msg = body?.messages;
                const messageText =
                    typeof msg === 'string' ? msg : Array.isArray(msg) ? msg[0] : body?.message;

                if (status === 419) {
                    form.errors.login =
                        'Phiên trang hết hạn (CSRF). Vui lòng tải lại trang và đăng nhập lại.';
                } else if (status === 401) {
                    form.errors.login = messageText || 'Thông tin đăng nhập không chính xác.';
                    form.reset('password');
                } else if (status === 422) {
                    applyLaravelErrorsToInertiaForm(form, error);
                } else {
                    form.errors.login = 'Đã có lỗi xảy ra. Vui lòng thử lại sau.';
                }
            })
            .finally(() => {
                form.processing = false;
            });
    };

    return {
        showPassword,
        form,
        submit,
    };
}
