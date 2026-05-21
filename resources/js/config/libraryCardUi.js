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

/** Trạng thái thẻ (cột status) — khớp {@link App\Enums\LibraryCardStatus} */
export const LIBRARY_CARD_STATUS = {
    1: { label: 'Hoạt động', class: 'bg-emerald-500 dark:bg-emerald-600 text-white' },
    2: { label: 'Hết hạn', class: 'bg-slate-500 dark:bg-slate-600 text-white' },
    3: { label: 'Khóa', class: 'bg-rose-500 dark:bg-rose-600 text-white' },
    4: { label: 'Chờ xử lý', class: 'bg-amber-500 dark:bg-amber-600 text-white' },
};

/** Lọc trạng thái thẻ (admin — Quản lý thẻ) */
export const LIBRARY_CARD_STATUS_FILTER_OPTIONS = [
    { value: '', label: 'Trạng thái thẻ' },
    { value: '1', label: 'Hoạt động' },
    { value: '2', label: 'Hết hạn' },
    { value: '3', label: 'Khóa' },
    { value: '4', label: 'Chờ xử lý' },
];

/** Quy trình cấp thẻ — chỉ các bước nghiệp vụ */
export const WORKFLOW_LABELS = {
    pending_review: 'Chờ duyệt',
    pending_payment: 'Chờ thanh toán',
    pending_pickup: 'Chờ lấy thẻ',
    active: 'Đang hiệu lực',
    rejected: 'Đã từ chối',
    cancelled: 'Đã hủy',
};

/** Nhãn đọc DB legacy (không dùng cho thao tác mới) */
const WORKFLOW_LEGACY_LABELS = {
    draft: 'Chờ duyệt',
    expired: 'Đang hiệu lực',
    revoked: 'Đang hiệu lực',
};

export function workflowLabel(key) {
    if (key == null || key === '') {
        return '—';
    }
    return WORKFLOW_LABELS[key] ?? WORKFLOW_LEGACY_LABELS[key] ?? key ?? '—';
}

export function statusLabel(value) {
    const n = Number(value);
    return LIBRARY_CARD_STATUS[n]?.label ?? '—';
}

/** Gợi ý bước tiếp theo (admin / độc giả). */
export const WORKFLOW_HINTS = {
    pending_review: 'Hồ sơ đang chờ thủ thư duyệt tại mục « Duyệt yêu cầu ».',
    pending_payment: 'Đã duyệt — bạn đọc cần thanh toán lệ phí trước khi nhận thẻ.',
    pending_pickup: 'Đã thu phí / đã duyệt — chờ bạn đọc đến quầy nhận thẻ; thủ thư bấm « Xác nhận đã giao thẻ ».',
    active: 'Đã giao thẻ — có ngày hiệu lực, dùng cho mượn/trả theo chính sách.',
    rejected: 'Hồ sơ bị từ chối (đã đưa vào thùng rác).',
    cancelled: 'Bạn đọc đã hủy yêu cầu.',
    draft: 'Dữ liệu cũ — hệ thống coi như « Chờ duyệt ».',
    expired: 'Dữ liệu cũ — hết hạn ghi trên trạng thái thẻ, không phải quy trình.',
    revoked: 'Dữ liệu cũ — thẻ bị khóa, không phải bước quy trình.',
};

export function workflowHint(key) {
    return WORKFLOW_HINTS[key] ?? '';
}

/** Quy trình đang xử lý (chưa kết thúc / chưa hủy) */
export const WORKFLOW_IN_PROGRESS = [
    'pending_review',
    'pending_payment',
    'pending_pickup',
];

/** Thẻ đủ điều kiện hiển thị tại « Quản lý thẻ » (đã qua duyệt hoặc đang lưu hành) */
export const WORKFLOW_MANAGEMENT_LIST = ['pending_payment', 'pending_pickup', 'active'];
