<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $messageBody;
    public $senderName;
    public $recipientType;

    public function __construct(
        Ticket $ticket,
        string $messageBody,
        string $senderName,
        string $recipientType
    ) {
        $this->ticket = $ticket;
        $this->messageBody = $messageBody;
        $this->senderName = $senderName;
        $this->recipientType = $recipientType;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Message - ' . $this->ticket->ticket_number
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-message'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}