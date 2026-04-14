/** Điều hướng site người đọc (công khai). Mục Quy định và Dịch vụ có menu con. */
export const readerNavItems = [
    { key: 'home', label: 'Trang chủ', route: 'reader.home' },
    { key: 'about', label: 'Giới thiệu', route: 'reader.about' },
    {
        key: 'regulations',
        label: 'Quy định',
        route: 'reader.regulations.index',
        children: [
            { key: 'reg_card', label: 'Thủ tục làm thẻ', route: 'reader.regulations.card' },
            { key: 'reg_schedule', label: 'Lịch phục vụ', route: 'reader.regulations.schedule' },
            { key: 'reg_borrow', label: 'Quy định mượn sách', route: 'reader.regulations.borrowing' },
        ],
    },
    { key: 'catalog', label: 'Tra cứu sách', route: 'reader.catalog' },
    {
        key: 'services',
        label: 'Dịch vụ',
        route: 'reader.services',
        children: [
            { key: 'services_card', label: 'Thẻ thư viện', route: 'reader.services.library-card' },
            { key: 'services_saved', label: 'Sách đã lưu', route: 'reader.saved-books' },
            { key: 'services_loan', label: 'Phiếu mượn và gia hạn', route: 'reader.services.loan-requests' },
        ],
    },
]
