<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Models\Item;
use App\Models\Requisition;
use App\Models\Sector;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Requisição de material com aprovação.
 *
 * Fluxo: solicitar (pendente) → administrador aprova (gera saída e baixa o estoque → atendida)
 * ou recusa (recusada). O solicitante pode cancelar enquanto pendente.
 */
class RequisicaoTest extends TestCase
{
    use RefreshDatabase;

    private function operador(): User
    {
        return User::factory()->operador()->create();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function itemComSaldo(float $saldo = 100): Item
    {
        return Item::factory()->create(['unit_id' => Unit::factory(), 'current_stock' => $saldo]);
    }

    public function test_operador_cria_requisicao_pendente(): void
    {
        $setor = Sector::factory()->create();
        $item  = $this->itemComSaldo();

        $this->actingAs($this->operador())->post(route('requisitions.store'), [
            'sector_id' => $setor->id,
            'notes'     => 'Para a obra X',
            'lines'     => [['item_id' => $item->id, 'quantity' => 5]],
        ])->assertRedirect();

        $req = Requisition::latest('id')->first();
        $this->assertNotNull($req);
        $this->assertSame('pendente', $req->status->value);
        $this->assertSame(1, $req->items()->count());
        // Criar a requisição NÃO altera o estoque.
        $this->assertEqualsWithDelta(100, (float) $item->fresh()->current_stock, 0.001);
    }

    public function test_requisicao_exige_setor_e_itens(): void
    {
        $this->actingAs($this->operador())->post(route('requisitions.store'), [
            'lines' => [],
        ])->assertSessionHasErrors(['sector_id', 'lines']);

        $this->assertSame(0, Requisition::count());
    }

    public function test_admin_aprova_gera_saida_e_baixa_estoque(): void
    {
        $setor = Sector::factory()->create();
        $item  = $this->itemComSaldo(100);

        $this->actingAs($this->operador())->post(route('requisitions.store'), [
            'sector_id' => $setor->id,
            'lines'     => [['item_id' => $item->id, 'quantity' => 30]],
        ]);
        $req = Requisition::latest('id')->first();

        $this->actingAs($this->admin())->post(route('requisitions.approve', $req))->assertRedirect();

        $fresh = $req->fresh();
        $this->assertSame('atendida', $fresh->status->value);
        $this->assertNotNull($fresh->stock_movement_id);
        $this->assertEqualsWithDelta(70, (float) $item->fresh()->current_stock, 0.001);
        $this->assertDatabaseHas('stock_movements', [
            'type'      => MovementType::Saida->value,
            'sector_id' => $setor->id,
        ]);
    }

    public function test_aprovacao_bloqueada_sem_saldo(): void
    {
        $setor = Sector::factory()->create();
        $item  = $this->itemComSaldo(10);

        $this->actingAs($this->operador())->post(route('requisitions.store'), [
            'sector_id' => $setor->id,
            'lines'     => [['item_id' => $item->id, 'quantity' => 50]], // mais que o saldo
        ]);
        $req = Requisition::latest('id')->first();

        $this->actingAs($this->admin())->post(route('requisitions.approve', $req))->assertSessionHasErrors();

        $this->assertSame('pendente', $req->fresh()->status->value);
        $this->assertEqualsWithDelta(10, (float) $item->fresh()->current_stock, 0.001);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_admin_recusa_requisicao(): void
    {
        $setor = Sector::factory()->create();
        $item  = $this->itemComSaldo(100);

        $this->actingAs($this->operador())->post(route('requisitions.store'), [
            'sector_id' => $setor->id,
            'lines'     => [['item_id' => $item->id, 'quantity' => 30]],
        ]);
        $req = Requisition::latest('id')->first();

        $this->actingAs($this->admin())
            ->post(route('requisitions.reject', $req), ['decision_notes' => 'Sem necessidade no momento'])
            ->assertRedirect();

        $this->assertSame('recusada', $req->fresh()->status->value);
        $this->assertEqualsWithDelta(100, (float) $item->fresh()->current_stock, 0.001);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_operador_nao_pode_aprovar(): void
    {
        $setor = Sector::factory()->create();
        $item  = $this->itemComSaldo(100);

        $this->actingAs($this->operador())->post(route('requisitions.store'), [
            'sector_id' => $setor->id,
            'lines'     => [['item_id' => $item->id, 'quantity' => 5]],
        ]);
        $req = Requisition::latest('id')->first();

        $this->actingAs($this->operador())->post(route('requisitions.approve', $req))->assertForbidden();
        $this->assertSame('pendente', $req->fresh()->status->value);
    }

    public function test_solicitante_cancela_propria_pendente(): void
    {
        $operador = $this->operador();
        $setor    = Sector::factory()->create();
        $item     = $this->itemComSaldo(100);

        $this->actingAs($operador)->post(route('requisitions.store'), [
            'sector_id' => $setor->id,
            'lines'     => [['item_id' => $item->id, 'quantity' => 5]],
        ]);
        $req = Requisition::latest('id')->first();

        $this->actingAs($operador)->post(route('requisitions.cancel', $req))->assertRedirect();
        $this->assertSame('cancelada', $req->fresh()->status->value);
    }

    public function test_aprovacao_dupla_nao_baixa_duas_vezes(): void
    {
        $setor = Sector::factory()->create();
        $item  = $this->itemComSaldo(100);

        $this->actingAs($this->operador())->post(route('requisitions.store'), [
            'sector_id' => $setor->id,
            'lines'     => [['item_id' => $item->id, 'quantity' => 30]],
        ]);
        $req   = Requisition::latest('id')->first();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('requisitions.approve', $req));
        $this->actingAs($admin)->post(route('requisitions.approve', $req))->assertRedirect();

        $this->assertEqualsWithDelta(70, (float) $item->fresh()->current_stock, 0.001);
        $this->assertSame(1, StockMovement::count());
    }

    public function test_operador_acessa_lista_e_criacao(): void
    {
        $this->actingAs($this->operador())->get(route('requisitions.index'))->assertOk();
        $this->actingAs($this->operador())->get(route('requisitions.create'))->assertOk();
    }

    public function test_admin_edita_requisicao_pendente(): void
    {
        $setorA = Sector::factory()->create();
        $setorB = Sector::factory()->create();
        $itemA  = $this->itemComSaldo();
        $itemB  = $this->itemComSaldo();

        $this->actingAs($this->operador())->post(route('requisitions.store'), [
            'sector_id' => $setorA->id,
            'lines'     => [['item_id' => $itemA->id, 'quantity' => 5]],
        ]);
        $req = Requisition::latest('id')->first();

        $this->actingAs($this->admin())->put(route('requisitions.update', $req), [
            'sector_id' => $setorB->id,
            'notes'     => 'Ajustado pelo admin',
            'lines'     => [['item_id' => $itemB->id, 'quantity' => 9]],
        ])->assertRedirect(route('requisitions.show', $req));

        $fresh = $req->fresh();
        $this->assertSame($setorB->id, $fresh->sector_id);
        $this->assertSame('Ajustado pelo admin', $fresh->notes);
        $this->assertSame(1, $fresh->items()->count());
        $line = $fresh->items()->first();
        $this->assertSame($itemB->id, $line->item_id);
        $this->assertEqualsWithDelta(9, (float) $line->quantity, 0.001);
    }

    public function test_admin_abre_form_de_edicao(): void
    {
        $setor = Sector::factory()->create();
        $item  = $this->itemComSaldo();
        $this->actingAs($this->operador())->post(route('requisitions.store'), [
            'sector_id' => $setor->id,
            'lines'     => [['item_id' => $item->id, 'quantity' => 5]],
        ]);
        $req = Requisition::latest('id')->first();

        $this->actingAs($this->admin())->get(route('requisitions.edit', $req))->assertOk();
    }

    public function test_operador_nao_pode_editar(): void
    {
        $setor = Sector::factory()->create();
        $item  = $this->itemComSaldo();
        $this->actingAs($this->operador())->post(route('requisitions.store'), [
            'sector_id' => $setor->id,
            'lines'     => [['item_id' => $item->id, 'quantity' => 5]],
        ]);
        $req = Requisition::latest('id')->first();

        $this->actingAs($this->operador())->get(route('requisitions.edit', $req))->assertForbidden();
        $this->actingAs($this->operador())->put(route('requisitions.update', $req), [
            'sector_id' => $setor->id,
            'lines'     => [['item_id' => $item->id, 'quantity' => 1]],
        ])->assertForbidden();
    }

    public function test_nao_edita_requisicao_ja_atendida(): void
    {
        $setor = Sector::factory()->create();
        $item  = $this->itemComSaldo(100);
        $this->actingAs($this->operador())->post(route('requisitions.store'), [
            'sector_id' => $setor->id,
            'lines'     => [['item_id' => $item->id, 'quantity' => 30]],
        ]);
        $req   = Requisition::latest('id')->first();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('requisitions.approve', $req)); // vira atendida

        $this->actingAs($admin)->put(route('requisitions.update', $req), [
            'sector_id' => $setor->id,
            'lines'     => [['item_id' => $item->id, 'quantity' => 999]],
        ])->assertRedirect();

        $fresh = $req->fresh();
        $this->assertSame('atendida', $fresh->status->value);
        $this->assertEqualsWithDelta(30, (float) $fresh->items()->first()->quantity, 0.001); // inalterado
    }

    public function test_pode_imprimir_requisicao_pendente(): void
    {
        $setor = Sector::factory()->create(['name' => 'Marcenaria']);
        $unit  = Unit::factory()->create();
        $item  = Item::factory()->create(['unit_id' => $unit->id, 'name' => 'Parafuso Impressão', 'current_stock' => 100]);

        $this->actingAs($this->operador())->post(route('requisitions.store'), [
            'sector_id' => $setor->id,
            'lines'     => [['item_id' => $item->id, 'quantity' => 5]],
        ]);
        $req = Requisition::latest('id')->first();

        $this->assertSame('pendente', $req->status->value);

        $this->actingAs($this->operador())
            ->get(route('requisitions.print', $req))
            ->assertOk()
            ->assertSee('Marcenaria')
            ->assertSee('Parafuso Impressão')
            ->assertSee('Pendente');
    }
}
