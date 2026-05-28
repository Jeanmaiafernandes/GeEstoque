@extends('layouts.app')

@section('title', 'Importar NF-e')

@section('content')
    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('movements.index') }}" class="hover:underline">Movimentações</a>
        <span>/</span>
        <span>Importar NF-e</span>
    </div>

    <h1 class="text-2xl font-semibold text-slate-800">Importar NF-e</h1>
    <p class="mt-1 text-slate-600">Envie o <strong>XML</strong> (recomendado, mais preciso) ou o <strong>PDF (DANFE)</strong> da nota. Vou ler os produtos e montar uma entrada para você conferir.</p>

    <form method="POST" action="{{ route('nfe.preview') }}" enctype="multipart/form-data" class="mt-6 max-w-xl rounded-lg bg-white p-6 shadow-sm">
        @csrf

        @if ($errors->any())
            <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <label for="arquivo" class="block text-sm font-medium text-slate-700">Arquivo da NF-e (XML ou PDF) *</label>
        <input id="arquivo" name="arquivo" type="file" accept=".xml,.pdf,text/xml,application/xml,application/pdf" required
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm file:mr-3 file:rounded file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-teal-700">
        <p class="mt-1 text-xs text-slate-400">Tamanho máximo: 8 MB. No PDF (DANFE), a leitura dos itens é aproximada — confira antes de registrar.</p>

        <div class="mt-6 flex gap-2">
            <button type="submit" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Analisar NF-e</button>
            <a href="{{ route('movements.index') }}" class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancelar</a>
        </div>
    </form>
@endsection
