/** Nhãn hiển thị — khớp backend {@link App\Models\LibraryCard} */

/** Tìm theo cột (API `search_in`) — khớp cột tìm kiếm backend */
export const LIBRARY_CARD_SEARCH_IN_OPTIONS = [
    { key: 'card_number', label: 'Mã thẻ' },
    { key: 'code', label: 'Mã định danh' },
    { key: 'full_name', label: 'Họ tên' },
    { key: 'email', label: 'Email' },
    { key: 'phone', label: 'Số điện thoại' },
];

/** Nhãn loại thẻ (cột « Loại thẻ ») */
export const HOLDER_LABELS = {
    student: 'Thẻ sinh viên',
    teacher: 'Thẻ giảng viên',
    external: 'Thẻ bạn đọc ngoài',
};

export function holderLabel(key) {
    return HOLDER_LABELS[key] ?? key ?? '—';
}

/** @deprecated dùng holderLabel — cùng nội dung « loại thẻ » */
export const cardTypeLabel = holderLabel;

/** Badge trạng thái — cùng phong cách pill như bảng Kho (Hoạt động = emerald solid) */
export const LIBRARY_CARD_STATUS = {
    1: { label: 'Hoạt động', class: 'bg-emerald-500 dark:bg-emerald-600 text-white' },
    2: { label: 'Hết hạn', class: 'bg-slate-500 dark:bg-slate-600 text-white' },
    3: { label: 'Khóa', class: 'bg-rose-500 dark:bg-rose-600 text-white' },
    4: { label: 'Chờ', class: 'bg-amber-500 dark:bg-amber-600 text-white' },
};

export const WORKFLOW_LABELS = {
    draft: 'Nháp',
    pending_payment: 'Chờ thanh toán',
    pending_review: 'Chờ duyệt',
    /** Không dùng cụm « chờ nhận thẻ » — bản ghi cũ vẫn hiển thị ngắn gọn */
    pending_pickup: 'Tại quầy',
    active: 'Đang hiệu lực',
    rejected: 'Từ chối',
    cancelled: 'Đã hủy',
    expired: 'Hết hạn (quy trình)',
    revoked: 'Thu hồi',
};

export function workflowLabel(key) {
    return WORKFLOW_LABELS[key] ?? key ?? '—';
}

export function statusLabel(value) {
    const n = Number(value);
    return LIBRARY_CARD_STATUS[n]?.label ?? '—';
}

/** Nhóm nội bộ → 2 nhãn quy trình chính (admin) */
const WORKFLOW_BUCKET_AWAITING_REVIEW = ['draft', 'pending_review'];
const WORKFLOW_BUCKET_COMPLETED = ['pending_pickup', 'active', 'expired', 'revoked'];

/**
 * Cột « Quy trình » (admin): « Chờ duyệt » / « Hoàn thành » (legacy pending_payment vẫn gom « Chờ duyệt »).
 * @param {string|undefined|null} key
 */
export function workflowQuyTrinhAdminLabel(key) {
    if (key == null || key === '') {
        return '—';
    }
    if (key === 'pending_payment') {
        return 'Chờ duyệt';
    }
    if (WORKFLOW_BUCKET_AWAITING_REVIEW.includes(key)) {
        return 'Chờ duyệt';
    }
    if (WORKFLOW_BUCKET_COMPLETED.includes(key)) {
        return 'Hoàn thành';
    }
    return WORKFLOW_LABELS[key] ?? key ?? '—';
}

