<?php

namespace App\Mail;

use App\Models\LibraryCard;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LibraryCardPickupReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public LibraryCard $libraryCard,
        public int $pickupWithinDays = 3
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Thẻ thư viện của bạn đã sẵn sàng, mời bạn ghé thư viện nhận thẻ'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.library-card-pickup-reminder',
        );
    }
}
