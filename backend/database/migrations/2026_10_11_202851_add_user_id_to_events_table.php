<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Usuario que provocó el evento (null si era un invitado).
     * Permite analizar el comportamiento por cliente, p. ej. carritos abandonados (Tarea 2).
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('session_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
