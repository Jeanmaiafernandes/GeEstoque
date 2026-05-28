@extends('layouts.app')

@section('title', 'Meu perfil')

@section('content')
    <h1 class="text-2xl font-semibold text-slate-800">Meu perfil</h1>
    <p class="mt-1 text-sm text-slate-500">Atualize seus dados e, se quiser, troque sua senha.</p>

    @if ($errors->any())
        <div class="mt-4 max-w-lg rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" class="mt-6 max-w-lg rounded-lg bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        <label for="name" class="block text-sm font-medium text-slate-700">Nome</label>
        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">

        <label for="email" class="mt-4 block text-sm font-medium text-slate-700">E-mail (login)</label>
        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">

        <p class="mt-4 rounded bg-slate-50 px-3 py-2 text-xs text-slate-500">
            Perfil de acesso: <strong>{{ $user->role?->label() ?? '—' }}</strong> — alterado apenas por um administrador na tela de Usuários.
        </p>

        <div class="mt-6 border-t border-slate-100 pt-4">
            <p class="text-sm font-semibold text-slate-700">Trocar senha</p>
            <p class="text-xs text-slate-400">Deixe em branco para manter a senha atual.</p>

            <label for="current_password" class="mt-3 block text-sm font-medium text-slate-700">Senha atual</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                   class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">

            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">Nova senha</label>
                    <input id="password" name="password" type="password" autocomplete="new-password"
                           class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirmar nova senha</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                           class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Salvar</button>
        </div>
    </form>
@endsection
