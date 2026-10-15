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
            $table->string('session_id')->nullable();

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

            $table->string('payment_method')->nullable();
            $table->string('gateway')->nullable();
            $table->string('response_code')->nullable();

            $table->string('discount_code')->nullable();

            // Extras secundarios
            $table->json('payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};