import client from './axios';

export const usersApi = {
    list(params = {}) {
        return client.get('/users', { params }).then((r) => r.data);
    },
    get(id) {
        return client.get(`/users/${id}`).then((r) => r.data);
    },
    create(payload) {
        return client.post('/users', payload).then((r) => r.data);
    },
    update(id, payload) {
        return client.put(`/users/${id}`, payload).then((r) => r.data);
    },
    remove(id) {
        return client.delete(`/users/${id}`).then((r) => r.data);
    },
    trash() {
        return client.get('/users/trash').then((r) => r.data);
    },
    restore(id) {
        return client.post(`/users/restore/${id}`).then((r) => r.data);
    },
    restoreMany(ids = []) {
        return client.post('/users/restore', { ids }).then((r) => r.data);
    },
    forceDelete(id) {
        return client.delete(`/users/force/${id}`).then((r) => r.data);
    },
    forceDeleteMany(ids = []) {
        return client.post('/users/force', { ids }).then((r) => r.data);
    },
    toggleStatus(id) {
        return client.post(`/users/${id}/toggle-status`).then((r) => r.data);
    },
    updateAvatar(id, formData) {
        return client.post(`/users/${id}/avatar`, formData).then((r) => r.data);
    },
    export(params = {}) {
        return client.get('/users/export', {
            params,
            responseType: 'blob',
        });
    },
    bulkUpdateAvatar(formData) {
        return client.post('/users/avatar-bulk', formData).then((r) => r.data);
    },
    listProfileUpdateRequests(params = {}) {
        return client.get('/users/profile-update-requests', { params }).then((r) => r.data);
    },
    approveProfileUpdateRequest(id, payload = {}) {
        return client.post(`/users/profile-update-requests/${id}/approve`, payload).then((r) => r.data);
    },
    rejectProfileUpdateRequest(id, payload = {}) {
        return client.post(`/users/profile-update-requests/${id}/reject`, payload).then((r) => r.data);
    },
    hideProfileUpdateRequest(id) {
        return client.post(`/users/profile-update-requests/${id}/hide`).then((r) => r.data);
    },
};
