/**
 * Danh sách menu sidebar Admin.
 * Mục có children: không có href, khi ấn sẽ sổ ra submenu (Bạn đọc, Tài khoản).
 */
export const adminNavigation = [
    { name: 'Bảng điều khiển', href: 'admin.dashboard', icon: 'lucide:layout-grid', active: 'admin.dashboard' },
    {
        name: 'Danh mục tài liệu',
        icon: 'lucide:panels-top-left',
        active: ['admin.books.*'],
        children: [
            { name: 'Sách in', href: 'admin.books.index', active: 'admin.books.*' },
        ],
    },
    {
        name: 'Quản lý người dùng',
        icon: 'lucide:users',
        active: ['admin.users.*'],
        children: [
            { name: 'Tài khoản', href: 'admin.users.index', active: 'admin.users.*' },
        ],
    },
    {
        name: 'Quản lý kho sách',
        icon: 'lucide:warehouse',
        active: ['admin.warehouses.*'],
        href: 'admin.warehouses.index',
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
