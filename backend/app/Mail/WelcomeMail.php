<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Bienvenida al cliente que acaba de crear su cuenta. */
class WelcomeMail extends Mailable
{
    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Te damos la bienvenida a '.config('shop.name'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.welcome');
    }
}
