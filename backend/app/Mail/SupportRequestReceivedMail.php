<?php

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Acuse de recibo al cliente que ha enviado una solicitud de soporte. */
class SupportRequestReceivedMail extends Mailable
{
    public function __construct(public SupportTicket $ticket) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Hemos recibido tu solicitud #{$this->ticket->id}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.support-received');
    }
}
