<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Endereçamento estruturado: corredor / prateleira / posição.
 * O campo livre antigo (location) vira "complemento" e continua disponível.
 */
class LocalizacaoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_cadastra_material_com_endereco_estruturado(): void
    {
        $unit = Unit::factory()->create();

        $this->actingAs($this->admin())->post(route('items.store'), [
            'code'      => 'END-001',
            'name'      => 'Chapa endereçada',
            'unit_id'   => $unit->id,
            'type'      => ItemType::MateriaPrima->value,
            'min_stock' => 0,
            'aisle'     => 'A',
            'shelf'     => '3',
            'position'  => '02',
            'active'    => '1',
        ])->assertRedirect();

        $item = Item::firstWhere('code', 'END-001');
        $this->assertNotNull($item);
        $this->assertSame('A', $item->aisle);
        $this->assertSame('3', $item->shelf);
        $this->assertSame('02', $item->position);
    }

    public function test_location_label_compoe_endereco_estruturado(): void
    {
        $item = Item::factory()->make([
            'aisle'    => 'A',
            'shelf'    => '3',
            'position' => '02',
            'location' => null,
        ]);

        $this->assertSame('Corredor A · Prateleira 3 · Posição 02', $item->location_label);
    }

    public function test_location_label_usa_texto_livre_quando_sem_estrutura(): void
    {
        $item = Item::factory()->make([
            'aisle'    => null,
            'shelf'    => null,
            'position' => null,
            'location' => 'Galpão dos fundos',
        ]);

        $this->assertSame('Galpão dos fundos', $item->location_label);
    }

    public function test_location_label_combina_estrutura_e_complemento(): void
    {
        $item = Item::factory()->make([
            'aisle'    => 'B',
            'shelf'    => null,
            'position' => null,
            'location' => 'fundo',
        ]);

        $this->assertSame('Corredor B · fundo', $item->location_label);
    }

    public function test_location_label_vazio_retorna_traco(): void
    {
        $item = Item::factory()->make([
            'aisle'    => null,
            'shelf'    => null,
            'position' => null,
            'location' => null,
        ]);

        $this->assertSame('—', $item->location_label);
    }
}
