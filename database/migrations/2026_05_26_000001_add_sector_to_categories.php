<?php

use App\Support\CategorySectorAssigner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categorias por setor (hierarquia).
 *
 *   + sector_id (nullable) — setor dono da categoria. NULL = categoria global
 *     (visível a todos os setores).
 *
 * O nome deixa de ser único globalmente: passa a ser único POR setor (validado na
 * aplicação), pois "Consumíveis" pode existir em mais de um setor.
 *
 * Migração de dados "pelo uso": cada categoria existente vai para o setor que a utiliza;
 * categorias usadas por vários setores (ou nenhum) permanecem globais.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('sector_id')->nullable()->after('name');
            $table->index('sector_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_name_unique');
        });

        // Atribuição automática por uso (roda apenas se houver dados; em testes o banco está vazio).
        CategorySectorAssigner::run();
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['sector_id']);
            $table->dropColumn('sector_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->unique('name');
        });
    }
};
