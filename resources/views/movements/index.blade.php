@extends('layouts.app')

@section('title', 'Movimentações')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-2xl font-semibold text-slate-800">Movimentações</h1>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('movements.create', 'entrada') }}" class="rounded bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">+ Entrada</a>
            <a href="{{ route('movements.create', 'saida') }}" class="rounded bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">− Saída</a>
            <a href="{{ route('movements.create', 'ajuste') }}" class="rounded bg-amber-600 px-3 py-2 text-sm font-medium text-white hover:bg-amber-700">Ajuste</a>
            <a href="{{ route('nfe.create') }}" class="rounded border border-teal-600 px-3 py-2 text-sm font-medium text-teal-700 hover:bg-teal-50">Importar NF-e</a>
        </div>
    </div>

    <form method="GET" action="{{ route('movements.index') }}" class="mt-4 grid gap-3 sm:grid-cols-5">
        <select name="type" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">Todos os tipos</option>
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="sector_id" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">Todos os setores</option>
            @foreach ($sectors as $sector)
                <option value="{{ $sector->id }}" @selected(($filters['sector_id'] ?? '') == $sector->id)>{{ $sector->name }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded border border-slate-300 px-3 py-2 text-sm" title="De">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded border border-slate-300 px-3 py-2 text-sm" title="Até">
        <div class="flex gap-2">
            <button class="rounded bg-slate-700 px-4 py-2 text-sm text-white hover:bg-slate-600">Filtrar</button>
            <a href="{{ route('movements.index') }}" class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Limpar</a>
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
                    <th class="px-4 py-3 text-right">Itens</th>
                    <th class="px-4 py-3">Usuário</th>
                    <th class="px-4 py-3">Observação</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($movements as $m)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-500">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $m->type->label() }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $m->sector?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ $m->items_count }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $m->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ \Illuminate\Support\Str::limit($m->notes, 40) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('movements.show', $m) }}" class="text-sm text-slate-600 hover:underline">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Nenhuma movimentação encontrada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $movements->links() }}</div>
@endsection
