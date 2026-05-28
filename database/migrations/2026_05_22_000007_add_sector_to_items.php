<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Setor responsável pelo material (Almoxarifado, Metalúrgica, etc.).
            // Sem FK no SQLite via ALTER; integridade tratada na aplicação.
            $table->unsignedBigInteger('sector_id')->nullable()->after('category_id');
            $table->index('sector_id');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['sector_id']);
            $table->dropColumn('sector_id');
        });
    }
};
