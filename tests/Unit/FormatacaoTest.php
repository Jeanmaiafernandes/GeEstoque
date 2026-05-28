<?php

namespace Tests\Unit;

use App\Enums\ItemType;
use App\Enums\MovementType;
use App\Enums\UserRole;
use App\Models\Item;
use Tests\TestCase;

class FormatacaoTest extends TestCase
{
    public function test_rotulos_dos_enums(): void
    {
        $this->assertSame('Administrador', UserRole::Admin->label());
        $this->assertSame('Operador', UserRole::Operador->label());
        $this->assertSame('Matéria-prima', ItemType::MateriaPrima->label());
        $this->assertSame('Saída', MovementType::Saida->label());
    }

    public function test_options_dos_enums(): void
    {
        $this->assertSame(['admin' => 'Administrador', 'operador' => 'Operador'], UserRole::options());
        $this->assertCount(2, ItemType::options());
        $this->assertCount(3, MovementType::options());
    }

    public function test_formatacao_de_saldo_em_pt_br(): void
    {
        $this->assertSame('1.500', (new Item)->forceFill(['current_stock' => 1500])->current_stock_label);
        $this->assertSame('2,5', (new Item)->forceFill(['current_stock' => 2.5])->current_stock_label);
        $this->assertSame('2', (new Item)->forceFill(['current_stock' => 2])->current_stock_label);
    }

    public function test_regra_de_estoque_minimo(): void
    {
        $this->assertFalse((new Item)->forceFill(['min_stock' => 0, 'current_stock' => 0])->isBelowMinimum());
        $this->assertTrue((new Item)->forceFill(['min_stock' => 10, 'current_stock' => 10])->isBelowMinimum());
        $this->assertFalse((new Item)->forceFill(['min_stock' => 10, 'current_stock' => 11])->isBelowMinimum());
    }
}
