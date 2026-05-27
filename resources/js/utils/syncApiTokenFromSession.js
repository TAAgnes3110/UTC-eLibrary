import { fetchSessionApiToken } from '@/utils/ensureApiToken';
import {
    clearClientApiCredentials,
    getCurrentAuthUserId,
    hasStoredApiToken,
    purgeAuthCredentialsExcept,
    setCurrentAuthUserId,
} from '@/utils/apiAuthStorage';

/**
 * Đồng bộ JWT từ session Inertia khi chưa có token API (tránh gọi lặp → 429).
 */
export async function syncApiTokenFromSession(authUser) {
    if (typeof window === 'undefined' || !authUser?.id) {
        return;
    }

    const uid = Number(authUser.id);
    const previousId = getCurrentAuthUserId();
    if (previousId && previousId !== uid) {
        clearClientApiCredentials();
    }
    setCurrentAuthUserId(uid);
    purgeAuthCredentialsExcept(uid);

    if (hasStoredApiToken(uid)) {
        return;
    }

    await fetchSessionApiToken({ force: true, userId: uid });
}
