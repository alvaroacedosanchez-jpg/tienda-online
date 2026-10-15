<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->string('session_id')->nullable()->index(); // adoptGuestEvents() busca por sesión

            // Claves foráneas (con nullOnDelete)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('support_ticket_id')->nullable()->constrained('support_tickets')->nullOnDelete();

            // Columnas tipadas cuantitativas y de estado
            $table->integer('quantity')->nullable();
            $table->integer('previous_quantity')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->nullable();

            $table->string('status')->nullable();
            $table->string('previous_status')->nullable();
            $table->string('origin')->nullable();

            // Método de pago, pasarela, código de respuesta y código de descuento no se guardan aquí:
            // se obtienen por relación (payments por payment_id, orders por order_id)

            // Extras secundarios
            $table->json('payload')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
