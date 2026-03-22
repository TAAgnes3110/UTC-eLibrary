import axios from 'axios';

function getCsrfHeaders() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta?.content) {
        return { 'X-CSRF-TOKEN': meta.content };
    }
    const row = document.cookie.split('; ').find((r) => r.startsWith('XSRF-TOKEN='));
    if (row) {
        const val = decodeURIComponent(row.split('=').slice(1).join('='));
        return { 'X-XSRF-TOKEN': val };
    }
    return {};
}

/**
 * POST /login (route web) — có StartSession + CSRF → session cho Inertia + middleware auth.
 */
export async function postWebLogin(payload) {
    const { data } = await axios.post('/login', payload, {
        withCredentials: true,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...getCsrfHeaders(),
        },
    });
    return data;
}
