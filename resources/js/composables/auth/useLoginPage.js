import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { postWebLogin } from '@/api/webSessionLogin';
import { applyLaravelErrorsToInertiaForm } from '@/utils/inertiaFormErrors';
import { isLibraryStaffUserType } from '@/utils/readerAuth';
import { toast } from '@/store/toast';
import { setClientApiCredentials } from '@/utils/apiAuthStorage';

export function useLoginPage(options = {}) {
    const showPassword = ref(false);
    const fromRegister = options.fromRegister === true;

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
                const payload = data?.data && typeof data.data === 'object' ? data.data : data;
                const token = payload?.token ?? data?.token ?? null;
                const userPayload = payload?.user?.data ?? payload?.user ?? data?.user?.data ?? data?.user ?? null;
                const loginSuccess = String(payload?.status ?? data?.status ?? '').toLowerCase() === 'success';

                // Có backend trả session login thành công nhưng không kèm token trong body.
                // Vẫn cho điều hướng theo user/session để tránh chặn đăng nhập.
                const userId = Number(userPayload?.id);
                if (userId > 0 && (token || userPayload)) {
                    setClientApiCredentials({
                        userId,
                        token: token || null,
                        user: userPayload || null,
                    });
                }
                if (token || userPayload || loginSuccess) {
                    form.reset('password');
                    const dest = isLibraryStaffUserType(userPayload?.user_type) ? 'admin.dashboard' : 'reader.home';
                    toast.success('Đăng nhập thành công.', { title: 'Xác thực' });
                    if (fromRegister && !isLibraryStaffUserType(userPayload?.user_type)) {
                        toast.success('Bạn có thể đổi sang Sinh viên/Giáo viên trong mục Thông tin cá nhân.', {
                            title: 'Gợi ý hồ sơ',
                        });
                    }
                    router.visit(window.route(dest), { replace: true });
                    return;
                }

                // Fallback an toàn: backend đã trả 2xx nhưng payload không đúng format mong đợi.
                // Điều hướng về dashboard để web middleware tự phân luồng theo quyền.
                toast.success('Đăng nhập thành công.', { title: 'Xác thực' });
                router.visit(window.route('dashboard'), { replace: true });
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
