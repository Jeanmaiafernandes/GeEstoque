@extends('layouts.app')

@section('title', 'Inventário')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Inventário rotativo</h1>
            <p class="mt-1 text-sm text-slate-500">Conte um grupo de materiais e o sistema ajusta as divergências de uma vez.</p>
        </div>
        <a href="{{ route('inventories.create') }}" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">+ Novo inventário</a>
    </div>

    <div class="mt-6 overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Início</th>
                    <th class="px-4 py-3">Setor</th>
                    <th class="px-4 py-3 text-right">Itens</th>
                    <th class="px-4 py-3">Responsável</th>
                    <th class="px-4 py-3">Situação</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($inventories as $inv)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $inv->id }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $inv->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $inv->sector?->name ?? 'Todos' }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ $inv->items_count }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $inv->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @php($s = $inv->status->value)
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ $s === 'aberto' ? 'bg-amber-50 text-amber-700' : ($s === 'finalizado' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500') }}">
                                {{ $inv->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            <a href="{{ route('inventories.show', $inv) }}" class="text-sm text-slate-600 hover:underline">
                                {{ $inv->isOpen() ? 'Contar' : 'Ver' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Nenhum inventário registrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $inventories->links() }}</div>
@endsection
