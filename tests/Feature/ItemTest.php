<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Enums\MovementType;
use App\Models\Category;
use App\Models\Item;
use App\Models\Sector;
use App\Models\Unit;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_cadastra_material_com_saldo_zero(): void
    {
        $unit = Unit::factory()->create();

        $this->actingAs($this->admin())->post(route('items.store'), [
            'code' => 'MP-001',
            'name' => 'Chapa de aço',
            'unit_id' => $unit->id,
            'type' => ItemType::MateriaPrima->value,
            'min_stock' => 10,
            'active' => '1',
        ])->assertRedirect();

        $item = Item::firstWhere('code', 'MP-001');
        $this->assertNotNull($item);
        $this->assertSame('0.000', (string) $item->current_stock);
        $this->assertTrue($item->active);
    }

    public function test_validacao_de_campos_obrigatorios(): void
    {
        $this->actingAs($this->admin())
            ->post(route('items.store'), ['name' => 'x'])
            ->assertSessionHasErrors(['code', 'unit_id', 'type', 'min_stock']);
    }

    public function test_codigo_duplicado_rejeitado(): void
    {
        Item::factory()->create(['code' => 'DUP-1']);
        $unit = Unit::factory()->create();

        $this->actingAs($this->admin())->post(route('items.store'), [
            'code' => 'DUP-1', 'name' => 'x', 'unit_id' => $unit->id,
            'type' => ItemType::MateriaPrima->value, 'min_stock' => 0,
        ])->assertSessionHasErrors('code');
    }

    public function test_codigo_de_barras_duplicado_rejeitado(): void
    {
        Item::factory()->create(['barcode' => '7891234567895']);
        $unit = Unit::factory()->create();

        $this->actingAs($this->admin())->post(route('items.store'), [
            'code' => 'NEW-1', 'barcode' => '7891234567895', 'name' => 'x', 'unit_id' => $unit->id,
            'type' => ItemType::MateriaPrima->value, 'min_stock' => 0,
        ])->assertSessionHasErrors('barcode');
    }

    public function test_admin_atualiza_material(): void
    {
        $item = Item::factory()->create(['name' => 'Antigo']);

        $this->actingAs($this->admin())->put(route('items.update', $item), [
            'code' => $item->code, 'name' => 'Novo nome', 'unit_id' => $item->unit_id,
            'type' => $item->type->value, 'min_stock' => 5, 'active' => '1',
        ])->assertRedirect(route('items.show', $item));

        $this->assertSame('Novo nome', $item->fresh()->name);
    }

    public function test_admin_remove_material_sem_movimentacoes(): void
    {
        $item = Item::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('items.destroy', $item))
            ->assertRedirect(route('items.index'));

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    public function test_material_com_movimentacoes_nao_pode_ser_removido(): void
    {
        $item = Item::factory()->withStock(5)->create();
        app(StockService::class)->register(MovementType::Entrada, [['item_id' => $item->id, 'amount' => 5]]);

        $this->actingAs($this->admin())->delete(route('items.destroy', $item));

        $this->assertDatabaseHas('items', ['id' => $item->id]);
    }

    public function test_admin_desativa_material(): void
    {
        $item = Item::factory()->create(['active' => true]);

        $this->actingAs($this->admin())
            ->patch(route('items.toggle-active', $item))
            ->assertRedirect(route('items.show', $item));

        $this->assertFalse((bool) $item->fresh()->active);
    }

    public function test_admin_reativa_material(): void
    {
        $item = Item::factory()->inactive()->create();

        $this->actingAs($this->admin())
            ->patch(route('items.toggle-active', $item))
            ->assertRedirect(route('items.show', $item));

        $this->assertTrue((bool) $item->fresh()->active);
    }

    public function test_operador_nao_pode_desativar_material(): void
    {
        $item = Item::factory()->create(['active' => true]);

        $this->actingAs(User::factory()->operador()->create())
            ->patch(route('items.toggle-active', $item))
            ->assertForbidden();

        $this->assertTrue((bool) $item->fresh()->active);
    }

    public function test_busca_por_texto(): void
    {
        Item::factory()->create(['name' => 'Parafuso Phillips']);
        Item::factory()->create(['name' => 'Arruela Lisa']);

        $this->actingAs($this->admin())
            ->get(route('items.index', ['q' => 'Parafuso']))
            ->assertOk()
            ->assertSee('Parafuso Phillips')
            ->assertDontSee('Arruela Lisa');
    }

    public function test_filtro_abaixo_do_minimo(): void
    {
        Item::factory()->below()->create(['name' => 'Item Critico']);
        Item::factory()->withStock(100)->create(['name' => 'Item Tranquilo', 'min_stock' => 1]);

        $this->actingAs($this->admin())
            ->get(route('items.index', ['status' => 'below']))
            ->assertOk()
            ->assertSee('Item Critico')
            ->assertDontSee('Item Tranquilo');
    }

    public function test_filtro_por_categoria(): void
    {
        $cat = Category::factory()->create();
        Item::factory()->create(['category_id' => $cat->id, 'name' => 'Com Categoria']);
        Item::factory()->create(['name' => 'Fora Da Categoria']);

        $this->actingAs($this->admin())
            ->get(route('items.index', ['category_id' => $cat->id]))
            ->assertOk()
            ->assertSee('Com Categoria')
            ->assertDontSee('Fora Da Categoria');
    }

    public function test_filtro_por_setor(): void
    {
        $sector = Sector::factory()->create();
        Item::factory()->create(['sector_id' => $sector->id, 'name' => 'Item Do Setor']);
        Item::factory()->create(['name' => 'Item Sem Setor']);

        $this->actingAs($this->admin())
            ->get(route('items.index', ['sector_id' => $sector->id]))
            ->assertOk()
            ->assertSee('Item Do Setor')
            ->assertDontSee('Item Sem Setor');
    }

    public function test_preco_maximo_e_rastreado_automaticamente(): void
    {
        $item = Item::factory()->create();

        $item->update(['unit_price' => 5]);
        $this->assertSame('5.0000', (string) $item->fresh()->max_price);

        $item->update(['unit_price' => 3]); // menor: o máximo não diminui
        $this->assertSame('5.0000', (string) $item->fresh()->max_price);

        $item->update(['unit_price' => 8]); // maior: atualiza o máximo
        $this->assertSame('8.0000', (string) $item->fresh()->max_price);
    }

    public function test_filtro_sem_categoria(): void
    {
        $cat = Category::factory()->create();
        Item::factory()->create(['category_id' => $cat->id, 'name' => 'Tem Categoria']);
        Item::factory()->create(['category_id' => null, 'name' => 'Falta Categoria']);

        $this->actingAs($this->admin())
            ->get(route('items.index', ['category_id' => '0']))
            ->assertOk()
            ->assertSee('Falta Categoria')
            ->assertDontSee('Tem Categoria');
    }

    public function test_ordena_por_saldo_decrescente(): void
    {
        Item::factory()->create(['name' => 'Item Saldo Baixo', 'current_stock' => 5]);
        Item::factory()->create(['name' => 'Item Saldo Alto', 'current_stock' => 999]);

        $this->actingAs($this->admin())
            ->get(route('items.index', ['sort' => 'current_stock', 'dir' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder(['Item Saldo Alto', 'Item Saldo Baixo']);
    }

    public function test_ordena_por_saldo_crescente(): void
    {
        Item::factory()->create(['name' => 'Item Saldo Baixo', 'current_stock' => 5]);
        Item::factory()->create(['name' => 'Item Saldo Alto', 'current_stock' => 999]);

        $this->actingAs($this->admin())
            ->get(route('items.index', ['sort' => 'current_stock', 'dir' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder(['Item Saldo Baixo', 'Item Saldo Alto']);
    }

    public function test_ordena_por_codigo_decrescente(): void
    {
        Item::factory()->create(['code' => 'AAA-001', 'name' => 'Material AAA']);
        Item::factory()->create(['code' => 'ZZZ-999', 'name' => 'Material ZZZ']);

        $this->actingAs($this->admin())
            ->get(route('items.index', ['sort' => 'code', 'dir' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder(['Material ZZZ', 'Material AAA']);
    }

    public function test_sort_invalido_usa_padrao(): void
    {
        $this->actingAs($this->admin())
            ->get(route('items.index', ['sort' => 'coluna_inexistente']))
            ->assertOk();
    }

    public function test_filtro_sem_setor(): void
    {
        $sector = Sector::factory()->create();
        Item::factory()->create(['sector_id' => $sector->id, 'name' => 'Tem Setor']);
        Item::factory()->create(['sector_id' => null, 'name' => 'Falta Setor']);

        $this->actingAs($this->admin())
            ->get(route('items.index', ['sector_id' => '0']))
            ->assertOk()
            ->assertSee('Falta Setor')
            ->assertDontSee('Tem Setor');
    }

    // ──────────────────────────────────────────────────
    // Validação: max_stock >= min_stock
    // ──────────────────────────────────────────────────

    public function test_max_stock_menor_que_min_stock_retorna_erro(): void
    {
        $unit = Unit::factory()->create();

        $this->actingAs($this->admin())->post(route('items.store'), [
            'code'      => 'VAL-001',
            'name'      => 'Teste Valida',
            'unit_id'   => $unit->id,
            'type'      => ItemType::MateriaPrima->value,
            'min_stock' => 100,
            'max_stock' => 50,   // menor que min_stock → erro
            'active'    => '1',
        ])->assertSessionHasErrors('max_stock');
    }

    public function test_max_stock_igual_a_min_stock_e_valido(): void
    {
        $unit = Unit::factory()->create();

        $this->actingAs($this->admin())->post(route('items.store'), [
            'code'      => 'VAL-002',
            'name'      => 'Teste Igual',
            'unit_id'   => $unit->id,
            'type'      => ItemType::MateriaPrima->value,
            'min_stock' => 100,
            'max_stock' => 100,  // igual ao min_stock → válido
            'active'    => '1',
        ])->assertRedirect();
    }

    public function test_max_stock_nulo_sempre_valido(): void
    {
        $unit = Unit::factory()->create();

        $this->actingAs($this->admin())->post(route('items.store'), [
            'code'      => 'VAL-003',
            'name'      => 'Sem Maximo',
            'unit_id'   => $unit->id,
            'type'      => ItemType::MateriaPrima->value,
            'min_stock' => 100,
            // max_stock ausente → null → sem alerta de máximo
            'active'    => '1',
        ])->assertRedirect();
    }

    public function test_max_stock_disponivel_para_item_normal(): void
    {
        $unit = Unit::factory()->create();

        $this->actingAs($this->admin())->post(route('items.store'), [
            'code'      => 'VAL-004',
            'name'      => 'Parafuso com Maximo',
            'unit_id'   => $unit->id,
            'type'      => ItemType::Consumivel->value,
            'min_stock' => 10,
            'max_stock' => 500,  // item normal com estoque máximo
            'active'    => '1',
        ])->assertRedirect();

        $item = Item::firstWhere('code', 'VAL-004');
        $this->assertNotNull($item);
        $this->assertEqualsWithDelta(500, (float) $item->max_stock, 0.001);
        $this->assertFalse((bool) $item->priced_by_weight);
    }

    public function test_item_normal_acima_do_max_stock_gera_alerta(): void
    {
        $item = Item::factory()->create([
            'min_stock'     => 10,
            'max_stock'     => 100,
            'current_stock' => 150,  // acima do máximo
        ]);

        $this->assertTrue($item->isAboveMaxStock());
    }

    // ──────────────────────────────────────────────────
    // Saldo inicial no cadastro
    // ──────────────────────────────────────────────────

    public function test_criar_item_com_saldo_inicial_cria_movimentacao_de_ajuste(): void
    {
        $unit = Unit::factory()->create();

        $this->actingAs($this->admin())->post(route('items.store'), [
            'code'          => 'INIT-001',
            'name'          => 'Parafuso M8',
            'unit_id'       => $unit->id,
            'type'          => ItemType::Consumivel->value,
            'min_stock'     => 0,
            'initial_stock' => '150',
            'active'        => '1',
        ])->assertRedirect();

        $item = Item::firstWhere('code', 'INIT-001');
        $this->assertNotNull($item);
        $this->assertEqualsWithDelta(150, (float) $item->current_stock, 0.001);
        $this->assertDatabaseHas('stock_movements', ['type' => 'ajuste']);
    }

    public function test_criar_item_sem_saldo_inicial_nao_cria_movimentacao(): void
    {
        $unit = Unit::factory()->create();

        $this->actingAs($this->admin())->post(route('items.store'), [
            'code'      => 'INIT-002',
            'name'      => 'Arruela',
            'unit_id'   => $unit->id,
            'type'      => ItemType::Consumivel->value,
            'min_stock' => 0,
            'active'    => '1',
        ])->assertRedirect();

        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertEqualsWithDelta(0, (float) Item::firstWhere('code', 'INIT-002')->current_stock, 0.001);
    }

    public function test_criar_item_com_saldo_inicial_zero_nao_cria_movimentacao(): void
    {
        $unit = Unit::factory()->create();

        $this->actingAs($this->admin())->post(route('items.store'), [
            'code'          => 'INIT-003',
            'name'          => 'Porca M8',
            'unit_id'       => $unit->id,
            'type'          => ItemType::Consumivel->value,
            'min_stock'     => 0,
            'initial_stock' => '0',
            'active'        => '1',
        ])->assertRedirect();

        $this->assertDatabaseCount('stock_movements', 0);
    }
}
