<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Integration\EventPayloadBuilder;
use Illuminate\Http\JsonResponse;

/**
 * Lectura para la sincronización inicial tienda → back-office (flujo manual de n8n).
 * Solo lectura y con el mismo token que /api/integration/sync. La tienda manda en el catálogo,
 * el stock y los pedidos: es el sistema que los crea (contrato, apartado 8).
 */
class IntegrationExportController extends Controller
{
    /**
     * GET /api/integration/catalogo · Ya en el formato de POST /api/v1/inventory/import del back-office.
     * Una fila por tamaño (SAL-002-100ML) o por producto si no tiene tamaños (SAL-004).
     */
    public function catalog(EventPayloadBuilder $builder): JsonResponse
    {
        $products = [];

        foreach (Product::with(['category', 'variants'])->orderBy('sku')->get() as $product) {
            $rows = $product->variants->isEmpty()
                ? [[null, $product->price, $product->stock]]
                : $product->variants->sortBy('id')->map(fn (ProductVariant $v) => [$v->size, $v->price, $v->stock])->all();

            foreach ($rows as [$size, $price, $stock]) {
                $products[] = [
                    'sku' => $builder->productCode($product->sku, $size),
                    'product_name' => $product->name,
                    'category' => $product->category?->slug,
                    'variant_size' => $size,
                    'unit_price' => round((float) $price, 2),
                    'stock_available' => max(0, (int) $stock),
                ];
            }
        }

        return response()->json(['count' => count($products), 'products' => $products], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * GET /api/integration/pedidos · Todos los pedidos, del más antiguo al más reciente, con la misma
     * "foto" que llevan los eventos (order, customer, shipping_address, lines) y su fecha real.
     */
    public function orders(EventPayloadBuilder $builder): JsonResponse
    {
        $orders = Order::with(['customer', 'items.product', 'payments'])->orderBy('created_at')->orderBy('id')->get()
            ->map(fn (Order $order) => [
                'reference' => $order->reference,
                'created_at' => $order->created_at->toIso8601String(),
                // Cómo se pagó (último pago aprobado): los pedidos de la Tarea 1 pueden ser de transferencia simulada
                'payment_method' => match ($order->payments->where('status', Payment::APPROVED)->sortByDesc('id')->first()?->method) {
                    Payment::METHOD_REDSYS => 'redsys_sandbox',
                    Payment::METHOD_TRANSFER => 'transferencia_simulada',
                    default => null,
                },
                'data' => $builder->orderSnapshot($order),
            ])->values();

        return response()->json(['count' => $orders->count(), 'orders' => $orders], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
