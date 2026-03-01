/**
 * API module: Master data (faculties, departments, cohorts, role_types) — gọi 1 lần khi load app, cache ở store.
 */
import client from './axios';

export const masterDataApi = {
    get() {
        return client.get('/master-data').then((r) => r.data);
    },
};
