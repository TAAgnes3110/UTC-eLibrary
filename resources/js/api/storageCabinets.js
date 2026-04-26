import client from './axios';

export const storageCabinetsApi = {
    list(params = {}) {
        return client.get('/storage-cabinets', { params }).then((r) => r.data);
    },
    create(payload) {
        return client.post('/storage-cabinets', payload).then((r) => r.data);
    },
    update(id, payload) {
        return client.put(`/storage-cabinets/${id}`, payload).then((r) => r.data);
    },
    remove(id) {
        return client.delete(`/storage-cabinets/${id}`).then((r) => r.data);
    },
};
