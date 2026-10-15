<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private CartService $cart,
        private EventLogger $events,
        private ShopMailer $mailer,
    ) {}

    public function createFromCart(User $user, array $shipping): Order
    {
        $cartItems = $this->cart->items();
        if ($cartItems->isEmpty()) {
            throw new CheckoutException('El carrito está vacío.');
        }

        $order = DB::transaction(function () use ($user, $shipping, $cartItems) {
            $customer = Customer::firstOrCreate(['user_id' => $user->id], [
                'name' => $shipping['name'],
                'email' => $user->email,
                'phone' => $shipping['phone'] ?? null,
                'address' => $shipping['address'],
                'city' => $shipping['city'],
                'postal_code' => $shipping['postal_code'],
            ]);

            $products = Product::whereIn('id', $cartItems->pluck('product.id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $variants = ProductVariant::whereIn('id', $cartItems->pluck('variant.id')->filter())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $items = $cartItems->map(function (array $item) use ($products, $variants) {
                /** @var Product|null $product */
                $product = $products->get($item['product']->id);
                if (! $product || ! $product->active) {
                    throw new CheckoutException("«{$item['product']->name}» ya no está disponible.");
                }

                $variant = null;
                if (! empty($item['variant'])) {
                    /** @var ProductVariant|null $variant */
                    $variant = $variants->get($item['variant']->id);
                    if (! $variant || (int) $variant->product_id !== (int) $product->id) {
                        throw new CheckoutException("«{$product->name} ({$item['variant']->size})» ya no está disponible.");
                    }
                }

                $stockSource = $variant ?? $product;
                if ($stockSource->stock < $item['quantity']) {
                    $label = $variant ? "{$product->name} ({$variant->size})" : $product->name;
                    throw new CheckoutException("No hay stock suficiente de «{$label}».");
                }

                return [
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => $item['quantity'],
                    'line_total' => round($stockSource->price * $item['quantity'], 2),
                ];
            });
            $summary = $this->cart->summaryFor($items);

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
                'shipping_name' => $shipping['name'],
                'shipping_phone' => $shipping['phone'] ?? null,
                'shipping_address' => $shipping['address'],
                'shipping_city' => $shipping['city'],
                'shipping_postal_code' => $shipping['postal_code'],
            ]);

            foreach ($items as $item) {
                $product = $item['product'];
                $variant = $item['variant'];

                ($variant ?? $product)->decrement('stock', $item['quantity']);

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'variant_size' => $variant?->size,
                    'unit_price' => ($variant ?? $product)->price,
                    'quantity' => $item['quantity'],
                    'line_total' => $item['line_total'],
                ]);
            }

            $this->events->log(
                EventLogger::ORDER_CREATED,
                [
                    'order_id'      => $order->id,
                    'quantity'      => $items->sum('quantity'),
                    'amount'        => (float) $order->total,
                    'discount_code' => $order->discount_code,
                ],
                [
                    'reference' => $order->reference,
                ]
            );

            $this->cart->clear();

            return $order;
        });

        return $order;
    }

    public function markPaid(Order $order, string $origin = 'payment'): Invoice
    {
        $this->changeStatus($order, Order::PAID, $origin);

        $invoice = Invoice::issueFor($order);
        $this->events->log(
            EventLogger::INVOICE_ISSUED,
            [
                'order_id'   => $order->id,
                'invoice_id' => $invoice->id,
                'amount'     => (float) $invoice->total,
            ],
            [
                'reference'      => $order->reference,
                'invoice_number' => $invoice->number,
            ]
        );

        $this->changeStatus($order, Order::PENDING_PREPARATION, $origin);

        $this->mailer->orderPaid($order);

        return $invoice;
    }

    public function cancelUnpaid(Order $order, string $origin = 'payment'): bool
    {
        return DB::transaction(function () use ($order, $origin) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            if ($order->status !== Order::CREATED) {
                return false;
            }

            $this->changeStatus($order, Order::CANCELLED, $origin);
            $this->cart->restoreOrder($order);

            return true;
        });
    }

    public function changeStatus(Order $order, string $newStatus, string $origin = 'system'): void
    {
        $old = $order->status;
        if ($old === $newStatus) {
            return;
        }

        DB::transaction(function () use ($order, $old, $newStatus, $origin) {
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

            if ($newStatus === Order::SHIPPED) {
                $this->mailer->orderShipped($order);
            }

            $this->events->log(
                EventLogger::ORDER_STATUS_CHANGED,
                [
                    'order_id'        => $order->id,
                    'previous_status' => $old,
                    'status'          => $newStatus,
                    'origin'          => $origin,
                ],
                [
                    'reference' => $order->reference,
                ]
            );
        });
    }

    private function newReference(): string
    {
        do {
            $ref = 'PQ-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::where('reference', $ref)->exists());

        return $ref;
    }
}