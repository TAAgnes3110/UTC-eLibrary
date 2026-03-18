import client from './axios';

export const classificationDetailsApi = {
    list(params = {}) {
        return client.get('/classification-details', { params }).then((r) => r.data);
    },
    downloadImportTemplate() {
        return client.get('/classification-details/import-template', {
            responseType: 'blob',
        });
    },
};

