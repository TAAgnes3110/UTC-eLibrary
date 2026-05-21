import client from './axios';

export const libraryCardsApi = {
    createForMe(payload) {
        if (payload instanceof FormData) {
            return client.post('/me/library-card', payload).then((r) => r.data);
        }
        return client.post('/me/library-card', payload).then((r) => r.data);
    },
    /** Chỉ khi hồ sơ đang chờ duyệt: hủy bản cũ + tạo bản mới (atomic). */
    replacePendingReviewForMe(payload) {
        if (payload instanceof FormData) {
            return client.post('/me/library-card/replace', payload).then((r) => r.data);
        }
        return client.post('/me/library-card/replace', payload).then((r) => r.data);
    },
    cancelForMe() {
        return client.delete('/me/library-card').then((r) => r.data);
    },
    list(params = {}) {
        return client.get('/library-cards', { params }).then((r) => r.data);
    },
    lookupForLoan(params = {}) {
        return client.get('/library-cards/lookup-for-loan', { params }).then((r) => r.data);
    },
    get(id) {
        return client.get(`/library-cards/${id}`).then((r) => r.data);
    },
    create(payload) {
        if (payload instanceof FormData) {
            return client.post('/library-cards', payload).then((r) => r.data);
        }
        return client.post('/library-cards', payload).then((r) => r.data);
    },
    update(id, payload) {
        return client.put(`/library-cards/${id}`, payload).then((r) => r.data);
    },
    remove(id) {
        return client.delete(`/library-cards/${id}`).then((r) => r.data);
    },
    trash(params = {}) {
        return client.get('/library-cards/trash', { params }).then((r) => r.data);
    },
    restore(id) {
        return client.post(`/library-cards/restore/${id}`).then((r) => r.data);
    },
    restoreMany(ids = []) {
        return client.post('/library-cards/restore', { ids }).then((r) => r.data);
    },
    forceDelete(id) {
        return client.delete(`/library-cards/force/${id}`).then((r) => r.data);
    },
    forceDeleteMany(ids = []) {
        return client.post('/library-cards/force', { ids }).then((r) => r.data);
    },
    updatePhoto(id, formData) {
        return client.post(`/library-cards/${id}/photo`, formData).then((r) => r.data);
    },
    approveReview(id) {
        return client.post(`/library-cards/${id}/approve-review`).then((r) => r.data);
    },
    confirmPickup(id) {
        return client.post(`/library-cards/${id}/confirm-pickup`).then((r) => r.data);
    },
    rejectReview(id, payload = {}) {
        return client.post(`/library-cards/${id}/reject-review`, payload).then((r) => r.data);
    },
    export(params = {}) {
        return client.get('/library-cards/export', {
            params,
            responseType: 'blob',
        });
    },
};
