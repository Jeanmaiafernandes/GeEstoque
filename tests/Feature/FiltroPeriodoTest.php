<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fixa o comportamento dos filtros por data (histórico, relatório de entradas e painel).
 * Garante que a troca de whereDate() por comparação de intervalo (uso de índice) mantenha
 * o dia final inteiro incluído e o dia inicial respeitado.
 */
class FiltroPeriodoTest extends TestCase
{
    use RefreshDatabase;

    private function operador(): User
    {
        return User::factory()->operador()->create();
    }

    /** Registra uma entrada de 1 unidade num item novo e fixa o created_at do documento. */
    private function entradaEm(string $datetime, string $itemName, ?string $notes = null): StockMovement
    {
        $item = Item::factory()->withStock(0)->create(['name' => $itemName]);

        $movement = app(StockService::class)->register(
            MovementType::Entrada,
            [['item_id' => $item->id, 'amount' => 1]],
            null,
            $notes,
        );

        // update direto evita o auto-touch de updated_at e os eventos do model.
        StockMovement::whereKey($movement->id)->update(['created_at' => $datetime]);

        return $movement;
    }

    public function test_historico_inclui_o_dia_final_inteiro(): void
    {
        $this->entradaEm('2026-05-10 22:30:00', 'Cabo Dentro', 'DENTRO_FIM_DIA');
        $this->entradaEm('2026-05-11 01:00:00', 'Cabo Fora', 'FORA_DIA_SEGUINTE');

        $this->actingAs($this->operador())
            ->get(route('movements.index', ['from' => '2026-05-01', 'to' => '2026-05-10']))
            ->assertOk()
            ->assertSee('DENTRO_FIM_DIA')
            ->assertDontSee('FORA_DIA_SEGUINTE');
    }

    public function test_historico_respeita_a_data_inicial(): void
    {
        $this->entradaEm('2026-05-05 08:00:00', 'Cabo Depois', 'DEPOIS_INICIO');
        $this->entradaEm('2026-05-04 23:30:00', 'Cabo Antes', 'ANTES_INICIO');

        $this->actingAs($this->operador())
            ->get(route('movements.index', ['from' => '2026-05-05']))
            ->assertOk()
            ->assertSee('DEPOIS_INICIO')
            ->assertDontSee('ANTES_INICIO');
    }

    public function test_relatorio_entradas_inclui_o_dia_final_inteiro(): void
    {
        $this->entradaEm('2026-05-10 22:00:00', 'Material Dentro Periodo');
        $this->entradaEm('2026-05-11 00:30:00', 'Material Fora Periodo');

        $this->actingAs($this->operador())
            ->get(route('reports.entradas', ['from' => '2026-05-01', 'to' => '2026-05-10']))
            ->assertOk()
            ->assertSee('Material Dentro Periodo')
            ->assertDontSee('Material Fora Periodo');
    }

    public function test_relatorio_entradas_ordena_do_mais_recente_para_o_mais_antigo(): void
    {
        $this->entradaEm('2026-05-02 09:00:00', 'Material Mais Antigo');
        $this->entradaEm('2026-05-09 09:00:00', 'Material Mais Recente');

        $this->actingAs($this->operador())
            ->get(route('reports.entradas', ['from' => '2026-05-01', 'to' => '2026-05-31']))
            ->assertOk()
            ->assertSeeInOrder(['Material Mais Recente', 'Material Mais Antigo']);
    }

    public function test_painel_conta_apenas_movimentacoes_de_hoje(): void
    {
        $this->entradaEm(now()->copy()->setTime(23, 59, 0)->format('Y-m-d H:i:s'), 'Entrada De Hoje');
        $this->entradaEm(now()->copy()->subDay()->setTime(12, 0, 0)->format('Y-m-d H:i:s'), 'Entrada De Ontem');

        $response = $this->actingAs($this->operador())->get(route('dashboard'));

        $response->assertOk();
        $this->assertSame(1, $response->viewData('todayMovements'));
    }
}
