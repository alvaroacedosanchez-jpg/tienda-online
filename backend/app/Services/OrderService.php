<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class OrderService
{
    public function __construct(
        private CartService $cart,
        private EventLogger $events,
    ) {}

    /**
     * Crea el pedido a partir del carrito. Descuenta stock dentro de una transacción.
     *
     * @throws RuntimeException si el carrito está vacío o no hay stock suficiente
     */
    public function createFromCart(array $customerData): Order
    {
        $items = $this->cart->items();
        if ($items->isEmpty()) {
            throw new RuntimeException('El carrito está vacío.');
        }
        $summary = $this->cart->summary();

        $order = DB::transaction(function () use ($customerData, $items, $summary) {
            $customer = Customer::create($customerData);

            $order = Order::create([
                'reference' => $this->newReference(),
                'customer_id' => $customer->id,
                'status' => Order::CREATED,
                'subtotal' => $summary['subtotal'],
                'discount' => $summary['discount'],
                'discount_code' => $summary['discount_code'],
                'shipping' => $summary['shipping'],
                'tax' => $summary['tax'],
                'total' => $summary['total'],
            ]);

            foreach ($items as $item) {
                /** @var Product $product */
                $product = $item['product'];
                $variant = $item['variant'] ?? null;

                if ($variant) {
                    $variantModel = ProductVariant::lockForUpdate()->find($variant->id);
                    if (!$variantModel || $variantModel->stock < $item['quantity']) {
                        throw new RuntimeException("No hay stock suficiente de «{$product->name} ({$variant->size})».");
                    }
                    $variantModel->decrement('stock', $item['quantity']);

                    $unitPrice = $variantModel->price;
                    $variantId = $variantModel->id;
                    $variantSize = $variantModel->size;
                } else {
                    $productModel = Product::lockForUpdate()->findOrFail($product->id);
                    if ($productModel->stock < $item['quantity']) {
                        throw new RuntimeException("No hay stock suficiente de «{$product->name}».");
                    }
                    $productModel->decrement('stock', $item['quantity']);

                    $unitPrice = $productModel->price;
                    $variantId = null;
                    $variantSize = null;
                }

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variantId,
                    'product_name' => $product->name,
                    'variant_size' => $variantSize,
                    'unit_price' => $unitPrice,
                    'quantity' => $item['quantity'],
                    'line_total' => $item['line_total'],
                ]);
            }

            return $order;
        });

        $this->events->log(EventLogger::ORDER_CREATED, [
            'reference' => $order->reference,
            'total' => (float) $order->total,
            'items' => $items->sum('quantity'),
            'discount_code' => $order->discount_code,
        ], $order->id);

        $this->cart->clear();

        return $order;
    }

    /** Cambia el estado, registra el evento y devuelve stock si se cancela. */
    public function changeStatus(Order $order, string $newStatus, string $origin = 'system'): void
    {
        $old = $order->status;
        if ($old === $newStatus) {
            return;
        }

        DB::transaction(function () use ($order, $newStatus) {
            if ($newStatus === Order::CANCELLED) {
                foreach ($order->items as $item) {
                    if ($item->product_variant_id) {
                        ProductVariant::whereKey($item->product_variant_id)->increment('stock', $item->quantity);
                    } else {
                        Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                    }
                }
            }
            $order->update(['status' => $newStatus]);
        });

        $this->events->log(EventLogger::ORDER_STATUS_CHANGED, [
            'reference' => $order->reference,
            'from' => $old,
            'to' => $newStatus,
            'origin' => $origin,
        ], $order->id);
    }

    private function newReference(): string
    {
        do {
            $ref = 'PQ-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::where('reference', $ref)->exists());

        return $ref;
    }
}