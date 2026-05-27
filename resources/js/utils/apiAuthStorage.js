/**
 * JWT / cache user phía trình duyệt — tách theo userId để tránh dùng lại token user khác khi đổi tài khoản.
 */
const LEGACY_TOKEN_KEY = 'token';
const LEGACY_USER_KEY = 'user';
const TOKEN_KEY_PREFIX = 'token_u_';
const USER_KEY_PREFIX = 'user_u_';

export const AUTH_CURRENT_USER_SESSION_KEY = 'utc_auth_current_user_id';

function normalizeUserId(userId) {
    const id = Number(userId);
    return Number.isInteger(id) && id > 0 ? id : null;
}

function buildTokenKey(userId) {
    const id = normalizeUserId(userId);
    return id ? `${TOKEN_KEY_PREFIX}${id}` : null;
}

function buildUserKey(userId) {
    const id = normalizeUserId(userId);
    return id ? `${USER_KEY_PREFIX}${id}` : null;
}

export function setCurrentAuthUserId(userId) {
    const id = normalizeUserId(userId);
    if (!id || typeof sessionStorage === 'undefined') return;
    try {
        sessionStorage.setItem(AUTH_CURRENT_USER_SESSION_KEY, String(id));
    } catch {
        /* ignore */
    }
}

export function getCurrentAuthUserId() {
    if (typeof sessionStorage === 'undefined') return null;
    try {
        return normalizeUserId(sessionStorage.getItem(AUTH_CURRENT_USER_SESSION_KEY));
    } catch {
        return null;
    }
}

export function clearLegacyAuthStorage() {
    if (typeof localStorage === 'undefined') return;
    try {
        localStorage.removeItem(LEGACY_TOKEN_KEY);
        localStorage.removeItem(LEGACY_USER_KEY);
    } catch {
        /* ignore */
    }
}

function collectScopedAuthKeys() {
    const keys = [];
    if (typeof localStorage === 'undefined') return keys;
    try {
        for (let i = 0; i < localStorage.length; i += 1) {
            const key = localStorage.key(i);
            if (!key) continue;
            if (key.startsWith(TOKEN_KEY_PREFIX) || key.startsWith(USER_KEY_PREFIX)) {
                keys.push(key);
            }
        }
    } catch {
        /* ignore */
    }
    return keys;
}

export function purgeAuthCredentialsExcept(keepUserId) {
    const keep = normalizeUserId(keepUserId);
    if (!keep) return;
    const keepToken = buildTokenKey(keep);
    const keepUser = buildUserKey(keep);
    for (const key of collectScopedAuthKeys()) {
        if (key === keepToken || key === keepUser) continue;
        try {
            localStorage.removeItem(key);
        } catch {
            /* ignore */
        }
    }
}

function purgeAllScopedAuthCredentials() {
    for (const key of collectScopedAuthKeys()) {
        try {
            localStorage.removeItem(key);
        } catch {
            /* ignore */
        }
    }
}

/**
 * Ghi token/user cho tài khoản hiện tại và dọn key legacy + key user khác.
 */
export function setClientApiCredentials({ userId, token = null, user = null } = {}) {
    const id = normalizeUserId(userId);
    if (!id) return;
    clearLegacyAuthStorage();
    setCurrentAuthUserId(id);
    purgeAuthCredentialsExcept(id);
    const tokenKey = buildTokenKey(id);
    const userKey = buildUserKey(id);
    if (token && tokenKey) {
        localStorage.setItem(tokenKey, token);
    }
    if (user && userKey) {
        localStorage.setItem(userKey, typeof user === 'string' ? user : JSON.stringify(user));
    }
}

/** Xóa JWT / cache user (đăng xuất hoặc hết session). */
export function clearClientApiCredentials() {
    const currentId = getCurrentAuthUserId();
    clearLegacyAuthStorage();
    if (currentId) {
        const tokenKey = buildTokenKey(currentId);
        const userKey = buildUserKey(currentId);
        try {
            if (tokenKey) localStorage.removeItem(tokenKey);
            if (userKey) localStorage.removeItem(userKey);
        } catch {
            /* ignore */
        }
    }
    purgeAllScopedAuthCredentials();
    if (typeof sessionStorage !== 'undefined') {
        try {
            sessionStorage.removeItem(AUTH_CURRENT_USER_SESSION_KEY);
        } catch {
            /* ignore */
        }
    }
}

export function getStoredApiToken(userId = null) {
    if (typeof localStorage === 'undefined') return null;
    const id = normalizeUserId(userId) ?? getCurrentAuthUserId();
    const tokenKey = buildTokenKey(id);
    if (!tokenKey) return null;
    try {
        const scoped = localStorage.getItem(tokenKey);
        if (scoped) return scoped;
        const legacy = localStorage.getItem(LEGACY_TOKEN_KEY);
        if (legacy && id === getCurrentAuthUserId()) {
            localStorage.setItem(tokenKey, legacy);
            localStorage.removeItem(LEGACY_TOKEN_KEY);
            return legacy;
        }
    } catch {
        return null;
    }
    return null;
}

export function hasStoredApiToken(userId = null) {
    return Boolean(getStoredApiToken(userId));
}

export function getStoredApiUser(userId = null) {
    if (typeof localStorage === 'undefined') return null;
    const id = normalizeUserId(userId) ?? getCurrentAuthUserId();
    const userKey = buildUserKey(id);
    if (!userKey) return null;
    try {
        const scoped = localStorage.getItem(userKey);
        if (scoped) return JSON.parse(scoped);
        const legacy = localStorage.getItem(LEGACY_USER_KEY);
        if (legacy && id === getCurrentAuthUserId()) {
            localStorage.setItem(userKey, legacy);
            localStorage.removeItem(LEGACY_USER_KEY);
            return JSON.parse(legacy);
        }
    } catch {
        return null;
    }
    return null;
}
