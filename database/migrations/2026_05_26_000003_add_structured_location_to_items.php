<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Endereçamento estruturado dos materiais.
 *
 *   + aisle    (corredor)
 *   + shelf    (prateleira)
 *   + position (posição)
 *
 * O campo `location` (texto livre) é preservado e passa a funcionar como "complemento".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('aisle', 30)->nullable()->after('location');
            $table->string('shelf', 30)->nullable()->after('aisle');
            $table->string('position', 30)->nullable()->after('shelf');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['aisle', 'shelf', 'position']);
        });
    }
};
