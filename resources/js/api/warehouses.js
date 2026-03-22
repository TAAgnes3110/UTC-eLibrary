import client from './axios';

export const warehousesApi = {
    list(params = {}) {
        return client.get('/warehouses', { params }).then((r) => r.data);
    },
    get(id) {
        return client.get(`/warehouses/${id}`).then((r) => r.data);
    },
    create(payload) {
        return client.post('/warehouses', payload).then((r) => r.data);
    },
    update(id, payload) {
        return client.put(`/warehouses/${id}`, payload).then((r) => r.data);
    },
    remove(id) {
        return client.delete(`/warehouses/${id}`).then((r) => r.data);
    },
    trash() {
        return client.get('/warehouses/trash').then((r) => r.data);
    },
    restore(id) {
        return client.post(`/warehouses/restore/${id}`).then((r) => r.data);
    },
    restoreMany(ids = []) {
        return client.post('/warehouses/restore', { ids }).then((r) => r.data);
    },
    forceDelete(id) {
        return client.delete(`/warehouses/force/${id}`).then((r) => r.data);
    },
    forceDeleteMany(ids = []) {
        return client.post('/warehouses/force', { ids }).then((r) => r.data);
    },
    toggleStatus(id) {
        return client.post(`/warehouses/${id}/toggle-status`).then((r) => r.data);
    },
    export(params = {}) {
        return client.get('/warehouses/export', {
            params,
            responseType: 'blob',
        });
    },
    downloadImportTemplate() {
        return client.get('/warehouses/import-template', {
            responseType: 'blob',
        });
    },
    import(formData) {
        return client.post('/warehouses/import', formData).then((r) => r.data);
    },
};

