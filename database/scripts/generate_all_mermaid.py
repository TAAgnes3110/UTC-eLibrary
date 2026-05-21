#!/usr/bin/env python3
"""Sinh toàn bộ biểu đồ Mermaid chuẩn — một actor Bạn đọc (không tách Khách)."""

from __future__ import annotations

from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
DIAG = ROOT / "docs" / "diagrams"
USECASE = DIAG / "usecase"
ACTIVITY = DIAG / "activity"
UML = DIAG / "uml"

# Actor đọc: gộp Khách + Bạn đọc
BD = "BanDoc"
BD_LABEL = "Bạn đọc"


def uc(
    title: str,
    system: str,
    usecases: list[tuple[str, str]],
    links: list[tuple[str, str]],
    extra_actors: list[tuple[str, str, str]] | None = None,
    dotted: list[tuple[str, str, str]] | None = None,
) -> str:
    """usecases: (id, label). links: (actor_id, uc_id). extra_actors: (id, label, shape)."""
    lines = [
        "---",
        f"title: {title}",
        "---",
        "flowchart TB",
        f"    {BD}(({BD_LABEL}))",
    ]
    for aid, label, shape in extra_actors or []:
        if shape == "person":
            lines.append(f"    {aid}(({label}))")
        else:
            lines.append(f"    {aid}[{label}]")
    lines.append(f'    subgraph SYS["{system}"]')
    lines.append("        direction TB")
    for uid, ulab in usecases:
        safe = ulab.replace('"', "'")
        lines.append(f"        {uid}([{safe}])")
    lines.append("    end")
    seen: set[tuple[str, str]] = set()
    for a, u in links:
        a = BD if a in ("Khach", "BanDoc", "Khách") else a
        key = (a, u)
        if key in seen:
            continue
        seen.add(key)
        lines.append(f"    {a} --> {u}")
    for a, u, lab in dotted or []:
        a = BD if a in ("Khach", "BanDoc") else a
        lines.append(f"    {a} -.->|{lab}| {u}")
    return "\n".join(lines) + "\n"


def write(rel: str, content: str) -> None:
    if rel.startswith("activity/"):
        path = ACTIVITY / rel.split("/", 1)[1]
    elif rel.startswith(("sequence/", "state/", "erd/", "class/", "kien-truc/")):
        path = UML / rel
    else:
        path = USECASE / rel
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content, encoding="utf-8", newline="\n")
    print(f"  {path.relative_to(DIAG)}")


def main() -> None:
    # --- USE CASE TỔNG QUÁT ---
    write(
        "01-usecase-tong-quat.mmd",
        """---
title: Hình 2.1 - Use case tổng quát UTC eLibrary
---
flowchart TB
    BanDoc((Bạn đọc))
    DocNgoai((Độc giả ngoài))
    CanBo((Thủ thư / Quản trị viên))
    HeThong[[Hệ thống]]
    SePay[[SePay]]
    subgraph EL["UTC eLibrary"]
        direction TB
        UC1([Tra cứu tài liệu])
        UC2([Quản lý tài khoản])
        UC3([Cấp thẻ thư viện])
        UC4([Mượn / trả sách])
        UC5([Tài liệu số và thanh toán])
        UC6([Nộp đồ án số])
        UC7([Quản trị thư viện])
        UC8([Tự động hóa và thông báo])
    end
    BanDoc --> UC1
    BanDoc --> UC2
    BanDoc --> UC3
    BanDoc --> UC4
    BanDoc --> UC5
    BanDoc --> UC6
    DocNgoai --> UC1
    DocNgoai --> UC2
    CanBo --> UC4
    CanBo --> UC7
    HeThong --> UC8
    SePay --> UC5
""",
    )

    write(
        "ban-doc/02-tong-hop-ban-doc.mmd",
        uc(
            "Hình 2.2a - Use case tổng hợp phân hệ Bạn đọc",
            "Phân hệ Bạn đọc",
            [
                ("UC1", "Tài khoản và xác thực"),
                ("UC2", "Tra cứu tài liệu"),
                ("UC3", "Thẻ thư viện"),
                ("UC4", "Mượn sách trực tuyến"),
                ("UC5", "Mua và đọc PDF"),
                ("UC6", "Gia hạn và nộp đồ án"),
            ],
            [("BanDoc", "UC1"), ("BanDoc", "UC2"), ("BanDoc", "UC3"), ("BanDoc", "UC4"), ("BanDoc", "UC5"), ("BanDoc", "UC6")],
        ),
    )

    write(
        "ban-doc/03-dang-nhap-dang-ky.mmd",
        uc(
            "Hình 2.2 - Use case phân rã Đăng ký, đăng nhập, đăng xuất",
            "UTC eLibrary - Tài khoản",
            [
                ("UC_Login", "Đăng nhập"),
                ("UC_Reg", "Đăng ký tài khoản"),
                ("UC_OTP", "Xác minh OTP email"),
                ("UC_Logout", "Đăng xuất"),
                ("UC_Reset", "Quên / đặt lại mật khẩu"),
                ("UC_Profile", "Cập nhật hồ sơ"),
                ("UC_Pass", "Đổi mật khẩu"),
            ],
            [
                ("BanDoc", "UC_Login"),
                ("BanDoc", "UC_Reg"),
                ("BanDoc", "UC_Reset"),
                ("BanDoc", "UC_Logout"),
                ("BanDoc", "UC_Profile"),
                ("BanDoc", "UC_Pass"),
            ],
            dotted=[
                ("UC_Reg", "UC_OTP", "include"),
                ("UC_Reg", "UC_Login", "extend"),
            ],
        ),
    )

    write(
        "ban-doc/04-tra-cuu.mmd",
        """---
title: Hình 2.3 - Use case phân rã Tra cứu và tìm kiếm
---
flowchart TB
    BanDoc((Bạn đọc))
    subgraph SYS["UTC eLibrary - Tra cứu"]
        direction TB
        UC_TraCuu([Tra cứu và tìm kiếm tài liệu])
        UC_Keyword([Tìm kiếm theo từ khóa])
        UC_LocTheLoai([Lọc theo thể loại])
        UC_LocLoaiHinh([Lọc loại hình in / số])
        UC_LocTon([Lọc tình trạng tồn])
        UC_Sort([Sắp xếp kết quả])
        UC_List([Hiển thị danh sách kết quả])
        UC_Detail([Xem chi tiết sách])
        UC_Related([Xem tài liệu liên quan])
    end
    BanDoc --> UC_TraCuu
    BanDoc --> UC_Detail
    UC_TraCuu -.->|include| UC_LocTheLoai
    UC_TraCuu -.->|include| UC_LocLoaiHinh
    UC_TraCuu -.->|include| UC_LocTon
    UC_TraCuu -.->|include| UC_Sort
    UC_TraCuu -.->|include| UC_List
    UC_Keyword -.->|extend| UC_TraCuu
    UC_Detail -.->|include| UC_Related
""",
    )

    write(
        "ban-doc/05-cap-the.mmd",
        """---
title: Hình 2.4 - Use case phân rã Cấp thẻ thư viện (Bạn đọc)
---
flowchart TB
    BanDoc((Bạn đọc))
    subgraph SYS["UTC eLibrary - Thẻ thư viện"]
        direction TB
        UC_Submit([Gửi yêu cầu cấp thẻ])
        UC_Check([Kiểm tra hồ sơ đủ điều kiện])
        UC_Update([Cập nhật hồ sơ khi chờ duyệt])
        UC_Cancel([Hủy yêu cầu cấp thẻ])
        UC_Status([Theo dõi trạng thái thẻ])
        UC_Guide([Xem hướng dẫn nộp phí và lấy thẻ tại quầy])
    end
    BanDoc --> UC_Submit
    BanDoc --> UC_Cancel
    BanDoc --> UC_Status
    BanDoc --> UC_Guide
    UC_Submit -.->|include| UC_Check
    UC_Update -.->|extend| UC_Submit
""",
    )

    write(
        "ban-doc/06-muon-sach.mmd",
        """---
title: Hình 2.5 - Use case phân rã Giỏ mượn và yêu cầu mượn (Bạn đọc)
---
flowchart TB
    BanDoc((Bạn đọc))
    subgraph SYS["UTC eLibrary - Mượn sách vật lý"]
        direction TB
        UC_Cart([Thêm sách vào giỏ mượn])
        UC_ViewCart([Xem / chỉnh giỏ mượn])
        UC_Submit([Gửi yêu cầu mượn])
        UC_Check([Kiểm tra thẻ, hạn mức và tồn kho])
        UC_Preview([Kiểm tra điều kiện trước khi gửi])
        UC_MyLoans([Theo dõi yêu cầu và phiếu mượn])
    end
    BanDoc --> UC_Cart
    BanDoc --> UC_ViewCart
    BanDoc --> UC_Submit
    BanDoc --> UC_MyLoans
    UC_Submit -.->|include| UC_Check
    UC_Submit -.->|include| UC_Preview
    UC_ViewCart -.->|include| UC_Cart
""",
    )

    write(
        "ban-doc/07-mua-pdf.mmd",
        """---
title: Hình 2.6 - Use case phân rã Mua tài liệu số SePay (Bạn đọc)
---
flowchart TB
    BanDoc((Bạn đọc))
    subgraph SYS["UTC eLibrary - Thanh toán số"]
        direction TB
        UC_Mua([Mua tài liệu số qua SePay])
        UC_Cart([Thêm PDF vào giỏ mua])
        UC_Order([Tạo đơn thanh toán và mã QR])
        UC_CheckPay([Kiểm tra trạng thái thanh toán])
        UC_BuyNow([Mua ngay một tài liệu])
        UC_Download([Tải PDF đã mua])
        UC_Cancel([Hủy đơn chờ thanh toán])
    end
    BanDoc --> UC_Mua
    BanDoc --> UC_Download
    UC_Mua -.->|include| UC_Cart
    UC_Mua -.->|include| UC_Order
    UC_Mua -.->|include| UC_CheckPay
    UC_BuyNow -.->|extend| UC_Mua
    UC_Download -.->|extend| UC_Mua
    UC_Cancel -.->|extend| UC_Order
""",
    )

    write(
        "ban-doc/08-doc-ebook.mmd",
        """---
title: Hình 2.7 - Use case phân rã Đọc tài liệu số (Bạn đọc)
---
flowchart TB
    BanDoc((Bạn đọc))
    subgraph SYS["UTC eLibrary - Đọc tài liệu số"]
        direction TB
        UC_Read([Đọc tài liệu số trực tuyến])
        UC_AuthZ([Kiểm tra quyền truy cập])
        UC_Stream([Stream PDF bảo mật])
        UC_Preview([Xem trước N trang catalog])
        UC_Download([Tải file PDF đã mua])
    end
    BanDoc --> UC_Read
    UC_Read -.->|include| UC_AuthZ
    UC_Read -.->|include| UC_Stream
    UC_Preview -.->|extend| UC_Read
    UC_Download -.->|extend| UC_Read
""",
    )

    write(
        "ban-doc/09-gia-han-nop.mmd",
        """---
title: Hình 2.8 - Use case phân rã Gia hạn mượn và nộp tài liệu số (Bạn đọc)
---
flowchart TB
    BanDoc((Bạn đọc))
    subgraph GH["Gia hạn mượn"]
        UC_Renew([Gửi yêu cầu gia hạn mượn])
        UC_RenewCheck([Kiểm tra điều kiện gia hạn])
        UC_RenewTrack([Theo dõi trạng thái yêu cầu gia hạn])
    end
    subgraph NOP["Nộp tài liệu số"]
        UC_SubmitDoc([Nộp đồ án / luận văn PDF])
        UC_ValidateDoc([Kiểm tra metadata và file])
        UC_TrackDoc([Theo dõi trạng thái duyệt nộp bài])
    end
    BanDoc --> UC_Renew
    BanDoc --> UC_RenewTrack
    BanDoc --> UC_SubmitDoc
    BanDoc --> UC_TrackDoc
    UC_Renew -.->|include| UC_RenewCheck
    UC_SubmitDoc -.->|include| UC_ValidateDoc
""",
    )

    # --- ADMIN ---
    write(
        "admin/09-tong-hop-admin.mmd",
        """---
title: Hình 2.9a - Use case tổng hợp quản trị thư viện
---
flowchart TB
    CanBo((Thủ thư / Quản trị viên))
    subgraph AD["Phân hệ Quản trị"]
        direction TB
        UC1([Quản lý người dùng])
        UC2([Danh mục tài liệu])
        UC3([Kho / phân loại / tủ])
        UC4([Mượn / trả / phạt])
        UC5([Thẻ thư viện])
        UC6([Cấu hình / báo cáo / tin tức])
    end
    CanBo --> UC1
    CanBo --> UC2
    CanBo --> UC3
    CanBo --> UC4
    CanBo --> UC5
    CanBo --> UC6
""",
    )

    write(
        "admin/10-dang-nhap.mmd",
        uc(
            "Hình 2.10 - Use case Admin đăng nhập",
            "UTC eLibrary - Admin",
            [("UC_Login", "Đăng nhập admin"), ("UC_Logout", "Đăng xuất")],
            [],
            extra_actors=[
                ("ThuThu", "Thủ thư", "person"),
                ("Admin", "Quản trị viên", "person"),
            ],
        )
        + "    ThuThu --> UC_Login\n    ThuThu --> UC_Logout\n    Admin --> UC_Login\n    Admin --> UC_Logout\n",
    )

    # Fix admin 10 - uc() always adds BanDoc, need custom for admin only
    write(
        "admin/10-dang-nhap.mmd",
        """---
title: Hình 2.10 - Use case Đăng nhập quản trị
---
flowchart TB
    CanBo((Thủ thư / Quản trị viên))
    subgraph SYS["UTC eLibrary - Quản trị"]
        UC_Login([Đăng nhập])
        UC_Logout([Đăng xuất])
    end
    CanBo --> UC_Login
    CanBo --> UC_Logout
""",
    )

    write(
        "admin/11-quan-ly-user.mmd",
        (USECASE / "admin" / "11-quan-ly-user.mmd").read_text(encoding="utf-8"),
    )

    for _admin_uc in (
        "12-muon-tra.mmd",
        "13-danh-muc.mmd",
        "14-kho-phan-loai-tu.mmd",
        "15-the-thu-vien.mmd",
        "16-cau-hinh-bao-cao.mmd",
    ):
        write(
            f"admin/{_admin_uc}",
            (USECASE / "admin" / _admin_uc).read_text(encoding="utf-8"),
        )

    write(
        "he-thong/17-tong-hop-he-thong.mmd",
        """---
title: Hình 2.17 - Use case Hệ thống
---
flowchart TB
    HeThong[[Hệ thống]]
    SePay[[SePay]]
    subgraph SYS["Dịch vụ ngầm"]
        UC1([Gửi email / OTP])
        UC2([Quét trễ hạn mượn])
        UC3([Tính phạt tích lũy])
        UC4([Nhắc hạn trả sắp tới])
        UC5([Xử lý webhook SePay])
        UC6([Hết hạn đơn chờ TT])
    end
    HeThong --> UC1
    HeThong --> UC2
    HeThong --> UC3
    HeThong --> UC4
    HeThong --> UC6
    SePay --> UC5
    UC2 -.->|include| UC3
    UC4 -.->|include| UC1
""",
    )

    write(
        "he-thong/18-gui-email-queue.mmd",
        """---
title: Hình 2.18 - Use case Gửi email Queue
---
flowchart TB
    HeThong[[Hệ thống]]
    subgraph SYS["Email"]
        UC1([Gửi OTP đăng ký])
        UC2([Gửi nhắc hạn trả])
        UC3([Đẩy job Laravel Queue])
    end
    HeThong --> UC1
    HeThong --> UC2
    UC1 -.->|include| UC3
    UC2 -.->|include| UC3
""",
    )

    # --- ACTIVITY ---
    write(
        "activity/14-muon-online.mmd",
        """---
title: Hình 2.27 - Hoạt động Đăng ký mượn sách trực tuyến
---
flowchart TD
    subgraph BD["Bạn đọc"]
        S1((Bắt đầu)) --> A1[Chọn sách, thêm giỏ mượn]
        A1 --> A2[Gửi yêu cầu mượn]
    end
    subgraph HT["Hệ thống"]
        A2 --> D1{Đủ điều kiện?}
        D1 -->|Có| B1[Lưu loan_borrow_requests]
        B1 --> B2[Gửi thông báo thủ thư]
        D1 -->|Không| E1[Trả lỗi]
        E1 --> E2((Kết thúc))
    end
    subgraph TT["Thủ thư"]
        B2 --> D2{Duyệt?}
        D2 -->|Đồng ý| C1[Duyệt yêu cầu]
        D2 -->|Từ chối| C2[Từ chối]
    end
    subgraph HT2["Hệ thống"]
        C1 --> F1[LoanService tạo phiếu mượn]
        C2 --> F2[Cập nhật trạng thái]
    end
    subgraph BD2["Bạn đọc"]
        F1 --> G1[Nhận thông báo]
        F2 --> G1
        G1 --> Z((Kết thúc))
    end
""",
    )

    write(
        "activity/15-doc-ebook.mmd",
        """---
title: Hình 2.28 - Hoạt động Đọc tài liệu số
---
flowchart TD
    S((Bắt đầu)) --> A[Truy cập trang tài liệu số]
    A --> D1{Đã đăng nhập?}
    D1 -->|Không| P1[Preview catalog nếu bật]
    D1 -->|Có| D2{Đã mua / có quyền?}
    D2 -->|Có| R1[Stream PDF bảo mật]
    D2 -->|Không| D3{Cho preview?}
    D3 -->|Có| P2[Hiển thị N trang đầu]
    D3 -->|Không| M1[Chuyển mua / đăng nhập]
    P1 --> Z((Kết thúc))
    R1 --> Z
    P2 --> Z
    M1 --> Z
""",
    )

    write(
        "activity/16-tra-sach-phat.mmd",
        """---
title: Hình 2.29 - Hoạt động Trả sách và xử lý vi phạm
---
flowchart TD
    subgraph TT["Thủ thư"]
        S((Bắt đầu)) --> A1[Chọn phiếu mượn tại quầy]
        A1 --> A2[Nhập ngày trả thực tế]
        A2 --> D2{Tình trạng sách?}
        D2 -->|Nguyên vẹn| OK[OK]
        D2 -->|Hư / mất| HM[Ghi nhận phạt]
    end
    subgraph HT["Hệ thống"]
        A2 --> D1{Trả muộn?}
        D1 -->|Có| F1[Tính phạt trễ hạn]
        D1 -->|Không| F1
        OK --> R1[LoanService::return]
        HM --> R1
        F1 --> R1
        R1 --> Z((Kết thúc))
    end
""",
    )

    write(
        "activity/17-dang-ky-otp.mmd",
        """---
title: Hình 2.30 - Hoạt động Đăng ký OTP
---
flowchart TD
    subgraph BD["Bạn đọc"]
        S((Bắt đầu)) --> F1[Điền form đăng ký]
        F1 --> F2[Nhập mã OTP]
    end
    subgraph HT["Hệ thống"]
        F1 --> V1{Email hợp lệ?}
        V1 -->|Có| E1[Gửi OTP qua Queue]
        V1 -->|Không| ERR[Lỗi validate]
        F2 --> V2{OTP đúng?}
        V2 -->|Có| OK[Tạo user, đăng nhập]
        V2 -->|Không| ERR2[OTP sai / hết hạn]
    end
    OK --> Z((Kết thúc))
    ERR --> Z
    ERR2 --> Z
""",
    )

    write(
        "activity/18-mua-pdf-sepay.mmd",
        """---
title: Hình 2.31 - Hoạt động Mua PDF SePay
---
flowchart TD
    subgraph BD["Bạn đọc"]
        S((Bắt đầu)) --> A1[Thêm PDF vào giỏ]
        A1 --> A2[Thanh toán chuyển khoản]
        A2 --> A3[Tải / đọc PDF]
    end
    subgraph HT["Hệ thống"]
        A1 --> O1[Tạo orders + QR]
        O1 --> W1{Webhook hợp lệ?}
        W1 -->|Có| W2[Mở quyền tải PDF]
        W1 -->|Không| W3[Bỏ qua]
    end
    subgraph SP["SePay"]
        A2 --> WH[Gửi webhook]
    end
    WH --> W1
    W2 --> A3
    A3 --> Z((Kết thúc))
""",
    )

    write(
        "activity/19-quet-phat-00h.mmd",
        """---
title: Hình 2.32 - Hoạt động Quét trễ hạn 00:00
---
flowchart TD
    subgraph HT["Hệ thống"]
        S((00:00)) --> C1[schedule:run]
        C1 --> C2[sync-overdue]
        C2 --> D1{Có phạt theo ngày?}
        D1 -->|Có| F1[Cập nhật fine_amount]
        D1 -->|Không| N1[notify-due-soon]
        F1 --> N1
        N1 --> Q1[Đẩy job email Queue]
        Q1 --> Q2[Worker gửi email]
        Q2 --> Z((Kết thúc))
    end
""",
    )

    write(
        "activity/20-cap-the.mmd",
        """---
title: Hình 2.33 - Hoạt động Cấp thẻ (bạn đọc và quầy)
---
flowchart TD
    subgraph BD["Bạn đọc"]
        S((Bắt đầu)) --> A1[Gửi yêu cầu cấp thẻ online]
        A1 --> A2[Theo dõi trạng thái trên web]
        A2 --> D0{Trạng thái?}
        D0 -->|Chờ thanh toán| A3[Đến quầy nộp phí thẻ]
        D0 -->|Chờ lấy thẻ| A4[Nhận thẻ tại quầy]
        A3 --> A2
        A4 --> Z((Kết thúc))
    end
    subgraph HT["Hệ thống"]
        A1 --> V1[Lưu pending_review / pending_payment]
    end
    subgraph TT["Thủ thư"]
        V1 --> D1{Duyệt hồ sơ?}
        D1 -->|Đồng ý| P1[Thu phí tại quầy + ghi nhận]
        D1 -->|Từ chối| NO[Từ chối]
        P1 --> P2[Giao thẻ / kích hoạt]
    end
    P2 --> A2
    NO --> A2
""",
    )

    write(
        "activity/21-tra-cuu-tim-kiem.mmd",
        """---
title: Hình 2.26 - Hoạt động Tra cứu và tìm kiếm
---
flowchart TD
    subgraph BD["Bạn đọc"]
        S((Bắt đầu)) --> A1[Chọn bộ lọc / nhập từ khóa]
        A1 --> A2[Nhấn Tìm kiếm]
        A2 --> A3[Xem danh sách / chi tiết]
        A3 --> Z((Kết thúc))
    end
    subgraph HT["Hệ thống"]
        A2 --> B1[readerCatalog: keyword + classification + resource_type + stock + sort]
        B1 --> D1{Có kết quả?}
        D1 -->|Có| B2[Trả danh sách phân trang]
        D1 -->|Không| B3[Danh sách rỗng]
    end
    B2 --> A3
    B3 --> A3
""",
    )

    # UML sequence: chỉnh tay docs/diagrams/uml/sequence/*.puml (mẫu đồ án)
    print("  (bo qua uml/sequence — xem docs/diagrams/uml/README.md)")

    n = sum(1 for d in (USECASE, ACTIVITY, UML) if d.is_dir() for _ in d.rglob("*.mmd"))
    print(f"\nDone: {n} .mmd under docs/diagrams/{{usecase,activity,uml}}")


if __name__ == "__main__":
    main()
