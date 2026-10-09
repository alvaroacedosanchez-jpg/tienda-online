<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private CartService $cart,
        private EventLogger $events,
    ) {}

    /**
     * Crea el pedido del usuario a partir del carrito (Order-to-Cash).
     *
     * Todo ocurre en una única transacción: ficha de cliente, pedido, líneas, stock
     * y evento order.created. Si cualquier paso falla, no se guarda nada y el
     * carrito se conserva para poder reintentar.
     *
     * @param  array{name: string, phone: ?string, address: string, city: string, postal_code: string}  $shipping
     *
     * @throws CheckoutException si el carrito está vacío, un producto ya no está disponible o no hay stock
     */
    public function createFromCart(User $user, array $shipping): Order
    {
        $cartItems = $this->cart->items();
        if ($cartItems->isEmpty()) {
            throw new CheckoutException('El carrito está vacío.');
        }

        $order = DB::transaction(function () use ($user, $shipping, $cartItems) {
            // 1. Ficha de cliente: se crea en la primera compra y después se reutiliza tal cual
            $customer = Customer::firstOrCreate(['user_id' => $user->id], [
                'name' => $shipping['name'],
                'email' => $user->email,
                'phone' => $shipping['phone'] ?? null,
                'address' => $shipping['address'],
                'city' => $shipping['city'],
                'postal_code' => $shipping['postal_code'],
            ]);

            // 2. Bloquear los productos (en orden de id para evitar interbloqueos) y releer precio y stock
            $products = Product::whereIn('id', $cartItems->pluck('product.id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // 3. Recalcular las líneas con los datos bloqueados, no con los leídos antes de la transacción
            $items = $cartItems->map(function (array $item) use ($products) {
                /** @var Product|null $product */
                $product = $products->get($item['product']->id);
                if (! $product || ! $product->active) {
                    throw new CheckoutException("«{$item['product']->name}» ya no está disponible.");
                }
                if ($product->stock < $item['quantity']) {
                    throw new CheckoutException("No hay stock suficiente de «{$product->name}».");
                }

                return [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'line_total' => round($product->price * $item['quantity'], 2),
                ];
            });
            $summary = $this->cart->summaryFor($items);

            // 4. Pedido con su propia copia de la dirección de envío
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

            // 5. Líneas (copia histórica de nombre y precio) y descuento de stock
            foreach ($items as $item) {
                $product = $item['product'];
                $product->decrement('stock', $item['quantity']);

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $item['quantity'],
                    'line_total' => $item['line_total'],
                ]);
            }

            // 6. Evento dentro de la transacción: o se guarda todo o nada
            $this->events->log(EventLogger::ORDER_CREATED, [
                'reference' => $order->reference,
                'total' => (float) $order->total,
                'items' => $items->sum('quantity'),
                'discount_code' => $order->discount_code,
            ], $order->id);

            return $order;
        });

        // El carrito vive en la sesión (no en la base de datos): se vacía solo si la transacción se ha confirmado
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

        // Estado, stock y evento en la misma transacción
        DB::transaction(function () use ($order, $old, $newStatus, $origin) {
            if ($newStatus === Order::CANCELLED) {
                foreach ($order->items as $item) {
                    Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                }
            }
            $order->update(['status' => $newStatus]);

            $this->events->log(EventLogger::ORDER_STATUS_CHANGED, [
                'reference' => $order->reference,
                'from' => $old,
                'to' => $newStatus,
                'origin' => $origin,
            ], $order->id);
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
