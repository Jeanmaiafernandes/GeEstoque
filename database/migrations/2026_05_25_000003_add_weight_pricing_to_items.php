<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona suporte à precificação por peso (kg) para chapas e tubos da Metalúrgica.
 *
 * Novos campos em `items`:
 *  - priced_by_weight : boolean — ativa a lógica de peso para este material.
 *  - unit_weight      : kg por peça (ex.: 12,5 kg/chapa).
 *  - price_per_kg     : R$/kg (base de cálculo do preço unitário).
 *
 * O `unit_price` continua existindo e, para itens por peso, é auto-calculado
 * pelo hook do model (unit_weight × price_per_kg), mantendo compatibilidade
 * total com relatórios e valor em estoque já existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->boolean('priced_by_weight')->default(false)->after('max_price');
            $table->decimal('unit_weight', 12, 3)->nullable()->after('priced_by_weight');
            $table->decimal('price_per_kg', 12, 4)->nullable()->after('unit_weight');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['priced_by_weight', 'unit_weight', 'price_per_kg']);
        });
    }
};
