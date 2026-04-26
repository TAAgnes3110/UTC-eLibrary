# Nghiệp vụ & chuẩn UTC — ĐH Giao thông Vận tải (eLibrary)

Mục lục tài liệu: [docs/README.md](../README.md). Rule kỹ thuật: [`.cursor/rules/utc-elibrary-core.mdc`](../../.cursor/rules/utc-elibrary-core.mdc).

## Đối tượng & thẻ

- **Mượn về nhà:** Chỉ **sinh viên, giảng viên, cán bộ có thẻ UTC hợp lệ** (đúng loại thẻ / workflow / hạn) mới được checkout tài liệu ra khỏi thư viện.
- **Người ngoài / đối tượng không đủ điều kiện:** Chỉ **đọc tại chỗ** (onsite). **Không** cho mượn về nhà — kiểm tra `holder_type`, loại thẻ, `workflow_status` / `allow_home` theo `loan_policies` và quy định nội bộ.

## Phân loại & tài liệu nội sinh

- **Ký hiệu xếp giá (Call number):** Tuân thủ hệ thống phân loại UTC; mã gắn với `classification`, `warehouse`, `cabinet`, `book_code` / ĐKCB (xem `BookService`).
- **Luận văn / đồ án / NC:** Chế độ **bảo mật cao** — `resource_kind`, `access_mode`, `digital_assets`, `thesis_metadata` theo migration; không lộ file/metadata ngoài quyền.

## Quy tắc mượn (trước khi cho mượn mới)

Logic tập trung tại **`App\Services\LoanService`** (`createHomeBorrow`, `returnHomeLoan`, `calculateOverdueFine`, …). Khi mở API mượn/trả, chỉ gọi service từ controller + `ApiResponse`.

Service phải kiểm tra **tuần tự / đủ điều kiện**:

1. Thẻ còn hạn, trạng thái được phép mượn (`library_cards` + policy).
2. Chưa vượt **số đầu sách / bản sao** đang mượn (theo `loan_policies.max_books`, …).
3. Không có **khoản mượn quá hạn** chưa xử lý.
4. Không có **nợ phạt** chưa thanh toán / chưa ghi nhận (nếu có module phạt).
5. Bản sao (`book_copies`) khả dụng; tài liệu không thuộc diện chỉ đọc tại chỗ với đối tượng này.

Tham chiếu số: bảng `loan_policies`, model `App\Models\LoanPolicy` (không nhầm Laravel `Policy`).

## Thẻ thư viện (workflow)

- Một nguồn `library_cards` — [library-card-flow-and-api.md](../library-card-flow-and-api.md).

## Số liệu cấu hình (điền khi có quyết định chính thức)

- Mượn tối đa: …
- Hạn mượn / gia hạn: …
- Công thức phạt quá hạn (VNĐ/ngày hoặc theo policy): …
