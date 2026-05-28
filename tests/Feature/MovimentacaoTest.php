<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Models\Item;
use App\Models\Sector;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovimentacaoTest extends TestCase
{
    use RefreshDatabase;

    private function operador(): User
    {
        return User::factory()->operador()->create();
    }

    public function test_entrada_incrementa_saldo(): void
    {
        $item = Item::factory()->withStock(10)->create();

        $this->actingAs($this->operador())
            ->post(route('movements.store'), [
                'type' => 'entrada',
                'lines' => [['item_id' => $item->id, 'amount' => 5]],
            ])
            ->assertSessionHasNoErrors()
            ->assertStatus(302);

        $this->assertSame('15.000', (string) $item->fresh()->current_stock);

        $line = $item->movementItems()->latest('id')->first();
        $this->assertSame('5.000', (string) $line->quantity);
        $this->assertSame('15.000', (string) $line->balance_after);
    }

    public function test_movimentacao_com_varios_itens_em_um_documento(): void
    {
        $a = Item::factory()->withStock(100)->create();
        $b = Item::factory()->withStock(50)->create();
        $sector = Sector::factory()->create();

        $this->actingAs($this->operador())
            ->post(route('movements.store'), [
                'type' => 'saida',
                'sector_id' => $sector->id,
                'lines' => [
                    ['item_id' => $a->id, 'amount' => 10],
                    ['item_id' => $b->id, 'amount' => 5],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('90.000', (string) $a->fresh()->current_stock);
        $this->assertSame('45.000', (string) $b->fresh()->current_stock);

        $this->assertSame(1, StockMovement::count());
        $this->assertSame(2, StockMovement::first()->items()->count());
    }

    public function test_saida_decrementa_saldo_com_setor(): void
    {
        $item = Item::factory()->withStock(10)->create();
        $sector = Sector::factory()->create();

        $this->actingAs($this->operador())
            ->post(route('movements.store'), [
                'type' => 'saida',
                'sector_id' => $sector->id,
                'lines' => [['item_id' => $item->id, 'amount' => 4]],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('6.000', (string) $item->fresh()->current_stock);
    }

    public function test_saida_exige_setor(): void
    {
        $item = Item::factory()->withStock(10)->create();

        $this->actingAs($this->operador())
            ->post(route('movements.store'), [
                'type' => 'saida',
                'lines' => [['item_id' => $item->id, 'amount' => 4]],
            ])
            ->assertSessionHasErrors('sector_id');

        $this->assertSame('10.000', (string) $item->fresh()->current_stock);
    }

    public function test_saida_bloqueia_saldo_insuficiente(): void
    {
        $item = Item::factory()->withStock(3)->create();
        $sector = Sector::factory()->create();

        $this->actingAs($this->operador())
            ->post(route('movements.store'), [
                'type' => 'saida',
                'sector_id' => $sector->id,
                'lines' => [['item_id' => $item->id, 'amount' => 5]],
            ])
            ->assertSessionHasErrors('lines');

        $this->assertSame('3.000', (string) $item->fresh()->current_stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_ajuste_diminuindo_define_saldo_contado(): void
    {
        $item = Item::factory()->withStock(10)->create();

        $this->actingAs($this->operador())
            ->post(route('movements.store'), [
                'type' => 'ajuste',
                'lines' => [['item_id' => $item->id, 'amount' => 7]],
            ]);

        $this->assertSame('7.000', (string) $item->fresh()->current_stock);
        $this->assertSame('-3.000', (string) $item->movementItems()->latest('id')->first()->quantity);
    }

    public function test_ajuste_aumentando(): void
    {
        $item = Item::factory()->withStock(5)->create();

        $this->actingAs($this->operador())
            ->post(route('movements.store'), [
                'type' => 'ajuste',
                'lines' => [['item_id' => $item->id, 'amount' => 8]],
            ]);

        $this->assertSame('8.000', (string) $item->fresh()->current_stock);
        $this->assertSame('3.000', (string) $item->movementItems()->latest('id')->first()->quantity);
    }

    public function test_registra_usuario_e_observacao(): void
    {
        $item = Item::factory()->withStock(0)->create();
        $operador = $this->operador();

        $this->actingAs($operador)
            ->post(route('movements.store'), [
                'type' => 'entrada',
                'notes' => 'NF 123',
                'lines' => [['item_id' => $item->id, 'amount' => 3]],
            ]);

        $movement = StockMovement::latest('id')->first();
        $this->assertSame($operador->id, $movement->user_id);
        $this->assertSame('NF 123', $movement->notes);
    }

    public function test_linhas_em_branco_sao_ignoradas(): void
    {
        $item = Item::factory()->withStock(0)->create();

        $this->actingAs($this->operador())
            ->post(route('movements.store'), [
                'type' => 'entrada',
                'lines' => [
                    ['item_id' => $item->id, 'amount' => 2],
                    ['item_id' => '', 'amount' => ''],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, StockMovement::first()->items()->count());
    }

    public function test_formularios_de_movimentacao_abrem(): void
    {
        Item::factory()->create();
        $this->actingAs($this->operador());

        foreach (['entrada', 'saida', 'ajuste'] as $type) {
            $this->get(route('movements.create', $type))->assertOk();
        }
    }

    public function test_historico_abre(): void
    {
        $this->actingAs($this->operador())->get(route('movements.index'))->assertOk();
    }

    public function test_comando_saldo_inicial(): void
    {
        $item = Item::factory()->withStock(50)->create();

        $this->artisan('almoxarifado:saldo-inicial')->assertSuccessful();

        $this->assertSame(1, $item->movementItems()->count());
        $this->assertSame('50.000', (string) $item->movementItems()->first()->balance_after);
    }

    public function test_comando_recalcular_saldo_corrige_divergencia(): void
    {
        $item = Item::factory()->withStock(0)->create();
        $sector = Sector::factory()->create();
        $service = app(StockService::class);

        $service->register(MovementType::Entrada, [['item_id' => $item->id, 'amount' => 10]]);
        $service->register(MovementType::Saida, [['item_id' => $item->id, 'amount' => 4]], $sector->id);

        $item->forceFill(['current_stock' => 999])->save();

        $this->artisan('almoxarifado:recalcular-saldo')->assertSuccessful();

        $this->assertSame('6.000', (string) $item->fresh()->current_stock);
    }
}
