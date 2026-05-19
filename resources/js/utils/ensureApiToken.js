import axios from 'axios';
import { ensureSanctumCsrfCookie, getApiCsrfHeaders } from '@/utils/apiCsrf';

export function extractApiTokenFromResponse(data) {
    return data?.token ?? data?.data?.token ?? null;
}

/**
 * Cấp JWT mới từ session Inertia (cookie) — không cần bearer còn hạn.
 */
export async function fetchSessionApiToken() {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        await ensureSanctumCsrfCookie();
        const res = await axios.post('/api/v1/auth/session-token', null, {
            withCredentials: true,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                domain: window.location.origin,
                ...getApiCsrfHeaders(),
            },
        });
        const token = extractApiTokenFromResponse(res.data);
        if (token) {
            localStorage.setItem('token', token);
        }
        return token;
    } catch {
        return null;
    }
}

export async function refreshStoredApiToken() {
    if (typeof window === 'undefined') {
        return null;
    }

    const oldToken = localStorage.getItem('token');
    if (!oldToken) {
        return null;
    }

    try {
        const res = await axios.post('/api/v1/auth/refresh', null, {
            withCredentials: true,
            headers: { Authorization: `Bearer ${oldToken}` },
        });
        const token = extractApiTokenFromResponse(res.data);
        if (token) {
            localStorage.setItem('token', token);
        }
        return token;
    } catch {
        return null;
    }
}

/** Ưu tiên session-token (admin Inertia), fallback refresh JWT cũ. */
export async function ensureApiToken() {
    const fromSession = await fetchSessionApiToken();
    if (fromSession) {
        return fromSession;
    }

    return refreshStoredApiToken();
}
