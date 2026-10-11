<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** El pedido ha salido del almacén. */
class OrderShippedMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Tu pedido {$this->order->reference} va de camino");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-shipped');
    }
}
