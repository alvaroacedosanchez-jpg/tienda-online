<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Imagen de cada producto y de cada variante (ruta relativa a public/).
     * Se guarda en BD para poder sustituir la ilustración por una foto sin tocar código.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('image')->nullable()->after('emoji');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('image')->nullable()->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('image');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
