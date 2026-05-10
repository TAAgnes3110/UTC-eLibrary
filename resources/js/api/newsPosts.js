import client from './axios';

export const newsPostsApi = {
    list(params = {}) {
        return client.get('/news-posts', { params }).then((r) => r.data);
    },
    get(id) {
        return client.get(`/news-posts/id/${id}`).then((r) => r.data);
    },
    create(formData) {
        return client.post('/news-posts', formData).then((r) => r.data);
    },
    update(id, formData) {
        return client.post(`/news-posts/${id}?_method=PUT`, formData).then((r) => r.data);
    },
    uploadContentImage(file) {
        const fd = new FormData();
        fd.append('image', file);
        return client.post('/news-posts/upload-content-image', fd).then((r) => r.data);
    },
    remove(id) {
        return client.delete(`/news-posts/${id}`).then((r) => r.data);
    },
    updateThumbnail(id, formData) {
        return client.post(`/news-posts/${id}/thumbnail`, formData).then((r) => r.data);
    },
    bulkUpdateThumbnail(formData) {
        return client.post('/news-posts/thumbnail-bulk', formData).then((r) => r.data);
    },
};
