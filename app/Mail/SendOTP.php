<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOTP extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(array $data = [])
    {
        $this->otp = $data['otp'] ?? 0;
        $this->name = $data['name'] ?? '';
    }

    public int $otp;
    public string $name;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mã xác thực OTP - UTC eLibrary',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.SendOTP',
        );
    }
}
