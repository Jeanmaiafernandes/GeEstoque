<?php

namespace App\Console\Commands;

use App\Enums\ItemType;
use App\Models\Category;
use App\Models\Item;
use App\Models\Sector;
use App\Models\Unit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportarAlmoxarifado extends Command
{
    protected $signature = 'almoxarifado:importar
        {arquivo : Caminho do arquivo .xlsx}
        {--tipo=materia_prima : Classificação do tipo: familia | materia_prima | consumivel}';

    protected $description = 'Importa os materiais das abas de área (Almoxarifado, Metalúrgica, Marcenaria, Expedição) de uma planilha .xlsx';

    /** Abas a importar => setor responsável atribuído. */
    private const AREAS = [
        'ALMOXARIFADO' => 'Almoxarifado',
        'METALÚRGICA' => 'Metalúrgica',
        'METALURGICA' => 'Metalúrgica',
        'MARCENARIA' => 'Marcenaria',
        'EXPEDIÇÃO' => 'Expedição',
        'EXPEDICAO' => 'Expedição',
    ];

    /** Famílias tratadas como Consumível/Ferramenta quando --tipo=familia. */
    private const CONSUMIVEIS = ['FERRAMENTAS E EQUIPAMENTOS', 'LIXAS E ABRASIVOS', 'EPI E SEGURANÇA', 'SOLDA E ELETRODOS'];

    /** Normalização de unidades da planilha => [nome, abreviação]. */
    private const UNIDADES = [
        'PC' => ['Peça', 'PC'],
        'PÇ' => ['Peça', 'PC'],
        'PR' => ['Par', 'PR'],
        'KG' => ['Quilograma', 'KG'],
        'G' => ['Grama', 'g'],
        'RL' => ['Rolo', 'RL'],
        'M' => ['Metro', 'm'],
        'MT' => ['Metro', 'm'],
        'M²' => ['Metro quadrado', 'm²'],
        'M2' => ['Metro quadrado', 'm²'],
        'LT' => ['Litro', 'L'],
        'L' => ['Litro', 'L'],
        'UN' => ['Peça/Unidade', 'un'],
        'PCT' => ['Pacote', 'pct'],
    ];

    public function handle(): int
    {
        $path = $this->argument('arquivo');

        if (! is_file($path)) {
            $this->error("Arquivo não encontrado: {$path}");

            return self::FAILURE;
        }

        $this->info('Lendo a planilha…');

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $sheetNames = $reader->listWorksheetNames($path);

        $totalCriados = 0;
        $totalAtualizados = 0;

        foreach (self::AREAS as $keyword => $sectorName) {
            $sheetName = collect($sheetNames)->first(fn ($n) => str_contains(mb_strtoupper($n), $keyword));

            if ($sheetName === null) {
                continue;
            }

            $reader->setLoadSheetsOnly([$sheetName]);
            $rows = $reader->load($path)->getActiveSheet()->toArray(null, false, false, false);

            $cols = $this->mapearColunas($rows);
            if ($cols === null) {
                $this->warn("Aba '{$sheetName}': cabeçalho não localizado, ignorada.");
                continue;
            }

            $sector = Sector::firstOrCreate(['name' => $sectorName]);

            [$criados, $atualizados] = $this->importarAba($rows, $cols, $sector);
            $totalCriados += $criados;
            $totalAtualizados += $atualizados;

            $this->line("  {$sectorName}: {$criados} criados, {$atualizados} atualizados.");
        }

        $this->newLine();
        $this->info("Importação concluída: {$totalCriados} criados, {$totalAtualizados} atualizados.");

        return self::SUCCESS;
    }

    /**
     * Localiza o cabeçalho e mapeia nome da coluna => índice.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<string, int>|null
     */
    private function mapearColunas(array $rows): ?array
    {
        foreach ($rows as $row) {
            if (! isset($row[0]) || mb_strtolower(trim((string) $row[0])) !== 'código') {
                continue;
            }

            $cols = [];
            foreach ($row as $index => $cell) {
                $h = mb_strtolower(trim((string) $cell));
                match (true) {
                    str_contains($h, 'código') => $cols['code'] = $index,
                    str_contains($h, 'descrição') => $cols['name'] = $index,
                    $h === 'família' => $cols['family'] = $index,
                    $h === 'unidade' => $cols['unit'] = $index,
                    str_contains($h, 'qtd') || str_contains($h, 'quantidade') => $cols['qty'] = $index,
                    str_contains($h, 'mín') || str_contains($h, 'mini') => $cols['min'] = $index,
                    str_contains($h, 'localiz') => $cols['location'] = $index,
                    str_contains($h, 'preço') || str_contains($h, 'preco') => $cols['price'] = $index,
                    default => null,
                };
            }

            return isset($cols['code'], $cols['name'], $cols['unit'], $cols['qty']) ? $cols : null;
        }

        return null;
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<string, int>  $cols
     * @return array{0: int, 1: int} [criados, atualizados]
     */
    private function importarAba(array $rows, array $cols, Sector $sector): array
    {
        $tipoOpcao = $this->option('tipo');
        $criados = 0;
        $atualizados = 0;

        DB::transaction(function () use ($rows, $cols, $sector, $tipoOpcao, &$criados, &$atualizados) {
            $unitCache = [];
            $categoryCache = [];

            foreach ($rows as $row) {
                $code = trim((string) ($row[$cols['code']] ?? ''));
                if ($code === '' || mb_strtolower($code) === 'código' || ! preg_match('/^[A-Za-z]{2,4}-/', $code)) {
                    continue;
                }

                $name = trim((string) ($row[$cols['name']] ?? ''));
                if ($name === '') {
                    continue;
                }

                $unitCode = mb_strtoupper(trim((string) ($row[$cols['unit']] ?? ''))) ?: 'PC';
                $unit = $unitCache[$unitCode] ??= $this->resolveUnit($unitCode);

                $categoryId = null;
                if (isset($cols['family'])) {
                    $family = trim((string) ($row[$cols['family']] ?? ''));
                    if ($family !== '') {
                        $categoryId = ($categoryCache[mb_strtoupper($family)] ??= Category::firstOrCreate(['name' => $family]))->id;
                    }
                }

                $min = isset($cols['min']) ? $this->toNumber($row[$cols['min']] ?? null) : 0.0;
                $qty = $this->toNumber($row[$cols['qty']] ?? null);
                $price = isset($cols['price']) ? $this->toNumber($row[$cols['price']] ?? null) : 0.0;
                $location = isset($cols['location']) ? trim((string) ($row[$cols['location']] ?? '')) : '';

                $existing = Item::firstWhere('code', $code);

                $attributes = [
                    'name' => $name,
                    'category_id' => $categoryId,
                    'sector_id' => $sector->id,
                    'unit_id' => $unit->id,
                    'type' => $this->resolveType($tipoOpcao, $family ?? ''),
                    'location' => $location !== '' ? $location : null,
                    'min_stock' => $min,
                    'active' => true,
                ];

                if ($price > 0) {
                    $attributes['unit_price'] = $price;
                }

                if ($existing === null) {
                    // Saldo inicial só na criação (não sobrescreve saldo já movimentado).
                    $attributes['code'] = $code;
                    $attributes['current_stock'] = $qty;
                    Item::create($attributes);
                    $criados++;
                } else {
                    $existing->update($attributes);
                    $atualizados++;
                }
            }
        });

        return [$criados, $atualizados];
    }

    private function resolveUnit(string $code): Unit
    {
        [$name, $abbreviation] = self::UNIDADES[$code] ?? [$code, $code];

        return Unit::firstOrCreate(['abbreviation' => $abbreviation], ['name' => $name]);
    }

    private function resolveType(string $option, string $family): ItemType
    {
        return match ($option) {
            'consumivel' => ItemType::Consumivel,
            'familia' => in_array(mb_strtoupper($family), self::CONSUMIVEIS, true) ? ItemType::Consumivel : ItemType::MateriaPrima,
            default => ItemType::MateriaPrima,
        };
    }

    private function toNumber(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace(['.', ','], ['', '.'], (string) $value);

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }
}
