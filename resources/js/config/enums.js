/**
 * Enum labels dùng chung cho Admin & Reader (khớp lang/vi/enums.php và backend).
 * Dùng cho dropdown, filter, badge.
 */

export const ROLE_LABELS = {
    SUPER_ADMIN: { label: 'Administrator', class: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' },
    ADMIN: { label: 'Quản trị đơn vị', class: 'bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200' },
    LIBRARIAN: { label: 'Thủ thư', class: 'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300' },
    MEMBER: { label: 'Bạn đọc', class: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' },
    GUEST: { label: 'Khách', class: 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300' },
};

export const BOOK_TYPES = [
    { value: '', label: 'Tất cả loại tài liệu' },
    { value: 'book', label: 'Sách' },
    { value: 'textbook', label: 'Giáo trình' },
    { value: 'thesis', label: 'Bài luận / Khóa luận / Đồ án' },
    { value: 'dissertation', label: 'Luận văn / Luận án' },
    { value: 'research', label: 'Báo cáo khoa học' },
    { value: 'magazine', label: 'Tạp chí' },
    { value: 'newspaper', label: 'Báo' },
    { value: 'other', label: 'Tài liệu khác' },
];

/** Options chỉ value + label (bỏ dòng "Tất cả") cho form. */
export const BOOK_TYPE_OPTIONS = BOOK_TYPES.filter((t) => t.value !== '');

/** Theo nhóm: chỉ các type thuộc nhóm đó (để form thêm sách theo từng kiểu). */
export const BOOK_TYPES_BY_GROUP = {
    printed: [
        { value: 'book', label: 'Sách' },
        { value: 'textbook', label: 'Giáo trình' },
        { value: 'other', label: 'Tài liệu khác' },
    ],
    digital: [
        { value: 'book', label: 'Sách' },
        { value: 'textbook', label: 'Giáo trình' },
        { value: 'research', label: 'Báo cáo khoa học' },
        { value: 'other', label: 'Tài liệu khác' },
    ],
    newspaper_magazine: [
        { value: 'newspaper', label: 'Báo' },
        { value: 'magazine', label: 'Tạp chí' },
    ],
    thesis: [
        { value: 'thesis', label: 'Bài luận / Khóa luận / Đồ án' },
        { value: 'dissertation', label: 'Luận văn / Luận án' },
        { value: 'research', label: 'Báo cáo khoa học' },
    ],
};

export const BOOK_STATUS_OPTIONS = [
    { value: '', label: 'Tất cả trạng thái' },
    { value: 'available', label: 'Sẵn có' },
    { value: 'unavailable', label: 'Ẩn' },
    { value: 'processing', label: 'Đang xử lý' },
];

export const STATUS_LABELS = {
    active: { label: 'Hoạt động', class: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' },
    inactive: { label: 'Tạm khóa', class: 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300' },
};

export function getRoleInfo(role) {
    return ROLE_LABELS[role] || { label: role || '—', class: 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300' };
}

export function getStatusInfo(status) {
    return STATUS_LABELS[status] || STATUS_LABELS.active;
}

export function getBookTypeLabel(value) {
    const t = BOOK_TYPES.find((x) => x.value === value);
    return t ? t.label : value || '—';
}

/** Nhóm tài nguyên (Danh mục tài liệu) – khớp BookType::getTypesByGroup. */
export const RESOURCE_GROUPS = {
    printed: 'Sách in',
    digital: 'Tài liệu số',
    newspaper_magazine: 'Báo – Tạp chí',
    thesis: 'Luận văn – Luận án – Đề tài NCKH',
};

export function getResourceGroupLabel(group) {
    return RESOURCE_GROUPS[group] || 'Tất cả tài liệu';
}

/** Xác định nhóm từ type (và is_digital: true => digital). */
export function getResourceGroupByType(type, isDigital = false) {
    if (isDigital) return 'digital';
    const printed = ['book', 'textbook', 'other'];
    const newspaper_magazine = ['newspaper', 'magazine'];
    const thesis = ['thesis', 'dissertation', 'research'];
    if (printed.includes(type)) return 'printed';
    if (newspaper_magazine.includes(type)) return 'newspaper_magazine';
    if (thesis.includes(type)) return 'thesis';
    return 'printed';
}
