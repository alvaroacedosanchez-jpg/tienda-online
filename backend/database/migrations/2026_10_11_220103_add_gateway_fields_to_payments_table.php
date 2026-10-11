<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos de los pagos hechos en el TPV Virtual de Redsys (entorno de pruebas).
     * Un pago de Redsys nace en estado "pending" y se resuelve con la respuesta firmada.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Número de pedido enviado a Redsys (4-12 caracteres, los 4 primeros numéricos; uno por intento)
            $table->string('gateway_order', 12)->nullable()->unique()->after('reference');
            $table->string('response_code', 4)->nullable()->after('gateway_order');      // Ds_Response
            $table->string('authorisation_code', 12)->nullable()->after('response_code'); // Ds_AuthorisationCode
            $table->json('gateway_response')->nullable()->after('authorisation_code');    // respuesta verificada (evidencia)
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['gateway_order']);
            $table->dropColumn(['gateway_order', 'response_code', 'authorisation_code', 'gateway_response']);
        });
    }
};
