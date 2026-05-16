import client from './axios';

export const librarySettingsApi = {
    get() {
        return client.get('/library-settings').then((r) => r.data);
    },
    update(payload) {
        return client.put('/library-settings', payload).then((r) => r.data);
    },
    updatePricing(payload) {
        return client.put('/library-settings/pricing', payload).then((r) => r.data);
    },
};

