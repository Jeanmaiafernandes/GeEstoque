@extends('layouts.app')

@section('title', 'Ficha Kardex — ' . $item->name)

@section('content')
    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('items.index') }}" class="hover:underline">Materiais</a>
        <span>/</span>
        <a href="{{ route('items.show', $item) }}" class="hover:underline">{{ $item->name }}</a>
        <span>/</span>
        <span>Ficha Kardex</span>
    </div>

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Ficha Kardex</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $item->name }} · <span class="font-mono">{{ $item->code }}</span></p>
        </div>
        <div class="rounded-lg bg-white px-5 py-3 text-right shadow-sm">
            <p class="text-xs text-slate-500">Saldo atual</p>
            <p class="text-2xl font-semibold text-slate-800">
                {{ $item->current_stock_label }} <span class="text-sm font-normal text-slate-500">{{ $item->unit->abbreviation }}</span>
            </p>
        </div>
    </div>

    <form method="GET" action="{{ route('items.kardex', $item) }}" class="mt-4 grid gap-3 sm:grid-cols-4">
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded border border-slate-300 px-3 py-2 text-sm" title="De">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded border border-slate-300 px-3 py-2 text-sm" title="Até">
        <div class="flex gap-2 sm:col-span-2">
            <button class="rounded bg-slate-700 px-4 py-2 text-sm text-white hover:bg-slate-600">Filtrar período</button>
            <a href="{{ route('items.kardex', $item) }}" class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Limpar</a>
        </div>
    </form>

    <div class="mt-3 flex gap-4 text-sm">
        <a href="{{ request()->fullUrlWithQuery(['format' => 'xlsx', 'page' => null]) }}" class="text-emerald-700 hover:underline">Exportar Excel</a>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf', 'page' => null]) }}" class="text-red-700 hover:underline">Exportar PDF</a>
    </div>

    <div class="mt-4 overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Data/hora</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Setor</th>
                    <th class="px-4 py-3 text-right">Quantidade</th>
                    <th class="px-4 py-3 text-right">Saldo após</th>
                    <th class="px-4 py-3">Usuário</th>
                    <th class="px-4 py-3">Observação</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($lines as $line)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-500">{{ $line->movement->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            @php($type = $line->movement->type->value)
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ $type === 'entrada' ? 'bg-emerald-50 text-emerald-700' : ($type === 'saida' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                                {{ $line->movement->type->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $line->movement->sector?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-medium {{ (float) $line->quantity < 0 ? 'text-red-600' : 'text-emerald-700' }}">
                            {{ $line->quantity_label }} {{ $item->unit->abbreviation }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">{{ $line->balance_after_label }} {{ $item->unit->abbreviation }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $line->movement->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ \Illuminate\Support\Str::limit($line->movement->notes, 40) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Nenhuma movimentação no período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $lines->links() }}</div>
@endsection
