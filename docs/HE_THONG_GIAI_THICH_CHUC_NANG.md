# UTC-eLibrary - Tai lieu giai thich chuc nang va kien truc

Tai lieu nay mo ta "he thong dang hoat dong nhu the nao", "tai sao lai thiet ke nhu vay", va "header/request-response co dung chung khong" dua tren code hien tai cua du an.

## 1) Tong quan nhanh

- Nen tang: Laravel 12 + Vue 3 + Inertia.
- Hinh thuc app: hybrid SSR + SPA.
  - Route web render page bang Inertia.
  - Trong page, FE goi API `/api/v1` bang Axios de lay/ghi du lieu.
- Xac thuc ket hop:
  - Session web (de dung route Inertia, middleware `auth`).
  - JWT Bearer (de goi API linh hoat, refresh token khi 401).

Muc tieu cua cach lam nay:
- Giup dieu huong web on dinh, SEO/trang public de lam.
- Van co trai nghiem tuong tac nhanh o phan quan tri.
- Tach ro "render page" va "xu ly nghiep vu/du lieu".

## 2) Nhom chuc nang va tac dung

### 2.1 Reader (doc gia)

Tac dung:
- Cung cap trang cong khai (home, gioi thieu, quy dinh, tra cuu).
- Cung cap dich vu ca nhan (the thu vien, sach da luu, phieu muon cua toi).
- Quan ly tai khoan (ho so, doi mat khau, yeu cau cap nhat).

Luot hoat dong:
- Web route -> Frontend Reader controller -> `Inertia::render(...)`.
- Page Reader tiep tuc goi API khi can thao tac data (luu sach, muon, cap nhat...).

Tai sao dung cach nay:
- Trang reader co nhieu noi dung "page-like", dieu huong Inertia de kiem soat route va auth de hon.
- Du lieu dong thi lay qua API de tai su dung cho mobile/app/ben thu 3 sau nay.

### 2.2 Admin/Librarian

Tac dung:
- Quan tri users, books, warehouses, library cards, loans.
- Dashboard, thong ke, import/export.
- Duyet cac quy trinh nghiep vu (the, ho so, gia han, tra sach...).

Luot hoat dong:
- Web route `/admin/*` render shell Inertia.
- FE goi API role-protected (`init` + `role_or_permission`) de thao tac.

Tai sao dung cach nay:
- Luong quan tri can tuong tac nhieu, phan trang, loc, import/export -> giao dien "app-style" phu hop.
- Giao tiep API giup dong bo logic nghiep vu tai backend, FE gon hon.

## 3) Cac luong nghiep vu quan trong

### 3.1 Dang nhap va xac thuc

Hoat dong:
- FE goi `/login` (web) de tao session va lay token JWT.
- Axios API tu dong gan `Authorization: Bearer <token>` neu co token trong `localStorage`.
- Khi API 401, FE thu refresh qua `/api/v1/auth/refresh`, sau do retry request.

Tai sao:
- Session phuc vu middleware web + Inertia.
- JWT phuc vu API stateless, de phat trien them client khac.
- Refresh interceptor giup UX muot, giam logout dot ngot.

### 3.2 Muon/tra sach

Hoat dong:
- Service layer (`LoanService`, `LoanHelper`) xu ly chinh.
- Moi buoc ghi quan trong boi `DB::transaction()`.
- Dung `lockForUpdate()` tren ban ghi de tranh race-condition.
- Kiem tra policy truoc khi muon (loai the, quota, loai tai lieu, ton kho).
- Tra sach tinh phat (qua han/hu/hong/mat) + cap nhat ton kho.

Tai sao:
- Bai toan thu vien co tinh toan dong thoi (2 nguoi muon cung dau sach).
- Transaction + lock giu du lieu nhat quan va rollback neu loi.
- Tach helper/service de test va mo rong cong thuc phat.

### 3.3 The thu vien va chinh sach muon

Hoat dong:
- Chinh sach muon theo holder type (student/teacher/external) qua `LoanPoliciesService`.
- Co cache (`Cache::remember`) cho limits/permissions/policy theo holder type.
- Cap nhat policy thi xoa cache lien quan.

Tai sao:
- Chinh sach duoc doc rat nhieu, thay doi it -> cache de giam tai DB.
- Gom logic policy vao mot service de tranh duplicate trong controller.

## 4) Tai sao backend to chuc theo Service layer

Mau hien tai:
- Controller: nhan request, validate, goi service, tra `ApiResponse`.
- Service: chua logic nghiep vu.
- Resource: chuan hoa du lieu tra ve FE.
- FormRequest/BaseRequest: chuan hoa validate + loi 422.

Ly do:
- Controller gon, de doc.
- Logic nghiep vu co the tai su dung giua API/Command.
- De viet test theo use-case.
- Giam risk khi doi UI vi luat nghiep vu khong nam trong Vue.

## 5) Tai sao frontend "nhu hien tai"

Frontend duoc chia theo 3 lop:
- `Layouts`: khung dung chung (`ReaderLayout`, `AdminLayout`, `AuthLayout`).
- `Pages`: moi man hinh.
- `api/* + composables/*`: goi API, quan ly state man hinh.

Ly do thiet ke:
- Inertia giup route server-side ro rang, khong phai tu tuyen router phuc tap.
- Composable va API module giup code tai su dung, de test, de thay doi endpoint.
- Layout tach rieng giup consistency UI/UX.

Ghi chu thuc te:
- Notification dropdown trong header hien tai dang la du lieu local hardcoded (mock), chua noi voi API realtime.
- UI uu tien mobile/touch target (44px+, safe-area), phu hop ngu canh app su dung tren dien thoai.

## 6) Header co dung chung khong?

Co. He thong dang co "bo header mac dinh dung chung" cho API.

### 6.1 Header request dung chung (API client)

Trong `resources/js/api/axios.js`:
- `X-Requested-With: XMLHttpRequest`
- `Content-Type: application/json`
- `Accept: application/json`
- `Authorization: Bearer ...` (tu dong neu co token)
- `domain: window.location.origin` (neu FE chua tu dat)

Y nghia:
- `Authorization`: xac thuc JWT.
- `domain`: duoc middleware `Init` dung de doi chieu `API_ALLOWED_DOMAINS`.
- `Accept/Content-Type`: thong nhat kieu payload JSON.

Ngoai le FormData:
- Neu request gui `FormData`, client tu xoa `Content-Type` de browser tu set boundary.

### 6.2 Header cho web login (session + CSRF)

Trong `resources/js/api/webSessionLogin.js`:
- Them `X-XSRF-TOKEN` hoac `X-CSRF-TOKEN` (lay tu cookie/meta).
- Van gui `X-Requested-With`, `Accept`, `Content-Type`.
- `withCredentials: true` de dam bao cookie session.

Y nghia:
- Login web can CSRF/session, khac voi API thuần JWT.

### 6.3 Response co dung chung khong?

Phan lon API dung format:
- Thanh cong: `{ status: "success", messages?, data? }`
- Loi: `{ status: "error", messages, data?/errors? }`

`ApiResponse` la helper trung tam de giu format thong nhat.

## 7) Cac middleware/chot an toan chinh

- `init`: xac thuc user (JWT hoac web guard), nap current context, check domain allowlist.
- `role_or_permission`: chan theo vai tro/quyen.
- `throttle`: gioi han tan suat (`api`, `auth`, `refresh`).
- Exception renderer trong `bootstrap/app.php`:
  - Chuyen Authentication/Authorization/QueryException thanh JSON an toan cho API.

Tai sao:
- Bao mat theo nhieu lop.
- Tra loi API nhat quan, khong lo stack trace lo ra FE.

## 8) Vi sao can xoa `public/hot` khi deploy production

Neu con `public/hot`, Laravel se coi nhu dang chay Vite dev server:
- FE script se tro ve `http://[::1]:5173/...`.
- Production domain khac origin -> CORS + ERR_FAILED.

Vi vay:
- Production phai dung `public/build` + `manifest.json`.
- Khong de `public/hot` trong goi deploy.

## 9) Huong dan mo rong chuc nang dung huong hien tai

- Them endpoint moi:
  1) Tao FormRequest (neu thay doi state)
  2) Tao/bo sung Service method
  3) Controller chi dieu huong + tra `ApiResponse`
  4) FE them module `resources/js/api/*.js` + composable
- Neu chuc nang co ghi du lieu lien quan ton kho/loan/card:
  - Bat buoc transaction va lock ban ghi quan trong.
- Neu du lieu tra cuu doc nhieu:
  - Can nhac cache + co co che forget cache ro rang khi update.

## 10) Ket luan

He thong hien tai chon huong "service-first + Inertia shell + API module" de can bang:
- Toc do phat trien giao dien.
- Do an toan nghiep vu backend.
- Kha nang mo rong ve sau (them client, them module, them quy tac nghiep vu).

Phan "header dung chung" da co bo quy uoc ro trong API client; can tiep tuc giu dong bo voi middleware `init` va `ApiResponse` de tranh loi kho debug giua FE/BE.

## 11) Chi tiet tung chuc nang - Module Quan ly nguoi dung

Muc nay di vao dung y ban: moi thao tac (them, sua, xuat excel, xoa...) dang chay ra sao va vi sao lai lam nhu vay.

### 11.1 Xem danh sach nguoi dung

Luot chay:
1) FE goi `GET /api/v1/users` voi bo loc (`keyword`, `search_in`, `type=reader`, `per_page`).
2) `UserController@index` validate tham so, parse `search_in`.
3) `UserService@index` dung query Eloquent:
   - eager load `faculty`, `department`, `period` de tranh N+1,
   - loc theo reader type neu can,
   - tim theo cac cot duoc cho phep,
   - phan trang va giu query string.
4) Ket qua tra ve theo `UserResource` + `ApiResponse::success`.

Tai sao:
- Parse `search_in` giup tim kiem linh hoat ma van an toan (chi cho cot hop le).
- Eager loading giu toc do on dinh khi danh sach lon.
- Pagination de khong tai qua nhieu ban ghi mot lan.

### 11.2 Xem chi tiet 1 nguoi dung

Luot chay:
1) FE goi `GET /api/v1/users/{user}`.
2) Controller `show()` load them cac quan he: the thu vien, khoa, bo mon, nien khoa, thong tin nguoi thao tac.
3) Tra ve `UserResource`.

Tai sao:
- Trang detail can du lieu tong hop; load mot lan de FE khong phai goi nhieu request.
- Dung resource de du lieu tra ve dong nhat giua man hinh.

### 11.3 Them moi nguoi dung

Luot chay:
1) FE submit form -> `POST /api/v1/users`.
2) `UserRequest` validate du lieu tao moi.
3) `UserService::create()` tao ban ghi.
4) Controller tra 201 + message thanh cong.

Tai sao:
- Validate truoc tai FormRequest de backend khong nhan data sai.
- Service layer giu cho controller gon, de sau nay co them logic (gui mail, gan role...) cung de mo rong.

### 11.4 Chinh sua thong tin nguoi dung

Luot chay:
1) FE submit -> `PUT /api/v1/users/{id}`.
2) Controller tim user, not found thi 404.
3) `UserService::update()` chu dong bo qua cac truong nhay cam/khong cho sua:
   - `id`, `code`, `created_at`, `updated_at`, `email_verified_at`.
4) Neu password rong thi bo qua, khong ghi de.

Tai sao:
- Khong cho sua cac field he thong de giu toan ven du lieu.
- Khong ghi de password rong de tranh vo tinh khoa account.

### 11.5 Xoa mem (soft delete)

Luot chay:
1) FE goi `DELETE /api/v1/users/{id}`.
2) `UserService::destroy()` goi `$user->delete()`.
3) Ban ghi vao trang thai da xoa mem, van con trong DB.

Tai sao:
- Cho phep khoi phuc neu xoa nham.
- Bao toan lich su audit va lien ket nghiep vu.

### 11.6 Thung rac + khoi phuc

Chuc nang:
- `GET /api/v1/users/trash`: xem user da xoa mem.
- `POST /api/v1/users/restore/{id}`: khoi phuc 1 user.
- `POST /api/v1/users/restore`: khoi phuc nhieu user.

Tai sao:
- Ho tro van hanh thuc te: thao tac nham co the rollback nhanh.
- Khoi phuc nhieu ban ghi giup admin xu ly nhanh khi thao tac lo.

### 11.7 Xoa vinh vien

Chuc nang:
- `DELETE /api/v1/users/force/{id}`
- `POST|DELETE /api/v1/users/force` (many)

Tai sao:
- Tach rieng khoi soft delete de tranh mat du lieu khong hoi phuc.
- Buoc "force" chi dung khi can don dep du lieu thuc su.

### 11.8 Khoa/mo khoa tai khoan (is_active)

Chuc nang:
- Bulk update status: `POST /api/v1/users/update-status` (ids + is_active).
- Toggle 1 user: `POST /api/v1/users/{id}/toggle-status`.

Tai sao:
- "Khoa tai khoan" khac voi "xoa tai khoan":
  - Khoa: user tam thoi khong duoc su dung.
  - Xoa: nghieng ve nghiep vu xoa ban ghi.
- Cho phep van giu toan bo lich su du lieu.

### 11.9 Cap nhat avatar 1 nguoi

Luot chay:
1) FE gui file -> `POST /api/v1/users/{id}/avatar`.
2) Service dung `FileHelpers::updateModelImage(...)` de:
   - luu file moi,
   - cap nhat duong dan avatar,
   - xu ly nhat quan ten/thu muc.

Tai sao:
- Gom logic file vao helper dung chung de khong lap code o moi module.
- Dam bao mot quy trinh luu/xoa anh thong nhat.

### 11.10 Bulk avatar bang file zip

Luot chay:
1) FE upload `.zip` -> `POST /api/v1/users/avatar-bulk`.
2) Service giai nen vao temp folder.
3) Duyet tung file:
   - bo qua file rac/he thong,
   - chi nhan extension anh hop le,
   - lay `code` user tu ten file,
   - tim user theo `code`,
   - update avatar.
4) Tra summary `updated/skipped` (+ selected_missing neu co filter id).
5) Xoa temp folder sau khi xong.

Tai sao:
- Ten file = `user.code` de map nhanh, giam sai soat.
- Co `skipped` de admin biet file nao khong map duoc.
- Luon don temp folder de tranh ro ri bo nho/dung luong.

### 11.11 Xuat Excel danh sach nguoi dung

Luot chay:
1) FE goi `GET /api/v1/users/export` (co the kem `ids[]`).
2) `UserExport::stream()` query du lieu + quan he can thiet.
3) Chuan hoa tung dong:
   - role label,
   - trang thai active/blocked/inactive (inactive = da xoa mem),
   - thong tin the thu vien va audit fields.
4) Stream file `FileNguoiDung.xlsx`.

Tai sao:
- Streamed response giup xuat file lon khong ngon RAM.
- Export kem thong tin audit (`created_by`, `updated_by`, `deleted_by`) de dung cho doi soat nghiep vu.
- Co loc theo `ids` de xuat dung tap da chon tren UI.

### 11.12 Vi sao module nay tach Controller -> Service -> Export/Helper

- Controller:
  - xu ly HTTP concerns (validate params, tra ma loi, response shape).
- Service:
  - chua logic nghiep vu CRUD/trang thai/anh.
- Export:
  - dong goi rieng logic tao file Excel.
- Helper file:
  - xu ly luu/xoa/doi anh dung chung toan he thong.

Muc dich:
- De test tung phan doc lap.
- Giam coupling giua HTTP va nghiep vu.
- Khi doi UI (Inertia, mobile app, API client khac), logic core van giu nguyen.
