<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Enums\MovementType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Item;
use App\Models\Sector;
use App\Models\Unit;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelatorioTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => UserRole::Operador]);
    }

    private function seedItem(): void
    {
        $unit = Unit::create(['name' => 'Peça', 'abbreviation' => 'PC']);

        Item::create([
            'code' => 'ALM-PAR-0001',
            'name' => 'Parafuso sextavado',
            'unit_id' => $unit->id,
            'type' => ItemType::MateriaPrima->value,
            'min_stock' => 10,
            'current_stock' => 3,
        ]);
    }

    public function test_pagina_de_relatorios_abre(): void
    {
        $this->actingAs($this->user())->get(route('reports.index'))->assertOk();
    }

    public function test_saldo_atual_html(): void
    {
        $this->seedItem();

        $this->actingAs($this->user())
            ->get(route('reports.saldo'))
            ->assertOk()
            ->assertSee('Parafuso sextavado');
    }

    public function test_saldo_atual_exporta_excel(): void
    {
        $this->seedItem();

        $response = $this->actingAs($this->user())->get(route('reports.saldo', ['format' => 'xlsx']));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', (string) $response->headers->get('content-type'));
    }

    public function test_saldo_atual_exporta_pdf(): void
    {
        $this->seedItem();

        $response = $this->actingAs($this->user())->get(route('reports.saldo', ['format' => 'pdf']));

        $response->assertOk();
        $this->assertStringContainsString('pdf', (string) $response->headers->get('content-type'));
    }

    public function test_relatorio_abaixo_do_minimo(): void
    {
        $this->seedItem();

        $this->actingAs($this->user())
            ->get(route('reports.abaixo'))
            ->assertOk()
            ->assertSee('Parafuso sextavado');
    }

    public function test_relatorio_entradas_por_periodo(): void
    {
        $this->actingAs($this->user())->get(route('reports.entradas'))->assertOk();
    }

    public function test_historico_exporta_excel(): void
    {
        $this->actingAs($this->user())
            ->get(route('movements.index', ['format' => 'xlsx']))
            ->assertOk();
    }

    public function test_historico_exporta_pdf(): void
    {
        // Regressão: a exportação de movimentações em PDF retornava 500
        // (dompdf estourava o limite de memória). Deve gerar o PDF com sucesso.
        $unit  = Unit::factory()->create();
        $items = Item::factory()->count(5)->create(['unit_id' => $unit->id, 'current_stock' => 0]);

        foreach ($items as $item) {
            app(StockService::class)->register(
                MovementType::Entrada,
                [['item_id' => $item->id, 'amount' => 10]],
            );
        }

        $response = $this->actingAs($this->user())
            ->get(route('movements.index', ['format' => 'pdf']));

        $response->assertOk();
        $this->assertStringContainsString('pdf', (string) $response->headers->get('content-type'));
    }

    public function test_pdf_vazio_nao_quebra(): void
    {
        $this->actingAs($this->user())
            ->get(route('movements.index', ['format' => 'pdf']))
            ->assertOk();
    }

    public function test_saldo_filtra_por_categoria(): void
    {
        $categoria = Category::factory()->create(['name' => 'Parafusos']);
        Item::factory()->create(['category_id' => $categoria->id, 'name' => 'Parafuso Sextavado M8']);
        Item::factory()->create(['name' => 'Arruela Lisa 1/4']);

        $this->actingAs($this->user())
            ->get(route('reports.saldo', ['category_id' => $categoria->id]))
            ->assertOk()
            ->assertSee('Parafuso Sextavado M8')
            ->assertDontSee('Arruela Lisa 1/4');
    }

    public function test_abaixo_do_minimo_filtra_por_categoria(): void
    {
        $categoria = Category::factory()->create();
        Item::factory()->below()->create(['category_id' => $categoria->id, 'name' => 'Critico Da Categoria']);
        Item::factory()->below()->create(['name' => 'Critico De Fora']);

        $this->actingAs($this->user())
            ->get(route('reports.abaixo', ['category_id' => $categoria->id]))
            ->assertOk()
            ->assertSee('Critico Da Categoria')
            ->assertDontSee('Critico De Fora');
    }

    public function test_saldo_filtra_por_setor(): void
    {
        $setor = Sector::factory()->create();
        Item::factory()->create(['sector_id' => $setor->id, 'name' => 'Material Do Setor']);
        Item::factory()->create(['name' => 'Material De Outro']);

        $this->actingAs($this->user())
            ->get(route('reports.saldo', ['sector_id' => $setor->id]))
            ->assertOk()
            ->assertSee('Material Do Setor')
            ->assertDontSee('Material De Outro');
    }

    // ──────────────────────────────────────────────────
    // Consumo por setor (centro de custo = setor de destino da saída)
    // ──────────────────────────────────────────────────

    public function test_consumo_por_setor_agrupa_e_soma_valor(): void
    {
        $marcenaria = Sector::factory()->create(['name' => 'Marcenaria']);
        $unit = Unit::factory()->create();
        $item = Item::factory()->create([
            'unit_id'       => $unit->id,
            'name'          => 'Parafuso Consumo',
            'unit_price'    => 10,
            'current_stock' => 100,
        ]);

        app(StockService::class)->register(
            MovementType::Saida,
            [['item_id' => $item->id, 'amount' => 20]],
            $marcenaria->id,
        );

        $this->actingAs($this->user())
            ->get(route('reports.consumo'))
            ->assertOk()
            ->assertSee('Marcenaria')
            ->assertSee('Parafuso Consumo')
            ->assertSee('200,00'); // 20 × R$ 10,00
    }

    public function test_consumo_ignora_entradas(): void
    {
        $unit = Unit::factory()->create();
        $item = Item::factory()->create([
            'unit_id'       => $unit->id,
            'name'          => 'So Entrada ZZZ',
            'unit_price'    => 5,
            'current_stock' => 0,
        ]);

        app(StockService::class)->register(MovementType::Entrada, [['item_id' => $item->id, 'amount' => 10]]);

        $this->actingAs($this->user())
            ->get(route('reports.consumo'))
            ->assertOk()
            ->assertDontSee('So Entrada ZZZ');
    }

    public function test_consumo_filtra_por_periodo(): void
    {
        $setor = Sector::factory()->create();
        $unit  = Unit::factory()->create();
        $item  = Item::factory()->create([
            'unit_id'       => $unit->id,
            'name'          => 'Item Fora Do Periodo',
            'unit_price'    => 10,
            'current_stock' => 100,
        ]);

        app(StockService::class)->register(MovementType::Saida, [['item_id' => $item->id, 'amount' => 5]], $setor->id);

        // Período totalmente no passado não inclui a saída de hoje.
        $this->actingAs($this->user())
            ->get(route('reports.consumo', ['from' => '2020-01-01', 'to' => '2020-01-31']))
            ->assertOk()
            ->assertDontSee('Item Fora Do Periodo');
    }

    public function test_consumo_exporta_excel(): void
    {
        $this->actingAs($this->user())
            ->get(route('reports.consumo', ['format' => 'xlsx']))
            ->assertOk();
    }

    public function test_consumo_exporta_pdf(): void
    {
        $response = $this->actingAs($this->user())
            ->get(route('reports.consumo', ['format' => 'pdf']));

        $response->assertOk();
        $this->assertStringContainsString('pdf', (string) $response->headers->get('content-type'));
    }
}

