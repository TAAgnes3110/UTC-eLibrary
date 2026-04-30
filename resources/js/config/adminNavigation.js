export const adminNavigation = [
    { name: 'Bảng điều khiển', href: 'admin.dashboard', icon: 'lucide:layout-grid', active: 'admin.dashboard' },
    {
        name: 'Danh mục tài liệu',
        icon: 'lucide:panels-top-left',
        active: ['admin.books.*'],
        children: [
            { name: 'Sách in', href: 'admin.books.printed', active: ['admin.books.printed', 'admin.books.textbook', 'admin.books.reference'] },
            { name: 'Tài liệu số', href: 'admin.books.digital', active: 'admin.books.digital' },
        ],
    },
    {
        name: 'Quản lý người dùng',
        icon: 'lucide:users',
        active: ['admin.users.*'],
        children: [
            { name: 'Tài khoản', href: 'admin.users.index', active: 'admin.users.index' },
            { name: 'Duyệt yêu cầu cập nhật', href: 'admin.users.update-requests', active: 'admin.users.update-requests' },
        ],
    },
    {
        name: 'Quản lý kho sách',
        icon: 'lucide:warehouse',
        active: ['admin.warehouses.*'],
        children: [
            { name: 'Danh sách kho', href: 'admin.warehouses.index', active: 'admin.warehouses.index' },
            { name: 'Quản lý tủ sách', href: 'admin.warehouses.storage-cabinets', active: 'admin.warehouses.storage-cabinets' },
        ],
    },
    {
        name: 'Cấu hình thư viện',
        icon: 'lucide:scale',
        active: ['admin.library-settings.*'],
        children: [
            { name: 'Chính sách mượn', href: 'admin.library-settings.index', active: 'admin.library-settings.index' },
        ],
    },
    {
        name: 'Quản lý phân loại',
        icon: 'lucide:folder-tree',
        active: ['admin.classifications.*'],
        children: [
            { name: 'Phân loại sách', href: 'admin.classifications.index', active: 'admin.classifications.index' },
        ],
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
            { name: 'Duyệt yêu cầu gia hạn', href: 'admin.loans.renewal-requests', active: 'admin.loans.renewal-requests' },
            { name: 'Duyệt yêu cầu mượn', href: 'admin.loans.borrow-requests', active: 'admin.loans.borrow-requests' },
            { name: 'Tạo phiếu mới', href: 'admin.loans.create', active: 'admin.loans.create' },
        ],
    },
];
