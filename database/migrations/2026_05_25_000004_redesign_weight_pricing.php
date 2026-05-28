<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Redesign da precificação por peso:
 *
 * CATEGORIA
 *   + is_weight_category (bool) — marca categorias cujos materiais são
 *     precificados por kg (ex.: Tubos, Chapas). Ao salvar um material,
 *     o campo priced_by_weight é sincronizado automaticamente desta flag.
 *
 * ITEM
 *   + max_stock (decimal) — estoque máximo desejado em kg.
 *                            Gera alerta "acima do máximo" quando ultrapassado.
 *   - unit_weight          — removido: quando a unidade já é KG, não há
 *                            "peso por peça" — o próprio saldo é o peso.
 *
 * Fórmula corrigida: stock_value = current_stock × price_per_kg
 * (unit_price = price_per_kg é auto-setado no hook saving do model Item)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_weight_category')->default(false)->after('name');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->decimal('max_stock', 12, 3)->nullable()->after('min_stock');
            $table->dropColumn('unit_weight');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('is_weight_category');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('max_stock');
            $table->decimal('unit_weight', 12, 3)->nullable()->after('priced_by_weight');
        });
    }
};
