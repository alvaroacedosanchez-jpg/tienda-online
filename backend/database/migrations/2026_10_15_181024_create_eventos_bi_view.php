<?php

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // estado_texto se genera desde las constantes de los modelos (única fuente de verdad).
        // Los códigos de pedido y de pago no se repiten, así que caben en un solo CASE.
        // Si cambian los textos, hay que volver a crear la vista.
        $pdo = DB::getPdo();
        $whens = '';
        foreach (Order::STATUS_LABELS + Payment::STATUS_LABELS as $code => $label) {
            $whens .= 'WHEN '.$pdo->quote($code).' THEN '.$pdo->quote($label).' ';
        }

        DB::statement("
            CREATE VIEW eventos_bi AS
            SELECT 
                e.id AS evento_id,
                e.occurred_at AS fecha,
                e.type AS tipo,
                CASE e.type
                    WHEN 'product.viewed' THEN 'Producto visto'
                    WHEN 'cart.item_added' THEN 'Añadido al carrito'
                    WHEN 'cart.item_updated' THEN 'Carrito actualizado'
                    WHEN 'cart.item_removed' THEN 'Producto quitado del carrito'
                    WHEN 'checkout.started' THEN 'Iniciado checkout'
                    WHEN 'order.created' THEN 'Pedido creado'
                    WHEN 'payment.simulated' THEN 'Pago procesado'
                    WHEN 'order.status_changed' THEN 'Cambio estado pedido'
                    WHEN 'invoice.issued' THEN 'Factura emitida'
                    WHEN 'support.requested' THEN 'Soporte solicitado'
                    ELSE e.type
                END AS descripcion,
                e.session_id AS sesion,
                e.user_id AS usuario_id,
                COALESCE(u.is_admin, 0) AS es_admin,
                c.name AS cliente,
                c.city AS ciudad_cliente,
                e.product_id AS producto_id,
                p.name AS producto,
                p.sku AS sku,
                cat.name AS categoria,
                pv.size AS tamano,
                e.quantity AS cantidad,
                e.previous_quantity AS cantidad_anterior,
                e.unit_price AS precio_unitario,
                e.amount AS importe,
                e.order_id AS pedido_id,
                o.reference AS pedido_referencia,
                o.status AS pedido_estado_actual,
                e.status AS estado,
                e.previous_status AS estado_anterior,
                e.origin AS origen,
                CASE e.status {$whens}ELSE e.status END AS estado_texto,
                e.payment_method AS metodo_pago,
                e.gateway AS pasarela,
                e.response_code AS codigo_respuesta,
                inv.number AS factura_numero,
                e.support_ticket_id AS ticket_id,
                st.subject AS ticket_asunto,
                e.discount_code AS codigo_descuento
            FROM events e
            LEFT JOIN users u ON e.user_id = u.id
            LEFT JOIN customers c ON e.user_id = c.user_id
            LEFT JOIN products p ON e.product_id = p.id
            LEFT JOIN categories cat ON p.category_id = cat.id
            LEFT JOIN product_variants pv ON e.product_variant_id = pv.id
            LEFT JOIN orders o ON e.order_id = o.id
            LEFT JOIN invoices inv ON e.invoice_id = inv.id
            LEFT JOIN support_tickets st ON e.support_ticket_id = st.id
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS eventos_bi");
    }
};