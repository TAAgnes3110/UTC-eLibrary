import client from './axios';

export const loansApi = {
    list(params = {}) {
        return client.get('/loans', { params }).then((r) => r.data);
    },
    export(params = {}) {
        return client.get('/loans/export', {
            params,
            responseType: 'blob',
        });
    },
    get(id) {
        return client.get(`/loans/${id}`).then((r) => r.data);
    },
    create(payload) {
        return client.post('/loans', payload).then((r) => r.data);
    },
    update(id, payload) {
        return client.put(`/loans/${id}`, payload).then((r) => r.data);
    },
    remove(id) {
        return client.delete(`/loans/${id}`).then((r) => r.data);
    },
    returnBooks(id, payload) {
        return client.post(`/loans/${id}/return`, payload).then((r) => r.data);
    },
};
