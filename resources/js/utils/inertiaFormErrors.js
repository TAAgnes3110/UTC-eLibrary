import { extractLaravelValidationErrors } from '@/utils/laravelApiError';

export function applyLaravelErrorsToInertiaForm(form, axiosError) {
    const errs = extractLaravelValidationErrors(axiosError);
    if (!errs || typeof errs !== 'object') return;
    if (typeof form.clearErrors === 'function') {
        form.clearErrors();
    }
    for (const [key, val] of Object.entries(errs)) {
        const msg = Array.isArray(val) ? val[0] : val;
        if (typeof msg === 'string' && msg.trim()) {
            form.setError(key, msg);
        }
    }
}
