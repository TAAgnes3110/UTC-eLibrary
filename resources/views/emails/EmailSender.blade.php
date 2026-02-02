<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mailData['subject'] ?? 'UTC eLibrary Notification' }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .header {
            background-color: #003366;
            padding: 20px;
            text-align: center;
        }
        .header img {
            max-width: 150px;
            height: auto;
        }
        .content {
            padding: 30px;
            text-align: left;
        }
        .content h1 {
            color: #003366;
            font-size: 24px;
            margin-top: 0;
        }
        .otp-box {
            background-color: #f0f8ff;
            border: 2px dashed #003366;
            color: #003366;
            font-size: 32px;
            font-weight: bold;
            text-align: center;
            padding: 15px;
            margin: 20px 0;
            letter-spacing: 5px;
            border-radius: 5px;
        }
        .footer {
            background-color: #eee;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        .btn-link {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #003366;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $message->embed(public_path('Image/logoUTC.png')) }}" alt="UTC eLibrary Logo">
        </div>
        <div class="content">
            <h1>Xác thực tài khoản</h1>
            <p>Xin chào,</p>
            <p>Cảm ơn bạn đã đăng ký tài khoản tại <strong>UTC eLibrary</strong>. Để hoàn tất quá trình đăng ký, vui lòng sử dụng mã xác nhận (OTP) dưới đây:</p>

            @php
                $bodyLines = explode("\n", $mailData['body'] ?? '');
                $otp = '';
                foreach ($bodyLines as $line) {
                    if (preg_match('/OTP.*:\s*(\d{6})/', $line, $matches)) {
                        $otp = $matches[1];
                        break;
                    }
                }
            @endphp

            @if($otp)
                <div class="otp-box">{{ $otp }}</div>
                <p>Mã này có hiệu lực trong vòng 10 phút. Tuyệt đối không chia sẻ mã này cho bất kỳ ai.</p>
            @else
                <div style="white-space: pre-line;">
                   {!! nl2br(e($mailData['body'] ?? '')) !!}
                </div>
            @endif

            <p>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.</p>

            <p style="margin-top: 30px;">
                Trân trọng,<br>
                Đội ngũ phát triển UTC eLibrary
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} UTC eLibrary. All rights reserved.<br>
            Trường Đại học Giao thông Vận tải - University of Transport and Communications
        </div>
    </div>
</body>
</html>
