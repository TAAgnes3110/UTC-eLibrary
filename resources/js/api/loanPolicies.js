import client from './axios';

export const loanPoliciesApi = {
    list() {
        return client.get('/loan-policies').then((r) => r.data);
    },
    create(payload) {
        return client.post('/loan-policies', payload).then((r) => r.data);
    },
    update(id, payload) {
        return client.put(`/loan-policies/${id}`, payload).then((r) => r.data);
    },
};
