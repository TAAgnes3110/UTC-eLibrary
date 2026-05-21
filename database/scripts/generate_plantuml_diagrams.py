#!/usr/bin/env python3
"""Generate PlantUML diagrams (stick actor, oval use case) — flat docs/diagrams/."""

from pathlib import Path

OUT = Path(__file__).resolve().parents[2] / "docs" / "diagrams"

HEADER = """@startuml
skinparam actorStyle awesome
skinparam usecase {
  BackgroundColor White
  BorderColor Black
}
skinparam rectangle {
  BackgroundColor #FEFECE
  BorderColor Black
}
left to right direction
"""

FOOTER = "@enduml\n"


def write(name: str, body: str) -> None:
    path = OUT / name
    path.write_text(HEADER + body.strip() + "\n" + FOOTER, encoding="utf-8")


# --- USE CASE ---
write("01-usecase-tong-quat.puml", """
actor "Khách truy cập" as Khach
actor "Bạn đọc" as BanDoc
actor "Độc giả ngoài" as DocNgoai
actor "Thủ thư" as ThuThu
actor "Quản trị viên" as QuanTri
actor "Hệ thống" as HeThong
actor "SePay" as SePay

rectangle "UTC eLibrary" {
  usecase "Tra cứu công khai" as UC1
  usecase "Quản lý tài khoản" as UC2
  usecase "Dịch vụ thẻ và mượn sách" as UC3
  usecase "Dịch vụ tài liệu số" as UC4
  usecase "Quản trị thư viện" as UC5
  usecase "Tự động hóa hệ thống" as UC6
}

Khach --> UC1
Khach --> UC2
BanDoc --> UC1
BanDoc --> UC2
BanDoc --> UC3
BanDoc --> UC4
DocNgoai --> UC3
ThuThu --> UC5
QuanTri --> UC5
HeThong --> UC6
SePay --> UC6

UC3 ..> UC2 : <<include>>
UC4 ..> UC2 : <<include>>
UC5 ..> UC2 : <<include>>
UC3 ..> UC1 : <<include>>

ThuThu <|-- QuanTri
""")

write("02-usecase-ban-doc-tong-hop.puml", """
actor "Khách" as Khach
actor "Bạn đọc" as BanDoc
actor "Độc giả ngoài" as DocNgoai

rectangle "Phân hệ Bạn đọc" {
  usecase "Quản lý tài khoản" as UC1
  usecase "Tra cứu và đọc tài liệu" as UC2
  usecase "Dịch vụ thẻ thư viện" as UC3
  usecase "Mượn trả sách in" as UC4
  usecase "Mua tài liệu số" as UC5
  usecase "Nộp đồ án luận văn" as UC6
  usecase "Tiện ích cá nhân" as UC7
}

Khach --> UC1
Khach --> UC2
BanDoc --> UC1
BanDoc --> UC2
BanDoc --> UC3
BanDoc --> UC4
BanDoc --> UC5
BanDoc --> UC6
BanDoc --> UC7
DocNgoai --> UC3

UC4 ..> UC3 : <<include>>
UC4 ..> UC1 : <<include>>
UC5 ..> UC1 : <<include>>
UC6 ..> UC1 : <<include>>
UC7 ..> UC1 : <<include>>
""")

write("03-usecase-dang-nhap-dang-ky.puml", """
actor "Khách hàng" as Khach
actor "Bạn đọc" as BanDoc

rectangle "Phân rã Đăng nhập / Đăng ký / Đăng xuất" {
  usecase "Đăng nhập" as DN
  usecase "Đăng xuất" as DX
  usecase "Đăng ký" as DK
  usecase "Xác minh OTP" as OTP
  usecase "Quên mật khẩu" as QMK
  usecase "Đăng nhập Microsoft" as MS
}

Khach --> DN
Khach --> DK
BanDoc --> DN
BanDoc --> DX
BanDoc --> QMK

DK ..> DN : <<extend>>
MS ..> DN : <<extend>>
DK ..> OTP : <<include>>
QMK ..> OTP : <<include>>
""")

write("04-usecase-tra-cuu.puml", """
actor "Khách" as Khach
actor "Bạn đọc" as BanDoc

rectangle "Phân rã Tra cứu" {
  usecase "Tra cứu tài liệu" as TC
  usecase "Tìm kiếm và lọc" as TK
  usecase "Xem chi tiết" as CT
  usecase "Xem thử PDF" as PDF
  usecase "Đọc quy định" as QD
  usecase "Lưu sách yêu thích" as LS
}

Khach --> TC
BanDoc --> TC

TC ..> TK : <<include>>
TC ..> CT : <<include>>
TC ..> QD : <<include>>
PDF ..> CT : <<extend>>
LS ..> CT : <<extend>>
""")

write("05-usecase-cap-the.puml", """
actor "Bạn đọc UTC" as BanDoc
actor "Độc giả ngoài" as DocNgoai

rectangle "Phân rã Cấp thẻ" {
  usecase "Cấp thẻ thư viện" as THE
  usecase "Nộp hồ sơ" as NOP
  usecase "Theo dõi hồ sơ" as TD
  usecase "Thanh toán phí thẻ" as TT
  usecase "Đăng ký độc giả ngoài" as GR
}

BanDoc --> THE
DocNgoai --> GR

THE ..> NOP : <<include>>
THE ..> TD : <<include>>
TT ..> THE : <<extend>>
GR ..> THE : <<extend>>
""")

write("06-usecase-muon-sach.puml", """
actor "Bạn đọc" as BanDoc

rectangle "Phân rã Mượn sách in" {
  usecase "Mượn trả sách" as MUON
  usecase "Quản lý giỏ mượn" as GIO
  usecase "Gửi yêu cầu mượn" as GUI
  usecase "Theo dõi phiếu mượn" as PHIEU
  usecase "Yêu cầu gia hạn" as GH
  usecase "Kiểm tra thẻ và policy" as KT
}

BanDoc --> MUON

MUON ..> GIO : <<include>>
MUON ..> GUI : <<include>>
MUON ..> PHIEU : <<include>>
GH ..> MUON : <<extend>>
GUI ..> KT : <<include>>
""")

write("07-usecase-mua-pdf.puml", """
actor "Bạn đọc" as BanDoc

rectangle "Phân rã Mua tài liệu số" {
  usecase "Mua tài liệu số" as MUA
  usecase "Giỏ mua PDF" as GIO
  usecase "Thanh toán SePay" as TT
  usecase "Tải file PDF" as TAI
  usecase "Xem lịch sử đơn" as LS
}

BanDoc --> MUA

MUA ..> GIO : <<include>>
MUA ..> TT : <<include>>
MUA ..> TAI : <<include>>
LS ..> MUA : <<extend>>
TT ..> TAI : <<include>>
""")

write("08-usecase-nop-do-an.puml", """
actor "Bạn đọc" as BanDoc

rectangle "Phân rã Nộp đồ án" {
  usecase "Nộp đồ án luận văn" as NOP
  usecase "Tải lên tài liệu" as UP
  usecase "Theo dõi trạng thái" as TD
}

BanDoc --> NOP

NOP ..> UP : <<include>>
NOP ..> TD : <<include>>
""")

write("09-usecase-admin-tong-hop.puml", """
actor "Thủ thư" as ThuThu
actor "Quản trị viên" as QuanTri

rectangle "Phân hệ Quản trị" {
  usecase "Quản lý tài khoản HT" as UC1
  usecase "Quản lý danh mục" as UC2
  usecase "Quản lý mượn trả" as UC3
  usecase "Quản lý thẻ" as UC4
  usecase "Quản lý tài liệu số" as UC5
  usecase "Vận hành và thống kê" as UC6
}

ThuThu --> UC2
ThuThu --> UC3
ThuThu --> UC4
ThuThu --> UC5
ThuThu --> UC6
QuanTri --> UC1
QuanTri --> UC2
QuanTri --> UC3
QuanTri --> UC4
QuanTri --> UC5
QuanTri --> UC6

UC3 ..> UC4 : <<include>>
UC5 ..> UC2 : <<include>>

ThuThu <|-- QuanTri
""")

write("10-usecase-admin-muon-tra.puml", """
actor "Thủ thư" as ThuThu

rectangle "Phân rã Quản lý mượn trả" {
  usecase "Quản lý mượn trả" as MT
  usecase "Duyệt yêu cầu mượn" as DY
  usecase "Lập phiếu tại quầy" as PH
  usecase "Tiếp nhận trả sách" as TR
  usecase "Duyệt gia hạn" as GH
  usecase "Cấu hình loan policy" as PL
}

ThuThu --> MT

MT ..> DY : <<include>>
MT ..> PH : <<include>>
MT ..> TR : <<include>>
MT ..> GH : <<include>>
MT ..> PL : <<include>>
""")

write("11-usecase-admin-danh-muc.puml", """
actor "Thủ thư" as ThuThu

rectangle "Phân rã Quản lý danh mục" {
  usecase "Quản lý danh mục" as DM
  usecase "CRUD sách in" as IN
  usecase "CRUD bản sao" as BS
  usecase "CRUD tài liệu số PDF" as SO
  usecase "Kho phân loại" as KHO
  usecase "Nhập Excel" as EX
}

ThuThu --> DM

DM ..> IN : <<include>>
DM ..> BS : <<include>>
DM ..> SO : <<include>>
DM ..> KHO : <<include>>
EX ..> IN : <<extend>>
""")

write("12-usecase-admin-the.puml", """
actor "Thủ thư" as ThuThu

rectangle "Phân rã Quản lý thẻ" {
  usecase "Quản lý thẻ" as THE
  usecase "Duyệt hồ sơ" as DY
  usecase "Yêu cầu thu phí" as PHI
  usecase "Thu hồi thẻ" as THU
}

ThuThu --> THE

THE ..> DY : <<include>>
THE ..> THU : <<include>>
PHI ..> THE : <<extend>>
""")

write("13-usecase-he-thong.puml", """
actor "Scheduler" as Sch
actor "SePay" as SePay
actor "SMTP" as Smtp

rectangle "Tự động hóa" {
  usecase "Lập lịch nghiệp vụ" as LICH
  usecase "Xử lý thanh toán" as TT
  usecase "Thông báo và email" as TB
  usecase "Xử lý preview PDF" as PDF
}

Sch --> LICH
Sch --> TT
SePay --> TT
Smtp --> TB

LICH ..> TB : <<include>>
TT ..> TB : <<include>>
PDF ..> TB : <<include>>
""")

# --- ACTIVITY (PlantUML activity diagram syntax) ---
def activity(name: str, body: str) -> None:
    path = OUT / name
    path.write_text("@startuml\nskinparam activity {\n  BackgroundColor White\n}\n" + body.strip() + "\n@enduml\n", encoding="utf-8")

activity("14-activity-muon-sach.puml", """
start
partition "Bạn đọc" {
  :Tra cứu và thêm giỏ mượn;
  :Gửi yêu cầu mượn;
}
partition "Hệ thống" {
  if (Thẻ active và policy?) then (có)
    :Tạo borrow_request pending;
    :Thông báo thủ thư;
  else (không)
    :Báo lỗi;
    stop
  endif
}
partition "Thủ thư" {
  if (Duyệt?) then (đồng ý)
    :LoanService tạo phiếu mượn;
  else (từ chối)
    :Cập nhật rejected;
  endif
}
stop
""")

activity("15-activity-mua-pdf.puml", """
start
if (Đã có entitlement?) then (có)
  :Tải PDF;
  stop
else (chưa)
  :Thêm giỏ mua;
  :Tạo đơn pending + QR;
  :Khách chuyển khoản;
  :Webhook SePay xác nhận;
  if (Thanh toán hợp lệ?) then (có)
    :Cấp entitlement;
    :Tải PDF;
  else (không)
    :Đơn hết hạn;
  endif
endif
stop
""")

activity("16-activity-cap-the.puml", """
start
:Nộp hồ sơ cấp thẻ;
:Trạng thái chờ duyệt;
if (Thu thư?) then (duyệt)
  :Kích hoạt thẻ active;
elseif (thu phí) then
  :Chờ thanh toán phí;
  :Ghi nhận paid → active;
else (từ chối)
  :Rejected;
endif
stop
""")

activity("17-activity-dang-ky-otp.puml", """
start
:Nhập thông tin đăng ký;
:Tạo và gửi OTP email;
:Nhập mã OTP;
if (OTP đúng?) then (có)
  :Kích hoạt tài khoản;
else (sai)
  :Thông báo lỗi;
  detach
endif
stop
""")

# --- SEQUENCE ---
def sequence(name: str, body: str) -> None:
    path = OUT / name
    path.write_text("@startuml\n" + body.strip() + "\n@enduml\n", encoding="utf-8")

sequence("18-sequence-dang-nhap.puml", """
actor "Người dùng" as ND
participant "Giao diện" as GD
participant "AuthController" as AC
participant "AuthService" as AS
database "MySQL" as DB

ND -> GD : Đăng nhập
GD -> AC : LoginRequest
AC -> AS : login()
AS -> DB : xác thực user
AS --> AC : JWT
AC --> GD : token
""")

sequence("19-sequence-duyet-muon.puml", """
actor "Bạn đọc" as BD
actor "Thủ thư" as TT
participant "API" as API
participant "BorrowRequestService" as BRS
participant "LoanService" as LS
database "MySQL" as DB

BD -> API : POST yêu cầu mượn
API -> BRS : createForReader()
BRS -> DB : pending

TT -> API : POST duyệt
API -> BRS : approve()
BRS -> LS : createHomeBorrow()
LS -> DB : loans + items
""")

sequence("20-sequence-sepay.puml", """
actor "Bạn đọc" as BD
participant "PaymentOrderService" as PAY
database "MySQL" as DB
actor "SePay" as SP

BD -> PAY : tạo đơn pending
PAY -> DB : Order + items
SP -> PAY : webhook
PAY -> DB : paid + entitlement
""")

# --- STATE ---
def state(name: str, body: str) -> None:
    path = OUT / name
    path.write_text("@startuml\n" + body.strip() + "\n@enduml\n", encoding="utf-8")

state("21-state-the-thu-vien.puml", """
[*] --> draft
draft --> pending_review
draft --> pending_payment
pending_review --> active
pending_review --> rejected
pending_review --> pending_payment
pending_payment --> active
pending_payment --> expired
active --> expired
active --> revoked
rejected --> [*]
expired --> [*]
revoked --> [*]
""")

state("22-state-don-hang.puml", """
[*] --> pending
pending --> paid
pending --> expired
pending --> cancelled
paid --> [*]
expired --> [*]
cancelled --> [*]
""")

state("23-state-phieu-muon.puml", """
[*] --> da_muon
da_muon --> qua_han
da_muon --> da_tra
qua_han --> da_tra
da_tra --> [*]
""")

# --- ERD + ARCH ---
state("24-erd-tong-the.puml", """
entity users
entity library_cards
entity books
entity book_copies
entity loans
entity loan_borrow_requests
entity digital_assets
entity orders

users ||--o{ library_cards
users ||--o{ loans
users ||--o{ orders
books ||--o{ book_copies
books ||--o| digital_assets
library_cards ||--o{ loan_borrow_requests
library_cards ||--o{ loans
""")

state("25-kien-truc-tong-the.puml", """
package "Client" {
  [Vue Reader]
  [Vue Admin]
}

package "Laravel 12" {
  [Inertia Web]
  [API JWT]
  [Webhook SePay]
}

database "MySQL" as DB
cloud "SePay" as SP
cloud "Redis" as RD

[Vue Reader] --> [Inertia Web]
[Vue Admin] --> [Inertia Web]
[Inertia Web] --> DB
[API JWT] --> DB
[Webhook SePay] --> SP
[API JWT] --> SP
""")

print(f"Generated {len(list(OUT.glob('*.puml')))} files in {OUT}")
