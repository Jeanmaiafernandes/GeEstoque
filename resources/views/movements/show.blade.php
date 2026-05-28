@extends('layouts.app')

@section('title', 'Movimentação #' . $movement->id)

@section('content')
    @php($color = match ($movement->type) {
        \App\Enums\MovementType::Entrada => 'text-emerald-700',
        \App\Enums\MovementType::Saida => 'text-red-700',
        \App\Enums\MovementType::Ajuste => 'text-amber-700',
    })

    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('movements.index') }}" class="hover:underline">Movimentações</a>
        <span>/</span>
        <span>#{{ $movement->id }}</span>
    </div>

    <h1 class="text-2xl font-semibold text-slate-800">{{ $movement->type->label() }} — #{{ $movement->id }}</h1>

    <dl class="mt-4 grid gap-x-8 gap-y-3 rounded-lg bg-white p-6 shadow-sm sm:grid-cols-2">
        <div>
            <dt class="text-sm text-slate-500">Data/hora</dt>
            <dd class="text-slate-800">{{ $movement->created_at->format('d/m/Y H:i') }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Setor</dt>
            <dd class="text-slate-800">{{ $movement->sector?->name ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Usuário</dt>
            <dd class="text-slate-800">{{ $movement->user?->name ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Observação</dt>
            <dd class="text-slate-800">{{ $movement->notes ?: '—' }}</dd>
        </div>
    </dl>

    <h2 class="mt-6 text-lg font-semibold text-slate-800">Itens ({{ $movement->items->count() }})</h2>
    <div class="mt-3 overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Código</th>
                    <th class="px-4 py-3">Material</th>
                    <th class="px-4 py-3 text-right">Quantidade</th>
                    <th class="px-4 py-3 text-right">Saldo após</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($movement->items as $line)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $line->item->code }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('items.show', $line->item_id) }}" class="text-slate-800 hover:underline">{{ $line->item->name }}</a>
                        </td>
                        <td class="px-4 py-3 text-right font-medium {{ $color }}">{{ $line->quantity_label }} {{ $line->item->unit->abbreviation }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ $line->balance_after_label }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
