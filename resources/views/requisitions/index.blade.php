@extends('layouts.app')

@section('title', 'Requisições')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Requisições de material</h1>
            <p class="mt-1 text-sm text-slate-500">Pedidos de material por setor. A aprovação dá baixa no estoque.</p>
        </div>
        <a href="{{ route('requisitions.create') }}" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">+ Nova requisição</a>
    </div>

    <div class="mt-6 overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Data</th>
                    <th class="px-4 py-3">Setor</th>
                    <th class="px-4 py-3">Solicitante</th>
                    <th class="px-4 py-3 text-right">Itens</th>
                    <th class="px-4 py-3">Situação</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($requisitions as $req)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $req->id }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $req->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $req->sector?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $req->requester?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ $req->items_count }}</td>
                        <td class="px-4 py-3">
                            @php($s = $req->status->value)
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ $s === 'pendente' ? 'bg-amber-50 text-amber-700' : ($s === 'atendida' ? 'bg-emerald-50 text-emerald-700' : ($s === 'recusada' ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-500')) }}">
                                {{ $req->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            <a href="{{ route('requisitions.show', $req) }}" class="text-sm text-slate-600 hover:underline">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Nenhuma requisição registrada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $requisitions->links() }}</div>
@endsection
