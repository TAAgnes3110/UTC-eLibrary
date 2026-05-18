# ĐỒ ÁN TỐT NGHIỆP

**TRƯỜNG ĐẠI HỌC GIAO THÔNG VẬN TẢI**  
**KHOA CÔNG NGHỆ THÔNG TIN**

## ĐỒ ÁN TỐT NGHIỆP

**ĐỀ TÀI:** XÂY DỰNG WEBSITE QUẢN LÝ THƯ VIỆN (UTC eLibrary)

| | |
|---|---|
| **Giảng viên hướng dẫn** | Nguyễn Hữu Luân |
| **Sinh viên thực hiện** | Vũ Tuấn Kiệt |
| **Mã sinh viên** | 223630694 |
| **Lớp** | Khoa học máy tính |
| **Khóa** | 63 |

**Hà Nội – 2026**

---

> **Hướng dẫn sử dụng bản Markdown này**  
> Nội dung được soạn theo khung đồ án Word mẫu và mã nguồn `UTC-eLibrary`. Khi dán vào Word: font Times New Roman cỡ 13, giãn dòng 1,5, lề theo quy định Khoa — ước lượng **khoảng 55–65 trang** (chưa tính hình). Chèn biểu đồ use case, ERD và ảnh màn hình vào các vị trí *\[Hình …\]*.

---

## LỜI CẢM ƠN

Trong suốt thời gian thực hiện đồ án tốt nghiệp, em đã nhận được sự quan tâm, chỉ bảo và hỗ trợ nhiệt tình từ nhiều phía. Trước hết, em xin gửi lời cảm ơn chân thành và sâu sắc tới thầy **Nguyễn Hữu Luân** — Giảng viên Khoa Công nghệ Thông tin, Trường Đại học Giao thông Vận tải. Thầy đã tận tình hướng dẫn em từ giai đoạn chọn hướng đề tài, xác định phạm vi nghiệp vụ thư viện, tới việc hoàn thiện sản phẩm phần mềm và báo cáo. Những góp ý của thầy về kiến trúc hệ thống, quy trình mượn–trả và cách trình bày đồ án đã giúp em định hướng rõ ràng hơn, tránh sa đà vào chi tiết kỹ thuật mà thiếu căn cứ thực tiễn.

Em xin bày tỏ lòng biết ơn tới các thầy cô trong Khoa đã truyền đạt kiến thức nền tảng về lập trình, cơ sở dữ liệu, phân tích thiết kế hệ thống và công nghệ web trong suốt khóa học. Những kiến thức ấy là cơ sở để em tiếp cận và vận dụng framework Laravel, Vue.js cùng các công cụ hiện đại khi xây dựng UTC eLibrary.

Em cũng không thể không nhắc tới gia đình và bạn bè — những người đã động viên, tạo điều kiện về thời gian và tinh thần để em hoàn thành đồ án. Sự đồng hành ấy có ý nghĩa rất lớn trong giai đoạn vừa học vừa triển khai dự án phần mềm có quy mô tương đối lớn.

Cuối cùng, do kinh nghiệm thực tế và thời gian có hạn, báo cáo và sản phẩm chắc chắn còn điểm chưa hoàn thiện. Em rất mong nhận được thêm ý kiến góp ý từ thầy cô và hội đồng để em có cơ hội hoàn thiện bản thân và hệ thống trong thời gian tới.

Em xin chân thành cảm ơn!

---

## TÓM TẮT NỘI DUNG ĐỒ ÁN

Chúng ta đang sống trong thời đại bùng nổ thông tin và chuyển đổi số. Trong lĩnh vực giáo dục đại học, thư viện không còn là nơi lưu trữ sách đơn thuần mà đã trở thành trung tâm tri thức phục vụ học tập, giảng dạy và nghiên cứu. Sinh viên, giảng viên và cán bộ ngày càng quen với việc tra cứu tài liệu, theo dõi hoạt động mượn trả và sử dụng nguồn học liệu số thông qua Internet. Tuy nhiên, tại nhiều đơn vị, công tác quản lý biên mục, thẻ thư viện, phiếu mượn và tài liệu điện tử vẫn còn dựa nhiều vào thủ công hoặc các công cụ rời rạc, gây khó khăn cho cán bộ thư viện lẫn bạn đọc.

Tại **Trường Đại học Giao thông Vận tải (UTC)**, đối tượng phục vụ đa dạng: sinh viên các ngành, giảng viên, cán bộ và độc giả bên ngoài. Mỗi nhóm chịu sự điều chỉnh của **quy định mượn khác nhau** — ví dụ sinh viên có thể được mượn sách về nhà trong giới hạn số lượng và thời gian, trong khi độc giả ngoài thường chỉ được phép đọc tại chỗ. Tài liệu mang tính đặc thù như luận văn, đồ án, đề tài nghiên cứu cần được kiểm soát truy cập chặt chẽ. Việc xây dựng một **hệ thống thư viện điện tử thống nhất** là giải pháp cần thiết để nâng cao chất lượng phục vụ và minh bạch hóa quy trình.

Đồ án với đề tài **“Thiết kế và xây dựng hệ thống quản lý thư viện UTC eLibrary”** hướng tới xây dựng website phục vụ hai nhóm người dùng chính. Nhóm **bạn đọc** có thể tra cứu sách, đọc quy định, đăng ký tài khoản, làm thẻ thư viện, gửi yêu cầu mượn sách giấy, theo dõi phiếu mượn, mua và tải tài liệu điện tử (qua cổng thanh toán SePay), nộp đồ án/luận văn số và nhận thông báo từ thư viện. Nhóm **cán bộ thư viện / quản trị** sử dụng khu vực quản trị để duyệt hồ sơ thẻ, xử lý mượn–trả, quản lý kho sách, cấu hình chính sách mượn, duyệt tài liệu nộp, đăng tin tức và xem thống kê tổng quan.

Về mặt kỹ thuật, hệ thống được xây dựng trên nền **Laravel 12** (PHP 8.2) cho xử lý nghiệp vụ và API REST `/api/v1`, kết hợp **Vue 3** và **Inertia.js** cho giao diện, **Tailwind CSS** cho thiết kế responsive, **MySQL** lưu trữ dữ liệu, **JWT** và **Spatie Permission** cho xác thực và phân quyền. Các luồng nhạy cảm như tạo phiếu mượn, duyệt thẻ và xác nhận thanh toán được bọc trong transaction và service layer tách biệt, bám sát quy định nghiệp vụ UTC.

**Kết quả đạt được:** Sản phẩm demo chạy được trên môi trường phát triển với đầy đủ luồng chính; bộ kiểm thử tự động (Feature/Unit) cho các module bảo mật và nghiệp vụ trọng yếu. **Hạn chế:** Chưa triển khai ứng dụng di động riêng, chưa tích hợp RFID; một số biểu đồ trong báo cáo cần vẽ bổ sung theo hệ thống thực tế.

**Từ khóa:** Thư viện điện tử; quản lý mượn trả; Laravel; Vue.js; Inertia.js; SePay; UTC.

---

## MỤC LỤC

*(Cập nhật số trang trong Word sau khi định dạng — nhấn F9)*

- Lời cảm ơn  
- Tóm tắt  
- Mục lục  
- Danh mục hình ảnh  
- Danh mục bảng biểu  
- Danh mục thuật ngữ và từ viết tắt  

**CHƯƠNG 1. GIỚI THIỆU ĐỀ TÀI**  
1.1. Đặt vấn đề  
1.2. Mục tiêu và phạm vi đề tài  
1.3. Định hướng giải pháp  
1.4. Phương pháp thực hiện  
1.5. Bố cục đồ án  

**CHƯƠNG 2. KHẢO SÁT VÀ PHÂN TÍCH YÊU CẦU**  
2.1. Khảo sát hiện trạng  
2.2. Tổng quan chức năng  
2.3. Biểu đồ use case  
2.4. Quy trình nghiệp vụ  
2.5. Đặc tả chức năng  
2.6. Yêu cầu phi chức năng  

**CHƯƠNG 3. CÔNG NGHỆ SỬ DỤNG**  
3.1. Kiến trúc tổng thể  
3.2. PHP và Laravel  
3.3. Vue.js và Inertia.js  
3.4. Tailwind CSS  
3.5. MySQL  
3.6. JWT, Spatie Permission và bảo mật API  
3.7. SePay và xử lý đơn hàng số  
3.8. Lập lịch tác vụ và hàng đợi  

**CHƯƠNG 4. THIẾT KẾ, XÂY DỰNG, KIỂM THỬ VÀ TRIỂN KHAI**  
4.1. Thiết kế kiến trúc phần mềm  
4.2. Thiết kế giao diện  
4.3. Thiết kế cơ sở dữ liệu  
4.4. Xây dựng ứng dụng  
4.5. Kiểm thử  
4.6. Triển khai  

**CHƯƠNG 5. CÁC GIẢI PHÁP VÀ ĐÓNG GÓP NỔI BẬT**  

**CHƯƠNG 6. KẾT LUẬN VÀ HƯỚNG PHÁT TRIỂN**  

- Tài liệu tham khảo  
- Phụ lục  

---

# CHƯƠNG 1. GIỚI THIỆU ĐỀ TÀI

## 1.1. Đặt vấn đề

Đề tài đồ án tốt nghiệp của em là **“Thiết kế và xây dựng hệ thống quản lý thư viện (UTC-eLibrary)”**. Trong bối cảnh học tập và nghiên cứu hiện đại, sinh viên và giảng viên không chỉ đến thư viện để đọc tại chỗ mà còn có nhu cầu **tra cứu sách, xem quy định, làm thẻ, đăng ký mượn sách và sử dụng tài liệu điện tử** ngay trên máy tính hoặc điện thoại. Nếu thư viện chỉ vận hành theo cách truyền thống — ghi sổ, tra cứu thủ công, làm thủ tục nhiều lần tại quầy — cả bạn đọc lẫn cán bộ đều tốn thời gian, dễ phát sinh sai sót khi khối lượng giao dịch tăng cao.

**Hệ thống UTC eLibrary** là trang web em phát triển nhằm mang đến trải nghiệm tra cứu và sử dụng dịch vụ thư viện **thuận tiện, rõ ràng và đáng tin cậy**. Giao diện hướng tới người dùng phổ thông: tìm sách, đọc quy định, đăng ký thẻ, theo dõi phiếu mượn và tài liệu số mà không cần am hiểu kỹ thuật. Phía thư viện, hệ thống hỗ trợ quản lý sách, thẻ, phiếu mượn, tin tức và các công việc hằng ngày trên cùng một nền tảng.

### 1.1.1. Bối cảnh và nhu cầu thực tế

Trong thực tế tại các trường đại học, lượng sách và tài liệu ngày càng lớn, trong khi thói quen tra cứu trên mạng cũng tăng mạnh. Tại UTC, thư viện cần phục vụ đông đối tượng với **quy định mượn khác nhau**: sinh viên được mượn sách về nhà trong giới hạn nhất định; giảng viên và cán bộ có ngưỡng riêng; độc giả ngoài trường thường **chỉ được đọc tại chỗ**, không được mang tài liệu ra khỏi thư viện. Nếu không có phần mềm hỗ trợ, việc kiểm tra “ai được mượn gì, mượn bao nhiêu, đã quá hạn chưa” rất dễ nhầm lẫn.

Một khó khăn thường gặp là **tra cứu không thuận tiện**: bạn đọc khó biết nhanh thư viện còn cuốn sách hay không, còn mấy bản, nằm ở kho nào. Thông tin và thủ tục hay **phân tán** — quy định mượn, cách làm thẻ, lịch mở cửa có thể nằm ở bảng tin, mạng xã hội hoặc giấy tờ riêng. Việc làm thẻ và quản lý hồ sơ thủ công khiến cả hai phía khó theo dõi tiến độ từ lúc nộp đến lúc được duyệt.

Về **mượn–trả**, ghi phiếu giấy và theo dõi quá hạn với số lượng lớn dễ sót. Bạn đọc quên hạn trả dẫn đến phạt hoặc bị hạn chế mượn tiếp. Với **tài liệu điện tử**, đặc biệt luận văn và đồ án, cần quản lý cẩn thận — không thể để ai cũng tải về tùy tiện; một số miễn phí, một số thu phí, cần xem thử, thanh toán và cấp quyền tải rõ ràng.

Từ những vấn đề trên, em đặt ra hướng giải quyết bằng **website UTC-eLibrary**: bạn đọc và thư viện làm việc trên cùng hệ thống với tra cứu minh bạch, quy trình thẻ và mượn có kiểm soát, tài liệu số có paywall, cán bộ quản lý tập trung và có số liệu phục vụ báo cáo.

### 1.1.2. Các dịch vụ chính trên website (mô tả nghiệp vụ)

**Trang chủ** (`/`) giới thiệu thư viện số UTC, đường dẫn nhanh tới tra cứu, đăng nhập, đăng ký, hiển thị tin tức và sách mới.

**Quy định** (`/quy-dinh/*`) gồm thủ tục làm thẻ, lịch phục vụ, quy định mượn sách — giúp bạn đọc biết được mượn về nhà hay chỉ đọc tại chỗ, số cuốn tối đa, thời hạn trả.

**Tra cứu sách** (`/tra-cuu-sach`): tìm theo tên, tác giả, loại sách; xem chi tiết; với tài liệu số có thể xem thử PDF và biết điều kiện tải/mua.

**Mượn sách in:** giỏ mượn → gửi yêu cầu → thủ thư duyệt → phiếu mượn; theo dõi trạng thái tại `/dich-vu/phieu-muon`.

**Tài liệu số trả phí:** giỏ mua → thanh toán SePay (`/dich-vu/thanh-toan`) → tải PDF khi có quyền.

**Tài khoản:** đăng ký có xác thực OTP email; đăng nhập thường hoặc Microsoft Azure; quản lý hồ sơ, đổi mật khẩu.

**Nộp đồ án/luận văn:** upload PDF, chờ duyệt, sau đó đưa vào kho tra cứu chính thức.

**Khu quản trị** (`/admin`): dashboard, quản lý sách, thẻ, phiếu mượn, duyệt yêu cầu, cài đặt policy, tin tức, nhập/xuất Excel.

**Tự động hóa:** nhắc sắp đến hạn trả, đồng bộ quá hạn, hết hạn đơn chưa thanh toán — chạy theo lịch (`routes/console.php`).

## 1.2. Mục tiêu và phạm vi đề tài

### 1.2.1. Mục tiêu chung

Xây dựng **website quản lý thư viện số UTC eLibrary** đủ chức năng cốt lõi, có thể chạy thử trên máy cá nhân hoặc máy chủ, góp phần số hóa dịch vụ thư viện tại UTC.

### 1.2.2. Mục tiêu cụ thể

**Phần bạn đọc (công khai + sau đăng nhập):**

- Trang chủ, giới thiệu, quy định (làm thẻ, lịch, mượn sách).
- Tra cứu sách, xem chi tiết, xem thử PDF.
- Đăng ký, đăng nhập, quên mật khẩu, OTP email.
- Đăng ký và theo dõi thẻ thư viện; khách không tài khoản có luồng `guest-register`.
- Giỏ mượn, gửi yêu cầu mượn, xem phiếu mượn, yêu cầu gia hạn.
- Giỏ mua tài liệu số, thanh toán SePay, xem đơn đã mua, tải PDF khi được cấp quyền.
- Nộp đồ án/luận văn số; đọc tin tức; nhận thông báo.

**Phần quản trị:**

- Dashboard thống kê (`StatisticsService`).
- CRUD sách in/điện tử, bản sao, kho, tủ, phân loại, tác giả, NXB.
- Duyệt thẻ, yêu cầu mượn, gia hạn; lập phiếu tại quầy; xử lý trả sách.
- Duyệt nộp tài liệu số; quản lý người dùng; duyệt sửa hồ sơ.
- Cấu hình `loan_policies`, `library_settings`, giá paywall.
- Đăng tin tức; nhập/xuất Excel.

**Mục tiêu kỹ thuật:**

- API REST `/api/v1` với JWT và middleware `init`.
- RBAC Spatie; rate limiting; FormRequest validate.
- Service layer + `DB::transaction()` cho mượn–trả, thẻ, đơn hàng.
- Kiểm thử Feature/Unit cho module trọng yếu.

### 1.2.3. Phạm vi đồ án

**Trong phạm vi:** Toàn bộ website độc giả và admin; CSDL MySQL; thanh toán SePay; cron/queue; lưu file local hoặc Cloudflare R2 (tùy cấu hình).

**Ngoài phạm vi (hướng phát triển sau):** Ứng dụng mobile native; quét RFID/thẻ từ; liên kết sâu hệ thống quản lý sinh viên; gợi ý sách bằng AI.

### 1.2.4. Kết quả mong đợi

Sản phẩm demo chạy được các luồng: vào web → tra cứu → đăng ký → làm thẻ → được duyệt → mượn → theo dõi phiếu → trả sách; luồng tài liệu số: xem thử → mua → thanh toán → tải PDF. Báo cáo mô tả phân tích, thiết kế, công nghệ, kiểm thử và hướng dẫn cài đặt cơ bản.

## 1.3. Định hướng giải pháp

### 1.3.1. Giải pháp công nghệ

- **Back-end:** Laravel 12, PHP 8.2; controller mỏng; logic trong `app/Services/`.
- **Front-end:** Vue 3 + Inertia.js + Vite 7 + Tailwind CSS 3.
- **CSDL:** MySQL; Eloquent ORM; migration có `comment()` trạng thái.
- **Auth:** Session (web) + JWT (API); OTP; Socialite Microsoft Azure.
- **Thanh toán:** SePay — `DigitalPaymentOrderService`, webhook, QR.
- **File:** PDF private; preview PNG; R2 tùy `DeployProfile`.

### 1.3.2. Giải pháp chức năng

- Tìm kiếm và lọc sách rõ ràng; trang chi tiết đầy đủ trước khi mượn/mua.
- Giỏ mượn và giỏ mua tách biệt trên `/dich-vu/gio-sach` (tab).
- Mọi bước nhạy cảm (cấp thẻ, duyệt mượn, duyệt nộp bài) đều qua thủ thư.
- Thông báo in-app và email cho sự kiện quan trọng.

### 1.3.3. Nguyên tắc nghiệp vụ UTC

- Mượn về nhà chỉ khi thẻ hợp lệ + policy `allow_home` + không vi phạm (quá hạn, nợ phạt, vượt `max_books`).
- Độc giả ngoài: `holder_type = external` — onsite, không home borrow.
- Luận văn/đồ án: `resource_kind`, `access_mode`, paywall, `thesis_metadata`.

## 1.4. Phương pháp thực hiện

1. **Khảo sát** yêu cầu thư viện và các hệ thống tương tự (OPAC, phần mềm quản lý thư viện).
2. **Phân tích & thiết kế:** use case, quy trình, ERD, kiến trúc 3 lớp.
3. **Cài đặt** theo module: auth → biên mục → thẻ → mượn → số hóa → thanh toán → admin.
4. **Kiểm thử** tay và tự động (PHPUnit).
5. **Triển khai thử** local/VPS; tài liệu hóa cấu hình.

## 1.5. Bố cục đồ án

- **Chương 2** — Khảo sát, chức năng, use case, đặc tả, yêu cầu phi chức năng.  
- **Chương 3** — Công nghệ sử dụng.  
- **Chương 4** — Thiết kế, xây dựng, kiểm thử, triển khai.  
- **Chương 5** — Giải pháp và đóng góp nổi bật.  
- **Chương 6** — Kết luận và hướng phát triển.

---

# CHƯƠNG 2. KHẢO SÁT VÀ PHÂN TÍCH YÊU CẦU

## 2.1. Khảo sát hiện trạng

### 2.1.1. Khảo sát nhu cầu bạn đọc

Cuộc sống và học tập ngày càng gắn với Internet. Sinh viên, giảng viên và cán bộ mong muốn **tra cứu sách, xem quy định mượn, làm thẻ và theo dõi phiếu mượn** trên máy tính hoặc điện thoại thay vì phải đến quầy nhiều lần. Bạn đọc đánh giá cao khi biết nhanh: thư viện có cuốn sách không, còn mấy bản, mượn được bao lâu, thẻ đang ở trạng thái gì.

Tuy nhiên, trải nghiệm trên nhiều kênh hiện nay **chưa đồng nhất**: có nơi chỉ tra cứu tên sách nhưng không đăng ký mượn online; quy định nằm rải rác ở file PDF hoặc bảng tin; giao diện cũ, khó dùng trên mobile; thiếu thông báo khi hồ sơ thẻ hoặc yêu cầu mượn được duyệt.

### 2.1.2. Khảo sát hệ thống trên thị trường

Đã có nhiều giải pháp: OPAC tra cứu, phần mềm quản lý mượn trả, module tài liệu số. Chức năng thường gặp: tìm sách, quản lý bạn đọc–thẻ, lập phiếu, báo cáo. Hạn chế khi áp dụng cho UTC:

- Giao diện khó với người không chuyên; chưa tối ưu mobile.
- Tra cứu, làm thẻ, mượn online, mua tài liệu số **tách rời**.
- Khó cấu hình policy theo từng đối tượng trên cùng một web.
- Chưa hỗ trợ trọn vẹn **nộp và duyệt đồ án** trước khi công bố tra cứu.
- Hiệu năng và ổn định khi đông người tra cứu; xử lý thanh toán và email nhắc hạn.

### 2.1.3. Kết luận khảo sát

UTC eLibrary cần: giao diện thân thiện; gom dịch vụ trên một hệ thống; quy trình từng bước có thông báo; công cụ quản trị tập trung; cấu hình policy và paywall linh hoạt. Đây là cơ sở xác định yêu cầu Chương 2.

## 2.2. Tổng quan chức năng

### 2.2.1. Phân loại tác nhân

| STT | Tác nhân | Mô tả |
|-----|----------|--------|
| 1 | Bạn đọc chưa đăng nhập | Truy cập trang công khai, tra cứu, đọc quy định, đăng ký |
| 2 | Bạn đọc đã đăng nhập | Dịch vụ cá nhân: thẻ, mượn, mua PDF, nộp đồ án |
| 3 | Khách không tài khoản | Đăng ký thẻ qua API `library-cards/guest-register` |
| 4 | Thủ thư | Nghiệp vụ thư viện trên `/admin` |
| 5 | Admin / Super Admin | Quản trị hệ thống, phân quyền |
| 6 | Hệ thống | Cron, queue, webhook SePay, gửi notification |

**Vai trò** (`RoleType`): `STUDENT`, `TEACHER`, `MEMBER`, `GUEST`, `LIBRARIAN`, `ADMIN`, `SUPER_ADMIN`. Staff = `LIBRARIAN | ADMIN | SUPER_ADMIN`.

### 2.2.2. Danh sách chức năng bạn đọc

| Mã | Chức năng | Mô tả ngắn |
|----|-----------|------------|
| BD-01 | Đăng ký tài khoản | Form + xác thực OTP email |
| BD-02 | Đăng nhập / đăng xuất | Email/mật khẩu hoặc Microsoft Azure |
| BD-03 | Quên mật khẩu | Gửi link/OTP reset |
| BD-04 | Tra cứu sách | Tìm kiếm, lọc, phân trang |
| BD-05 | Xem chi tiết sách | Thông tin biên mục, bản sao, tài liệu số |
| BD-06 | Xem thử PDF | Preview có giới hạn trang (paywall) |
| BD-07 | Đăng ký thẻ | Workflow nhiều bước, có thể kèm phí |
| BD-08 | Giỏ mượn | Thêm sách, gửi yêu cầu mượn |
| BD-09 | Theo dõi phiếu mượn | Danh sách, chi tiết, trạng thái quá hạn |
| BD-10 | Yêu cầu gia hạn | Gửi và chờ duyệt |
| BD-11 | Giỏ mua tài liệu số | Thêm tài liệu trả phí |
| BD-12 | Thanh toán SePay | QR/chuyển khoản, webhook xác nhận |
| BD-13 | Tải PDF | Sau entitlement hoặc miễn phí theo policy |
| BD-14 | Nộp đồ án/luận văn | Upload, theo dõi trạng thái duyệt |
| BD-15 | Tin tức | Đọc bài public |
| BD-16 | Thông báo | `me/notifications` |
| BD-17 | Cập nhật hồ sơ | Một số trường cần thư viện duyệt |

### 2.2.3. Danh sách chức năng quản trị

| Mã | Chức năng | Mô tả ngắn |
|----|-----------|------------|
| QT-01 | Dashboard | Thống kê tổng quan |
| QT-02 | Quản lý người dùng | CRUD, phân quyền |
| QT-03 | Duyệt sửa hồ sơ | `user_profile_update_requests` |
| QT-04 | Quản lý sách in | Giáo trình, tham khảo, bản sao |
| QT-05 | Quản lý sách điện tử | `digital_assets`, paywall |
| QT-06 | Duyệt nộp tài liệu | `digital_document_submissions` |
| QT-07 | Kho, tủ, vị trí | `warehouses`, `storage_cabinets` |
| QT-08 | Phân loại, tác giả, NXB | Danh mục biên mục |
| QT-09 | Quản lý thẻ | Duyệt, cấp, thu hồi |
| QT-10 | Yêu cầu mượn | Duyệt/từ chối borrow requests |
| QT-11 | Phiếu mượn | Tạo tại quầy, trả sách, đồng bộ quá hạn |
| QT-12 | Gia hạn | Duyệt renewal requests |
| QT-13 | Chính sách mượn | `loan_policies` |
| QT-14 | Cài đặt thư viện & giá | `library_settings` |
| QT-15 | Tin tức | CRUD `news_posts` |
| QT-16 | Nhập/xuất Excel | Sách, kho, thẻ, phiếu mượn |

## 2.3. Biểu đồ use case

### 2.3.1. Use case tổng quát

*\[Hình 2.1: Biểu đồ use case tổng quát UTC eLibrary\]*

**Tác nhân Bạn đọc** (chưa/đã đăng nhập) gồm các use case: Đăng ký/đăng nhập; Tra cứu sách; Xem chi tiết & xem thử PDF; Đăng ký thẻ; Quản lý giỏ mượn; Gửi yêu cầu mượn; Xem phiếu mượn; Yêu cầu gia hạn; Giỏ mua & thanh toán tài liệu số; Nộp đồ án; Đọc tin tức; Quản lý tài khoản.

**Tác nhân Thủ thư/Admin:** Đăng nhập quản trị; Quản lý người dùng; Quản lý sách & kho; Duyệt thẻ; Duyệt mượn & gia hạn; Xử lý trả sách; Duyệt nộp tài liệu; Cấu hình policy & paywall; Đăng tin; Xem thống kê; Xuất Excel.

**Tác nhân Hệ thống:** Gửi email/notification; Chạy cron nhắc hạn/quá hạn; Xử lý webhook SePay.

### 2.3.2. Use case phân rã — Đăng ký, đăng nhập

*\[Hình 2.2: Use case phân rã đăng ký, đăng nhập, đăng xuất\]*

- **Đăng ký:** Nhập thông tin → gửi OTP (`OtpService`) → xác thực → tạo `users`.
- **Đăng nhập:** Credentials hoặc Azure AD → session web / JWT API.
- **Đăng xuất:** Hủy session / invalidate token.
- **Quên mật khẩu:** OTP hoặc link reset.

### 2.3.3. Use case phân rã — Tra cứu và mượn sách

*\[Hình 2.3–2.6: Tra cứu, giỏ mượn, yêu cầu mượn, phiếu mượn\]*

- Tra cứu không bắt buộc đăng nhập.
- Mượn yêu cầu đăng nhập + thẻ `active` + `LoanBorrowRequestService`.
- Thủ thư duyệt → `LoanService::createHomeBorrow` hoặc từ chối kèm lý do.

### 2.3.4. Use case phân rã — Tài liệu số và thanh toán

*\[Hình 2.7–2.9: Giỏ mua, thanh toán SePay, tải PDF\]*

- `DigitalPurchaseCartService` quản lý giỏ loại `digital_purchase`.
- `DigitalPaymentOrderService` tạo đơn, QR, nhận webhook.
- `DigitalPaywallService` kiểm tra quyền xem/tải.

### 2.3.5. Use case phân rã — Quản trị

*\[Hình 2.10–2.17: Quản lý user, thẻ, phiếu mượn, sách, thống kê\]*

Mỗi nhóm chức năng QT tương ứng một biểu đồ phân rã; thủ thư phải có role `LIBRARIAN` trở lên (hoặc permission cụ thể).

## 2.4. Quy trình nghiệp vụ

### 2.4.1. Quy trình làm thẻ thư viện

1. Bạn đọc điền hồ sơ (web hoặc guest-register).  
2. Trạng thái `workflow_status`: `draft` → `pending_payment` (nếu có phí) → `pending_review`.  
3. Thủ thư xem hồ sơ trên `/admin/library-cards`, `approve-review` hoặc `reject-review`.  
4. Nếu duyệt: `pending_pickup` → cấp thẻ `active`, gán `card_number`, `issue_date`, `expiry_date`.  
5. `LibraryCardNotificationDispatcher` thông báo cho bạn đọc.

### 2.4.2. Quy trình mượn sách về nhà

1. Tra cứu → chọn sách còn bản sao khả dụng.  
2. Thêm vào giỏ mượn (`/dich-vu/gio-sach`).  
3. Gửi yêu cầu → bản ghi `loan_borrow_requests` (+ items).  
4. Thủ thư duyệt trên `/admin/loans/borrow-requests`.  
5. Hệ thống gọi `LoanService::createHomeBorrow` trong transaction:  
   - Kiểm tra thẻ, policy (`allow_home`, `max_books`, `max_days`).  
   - Không quá hạn chưa xử lý; không nợ phạt (nếu có).  
   - Gán `book_copy`, tạo `loans` + `loan_items`.  
6. Bạn đọc xem phiếu tại `/dich-vu/phieu-muon/{id}`.  
7. Đến hạn: cron `loans:notify-due-soon`; quá hạn: `loans:sync-overdue`, có thể khóa thẻ `library-cards:sync-overdue-locks`.

### 2.4.3. Quy trình mua tài liệu điện tử

1. Xem chi tiết sách → xem thử (giới hạn trang).  
2. Thêm vào giỏ mua → `/dich-vu/thanh-toan`.  
3. `POST me/digital-payment-orders` → đơn `pending`, hiển thị QR SePay.  
4. Bạn đọc chuyển khoản → SePay gọi webhook → `DigitalPaymentOrderService` xác nhận.  
5. Tạo `digital_asset_pdf_download_entitlements` → cho phép tải tại route có middleware kiểm tra entitlement.  
6. Đơn `pending` quá hạn: `digital-orders:expire-pending` (mỗi 5 phút).

### 2.4.4. Quy trình nộp đồ án/luận văn

1. Sinh viên đăng nhập → nộp file qua `DigitalDocumentSubmissionService`.  
2. Trạng thái chờ duyệt.  
3. Thủ thư trên `/admin/books/digital/submissions` — approve/reject.  
4. Approve: có thể tạo `Book` + `DigitalAsset` + `thesis_metadata` — đưa vào kho tra cứu có kiểm soát truy cập.

### 2.4.5. Biểu đồ tuần tự (mô tả)

*\[Hình 2.19: Biểu đồ tuần tự đăng nhập\]* — Client → AuthController → AuthService → users; trả JWT/session.

*\[Hình 2.20–2.26: Biểu đồ tuần tự duyệt mượn, thanh toán, …\]* — Mô tả tương tác Client ↔ Controller ↔ Service ↔ DB ↔ SePay.

## 2.5. Đặc tả chức năng

### Bảng 2.1. Đặc tả chức năng tra cứu sách

| Hạng mục | Nội dung |
|----------|----------|
| **Tên** | Tra cứu sách và tài liệu số |
| **Mã** | BD-04 |
| **Mô tả** | Cho phép tìm kiếm trong kho biên mục `books` kèm tác giả, phân loại, trạng thái bản sao |
| **Tác nhân** | Bạn đọc (không yêu cầu đăng nhập) |
| **Tiền điều kiện** | Hệ thống hoạt động; dữ liệu sách đã được thủ thư nhập |
| **Hậu điều kiện** | Hiển thị danh sách phù hợp; không lộ file PDF đầy đủ nếu chưa có quyền |
| **Luồng chính** | 1) Truy cập `/tra-cuu-sach`. 2) Nhập từ khóa/chọn bộ lọc. 3) Server truy vấn có `with()` tránh N+1. 4) Hiển thị kết quả phân trang. 5) Chọn một đầu sách → chuyển chi tiết. |
| **Luồng thay thế** | Không có kết quả → thông báo gợi ý đổi từ khóa. |
| **Luồng ngoại lệ** | Lỗi mạng/server → thông báo lỗi thân thiện. |

### Bảng 2.2. Đặc tả chức năng gửi yêu cầu mượn

| Hạng mục | Nội dung |
|----------|----------|
| **Tên** | Gửi yêu cầu mượn sách từ giỏ |
| **Mã** | BD-08 |
| **Tác nhân** | Bạn đọc đã đăng nhập |
| **Tiền điều kiện** | Thẻ `workflow_status` cho phép; có sách trong giỏ; sách còn bản khả dụng |
| **Luồng chính** | 1) Mở giỏ mượn. 2) Xác nhận danh sách. 3) `POST me/loan-borrow-requests`. 4) Lưu request + items. 5) Thông báo thủ thư (`StaffWorkQueueNotificationService`). |
| **Hậu điều kiện** | Request ở trạng thái chờ duyệt; bạn đọc theo dõi được trạng thái |

### Bảng 2.3. Đặc tả chức năng duyệt yêu cầu mượn

| Hạng mục | Nội dung |
|----------|----------|
| **Tên** | Duyệt yêu cầu mượn |
| **Mã** | QT-10 |
| **Tác nhân** | Thủ thư |
| **Luồng chính** | 1) Mở `/admin/loans/borrow-requests`. 2) Xem chi tiết. 3) Approve → `LoanService::createHomeBorrow` hoặc Reject + lý do. 4) Thông báo bạn đọc. |

### Bảng 2.4. Đặc tả thanh toán SePay

| Hạng mục | Nội dung |
|----------|----------|
| **Tên** | Thanh toán tài liệu điện tử |
| **Mã** | BD-12 |
| **Luồng chính** | Tạo đơn → hiển thị QR → webhook → cập nhật `orders` → tạo entitlement |
| **Bảo mật** | Xác thực `SEPAY_WEBHOOK_SECRET`; idempotency; không log PII nhạy cảm |

### Bảng 2.5. Đặc tả duyệt thẻ

| Hạng mục | Nội dung |
|----------|----------|
| **Tên** | Duyệt hồ sơ thẻ thư viện |
| **Mã** | QT-09 |
| **Luồng** | `approve-review` / `reject-review` trên `LibraryCardManagementService` |

### Bảng 2.6. Đặc tả nộp đồ án

| Hạng mục | Nội dung |
|----------|----------|
| **Tên** | Nộp tài liệu số chờ duyệt |
| **Mã** | BD-14 |
| **Luồng** | Upload → `submitAsReaderPending` → staff approve → tạo biên mục chính thức |

## 2.6. Yêu cầu phi chức năng

### 2.6.1. Tính tin cậy và sẵn sàng

- Các thao tác ghi quan trọng dùng `DB::transaction()`.
- Health check `/api/health` (database, cache, redis).
- Queue xử lý tác vụ nền; cron `withoutOverlapping()`.

### 2.6.2. Tính dễ sử dụng

- Giao diện Tailwind thống nhất; tiếng Việt.
- Nút bấm tối thiểu 44×44px trên mobile; `safe-area-inset`.
- Bảng admin `overflow-x-auto`.

### 2.6.3. Tính bảo trì

- Service layer tách khỏi controller; FormRequest validate.
- Migration có `down()`; comment cột trạng thái.
- Test tự động regression.

### 2.6.4. Hiệu năng

- Eager loading quan hệ list/detail.
- Index CSDL (`performance_indexes` migration).
- Rate limit: `api` 60/phút, `auth` 5/phút.

### 2.6.5. Bảo mật

- JWT + middleware `init`; kiểm tra domain API.
- Spatie RBAC; middleware `role_or_permission` trên `/admin`.
- Chống XSS/CSRF (Laravel + Inertia); paywall file PDF.
- Audit `created_by`, `updated_by` (`HasAuditFields`).

### 2.6.6. Khả năng mở rộng

- API `/api/v1` tách khỏi giao diện Inertia — sẵn sàng cho app mobile.
- `DeployProfile` enum: local, vps, infinityfree.

---

# CHƯƠNG 3. CÔNG NGHỆ SỬ DỤNG

Dựa trên yêu cầu Chương 2, em lựa chọn các công nghệ phổ biến, cộng đồng hỗ trợ lớn, phù hợp triển khai web doanh nghiệp/vừa và nhỏ. Hệ thống UTC eLibrary gồm **hai phần giao tiếp chặt chẽ**: Back-end (Laravel) và Front-end (Vue + Inertia), cùng **MySQL** lưu trữ và các dịch vụ bổ trợ (SePay, email, lưu file).

## 3.1. Kiến trúc tổng thể

### 3.1.1. Mô hình triển khai logic

Hệ thống theo kiểu **monolith có tổ chức lớp**:

1. **Lớp trình bày:** Vue components trong `resources/js/Pages`, layouts, UI components.
2. **Lớp ứng dụng:** HTTP Controllers (web + API), FormRequest, Middleware.
3. **Lớp nghiệp vụ:** `app/Services/*` — nơi đặt quy tắc mượn, thẻ, thanh toán.
4. **Lớp dữ liệu:** Eloquent Models, migration, seeders.

Luồng Inertia: Trình duyệt gửi request → Laravel route → Controller trả `Inertia::render(Page, props)` → Vue render. Luồng API: Client gửi request kèm `Authorization: Bearer` → middleware `init` → Controller → Service → `ApiResponse` JSON.

### 3.1.2. Hai kênh truy cập

| Kênh | Công nghệ | Đối tượng |
|------|-----------|-----------|
| Web Inertia | Session + cookie | Bạn đọc, thủ thư dùng trình duyệt |
| REST API | JWT | SPA tương lai, tích hợp, test tự động |

Cùng chia sẻ Service và Model — tránh trùng lặp logic.

## 3.2. PHP và Laravel

### 3.2.1. Vai trò PHP

PHP (phiên bản ^8.2) là ngôn ngữ kịch bản phía máy chủ. Trong UTC eLibrary, PHP thông qua Laravel xử lý: validate input, orchestrate service, truy vấn CSDL, tạo response HTML/JSON, gửi mail, lên lịch command.

### 3.2.2. Giới thiệu Laravel 12

Laravel là framework PHP mã nguồn mở theo hướng **MVC mở rộng**, cung cấp routing, ORM Eloquent, migration, validation, queue, scheduling, authentication. Em chọn Laravel vì:

- Cấu trúc thư mục rõ ràng, phù hợp đồ án có nhiều module.
- Eloquent giúp làm việc với quan hệ phức tạp (sách–bản sao–mượn–thẻ).
- Hệ sinh thái package (JWT, Spatie Permission, Excel, Socialite).
- Tài liệu đầy đủ, cộng đồng lớn tại Việt Nam.

### 3.2.3. Cấu trúc MVC trong dự án

- **Model:** `App\Models\Book`, `Loan`, `LibraryCard`, … — quan hệ `hasMany`, `belongsTo`, soft delete nơi cần.
- **View (Inertia):** không dùng Blade view truyền thống cho trang chính mà dùng Vue page; Blade chỉ layout gốc `app.blade.php` nhúng Vite.
- **Controller:** ví dụ `BookController`, `LoanController`, `MeLoanController` — điều hướng, gọi service, trả Resource/`ApiResponse`.

**Service layer** là điểm bổ sung so với MVC thuần: `LoanService::createHomeBorrow`, `returnHomeLoan`, `calculateOverdueFine` tập trung nghiệp vụ UTC.

### 3.2.4. Routing và middleware

- `routes/web.php` — trang độc giả và admin Inertia.
- `routes/api.php` — prefix `v1`, nhóm `me`, staff routes.
- Middleware tiêu biểu: `auth`, `init`, `role_or_permission`, `throttle:api|auth|refresh`, `LogApiRequests`.

### 3.2.5. Migration và Eloquent

37+ migration tạo bảng có index, foreign key, `comment()` mô tả cột trạng thái. Ví dụ `library_cards.workflow_status` ghi chú các bước workflow. Trait `HasAuditFields` ghi `created_by`, `updated_by` phục vụ truy vết.

## 3.3. Vue.js và Inertia.js

### 3.3.1. Vue.js 3

Vue là framework JavaScript tiến bộ, dùng **Composition API** và `<script setup>` trong dự án. Vue đảm nhiệm:

- Render danh sách tra cứu, form đa bước làm thẻ.
- State cục bộ (giỏ mượn/mua trên client kết hợp API).
- Component tái sử dụng: bảng admin, modal, toast.

Ưu điểm: học curve vừa phải, tích hợp tốt với Vite, reactivity cho UI cập nhật nhanh.

### 3.3.2. Inertia.js

Inertia là cầu nối giữa Laravel và Vue **không cần viết REST API riêng cho từng màn hình** web. Khi người dùng click link hoặc submit form Inertia:

1. Gửi request XHR với header `X-Inertia`.
2. Server trả JSON gồm tên component + props.
3. Vue swap page — **không reload toàn bộ HTML**.

So với AJAX thuần: Inertia chuẩn hóa pattern, tận dụng validation Laravel, redirect, flash message. Phù hợp đồ án có nhiều form server-driven.

### 3.3.3. Vite 7

Vite build và HMR (Hot Module Replacement) khi `npm run dev` — tăng tốc phát triển giao diện. Production build tối ưu chunk JS/CSS.

### 3.3.4. Ziggy

Package `tightenco/ziggy` export tên route Laravel sang JS — gọi `route('reader.catalog')` trong Vue, tránh hard-code URL.

## 3.4. Tailwind CSS

Tailwind là framework **utility-first**: style trực tiếp bằng class (`flex`, `p-4`, `text-sm`). Dự án dùng Tailwind 3 kèm `@tailwindcss/forms`, `tailwindcss-animate`, component UI (radix-vue, reka-ui).

**Responsive:** breakpoint `sm`, `md`, `lg` — layout admin chuyển từ sidebar sang drawer trên mobile.

**Chuẩn mobile UTC:** `min-h-[44px]`, `min-w-[44px]` cho nút; `pb-safe` với `env(safe-area-inset-bottom)` trên header/footer reader.

## 3.5. MySQL

MySQL là hệ quản trị CSDL quan hệ mã nguồn mở, phù hợp lưu dữ liệu có cấu trúc: biên mục, giao dịch mượn, đơn hàng. Laravel dùng driver `mysql` mặc định.

**Nguyên tắc thiết kế:**

- Chuẩn hóa: tách `books` và `book_copies`, `loans` và `loan_items`.
- Index cho cột lọc (`workflow_status`, `holder_type`, foreign keys).
- Soft delete cho thẻ, sách khi cần khôi phục.
- JSON `params` trên `library_cards` cho metadata linh hoạt.

## 3.6. JWT, Spatie Permission và bảo mật API

### 3.6.1. JWT (`php-open-source-saver/jwt-auth`)

API stateless dùng Bearer token. Luồng: `POST auth/login` → access token; `POST auth/refresh` (throttle riêng); middleware `init` parse JWT hoặc fallback session web.

### 3.6.2. Spatie Laravel Permission

Quản lý `roles` và `permissions` guard `api`. Staff route bọc `role_or_permission:SUPER_ADMIN|role_prefix_ADMIN|role_prefix_LIBRARIAN`. `CurrentUser::hasRoleOrPermission` — Super Admin và staff có quyền truy cập admin UI.

### 3.6.3. Rate limiting (`AppServiceProvider`)

| Tên | Giới hạn | Áp dụng |
|-----|----------|---------|
| api | 60/phút | Theo user id hoặc IP |
| auth | 5/phút | Login, register, OTP |
| refresh | 10/phút | Refresh token |

### 3.6.4. FormRequest và validation

Mọi POST/PUT/PATCH thay đổi state qua FormRequest — thông báo lỗi tiếng Việt, tái sử dụng rule.

## 3.7. SePay và xử lý đơn hàng số

**SePay** là cổng thanh toán tại Việt Nam. Luồng:

1. `DigitalPaymentOrderService` tạo `orders`, `order_items`, số tiền, mã tham chiếu.
2. `SepayQrService` sinh QR hiển thị cho bạn đọc.
3. SePay gọi `POST /api/v1/sepay/webhook` — xác thực secret/token.
4. Cập nhật trạng thái đơn, ghi `payment_transactions`, tạo entitlement tải PDF.

`SepayTransactionApiService` hỗ trợ đối soát khi cần. Cron hủy đơn treo quá hạn.

## 3.8. Lập lịch tác vụ và hàng đợi

**Schedule** (`routes/console.php`):

| Command | Mô tả |
|---------|--------|
| `loans:notify-due-soon` | Email/notification nhắc trước hạn trả |
| `loans:sync-overdue` | Đánh dấu quá hạn |
| `library-cards:sync-overdue-locks` | Khóa mượn khi vi phạm |
| `digital-orders:expire-pending` | Hết hạn đơn chưa thanh toán |
| `storage:sync-quantities` | Đồng bộ số lượng kho |
| `trash:purge` | Dọn soft delete cũ |

**Queue:** `QUEUE_CONNECTION=database` — job gửi mail, xử lý nặng. Script `composer dev` chạy `queue:listen` song song `artisan serve` và `vite`.

## 3.9. Các thư viện bổ trợ

| Package | Mục đích |
|---------|----------|
| maatwebsite/excel | Import/export sách, kho, thẻ, phiếu mượn |
| barryvdh/laravel-dompdf | Xuất PDF khi cần |
| laravel/socialite + microsoft-azure | Đăng nhập Azure AD |
| league/flysystem-aws-s3-v3 | Lưu media Cloudflare R2 |
| predis/predis | Redis cache/queue |

---

# CHƯƠNG 4. THIẾT KẾ, XÂY DỰNG, KIỂM THỬ VÀ TRIỂN KHAI

## 4.1. Thiết kế kiến trúc phần mềm

### 4.1.1. Lựa chọn kiến trúc

Em áp dụng kiến trúc **layered monolith** thay vì microservices vì:

- Quy mô đồ án vừa phải, team một người — microservices tăng độ phức tạp vận hành.
- Nghiệp vụ mượn–trả cần **transaction ACID** trên nhiều bảng — monolith + MySQL thuận lợi.
- Vẫn tách **Service** để sau này có thể trích module nếu cần.

### 4.1.2. Sơ đồ thành phần (mô tả)

*\[Hình 4.1: Sơ đồ kiến trúc tổng thể\]*

- **Client:** Trình duyệt (Reader UI, Admin UI).
- **Application server:** Laravel (Web + API + Queue worker + Scheduler).
- **Database:** MySQL.
- **External:** SePay, SMTP email, Azure AD, (tùy chọn) Cloudflare R2.

### 4.1.3. Phân tách module theo thư mục

| Thư mục | Nội dung |
|---------|----------|
| `app/Services/` | Logic nghiệp vụ |
| `app/Http/Controllers/Api/` | API REST |
| `app/Http/Controllers/Frontend/` | Inertia pages |
| `app/Models/` | Eloquent models |
| `resources/js/Pages/Reader/` | Giao diện độc giả |
| `resources/js/Pages/Admin/` | Giao diện quản trị |
| `database/migrations/` | Schema CSDL |
| `tests/Feature/` | Kiểm thử tích hợp |

## 4.2. Thiết kế giao diện

### 4.2.1. Nguyên tắc thiết kế UX

- **Rõ ràng:** Mỗi trang một mục đích chính (tra cứu, giỏ, phiếu mượn).
- **Nhất quán:** Header/footer reader chung; admin dùng layout sidebar.
- **Phản hồi:** Loading state, thông báo thành công/lỗi (toast/alert).
- **Tiếp cận mobile:** Touch target đủ lớn; bảng cuộn ngang.

### 4.2.2. Màn hình độc giả (mô tả chi tiết)

**Trang chủ** — `Reader/Home.vue`  
Banner giới thiệu, ô tìm kiếm nhanh, liên kết dịch vụ (tra cứu, làm thẻ, quy định), danh sách tin tức và sách mới. Giúp người mới định hướng trong 1–2 thao tác.

**Tra cứu** — `Reader/Catalog.vue`  
Thanh tìm kiếm, bộ lọc (phân loại, loại tài liệu), lưới hoặc danh sách kết quả. Mỗi thẻ hiển thị: ảnh bìa, nhan đề, tác giả, số bản khả dụng. Phân trang phía dưới.

**Chi tiết sách** — `Reader/BookShow.vue`  
Metadata đầy đủ, danh sách bản sao hoặc tóm tắt vị trí kho, khu vực tài liệu số: nút xem thử, thêm giỏ mượn/mua. Hiển thị trạng thái “chỉ đọc tại chỗ” nếu áp dụng.

**Xem thử PDF** — `Reader/BookDigitalPreview.vue`  
Hiển thị preview từng trang PNG (route `xem-truoc/trang/{page}.png`), giới hạn theo `DigitalAssetPreviewService` — không lộ full file.

**Dịch vụ — Giỏ sách** — `Reader/Services/BookCart.vue`  
Tab **Mượn** và **Mua**: chỉnh số lượng, xóa dòng, tổng hợp trước khi gửi yêu cầu hoặc chuyển thanh toán.

**Thanh toán** — tích hợp trong luồng `digital-payment`  
Hiển thị mã đơn, QR SePay, hướng dẫn chuyển khoản, trạng thái chờ xác nhận — polling hoặc thông báo sau webhook.

**Phiếu mượn** — `Reader/Loans/Index.vue`, `Show.vue`  
Bảng phiếu: mã, ngày mượn, hạn trả, trạng thái (đang mượn, quá hạn, đã trả). Chi tiết liệt kê từng cuốn. Nút yêu cầu gia hạn nếu đủ điều kiện.

**Tài khoản** — `Reader/Profile.vue`  
Sửa thông tin, avatar, xem lịch sử yêu cầu cập nhật hồ sơ. Liên kết đổi mật khẩu.

**Đăng nhập / đăng ký** — `Auth/Login.vue`, `Register.vue`, `VerifyOtp.vue`  
Form gọn; link đăng nhập Microsoft; hướng dẫn OTP.

*\[Hình 4.2–4.15: Chụp màn hình các trang Reader — chèn khi hoàn thiện Word\]*

### 4.2.3. Màn hình quản trị

**Dashboard** — `Admin/Dashboard.vue`  
Thẻ số liệu: tổng sách, thẻ active, phiếu đang mượn, quá hạn, đơn pending. Biểu đồ đơn giản (nếu có).

**Quản lý sách** — `Admin/Books/Index.vue` (phân nhánh printed, textbook, reference, digital)  
Bảng có tìm kiếm, lọc, nút thêm/sửa/xóa, import/export Excel.

**Thẻ thư viện** — `Admin/LibraryCards/Index.vue`, `Requests.vue`, `Counter.vue`  
Hàng đợi duyệt, form duyệt/từ chối, thao tác tại quầy cấp thẻ.

**Phiếu mượn** — `Admin/Loans/*`  
Danh sách phiếu, tạo phiếu tại quầy, màn trả sách, duyệt borrow/renewal requests.

**Cài đặt** — `Admin/LibrarySettings/Index.vue`, `Pricing.vue`  
Form cấu hình chung và bảng giá paywall tài liệu số.

*\[Hình 4.16–4.25: Chụp màn hình Admin\]*

## 4.3. Thiết kế cơ sở dữ liệu

### 4.3.1. Mô hình quan hệ tổng quát

*\[Hình 4.26: Sơ đồ ERD UTC eLibrary\]*

Quan hệ chính:

- `users` — `library_cards` (1:0..1 hoặc 1:n tùy nghiệp vụ thay thẻ)
- `library_cards` — `loans` (1:n)
- `books` — `book_copies` (1:n)
- `books` — `digital_assets` (1:n)
- `loans` — `loan_items` (1:n)
- `users` — `orders` (1:n) — đơn mua tài liệu số
- `digital_assets` — `digital_asset_pdf_download_entitlements` (1:n)

### 4.3.2. Bảng `users`

| Cột | Kiểu | Mô tả |
|-----|------|--------|
| id | int PK | Khóa chính |
| email | varchar | Email đăng nhập, unique |
| password | varchar | Băm bcrypt |
| user_type | enum/RoleType | STUDENT, TEACHER, … |
| faculty_id, department_id | int FK | Liên kết đơn vị |
| … | | Các trường profile, audit |

### 4.3.3. Bảng `library_cards`

| Cột | Kiểu | Mô tả |
|-----|------|--------|
| card_number | varchar | Số thẻ, index |
| holder_type | enum | student / teacher / external |
| workflow_status | varchar | draft, pending_review, active, … |
| issue_date, expiry_date | date | Hiệu lực thẻ |
| user_id | int FK nullable | Liên kết tài khoản |
| reviewed_by, reviewed_at | | Duyệt hồ sơ |

Bảng phụ `library_card_payments` lưu trạng thái phí làm thẻ.

### 4.3.4. Bảng `books` và `book_copies`

`books`: nhan đề, ISBN, classification_id, warehouse_id, resource_kind (sách in, đồ án, …), mô tả, ảnh bìa.

`book_copies`: mã ĐKCB, tình trạng (available, on_loan, …), vị trí tủ/kho.

### 4.3.5. Bảng `loans`, `loan_items`

`loans`: library_card_id, loan_type (home/onsite), borrowed_at, due_at, returned_at, status, fine_amount.

`loan_items`: loan_id, book_id, book_copy_id — chi tiết từng cuốn.

### 4.3.6. Bảng thanh toán số

`orders`, `order_items`, `payment_transactions`, `digital_asset_pdf_download_entitlements` — phục vụ SePay và quyền tải file.

### 4.3.7. Bảng `loan_policies`

Cấu hình theo loại bạn đọc: `max_books`, `max_days`, `allow_home`, `allow_onsite`, `overdue_fine_per_day`, …

### 4.3.8. Bảng `digital_document_submissions`

File upload chờ duyệt, trạng thái, `approved_book_id` sau khi duyệt.

## 4.4. Xây dựng ứng dụng

### 4.4.1. Quy trình xây dựng theo giai đoạn

| Giai đoạn | Nội dung | Kết quả |
|-----------|----------|---------|
| 1 | Khởi tạo Laravel, Vue, Inertia, auth | Đăng nhập cơ bản |
| 2 | Module biên mục: sách, kho, phân loại | Tra cứu admin + public |
| 3 | Thẻ thư viện + workflow | Làm thẻ online |
| 4 | Mượn–trả + borrow request | Phiếu mượn end-to-end |
| 5 | Digital asset + paywall + SePay | Mua và tải PDF |
| 6 | Nộp đồ án, tin tức, notification | Dịch vụ bổ sung |
| 7 | Test, tối ưu, tài liệu triển khai | Bàn giao đồ án |

### 4.4.2. Cấu trúc thư mục dự án

*\[Hình 4.27: Cây thư mục app/ và resources/js\]*

- `app/Services/LoanService.php` — ~hàng trăm dòng logic mượn/trả/phạt.
- `app/Services/LibraryCard/*` — tách guest, account, management.
- `app/Services/DigitalPaymentOrderService.php` — đơn hàng SePay.

### 4.4.3. Ví dụ luồng code — Duyệt mượn

1. `LoanBorrowRequestController@approve` nhận request đã validate.  
2. Gọi `LoanBorrowRequestService` kiểm tra trạng thái request.  
3. Gọi `LoanService::createHomeBorrow` trong `DB::transaction()`.  
4. Trừ trạng thái `book_copy`, tạo `loans` + `loan_items`.  
5. `NotificationService` gửi thông báo bạn đọc.  
6. Trả `ApiResponse` success + `LoanResource`.

### 4.4.4. Thư viện và công cụ sử dụng

**Bảng 4.1. Công cụ phát triển**

| STT | Công cụ | Phiên bản gợi ý | Mục đích |
|-----|---------|-----------------|----------|
| 1 | Windows 11 | | Hệ điều hành |
| 2 | PHP | 8.2+ | Runtime back-end |
| 3 | Composer | 2.x | Quản lý package PHP |
| 4 | Node.js | 20+ | Build front-end |
| 5 | MySQL | 8.x | CSDL |
| 6 | Git | | Version control |
| 7 | VS Code / Cursor | | IDE |
| 8 | Postman / Insomnia | | Test API |
| 9 | PHPUnit | 11.x | Unit/Feature test |

## 4.5. Kiểm thử

### 4.5.1. Chiến lược kiểm thử

- **Unit test:** Service thuần logic (paywall, tính tiền đơn).  
- **Feature test:** HTTP API end-to-end với database refresh.  
- **Manual test:** Luồng UI Inertia trên Chrome + mobile emulator.

### 4.5.2. Các test tự động tiêu biểu

| File test | Mục đích |
|-----------|----------|
| `AuthSecurityTest` | Đăng nhập, token, quyền truy cập |
| `LibraryCardSecurityTest` | Thẻ, workflow, không vượt quyền |
| `LoanApiSecurityTest` | Mượn, policy, chặn vi phạm |
| `DigitalPaywallAccessTest` | Không tải PDF trái phép |
| `DigitalPurchaseCartApiTest` | Giỏ mua, đồng bộ |
| `DigitalPaymentOrderSecurityTest` | Đơn, webhook giả mạo |
| `BookApiSecurityTest` | CRUD sách theo role |

### 4.5.3. Bảng test case thủ công (trích)

**Bảng 4.2. Kiểm thử tra cứu sách**

| TC | Bước thực hiện | Kết quả mong đợi |
|----|----------------|------------------|
| TC-01 | Tìm từ khóa có trong kho | Danh sách khớp |
| TC-02 | Tìm từ khóa không có | Thông báo trống |
| TC-03 | Lọc theo phân loại | Chỉ hiện loại đó |

**Bảng 4.3. Kiểm thử mượn vượt policy**

| TC | Điều kiện | Kết quả mong đợi |
|----|-----------|------------------|
| TC-10 | Thẻ hết hạn | Từ chối, message rõ |
| TC-11 | Đã mượn đủ max_books | Từ chối |
| TC-12 | Có phiếu quá hạn | Từ chối mượn mới |

**Bảng 4.4. Kiểm thử thanh toán SePay**

| TC | Bước | Kết quả |
|----|------|---------|
| TC-20 | Tạo đơn, hiển thị QR | Đơn pending |
| TC-21 | Webhook thành công | Đơn paid + entitlement |
| TC-22 | Tải PDF sau paid | File tải được |
| TC-23 | Tải PDF chưa paid | HTTP 403 |

## 4.6. Triển khai

### 4.6.1. Môi trường

- **Local:** `.env` APP_URL localhost, DB local, SePay sandbox.  
- **VPS:** Nginx + PHP-FPM, SSL, supervisor cho queue và schedule.  
- **Shared hosting:** `DeployProfile::infinityfree` — giới hạn preview PDF, disk.

### 4.6.2. Biến môi trường quan trọng

`DB_*`, `JWT_SECRET`, `SEPAY_*`, `MEDIA_DISK`, `DIGITAL_ASSETS_DISK`, `AZURE_*` (SSO), mail SMTP.

### 4.6.3. Các bước triển khai VPS (tóm tắt)

1. Clone mã, `composer install --no-dev`, `npm ci && npm run build`.  
2. `php artisan migrate --force`, `storage:link`.  
3. Cấu hình cron: `* * * * * php artisan schedule:run`.  
4. Supervisor: `queue:work`.  
5. Đăng ký webhook SePay trỏ URL production.

---

## 2.7. Phân tích so sánh với quy trình thủ công (bổ sung)

Khi vận hành thủ công, mỗi lượt mượn sách thường cần: tra cứu sổ hoặc Excel → kiểm tra thẻ giấy → ghi phiếu → cập nhật tình trạng bản sao. Với 500 lượt mượn/tháng, sai sót nhập liệu và chậm tra cứu tích lũy đáng kể. UTC eLibrary **chuẩn hóa dữ liệu** trên một schema: mỗi bản sao (`book_copies`) có trạng thái rõ ràng; mỗi phiếu (`loans`) liên kết thẻ và hạn trả; hệ thống tự nhắc qua cron thay vì phụ thuộc nhớ việc của cán bộ.

Đối với tài liệu số, quy trình cũ thường là email hoặc USB nội bộ — khó kiểm soát ai đã tải. Paywall + SePay + entitlement tạo **dấu vết giao dịch** (`orders`, `payment_transactions`) phục vụ đối soát và báo cáo doanh thu tài liệu số (nếu nhà trường thu phí).

## 2.8. Ma trận chức năng theo tác nhân (bổ sung)

| Chức năng \ Tác nhân | Khách | SV/GV | Thủ thư | Admin |
|----------------------|:-----:|:-----:|:-------:|:-----:|
| Tra cứu công khai | ✓ | ✓ | ✓ | ✓ |
| Đăng ký tài khoản | ✓ | ✓ | — | — |
| Làm thẻ | ✓ | ✓ | Duyệt | Cấu hình |
| Gửi yêu cầu mượn | — | ✓ | Duyệt | — |
| Lập phiếu tại quầy | — | — | ✓ | ✓ |
| Mua PDF | — | ✓ | — | Cấu giá |
| Nộp đồ án | — | ✓ | Duyệt | — |
| CRUD sách | — | — | ✓ | ✓ |
| Phân quyền | — | — | — | ✓ |

## 2.9. Mô tả chi tiết luồng đăng ký tài khoản (kịch bản văn bản)

**Bước 1:** Người dùng mở `/register`, nhập email, mật khẩu, họ tên và thông tin bắt buộc. Client Vue validate sơ bộ (định dạng email, độ dài mật khẩu).

**Bước 2:** `POST /api/v1/auth/register` — server validate FormRequest, tạo bản ghi tạm hoặc gửi OTP qua `OtpService` và bảng `email_otp`.

**Bước 3:** Người dùng chuyển `/verify-otp`, nhập mã. `POST auth/verify-otp` kích hoạt tài khoản `users`.

**Bước 4:** Có thể đăng nhập Microsoft Azure thay cho bước 2–3 nếu trường bật SSO — `SocialAuthController` map claim Azure sang user nội bộ.

**Bước 5:** Sau đăng nhập, redirect `reader.home` hoặc `admin.dashboard` nếu `user_type` thuộc staff.

Kịch bản lỗi: OTP hết hạn, email trùng, vượt throttle `auth` — trả message tiếng Việt, không lộ user enumeration quá chi tiết (cân bằng bảo mật và UX).

## 2.10. Mô tả chi tiết luồng trả sách tại quầy

Thủ thư mở `/admin/loans/return`, quét hoặc nhập mã phiếu/mã ĐKCB. `LoanService::processReturnBook` kiểm tra phiếu đang active, cập nhật `returned_at`, đặt `book_copy` về available, tính phạt quá hạn nếu có (`calculateOverdueFine`) theo `loan_policies.overdue_fine_per_day`. Nếu trả một phần (nhiều cuốn), `bulkProcessReturnBooks` xử lý theo lô trong transaction. In biên nhận hoặc hiển thị tổng phạt trên màn hình — bạn đọc thanh toán phạt ngoài phạm vi đồ án hoặc ghi nhận tùy cấu hình triển khai thực tế.

---

## 3.10. PHP — vai trò và cách dùng trong dự án (mở rộng)

PHP là ngôn ngữ kịch bản phía máy chủ, phù hợp ứng dụng web động. Trong UTC eLibrary, PHP không render HTML thuần cho từng trang mà chủ yếu **trả JSON Inertia** hoặc **JSON API**. Ưu điểm: hosting phổ biến, chi phí thấp, tích hợp MySQL tốt. PHP 8.2 bổ sung cải tiến hiệu năng và type system — code service có type-hint rõ ràng, giảm lỗi runtime.

Các điểm PHP đảm nhiệm cụ thể: (1) Validate và sanitize input từ form/API. (2) Điều phối transaction CSDL. (3) Gọi HTTP client tới SePay khi cần. (4) Xử lý upload file PDF, lưu disk S3/R2. (5) Render PDF preview pipeline. (6) Đăng ký scheduled command.

## 3.11. Laravel — các thành phần được sử dụng sâu (mở rộng)

**Routing:** Tách file `web.php` và `api.php` — tránh lẫn middleware session và JWT.

**Eloquent:** Quan hệ `Book::with(['authors','classification','copies'])` trên trang tra cứu — giảm N+1. Scope local trên model (ví dụ chỉ sách published) nếu có.

**Migration:** Version schema theo thời gian phát triển đồ án; `down()` hỗ trợ rollback khi demo.

**Queue & Mail:** Job gửi email OTP, notification — không block request HTTP.

**Storage:** Facade `Storage::disk('digital_assets')` abstract local vs R2.

**Exception Handler:** Format lỗi API thống nhất qua `ApiResponse`.

## 3.12. Vue 3 Composition API — tổ chức component (mở rộng)

Trang Inertia thường có cấu trúc: `defineProps` nhận dữ liệu server; `useForm` từ Inertia cho submit; `computed` cho lọc client-side nhẹ; gọi `axios` hoặc `router.visit` cho thao tác không reload full page. Component dùng chung: `DataTable`, `Pagination`, `ConfirmDialog`, `Toast`. Tách logic giỏ mượn sang composable `useBookCart` (nếu có) để tái sử dụng giữa BookShow và BookCart page.

State management: không bắt buộc Pinia vì Inertia ưu tiên server state — giảm phức tạp đồ án.

## 3.13. So sánh Inertia với kiến trúc SPA + API thuần

| Tiêu chí | Inertia + Laravel | Vue SPA + REST riêng |
|----------|-------------------|----------------------|
| Số layer API | Ít hơn cho web | Mỗi màn một endpoint |
| SEO / first paint | Server-driven tốt hơn | Cần SSR riêng |
| Phù hợp đồ án | ✓ Tập trung nghiệp vụ | Tách team FE/BE |
| Mobile app | Vẫn cần API `/v1` | Đã có API |

Dự án kết hợp **cả hai**: Inertia cho web admin/reader; API JWT cho tích hợp lâu dài — không loại trừ nhau.

## 3.14. MySQL — chỉ mục và tối ưu (mở rộng)

Migration `add_performance_indexes_for_loans_and_items` thêm index phục vụ báo cáo phiếu quá hạn và join `loan_items`. Nguyên tắc: index các cột `WHERE`, `JOIN`, `ORDER BY` thường xuyên (`workflow_status`, `due_at`, `library_card_id`). Tránh `SELECT *` trên bảng lớn — API Resource chỉ trả cột cần thiết.

Sao lưu: khuyến nghị `mysqldump` định kỳ trên production; đồ án local dùng seed để tái tạo dữ liệu mẫu.

---

## 4.7. Mô tả chi tiết từng nhóm màn hình Admin (mở rộng)

**Quản lý người dùng (`Admin/Users/Index.vue`):** Bảng sortable, lọc theo khoa/role/trạng thái. Modal tạo/sửa user, gán role Spatie. Không hiển thị hash mật khẩu. Nút reset mật khẩu hoặc khóa tài khoản tùy policy.

**Yêu cầu cập nhật hồ sơ (`UpdateRequests.vue`):** Hàng đợi `user_profile_update_requests` — diff trường thay đổi, nút approve/reject, ghi lý do từ chối.

**Sách giáo trình / tham khảo:** Phân loại theo `resource_kind` — form nhập nhanh tác giả, NXB qua autocomplete API `master-data`.

**Kho và tủ (`Warehouses`, `StorageCabinets`):** Cây kho → tủ → ngăn; đồng bộ số lượng qua `storage:sync-quantities`.

**Duyệt nộp đồ án (`DigitalSubmissions.vue`):** Xem PDF upload, metadata, nút approve tạo biên mục hoặc reject.

Mỗi màn đều gọi API tương ứng với loading skeleton — tránh double-submit bằng disable nút khi `form.processing`.

## 4.8. Bảng mô tả thêm các thực thể CSDL (mở rộng)

### Bảng `loan_borrow_requests` / `loan_borrow_request_items`

Lưu yêu cầu mượn online trước khi thành phiếu chính thức. Trạng thái: pending, approved, rejected. Item giữ `book_id`, số lượng, ghi chú. Foreign key tới `library_card_id`.

### Bảng `loan_renewal_requests`

Sinh viên yêu cầu gia hạn; thủ thư duyệt; nếu approve — cập nhật `due_at` trên `loans` trong transaction.

### Bảng `notifications`

`recipient_type` (user/admin), `type`, payload JSON, `read_at`. API đánh dấu đã đọc hàng loạt.

### Bảng `news_posts` / attachments

Slug SEO-friendly, trạng thái draft/published, file đính kèm tin tức.

### Bảng `carts` / `cart_items`

`type` phân biệt giỏ mượn vs `digital_purchase`; item type `digital_asset_unlock` hoặc book borrow line.

## 4.9. Kế hoạch kiểm thử chi tiết (mở rộng)

**Giai đoạn 1 — Module Auth:** Đăng ký, OTP sai, login lock throttle, JWT hết hạn, refresh token.

**Giai đoạn 2 — Thẻ:** Workflow từng bước, guest-register, reject có lý do, thẻ active mới cho mượn.

**Giai đoạn 3 — Mượn:** Đủ điều kiện, vượt max_books, quá hạn, external không home borrow, duyệt/từ chối request.

**Giai đoạn 4 — Digital:** Preview giới hạn trang, thêm giỏ, tạo đơn, webhook giả, webhook đúng, tải PDF.

**Giai đoạn 5 — Regression:** Chạy full suite PHPUnit trước bàn giao.

**Tiêu chí đạt:** 0 test fail critical; manual checklist 30 case pass ≥ 90%.

## 4.10. Rủi ro triển khai và giảm thiểu (mở rộng)

| Rủi ro | Giảm thiểu |
|--------|-------------|
| Webhook SePay không tới | Log request, retry manual, đối soát API SePay |
| Disk đầy (PDF) | R2, giới hạn dung lượng upload, cron dọn file tạm |
| Queue không chạy | Supervisor, cảnh báo health |
| Lộ PDF luận văn | Paywall test tự động, audit download |
| SQL injection | Eloquent binding, forbid raw SQL không bind |

---

# CHƯƠNG 5. CÁC GIẢI PHÁP VÀ ĐÓNG GÓP NỔI BẬT

Chương này trình bày các giải pháp em chủ động thiết kế hoặc tích hợp nhằm giải quyết bài toán đặc thù của thư viện UTC — không chỉ dừng ở CRUD đơn giản.

## 5.1. Tập trung hóa logic mượn–trả trong LoanService

Thay vì rải điều kiện mượn trong controller, toàn bộ kiểm tra trước khi `createHomeBorrow` nằm trong **`App\Services\LoanService`**. Lợi ích:

- **Một nguồn sự thật** cho quy tắc UTC (thẻ, policy, quá hạn, phạt, bản sao).
- Dễ viết test unit/feature tái sử dụng.
- Khi quy định trường thay đổi (`loan_policies`), chỉ cập nhật service và seed policy.

Các phương thức tiêu biểu: `createHomeBorrow`, `createOnsiteLoan`, `processReturnBook`, `bulkProcessReturnBooks`, `calculateOverdueFine`. Mọi phương thức ghi dữ liệu đều bọc **`DB::transaction()`** — nếu cập nhật `book_copies` thành công mà tạo `loan_items` thất bại thì rollback toàn bộ, tránh “mất sách” trên hệ thống.

## 5.2. Workflow thẻ thư viện và khóa mượn tự động

Thẻ không chuyển thẳng từ “mới tạo” sang “dùng được” mà qua **`workflow_status`** (draft → pending_payment → pending_review → active). Phù hợp thực tế thủ tục có phí và duyệt hồ sơ.

**`LibraryCardOverdueLockService`** kết hợp cron `library-cards:sync-overdue-locks` tự động hạn chế mượn mới khi bạn đọc vi phạm quá hạn kéo dài — giảm gánh nặng nhắc nhở thủ công cho thủ thư.

Độc giả **external** được model hóa bằng `holder_type` — service mượn kiểm tra `allow_home` trên policy, đảm bảo **không checkout về nhà** trái quy định.

## 5.3. Paywall và bảo vệ tài liệu luận văn / đồ án

**`DigitalPaywallService`** và **`DigitalAssetPreviewService`** tách bạch:

- **Xem thử:** Giới hạn số trang preview (PNG render từ PDF qua Poppler hoặc pipeline DomPDF tùy môi trường).
- **Tải đầy đủ:** Chỉ khi miễn phí theo cấu hình, hoặc đã có `digital_asset_pdf_download_entitlements` sau thanh toán.

Metadata **`thesis_metadata`** gắn đầu sách loại luận văn/NC — hỗ trợ tra cứu có kiểm soát, không index công khai sai mức độ bảo mật.

## 5.4. Thanh toán SePay và xử lý bất đồng bộ

Giải pháp **`DigitalPaymentOrderService`**:

- Tạo mã đơn duy nhất (`publicId`) hiển thị nội dung chuyển khoản — giảm nhầm lẫn.
- Webhook xác thực chữ ký/secret — chống giả mạo request.
- **Idempotency:** webhook trùng không tạo double entitlement.
- Cron **`digital-orders:expire-pending`** giải phóng tài nguyên khi bạn đọc bỏ dở thanh toán.

Đây là đóng góp thực tiễn cho thư viện vừa **bán tài liệu số** vừa **kiểm soát quyền tải** — khác với chỉ đặt link Google Drive công khai.

## 5.5. Luồng nộp đồ án qua DigitalDocumentSubmissionService

Sinh viên nộp bài qua web; thủ thư duyệt trên admin. Khi approve, service có thể khởi tạo **`Book` + `DigitalAsset`** — chuẩn hóa biên mục thay vì file rời. Từ chối kèm lý do, lưu audit.

## 5.6. Trải nghiệm mobile và thông báo chủ động

- Tailwind responsive + touch target 44px theo rule dự án.
- **`LoanDueSoonNotificationService`** chạy cron buổi sáng — email/in-app nhắc trước hạn trả.
- **`StaffWorkQueueNotificationService`** báo thủ thư khi có hồ sơ chờ duyệt — rút ngắn thời gian chờ của bạn đọc.

## 5.7. Nhập xuất Excel phục vụ vận hành

Tích hợp **Maatwebsite Excel**:

- `BookImport` / template export — nhập khối lượng lớn đầu sách khi chuyển đổi số.
- `LibraryCardExport`, `LoanExport` — phục vụ báo cáo và đối soát.

Logic import nằm trong **Service** (sanitize ô, validate) — tránh controller phình to.

## 5.8. Bảo mật phân lớp API và admin

- JWT + `init` middleware gắn `CurrentUser`.
- Spatie roles: tách sinh viên và staff.
- Rate limit chống brute force OTP/login.
- Feature test **` * SecurityTest`** đảm bảo sinh viên không gọi được API staff.

## 5.9. Triển khai đa profile (DeployProfile)

Enum **`DeployProfile`** (`local`, `vps`, `infinityfree`) điều chỉnh hành vi preview PDF và disk — một codebase triển khai nhiều môi trường mà không fork dự án. Phù hợp đồ án vừa demo local vừa thử hosting giá rẻ.

---

# CHƯƠNG 6. KẾT LUẬN VÀ HƯỚNG PHÁT TRIỂN

## 6.1. Kết luận

### 6.1.1. Thuận lợi và khó khăn trong quá trình thực hiện

**Thuận lợi:** Đề tài gắn với dự án thực tế tại UTC; có tài liệu nghiệp vụ (`context-utc-library.md`); framework Laravel và Vue có tài liệu phong phú; cộng đồng package giải quyết nhanh auth, Excel, payment.

**Khó khăn:** Nghiệp vụ mượn đa đối tượng dễ nhầm lẫn khi mới tiếp cận; tích hợp SePay webhook đòi hỏi test kỹ edge case; preview PDF trên shared hosting bị giới hạn tài nguyên; đồng bộ giỏ mượn/mua giữa client và server cần thiết kế API rõ ràng.

### 6.1.2. Kết quả đạt được

**Về lý thuyết:** Em đã vận dụng quy trình phân tích–thiết kế–cài đặt–kiểm thử phần mềm; mô hình use case, ERD, kiến trúc layered; hiểu trade-off monolith vs API-first.

**Về thực hành:** Hoàn thiện **UTC eLibrary** với các module:

- Độc giả: tra cứu, thẻ, mượn, mua PDF, nộp đồ án, tin tức.  
- Admin: quản trị tập trung, duyệt, Excel, thống kê.  
- Kỹ thuật: Laravel 12 + Vue 3 + Inertia + MySQL + JWT + SePay + cron.

Sản phẩm chạy được trên môi trường phát triển; có bộ test Feature/Unit cho các luồng bảo mật và thanh toán.

### 6.1.3. Hạn chế

- Một số biểu đồ trong báo cáo ban đầu theo mẫu website khác — cần vẽ lại cho khớp hệ thống thư viện.  
- Chưa có ứng dụng di động chính thức; chưa tích hợp RFID/quét mã vạch tại quầy.  
- Báo cáo thống kê nâng cao (xu hướng mượn theo khoa, heatmap…) mới ở mức dashboard cơ bản.  
- Một số tham số phạt và hạn mượn trong `loan_policies` cần đồng bộ chính thức với quy định nhà trường (hiện có thể cấu hình nhưng cần xác nhận số liệu).

### 6.1.4. Bài học kinh nghiệm

- **Service layer** đáng đầu tư sớm — tiết kiệm thời gian sửa policy sau này.  
- **Test bảo mật** (không chỉ test happy path) phát hiện lỗi phân quyền sớm.  
- **Tách giỏ mượn và giỏ mua** trên UI giúp bạn đọc ít nhầm lẫn hơn gộp chung một “giỏ hàng” thương mại.

## 6.2. Hướng phát triển

1. **Ứng dụng di động** (Flutter/React Native) dùng chung API `/api/v1` đã có.  
2. **Liên kết hệ thống quản lý sinh viên** — đồng bộ mã sinh viên, khoa, lớp, tự động gia hạn thẻ theo niên khóa.  
3. **RFID / mã vạch ĐKCB** tại quầy — quét trả sách nhanh, giảm nhập tay.  
4. **Báo cáo BI** cho lãnh đạo thư viện — biểu đồ mượn theo thời gian, tỷ lệ quá hạn.  
5. **Gợi ý sách** dựa trên lịch sử mượn (machine learning đơn giản).  
6. **Tối ưu hiệu năng** — Redis cache cho tra cứu nóng, CDN R2 toàn diện.  
7. **Đa ngôn ngữ** (Việt/Anh) cho độc giả quốc tế nếu trường mở rộng.

---

## TÀI LIỆU THAM KHẢO

1. Nguyễn Văn A, *Phân tích thiết kế hệ thống thông tin*, NXB Đại học Quốc gia.  
2. Sommerville I., *Software Engineering*, Pearson — chương về kiến trúc web và kiểm thử.  
3. Laravel Documentation (2025). https://laravel.com/docs  
4. Vue.js Guide (2025). https://vuejs.org/guide/  
5. Inertia.js Documentation. https://inertiajs.com/  
6. Tailwind CSS Documentation. https://tailwindcss.com/docs  
7. Spatie — Laravel Permission. https://spatie.be/docs/laravel-permission  
8. PHP-Open-Source-Saver JWT Auth. https://github.com/PHP-Open-Source-Saver/jwt-auth  
9. Maatwebsite — Laravel Excel. https://docs.laravel-excel.com/  
10. Tài liệu nghiệp vụ UTC eLibrary: `docs/ai/context-utc-library.md`  
11. Tài liệu triển khai media R2: `docs/deployment/cloudflare-r2-media.md`  
12. Fowler M., *Patterns of Enterprise Application Architecture* — Service Layer, Repository.

---

## DANH MỤC THUẬT NGỮ VÀ TỪ VIẾT TẮT

| Thuật ngữ / từ viết tắt | Ý nghĩa |
|-------------------------|---------|
| API | Application Programming Interface — giao diện lập trình ứng dụng |
| CRUD | Create, Read, Update, Delete |
| ĐKCB | Điều kiện kỹ thuật bản sao (book copy) |
| ERD | Entity Relationship Diagram — sơ đồ thực thể liên kết |
| JWT | JSON Web Token — chuẩn token xác thực API |
| MVC | Model – View – Controller |
| OPAC | Online Public Access Catalog — hệ thống tra cứu công khai |
| ORM | Object-Relational Mapping — Eloquent trong Laravel |
| Paywall | Cơ chế giới hạn truy cập nội dung số |
| RBAC | Role-Based Access Control — phân quyền theo vai trò |
| REST | Representational State Transfer — kiểu API HTTP |
| SePay | Cổng thanh toán tích hợp tại Việt Nam |
| SSO | Single Sign-On — đăng nhập một lần (Azure AD) |
| UTC | Trường Đại học Giao thông Vận tải |
| UX/UI | Trải nghiệm / giao diện người dùng |
| XHR | XMLHttpRequest — request HTTP từ trình duyệt |

---

## DANH MỤC HÌNH ẢNH (mẫu — cập nhật số hình khi chèn Word)

| Hình | Tên |
|------|-----|
| Hình 2.1 | Use case tổng quát UTC eLibrary |
| Hình 2.2 | Use case đăng ký, đăng nhập |
| Hình 2.3–2.9 | Use case phân rã bạn đọc |
| Hình 2.10–2.17 | Use case phân rã quản trị |
| Hình 2.19–2.26 | Biểu đồ tuần tự |
| Hình 4.1 | Kiến trúc hệ thống |
| Hình 4.2–4.15 | Giao diện độc giả |
| Hình 4.16–4.25 | Giao diện quản trị |
| Hình 4.26 | Sơ đồ ERD |
| Hình 4.27 | Cây thư mục dự án |

---

## DANH MỤC BẢNG BIỂU (mẫu)

| Bảng | Tên |
|------|-----|
| Bảng 2.1 | Đặc tả tra cứu sách |
| Bảng 2.2 | Đặc tả gửi yêu cầu mượn |
| Bảng 2.3 | Đặc tả duyệt mượn |
| Bảng 2.4 | Đặc tả thanh toán SePay |
| Bảng 2.5 | Đặc tả duyệt thẻ |
| Bảng 2.6 | Đặc tả nộp đồ án |
| Bảng 4.1 | Công cụ phát triển |
| Bảng 4.2–4.4 | Test case |
| Bảng 4.x | Mô tả các bảng CSDL (users, library_cards, books, loans, orders) |

---

## PHỤ LỤC D — MÔ TẢ CHI TIẾT CÁC TRANG ĐỘC GIẢ (VĂN BẢN MỞ RỘNG)

Phần này mô tả kỹ từng khu vực giao diện để khi chuyển sang Word có thể chèn hình và diễn giải thành 10–15 trang riêng.

### D.1. Trang chủ (`Reader/Home.vue`)

Trang chủ đóng vai trò **điểm neo** của toàn hệ thống. Phần hero giới thiệu sứ mệnh thư viện số UTC — không chỉ là kho sách mà là cổng tri thức phục vụ đào tạo và nghiên cứu giao thông vận tải. Ngay dưới hero là ô tìm kiếm nhanh dẫn tới `/tra-cuu-sach` với query sẵn, giảm một bước cho người đã biết tên sách. Các **ô dịch vụ** (icon + nhãn) liên kết tới: Tra cứu, Làm thẻ, Quy định mượn, Tin tức, Đăng nhập/Tài khoản. Khu vực **tin tức mới** kéo dữ liệu từ API `news-posts/public` — hiển thị tiêu đề, ngày, excerpt; click sang `/tin-tuc/{slug}`. Khu **sách mới** hoặc **đề xuất** (nếu cấu hình) giúp bạn đọc khám phá tài liệu vừa nhập kho. Footer chứa thông tin liên hệ thư viện, link quy định, bản quyền. Trên mobile, menu hamburger gom điều hướng; header cố định với `safe-area-inset` tránh che notch.

### D.2. Nhóm trang quy định (`/quy-dinh/*`)

**Trang mục lục quy định** liệt kê ba mục chính: Thủ tục làm thẻ, Lịch phục vụ, Quy định mượn sách — mỗi mục một route riêng để URL có thể chia sẻ trực tiếp trong thông báo của trường.

**Thủ tục làm thẻ** trình bày hồ sơ cần nộp (ảnh, CMND/CCCD, thông tin liên hệ), phí (nếu có), thời gian xử lý và nút CTA “Bắt đầu làm thẻ” dẫn tới `/dich-vu/cap-the-thu-vien` hoặc đăng nhập trước.

**Lịch phục vụ** có bảng giờ mở cửa theo ngày trong tuần, ghi chú ngày lễ — có thể lấy từ `site_contents` hoặc nội dung tĩnh do thủ thư cập nhật.

**Quy định mượn sách** giải thích số cuốn tối đa, thời hạn, phạt quá hạn, điều kiện gia hạn — **bám policy** trong `loan_policies` để tránh mâu thuẫn giữa văn bản và phần mềm.

### D.3. Tra cứu và chi tiết sách

**Catalog** hỗ trợ tìm kiếm full-text hoặc theo trường (tùy triển khai BookService): tiêu đề, ISBN, tác giả. Bộ lọc sidebar (desktop) hoặc drawer (mobile): phân loại, loại tài liệu, năm xuất bản. Kết quả dạng card: ảnh bìa placeholder nếu thiếu ảnh, badge “Còn N bản” hoặc “Chỉ đọc tại chỗ”. Phân trang Laravel/Inertia giữ query string khi chuyển trang.

**BookShow** là trang quyết định mượn/mua. Hiển thị: nhan đề, tác giả (link tìm theo tác giả), NXB, năm, mô tả, phân loại, ký hiệu xếp giá. Tab hoặc section **Bản sao** liệt kê ĐKCB còn available. Section **Tài liệu điện tử** liệt kê file PDF: nút “Xem thử”, giá (nếu paywall), nút “Thêm vào giỏ mua”. Nút **Thêm vào giỏ mượn** chỉ enable khi user đã đăng nhập + thẻ active + còn bản sao — tooltip giải thích khi disabled.

### D.4. Dịch vụ cá nhân sau đăng nhập

**Cấp thẻ** (`servicesLibraryCard`): form nhiều bước (wizard) — bước 1 thông tin cá nhân, bước 2 upload ảnh, bước 3 xác nhận, bước 4 theo dõi trạng thái workflow. Thanh tiến trình hiển thị `pending_review`, v.v.

**Giỏ sách** (`BookCart`): hai tab rõ ràng — **Mượn sách giấy** và **Mua tài liệu số**. Mỗi dòng: ảnh, tên, nút xóa, ghi chú. Tổng tiền tab mua. Nút “Gửi yêu cầu mượn” / “Thanh toán”.

**Thanh toán** (`digital-payment`): hiển thị QR SePay, số tiền, nội dung chuyển khoản, đồng hồ đếm ngược hết hạn đơn (sync cron). Trạng thái polling hoặc thông báo push khi webhook xử lý xong.

**Phiếu mượn** (`Loans/Index`, `Show`): bảng có badge màu theo trạng thái (đang mượn, quá hạn). Chi tiết phiếu: timeline ngày mượn, hạn, phạt dự kiến.

**Đơn hàng của tôi** (`Orders/Index`): lịch sử mua PDF — mã đơn, ngày, trạng thái paid/expired, link tải lại nếu còn entitlement.

**Tài khoản** (`Profile`): form sửa thông tin; cảnh báo khi thay đổi cần duyệt; link lịch sử yêu cầu cập nhật.

### D.5. Tin tức và xác thực

**News index/show:** layout đọc thoải mái, typography rõ, ảnh đại diện bài viết.

**Auth:** Login có link forgot password và Microsoft. Register dẫn OTP. Verify OTP có resend với countdown throttle.

---

## PHỤ LỤC E — MÔ TẢ CHI TIẾT KHU VỰC QUẢN TRỊ (MỞ RỘNG)

### E.1. Dashboard

Tổng hợp số liệu real-time hoặc cache ngắn hạn từ `StatisticsService`: tổng đầu sách, bản sao, thẻ active, phiếu đang mượn, số quá hạn, đơn digital pending, submission chờ duyệt. Giúp thủ trưởng thư viện nắm tình hình trong 30 giây. Có thể có shortcut tới hàng đợi duyệt (badge số lượng pending).

### E.2. Quản lý sách và tài liệu số

Phân nhánh **in ấn** (giáo trình, tham khảo) và **digital** — form nhập metadata đầy đủ, upload bìa, gán tác giả/NXB qua multi-select. **Bản sao:** nhập ĐKCB, gán tủ/kho. **Digital asset:** upload PDF, cấu hình paywall (miễn phí / trả phí / số trang preview). **Submissions:** queue duyệt đồ án với preview PDF inline.

### E.3. Mượn trả và yêu cầu

**Borrow requests:** bảng lọc pending — approve gọi LoanService, reject nhập lý do gửi notification. **Loans list:** lọc theo trạng thái, export Excel. **Create loan tại quầy:** chọn thẻ, quét ĐKCB, xác nhận. **Return:** giao diện trả hàng loạt hoặc từng cuốn. **Renewal requests:** tương tự borrow.

### E.4. Thẻ và người dùng

**Library cards:** tìm theo số thẻ, tên, trạng thái workflow. **Counter mode:** tối ưu thao tác tác nhanh tại quầy (màn hình lớn). **Users:** không xóa cứng nếu có lịch sử mượn — soft delete hoặc khóa.

### E.5. Cấu hình hệ thống

**Loan policies:** một dòng mỗi `user_type` hoặc holder — sửa max_books, max_days, fine. **Library settings & pricing:** key-value cấu hình chung, bảng giá từng loại digital resource. **Classifications, warehouses:** danh mục dùng chung khi nhập sách.

---

## PHỤ LỤC F — DANH SÁCH SERVICE VÀ TRÁCH NHIỆM (THAM CHIẾU MÃ NGUỒN)

| Service | Trách nhiệm chính |
|---------|-------------------|
| `AuthService` | Đăng nhập, đăng ký, token |
| `OtpService` | Sinh và xác thực OTP email |
| `BookService` | CRUD sách, import/export, tra cứu |
| `LibraryCardService` | Workflow thẻ cơ bản |
| `LibraryCardManagementService` | Staff duyệt, cấp thẻ |
| `LibraryCardGuestService` | Khách không tài khoản |
| `LibraryCardAccountService` | Thẻ gắn user đăng nhập |
| `LibraryCardOverdueLockService` | Khóa mượn khi quá hạn |
| `LoanService` | Tạo/trả phiếu, phạt, kiểm tra điều kiện |
| `LoanBorrowRequestService` | Yêu cầu mượn online |
| `LoanRenewalRequestService` | Gia hạn |
| `LoanPoliciesService` | Đọc cấu hình policy |
| `DigitalAssetService` | Quản lý file số |
| `DigitalPaywallService` | Kiểm tra quyền xem/tải |
| `DigitalAssetPreviewService` | Tạo preview PNG |
| `DigitalPurchaseCartService` | Giỏ mua |
| `DigitalPaymentOrderService` | Đơn SePay, webhook |
| `DigitalDocumentSubmissionService` | Nộp và duyệt đồ án |
| `NotificationService` | Thông báo in-app |
| `LoanDueSoonNotificationService` | Nhắc hạn trả |
| `StatisticsService` | Dashboard số liệu |
| `NewsPostService` | Tin tức |
| `WarehouseService`, `StorageCabinetService` | Kho và tủ |
| `UserService`, `ProfileService` | Người dùng và hồ sơ |
| `UserProfileUpdateRequestService` | Duyệt sửa hồ sơ |

Mô hình này minh họa **Separation of Concerns** — khi viết báo cáo có thể chèn sơ đồ lớp (layer diagram) từ Controller xuống Service xuống Model.

---

## PHỤ LỤC G — ĐẶC TẢ BỔ SUNG (BẢNG 2.7 – 2.12)

### Bảng 2.7. Đặc tả đăng ký tài khoản

| Hạng mục | Nội dung |
|----------|----------|
| Tên | Đăng ký tài khoản bạn đọc |
| Tác nhân | Khách truy cập |
| Luồng chính | Điền form → nhận OTP → verify → tạo user |
| Ngoại lệ | Email trùng, OTP sai 3 lần, hết hạn OTP |

### Bảng 2.8. Đặc tả đăng nhập Microsoft

| Hạng mục | Nội dung |
|----------|----------|
| Tên | SSO Azure AD |
| Luồng | Redirect OAuth → callback map user → session |

### Bảng 2.9. Đặc tả xem thử PDF

| Hạng mục | Nội dung |
|----------|----------|
| Tên | Xem thử tài liệu số |
| Tiền điều kiện | Sách có digital asset + paywall cho phép preview |
| Luồng | Route preview → render trang PNG giới hạn |

### Bảng 2.10. Đặc tả yêu cầu gia hạn

| Hạng mục | Nội dung |
|----------|----------|
| Tác nhân | Bạn đọc, Thủ thư |
| Luồng | POST renewal → duyệt → cập nhật due_at |

### Bảng 2.11. Đặc tả import sách Excel

| Hạng mục | Nội dung |
|----------|----------|
| Tác nhân | Thủ thư |
| Luồng | Upload file → validate template → BookImport → báo cáo dòng lỗi |

### Bảng 2.12. Đặc tả đồng bộ quá hạn (cron)

| Hạng mục | Nội dung |
|----------|----------|
| Tác nhân | Hệ thống |
| Luồng | `loans:sync-overdue` đánh dấu phiếu quá hạn, có thể kết hợp khóa thẻ |

---

## PHỤ LỤC H — CÂU HỎI THƯỜNG GẶP KHI BẢO VỆ ĐỒ ÁN (GỢI Ý TRẢ LỜI)

**Vì sao chọn Laravel + Vue mà không dùng WordPress?**  
WordPress phù hợp CMS đơn giản; đồ án có nghiệp vụ mượn–trả, transaction, paywall — cần framework cho phép model hóa quan hệ phức tạp và test tự động. Laravel phù hợp hơn.

**Vì sao cần cả Inertia và API JWT?**  
Inertia tối ưu thời gian phát triển web; JWT API sẵn sàng cho app mobile và tách frontend sau này — không lãng phí nếu chỉ coi là đầu tư mở rộng.

**Làm sao đảm bảo người ngoài không mượn về nhà?**  
Kết hợp `holder_type`, `loan_policies.allow_home`, và kiểm tra trong `LoanService` trước `createHomeBorrow` — có test Feature chứng minh.

**SePay lỗi webhook thì sao?**  
Đơn ở pending; cron expire; thủ thư đối soát thủ công qua `SepayTransactionApiService`; user có thể tạo đơn mới.

**Khác biệt so với phần mềm thư viện thương mại?**  
Tùy biến 100% theo UTC; tích hợp SePay và nộp đồ án trên cùng portal; mã nguồn mở nội bộ trường.

---

## PHỤ LỤC A — HƯỚNG DẪN CÀI ĐẶT NHANH (LOCAL)

```bash
# Clone và cài dependency
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

npm install
npm run build   # hoặc npm run dev

# Chạy (một lệnh nếu có composer dev)
composer dev
```

Truy cập: `http://localhost:8000` — tài khoản seed xem `database/seeders`.

## PHỤ LỤC B — MỘT SỐ API TIÊU BIỂU

| Method | Endpoint | Mô tả |
|--------|----------|--------|
| POST | `/api/v1/auth/login` | Đăng nhập JWT |
| GET | `/api/v1/me/profile` | Hồ sơ |
| POST | `/api/v1/me/library-card` | Đăng ký thẻ |
| GET | `/api/v1/me/loans` | Danh sách phiếu mượn |
| POST | `/api/v1/me/loan-borrow-requests` | Gửi yêu cầu mượn |
| POST | `/api/v1/me/digital-payment-orders` | Tạo đơn SePay |
| POST | `/api/v1/sepay/webhook` | Webhook thanh toán |

## PHỤ LỤC C — ƯỚC LƯỢNG ĐỘ DÀI BÁO CÁO

Khi dán vào Word (Times New Roman 13, giãn dòng 1,5, lề trên/dưới 1.27 cm, lề trái 3.5 cm, lề phải 2 cm — theo format thường dùng), toàn văn bản này ước đạt **khoảng 38–48 trang chỉ chữ và bảng**. Để đủ **~60 trang** như yêu cầu, nên bổ sung **18–25 hình** (use case, ERD, sequence, chụp màn hình Reader/Admin) — mỗi hình chiếm khoảng 0,5–1 trang kèm chú thích. Phụ lục D–H có thể tách thành mục riêng trong Word nếu cần dày hơn.

---

*Tài liệu: `docs/do-an-utc-elibrary-noi-dung.md` — đồng bộ với mã nguồn UTC-eLibrary, sinh viên Vũ Tuấn Kiệt, 2026.*
