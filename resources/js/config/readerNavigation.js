/**
 * Menu sidebar khu vực bạn đọc (Thư viện số).
 * Một số mục chỉ hiển thị khi đã đăng nhập (auth).
 */
export const readerNavigation = [
    { name: 'Tổng quan', href: 'library.dashboard', icon: 'lucide:layout-dashboard', active: 'library.dashboard', auth: true },
    { name: 'Tra cứu sách', href: 'library.search', icon: 'lucide:search', active: 'library.search' },
    { name: 'Sách đã lưu', href: 'library.saved', icon: 'lucide:bookmark', active: 'library.saved' },
    { name: 'Xem thẻ / Quản lý thẻ', href: 'library.card', icon: 'lucide:credit-card', active: 'library.card', auth: true },
    { name: 'Sách mượn', href: 'library.loans', icon: 'lucide:clipboard-list', active: 'library.loans', auth: true },
    { name: 'Giới thiệu', href: 'library.intro', icon: 'lucide:info', active: 'library.intro' },
    { name: 'Nội quy', href: 'library.rules', icon: 'lucide:file-text', active: 'library.rules' },
];
