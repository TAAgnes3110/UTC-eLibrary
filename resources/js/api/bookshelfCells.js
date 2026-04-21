import client from './axios';

export const bookshelfCellsApi = {
    list(params = {}) {
        return client.get('/bookshelf-cells', { params }).then((r) => r.data);
    },
    create(payload) {
        return client.post('/bookshelf-cells', payload).then((r) => r.data);
    },
    listByWarehouse(warehouseId, params = {}) {
        return client.get(`/warehouses/${warehouseId}/bookshelf-cells`, { params }).then((r) => r.data);
    },
    generateByWarehouse(warehouseId, payload = {}) {
        return client.post(`/warehouses/${warehouseId}/bookshelf-cells/generate`, payload).then((r) => r.data);
    },
    update(id, payload) {
        return client.put(`/bookshelf-cells/${id}`, payload).then((r) => r.data);
    },
    remove(id) {
        return client.delete(`/bookshelf-cells/${id}`).then((r) => r.data);
    },
    export(params = {}) {
        return client.get('/bookshelf-cells/export', {
            params,
            responseType: 'blob',
        });
    },
};
