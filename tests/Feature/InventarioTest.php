<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Enums\UserRole;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\Sector;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Inventário rotativo guiado.
 *
 * Fluxo: abrir sessão (snapshot do saldo do sistema) → lançar contagem → finalizar
 * (aplica ajustes só nas divergências, em uma transação, via MovementType::Ajuste).
 */
class InventarioTest extends TestCase
{
    use RefreshDatabase;

    private function operador(): User
    {
        return User::factory()->create(['role' => UserRole::Operador]);
    }

    private function novoItem(Sector $setor, float $saldo): Item
    {
        return Item::factory()->create([
            'unit_id'       => Unit::factory(),
            'sector_id'     => $setor->id,
            'current_stock' => $saldo,
        ]);
    }

    public function test_operador_inicia_inventario_com_snapshot_do_saldo(): void
    {
        $setor = Sector::factory()->create();
        $item  = $this->novoItem($setor, 50);

        $this->actingAs($this->operador())
            ->post(route('inventories.store'), ['sector_id' => $setor->id])
            ->assertRedirect();

        $inv = Inventory::latest('id')->first();
        $this->assertNotNull($inv);
        $this->assertSame('aberto', $inv->status->value);

        $invItem = $inv->items()->where('item_id', $item->id)->first();
        $this->assertNotNull($invItem);
        $this->assertEqualsWithDelta(50, (float) $invItem->system_qty, 0.001);
        $this->assertNull($invItem->counted_qty);
    }

    public function test_iniciar_sem_itens_falha(): void
    {
        $setor = Sector::factory()->create(); // sem itens

        $this->actingAs($this->operador())
            ->post(route('inventories.store'), ['sector_id' => $setor->id])
            ->assertSessionHasErrors();

        $this->assertSame(0, Inventory::count());
    }

    public function test_salvar_contagem_persiste_sem_finalizar(): void
    {
        $setor = Sector::factory()->create();
        $item  = $this->novoItem($setor, 100);

        $this->actingAs($this->operador())->post(route('inventories.store'), ['sector_id' => $setor->id]);
        $inv     = Inventory::latest('id')->first();
        $invItem = $inv->items()->first();

        $this->actingAs($this->operador())->put(route('inventories.update', $inv), [
            'action' => 'salvar',
            'counts' => [$invItem->id => 95],
        ])->assertRedirect();

        $this->assertEqualsWithDelta(95, (float) $invItem->fresh()->counted_qty, 0.001);
        $this->assertSame('aberto', $inv->fresh()->status->value);
        // Saldo do item NÃO muda só ao salvar a contagem.
        $this->assertEqualsWithDelta(100, (float) $item->fresh()->current_stock, 0.001);
    }

    public function test_finalizar_aplica_ajuste_na_divergencia(): void
    {
        $setor = Sector::factory()->create();
        $item  = $this->novoItem($setor, 100);

        $this->actingAs($this->operador())->post(route('inventories.store'), ['sector_id' => $setor->id]);
        $inv     = Inventory::latest('id')->first();
        $invItem = $inv->items()->first();

        $this->actingAs($this->operador())->put(route('inventories.update', $inv), [
            'action' => 'finalizar',
            'counts' => [$invItem->id => 90],
        ])->assertRedirect();

        $this->assertEqualsWithDelta(90, (float) $item->fresh()->current_stock, 0.001);
        $this->assertDatabaseHas('stock_movements', ['type' => MovementType::Ajuste->value]);
        $this->assertSame('finalizado', $inv->fresh()->status->value);
        $this->assertNotNull($inv->fresh()->finished_at);
    }

    public function test_finalizar_sem_divergencia_nao_cria_movimento(): void
    {
        $setor = Sector::factory()->create();
        $item  = $this->novoItem($setor, 100);

        $this->actingAs($this->operador())->post(route('inventories.store'), ['sector_id' => $setor->id]);
        $inv     = Inventory::latest('id')->first();
        $invItem = $inv->items()->first();

        $this->actingAs($this->operador())->put(route('inventories.update', $inv), [
            'action' => 'finalizar',
            'counts' => [$invItem->id => 100], // igual ao saldo → sem ajuste
        ])->assertRedirect();

        $this->assertSame(0, \App\Models\StockMovement::count());
        $this->assertEqualsWithDelta(100, (float) $item->fresh()->current_stock, 0.001);
        $this->assertSame('finalizado', $inv->fresh()->status->value);
    }

    public function test_finalizar_ignora_itens_nao_contados(): void
    {
        $setor = Sector::factory()->create();
        $itemA = $this->novoItem($setor, 100);
        $itemB = $this->novoItem($setor, 200);

        $this->actingAs($this->operador())->post(route('inventories.store'), ['sector_id' => $setor->id]);
        $inv      = Inventory::latest('id')->first();
        $invItemA = $inv->items()->where('item_id', $itemA->id)->first();

        // Conta só o A (divergente); B fica sem contagem.
        $this->actingAs($this->operador())->put(route('inventories.update', $inv), [
            'action' => 'finalizar',
            'counts' => [$invItemA->id => 80],
        ])->assertRedirect();

        $this->assertEqualsWithDelta(80, (float) $itemA->fresh()->current_stock, 0.001);
        $this->assertEqualsWithDelta(200, (float) $itemB->fresh()->current_stock, 0.001); // intacto
    }

    public function test_inventario_finalizado_nao_recebe_nova_contagem(): void
    {
        $setor = Sector::factory()->create();
        $item  = $this->novoItem($setor, 100);

        $this->actingAs($this->operador())->post(route('inventories.store'), ['sector_id' => $setor->id]);
        $inv     = Inventory::latest('id')->first();
        $invItem = $inv->items()->first();

        $this->actingAs($this->operador())->put(route('inventories.update', $inv), [
            'action' => 'finalizar',
            'counts' => [$invItem->id => 90],
        ]);

        // Tenta recontar após finalizado → não altera mais.
        $this->actingAs($this->operador())->put(route('inventories.update', $inv), [
            'action' => 'finalizar',
            'counts' => [$invItem->id => 10],
        ])->assertRedirect();

        $this->assertEqualsWithDelta(90, (float) $item->fresh()->current_stock, 0.001);
    }

    public function test_operador_acessa_lista_e_criacao(): void
    {
        $this->actingAs($this->operador())->get(route('inventories.index'))->assertOk();
        $this->actingAs($this->operador())->get(route('inventories.create'))->assertOk();
    }

    public function test_cancelar_inventario_nao_altera_saldo(): void
    {
        $setor = Sector::factory()->create();
        $item  = $this->novoItem($setor, 100);

        $this->actingAs($this->operador())->post(route('inventories.store'), ['sector_id' => $setor->id]);
        $inv = Inventory::latest('id')->first();

        $this->actingAs($this->operador())
            ->post(route('inventories.cancel', $inv))
            ->assertRedirect();

        $this->assertSame('cancelado', $inv->fresh()->status->value);
        $this->assertEqualsWithDelta(100, (float) $item->fresh()->current_stock, 0.001);
        $this->assertSame(0, \App\Models\StockMovement::count());
    }

    public function test_inventario_finalizado_nao_pode_ser_cancelado(): void
    {
        $setor = Sector::factory()->create();
        $item  = $this->novoItem($setor, 100);

        $this->actingAs($this->operador())->post(route('inventories.store'), ['sector_id' => $setor->id]);
        $inv     = Inventory::latest('id')->first();
        $invItem = $inv->items()->first();

        $this->actingAs($this->operador())->put(route('inventories.update', $inv), [
            'action' => 'finalizar',
            'counts' => [$invItem->id => 90],
        ]);

        $this->actingAs($this->operador())->post(route('inventories.cancel', $inv))->assertRedirect();

        // Continua finalizado (não vira cancelado) e o saldo permanece o ajustado.
        $this->assertSame('finalizado', $inv->fresh()->status->value);
        $this->assertEqualsWithDelta(90, (float) $item->fresh()->current_stock, 0.001);
    }
}
