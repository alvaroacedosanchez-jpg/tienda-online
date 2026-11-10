<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bandeja de entrada de la integración: cada aviso que n8n trae del back-office (POST /api/integration/sync).
 * El índice único de event_id es la idempotencia: un mismo aviso nunca se aplica dos veces.
 * Se guardan también los rechazos (404, 422), con la respuesta que se dio, como rastro de la gestión de errores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_inbox', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 100)->unique();       // evt_bo_… del back-office
            $table->string('change_type', 20)->nullable();   // ORDER_STATUS | STOCK
            $table->string('entity_ref', 60)->nullable();    // order_ref o product_code
            $table->string('result', 30);                    // updated | unchanged | not_found | invalid | invalid_transition
            $table->unsignedSmallInteger('http_status');
            $table->json('payload');                         // lo que llegó
            $table->json('response');                        // lo que se respondió (se repite en los duplicados)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_inbox');
    }
};
