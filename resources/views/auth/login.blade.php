<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Entrar</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
<div class="flex min-h-full">

    {{-- Painel da marca (esquerda) — visível em telas grandes --}}
    <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-gradient-to-br from-teal-800 via-teal-700 to-teal-600 p-12 text-white lg:flex">
        {{-- Formas decorativas --}}
        <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-white/10"></div>
        <div class="pointer-events-none absolute -bottom-24 -left-12 h-80 w-80 rounded-full bg-white/5"></div>
        <div class="pointer-events-none absolute right-10 bottom-24 h-24 w-24 rounded-full bg-white/5"></div>

        {{-- Topo: logo + nome --}}
        <div class="relative flex items-center gap-3">
            @if (! empty($companyLogo))
                <span class="grid h-11 w-11 place-items-center overflow-hidden rounded-xl bg-white">
                    <img src="{{ $companyLogo }}" alt="Logo" class="h-11 w-11 object-contain p-1">
                </span>
            @else
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-white/15 text-lg font-bold tracking-tight" aria-hidden="true">Ge</span>
            @endif
            <span class="text-xl font-semibold tracking-tight">{{ config('app.name') }}</span>
        </div>

        {{-- Meio: chamada --}}
        <div class="relative">
            <h2 class="text-3xl font-bold leading-tight">Controle de estoque<br>simples e completo.</h2>
            <p class="mt-3 max-w-sm text-teal-100">Materiais, movimentações, requisições e inventário — tudo em um só lugar.</p>
            <ul class="mt-8 space-y-3 text-sm text-teal-50/90">
                <li class="flex items-center gap-2">
                    <svg class="h-5 w-5 shrink-0 text-teal-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    Saldo em tempo real e alertas de reposição
                </li>
                <li class="flex items-center gap-2">
                    <svg class="h-5 w-5 shrink-0 text-teal-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    Etiquetas com código de barras e QR
                </li>
                <li class="flex items-center gap-2">
                    <svg class="h-5 w-5 shrink-0 text-teal-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    Relatórios, ficha Kardex e inventário
                </li>
            </ul>
        </div>

        {{-- Rodapé --}}
        <p class="relative text-xs text-teal-100/70">© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>

    {{-- Formulário (direita) --}}
    <div class="flex w-full flex-col items-center justify-center px-6 py-12 lg:w-1/2">
        <div class="w-full max-w-sm">
            {{-- Logo compacto (apenas no mobile, já que o painel fica oculto) --}}
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-teal-700 text-sm font-bold tracking-tight text-white" aria-hidden="true">Ge</span>
                <span class="text-lg font-semibold text-slate-800">{{ config('app.name') }}</span>
            </div>

            <h1 class="text-2xl font-bold text-slate-800">Bem-vindo de volta</h1>
            <p class="mt-1 text-sm text-slate-500">Entre com sua conta para continuar.</p>

            @if ($errors->any())
                <div class="mt-5 rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm transition focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/30">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">Senha</label>
                    <input id="password" name="password" type="password" required
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm transition focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/30">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-teal-700 focus:ring-teal-500/40">
                    Manter conectado
                </label>
                <button type="submit"
                        class="w-full rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500/40">
                    Entrar
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
