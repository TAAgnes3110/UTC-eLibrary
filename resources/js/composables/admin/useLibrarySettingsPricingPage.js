import { onMounted, ref } from 'vue';
import { librarySettingsApi } from '@/api/librarySettings';
import { toast } from '@/store/toast';
import { extractLaravelValidationErrors } from '@/utils/laravelApiError';

function map422ToObject(err) {
    const raw = extractLaravelValidationErrors(err);
    if (!raw) return {};
    const out = {};
    for (const [k, v] of Object.entries(raw)) {
        const msg = Array.isArray(v) ? v[0] : v;
        out[k] = typeof msg === 'string' ? msg : String(msg);
    }
    return out;
}

export function useLibrarySettingsPricingPage() {
    const loading = ref(false);
    const saving = ref(false);
    const loadError = ref('');
    const errors = ref({});

    const form = ref({
        digital_default_pdf_download_price_vnd: 0,
    });
    const snapshot = ref(null);

    const fetchSettings = async () => {
        loading.value = true;
        loadError.value = '';
        errors.value = {};
        try {
            const payload = await librarySettingsApi.get();
            const data = payload?.data ?? payload;
            form.value = {
                digital_default_pdf_download_price_vnd: Number(data?.digital_default_pdf_download_price_vnd ?? 0),
            };
            snapshot.value = JSON.parse(JSON.stringify(form.value));
        } catch (e) {
            loadError.value = e?.response?.data?.messages || 'Không tải được cấu hình.';
            toast.error(loadError.value);
        } finally {
            loading.value = false;
        }
    };

    onMounted(() => {
        fetchSettings();
    });

    const save = async () => {
        if (saving.value) return;
        saving.value = true;
        errors.value = {};
        try {
            await librarySettingsApi.update({
                digital_default_pdf_download_price_vnd: Math.max(0, Math.trunc(Number(form.value.digital_default_pdf_download_price_vnd || 0))),
            });
            toast.success('Đã lưu giá tài liệu số.');
            snapshot.value = JSON.parse(JSON.stringify(form.value));
        } catch (e) {
            if (e?.response?.status === 422) {
                errors.value = map422ToObject(e);
                toast.error('Vui lòng kiểm tra lại dữ liệu đã nhập.');
            } else {
                toast.error(e?.response?.data?.messages ?? 'Lưu thất bại.');
            }
        } finally {
            saving.value = false;
        }
    };

    const cancel = () => {
        errors.value = {};
        if (snapshot.value) {
            form.value = JSON.parse(JSON.stringify(snapshot.value));
        }
    };

    return {
        form,
        loading,
        saving,
        loadError,
        errors,
        fetchSettings,
        save,
        cancel,
    };
}
