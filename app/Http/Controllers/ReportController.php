<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Models\Category;
use App\Models\Item;
use App\Models\Sector;
use App\Models\StockMovementItem;
use App\Support\ReportExport;
use App\Support\Settings;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function index(): Renderable
    {
        return view('reports.index');
    }

    public function saldoAtual(Request $request): Response|Renderable
    {
        $categoryId = $request->query('category_id');
        $sectorId = $request->query('sector_id');

        $items = Item::with(['category', 'sector', 'unit'])
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($sectorId, fn ($q) => $q->where('sector_id', $sectorId))
            ->orderBy('name')
            ->get();

        $columns = ['Código', 'Material', 'Setor', 'Categoria', 'Unidade', 'Saldo', 'Estoque mínimo', 'Preço unit.', 'Preço máx.', 'Valor', 'Situação'];

        $rows = $items->map(fn (Item $i) => [
            $i->code,
            $i->name,
            $i->sector?->name ?? '-',
            $i->category?->name ?? '-',
            $i->unit->abbreviation,
            $i->current_stock_label,
            $i->min_stock_label,
            $i->unit_price_label,
            $i->max_price_label,
            $i->stock_value_label,
            $i->isBelowMinimum() ? 'Abaixo do mínimo' : ($i->active ? 'OK' : 'Inativo'),
        ])->all();

        $total = $items->sum(fn (Item $i) => $i->stock_value ?? 0);
        $rows[] = ['', 'TOTAL', '', '', '', '', '', '', '', 'R$ ' . number_format($total, 2, ',', '.'), ''];

        return $this->respond($request, 'saldo-atual', 'Relatório de Saldo Atual',
            $this->subtitle(count($items) . ' materiais', $categoryId, $sectorId), $columns, $rows,
            withCategory: true, withSector: true);
    }

    public function abaixoMinimo(Request $request): Response|Renderable
    {
        $categoryId = $request->query('category_id');
        $sectorId = $request->query('sector_id');

        $items = Item::with(['category', 'sector', 'unit'])
            ->where('active', true)
            ->belowMinimum()
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($sectorId, fn ($q) => $q->where('sector_id', $sectorId))
            ->orderBy('name')
            ->get();

        $columns = ['Código', 'Material', 'Setor', 'Categoria', 'Unidade', 'Saldo', 'Estoque mínimo', 'Faltam'];

        $rows = $items->map(function (Item $i) {
            $falta = max(0, (float) $i->min_stock - (float) $i->current_stock);

            return [
                $i->code,
                $i->name,
                $i->sector?->name ?? '-',
                $i->category?->name ?? '-',
                $i->unit->abbreviation,
                $i->current_stock_label,
                $i->min_stock_label,
                rtrim(rtrim(number_format($falta, 3, ',', '.'), '0'), ','),
            ];
        })->all();

        return $this->respond($request, 'abaixo-do-minimo', 'Itens Abaixo do Estoque Mínimo',
            $this->subtitle(count($rows) . ' itens', $categoryId, $sectorId), $columns, $rows,
            withCategory: true, withSector: true);
    }

    public function entradas(Request $request): Response|Renderable
    {
        $categoryId = $request->query('category_id');
        $sectorId = $request->query('sector_id');
        $from = $request->query('from', now()->subDays(Settings::int('report_default_days', 30))->toDateString());
        $to = $request->query('to', now()->toDateString());

        $lines = StockMovementItem::query()
            ->with(['item.unit', 'movement.user'])
            ->join('stock_movements', 'stock_movements.id', '=', 'stock_movement_items.stock_movement_id')
            ->where('stock_movements.type', MovementType::Entrada->value)
            ->where('stock_movements.created_at', '>=', Carbon::parse($from)->startOfDay())
            ->where('stock_movements.created_at', '<', Carbon::parse($to)->addDay()->startOfDay())
            ->when($categoryId, fn ($q) => $q->whereHas('item', fn ($i) => $i->where('category_id', $categoryId)))
            ->when($sectorId, fn ($q) => $q->whereHas('item', fn ($i) => $i->where('sector_id', $sectorId)))
            ->orderByDesc('stock_movements.created_at')
            ->select('stock_movement_items.*')
            ->get();

        $columns = ['Data/hora', 'Código', 'Material', 'Quantidade', 'Usuário', 'Observação'];

        $rows = $lines->map(fn (StockMovementItem $line) => [
            $line->movement->created_at->format('d/m/Y H:i'),
            $line->item->code,
            $line->item->name,
            $line->quantity_label . ' ' . $line->item->unit->abbreviation,
            $line->movement->user?->name ?? '-',
            $line->movement->notes,
        ])->all();

        $subtitle = 'Período: ' . $this->fmtDate($from) . ' a ' . $this->fmtDate($to) . ' — ' . count($rows) . ' entradas';
        if ($sectorId && $sector = Sector::find($sectorId)) {
            $subtitle .= ' — Setor: ' . $sector->name;
        }

        return $this->respond($request, 'entradas-por-periodo', 'Entradas por Período',
            $subtitle, $columns, $rows, ['from' => $from, 'to' => $to], withCategory: true, withSector: true);
    }

    /**
     * Consumo por setor (centro de custo): saídas agrupadas pelo setor de destino,
     * somando quantidade e valor por item. Valor estimado pelo preço unitário atual.
     */
    public function consumoSetor(Request $request): Response|Renderable
    {
        $categoryId = $request->query('category_id');
        $sectorId = $request->query('sector_id');
        $from = $request->query('from', now()->subDays(Settings::int('report_default_days', 30))->toDateString());
        $to = $request->query('to', now()->toDateString());

        $lines = StockMovementItem::query()
            ->with(['item.unit', 'item.category', 'movement.sector'])
            ->join('stock_movements', 'stock_movements.id', '=', 'stock_movement_items.stock_movement_id')
            ->where('stock_movements.type', MovementType::Saida->value)
            ->where('stock_movements.created_at', '>=', Carbon::parse($from)->startOfDay())
            ->where('stock_movements.created_at', '<', Carbon::parse($to)->addDay()->startOfDay())
            ->when($sectorId, fn ($q) => $q->where('stock_movements.sector_id', $sectorId))
            ->when($categoryId, fn ($q) => $q->whereHas('item', fn ($i) => $i->where('category_id', $categoryId)))
            ->select('stock_movement_items.*')
            ->get();

        // Agrupa por setor de destino → item, somando quantidade e valor.
        $bySector = [];
        foreach ($lines as $line) {
            $sectorName = $line->movement->sector?->name ?? 'Sem setor';
            $qty = abs((float) $line->quantity);
            $value = $qty * (float) ($line->item->unit_price ?? 0);

            $bySector[$sectorName] ??= ['items' => [], 'total' => 0.0];
            $key = $line->item_id;
            $bySector[$sectorName]['items'][$key] ??= [
                'code'     => $line->item->code,
                'name'     => $line->item->name,
                'category' => $line->item->category?->name ?? '-',
                'unit'     => $line->item->unit->abbreviation,
                'qty'      => 0.0,
                'value'    => 0.0,
            ];
            $bySector[$sectorName]['items'][$key]['qty'] += $qty;
            $bySector[$sectorName]['items'][$key]['value'] += $value;
            $bySector[$sectorName]['total'] += $value;
        }
        ksort($bySector);

        $columns = ['Setor', 'Código', 'Material', 'Categoria', 'Unidade', 'Qtd. consumida', 'Valor (R$)'];

        $rows = [];
        $grandTotal = 0.0;
        foreach ($bySector as $sectorName => $data) {
            $items = $data['items'];
            uasort($items, fn ($a, $b) => strcmp($a['name'], $b['name']));

            foreach ($items as $it) {
                $rows[] = [
                    $sectorName,
                    $it['code'],
                    $it['name'],
                    $it['category'],
                    $it['unit'],
                    $this->fmtQty($it['qty']),
                    'R$ ' . number_format($it['value'], 2, ',', '.'),
                ];
            }

            $rows[] = ['', '', 'Subtotal — ' . $sectorName, '', '', '', 'R$ ' . number_format($data['total'], 2, ',', '.')];
            $grandTotal += $data['total'];
        }

        if ($rows !== []) {
            $rows[] = ['', '', 'TOTAL GERAL', '', '', '', 'R$ ' . number_format($grandTotal, 2, ',', '.')];
        }

        $subtitle = 'Período: ' . $this->fmtDate($from) . ' a ' . $this->fmtDate($to)
            . ' — Consumo por setor (valores pelo preço unitário atual)';
        if ($sectorId && $sector = Sector::find($sectorId)) {
            $subtitle .= ' — Setor: ' . $sector->name;
        }

        return $this->respond($request, 'consumo-por-setor', 'Consumo por Setor',
            $subtitle, $columns, $rows, ['from' => $from, 'to' => $to], withCategory: true, withSector: true);
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<string, string>|null  $period
     */
    private function respond(Request $request, string $slug, string $title, ?string $subtitle, array $columns, array $rows, ?array $period = null, bool $withCategory = false, bool $withSector = false): Response|Renderable
    {
        $format = (string) $request->query('format', 'html');

        $export = ReportExport::make($format, $slug, $title, $subtitle, $columns, $rows);

        if ($export !== null) {
            return $export;
        }

        return view('reports.show', [
            'title' => $title,
            'subtitle' => $subtitle,
            'columns' => $columns,
            'rows' => $rows,
            'period' => $period,
            'categories' => $withCategory ? Category::orderBy('name')->get() : null,
            'selectedCategory' => $request->query('category_id'),
            'sectors' => $withSector ? Sector::orderBy('name')->get() : null,
            'selectedSector' => $request->query('sector_id'),
        ]);
    }

    private function subtitle(string $base, ?string $categoryId, ?string $sectorId = null): string
    {
        $subtitle = 'Gerado em ' . now()->format('d/m/Y H:i') . ' — ' . $base;

        if ($sectorId && $sector = Sector::find($sectorId)) {
            $subtitle .= ' — Setor: ' . $sector->name;
        }

        if ($categoryId && $category = Category::find($categoryId)) {
            $subtitle .= ' — Categoria: ' . $category->name;
        }

        return $subtitle;
    }

    private function fmtDate(string $date): string
    {
        return Carbon::parse($date)->format('d/m/Y');
    }

    private function fmtQty(float $value): string
    {
        $number = number_format($value, 3, ',', '.');

        return str_contains($number, ',') ? rtrim(rtrim($number, '0'), ',') : $number;
    }
}
