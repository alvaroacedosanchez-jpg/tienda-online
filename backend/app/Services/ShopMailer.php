<?php

namespace App\Services;

use App\Mail\OrderPaidMail;
use App\Mail\OrderShippedMail;
use App\Mail\SupportRequestNotificationMail;
use App\Mail\SupportRequestReceivedMail;
use App\Mail\WelcomeMail;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Único punto de envío de correos de la tienda (SMTP de la cuenta de DonDominio).
 *
 * - Si se llama dentro de una transacción, el correo sale SOLO cuando la transacción se confirma:
 *   nunca se avisa de un pago que luego se ha revertido.
 * - Si el envío falla (SMTP caído, credenciales...), se anota en el log y NO se interrumpe
 *   el registro, el pago ni el pedido.
 */
class ShopMailer
{
    public function welcome(User $user): void
    {
        $this->send($user->email, new WelcomeMail($user), "bienvenida usuario #{$user->id}");
    }

    public function orderPaid(Order $order): void
    {
        $this->send($order->customer->email, new OrderPaidMail($order), "pedido pagado {$order->reference}");
    }

    public function orderShipped(Order $order): void
    {
        $this->send($order->customer->email, new OrderShippedMail($order), "pedido enviado {$order->reference}");
    }

    /** Acuse de recibo al cliente y aviso a la tienda. */
    public function supportRequested(SupportTicket $ticket): void
    {
        $this->send($ticket->email, new SupportRequestReceivedMail($ticket), "acuse soporte #{$ticket->id}");
        $this->send(config('shop.notify_email'), new SupportRequestNotificationMail($ticket), "aviso soporte #{$ticket->id}");
    }

    private function send(?string $to, Mailable $mail, string $description): void
    {
        if (! $to) {
            Log::warning("Correo no enviado ({$description}): no hay destinatario.");

            return;
        }

        DB::afterCommit(function () use ($to, $mail, $description) {
            try {
                Mail::to($to)->send($mail);
            } catch (Throwable $e) {
                Log::error("Error al enviar el correo ({$description}) a {$to}: {$e->getMessage()}");
            }
        });
    }
}
