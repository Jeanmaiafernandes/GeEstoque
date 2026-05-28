@extends('layouts.app')

@section('title', 'Configurações')

@section('content')
    <h1 class="text-2xl font-semibold text-slate-800">Configurações</h1>
    <p class="mt-1 text-slate-600">Dados e cadastros gerais do sistema.</p>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('settings.company.edit') }}" class="flex items-start gap-4 rounded-xl bg-white p-5 shadow-sm hover:shadow">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-teal-50 text-teal-700" aria-hidden="true">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21a.75.75 0 0 1 .75.75V21"/></svg>
            </span>
            <div>
                <h2 class="font-semibold text-slate-800">Dados da empresa</h2>
                <p class="mt-1 text-sm text-slate-500">Nome, CNPJ, contato e logo (usados nos relatórios e no login).</p>
            </div>
        </a>

        <a href="{{ route('sectors.index') }}" class="flex items-start gap-4 rounded-xl bg-white p-5 shadow-sm hover:shadow">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-teal-50 text-teal-700" aria-hidden="true">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
            </span>
            <div>
                <h2 class="font-semibold text-slate-800">Setores</h2>
                <p class="mt-1 text-sm text-slate-500">Cadastre e gerencie os setores (Almoxarifado, Metalúrgica…).</p>
            </div>
        </a>

        <a href="{{ route('units.index') }}" class="flex items-start gap-4 rounded-xl bg-white p-5 shadow-sm hover:shadow">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-teal-50 text-teal-700" aria-hidden="true">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m0-3.75h7.5c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-9.75 0h17.25"/></svg>
            </span>
            <div>
                <h2 class="font-semibold text-slate-800">Unidades</h2>
                <p class="mt-1 text-sm text-slate-500">Unidades de medida (UN, KG, M…) usadas nos materiais.</p>
            </div>
        </a>

        <a href="{{ route('settings.params.edit') }}" class="flex items-start gap-4 rounded-xl bg-white p-5 shadow-sm hover:shadow">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-teal-50 text-teal-700" aria-hidden="true">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/></svg>
            </span>
            <div>
                <h2 class="font-semibold text-slate-800">Parâmetros</h2>
                <p class="mt-1 text-sm text-slate-500">Limite do PDF, período padrão dos relatórios e do painel.</p>
            </div>
        </a>
    </div>
@endsection
