@extends('layouts.app')

@section('title', 'Requisição #' . $requisition->id)

@php($isAdmin = auth()->user()->isAdmin())
@php($isOwner = auth()->id() === $requisition->requester_id)
@php($pending = $requisition->isPending())
@php($s = $requisition->status->value)

@section('content')
    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('requisitions.index') }}" class="hover:underline">Requisições</a>
        <span>/</span>
        <span>#{{ $requisition->id }}</span>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Requisição #{{ $requisition->id }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                Setor: <strong>{{ $requisition->sector?->name ?? '—' }}</strong>
                · solicitado por {{ $requisition->requester?->name ?? '—' }}
                em {{ $requisition->created_at->format('d/m/Y H:i') }}
            </p>
            @if ($requisition->notes)
                <p class="mt-1 text-sm text-slate-500">{{ $requisition->notes }}</p>
            @endif
        </div>
        <div class="flex flex-col items-end gap-2">
            <span class="inline-flex rounded-full px-3 py-1 text-sm font-medium
                {{ $s === 'pendente' ? 'bg-amber-50 text-amber-700' : ($s === 'atendida' ? 'bg-emerald-50 text-emerald-700' : ($s === 'recusada' ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-500')) }}">
                {{ $requisition->status->label() }}
            </span>
            <a href="{{ route('requisitions.print', $requisition) }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1.5 rounded border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/></svg>
                Imprimir
            </a>
        </div>
    </div>

    <div class="mt-4 overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Código</th>
                    <th class="px-4 py-3">Material</th>
                    <th class="px-4 py-3 text-right">Qtd. pedida</th>
                    <th class="px-4 py-3 text-right">Saldo atual</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($requisition->items as $line)
                    @php($abbr = $line->item?->unit?->abbreviation)
                    <tr>
                        <td class="px-4 py-2 font-mono text-xs text-slate-600">{{ $line->item?->code }}</td>
                        <td class="px-4 py-2 text-slate-800">{{ $line->item?->name }}</td>
                        <td class="px-4 py-2 text-right font-medium text-slate-800">
                            {{ rtrim(rtrim(number_format((float) $line->quantity, 3, ',', '.'), '0'), ',') }} {{ $abbr }}
                        </td>
                        <td class="px-4 py-2 text-right text-slate-500">{{ $line->item?->current_stock_label }} {{ $abbr }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Situação / decisão --}}
    @if ($requisition->status->value === 'atendida')
        <p class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            Aprovada por {{ $requisition->approver?->name ?? '—' }} em {{ optional($requisition->decided_at)->format('d/m/Y H:i') }}.
            @if ($requisition->movement)
                <a href="{{ route('movements.show', $requisition->movement) }}" class="font-medium underline">Ver baixa no estoque</a>.
            @endif
        </p>
    @elseif ($requisition->status->value === 'recusada')
        <p class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            Recusada por {{ $requisition->approver?->name ?? '—' }} em {{ optional($requisition->decided_at)->format('d/m/Y H:i') }}.
            @if ($requisition->decision_notes) <br>Motivo: {{ $requisition->decision_notes }} @endif
        </p>
    @elseif ($requisition->status->value === 'cancelada')
        <p class="mt-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">Requisição cancelada.</p>
    @endif

    {{-- Ações --}}
    @if ($pending)
        <div class="mt-4 flex flex-wrap items-start gap-2">
            @if ($isAdmin)
                <a href="{{ route('requisitions.edit', $requisition) }}"
                   class="rounded border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Editar</a>

                <form method="POST" action="{{ route('requisitions.approve', $requisition) }}"
                      onsubmit="return confirm('Aprovar a requisição e baixar o estoque?');">
                    @csrf
                    <button type="submit" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Aprovar e dar baixa</button>
                </form>

                <form method="POST" action="{{ route('requisitions.reject', $requisition) }}" class="flex items-start gap-2">
                    @csrf
                    <input type="text" name="decision_notes" maxlength="500" placeholder="Motivo (opcional)"
                           class="w-56 rounded border border-slate-300 px-3 py-2 text-sm">
                    <button type="submit" class="rounded border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Recusar</button>
                </form>
            @endif

            @if ($isOwner || $isAdmin)
                <form method="POST" action="{{ route('requisitions.cancel', $requisition) }}"
                      onsubmit="return confirm('Cancelar esta requisição?');">
                    @csrf
                    <button type="submit" class="rounded border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar requisição</button>
                </form>
            @endif
        </div>
        @unless ($isAdmin)
            <p class="mt-2 text-xs text-slate-400">Aguardando aprovação de um administrador.</p>
        @endunless
    @endif
@endsection
