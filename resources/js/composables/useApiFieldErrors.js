import { ref } from 'vue';
import { getFieldErrorsFromAxiosError, BOOK_ERROR_DISPLAY_KEYS } from '@/utils/laravelApiError';

/**
 * Quản lý lỗi theo field từ validation client + response API 422.
 * (Trong Vue, ref + các hàm này thay cho event bus — cùng một luồng dữ liệu, dễ theo dõi.)
 *
 * @param {Record<string, string>} fieldMap — map key Laravel → key field trên form
 * @param {{ displayKeys?: Set<string> }} [options] — form kho: `{ displayKeys: WAREHOUSE_ERROR_DISPLAY_KEYS }`
 */
export function useApiFieldErrors(fieldMap, options = {}) {
    const displayKeys = options.displayKeys ?? BOOK_ERROR_DISPLAY_KEYS;
    const fieldErrors = ref({});

    function clearField(key) {
        if (!fieldErrors.value[key]) return;
        const { [key]: _drop, ...rest } = fieldErrors.value;
        fieldErrors.value = rest;
    }

    function clearAll() {
        fieldErrors.value = {};
    }

    function applyAxios422(error) {
        fieldErrors.value = getFieldErrorsFromAxiosError(error, fieldMap, displayKeys);
    }

    function setClientErrors(errors) {
        fieldErrors.value = { ...(errors && typeof errors === 'object' ? errors : {}) };
    }

    return {
        fieldErrors,
        clearField,
        clearAll,
        applyAxios422,
        setClientErrors,
    };
}
