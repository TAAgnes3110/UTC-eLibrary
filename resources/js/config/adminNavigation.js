/**
 * Danh sách menu sidebar Admin.
 * Mục có children: không có href, khi ấn sẽ sổ ra submenu (Bạn đọc, Tài khoản).
 */
export const adminNavigation = [
    { name: 'Bảng điều khiển', href: 'admin.dashboard', icon: 'lucide:layout-grid', active: 'admin.dashboard' },
    {
        name: 'Quản lý người dùng',
        icon: 'lucide:users',
        active: ['admin.readers.*', 'admin.users.*'],
        children: [
            { name: 'Bạn đọc', href: 'admin.readers.index', active: 'admin.readers.*' },
            { name: 'Tài khoản', href: 'admin.users.index', active: 'admin.users.*' },
        ],
    },
    {
        name: 'Quản lý sách',
        icon: 'lucide:library',
        active: 'admin.books.*',
        children: [
            { name: 'Sách in', href: 'admin.books.index', active: 'admin.books.*', query: { group: 'printed' } },
            { name: 'Tài liệu số', href: 'admin.books.index', active: 'admin.books.*', query: { group: 'digital' } },
            { name: 'Báo – Tạp chí', href: 'admin.books.index', active: 'admin.books.*', query: { group: 'newspaper_magazine' } },
            { name: 'Luận văn – Luận án – Đề tài NCKH', href: 'admin.books.index', active: 'admin.books.*', query: { group: 'thesis' } },
        ],
    },
    {
        name: 'Quản lý phiếu',
        icon: 'lucide:file-text',
        active: 'admin.library.slips',
        children: [
            { name: 'Phiếu nhập', href: 'admin.library.slips', active: 'admin.library.slips', query: { tab: 'import' } },
            { name: 'Phiếu xuất', href: 'admin.library.slips', active: 'admin.library.slips', query: { tab: 'export' } },
        ],
    },
    { name: 'Quản lý Kiểm kê kho', href: 'admin.library.inventory', icon: 'lucide:clipboard-check', active: 'admin.library.inventory.*' },
    { name: 'Quản lý Thanh lý', href: 'admin.library.liquidation', icon: 'lucide:archive', active: 'admin.library.liquidation' },
    { name: 'Quản lý Mượn & Trả sách', href: 'admin.loans.index', icon: 'lucide:clipboard-list', active: 'admin.loans.index' },
    { name: 'Quản lý Trả muộn & Phạt', href: 'admin.loans.penalties', icon: 'lucide:alert-circle', active: 'admin.loans.penalties' },
    { name: 'Quản lý Thẻ thư viện', href: 'admin.cards.index', icon: 'lucide:contact', active: 'admin.cards.*' },
    { name: 'Duyệt yêu cầu chỉnh sửa', href: 'admin.profile-change-requests.index', icon: 'lucide:user-check', active: 'admin.profile-change-requests.*' },
    { name: 'Quy định mượn trả', href: 'admin.settings.rules', icon: 'lucide:clipboard-list', active: 'admin.settings.rules' },
    { name: 'Nội quy & Hướng dẫn', href: 'admin.settings.content', icon: 'lucide:file-text', active: 'admin.settings.content' },
    { name: 'Cài đặt giao diện', href: 'admin.settings.appearance', icon: 'lucide:settings', active: 'admin.settings.appearance' },
    { name: 'Quản lý Báo cáo', href: 'admin.stats.index', icon: 'lucide:trending-up', active: 'admin.stats.*' },
];
