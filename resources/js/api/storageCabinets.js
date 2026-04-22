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
    createSlot(cabinetId, payload) {
        return client.post(`/storage-cabinets/${cabinetId}/slots`, payload).then((r) => r.data);
    },
    updateSlot(cabinetId, slotId, payload) {
        return client.put(`/storage-cabinets/${cabinetId}/slots/${slotId}`, payload).then((r) => r.data);
    },
    removeSlot(cabinetId, slotId) {
        return client.delete(`/storage-cabinets/${cabinetId}/slots/${slotId}`).then((r) => r.data);
    },
    listSlots(params = {}) {
        return client.get('/storage-slots', { params }).then((r) => r.data);
    },
};
