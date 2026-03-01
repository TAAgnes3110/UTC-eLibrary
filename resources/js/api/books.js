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
    trash() {
        return client.get('/books/trash').then((r) => r.data);
    },
    restore(id) {
        return client.post(`/books/restore/${id}`).then((r) => r.data);
    },
    forceDelete(id) {
        return client.delete(`/books/force/${id}`).then((r) => r.data);
    },
    searchPublishers(q = '') {
        return client.get('/books/search-publishers', { params: { q } }).then((r) => r.data);
    },
    searchAuthors(q = '') {
        return client.get('/books/search-authors', { params: { q } }).then((r) => r.data);
    },
    uploadDocument(formData) {
        return client
            .post('/books/upload-document', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
            .then((r) => r.data);
    },
    exportUrl(ids = []) {
        const base = client.defaults.baseURL || '/api/v1';
        return ids.length ? `${base}/books/export?ids=${ids.join(',')}` : `${base}/books/export`;
    },
};
