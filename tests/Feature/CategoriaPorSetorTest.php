<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Sector;
use App\Models\User;
use App\Support\CategorySectorAssigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Categorias por setor (hierarquia).
 *
 * Regras de negócio:
 *  - Cada categoria pode pertencer a um setor (sector_id) ou ser global (sector_id null).
 *  - Ao escolher a categoria de um material, ficam visíveis: as globais + as do setor do material.
 *  - Material sem setor vê apenas as categorias globais.
 *  - O nome da categoria é único POR setor (pode repetir entre setores diferentes).
 *  - Migração "pelo uso": categoria usada por 1 setor recebe esse setor; por vários (ou nenhum) fica global.
 */
class CategoriaPorSetorTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    // ──────────────────────────────────────────────────
    // Model / relação
    // ──────────────────────────────────────────────────

    public function test_categoria_pode_pertencer_a_um_setor(): void
    {
        $setor = Sector::factory()->create();
        $cat   = Category::factory()->create(['sector_id' => $setor->id]);

        $this->assertSame($setor->id, $cat->sector->id);
        $this->assertFalse($cat->is_global);
    }

    public function test_categoria_sem_setor_e_global(): void
    {
        $cat = Category::factory()->create(['sector_id' => null]);

        $this->assertNull($cat->sector_id);
        $this->assertTrue($cat->is_global);
    }

    // ──────────────────────────────────────────────────
    // Scope visibleForSector
    // ──────────────────────────────────────────────────

    public function test_scope_inclui_globais_e_do_proprio_setor(): void
    {
        $almox = Sector::factory()->create(['name' => 'Almoxarifado']);
        $metal = Sector::factory()->create(['name' => 'Metalúrgica']);

        $global  = Category::factory()->create(['name' => 'Consumíveis', 'sector_id' => null]);
        $doAlmox = Category::factory()->create(['name' => 'Ferragens', 'sector_id' => $almox->id]);
        $doMetal = Category::factory()->create(['name' => 'Chapas', 'sector_id' => $metal->id]);

        $visiveis = Category::visibleForSector($almox->id)->pluck('id');

        $this->assertTrue($visiveis->contains($global->id));
        $this->assertTrue($visiveis->contains($doAlmox->id));
        $this->assertFalse($visiveis->contains($doMetal->id));
    }

    public function test_scope_sem_setor_mostra_apenas_globais(): void
    {
        $almox  = Sector::factory()->create();
        $global = Category::factory()->create(['sector_id' => null]);
        $doAlmox = Category::factory()->create(['sector_id' => $almox->id]);

        $visiveis = Category::visibleForSector(null)->pluck('id');

        $this->assertTrue($visiveis->contains($global->id));
        $this->assertFalse($visiveis->contains($doAlmox->id));
    }

    // ──────────────────────────────────────────────────
    // HTTP — criação de categorias
    // ──────────────────────────────────────────────────

    public function test_admin_cria_categoria_com_setor(): void
    {
        $setor = Sector::factory()->create();

        $this->actingAs($this->admin())->post(route('categories.store'), [
            'name'      => 'Chapas',
            'sector_id' => $setor->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('categories', ['name' => 'Chapas', 'sector_id' => $setor->id]);
    }

    public function test_admin_cria_categoria_global(): void
    {
        $this->actingAs($this->admin())->post(route('categories.store'), [
            'name' => 'Consumíveis Gerais',
            // sem sector_id → global
        ])->assertRedirect();

        $this->assertDatabaseHas('categories', ['name' => 'Consumíveis Gerais', 'sector_id' => null]);
    }

    public function test_mesmo_nome_permitido_em_setores_diferentes(): void
    {
        $a = Sector::factory()->create();
        $b = Sector::factory()->create();
        Category::factory()->create(['name' => 'Consumíveis', 'sector_id' => $a->id]);

        $this->actingAs($this->admin())->post(route('categories.store'), [
            'name'      => 'Consumíveis',
            'sector_id' => $b->id,
        ])->assertRedirect();

        $this->assertSame(2, Category::where('name', 'Consumíveis')->count());
    }

    public function test_nome_duplicado_no_mesmo_setor_rejeitado(): void
    {
        $setor = Sector::factory()->create();
        Category::factory()->create(['name' => 'Chapas', 'sector_id' => $setor->id]);

        $this->actingAs($this->admin())->post(route('categories.store'), [
            'name'      => 'Chapas',
            'sector_id' => $setor->id,
        ])->assertSessionHasErrors('name');
    }

    public function test_duas_globais_com_mesmo_nome_rejeitado(): void
    {
        Category::factory()->create(['name' => 'Diversos', 'sector_id' => null]);

        $this->actingAs($this->admin())->post(route('categories.store'), [
            'name' => 'Diversos',
        ])->assertSessionHasErrors('name');
    }

    // ──────────────────────────────────────────────────
    // Migração "pelo uso"
    // ──────────────────────────────────────────────────

    public function test_atribuicao_por_uso_define_setor_unico(): void
    {
        $almox = Sector::factory()->create();
        $cat   = Category::factory()->create(['sector_id' => null]);
        Item::factory()->count(3)->create(['category_id' => $cat->id, 'sector_id' => $almox->id]);

        $report = CategorySectorAssigner::run();

        $this->assertSame($almox->id, $cat->fresh()->sector_id);
        $this->assertSame(1, $report['assigned']);
    }

    public function test_atribuicao_por_uso_mantem_global_se_varios_setores(): void
    {
        $a   = Sector::factory()->create();
        $b   = Sector::factory()->create();
        $cat = Category::factory()->create(['sector_id' => null]);
        Item::factory()->create(['category_id' => $cat->id, 'sector_id' => $a->id]);
        Item::factory()->create(['category_id' => $cat->id, 'sector_id' => $b->id]);

        CategorySectorAssigner::run();

        $this->assertNull($cat->fresh()->sector_id);
    }

    public function test_atribuicao_por_uso_mantem_global_sem_itens(): void
    {
        $cat = Category::factory()->create(['sector_id' => null]);

        CategorySectorAssigner::run();

        $this->assertNull($cat->fresh()->sector_id);
    }
}
