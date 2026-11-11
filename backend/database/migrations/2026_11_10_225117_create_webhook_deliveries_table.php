<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bandeja de salida (patrón outbox) de los eventos que la tienda envía a n8n.
 * La fila se crea en la misma transacción que el cambio de negocio; el envío va después y se reintenta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();                 // el del sobre: el mismo en todos los reintentos
            $table->string('type', 50);                          // order.created, payment.simulated…
            $table->foreignId('source_event_id')->nullable()     // fila de `events` que lo originó
                ->constrained('events')->nullOnDelete();
            $table->longText('body')->nullable();                // JSON exacto; se congela en el primer intento
            $table->string('status', 20)->default('pendiente');  // pendiente | enviado | fallido | descartado
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->unsignedSmallInteger('last_status_code')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_attempt_at']);        // lo que consulta el comando de reintentos
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
