<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Adicionar novas colunas apenas se elas não existirem
            if (!Schema::hasColumn('suppliers', 'supplier_name')) {
                $table->string('supplier_name');
            }
            if (!Schema::hasColumn('suppliers', 'supplier_contact')) {
                $table->string('supplier_contact');
            }
            if (!Schema::hasColumns('suppliers', ['created_at', 'updated_at'])) {
                $table->timestamps();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supliers', function (Blueprint $table) {
            //
        });
    }
};
