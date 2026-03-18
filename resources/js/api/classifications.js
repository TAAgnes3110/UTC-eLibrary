import client from './axios';

export const classificationsApi = {
    list(params = {}) {
        return client.get('/classifications', { params }).then((r) => r.data);
    },
    listAll() {
        return client.get('/classifications/list').then((r) => r.data);
    },
    downloadImportTemplate() {
        return client.get('/classifications/import-template', {
            responseType: 'blob',
        });
    },
};

