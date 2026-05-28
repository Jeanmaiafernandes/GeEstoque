<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Requisição de material com aprovação.
 *
 *   requisitions       — um pedido (status pendente/atendida/recusada/cancelada).
 *   requisition_items  — itens pedidos e quantidades.
 *
 * Ao aprovar, gera-se uma saída (stock_movement) com o setor solicitante como destino
 * (centro de custo) e o vínculo é guardado em stock_movement_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requester_id')->nullable();
            $table->unsignedBigInteger('sector_id')->nullable();
            $table->string('status')->default('pendente');
            $table->string('notes')->nullable();
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->string('decision_notes')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->unsignedBigInteger('stock_movement_id')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('requisition_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requisition_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 12, 3);
            $table->timestamps();

            $table->index('requisition_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_items');
        Schema::dropIfExists('requisitions');
    }
};
