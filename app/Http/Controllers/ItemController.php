<?php

namespace App\Http\Controllers;

use App\Enums\ItemType;
use App\Enums\MovementType;
use App\Models\Category;
use App\Models\Item;
use App\Models\Sector;
use App\Models\Unit;
use App\Services\StockService;
use App\Support\ReportExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ItemController extends Controller implements HasMiddleware
{
    /**
     * Apenas administradores podem criar/editar/remover materiais.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('role:admin', except: ['index', 'show', 'kardex']),
        ];
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'category_id', 'sector_id', 'type', 'status']);

        // Ordenação por coluna (cabeçalhos clicáveis). Colunas relacionadas usam subconsulta.
        $sortable = [
            'code'          => 'code',
            'name'          => 'name',
            'type'          => 'type',
            'current_stock' => 'current_stock',
            'min_stock'     => 'min_stock',
            'sector'        => Sector::select('name')->whereColumn('sectors.id', 'items.sector_id'),
            'category'      => Category::select('name')->whereColumn('categories.id', 'items.category_id'),
        ];
        $sort = $request->query('sort', 'name');
        if (! array_key_exists($sort, $sortable)) {
            $sort = 'name';
        }
        $dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        $items = Item::query()
            ->with(['category', 'sector', 'unit'])
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('code', 'like', "%{$term}%")
                        ->orWhere('name', 'like', "%{$term}%")
                        ->orWhere('barcode', 'like', "%{$term}%");
                });
            })
            ->when(($filters['category_id'] ?? '') !== '', fn ($query) => $filters['category_id'] === '0'
                ? $query->whereNull('category_id')
                : $query->where('category_id', $filters['category_id']))
            ->when(($filters['sector_id'] ?? '') !== '', fn ($query) => $filters['sector_id'] === '0'
                ? $query->whereNull('sector_id')
                : $query->where('sector_id', $filters['sector_id']))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when(($filters['status'] ?? null) === 'below', fn ($query) => $query->belowMinimum())
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('active', false))
            ->orderBy($sortable[$sort], $dir)
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('items.index', [
            'items' => $items,
            'categories' => Category::orderBy('name')->get(),
            'sectors' => Sector::orderBy('name')->get(),
            'types' => ItemType::options(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('items.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['active'] = $request->boolean('active');
        // priced_by_weight é auto-sincronizado da categoria no hook saving do model.

        $item = Item::create($data);

        // Saldo inicial opcional: cria um ajuste de inventário para definir o ponto de partida.
        // Isso mantém o histórico de movimentações completo desde o primeiro dia.
        $initialStock = (float) $request->input('initial_stock', 0);
        if ($initialStock > 0) {
            app(StockService::class)->register(
                MovementType::Ajuste,
                [['item_id' => $item->id, 'amount' => $initialStock]],
                null,
                'Saldo inicial informado no cadastro',
                auth()->id(),
            );
        }

        return redirect()->route('items.show', $item)
            ->with('status', 'Material cadastrado com sucesso.');
    }

    public function show(Item $item): View
    {
        $item->load(['category', 'unit']);

        return view('items.show', ['item' => $item]);
    }

    /**
     * Ficha Kardex: histórico completo do item (entradas/saídas/ajustes) com saldo acumulado.
     * O saldo após cada movimento já está gravado em stock_movement_items.balance_after.
     */
    public function kardex(Request $request, Item $item): View|Response
    {
        $item->load('unit');

        $filters = $request->only(['from', 'to']);

        $query = $item->movementItems()
            ->with(['movement.sector', 'movement.user'])
            ->join('stock_movements', 'stock_movements.id', '=', 'stock_movement_items.stock_movement_id')
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->where('stock_movements.created_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->where('stock_movements.created_at', '<', Carbon::parse($date)->addDay()->startOfDay()))
            ->orderByDesc('stock_movements.created_at')
            ->orderByDesc('stock_movement_items.id')
            ->select('stock_movement_items.*');

        $format = (string) $request->query('format', 'html');

        if (in_array($format, ['xlsx', 'pdf'], true)) {
            $abbr = $item->unit->abbreviation;
            $columns = ['Data/hora', 'Tipo', 'Setor', 'Quantidade', 'Saldo após', 'Usuário', 'Observação'];

            $rows = [];
            foreach ($query->get() as $line) {
                $rows[] = [
                    $line->movement->created_at->format('d/m/Y H:i'),
                    $line->movement->type->label(),
                    $line->movement->sector?->name ?? '-',
                    $line->quantity_label . ' ' . $abbr,
                    $line->balance_after_label . ' ' . $abbr,
                    $line->movement->user?->name ?? '-',
                    $line->movement->notes,
                ];
            }

            return ReportExport::make(
                $format,
                'kardex-' . $item->code,
                'Ficha Kardex — ' . $item->name,
                'Código ' . $item->code . ' · Saldo atual ' . $item->current_stock_label . ' ' . $abbr
                    . ' · Gerado em ' . now()->format('d/m/Y H:i'),
                $columns,
                $rows,
            );
        }

        return view('items.kardex', [
            'item'    => $item,
            'lines'   => $query->paginate(30)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function edit(Item $item): View
    {
        return view('items.edit', array_merge($this->formData(), ['item' => $item]));
    }

    public function update(Request $request, Item $item): RedirectResponse
    {
        $data = $this->validateData($request, $item);
        $data['active'] = $request->boolean('active');
        // priced_by_weight é auto-sincronizado da categoria no hook saving do model.

        $item->update($data);

        return redirect()->route('items.show', $item)
            ->with('status', 'Material atualizado com sucesso.');
    }

    /**
     * Alterna ativo/inativo do material (alternativa à remoção para itens com movimentações).
     */
    public function toggleActive(Item $item): RedirectResponse
    {
        $item->update(['active' => ! $item->active]);

        return redirect()->route('items.show', $item)
            ->with('status', $item->active ? 'Material reativado.' : 'Material desativado.');
    }

    public function destroy(Item $item): RedirectResponse
    {
        if ($item->movementItems()->exists()) {
            return redirect()->route('items.show', $item)
                ->with('status', 'Não é possível remover um material com movimentações. Desative-o em vez de remover.');
        }

        $item->delete();

        return redirect()->route('items.index')
            ->with('status', 'Material removido.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'sectors' => Sector::orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
            'types' => ItemType::options(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateData(Request $request, ?Item $item = null): array
    {
        $validator = Validator::make($request->all(), [
            'code'         => ['required', 'string', 'max:50', Rule::unique('items', 'code')->ignore($item)],
            'barcode'      => ['nullable', 'string', 'max:100', Rule::unique('items', 'barcode')->ignore($item)],
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'category_id'  => ['nullable', 'exists:categories,id'],
            'sector_id'    => ['nullable', 'exists:sectors,id'],
            'unit_id'      => ['required', 'exists:units,id'],
            'type'         => ['required', Rule::enum(ItemType::class)],
            'location'     => ['nullable', 'string', 'max:100'],
            'aisle'        => ['nullable', 'string', 'max:30'],
            'shelf'        => ['nullable', 'string', 'max:30'],
            'position'     => ['nullable', 'string', 'max:30'],
            'min_stock'    => ['required', 'numeric', 'min:0'],
            'max_stock'    => ['nullable', 'numeric', 'min:0'],
            // Preço padrão (materiais normais)
            'unit_price'   => ['nullable', 'numeric', 'min:0'],
            // Preço por kg (ativado automaticamente quando a categoria é por peso)
            'price_per_kg' => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'code'         => 'código interno',
            'barcode'      => 'código de barras',
            'name'         => 'nome',
            'unit_id'      => 'unidade',
            'type'         => 'tipo',
            'min_stock'    => 'estoque mínimo',
            'max_stock'    => 'estoque máximo',
            'price_per_kg' => 'preço por kg',
        ]);

        // Regra de negócio: estoque máximo não pode ser menor que o mínimo.
        $validator->after(function ($v) use ($request) {
            $maxRaw = $request->input('max_stock');
            if ($maxRaw !== null && $maxRaw !== '') {
                $min = (float) $request->input('min_stock', 0);
                $max = (float) $maxRaw;
                if ($max < $min) {
                    $v->errors()->add(
                        'max_stock',
                        'O estoque máximo não pode ser menor que o mínimo (' . number_format($min, 3, ',', '.') . ').'
                    );
                }
            }
        });

        return $validator->validate();
    }
}
