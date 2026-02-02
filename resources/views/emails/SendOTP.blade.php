<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã xác thực OTP - UTC eLibrary</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); overflow: hidden; }
        .header { background-color: #1e3a8a; padding: 20px; text-align: center; }
        .content { padding: 30px; text-align: left; }
        .content h1 { color: #1e3a8a; font-size: 24px; margin-top: 0; }
        .otp-box { background-color: #eef2ff; border: 2px dashed #1e3a8a; color: #1e3a8a; font-size: 32px; font-weight: bold; text-align: center; padding: 15px; margin: 20px 0; letter-spacing: 8px; border-radius: 8px; }
        .footer { background-color: #eee; padding: 15px; text-align: center; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $message->embed(public_path('Image/logoUTC.png')) }}" alt="UTC eLibrary" style="max-width: 120px;">
        </div>
        <div class="content">
            <h1>Mã xác thực OTP</h1>
            @if($name)
            <p>Xin chào <strong>{{ $name }}</strong>,</p>
            @endif
            <p>Bạn đã yêu cầu mã xác thực OTP. Vui lòng sử dụng mã dưới đây:</p>
            <div class="otp-box">{{ $otp }}</div>
            <p>Mã có hiệu lực trong <strong>5 phút</strong>. Không chia sẻ mã này cho bất kỳ ai.</p>
            <p style="margin-top: 30px;">Trân trọng,<br><strong>UTC eLibrary</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} UTC eLibrary - Trường Đại học Giao thông Vận tải
        </div>
    </div>
</body>
</html>
