<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Models\Category;
use App\Models\Item;
use App\Models\Sector;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes para a funcionalidade de precificação por peso (kg).
 *
 * Regras de negócio:
 *  - A categoria define se os materiais usam precificação por kg (is_weight_category = true).
 *  - Ao salvar um item, priced_by_weight é sincronizado automaticamente da categoria.
 *  - Para itens por peso: unit_price = price_per_kg (a unidade JÁ é KG).
 *  - stock_value = current_stock × price_per_kg (sem unit_weight — contagem direta em kg).
 *  - max_stock = estoque máximo desejado em kg (alerta "acima do máximo").
 *  - max_price continua rastreando o maior unit_price já registrado.
 *  - Itens normais (is_weight_category = false) não são afetados.
 */
class PrecoKgTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function weightCategory(): Category
    {
        return Category::factory()->create(['is_weight_category' => true]);
    }

    private function normalCategory(): Category
    {
        return Category::factory()->create(['is_weight_category' => false]);
    }

    // ──────────────────────────────────────────────────
    // Categoria — flag is_weight_category
    // ──────────────────────────────────────────────────

    public function test_categoria_pode_ser_marcada_como_peso(): void
    {
        $cat = Category::factory()->create(['is_weight_category' => true]);

        $this->assertTrue((bool) $cat->is_weight_category);
    }

    public function test_categoria_padrao_nao_e_por_peso(): void
    {
        $cat = Category::factory()->create();

        $this->assertFalse((bool) $cat->is_weight_category);
    }

    // ──────────────────────────────────────────────────
    // Item — sync de priced_by_weight da categoria
    // ──────────────────────────────────────────────────

    public function test_item_herda_priced_by_weight_da_categoria(): void
    {
        $cat  = $this->weightCategory();
        $item = Item::factory()->create(['category_id' => $cat->id]);

        $this->assertTrue((bool) $item->priced_by_weight);
    }

    public function test_item_sem_categoria_peso_fica_false(): void
    {
        $item = Item::factory()->create(['category_id' => null]);

        $this->assertFalse((bool) $item->priced_by_weight);
    }

    public function test_item_com_categoria_normal_fica_false(): void
    {
        $cat  = $this->normalCategory();
        $item = Item::factory()->create(['category_id' => $cat->id]);

        $this->assertFalse((bool) $item->priced_by_weight);
    }

    public function test_priced_by_weight_atualiza_ao_trocar_categoria(): void
    {
        $catPeso   = $this->weightCategory();
        $catNormal = $this->normalCategory();

        $item = Item::factory()->create(['category_id' => $catPeso->id]);
        $this->assertTrue((bool) $item->priced_by_weight);

        // Troca para categoria normal
        $item->update(['category_id' => $catNormal->id]);
        $this->assertFalse((bool) $item->fresh()->priced_by_weight);

        // Volta para categoria por peso
        $item->update(['category_id' => $catPeso->id]);
        $this->assertTrue((bool) $item->fresh()->priced_by_weight);
    }

    // ──────────────────────────────────────────────────
    // Lógica de preço e valor
    // ──────────────────────────────────────────────────

    public function test_unit_price_igual_a_price_per_kg_para_item_por_peso(): void
    {
        $cat  = $this->weightCategory();
        $item = Item::factory()->create([
            'category_id'  => $cat->id,
            'price_per_kg' => 9.70,
            'current_stock' => 495,
        ]);

        // Unidade = KG, então unit_price é igual ao price_per_kg
        $this->assertEqualsWithDelta(9.70, (float) $item->unit_price, 0.001);
    }

    public function test_stock_value_correto_para_item_por_peso(): void
    {
        $cat  = $this->weightCategory();
        $item = Item::factory()->create([
            'category_id'   => $cat->id,
            'price_per_kg'  => 9.70,
            'current_stock' => 495,
        ]);

        // stock_value = 495 × 9,70 = R$ 4.801,50
        $this->assertEqualsWithDelta(4801.50, (float) $item->stock_value, 0.01);
    }

    public function test_stock_value_nao_dobra_o_peso(): void
    {
        // Garante que o bug original (current_stock × unit_weight × price_per_kg) não volta.
        $cat  = $this->weightCategory();
        $item = Item::factory()->create([
            'category_id'   => $cat->id,
            'price_per_kg'  => 9.70,
            'current_stock' => 495,
        ]);

        // Deve ser 4.801,50 — e NÃO 2.376.742,50 (bug da multiplicação dupla)
        $this->assertLessThan(10_000, (float) $item->stock_value);
        $this->assertEqualsWithDelta(4801.50, (float) $item->stock_value, 0.01);
    }

    public function test_max_price_rastreado_ao_atualizar_price_per_kg(): void
    {
        $cat  = $this->weightCategory();
        $item = Item::factory()->create([
            'category_id'  => $cat->id,
            'price_per_kg' => 8.00,
        ]);
        $this->assertEqualsWithDelta(8.00, (float) $item->max_price, 0.001);

        $item->update(['price_per_kg' => 12.00]);
        $this->assertEqualsWithDelta(12.00, (float) $item->fresh()->max_price, 0.001);

        // Baixar o preço não deve diminuir o máximo
        $item->update(['price_per_kg' => 5.00]);
        $this->assertEqualsWithDelta(12.00, (float) $item->fresh()->max_price, 0.001);
    }

    public function test_item_normal_nao_e_afetado(): void
    {
        $cat  = $this->normalCategory();
        $item = Item::factory()->create([
            'category_id'   => $cat->id,
            'unit_price'    => 25.00,
            'current_stock' => 10,
        ]);

        $this->assertFalse((bool) $item->priced_by_weight);
        $this->assertEqualsWithDelta(25.0, (float) $item->unit_price, 0.001);
        $this->assertEqualsWithDelta(250.0, (float) $item->stock_value, 0.001);
    }

    // ──────────────────────────────────────────────────
    // Estoque máximo (max_stock)
    // ──────────────────────────────────────────────────

    public function test_item_abaixo_do_max_stock_nao_e_alerta(): void
    {
        $cat  = $this->weightCategory();
        $item = Item::factory()->create([
            'category_id'   => $cat->id,
            'current_stock' => 400,
            'max_stock'     => 600,
        ]);

        $this->assertFalse($item->isAboveMaxStock());
    }

    public function test_item_acima_do_max_stock_e_alerta(): void
    {
        $cat  = $this->weightCategory();
        $item = Item::factory()->create([
            'category_id'   => $cat->id,
            'current_stock' => 700,
            'max_stock'     => 600,
        ]);

        $this->assertTrue($item->isAboveMaxStock());
    }

    public function test_max_stock_nulo_nunca_gera_alerta(): void
    {
        $cat  = $this->weightCategory();
        $item = Item::factory()->create([
            'category_id'   => $cat->id,
            'current_stock' => 9999,
            'max_stock'     => null,
        ]);

        $this->assertFalse($item->isAboveMaxStock());
    }

    // ──────────────────────────────────────────────────
    // HTTP — cadastro e edição
    // ──────────────────────────────────────────────────

    public function test_admin_cadastra_item_por_peso_via_categoria(): void
    {
        $unit   = Unit::factory()->create();
        $sector = Sector::factory()->create(['name' => 'Metalúrgica']);
        $cat    = $this->weightCategory();

        $this->actingAs($this->admin())->post(route('items.store'), [
            'code'        => 'MET-CH-001',
            'name'        => 'TB QD FF 35x35x1,50',
            'unit_id'     => $unit->id,
            'sector_id'   => $sector->id,
            'category_id' => $cat->id,
            'type'        => ItemType::MateriaPrima->value,
            'min_stock'   => 100,
            'max_stock'   => 800,
            'price_per_kg' => '9.70',
            'active'      => '1',
        ])->assertRedirect();

        $item = Item::firstWhere('code', 'MET-CH-001');
        $this->assertNotNull($item);
        $this->assertTrue((bool) $item->priced_by_weight); // auto da categoria
        $this->assertEqualsWithDelta(9.70, (float) $item->price_per_kg, 0.001);
        $this->assertEqualsWithDelta(9.70, (float) $item->unit_price, 0.001); // igual ao price_per_kg
        $this->assertEqualsWithDelta(800, (float) $item->max_stock, 0.001);
    }

    public function test_admin_atualiza_price_per_kg(): void
    {
        $cat  = $this->weightCategory();
        $item = Item::factory()->create([
            'category_id'  => $cat->id,
            'price_per_kg' => 9.00,
        ]);

        $this->actingAs($this->admin())->put(route('items.update', $item), [
            'code'         => $item->code,
            'name'         => $item->name,
            'unit_id'      => $item->unit_id,
            'category_id'  => $cat->id,
            'type'         => $item->type->value,
            'min_stock'    => $item->min_stock,
            'max_stock'    => '800',
            'price_per_kg' => '12.50',
            'active'       => '1',
        ])->assertRedirect(route('items.show', $item));

        $fresh = $item->fresh();
        $this->assertEqualsWithDelta(12.50, (float) $fresh->price_per_kg, 0.001);
        $this->assertEqualsWithDelta(12.50, (float) $fresh->unit_price, 0.001);
        $this->assertEqualsWithDelta(800, (float) $fresh->max_stock, 0.001);
    }

    public function test_item_normal_nao_exige_price_per_kg(): void
    {
        $unit = Unit::factory()->create();
        $cat  = $this->normalCategory();

        $this->actingAs($this->admin())->post(route('items.store'), [
            'code'        => 'ALM-NORMAL',
            'name'        => 'Item Normal',
            'unit_id'     => $unit->id,
            'category_id' => $cat->id,
            'type'        => ItemType::MateriaPrima->value,
            'min_stock'   => 0,
            'unit_price'  => '25.00',
            'active'      => '1',
        ])->assertRedirect();

        $item = Item::firstWhere('code', 'ALM-NORMAL');
        $this->assertNotNull($item);
        $this->assertFalse((bool) $item->priced_by_weight);
        $this->assertEqualsWithDelta(25.0, (float) $item->unit_price, 0.001);
    }

    // ──────────────────────────────────────────────────
    // Views
    // ──────────────────────────────────────────────────

    public function test_pagina_show_exibe_preco_por_kg_e_valor_correto(): void
    {
        $cat  = $this->weightCategory();
        $item = Item::factory()->create([
            'name'          => 'Chapa Inox 2mm',
            'category_id'   => $cat->id,
            'price_per_kg'  => 12.00,
            'current_stock' => 10,
        ]);
        $item->load(['category', 'unit']);

        $this->actingAs($this->admin())
            ->get(route('items.show', $item))
            ->assertOk()
            ->assertSee('/kg')       // label preço por kg (ex.: "R$ 12,00/kg")
            ->assertSee('Por peso'); // badge identificador
    }

    public function test_pagina_create_renderiza_sem_erros(): void
    {
        $this->actingAs($this->admin())
            ->get(route('items.create'))
            ->assertOk()
            ->assertSee('Preço (R$/kg)'); // campo peso visível na form
    }

    public function test_admin_cria_categoria_por_peso_via_http(): void
    {
        $this->actingAs($this->admin())
            ->post(route('categories.store'), [
                'name'             => 'Chapas',
                'is_weight_category' => '1',
            ])->assertRedirect();

        $cat = Category::firstWhere('name', 'Chapas');
        $this->assertNotNull($cat);
        $this->assertTrue((bool) $cat->is_weight_category);
    }

    public function test_admin_edita_categoria_para_tipo_peso(): void
    {
        $cat = $this->normalCategory();

        $this->actingAs($this->admin())
            ->put(route('categories.update', $cat), [
                'name'               => $cat->name,
                'is_weight_category' => '1',
            ])->assertRedirect();

        $this->assertTrue((bool) $cat->fresh()->is_weight_category);
    }

    // ──────────────────────────────────────────────────
    // Sincronização de itens ao alterar categoria
    // ──────────────────────────────────────────────────

    public function test_editar_categoria_para_peso_sincroniza_itens_existentes(): void
    {
        $cat  = $this->normalCategory();
        $item = Item::factory()->create([
            'category_id'  => $cat->id,
            'price_per_kg' => 15.00,
        ]);
        $this->assertFalse((bool) $item->priced_by_weight);

        $this->actingAs($this->admin())
            ->put(route('categories.update', $cat), [
                'name'               => $cat->name,
                'is_weight_category' => '1',
            ])->assertRedirect();

        $fresh = $item->fresh();
        $this->assertTrue((bool) $fresh->priced_by_weight);           // sincronizou flag
        $this->assertEqualsWithDelta(15.00, (float) $fresh->unit_price, 0.001); // sincronizou preço
    }

    public function test_editar_categoria_de_peso_para_normal_sincroniza_itens_existentes(): void
    {
        $cat  = $this->weightCategory();
        $item = Item::factory()->create(['category_id' => $cat->id]);
        $this->assertTrue((bool) $item->priced_by_weight);

        // Remove a flag de peso da categoria
        $this->actingAs($this->admin())
            ->put(route('categories.update', $cat), [
                'name' => $cat->name,
                // is_weight_category não enviado → false (boolean do request)
            ])->assertRedirect();

        $this->assertFalse((bool) $item->fresh()->priced_by_weight); // sincronizou
    }

    public function test_editar_categoria_sem_mudar_peso_nao_altera_itens(): void
    {
        $cat  = $this->weightCategory();
        $item = Item::factory()->create([
            'category_id'  => $cat->id,
            'price_per_kg' => 10.00,
        ]);
        $originalUpdatedAt = $item->updated_at;

        // Edita só o nome, mantém is_weight_category = true
        $this->actingAs($this->admin())
            ->put(route('categories.update', $cat), [
                'name'               => 'Novo Nome Categoria',
                'is_weight_category' => '1',
            ])->assertRedirect();

        // updated_at do item não mudou (saveQuietly não foi chamado)
        $this->assertEquals($originalUpdatedAt, $item->fresh()->updated_at);
    }
}
