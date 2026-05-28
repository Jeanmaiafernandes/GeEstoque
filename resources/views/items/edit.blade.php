@extends('layouts.app')

@section('title', 'Editar material')

@section('content')
    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('items.index') }}" class="hover:underline">Materiais</a>
        <span>/</span>
        <a href="{{ route('items.show', $item) }}" class="hover:underline">{{ $item->name }}</a>
        <span>/</span>
        <span>Editar</span>
    </div>

    <h1 class="text-2xl font-semibold text-slate-800">Editar material</h1>

    <form method="POST" action="{{ route('items.update', $item) }}" class="mt-6 max-w-3xl rounded-lg bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        @include('items._form')

        <div class="mt-6 flex flex-wrap gap-2">
            <button type="submit" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Salvar</button>
            <a href="{{ route('items.show', $item) }}" class="rounded border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</a>
            <button type="submit" form="item-delete"
                    class="rounded border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Remover material</button>
        </div>
    </form>

    {{-- Formulário de remoção associado ao botão acima via atributo form --}}
    <form id="item-delete" method="POST" action="{{ route('items.destroy', $item) }}"
          onsubmit="return confirm('Tem certeza que deseja remover este material? Esta ação não pode ser desfeita.');">
        @csrf
        @method('DELETE')
    </form>
@endsection
