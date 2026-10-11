<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Redsys\Endpoints;
use Redsys\Merchant;
use Redsys\Parameters;
use Redsys\RequestData;
use RuntimeException;

/**
 * Pago con tarjeta en el TPV Virtual de Redsys (entorno de pruebas), integración por redirección.
 *
 * 1. start(): crea el pago "pending" y prepara el formulario firmado que lleva al cliente a Redsys.
 * 2. settle(): procesa la respuesta FIRMADA de Redsys (notificación o retorno a urlOk/urlKo).
 *    Es idempotente: si llegan las dos, solo cuenta la primera.
 */
class RedsysGateway
{
    public function __construct(
        private OrderService $orders,
        private EventLogger $events,
    ) {}

    /**
     * @return array{url: string, fields: array<string, string>, payment: Payment}
     *
     * @throws CheckoutException si el pedido ya no está pendiente de pago
     */
    public function start(Order $order): array
    {
        $merchant = $this->merchant();

        $payment = DB::transaction(function () use ($order) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            if ($order->status !== Order::CREATED) {
                throw new CheckoutException('Este pedido ya no está pendiente de pago.');
            }

            $payment = $order->payments()->create([
                'method' => Payment::METHOD_REDSYS,
                'status' => Payment::PENDING,
                'amount' => $order->total,
                'reference' => 'RSY-'.Str::upper(Str::random(10)),
            ]);

            // Redsys exige 4-12 caracteres empezando por 4 dígitos y uno distinto por intento:
            // fecha (aammdd) + id del pago con 6 cifras → 12 dígitos únicos
            $payment->update(['gateway_order' => now()->format('ymd').str_pad((string) ($payment->id % 1000000), 6, '0', STR_PAD_LEFT)]);

            return $payment;
        });

        $params = new Parameters;
        $params->amount = (string) $this->cents($payment->amount);
        $params->order = $payment->gateway_order;
        $params->merchantName = config('services.redsys.merchant_name');
        $params->productDescription = "Pedido {$order->reference}";
        $params->consumerLanguage = '001'; // español
        $params->merchantUrl = route('redsys.notify');
        $params->urlOk = route('redsys.ok', $payment);
        $params->urlKo = route('redsys.ko', $payment);

        return [
            'url' => Endpoints::$redirect[$merchant->env],
            'fields' => RequestData::generate($merchant, $params),
            'payment' => $payment,
        ];
    }

    /**
     * Procesa una respuesta de Redsys (Ds_SignatureVersion, Ds_MerchantParameters, Ds_Signature).
     *
     * @throws RuntimeException si la firma no es válida, el pedido de Redsys no existe o el importe no coincide
     */
    public function settle(array $data): Payment
    {
        foreach (['Ds_SignatureVersion', 'Ds_MerchantParameters', 'Ds_Signature'] as $field) {
            if (empty($data[$field])) {
                throw new RuntimeException("Respuesta de Redsys incompleta: falta {$field}.");
            }
        }

        // Verifica la firma con la clave del comercio: si alguien la ha manipulado, lanza una excepción
        $params = Parameters::digest($this->merchant(), $data);

        $payment = Payment::where('gateway_order', (string) $params->order)->first()
            ?? throw new RuntimeException("Pedido de Redsys desconocido: {$params->order}.");

        if ((int) $params->amount !== $this->cents($payment->amount)) {
            throw new RuntimeException("El importe de Redsys ({$params->amount}) no coincide con el del pago {$payment->reference}.");
        }

        return DB::transaction(function () use ($payment, $params) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->status !== Payment::PENDING) {
                return $payment; // ya procesado (llegaron la notificación y el retorno): no se repite
            }

            $order = Order::lockForUpdate()->findOrFail($payment->order_id);
            $code = (string) $params->codResponse;
            $approved = ctype_digit($code) && (int) $code <= 99; // 0000-0099 = operación autorizada

            $payment->update([
                'status' => $approved ? Payment::APPROVED : Payment::DECLINED,
                'response_code' => $code,
                'authorisation_code' => $params->authorisationCode ? trim((string) $params->authorisationCode) : null,
                'card_last4' => $params->cardNumber ? substr(preg_replace('/\D/', '', (string) $params->cardNumber), -4) ?: null : null,
                'gateway_response' => $params->toArrayOut(),
            ]);

            $this->events->log(EventLogger::PAYMENT_SIMULATED, [
                'reference' => $order->reference,
                'payment_reference' => $payment->reference,
                'gateway' => 'redsys',
                'gateway_order' => $payment->gateway_order,
                'method' => $payment->method,
                'status' => $payment->status,
                'response_code' => $code,
                'amount' => (float) $payment->amount,
            ], $order->id);

            // Solo se cobra el pedido si sigue esperando el pago
            if ($approved && $order->status === Order::CREATED) {
                $this->orders->markPaid($order, 'redsys');
            }

            return $payment;
        });
    }

    private function merchant(): Merchant
    {
        return Merchant::initWithApiKey(config('services.redsys.api_key'))
            ?? throw new RuntimeException('Falta REDSYS_API_KEY (o no es válida) en el .env.');
    }

    /** Importe en céntimos, como lo espera Redsys. */
    private function cents(string|float $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
