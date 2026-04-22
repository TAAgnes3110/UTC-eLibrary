import client from './axios';

export const classificationDetailsApi = {
    list(params = {}) {
        return client.get('/classification-details', { params }).then((r) => r.data);
    },
    create(payload) {
        return client.post('/classification-details', payload).then((r) => r.data);
    },
    update(id, payload) {
        return client.put(`/classification-details/${id}`, payload).then((r) => r.data);
    },
    remove(id) {
        return client.delete(`/classification-details/${id}`).then((r) => r.data);
    },
    downloadImportTemplate() {
        return client.get('/classification-details/import-template', {
            responseType: 'blob',
        });
    },
};

