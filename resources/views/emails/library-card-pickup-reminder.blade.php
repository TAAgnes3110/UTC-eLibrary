<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhắc nhận thẻ thư viện</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; line-height: 1.55; color: #1f2937;">
    <h2 style="margin: 0 0 12px;">Thẻ thư viện của bạn đã sẵn sàng</h2>
    <p style="margin: 0 0 10px;">Chào {{ $libraryCard->full_name ?: 'bạn đọc' }},</p>
    <p style="margin: 0 0 10px;">
        Tin vui là thẻ thư viện của bạn đã được duyệt/cấp thành công.
        Bạn vui lòng ghé thư viện để nhận thẻ trong vòng <strong>{{ $pickupWithinDays }}</strong> ngày nhé.
    </p>
    <p style="margin: 0 0 10px;">
        <strong>Mã thẻ:</strong> {{ $libraryCard->card_number ?: ($libraryCard->code ?: '—') }}
    </p>
    <p style="margin: 0 0 10px;">
        <strong>Hiệu lực:</strong>
        {{ optional($libraryCard->issue_date)->format('d/m/Y') ?: '—' }}
        -
        {{ optional($libraryCard->expiry_date)->format('d/m/Y') ?: '—' }}
    </p>
    <p style="margin: 18px 0 0;">Cảm ơn bạn,<br>Thư viện Trường Đại học Giao thông Vận tải</p>
</body>
</html>
