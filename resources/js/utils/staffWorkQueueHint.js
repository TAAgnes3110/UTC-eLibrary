/**
 * Tránh nhắc lặp hàng đợi việc trong cùng phiên tab (sau khi đã hiện toast đăng nhập / lần đầu vào admin).
 * Xóa khi đăng xuất để lần đăng nhập sau hiện lại.
 */
export const STAFF_WORK_QUEUE_HINT_KEY = 'utc_elibrary_staff_work_queue_hint';

export function clearStaffWorkQueueSessionHint() {
    try {
        sessionStorage.removeItem(STAFF_WORK_QUEUE_HINT_KEY);
    } catch {
        // ignore
    }
}

/**
 * @param {Record<string, unknown>|null|undefined} q
 * @returns {string}
 */
export function buildStaffWorkQueueToastMessage(q) {
    if (!q || typeof q !== 'object') {
        return '';
    }
    const parts = [];
    const pr = Number(q.library_cards_pending_review ?? 0);
    if (pr > 0) {
        parts.push(`${pr} hồ sơ xin cấp thẻ chờ duyệt`);
    }
    const pp = Number(q.library_cards_pending_payment ?? 0);
    if (pp > 0) {
        parts.push(`${pp} hồ sơ chờ thanh toán lệ phí`);
    }
    const pu = Number(q.user_profile_update_requests_pending ?? 0);
    if (pu > 0) {
        parts.push(`${pu} yêu cầu cập nhật hồ sơ chờ duyệt`);
    }
    const lr = Number(q.loan_renewal_requests_pending ?? 0);
    if (lr > 0) {
        parts.push(`${lr} yêu cầu gia hạn mượn chờ duyệt`);
    }
    if (parts.length === 0) {
        return '';
    }
    return `Hiện có ${parts.join('; ')}. Vui lòng vào các mục quản lý tương ứng để xử lý.`;
}
