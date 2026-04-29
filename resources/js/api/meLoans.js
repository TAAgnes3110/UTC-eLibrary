import client from './axios';

export const meLoansApi = {
    list(params = {}) {
        return client.get('/me/loans', { params }).then((r) => r.data);
    },
    summary() {
        return client.get('/me/loans/summary').then((r) => r.data);
    },
    export(params = {}) {
        return client.get('/me/loans/export', {
            params,
            responseType: 'blob',
        });
    },
    get(id) {
        return client.get(`/me/loans/${id}`).then((r) => r.data);
    },
    remove(id) {
        return client.delete(`/me/loans/${id}`).then((r) => r.data);
    },
    requestRenewal(id, payload = {}) {
        return client.post(`/me/loans/${id}/renewal-requests`, payload).then((r) => r.data);
    },
};
