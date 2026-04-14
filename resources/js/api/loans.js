import client from './axios';

export const loansApi = {
    list(params = {}) {
        return client.get('/loans', { params }).then((r) => r.data);
    },
    statistics(params = {}) {
        return client.get('/loans/statistics', { params }).then((r) => r.data);
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
    bulkDelete(payload) {
        return client.post('/loans/bulk-delete', payload).then((r) => r.data);
    },
    bulkReturn(payload) {
        return client.post('/loans/bulk-return', payload).then((r) => r.data);
    },
    trash(params = {}) {
        return client.get('/loans/trash', { params }).then((r) => r.data);
    },
    restore(id) {
        return client.post(`/loans/restore/${id}`).then((r) => r.data);
    },
    restoreMany(ids = []) {
        return client.post('/loans/restore', { ids }).then((r) => r.data);
    },
    forceDelete(id) {
        return client.delete(`/loans/force/${id}`).then((r) => r.data);
    },
    forceDeleteMany(ids = []) {
        return client.post('/loans/force', { ids }).then((r) => r.data);
    },
};
