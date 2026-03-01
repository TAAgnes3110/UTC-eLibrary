/**
 * API module: Auth (login, register, verify-otp, resend-otp, reset-password, refresh, logout, user).
 */
import client from './axios';

export const authApi = {
    login(payload) {
        return client.post('/auth/login', payload).then((r) => r.data);
    },
    register(payload) {
        return client.post('/auth/register', payload).then((r) => r.data);
    },
    verifyOtp(payload) {
        return client.post('/auth/verify-otp', payload).then((r) => r.data);
    },
    resendOtp(payload) {
        return client.post('/auth/resend-otp', payload).then((r) => r.data);
    },
    resetPassword(payload) {
        return client.post('/auth/reset-password', payload).then((r) => r.data);
    },
    refresh() {
        return client.post('/auth/refresh').then((r) => r.data);
    },
    logout() {
        return client.post('/auth/logout').then((r) => r.data);
    },
    user() {
        return client.get('/auth/user').then((r) => r.data);
    },
};
