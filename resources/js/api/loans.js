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
    renewalRequests(params = {}) {
        return client.get('/loans/renewal-requests', { params }).then((r) => r.data);
    },
    approveRenewalRequest(id, payload = {}) {
        return client.post(`/loans/renewal-requests/${id}/approve`, payload).then((r) => r.data);
    },
    rejectRenewalRequest(id, payload = {}) {
        return client.post(`/loans/renewal-requests/${id}/reject`, payload).then((r) => r.data);
    },
    borrowRequests(params = {}) {
        return client.get('/loans/borrow-requests', { params }).then((r) => r.data);
    },
    approveBorrowRequest(id, payload = {}) {
        return client.post(`/loans/borrow-requests/${id}/approve`, payload).then((r) => r.data);
    },
    rejectBorrowRequest(id, payload = {}) {
        return client.post(`/loans/borrow-requests/${id}/reject`, payload).then((r) => r.data);
    },
};
