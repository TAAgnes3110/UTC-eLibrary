import client from './axios'

export const profileApi = {
    get() {
        return client.get('/me/profile').then((r) => r.data)
    },
    update(payload) {
        return client.put('/me/profile', payload).then((r) => r.data)
    },
    updateAvatar(formData) {
        return client.post('/me/avatar', formData).then((r) => r.data)
    },
    updatePassword(payload) {
        return client.put('/me/password', payload).then((r) => r.data)
    },
    submitProfileUpdateRequest(formData) {
        return client.post('/me/profile-update-requests', formData).then((r) => r.data)
    },
    myProfileUpdateRequests() {
        return client.get('/me/profile-update-requests').then((r) => r.data)
    },
}
