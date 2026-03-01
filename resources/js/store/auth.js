/**
 * Auth state: user, token. Có thể chuyển sang Pinia (defineStore) khi cài pinia.
 */
import { ref, computed } from 'vue';

export const authUser = ref(null);
export const authToken = ref(typeof localStorage !== 'undefined' ? localStorage.getItem('token') : null);

export const isAuthenticated = computed(() => !!authToken.value);

export function setAuthUser(user) {
    authUser.value = user;
}
export function setAuthToken(token) {
    authToken.value = token;
    if (typeof localStorage !== 'undefined') {
        if (token) localStorage.setItem('token', token);
        else localStorage.removeItem('token');
    }
}
export function clearAuth() {
    authUser.value = null;
    authToken.value = null;
    if (typeof localStorage !== 'undefined') {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
    }
}
