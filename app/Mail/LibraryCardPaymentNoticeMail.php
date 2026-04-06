<?php

namespace App\Mail;

use App\Models\LibraryCard;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LibraryCardPaymentNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public LibraryCard $libraryCard,
        public Carbon $paymentDueAt
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Hồ sơ cấp thẻ thư viện đã được duyệt — vui lòng thanh toán'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.library-card-payment-notice',
        );
    }
}
