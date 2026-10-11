<?php

namespace Tests\Concerns;

use App\Models\Order;
use App\Models\Payment;
use Redsys\Merchant;
use Redsys\Signature;
use Redsys\Utils;

/**
 * Simula al TPV de Redsys en los tests: inicia el pago por la ruta real y fabrica la
 * respuesta FIRMADA con la clave de pruebas, igual que la firmaría Redsys.
 */
trait FakesRedsys
{
    /** Inicia el pago con Redsys (como el cliente) y devuelve el pago pendiente creado. */
    protected function startRedsysPayment(Order $order): Payment
    {
        $this->post(route('orders.pay.store', $order), ['method' => 'redsys'])->assertOk();

        return $order->payments()->where('method', Payment::METHOD_REDSYS)->latest('id')->firstOrFail();
    }

    /**
     * Respuesta de Redsys firmada (Ds_SignatureVersion, Ds_MerchantParameters, Ds_Signature).
     *
     * @param  array<string, string>  $overrides  campos Ds_* a cambiar (p. ej. Ds_Response)
     */
    protected function redsysResponse(Payment $payment, string $response = '0000', array $overrides = []): array
    {
        $merchant = Merchant::initWithApiKey(config('services.redsys.api_key'));

        $parameters = array_merge([
            'Ds_Date' => now()->format('d/m/Y'),
            'Ds_Hour' => now()->format('H:i'),
            'Ds_Amount' => (string) (int) round($payment->amount * 100),
            'Ds_Currency' => '978',
            'Ds_Order' => $payment->gateway_order,
            'Ds_MerchantCode' => $merchant->fuc,
            'Ds_Terminal' => $merchant->terminal,
            'Ds_Response' => $response,
            'Ds_TransactionType' => '0',
            'Ds_SecurePayment' => '1',
            'Ds_AuthorisationCode' => $response === '0000' ? '123456' : '',
            'Ds_Card_Number' => '454881******0003',
        ], $overrides);

        $encoded = Utils::base64_url_encode_safe(json_encode($parameters));

        return [
            'Ds_SignatureVersion' => 'HMAC_SHA512_V2',
            'Ds_MerchantParameters' => $encoded,
            'Ds_Signature' => Signature::createMerchantSignature($merchant->kc, $encoded, $parameters['Ds_Order'], 'HMAC_SHA512_V2'),
        ];
    }

    /** Envía la notificación de Redsys (servidor a servidor). */
    protected function notifyRedsys(Payment $payment, string $response = '0000', array $overrides = [])
    {
        return $this->post(route('redsys.notify'), $this->redsysResponse($payment, $response, $overrides));
    }
}
