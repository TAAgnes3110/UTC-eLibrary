import axios from 'axios';

function getCsrfHeaders() {
    const row = document.cookie.split('; ').find((r) => r.startsWith('XSRF-TOKEN='));
    if (row) {
        const val = decodeURIComponent(row.split('=').slice(1).join('='));
        return { 'X-XSRF-TOKEN': val };
    }
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta?.content) {
        return { 'X-CSRF-TOKEN': meta.content };
    }
    return {};
}

function baseHeaders() {
    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
}

async function postLoginOnce(payload) {
    const { data } = await axios.post('/login', payload, {
        withCredentials: true,
        headers: {
            ...baseHeaders(),
            ...getCsrfHeaders(),
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

        await axios.get('/login', {
            withCredentials: true,
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        return postLoginOnce(payload);
    }
}
