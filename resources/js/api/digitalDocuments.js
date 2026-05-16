import client from './axios';

export const digitalDocumentsApi = {
    publicList(params = {}) {
        return client.get('/digital-document-submissions', { params }).then((r) => r.data);
    },
    list(params = {}) {
        return client.get('/me/digital-document-submissions', { params }).then((r) => r.data);
    },
    submit(payload) {
        return client
            .post('/me/digital-document-submissions', payload, {
                timeout: 300000,
            })
            .then((r) => r.data);
    },
    hide(id) {
        return client.post(`/me/digital-document-submissions/${id}/hide`).then((r) => r.data);
    },
    approve(id, payload = {}) {
        return client.post(`/digital-document-submissions/${id}/approve`, payload).then((r) => r.data);
    },
    reject(id, payload = {}) {
        return client.post(`/digital-document-submissions/${id}/reject`, payload).then((r) => r.data);
    },
};
