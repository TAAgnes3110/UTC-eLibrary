import client from './axios';

export const usersApi = {
    list(params = {}) {
        return client.get('/users', { params }).then((r) => r.data);
    },
    listReaders(params = {}) {
        return client.get('/users', { params: { ...params, type: 'reader' } }).then((r) => r.data);
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
    forceDelete(id) {
        return client.delete(`/users/force/${id}`).then((r) => r.data);
    },
    toggleStatus(id) {
        return client.post(`/users/${id}/toggle-status`).then((r) => r.data);
    },
    updateAvatar(id, formData) {
        return client
            .post(`/users/${id}/avatar`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
            .then((r) => r.data);
    },
};
