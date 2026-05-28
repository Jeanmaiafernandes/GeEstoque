<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Enums\RequisitionStatus;
use App\Models\Category;
use App\Models\Item;
use App\Models\Requisition;
use App\Models\Sector;
use App\Services\StockService;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class RequisitionController extends Controller implements HasMiddleware
{
    /**
     * Aprovar/recusar é exclusivo de administradores; criar e visualizar é de todos.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('role:admin', only: ['approve', 'reject', 'edit', 'update']),
        ];
    }

    public function index(): View
    {
        return view('requisitions.index', [
            'requisitions' => Requisition::with(['requester', 'sector'])
                ->withCount('items')
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('requisitions.create', [
            'items'      => Item::with('unit')->where('active', true)->orderBy('name')->get(),
            'sectors'    => Sector::where('active', true)->orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Descarta linhas em branco antes de validar.
        $lines = collect($request->input('lines', []))
            ->filter(fn ($line) => filled($line['item_id'] ?? null))
            ->values()
            ->all();
        $request->merge(['lines' => $lines]);

        $validated = $request->validate([
            'sector_id'         => ['required', 'exists:sectors,id'],
            'notes'             => ['nullable', 'string', 'max:500'],
            'lines'             => ['required', 'array', 'min:1'],
            'lines.*.item_id'   => ['required', 'exists:items,id'],
            'lines.*.quantity'  => ['required', 'numeric', 'min:0'],
        ], [], [
            'sector_id'        => 'setor',
            'lines'            => 'itens',
            'lines.*.item_id'  => 'material',
            'lines.*.quantity' => 'quantidade',
        ]);

        foreach ($validated['lines'] as $index => $line) {
            if ((float) $line['quantity'] <= 0) {
                return back()->withInput()->withErrors(["lines.{$index}.quantity" => 'A quantidade deve ser maior que zero.']);
            }
        }

        $requisition = Requisition::create([
            'requester_id' => $request->user()->id,
            'sector_id'    => $validated['sector_id'],
            'status'       => RequisitionStatus::Pendente->value,
            'notes'        => $validated['notes'] ?? null,
        ]);

        foreach ($validated['lines'] as $line) {
            $requisition->items()->create([
                'item_id'  => $line['item_id'],
                'quantity' => (float) $line['quantity'],
            ]);
        }

        return redirect()->route('requisitions.show', $requisition)
            ->with('status', 'Requisição enviada para aprovação.');
    }

    public function show(Requisition $requisition): View
    {
        $requisition->load(['items.item.unit', 'requester', 'approver', 'sector', 'movement']);

        return view('requisitions.show', ['requisition' => $requisition]);
    }

    /**
     * Versão imprimível da requisição (qualquer status, inclusive pendente).
     */
    public function print(Requisition $requisition): View
    {
        $requisition->load(['items.item.unit', 'requester', 'approver', 'sector']);

        return view('requisitions.print', [
            'requisition' => $requisition,
            'company'     => [
                'name' => Settings::get('company_name'),
                'cnpj' => Settings::get('company_cnpj'),
                'logo' => Settings::companyLogoUrl(),
            ],
        ]);
    }

    /**
     * Edição (admin) — apenas requisições pendentes.
     */
    public function edit(Requisition $requisition): View|RedirectResponse
    {
        if (! $requisition->isPending()) {
            return redirect()->route('requisitions.show', $requisition)
                ->with('status', 'Só é possível editar requisições pendentes.');
        }

        $requisition->load('items');

        return view('requisitions.edit', [
            'requisition' => $requisition,
            'items'       => Item::with('unit')->where('active', true)->orderBy('name')->get(),
            'sectors'     => Sector::where('active', true)->orderBy('name')->get(),
            'categories'  => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Requisition $requisition): RedirectResponse
    {
        if (! $requisition->isPending()) {
            return redirect()->route('requisitions.show', $requisition)
                ->with('status', 'Só é possível editar requisições pendentes.');
        }

        $lines = collect($request->input('lines', []))
            ->filter(fn ($line) => filled($line['item_id'] ?? null))
            ->values()
            ->all();
        $request->merge(['lines' => $lines]);

        $validated = $request->validate([
            'sector_id'        => ['required', 'exists:sectors,id'],
            'notes'            => ['nullable', 'string', 'max:500'],
            'lines'            => ['required', 'array', 'min:1'],
            'lines.*.item_id'  => ['required', 'exists:items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0'],
        ], [], [
            'sector_id'        => 'setor',
            'lines'            => 'itens',
            'lines.*.item_id'  => 'material',
            'lines.*.quantity' => 'quantidade',
        ]);

        foreach ($validated['lines'] as $index => $line) {
            if ((float) $line['quantity'] <= 0) {
                return back()->withInput()->withErrors(["lines.{$index}.quantity" => 'A quantidade deve ser maior que zero.']);
            }
        }

        $requisition->update([
            'sector_id' => $validated['sector_id'],
            'notes'     => $validated['notes'] ?? null,
        ]);

        // Substitui os itens pelo novo conjunto.
        $requisition->items()->delete();
        foreach ($validated['lines'] as $line) {
            $requisition->items()->create([
                'item_id'  => $line['item_id'],
                'quantity' => (float) $line['quantity'],
            ]);
        }

        return redirect()->route('requisitions.show', $requisition)
            ->with('status', 'Requisição atualizada.');
    }

    /**
     * Aprova e dá baixa: gera uma saída com o setor solicitante como destino (centro de custo).
     * Se faltar saldo, o StockService lança ValidationException e nada é alterado.
     */
    public function approve(Requisition $requisition, StockService $stock): RedirectResponse
    {
        if (! $requisition->isPending()) {
            return redirect()->route('requisitions.show', $requisition)
                ->with('status', 'Esta requisição já foi processada.');
        }

        $requisition->loadMissing('items');

        $lines = $requisition->items
            ->map(fn ($ri) => ['item_id' => $ri->item_id, 'amount' => (float) $ri->quantity])
            ->all();

        $movement = $stock->register(
            MovementType::Saida,
            $lines,
            $requisition->sector_id,
            'Requisição #' . $requisition->id,
            (int) request()->user()->id,
        );

        $requisition->update([
            'status'            => RequisitionStatus::Atendida->value,
            'approver_id'       => request()->user()->id,
            'decided_at'        => now(),
            'stock_movement_id' => $movement->id,
        ]);

        return redirect()->route('requisitions.show', $requisition)
            ->with('status', 'Requisição aprovada e baixa registrada no estoque.');
    }

    public function reject(Request $request, Requisition $requisition): RedirectResponse
    {
        if (! $requisition->isPending()) {
            return redirect()->route('requisitions.show', $requisition)
                ->with('status', 'Esta requisição já foi processada.');
        }

        $request->validate([
            'decision_notes' => ['nullable', 'string', 'max:500'],
        ], [], ['decision_notes' => 'motivo']);

        $requisition->update([
            'status'         => RequisitionStatus::Recusada->value,
            'approver_id'    => $request->user()->id,
            'decision_notes' => $request->input('decision_notes'),
            'decided_at'     => now(),
        ]);

        return redirect()->route('requisitions.show', $requisition)
            ->with('status', 'Requisição recusada.');
    }

    /**
     * Cancela uma requisição pendente. Só o próprio solicitante ou um administrador.
     */
    public function cancel(Request $request, Requisition $requisition): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && $requisition->requester_id !== $user->id) {
            abort(403);
        }

        if (! $requisition->isPending()) {
            return redirect()->route('requisitions.show', $requisition)
                ->with('status', 'Esta requisição já foi processada.');
        }

        $requisition->update(['status' => RequisitionStatus::Cancelada->value]);

        return redirect()->route('requisitions.index')
            ->with('status', 'Requisição cancelada.');
    }
}
