import client from './axios';

export const booksApi = {
    list(params = {}) {
        return client.get('/books', { params }).then((r) => r.data);
    },
    get(id) {
        return client.get(`/books/${id}`).then((r) => r.data);
    },
    create(payload) {
        return client.post('/books', payload).then((r) => r.data);
    },
    update(id, payload) {
        return client.put(`/books/${id}`, payload).then((r) => r.data);
    },
    remove(id) {
        return client.delete(`/books/${id}`).then((r) => r.data);
    },
    trash(params = {}) {
        return client.get('/books/trash', { params }).then((r) => r.data);
    },
    restore(id) {
        return client.post(`/books/restore/${id}`).then((r) => r.data);
    },
    restoreMany(ids = []) {
        return client.post('/books/restore', { ids }).then((r) => r.data);
    },
    forceDelete(id) {
        return client.delete(`/books/force/${id}`).then((r) => r.data);
    },
    forceDeleteMany(ids = []) {
        return client.post('/books/force', { ids }).then((r) => r.data);
    },
    downloadImportTemplate() {
        return client.get('/books/import-template', {
            responseType: 'blob',
        });
    },
    export(params = {}) {
        return client.get('/books/export', {
            params,
            responseType: 'blob',
        });
    },
    exportLost(params = {}) {
        return client.get('/books/export-lost', {
            params,
            responseType: 'blob',
        });
    },
    import(formData) {
        return client.post('/books/import', formData).then((r) => r.data);
    },
    previewIdentifiers(params = {}) {
        return client.get('/books/preview-identifiers', { params }).then((r) => r.data);
    },
    storageSuggestions(params = {}) {
        return client.get('/books/storage-suggestions', { params }).then((r) => r.data);
    },
    updateCover(id, formData) {
        return client.post(`/books/${id}/image`, formData).then((r) => r.data);
    },
    bulkUpdateCover(formData) {
        return client.post('/books/image-bulk', formData).then((r) => r.data);
    },
    uploadDigitalAsset(id, formData) {
        return client
            .post(`/books/${id}/digital-assets`, formData, {
                timeout: 300000,
            })
            .then((r) => r.data);
    },
};

