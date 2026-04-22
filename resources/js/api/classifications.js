import client from './axios';

export const classificationsApi = {
    list(params = {}) {
        return client.get('/classifications', { params }).then((r) => r.data);
    },
    listAll() {
        return client.get('/classifications/list').then((r) => r.data);
    },
    create(payload) {
        return client.post('/classifications', payload).then((r) => r.data);
    },
    update(id, payload) {
        return client.put(`/classifications/${id}`, payload).then((r) => r.data);
    },
    remove(id) {
        return client.delete(`/classifications/${id}`).then((r) => r.data);
    },
    downloadImportTemplate() {
        return client.get('/classifications/import-template', {
            responseType: 'blob',
        });
    },
};

