<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Pedido pagado: resumen para el cliente con la factura PDF adjunta. */
class OrderPaidMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Pedido {$this->order->reference} pagado: tu factura");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-paid');
    }

    public function attachments(): array
    {
        $invoice = $this->order->invoice;
        if (! $invoice) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $invoice->pdf()->output(), $invoice->number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
