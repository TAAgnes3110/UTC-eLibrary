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
    import(formData) {
        return client
            .post('/books/import', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
            .then((r) => r.data);
    },
    updateCover(id, formData) {
        return client
            .post(`/books/${id}/image`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
            .then((r) => r.data);
    },
    bulkUpdateCover(formData) {
        return client
            .post('/books/image-bulk', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
            .then((r) => r.data);
    },
};

