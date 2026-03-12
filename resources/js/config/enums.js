/**
 * Enum dùng chung cho Admin (hiện tại chỉ phục vụ màn Quản lý người dùng).
 */

// Nhãn & style cho các loại vai trò người dùng
export const ROLE_LABELS = {
    SUPER_ADMIN: { label: 'Administrator', class: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' },
    ADMIN: { label: 'Quản trị đơn vị', class: 'bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200' },
    LIBRARIAN: { label: 'Thủ thư', class: 'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300' },
    MEMBER: { label: 'Bạn đọc', class: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' },
    GUEST: { label: 'Khách', class: 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300' },
};

// Nhãn & style cho trạng thái active / inactive của tài khoản
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

