import client from './axios';

export const meDigitalOrdersApi = {
    list(params = {}) {
        return client.get('/me/digital-orders', { params }).then((r) => r.data);
    },
    summary() {
        return client.get('/me/digital-orders/summary').then((r) => r.data);
    },
};
