<?php

namespace App\Http\Controllers;

use App\Enums\ItemType;
use App\Enums\MovementType;
use App\Models\Category;
use App\Models\Item;
use App\Models\Sector;
use App\Models\Unit;
use App\Services\StockService;
use App\Support\DanfePdfParser;
use App\Support\NfeParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NfeImportController extends Controller
{
    /** Normalização de unidades da nota => [nome, abreviação]. */
    private const UNIDADES = [
        'PC' => ['Peça', 'PC'], 'PÇ' => ['Peça', 'PC'], 'PR' => ['Par', 'PR'],
        'KG' => ['Quilograma', 'KG'], 'G' => ['Grama', 'g'], 'RL' => ['Rolo', 'RL'],
        'M' => ['Metro', 'm'], 'MT' => ['Metro', 'm'], 'M²' => ['Metro quadrado', 'm²'],
        'M2' => ['Metro quadrado', 'm²'], 'LT' => ['Litro', 'L'], 'L' => ['Litro', 'L'],
        'UN' => ['Peça/Unidade', 'un'], 'PCT' => ['Pacote', 'pct'],
    ];

    public function create(): View
    {
        return view('nfe.create');
    }

    public function preview(Request $request, NfeParser $xmlParser, DanfePdfParser $pdfParser): View|RedirectResponse
    {
        $request->validate([
            'arquivo' => ['required', 'file', 'max:8192'],
        ], [], ['arquivo' => 'arquivo']);

        $path = $request->file('arquivo')->getRealPath();
        $isPdf = str_starts_with((string) file_get_contents($path, false, null, 0, 8), '%PDF');

        try {
            $dados = $isPdf
                ? $pdfParser->parse($path)
                : $xmlParser->parse((string) file_get_contents($path));
        } catch (\Throwable $e) {
            return back()->withErrors(['arquivo' => $e->getMessage()]);
        }

        return view('nfe.preview', [
            'dados' => $dados,
            'produtos' => collect($dados['produtos']),
            'categories' => Category::orderBy('name')->get(),
            'sectors' => Sector::orderBy('name')->get(),
            'source' => $isPdf ? 'pdf' : 'xml',
            'chave' => $dados['chave'] ?? null,
        ]);
    }

    public function store(Request $request, StockService $stock): RedirectResponse
    {
        $entradaLines = [];

        foreach ($request->input('lines', []) as $line) {
            if (empty($line['incluir'])) {
                continue;
            }

            $amount = (float) ($line['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $price = (float) ($line['price'] ?? 0);
            $name = trim((string) ($line['xprod'] ?? '')) ?: 'Material importado';
            $barcode = trim((string) ($line['barcode'] ?? ''));
            $categoryName = trim((string) ($line['category'] ?? ''));
            $sectorId = ! empty($line['sector_id']) ? (int) $line['sector_id'] : null;
            $location = trim((string) ($line['location'] ?? '')) ?: null;

            $categoryId = $categoryName !== '' ? Category::firstOrCreate(['name' => $categoryName])->id : null;

            // Dedupe por código de barras: se já existe, reaproveita; senão cria material novo.
            $item = $barcode !== '' ? Item::firstWhere('barcode', $barcode) : null;

            if ($item === null) {
                $codeBase = $barcode !== '' ? 'IMP-' . preg_replace('/[^A-Za-z0-9]/', '', $barcode) : 'IMP-' . mb_strtoupper(Str::random(5));
                $item = Item::create([
                    'code' => $this->codigoUnico($codeBase),
                    'name' => $name,
                    'barcode' => $barcode !== '' ? $barcode : null,
                    'category_id' => $categoryId,
                    'sector_id' => $sectorId,
                    'unit_id' => $this->resolveUnit($line['ucom'] ?? null)->id,
                    'type' => ItemType::MateriaPrima->value,
                    'location' => $location,
                    'min_stock' => 0,
                    'current_stock' => 0,
                    'unit_price' => $price > 0 ? $price : null,
                    'active' => true,
                ]);
            } else {
                $updates = [];
                if ($price > 0) {
                    $updates['unit_price'] = $price;
                }
                if ($categoryId !== null && $item->category_id === null) {
                    $updates['category_id'] = $categoryId;
                }
                if ($sectorId !== null && $item->sector_id === null) {
                    $updates['sector_id'] = $sectorId;
                }
                if ($location !== null && (string) $item->location === '') {
                    $updates['location'] = $location;
                }
                if ($updates !== []) {
                    $item->update($updates);
                }
            }

            $entradaLines[] = ['item_id' => $item->id, 'amount' => $amount];
        }

        if ($entradaLines === []) {
            return redirect()->route('nfe.create')
                ->withErrors(['arquivo' => 'Nenhum item incluído com quantidade. Envie a nota novamente.']);
        }

        $numero = $request->input('numero');
        $fornecedor = $request->input('fornecedor');
        $cabecalho = 'NF-e' . (! empty($numero) ? ' nº ' . $numero : '');
        $notes = ! empty($fornecedor) ? $cabecalho . ' - ' . $fornecedor : $cabecalho;

        $movement = $stock->register(
            type: MovementType::Entrada,
            lines: $entradaLines,
            sectorId: null,
            notes: $notes,
            userId: $request->user()->id,
        );

        $count = count($entradaLines);

        return redirect()->route('movements.show', $movement)
            ->with('status', 'Entrada registrada a partir da NF-e (' . $count . ' ' . ($count === 1 ? 'item' : 'itens') . ').');
    }

    private function resolveUnit(?string $code): Unit
    {
        $key = mb_strtoupper(trim((string) $code)) ?: 'UN';
        [$name, $abbreviation] = self::UNIDADES[$key] ?? [$key, $key];

        return Unit::firstOrCreate(['abbreviation' => $abbreviation], ['name' => $name]);
    }

    private function codigoUnico(string $code): string
    {
        $base = $code;
        $i = 1;

        while (Item::where('code', $code)->exists()) {
            $i++;
            $code = $base . '-' . $i;
        }

        return $code;
    }
}
