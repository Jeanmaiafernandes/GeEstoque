<?php

namespace App\Http\Controllers;

use App\Enums\InventoryStatus;
use App\Enums\MovementType;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\Sector;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        return view('inventories.index', [
            'inventories' => Inventory::with(['user', 'sector'])
                ->withCount('items')
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('inventories.create', [
            'sectors'    => Sector::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'sector_id'   => ['nullable', 'exists:sectors,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'notes'       => ['nullable', 'string', 'max:255'],
        ], [], ['sector_id' => 'setor', 'category_id' => 'categoria']);

        $sectorId   = $request->input('sector_id') ?: null;
        $categoryId = $request->input('category_id') ?: null;

        $items = Item::query()
            ->where('active', true)
            ->when($sectorId, fn ($q) => $q->where('sector_id', $sectorId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->orderBy('name')
            ->get(['id', 'current_stock']);

        if ($items->isEmpty()) {
            return back()->withInput()
                ->withErrors(['itens' => 'Nenhum material ativo encontrado para os filtros selecionados.']);
        }

        $inventory = Inventory::create([
            'user_id'     => $request->user()->id,
            'sector_id'   => $sectorId,
            'category_id' => $categoryId,
            'status'      => InventoryStatus::Aberto->value,
            'notes'       => $request->input('notes'),
        ]);

        $now = now();
        InventoryItem::insert($items->map(fn (Item $it) => [
            'inventory_id' => $inventory->id,
            'item_id'      => $it->id,
            'system_qty'   => $it->current_stock,
            'counted_qty'  => null,
            'created_at'   => $now,
            'updated_at'   => $now,
        ])->all());

        return redirect()->route('inventories.show', $inventory)
            ->with('status', 'Inventário iniciado com ' . $items->count() . ' itens. Lance as contagens abaixo.');
    }

    public function show(Inventory $inventory): View
    {
        $inventory->load(['user', 'sector', 'category', 'items.item.unit']);

        return view('inventories.show', ['inventory' => $inventory]);
    }

    /**
     * Salva as contagens; se action=finalizar, aplica os ajustes das divergências e encerra a sessão.
     */
    public function update(Request $request, Inventory $inventory): RedirectResponse
    {
        if (! $inventory->isOpen()) {
            return redirect()->route('inventories.show', $inventory)
                ->with('status', 'Este inventário já foi encerrado.');
        }

        $request->validate([
            'counts'   => ['array'],
            'counts.*' => ['nullable', 'numeric', 'min:0'],
        ], [], ['counts.*' => 'quantidade contada']);

        foreach ($inventory->items as $invItem) {
            $key = "counts.{$invItem->id}";
            if ($request->has($key)) {
                $value = $request->input($key);
                $invItem->counted_qty = ($value === null || $value === '') ? null : (float) $value;
                $invItem->save();
            }
        }

        if ($request->input('action') === 'finalizar') {
            $applied = $this->aplicarAjustes($inventory, (int) $request->user()->id);
            $inventory->update([
                'status'      => InventoryStatus::Finalizado->value,
                'finished_at' => now(),
            ]);

            return redirect()->route('inventories.show', $inventory)
                ->with('status', "Inventário finalizado. {$applied} ajuste(s) aplicado(s) por divergência.");
        }

        return redirect()->route('inventories.show', $inventory)
            ->with('status', 'Contagem salva. Você pode continuar depois.');
    }

    /**
     * Cancela uma sessão de contagem em aberto, sem alterar nenhum saldo.
     */
    public function cancel(Inventory $inventory): RedirectResponse
    {
        if (! $inventory->isOpen()) {
            return redirect()->route('inventories.show', $inventory)
                ->with('status', 'Este inventário já foi encerrado.');
        }

        $inventory->update([
            'status'      => InventoryStatus::Cancelado->value,
            'finished_at' => now(),
        ]);

        return redirect()->route('inventories.index')
            ->with('status', 'Inventário #' . $inventory->id . ' cancelado. Nenhum saldo foi alterado.');
    }

    /**
     * Aplica um único Ajuste com todas as linhas divergentes (contado ≠ saldo atual).
     * Itens não contados (counted_qty null) são ignorados.
     *
     * @return int número de linhas ajustadas
     */
    private function aplicarAjustes(Inventory $inventory, int $userId): int
    {
        $lines = [];

        foreach ($inventory->items()->with('item')->get() as $invItem) {
            if ($invItem->counted_qty === null || $invItem->item === null) {
                continue;
            }

            if ((float) $invItem->counted_qty !== (float) $invItem->item->current_stock) {
                $lines[] = ['item_id' => $invItem->item->id, 'amount' => (float) $invItem->counted_qty];
            }
        }

        if ($lines !== []) {
            app(StockService::class)->register(
                MovementType::Ajuste,
                $lines,
                null,
                'Inventário #' . $inventory->id,
                $userId,
            );
        }

        return count($lines);
    }
}
