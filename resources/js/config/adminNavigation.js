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
            { name: 'Sách in', href: 'admin.books.index', active: 'admin.books.index' },
            { name: 'Tài liệu số', href: 'admin.books.digital', active: 'admin.books.digital' },
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
    {
        name: 'Quản lý thẻ thư viện',
        icon: 'lucide:badge-check',
        active: ['admin.library-cards.manage', 'admin.library-cards.approve', 'admin.library-cards.quick'],
        children: [
            { name: 'Quản lý thẻ thư viện', href: 'admin.library-cards.manage', active: 'admin.library-cards.manage' },
            { name: 'Duyệt yêu cầu cấp thẻ', href: 'admin.library-cards.approve', active: 'admin.library-cards.approve' },
            { name: 'Cấp thẻ thư viện nhanh', href: 'admin.library-cards.quick', active: 'admin.library-cards.quick' },
        ],
    },
];
