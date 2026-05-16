import client from './axios';

export const digitalPurchaseCartApi = {
    list() {
        return client.get('/me/digital-purchase-cart').then((r) => r.data);
    },
    count() {
        return client.get('/me/digital-purchase-cart/count').then((r) => r.data);
    },
    addItem(payload) {
        return client.post('/me/digital-purchase-cart/items', payload).then((r) => r.data);
    },
    removeItem(digitalAssetId) {
        return client.delete(`/me/digital-purchase-cart/items/${digitalAssetId}`).then((r) => r.data);
    },
    bulkRemove(digitalAssetIds) {
        return client
            .post('/me/digital-purchase-cart/items/bulk-delete', {
                digital_asset_ids: Array.isArray(digitalAssetIds) ? digitalAssetIds : [digitalAssetIds],
            })
            .then((r) => r.data);
    },
};
