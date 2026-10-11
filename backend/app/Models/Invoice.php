<?php

namespace App\Models;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'shipping' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** PDF de la factura (se usa en la descarga de "Mis facturas" y como adjunto del correo). */
    public function pdf(): DomPDF
    {
        $this->loadMissing('order.items');

        return Pdf::loadView('invoices.pdf', [
            'invoice' => $this,
            'company' => config('shop.company'),
        ])->setOption('isFontSubsettingEnabled', true); // incrusta solo las letras usadas: PDF mucho más ligero
    }

    /**
     * Emite la factura de un pedido pagado. Debe llamarse dentro de una transacción:
     * el bloqueo sobre las facturas del año hace que dos pagos simultáneos no
     * obtengan el mismo número, y así la numeración es correlativa y sin huecos.
     */
    public static function issueFor(Order $order): self
    {
        $year = (int) now()->format('Y');

        $last = static::where('year', $year)->orderByDesc('sequence')->lockForUpdate()->first();
        $sequence = ($last?->sequence ?? 0) + 1;

        $customer = $order->customer;

        return static::create([
            'order_id' => $order->id,
            'number' => sprintf('FAC-%d-%04d', $year, $sequence),
            'year' => $year,
            'sequence' => $sequence,
            'issued_at' => now(),
            'billing_name' => $customer->name,
            'billing_email' => $customer->email,
            'billing_address' => $customer->address,
            'billing_city' => $customer->city,
            'billing_postal_code' => $customer->postal_code,
            'subtotal' => $order->subtotal,
            'discount' => $order->discount,
            'shipping' => $order->shipping,
            'tax' => $order->tax,
            'total' => $order->total,
        ]);
    }
}
