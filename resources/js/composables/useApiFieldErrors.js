import { ref } from 'vue';
import { getFieldErrorsFromAxiosError, BOOK_ERROR_DISPLAY_KEYS } from '@/utils/laravelApiError';

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
