import client from './axios'

export const profileApi = {
    get() {
        return client.get('/me/profile').then((r) => r.data)
    },
    update(payload) {
        return client.put('/me/profile', payload).then((r) => r.data)
    },
    updatePassword(payload) {
        return client.put('/me/password', payload).then((r) => r.data)
    },
}
