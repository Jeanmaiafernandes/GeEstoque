<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Models\Category;
use App\Models\Item;
use App\Models\Sector;
use App\Models\StockMovement;
use App\Services\StockService;
use App\Support\ReportExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class MovementController extends Controller
{
    public function index(Request $request): View|Response
    {
        $filters = $request->only(['type', 'sector_id', 'from', 'to']);

        $query = StockMovement::query()
            ->with(['sector', 'user'])
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['sector_id'] ?? null, fn ($q, $id) => $q->where('sector_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->where('created_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->where('created_at', '<', Carbon::parse($date)->addDay()->startOfDay()))
            ->latest();

        $format = (string) $request->query('format', 'html');

        if (in_array($format, ['xlsx', 'pdf'], true)) {
            $columns = ['Data/hora', 'Tipo', 'Setor', 'Código', 'Material', 'Quantidade', 'Saldo após', 'Usuário', 'Observação'];

            $rows = [];
            foreach ($query->with(['items.item.unit'])->get() as $movement) {
                foreach ($movement->items as $line) {
                    $rows[] = [
                        $movement->created_at->format('d/m/Y H:i'),
                        $movement->type->label(),
                        $movement->sector?->name ?? '-',
                        $line->item->code,
                        $line->item->name,
                        $line->quantity_label . ' ' . $line->item->unit->abbreviation,
                        $line->balance_after_label,
                        $movement->user?->name ?? '-',
                        $movement->notes,
                    ];
                }
            }

            return ReportExport::make($format, 'movimentacoes', 'Histórico de Movimentações',
                'Gerado em ' . now()->format('d/m/Y H:i'), $columns, $rows);
        }

        $movements = $query->withCount('items')->paginate(20)->withQueryString();

        return view('movements.index', [
            'movements' => $movements,
            'types' => MovementType::options(),
            'sectors' => Sector::orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(string $type): View
    {
        return view('movements.create', [
            'type' => MovementType::from($type),
            'items' => Item::with('unit')->where('active', true)->orderBy('name')->get(),
            'sectors' => Sector::where('active', true)->orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, StockService $stock): RedirectResponse
    {
        // Descarta linhas em branco (linhas adicionadas e não preenchidas) antes de validar.
        $lines = collect($request->input('lines', []))
            ->filter(fn ($line) => filled($line['item_id'] ?? null))
            ->values()
            ->all();
        $request->merge(['lines' => $lines]);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(MovementType::class)],
            'sector_id' => ['nullable', 'exists:sectors,id', Rule::requiredIf($request->input('type') === MovementType::Saida->value)],
            'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.amount' => ['required', 'numeric', 'min:0'],
        ], [], [
            'sector_id' => 'setor',
            'lines' => 'itens',
            'lines.*.item_id' => 'material',
            'lines.*.amount' => 'quantidade',
        ]);

        $type = MovementType::from($validated['type']);

        if (in_array($type, [MovementType::Entrada, MovementType::Saida], true)) {
            foreach ($validated['lines'] as $index => $line) {
                if ((float) $line['amount'] <= 0) {
                    return back()->withInput()->withErrors(["lines.{$index}.amount" => 'A quantidade deve ser maior que zero.']);
                }
            }
        }

        $movement = $stock->register(
            type: $type,
            lines: $validated['lines'],
            sectorId: $type === MovementType::Saida ? (int) $validated['sector_id'] : null,
            notes: $validated['notes'] ?? null,
            userId: $request->user()->id,
        );

        $count = $movement->items->count();

        return redirect()->route('movements.show', $movement)
            ->with('status', 'Movimentação registrada: ' . $type->label() . ' (' . $count . ' ' . ($count === 1 ? 'item' : 'itens') . ').');
    }

    public function show(StockMovement $movement): View
    {
        $movement->load(['items.item.unit', 'sector', 'user']);

        return view('movements.show', ['movement' => $movement]);
    }
}
