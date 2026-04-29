import client from './axios';

export const meBorrowRequestsApi = {
    list(params = {}) {
        return client.get('/me/loan-borrow-requests', { params }).then((r) => r.data);
    },
    preview(bookIds = []) {
        return client.post('/me/loan-borrow-requests/preview', { book_ids: bookIds }).then((r) => r.data);
    },
    create(payload) {
        return client.post('/me/loan-borrow-requests', payload).then((r) => r.data);
    },
};

