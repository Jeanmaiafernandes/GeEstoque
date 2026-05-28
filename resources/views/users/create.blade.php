@extends('layouts.app')

@section('title', 'Novo usuário')

@section('content')
    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('users.index') }}" class="hover:underline">Usuários</a>
        <span>/</span>
        <span>Novo</span>
    </div>

    <h1 class="text-2xl font-semibold text-slate-800">Novo usuário</h1>

    <form method="POST" action="{{ route('users.store') }}" class="mt-6 max-w-md rounded-lg bg-white p-6 shadow-sm">
        @csrf
        @include('users._form')

        <div class="mt-6 flex gap-2">
            <button type="submit" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Salvar</button>
            <a href="{{ route('users.index') }}" class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancelar</a>
        </div>
    </form>
@endsection
