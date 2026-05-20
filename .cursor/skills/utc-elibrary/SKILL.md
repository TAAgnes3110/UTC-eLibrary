---
name: utc-elibrary
description: Ngữ cảnh và quy trình UTC eLibrary (Laravel + Vue/Inertia). Dùng trước khi sửa mượn/trả, thẻ, tài liệu số, thanh toán.
origin: UTC-eLibrary
---

# UTC eLibrary — project context

## Khi nào dùng

- Task chạm `LoanService`, `library_cards`, `loan_policies`, digital assets, SePay
- Thêm/sửa API reader hoặc admin thư viện
- Không chắc quy định UTC (người ngoài, transaction, N+1)

## Search-first (trong repo)

1. Đọc `docs/ai/context-utc-library.md`
2. Grep `app/Services/` theo từ khóa nghiệp vụ (loan, card, digital, fine…)
3. Đối chiếu model + migration liên quan
4. Feature test: `tests/Feature/Backend/`

## Ràng buộc nhanh

- Người ngoài: **không** checkout về nhà
- Ghi mượn/trả/phạt: `DB::transaction()`
- POST/PUT/PATCH: FormRequest
- UI reader: mobile-friendly (44px, bảng cuộn)
- Trả lời user bằng **tiếng Việt**

## Skills ECC liên quan

- `search-first`, `laravel-patterns`, `laravel-tdd`, `laravel-verification`, `laravel-security`
- `e2e-testing` (Playwright), `frontend-patterns`, `ui-to-vue`
