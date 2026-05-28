<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Sector;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Central de Configurações (admin): hub + CRUD de Setores e Unidades.
 */
class ConfiguracoesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function operador(): User
    {
        return User::factory()->operador()->create();
    }

    // ── Hub e permissões ──────────────────────────────────────

    public function test_admin_ve_central_de_configuracoes(): void
    {
        $this->actingAs($this->admin())
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Dados da empresa')
            ->assertSee('Setores')
            ->assertSee('Unidades');
    }

    public function test_operador_bloqueado_nas_configuracoes(): void
    {
        $op = $this->operador();
        $this->actingAs($op)->get(route('settings.index'))->assertForbidden();
        $this->actingAs($op)->get(route('sectors.index'))->assertForbidden();
        $this->actingAs($op)->get(route('units.index'))->assertForbidden();
    }

    // ── Setores ───────────────────────────────────────────────

    public function test_admin_cria_setor(): void
    {
        $this->actingAs($this->admin())
            ->post(route('sectors.store'), ['name' => 'Pintura', 'active' => '1'])
            ->assertRedirect();

        $this->assertDatabaseHas('sectors', ['name' => 'Pintura', 'active' => true]);
    }

    public function test_setor_nome_obrigatorio_e_unico(): void
    {
        Sector::factory()->create(['name' => 'Metalúrgica']);

        $this->actingAs($this->admin())->post(route('sectors.store'), [])->assertSessionHasErrors('name');
        $this->actingAs($this->admin())->post(route('sectors.store'), ['name' => 'Metalúrgica'])->assertSessionHasErrors('name');
    }

    public function test_admin_edita_setor(): void
    {
        $sector = Sector::factory()->create(['name' => 'Antigo', 'active' => true]);

        $this->actingAs($this->admin())
            ->put(route('sectors.update', $sector), ['name' => 'Renomeado'])
            ->assertRedirect();

        $fresh = $sector->fresh();
        $this->assertSame('Renomeado', $fresh->name);
        $this->assertFalse((bool) $fresh->active); // checkbox ausente = inativo
    }

    public function test_admin_remove_setor_sem_materiais(): void
    {
        $sector = Sector::factory()->create();

        $this->actingAs($this->admin())->delete(route('sectors.destroy', $sector))->assertRedirect();

        $this->assertDatabaseMissing('sectors', ['id' => $sector->id]);
    }

    public function test_setor_com_materiais_nao_pode_ser_removido(): void
    {
        $sector = Sector::factory()->create();
        Item::factory()->create(['sector_id' => $sector->id]);

        $this->actingAs($this->admin())->delete(route('sectors.destroy', $sector))->assertRedirect();

        $this->assertDatabaseHas('sectors', ['id' => $sector->id]);
    }

    // ── Unidades ──────────────────────────────────────────────

    public function test_admin_cria_unidade(): void
    {
        $this->actingAs($this->admin())
            ->post(route('units.store'), ['name' => 'Metro', 'abbreviation' => 'M'])
            ->assertRedirect();

        $this->assertDatabaseHas('units', ['name' => 'Metro', 'abbreviation' => 'M']);
    }

    public function test_unidade_nome_obrigatorio_e_unico(): void
    {
        Unit::factory()->create(['name' => 'Quilograma']);

        $this->actingAs($this->admin())->post(route('units.store'), [])->assertSessionHasErrors('name');
        $this->actingAs($this->admin())->post(route('units.store'), ['name' => 'Quilograma'])->assertSessionHasErrors('name');
    }

    public function test_admin_edita_unidade(): void
    {
        $unit = Unit::factory()->create(['name' => 'Antigo', 'abbreviation' => 'XX']);

        $this->actingAs($this->admin())
            ->put(route('units.update', $unit), ['name' => 'Litro', 'abbreviation' => 'L'])
            ->assertRedirect();

        $fresh = $unit->fresh();
        $this->assertSame('Litro', $fresh->name);
        $this->assertSame('L', $fresh->abbreviation);
    }

    public function test_admin_remove_unidade_sem_uso(): void
    {
        $unit = Unit::factory()->create();

        $this->actingAs($this->admin())->delete(route('units.destroy', $unit))->assertRedirect();

        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    public function test_unidade_em_uso_nao_pode_ser_removida(): void
    {
        $unit = Unit::factory()->create();
        Item::factory()->create(['unit_id' => $unit->id]);

        $this->actingAs($this->admin())->delete(route('units.destroy', $unit))->assertRedirect();

        $this->assertDatabaseHas('units', ['id' => $unit->id]);
    }
}
