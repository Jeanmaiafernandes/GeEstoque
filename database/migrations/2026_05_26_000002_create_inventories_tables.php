<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventário rotativo guiado.
 *
 *   inventories      — uma sessão de contagem (status aberto/finalizado/cancelado).
 *   inventory_items  — itens da sessão, com saldo do sistema (snapshot) e quantidade contada.
 *
 * FKs sem constraint de banco (padrão do projeto p/ SQLite); integridade na aplicação.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('sector_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('status')->default('aberto');
            $table->string('notes')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('system_qty', 12, 3)->default(0);
            $table->decimal('counted_qty', 12, 3)->nullable();
            $table->timestamps();

            $table->unique(['inventory_id', 'item_id']);
            $table->index('inventory_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventories');
    }
};
