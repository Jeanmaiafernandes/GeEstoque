<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Models\Item;
use App\Models\Sector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportacaoTest extends TestCase
{
    use RefreshDatabase;

    private ?string $path = null;

    protected function tearDown(): void
    {
        if ($this->path !== null && is_file($this->path)) {
            @unlink($this->path);
        }

        parent::tearDown();
    }

    /**
     * @param  array<int, string>  $headers
     */
    private function escrever(Worksheet $sheet, array $headers, array $dataRows): void
    {
        $sheet->setCellValue('A1', 'TÍTULO');
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '8', $header);
            $col++;
        }
        $line = 9;
        foreach ($dataRows as $row) {
            foreach ($row as $letter => $value) {
                $sheet->setCellValue($letter . $line, $value);
            }
            $line++;
        }
    }

    private function criarPlanilha(): string
    {
        $spreadsheet = new Spreadsheet();

        // Aba Almoxarifado (layout com coluna Família).
        $alm = $spreadsheet->getActiveSheet();
        $alm->setTitle('Almoxarifado');
        $this->escrever($alm,
            ['Código', 'Descrição do Item', 'Família', 'Unidade', 'Qtd. Atual', 'Estoque Mín.', 'Estoque Máx.', 'Último Preço', 'Valor Total', 'Status', 'Classe ABC', 'Localização', 'Fornecedor', 'Observações'],
            [
                ['A' => 'ALM-TST-0001', 'B' => 'Parafuso Teste', 'C' => 'PARAFUSOS', 'D' => 'PC', 'E' => 100, 'F' => 20, 'L' => 'Prateleira 1'],
                ['A' => 'ALM-TST-0002', 'B' => 'Arruela Teste', 'C' => 'ARRUELAS', 'D' => 'KG', 'E' => 5, 'F' => 10, 'L' => 'Prateleira 2'],
            ],
        );

        // Aba Metalúrgica (layout sem Família; Unidade na coluna C).
        $met = $spreadsheet->createSheet();
        $met->setTitle('Metalúrgica');
        $this->escrever($met,
            ['Código', 'Descrição do Item', 'Unidade', 'Qtd. Atual', 'Estoque Mín.', 'Estoque Máx.', 'Último Preço', 'Valor Total', 'Status', 'Localização', 'Observações'],
            [
                ['A' => 'MET-001', 'B' => 'Chapa Teste', 'C' => 'KG', 'D' => 7, 'E' => 2, 'J' => 'Galpão A'],
            ],
        );

        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'import_test_' . uniqid() . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    public function test_importa_almoxarifado_e_setores(): void
    {
        $this->path = $this->criarPlanilha();

        $this->artisan('almoxarifado:importar', ['arquivo' => $this->path])->assertSuccessful();

        $this->assertSame(3, Item::count());
        $this->assertDatabaseHas('sectors', ['name' => 'Almoxarifado']);
        $this->assertDatabaseHas('sectors', ['name' => 'Metalúrgica']);

        $parafuso = Item::with(['sector', 'category', 'unit'])->firstWhere('code', 'ALM-TST-0001');
        $this->assertSame('Almoxarifado', $parafuso->sector->name);
        $this->assertSame('PARAFUSOS', $parafuso->category->name);
        $this->assertSame('PC', $parafuso->unit->abbreviation);
        $this->assertSame('100.000', (string) $parafuso->current_stock);
        $this->assertSame('20.000', (string) $parafuso->min_stock);
        $this->assertSame('Prateleira 1', $parafuso->location);
        $this->assertSame(ItemType::MateriaPrima, $parafuso->type);

        $chapa = Item::with(['sector', 'category', 'unit'])->firstWhere('code', 'MET-001');
        $this->assertSame('Metalúrgica', $chapa->sector->name);
        $this->assertNull($chapa->category_id);
        $this->assertSame('KG', $chapa->unit->abbreviation);
        $this->assertSame('7.000', (string) $chapa->current_stock);
        $this->assertSame('Galpão A', $chapa->location);
    }

    public function test_importacao_e_idempotente(): void
    {
        $this->path = $this->criarPlanilha();

        $this->artisan('almoxarifado:importar', ['arquivo' => $this->path])->assertSuccessful();
        $this->artisan('almoxarifado:importar', ['arquivo' => $this->path])->assertSuccessful();

        $this->assertSame(3, Item::count());
    }
}
