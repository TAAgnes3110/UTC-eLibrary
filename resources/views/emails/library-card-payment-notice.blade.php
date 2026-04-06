<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Thanh toán cấp thẻ thư viện') }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); overflow: hidden; }
        .header { background-color: #1e3a8a; padding: 20px; text-align: center; }
        .content { padding: 30px; text-align: left; }
        .content h1 { color: #1e3a8a; font-size: 22px; margin-top: 0; }
        .due-box { background-color: #fef3c7; border-left: 4px solid #d97706; padding: 14px 16px; margin: 20px 0; border-radius: 4px; }
        .footer { background-color: #eee; padding: 15px; text-align: center; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $message->embed(public_path('Image/logoUTC.png')) }}" alt="UTC eLibrary" style="max-width: 120px;">
        </div>
        <div class="content">
            <h1>{{ __('Yêu cầu cấp thẻ đã được xác nhận') }}</h1>
            <p>{{ __('Xin chào') }} <strong>{{ $libraryCard->full_name }}</strong>,</p>
            <p>{{ __('Hồ sơ đăng ký thẻ thư viện của bạn đã được thủ thư xác nhận. Vui lòng hoàn tất thanh toán lệ phí cấp thẻ trong thời hạn dưới đây.') }}</p>
            <div class="due-box">
                <strong>{{ __('Hạn thanh toán') }}:</strong>
                {{ $paymentDueAt->timezone(config('app.timezone'))->locale('vi')->translatedFormat('l, d/m/Y H:i') }}
            </div>
            <p>{{ __('Nếu quá hạn mà chưa thanh toán, hồ sơ sẽ tự động hủy. Bạn có thể đăng ký lại sau.') }}</p>
            <p>{{ __('Mã hồ sơ / số thẻ tạm') }}: <strong>{{ $libraryCard->card_number }}</strong></p>
            <p style="margin-top: 30px;">{{ __('Trân trọng') }},<br><strong>UTC eLibrary</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} UTC eLibrary - {{ __('Trường Đại học Giao thông Vận tải') }}
        </div>
    </div>
</body>
</html>
