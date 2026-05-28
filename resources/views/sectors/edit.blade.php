@extends('layouts.app')

@section('title', 'Editar setor')

@section('content')
    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('settings.index') }}" class="hover:underline">Configurações</a>
        <span>/</span>
        <a href="{{ route('sectors.index') }}" class="hover:underline">Setores</a>
        <span>/</span>
        <span>Editar</span>
    </div>

    <h1 class="text-2xl font-semibold text-slate-800">Editar setor</h1>

    <form method="POST" action="{{ route('sectors.update', $sector) }}" class="mt-6 max-w-md rounded-lg bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <p class="mb-3 text-sm text-red-600">{{ $errors->first() }}</p>
        @endif

        <label for="name" class="block text-sm font-medium text-slate-700">Nome</label>
        <input id="name" type="text" name="name" value="{{ old('name', $sector->name) }}" required
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">

        <label class="mt-4 flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="active" value="1" class="rounded border-slate-300" @checked(old('active', $sector->active))>
            Ativo
        </label>
        <p class="mt-1 text-xs text-slate-400">Setores inativos não aparecem como destino em saídas/movimentações.</p>

        <div class="mt-6 flex gap-2">
            <button type="submit" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Salvar</button>
            <a href="{{ route('sectors.index') }}" class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancelar</a>
        </div>
    </form>
@endsection
