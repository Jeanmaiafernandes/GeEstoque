@extends('layouts.app')

@section('title', $item->name)

@section('content')
    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('items.index') }}" class="hover:underline">Materiais</a>
        <span>/</span>
        <span>{{ $item->name }}</span>
    </div>

    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">{{ $item->name }}</h1>
            <p class="mt-1 font-mono text-sm text-slate-500">{{ $item->code }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('items.kardex', $item) }}"
               class="rounded border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Histórico (Kardex)</a>
            <a href="{{ route('labels.print', ['item_id' => $item->id]) }}" target="_blank" rel="noopener"
               class="rounded border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Imprimir etiqueta</a>
            @if (auth()->user()->isAdmin())
                <form method="POST" action="{{ route('items.toggle-active', $item) }}"
                      onsubmit="return confirm('{{ $item->active ? 'Desativar este material? Ele deixa de aparecer nas movimentações e requisições.' : 'Reativar este material?' }}');">
                    @csrf
                    @method('PATCH')
                    @if ($item->active)
                        <button type="submit" class="rounded border border-amber-300 bg-white px-4 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50">Desativar</button>
                    @else
                        <button type="submit" class="rounded border border-emerald-300 bg-white px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">Reativar</button>
                    @endif
                </form>
                <a href="{{ route('items.edit', $item) }}" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Editar</a>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Saldo atual</p>
            <p class="mt-1 text-3xl font-semibold {{ $item->isBelowMinimum() ? 'text-amber-600' : ($item->isAboveMaxStock() ? 'text-orange-500' : 'text-slate-800') }}">
                {{ $item->current_stock_label }} <span class="text-base font-normal text-slate-500">{{ $item->unit->abbreviation }}</span>
            </p>
            @if ($item->isBelowMinimum())
                <p class="mt-1 text-xs font-medium text-amber-600">Abaixo do mínimo ({{ $item->min_stock_label }})</p>
            @elseif ($item->isAboveMaxStock())
                <p class="mt-1 text-xs font-medium text-orange-500">Acima do máximo ({{ $item->max_stock_label }})</p>
            @endif
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Estoque mínimo</p>
            <p class="mt-1 text-3xl font-semibold text-slate-800">{{ $item->min_stock_label }}</p>
            @if ($item->max_stock !== null)
                <p class="mt-2 text-sm text-slate-500">Máximo: <span class="font-medium {{ $item->isAboveMaxStock() ? 'text-orange-600' : 'text-slate-700' }}">{{ $item->max_stock_label }}</span></p>
            @endif
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Situação</p>
            <p class="mt-1 text-lg font-semibold {{ $item->active ? 'text-emerald-600' : 'text-slate-500' }}">{{ $item->active ? 'Ativo' : 'Inativo' }}</p>
            @if ($item->priced_by_weight)
                <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-700">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/></svg>
                    Por peso (kg)
                </span>
            @endif
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Valor em estoque</p>
            <p class="mt-1 text-2xl font-semibold text-slate-800">{{ $item->stock_value_label }}</p>
            @if ($item->priced_by_weight)
                <p class="mt-1 text-xs text-slate-400">{{ $item->price_per_kg_label }} · saldo {{ $item->current_stock_label }} kg</p>
                <p class="mt-0.5 text-xs text-slate-400">Máx. histórico: {{ $item->max_price_label }}</p>
            @else
                <p class="mt-1 text-xs text-slate-400">Preço unit.: {{ $item->unit_price_label }} · Máx.: {{ $item->max_price_label }}</p>
            @endif
        </div>
    </div>

    <dl class="mt-6 grid gap-x-8 gap-y-4 rounded-lg bg-white p-6 shadow-sm sm:grid-cols-2">
        <div>
            <dt class="text-sm text-slate-500">Setor responsável</dt>
            <dd class="text-slate-800">{{ $item->sector?->name ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Categoria</dt>
            <dd class="text-slate-800">{{ $item->category?->name ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Tipo</dt>
            <dd class="text-slate-800">{{ $item->type->label() }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Unidade</dt>
            <dd class="text-slate-800">{{ $item->unit->name }} ({{ $item->unit->abbreviation }})</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Localização</dt>
            <dd class="text-slate-800">{{ $item->location_label }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Código de barras / QR</dt>
            <dd class="font-mono text-slate-800">{{ $item->barcode ?: '—' }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-sm text-slate-500">Descrição</dt>
            <dd class="whitespace-pre-line text-slate-800">{{ $item->description ?: '—' }}</dd>
        </div>
    </dl>
@endsection
