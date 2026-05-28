<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Config 3 — Parâmetros do sistema (admin):
 * linhas máx. no PDF, período padrão dos relatórios, dias do gráfico e top itens do painel.
 */
class ParametrosTest extends TestCase
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

    public function test_admin_ve_parametros(): void
    {
        $this->actingAs($this->admin())
            ->get(route('settings.params.edit'))
            ->assertOk()
            ->assertSee('Parâmetros');
    }

    public function test_operador_bloqueado(): void
    {
        $this->actingAs($this->operador())
            ->get(route('settings.params.edit'))
            ->assertForbidden();
    }

    public function test_admin_salva_parametros(): void
    {
        $this->actingAs($this->admin())->put(route('settings.params.update'), [
            'pdf_max_rows'         => 1500,
            'report_default_days'  => 45,
            'dashboard_chart_days' => 30,
            'dashboard_top_items'  => 10,
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'pdf_max_rows', 'value' => '1500']);
        $this->assertDatabaseHas('settings', ['key' => 'report_default_days', 'value' => '45']);
        $this->assertDatabaseHas('settings', ['key' => 'dashboard_chart_days', 'value' => '30']);
        $this->assertDatabaseHas('settings', ['key' => 'dashboard_top_items', 'value' => '10']);
    }

    public function test_valores_invalidos_rejeitados(): void
    {
        $this->actingAs($this->admin())->put(route('settings.params.update'), [
            'pdf_max_rows'         => 0,      // abaixo do mínimo
            'report_default_days'  => 'abc',  // não numérico
            'dashboard_chart_days' => 14,
            'dashboard_top_items'  => 8,
        ])->assertSessionHasErrors(['pdf_max_rows', 'report_default_days']);
    }

    public function test_periodo_padrao_afeta_relatorio_de_consumo(): void
    {
        Settings::put(['report_default_days' => 7]);
        $esperado = now()->subDays(7)->toDateString();

        $this->actingAs($this->admin())
            ->get(route('reports.consumo'))
            ->assertOk()
            ->assertSee($esperado);
    }
}
