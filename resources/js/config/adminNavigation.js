/**
 * Danh sách menu sidebar Admin.
 * Mục có children: không có href, khi ấn sẽ sổ ra submenu (Bạn đọc, Tài khoản).
 */
export const adminNavigation = [
    { name: 'Bảng điều khiển', href: 'admin.dashboard', icon: 'lucide:layout-grid', active: 'admin.dashboard' },
    {
        name: 'Quản lý người dùng',
        icon: 'lucide:users',
        active: ['admin.users.*', 'admin.readers.*'],
        children: [
            { name: 'Tài khoản', href: 'admin.users.index', active: 'admin.users.*' },
            { name: 'Bạn đọc / Thẻ thư viện', href: 'admin.readers.index', active: 'admin.readers.*' },
        ],
    },
    {
        name: 'Quản lý tài liệu',
        icon: 'lucide:book',
        active: ['admin.books.*'],
        children: [
            { name: 'Sách', href: 'admin.books.index', active: 'admin.books.*' },
        ],
    },
    {
        name: 'Cấu hình thư viện',
        icon: 'lucide:sliders',
        active: ['admin.loan-policies.*'],
        children: [
            { name: 'Quy định mượn trả', href: 'admin.loan-policies.index', active: 'admin.loan-policies.*' },
        ],
    },
];
