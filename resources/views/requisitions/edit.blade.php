@php
    $oldLines = old('lines');
    if (! is_array($oldLines) || count($oldLines) === 0) {
        $oldLines = $requisition->items->map(fn ($i) => ['item_id' => $i->item_id, 'quantity' => $i->quantity])->values()->all();
        if (count($oldLines) === 0) {
            $oldLines = [[]];
        }
    }
@endphp

@extends('layouts.app')

@section('title', 'Editar requisição #' . $requisition->id)

@section('content')
    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('requisitions.index') }}" class="hover:underline">Requisições</a>
        <span>/</span>
        <a href="{{ route('requisitions.show', $requisition) }}" class="hover:underline">#{{ $requisition->id }}</a>
        <span>/</span>
        <span>Editar</span>
    </div>

    <h1 class="text-2xl font-semibold text-slate-800">Editar requisição #{{ $requisition->id }}</h1>
    <p class="mt-1 text-sm text-slate-500">Disponível apenas enquanto a requisição está pendente.</p>

    <form method="POST" action="{{ route('requisitions.update', $requisition) }}" class="mt-6 max-w-4xl rounded-lg bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-4 max-w-xs">
            <label for="sector_id" class="block text-sm font-medium text-slate-700">Setor solicitante (centro de custo) *</label>
            <select id="sector_id" name="sector_id" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                <option value="">Selecione…</option>
                @foreach ($sectors as $sector)
                    <option value="{{ $sector->id }}" @selected(old('sector_id', $requisition->sector_id) == $sector->id)>{{ $sector->name }}</option>
                @endforeach
            </select>
        </div>

        @include('requisitions._picker')

        <div class="mt-4">
            <label for="notes" class="block text-sm font-medium text-slate-700">Justificativa / observação</label>
            <textarea id="notes" name="notes" rows="2" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">{{ old('notes', $requisition->notes) }}</textarea>
        </div>

        <div class="mt-6 flex gap-2">
            <button type="submit" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Salvar alterações</button>
            <a href="{{ route('requisitions.show', $requisition) }}" class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancelar</a>
        </div>
    </form>

    @include('requisitions._picker_script')
@endsection
