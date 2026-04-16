import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { postWebLogin } from '@/api/webSessionLogin';
import { applyLaravelErrorsToInertiaForm } from '@/utils/inertiaFormErrors';
import { isLibraryStaffUserType } from '@/utils/readerAuth';
import { buildStaffWorkQueueToastMessage, STAFF_WORK_QUEUE_HINT_KEY } from '@/utils/staffWorkQueueHint';
import { toast } from '@/store/toast';

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
                    const dest = isLibraryStaffUserType(userPayload?.user_type) ? 'admin.dashboard' : 'reader.home';
                    const queueMsg = buildStaffWorkQueueToastMessage(data?.staff_work_queue);
                    if (queueMsg) {
                        toast.info(queueMsg, { title: 'Việc cần xử lý' });
                        try {
                            sessionStorage.setItem(STAFF_WORK_QUEUE_HINT_KEY, '1');
                        } catch {
                            //
                        }
                    }
                    router.visit(window.route(dest), { replace: true });
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
