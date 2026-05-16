import client from './axios';

export const digitalAssetsApi = {
    createPaymentOrder(digitalAssetIds) {
        return client
            .post('/me/digital-payment-orders', {
                digital_asset_ids: Array.isArray(digitalAssetIds) ? digitalAssetIds : [digitalAssetIds],
            })
            .then((r) => r.data);
    },
    orderStatus(publicId, options = {}) {
        const params = options.sync ? { sync: 1 } : {};
        return client.get(`/me/orders/${publicId}`, { params }).then((r) => r.data);
    },
    cancelOrder(publicId) {
        return client.post(`/me/orders/${publicId}/cancel`).then((r) => r.data);
    },
};
