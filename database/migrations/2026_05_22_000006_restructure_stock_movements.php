<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cria a tabela de linhas e copia os movimentos antigos (1 movimento = 1 linha).
        if (! Schema::hasTable('stock_movement_items')) {
            Schema::create('stock_movement_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_movement_id')->constrained()->cascadeOnDelete();
                $table->foreignId('item_id')->constrained()->restrictOnDelete();
                $table->decimal('quantity', 12, 3);
                $table->decimal('balance_after', 12, 3);
                $table->timestamps();

                $table->index('item_id');
            });

            if (Schema::hasColumn('stock_movements', 'item_id')) {
                DB::table('stock_movement_items')->insertUsing(
                    ['stock_movement_id', 'item_id', 'quantity', 'balance_after', 'created_at', 'updated_at'],
                    DB::table('stock_movements')->select('id', 'item_id', 'quantity', 'balance_after', 'created_at', 'updated_at'),
                );
            }
        }

        // Remove as colunas antigas do cabeçalho (FK primeiro, depois colunas).
        if (Schema::hasColumn('stock_movements', 'item_id')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropForeign(['item_id']);
                $table->dropIndex(['item_id', 'created_at']);
            });

            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropColumn(['item_id', 'quantity', 'balance_after']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('item_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('balance_after', 12, 3)->default(0);
        });

        Schema::dropIfExists('stock_movement_items');
    }
};
