# Helpers

Các class tiện ích dùng chung trong app.

## Chuẩn comment (PHPDoc) dùng trong dự án

- **Class:** Mô tả ngắn chức năng. Có thể thêm `@todo` cho việc cần làm sau.
- **Method (public/private):**
  - Một dòng mô tả ngắn.
  - `@param Type $tên` cho từng tham số (mô tả ngắn nếu cần).
  - `@return Type` mô tả giá trị trả về (hoặc void).
  - `@throws` nếu method ném exception.
  - `@todo` nếu có việc cần làm cho method đó.

Ví dụ:

```php
/**
 * Controller quản lý X.
 *
 * @todo Bổ sung filter theo Y.
 */
class XController
{
    /**
     * Lấy danh sách X có phân trang.
     *
     * @param Request $request Query: keyword (optional).
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
``` Chỉ thêm helper mới khi logic **lặp lại nhiều nơi** hoặc **cần một chuẩn chung** (API, file, user).

## Hiện có

| File | Mục đích |
|------|----------|
| **CurrentUser** | DTO + phân quyền user đăng nhập: đọc từ `users.user_type`, lazy-load Spatie roles/permissions. Dùng trong middleware, controller. |
| **FileHelpers** | Excel: đọc/ghi, import result, parse ngày/số, getValueByAliases. Dùng trong Import và controller upload. |
| **Helpers** | `generateRandomNumber` (OTP), `ArrMerge` (merge mảng không trùng). Dùng trong OtpService, BasePeriodModel. |
| **ApiResponse** | Chuẩn hóa JSON API: `status` + `messages` + `data`. Tránh lệch key (message vs messages) giữa các controller. |

## Khi nào nên thêm helper mới?

- **Logic lặp ≥ 2–3 chỗ** và không thuộc một model rõ ràng → cân nhắc helper (hoặc Service nếu phức tạp).
- **Cần một chuẩn chung** (format response, format ngày, rule validation dùng lại) → helper hoặc Rule object.
- **Chỉ dùng 1 nơi** hoặc logic gắn chặt với một model → giữ trong model/controller/service, không tách helper.

## Không nên tách thành helper

- Logic nghiệp vụ phức tạp (mượn/trả, tính phạt) → nên đặt trong **Service** (vd. `LoanService`, `FineService`).
- Validation rule đơn giản → dùng **Form Request** hoặc **Rule**.
- Format ngày đơn lẻ → dùng Carbon/format trong chỗ dùng, hoặc accessor trên model.
