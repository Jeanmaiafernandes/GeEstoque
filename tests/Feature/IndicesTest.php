<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Garante a existência dos índices que sustentam a performance das consultas mais frequentes
 * (ordenação/filtro por data das movimentações e filtros da listagem de materiais).
 */
class IndicesTest extends TestCase
{
    use RefreshDatabase;

    private function temIndiceEm(string $table, string $column): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (in_array($column, $index['columns'], true)) {
                return true;
            }
        }

        return false;
    }

    public function test_stock_movements_tem_indice_em_created_at(): void
    {
        $this->assertTrue(
            $this->temIndiceEm('stock_movements', 'created_at'),
            'stock_movements deveria ter índice em created_at (ordenação/filtro por data).',
        );
    }

    public function test_items_tem_indice_em_active(): void
    {
        $this->assertTrue(
            $this->temIndiceEm('items', 'active'),
            'items deveria ter índice em active (filtro de listagem e painel).',
        );
    }

    public function test_items_tem_indice_em_type(): void
    {
        $this->assertTrue(
            $this->temIndiceEm('items', 'type'),
            'items deveria ter índice em type (filtro de listagem).',
        );
    }
}
