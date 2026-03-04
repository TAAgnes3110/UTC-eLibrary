# UTC eLibrary API – Postman

## Request chuẩn (bảo mật: token + domain + period)

Mọi request API (trừ Health, Login, Register, … không cần auth) cần:

### 1. Authorization – Bearer token

- Tab **Authorization** → Type: **Bearer Token** → Token: `{{token}}`.
- Lấy token: gọi **Auth → Login**, copy `token` trong response vào biến collection `token`.

### 2. Headers

| Key           | Value             | Ghi chú                          |
|---------------|-------------------|----------------------------------|
| `Accept`      | `application/json`| Response JSON                    |
| `Content-Type`| `application/json`| Body JSON (POST/PUT)             |
| `domain`      | `{{domain}}`      | Origin (vd: `https://your-ngrok.ngrok-free.dev`) |
| `period`      | `{{period}}`      | Năm học (vd: `2025-2026`)        |

- Có thể cấu hình **Collection → Headers** thêm 2 dòng `domain` và `period` để áp dụng cho mọi request.

### 3. Biến collection

- `base_url`: root API (vd: `https://succinic-unshaped-nery.ngrok-free.dev/api`).
- `token`: JWT sau khi login.
- `domain`: giống origin (vd: `https://succinic-unshaped-nery.ngrok-free.dev`).
- `period`: năm học (vd: `2025-2026`).

---

**Tóm tắt:** API viết theo chuẩn: **Auth = Bearer token**; **Headers = Accept, Content-Type, domain, period**. Cấu hình đúng token + domain + period thì request hợp lệ.
