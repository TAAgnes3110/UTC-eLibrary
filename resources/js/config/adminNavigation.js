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
        icon: 'lucide:scale',
        active: ['admin.library-settings.*'],
        href: 'admin.library-settings.index',
    },
    {
        name: 'Thẻ thư viện',
        icon: 'lucide:id-card',
        active: ['admin.library-cards.*'],
        children: [
            { name: 'Quản lý thẻ', href: 'admin.library-cards.index', active: 'admin.library-cards.index' },
            { name: 'Duyệt yêu cầu', href: 'admin.library-cards.requests', active: 'admin.library-cards.requests' },
            { name: 'Cấp thẻ tại quầy', href: 'admin.library-cards.counter', active: 'admin.library-cards.counter' },
        ],
    },
    {
        name: 'Phiếu mượn',
        icon: 'lucide:book-open-check',
        active: ['admin.loans.*'],
        children: [
            { name: 'Quản lý phiếu', href: 'admin.loans.index', active: 'admin.loans.index' },
            { name: 'Tạo phiếu mới', href: 'admin.loans.create', active: 'admin.loans.create' },
        ],
    },
];
