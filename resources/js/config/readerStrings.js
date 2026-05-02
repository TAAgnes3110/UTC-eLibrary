/** Reader-facing UI copy (layout, footer, public pages). */
export const readerLayoutStrings = {
    openMenu: "Mở menu",
    mainNav: "Chính",
    login: "Đăng nhập",
    register: "Đăng ký",
    goToApp: "Vào hệ thống",
    goToReader: "Trang Thư viện số",
    logout: "Đăng xuất",
    digitalLibrary: "Thư viện số",
    universityShort: "Trường đại học Giao Thông Vận Tải",
    footerTitle: "Thư viện Trường đại học Giao thông Vận tải",
    footerBlurb: "Cổng thông tin hỗ trợ tra cứu tài liệu, dịch vụ thẻ và mượn trả theo quy định của thư viện.",
    quickLinks: "Liên kết nhanh",
    about: "Giới thiệu",
    regulationsShort: "Quy định",
    catalog: "Tra cứu sách",
    services: "Dịch vụ",
    catalogFooterHint: "Đăng nhập để lưu đầu mục vào danh sách nhớ khi đến quầy mượn.",
    footerEmailLabel: "Liên hệ",
    homeServiceReadOnSite: "Đọc tại chỗ",
    homeServiceReadOnSiteDesc: "Sử dụng tài liệu tại phòng đọc theo nội quy.",
    homeServiceBorrowHome: "Mượn về nhà",
    homeServiceBorrowHomeDesc: "Đối tượng, hạn mượn và số lượng theo loại thẻ và quy định của thư viện.",
    footerReferenceNote: "Tham khảo bố cục"
}

/** About page body copy. */
export const readerAboutPageStrings = {
    headTitle: "Giới thiệu",
    heroSubtitle: "Cổng thông tin và dịch vụ Thư viện số phục vụ độc giả Đại học Giao thông Vận tải.",
    backHome: "Về trang chủ",
    lead: "Hệ thống Thư viện số phục vụ độc giả Đại học Giao thông Vận tải: tra cứu nguồn tài liệu in và điện tử, hỗ trợ quản lý mượn trả và dịch vụ thẻ theo chuẩn nghiệp vụ thư viện.",
    body: "Giao diện này dành cho người đọc: bạn có thể xem thông tin công khai; đăng nhập để sử dụng đầy đủ chức năng phù hợp với tài khoản của mình.",
    exploreTitle: "Khám phá nhanh",
    seeMore: "Xem thêm",
    quickCatalog: "Tìm đầu mục tài liệu in và điện tử; đăng nhập để lưu đầu mục nhớ mượn tại quầy.",
    quickRegulations: "Chính sách mượn, thẻ đọc và nội quy phục vụ theo chuẩn nghiệp vụ thư viện.",
    quickServices: "Cấp thẻ, mượn trả, hồ sơ độc giả và các nhóm dịch vụ đang triển khai.",
    quickLogin: "Đăng nhập để dùng đầy đủ chức năng theo vai trò và loại thẻ của bạn."
}

/** Hub: /quy-dinh — liên kết tới 3 trang con. */
export const readerRegulationsHubStrings = {
    headTitle: "Quy định thư viện",
    heroTitle: "Quy định thư viện",
    lead: "Chọn nội dung: thủ tục cấp thẻ, lịch phục vụ hoặc quy định mượn sách theo loại thẻ.",
    tileCardTitle: "Thủ tục làm thẻ",
    tileCardDesc: "Loại thẻ, đăng ký qua tài khoản hoặc guest, và các bước xử lý trong hệ thống.",
    tileScheduleTitle: "Lịch phục vụ",
    tileScheduleDesc: "Khung giờ mở cửa và phục vụ độc giả (tham khảo).",
    tileBorrowTitle: "Quy định mượn sách",
    tileBorrowDesc: "Bảng chính sách mượn, gia hạn và phạt theo loại thẻ trong hệ thống.",
    tileCta: "Xem chi tiết"
}

/** Thủ tục làm thẻ — khớp nghiệp vụ cấp thẻ (LibraryCard: guest / tài khoản, workflow). */
export const readerCardProcedurePageStrings = {
    headTitle: "Thủ tục làm thẻ",
    kicker: "Quy định thư viện",
    heroTitle: "Thủ tục làm thẻ",
    lead: "Trình tự dưới đây phản ánh quy trình cấp thẻ đọc trên eLibrary (hồ sơ thẻ, trạng thái xử lý). Chi tiết giấy tờ tại quầy theo hướng dẫn của thư viện.",
    backRegulations: "Về mục Quy định",
    section1Title: "1. Loại thẻ và đối tượng (trong hệ thống)",
    section1Items: [
        "Thẻ sinh viên — bạn đọc là sinh viên (holder_type student).",
        "Thẻ giảng viên — cán bộ giảng dạy (holder_type teacher).",
        "Thẻ bạn đọc ngoài — đối tượng không thuộc hai nhóm trên, đăng ký theo kênh bạn đọc ngoài (holder_type external)."
    ],
    section2Title: "2. Hồ sơ và điều kiện khi đăng ký qua tài khoản",
    section2Items: [
        "Đăng ký tài khoản eLibrary (email, xác thực OTP) rồi đăng nhập để gửi đăng ký cấp thẻ (API xin cấp thẻ sau đăng nhập).",
        "Phải có ảnh đại diện (3×4) trên tài khoản — hệ thống từ chối gửi hồ sơ nếu chưa có ảnh.",
        "Mã định danh (mã sinh viên, mã cán bộ hoặc mã theo quy định) là bắt buộc; thông tin khoa, lớp, niên khóa/đơn vị… điền đúng theo biểu mẫu và loại tài khoản (sinh viên/giảng viên).",
        "Tài khoản quản trị, thủ thư (nội bộ) không dùng luồng xin cấp thẻ bạn đọc này."
    ],
    section3Title: "3. Các cách gửi / tạo hồ sơ thẻ",
    section3Items: [
        "Độc giả đã có tài khoản: đăng nhập và gửi đăng ký cấp thẻ; có thể chọn thanh toán/làm thủ tục tại quầy (paid_at_counter) — khi đó hồ sơ chuyển sang bước nhận thẻ tại quầy sau khi thu phí được ghi nhận.",
        "Đăng ký không đăng nhập (guest-register): nộp thông tin theo form API công khai; loại sinh viên/giảng viên có thể ở trạng thái chờ duyệt hoặc tại quầy tuỳ cách nhập (thanh toán tại quầy hay gửi online).",
        "Tại quầy thư viện: thủ thư có thể tạo hồ sơ thẻ gắn với tài khoản người dùng; với sinh viên/giảng viên, mặc định coi đã thu phí tại quầy nên hồ sơ thường ở bước nhận thẻ tại quầy (trừ khi cấu hình khác)."
    ],
    section4Title: "4. Quy trình xử lý trong hệ thống (workflow)",
    section4Items: [
        "Chờ duyệt — hồ sơ chờ thủ thư xem xét; có thể được duyệt (kích hoạt thẻ) hoặc từ chối kèm ghi chú.",
        "Chờ thanh toán — khi áp dụng, cần hoàn tất thanh toán/lệ phí theo hướng dẫn trước khi chuyển bước tiếp.",
        "Tại quầy — đã xử lý thu phí/thủ tục tại quầy; độc giả đến nhận thẻ vật lý theo hướng dẫn.",
        "Đang hiệu lực — thẻ dùng được để mượn/trả và các dịch vụ theo chính sách (sau khi duyệt hoặc theo luồng phù hợp).",
        "Đối với đăng ký bạn đọc ngoài (guest), hệ thống có thể ghi nhận thẻ ở trạng thái đang hiệu lực ngay sau khi tạo hồ sơ hợp lệ (theo luồng nghiệp vụ hiện tại)."
    ],
    ctaTitle: "Bắt đầu trên eLibrary",
    ctaBody: "Đăng ký tài khoản hoặc đăng nhập để gửi đăng ký cấp thẻ khi bạn đã chuẩn bị đủ hồ sơ.",
    note: "Trạng thái cụ thể trên tài khoản của bạn là căn cứ chính; thông báo và giờ làm việc tại quầy theo Trung tâm Thông tin – Thư viện."
}

/** Trang lịch phục vụ (khung giờ tham khảo). */
export const readerSchedulePageStrings = {
    headTitle: "Lịch phục vụ",
    kicker: "Quy định thư viện",
    heroTitle: "Lịch phục vụ",
    lead: "Khung giờ phục vụ độc giả (minh họa); giờ thực tế theo thông báo của thư viện.",
    backRegulations: "Về mục Quy định",
    colDay: "Ngày",
    colHours: "Giờ phục vụ",
    colNote: "Ghi chú",
    rows: [
        { day: "Thứ Hai – Thứ Sáu", hours: "07:30 – 17:30", note: "Phục vụ mượn, trả, đọc tại chỗ" },
        { day: "Thứ Bảy", hours: "08:00 – 11:30", note: "Theo lịch từng học kỳ" },
        { day: "Chủ nhật & ngày lễ", hours: "Nghỉ", note: "Trừ khi có thông báo mở cửa đặc biệt" }
    ],
    bullets: [
        "Giờ phục vụ có thể thay đổi trong tuần thi hoặc dịp nghỉ lễ; xem thông báo tại thư viện.",
        "Một số khu vực có lịch riêng; làm theo hướng dẫn của cán bộ trực."
    ],
    note: "Bảng trên là khung giờ tham khảo; giờ chính xác theo quyết định hiện hành của Trung tâm Thông tin – Thư viện."
}

/** Tra cứu sách (danh mục công khai). */
export const readerCatalogPageStrings = {
    headTitle: "Tra cứu sách",
    heroTitle: "Tra cứu sách",
    keywordPlaceholder: "Tìm theo tên sách, tác giả, mã, nhà xuất bản, năm, phân loại…",
    searchBtn: "Tìm kiếm",
    filterResourceType: "Loại sách",
    filterClassification: "Phân loại",
    filterDetail: "Phân loại (đầu mục)",
    filterStock: "Tình trạng",
    stockAll: "Tất cả",
    stockIn: "Còn sách",
    stockOut: "Hết sách",
    perPageLabel: "Số dòng / trang",
    emptyTitle: "Không tìm thấy đầu mục phù hợp",
    emptyHint: "Thử bỏ bớt bộ lọc hoặc từ khóa khác.",
    subjectLabel: "Phân loại",
    gradeLabel: "Phân loại hiển thị",
    seeDetail: "Xem chi tiết",
    paginationPrev: "Trước",
    paginationNext: "Sau",
    paginationPage: "Trang"
}

/** Chi tiết sách (tra cứu công khai). */
export const readerBookShowStrings = {
    headTitleSuffix: "Chi tiết sách",
    breadcrumbHome: "Trang chủ",
    breadcrumbCatalog: "Tra cứu sách",
    backCatalog: "Về tra cứu",
    authors: "Tác giả",
    publicationInfo: "Thông tin xuất bản",
    physicalDesc: "Mô tả vật lý",
    price: "Đơn giá",
    subject: "Chủ đề / phân loại",
    resourceType: "Loại sách",
    availabilityTitle: "Tình trạng sách hiện có tại thư viện",
    totalCopies: "Tổng số bản",
    availableCopies: "Sẵn sàng mượn",
    borrowedCopies: "Đang mượn",
    summaryTitle: "Mô tả chung",
    borrowAtDeskHint: "Mượn sách thực hiện tại quầy thư viện khi có thẻ đọc hợp lệ theo quy định.",
    digitalAssets: "Tài liệu số đính kèm",
    noCover: "Chưa có ảnh bìa"
}

/** Quy định mượn sách — bảng chính sách theo loại thẻ. */
export const readerRegulationsBorrowingPageStrings = {
    headTitle: "Quy định mượn sách",
    heroTitle: "Quy định mượn sách",
    emptyTitle: "Chưa có chính sách mượn trong hệ thống",
    emptyHint: "Khi quản trị bổ sung chính sách, bảng tự cập nhật tại trang này.",
    sectionStudentCard: "Thẻ sinh viên",
    sectionTeacherCard: "Thẻ giáo viên",
    sectionReaderCard: "Thẻ bạn đọc",
    sectionOtherCard: "Khác",
    colCardName: "Loại thẻ (tên thẻ)",
    colMaxBooks: "Số sách mượn tối đa",
    colLoanTerm: "Thời hạn mượn",
    colRenewCount: "Số lần gia hạn",
    colLateFine: "Phạt trễ hạn",
    colMaxTextbooks: "Số sách giáo trình tối đa",
    colMaxReference: "Số sách tham khảo tối đa",
    colBorrowHome: "Cho mượn về nhà",
    colReadOnsite: "Đọc tại chỗ"
}
