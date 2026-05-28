<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Enums\UserRole;
use App\Models\Item;
use App\Models\Sector;
use App\Models\Unit;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ficha Kardex por item: histórico completo de entradas/saídas/ajustes com saldo acumulado.
 * Os dados já existem em stock_movement_items (quantity = delta assinado, balance_after = saldo após).
 */
class KardexTest extends TestCase
{
    use RefreshDatabase;

    private function operador(): User
    {
        return User::factory()->create(['role' => UserRole::Operador]);
    }

    private function itemComMovimentos(): Item
    {
        $unit   = Unit::factory()->create();
        $setor  = Sector::factory()->create();
        $item   = Item::factory()->create([
            'unit_id'       => $unit->id,
            'sector_id'     => $setor->id,
            'current_stock' => 0,
            'name'          => 'Parafuso Kardex',
        ]);

        $svc = app(StockService::class);
        $svc->register(MovementType::Entrada, [['item_id' => $item->id, 'amount' => 100]]);
        $svc->register(MovementType::Saida, [['item_id' => $item->id, 'amount' => 30]], $setor->id);

        return $item->fresh();
    }

    public function test_kardex_abre_e_lista_movimentos(): void
    {
        $item = $this->itemComMovimentos();

        $this->actingAs($this->operador())
            ->get(route('items.kardex', $item))
            ->assertOk()
            ->assertSee('Parafuso Kardex')
            ->assertSee('Entrada')
            ->assertSee('Saída');
    }

    public function test_kardex_mostra_saldo_apos_cada_movimento(): void
    {
        $item = $this->itemComMovimentos();

        $this->actingAs($this->operador())
            ->get(route('items.kardex', $item))
            ->assertOk()
            ->assertSee('100')  // saldo após a entrada
            ->assertSee('70');  // saldo após a saída (100 - 30)
    }

    public function test_kardex_so_mostra_movimentos_do_proprio_item(): void
    {
        $item  = $this->itemComMovimentos();
        $outro = Item::factory()->create(['name' => 'Outro Material ZZZ', 'current_stock' => 0]);
        app(StockService::class)->register(MovementType::Entrada, [['item_id' => $outro->id, 'amount' => 5]]);

        $this->actingAs($this->operador())
            ->get(route('items.kardex', $item))
            ->assertOk()
            ->assertDontSee('Outro Material ZZZ');
    }

    public function test_kardex_exporta_excel(): void
    {
        $item = $this->itemComMovimentos();

        $this->actingAs($this->operador())
            ->get(route('items.kardex', [$item, 'format' => 'xlsx']))
            ->assertOk();
    }

    public function test_kardex_exporta_pdf(): void
    {
        $item = $this->itemComMovimentos();

        $response = $this->actingAs($this->operador())
            ->get(route('items.kardex', [$item, 'format' => 'pdf']));

        $response->assertOk();
        $this->assertStringContainsString('pdf', (string) $response->headers->get('content-type'));
    }

    public function test_operador_pode_ver_kardex(): void
    {
        $item = $this->itemComMovimentos();

        $this->actingAs($this->operador())
            ->get(route('items.kardex', $item))
            ->assertOk();
    }
}
