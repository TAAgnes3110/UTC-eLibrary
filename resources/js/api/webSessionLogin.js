import axios from 'axios';
import {
    ensureSanctumCsrfCookie,
    getApiCsrfHeaders,
    resetSanctumCsrfCookieCache,
} from '@/utils/apiCsrf';

async function postLoginOnce(payload) {
    await ensureSanctumCsrfCookie();
    const { data } = await axios.post('/login', payload, {
        withCredentials: true,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...getApiCsrfHeaders(),
        },
    });
    return data;
}

/**
 * POST /login (route web) — có StartSession + CSRF → session cho Inertia + middleware auth.
 */
export async function postWebLogin(payload) {
    try {
        return await postLoginOnce(payload);
    } catch (error) {
        if (error?.response?.status !== 419) {
            throw error;
        }

        resetSanctumCsrfCookieCache();
        await ensureSanctumCsrfCookie({ force: true });
        return postLoginOnce(payload);
    }
}
