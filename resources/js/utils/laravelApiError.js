import { apiGenericFallback } from '@/constants/adminUiMessages';

function firstValidationMessage(value) {
    if (Array.isArray(value)) return value.find(Boolean) || '';
    if (typeof value === 'string') return value;
    return '';
}

export function extractLaravelValidationErrors(error) {
    const data = error?.response?.data;
    if (!data || typeof data !== 'object') return null;
    if (data.errors && typeof data.errors === 'object' && !Array.isArray(data.errors)) {
        return data.errors;
    }
    if (data.data?.errors && typeof data.data.errors === 'object' && !Array.isArray(data.data.errors)) {
        return data.data.errors;
    }
    return null;
}

/** Các key hiển thị được trên form sách (còn lại gộp vào `general`). */
export const BOOK_ERROR_DISPLAY_KEYS = new Set([
    'title',
    'warehouse',
    'classification',
    'classification_detail',
    'quantity',
    'price',
    'published_year',
    'registration_number',
    'book_code',
    'description',
    'resource_kind',
    'authors',
    'publisher',
    'general',
]);

export const WAREHOUSE_ERROR_DISPLAY_KEYS = new Set(['code', 'name', 'general']);

export const USER_ERROR_DISPLAY_KEYS = new Set([
    'name',
    'email',
    'phone',
    'code',
    'role',
    'password',
    'password_confirmation',
    'general',
]);

function appendMessage(out, target, msg) {
    if (out[target]) out[target] += ' ' + msg;
    else out[target] = msg;
}

export function getFieldErrorsFromAxiosError(error, fieldMap = {}, displayKeys = BOOK_ERROR_DISPLAY_KEYS) {
    const errs = extractLaravelValidationErrors(error);
    if (!errs) return {};
    const out = {};
    for (const [k, v] of Object.entries(errs)) {
        const msg = firstValidationMessage(v);
        if (!msg) continue;
        let target = fieldMap[k];
        if (!target) {
            if (k.startsWith('thesis_metadata.')) {
                target = 'general';
            } else if (k.includes('.')) {
                target = 'general';
            } else {
                target = k;
            }
        }
        if (!displayKeys.has(target)) {
            appendMessage(out, 'general', msg);
            continue;
        }
        appendMessage(out, target, msg);
    }
    return out;
}

export const BOOK_FORM_FIELD_MAP = {
    title: 'title',
    warehouse_id: 'warehouse',
    classification_id: 'classification',
    classification_detail_id: 'classification_detail',
    quantity: 'quantity',
    price: 'price',
    published_year: 'published_year',
    registration_number: 'registration_number',
    book_code: 'book_code',
    summary: 'description',
    resource_kind: 'resource_kind',
};

export const WAREHOUSE_FORM_FIELD_MAP = {
    code: 'code',
    name: 'name',
    parent_id: 'general',
    is_active: 'general',
};

/** Laravel UserRequest — `role` gửi lên được merge thành `user_type`. */
export const USER_FORM_FIELD_MAP = {
    name: 'name',
    email: 'email',
    phone: 'phone',
    code: 'code',
    user_type: 'role',
    role: 'role',
    password: 'password',
    password_confirmation: 'password_confirmation',
    is_active: 'general',
};

export function getLaravelErrorMessage(error, fallback = apiGenericFallback) {
    const data = error?.response?.data;
    if (!data || typeof data !== 'object') {
        return typeof error?.message === 'string' && error.message ? error.message : fallback;
    }
    const errs = extractLaravelValidationErrors(error);
    if (errs && typeof errs === 'object' && !Array.isArray(errs)) {
        const msgs = [];
        for (const k of Object.keys(errs)) {
            const v = errs[k];
            if (Array.isArray(v)) msgs.push(...v.filter(Boolean));
            else if (typeof v === 'string' && v) msgs.push(v);
        }
        if (msgs.length) return msgs.join(' ');
    }
    if (typeof data.message === 'string' && data.message.trim()) return data.message.trim();
    if (typeof data.error === 'string' && data.error.trim()) return data.error.trim();
    return fallback;
}
