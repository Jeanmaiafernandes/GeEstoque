@extends('layouts.app')

@section('title', 'Editar unidade')

@section('content')
    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('settings.index') }}" class="hover:underline">Configurações</a>
        <span>/</span>
        <a href="{{ route('units.index') }}" class="hover:underline">Unidades</a>
        <span>/</span>
        <span>Editar</span>
    </div>

    <h1 class="text-2xl font-semibold text-slate-800">Editar unidade</h1>

    <form method="POST" action="{{ route('units.update', $unit) }}" class="mt-6 max-w-md rounded-lg bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <p class="mb-3 text-sm text-red-600">{{ $errors->first() }}</p>
        @endif

        <label for="name" class="block text-sm font-medium text-slate-700">Nome</label>
        <input id="name" type="text" name="name" value="{{ old('name', $unit->name) }}" required
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">

        <label for="abbreviation" class="mt-4 block text-sm font-medium text-slate-700">Abreviação</label>
        <input id="abbreviation" type="text" name="abbreviation" value="{{ old('abbreviation', $unit->abbreviation) }}" maxlength="20"
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">

        <div class="mt-6 flex gap-2">
            <button type="submit" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Salvar</button>
            <a href="{{ route('units.index') }}" class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancelar</a>
        </div>
    </form>
@endsection
