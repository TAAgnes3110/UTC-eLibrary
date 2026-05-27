/** Prefill duyệt yêu cầu mượn (admin) — tách theo staff user + TTL ngắn. */
const PREFILL_KEY_PREFIX = 'loanBorrowRequestApprovalPrefill_u_';
const LEGACY_PREFILL_KEY = 'loanBorrowRequestApprovalPrefill';
const PREFILL_TTL_MS = 10 * 60 * 1000;

function normalizeStaffUserId(staffUserId) {
    const id = Number(staffUserId);
    return Number.isInteger(id) && id > 0 ? id : null;
}

export function buildBorrowRequestPrefillKey(staffUserId) {
    const id = normalizeStaffUserId(staffUserId);
    return id ? `${PREFILL_KEY_PREFIX}${id}` : null;
}

export function clearLegacyBorrowRequestPrefill() {
    if (typeof sessionStorage === 'undefined') return;
    try {
        sessionStorage.removeItem(LEGACY_PREFILL_KEY);
    } catch {
        /* ignore */
    }
}

export function stashBorrowRequestPrefill(staffUserId, payload) {
    const key = buildBorrowRequestPrefillKey(staffUserId);
    if (!key || typeof sessionStorage === 'undefined' || !payload) return;
    clearLegacyBorrowRequestPrefill();
    try {
        sessionStorage.setItem(
            key,
            JSON.stringify({
                ...payload,
                saved_at: Date.now(),
            }),
        );
    } catch {
        /* ignore */
    }
}

/**
 * @param {string|number|null} expectedRequestId — khớp query `from_borrow_request`
 * @returns {object|null}
 */
export function readBorrowRequestPrefill(staffUserId, expectedRequestId = null) {
    const key = buildBorrowRequestPrefillKey(staffUserId);
    if (!key || typeof sessionStorage === 'undefined') return null;
    try {
        const raw = sessionStorage.getItem(key);
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        if (!parsed || typeof parsed !== 'object') return null;
        const savedAt = Number(parsed.saved_at || 0);
        if (savedAt > 0 && Date.now() - savedAt > PREFILL_TTL_MS) {
            sessionStorage.removeItem(key);
            return null;
        }
        if (expectedRequestId != null && String(parsed.request_id || '') !== String(expectedRequestId)) {
            return null;
        }
        return parsed;
    } catch {
        return null;
    }
}

export function clearBorrowRequestPrefill(staffUserId) {
    const key = buildBorrowRequestPrefillKey(staffUserId);
    if (!key || typeof sessionStorage === 'undefined') return;
    try {
        sessionStorage.removeItem(key);
    } catch {
        /* ignore */
    }
    clearLegacyBorrowRequestPrefill();
}
