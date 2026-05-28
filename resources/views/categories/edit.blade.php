@extends('layouts.app')

@section('title', 'Editar categoria')

@section('content')
    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('categories.index') }}" class="hover:underline">Categorias</a>
        <span>/</span>
        <span>Editar</span>
    </div>

    <h1 class="text-2xl font-semibold text-slate-800">Editar categoria</h1>

    <form method="POST" action="{{ route('categories.update', $category) }}" class="mt-6 max-w-md rounded-lg bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <p class="mb-3 text-sm text-red-600">{{ $errors->first() }}</p>
        @endif

        <label for="name" class="block text-sm font-medium text-slate-700">Nome</label>
        <input id="name" type="text" name="name" value="{{ old('name', $category->name) }}" required
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">

        <label for="sector_id" class="mt-4 block text-sm font-medium text-slate-700">Setor</label>
        <select id="sector_id" name="sector_id" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">Global (todos os setores)</option>
            @foreach ($sectors as $sector)
                <option value="{{ $sector->id }}" @selected(old('sector_id', $category->sector_id) == $sector->id)>{{ $sector->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-400">Global = visível em todos os setores. Mudar o setor não altera os materiais já cadastrados.</p>

        <label class="mt-4 flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_weight_category" value="1"
                   class="rounded border-slate-300"
                   @checked(old('is_weight_category', $category->is_weight_category))>
            Categoria por peso (kg)
            <span class="text-xs text-slate-400">— materiais desta categoria usam R$/kg</span>
        </label>
        <p class="mt-1 text-xs text-slate-400">
            Ao alterar esta opção, os materiais já vinculados terão o modo de precificação atualizado automaticamente ao serem editados.
        </p>

        <div class="mt-6 flex gap-2">
            <button type="submit" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Salvar</button>
            <a href="{{ route('categories.index') }}" class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancelar</a>
        </div>
    </form>
@endsection
