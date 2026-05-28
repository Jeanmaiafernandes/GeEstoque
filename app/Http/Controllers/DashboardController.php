<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard', [
            'itemsCount' => Item::where('active', true)->count(),
            'belowMinimumCount' => Item::where('active', true)->belowMinimum()->count(),
            'todayMovements' => StockMovement::whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])->count(),
            'stockValue' => (float) Item::where('active', true)->selectRaw('SUM(current_stock * unit_price) as v')->value('v'),
            'recentLines' => StockMovementItem::with(['item.unit', 'movement.sector'])->latest()->take(8)->get(),
            'charts' => $this->charts(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function charts(): array
    {
        $chartDays = Settings::int('dashboard_chart_days', 14);
        $topItems = Settings::int('dashboard_top_items', 8);
        $start = now()->subDays($chartDays - 1)->startOfDay();

        // Movimentações por dia (período configurável) — entradas x saídas.
        $labels = [];
        $buckets = [];
        for ($i = $chartDays - 1; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $labels[] = $day->format('d/m');
            $buckets[$day->format('Y-m-d')] = ['entrada' => 0, 'saida' => 0];
        }

        foreach (StockMovement::where('created_at', '>=', $start)->get(['type', 'created_at']) as $movement) {
            $key = $movement->created_at->format('Y-m-d');
            if (! isset($buckets[$key])) {
                continue;
            }
            if ($movement->type === MovementType::Entrada) {
                $buckets[$key]['entrada']++;
            } elseif ($movement->type === MovementType::Saida) {
                $buckets[$key]['saida']++;
            }
        }

        // Consumo por setor (linhas de saída agrupadas por setor).
        $consumo = StockMovementItem::query()
            ->join('stock_movements', 'stock_movements.id', '=', 'stock_movement_items.stock_movement_id')
            ->join('sectors', 'sectors.id', '=', 'stock_movements.sector_id')
            ->where('stock_movements.type', MovementType::Saida->value)
            ->groupBy('sectors.name')
            ->orderByDesc(DB::raw('count(*)'))
            ->get([DB::raw('sectors.name as label'), DB::raw('count(*) as total')]);

        // Materiais por categoria (top 10).
        $categorias = Item::query()
            ->leftJoin('categories', 'categories.id', '=', 'items.category_id')
            ->groupBy('categories.name')
            ->orderByDesc(DB::raw('count(*)'))
            ->limit(10)
            ->get([DB::raw('categories.name as label'), DB::raw('count(*) as total')]);

        // Top itens mais movimentados (por número de linhas).
        $topItens = StockMovementItem::query()
            ->join('items', 'items.id', '=', 'stock_movement_items.item_id')
            ->groupBy('items.name')
            ->orderByDesc(DB::raw('count(*)'))
            ->limit($topItems)
            ->get([DB::raw('items.name as label'), DB::raw('count(*) as total')]);

        return [
            'movimentacoes' => [
                'labels' => $labels,
                'entradas' => array_map(fn ($b) => $b['entrada'], array_values($buckets)),
                'saidas' => array_map(fn ($b) => $b['saida'], array_values($buckets)),
            ],
            'consumoSetor' => [
                'labels' => $consumo->pluck('label')->all(),
                'data' => $consumo->pluck('total')->map(fn ($v) => (int) $v)->all(),
            ],
            'categorias' => [
                'labels' => $categorias->pluck('label')->map(fn ($v) => $v ?? 'Sem categoria')->all(),
                'data' => $categorias->pluck('total')->map(fn ($v) => (int) $v)->all(),
            ],
            'topItens' => [
                'labels' => $topItens->pluck('label')->all(),
                'data' => $topItens->pluck('total')->map(fn ($v) => (int) $v)->all(),
            ],
        ];
    }
}
