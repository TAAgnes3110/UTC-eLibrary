/**
 * Composable: auth state (user, token), login/logout helpers.
 */
import { ref, computed } from 'vue';
import { authApi } from '../api';

const user = ref(null);
const token = ref(localStorage.getItem('token') || null);

export function useAuth() {
    const isAuthenticated = computed(() => !!token.value);

    function setAuth(tok, userData) {
        token.value = tok;
        user.value = userData;
        if (tok) localStorage.setItem('token', tok);
        if (userData) localStorage.setItem('user', JSON.stringify(userData));
    }

    function clearAuth() {
        token.value = null;
        user.value = null;
        localStorage.removeItem('token');
        localStorage.removeItem('user');
    }

    async function fetchUser() {
        if (!token.value) return null;
        const data = await authApi.user();
        user.value = data?.data ?? data?.user ?? data;
        return user.value;
    }

    return {
        user,
        token,
        isAuthenticated,
        setAuth,
        clearAuth,
        fetchUser,
    };
}
