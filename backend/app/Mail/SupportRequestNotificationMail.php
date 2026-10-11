<?php

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Aviso interno a la tienda: nueva solicitud de soporte (al responder, se contesta al cliente). */
class SupportRequestNotificationMail extends Mailable
{
    public function __construct(public SupportTicket $ticket) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Soporte] #{$this->ticket->id} · {$this->ticket->subject}",
            replyTo: [new Address($this->ticket->email, $this->ticket->name)],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.support-notification');
    }
}
