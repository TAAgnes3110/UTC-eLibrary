<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailSender extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public array $mailData = ['from' => [], 'subject' => '', 'body' => '', 'files' => []];
    /**
     * Create a new message instance.
     */
    public function __construct($mailData)
    {
        $this->mailData = array_merge($this->mailData, $mailData);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        if ($this->mailData['from']) {
            return new Envelope(
                from: new Address($this->mailData['from']['address'], $this->mailData['from']['name']),
                subject: $this->mailData['subject'],
            );
        } else {
            return new Envelope(
                subject: $this->mailData['subject'],
            );
        }
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.EmailSender',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return $this->mailData['files'];
    }
}
