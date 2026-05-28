<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Índice perdido na reestruturação (000006): movimentações são ordenadas e
        // filtradas por data com frequência (histórico, painel, relatórios).
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index('created_at');
        });

        // Filtros recorrentes na listagem de materiais e no painel.
        Schema::table('items', function (Blueprint $table) {
            $table->index('active');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['active']);
            $table->dropIndex(['type']);
        });
    }
};
