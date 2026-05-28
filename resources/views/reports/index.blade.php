@extends('layouts.app')

@section('title', 'Relatórios')

@section('content')
    <h1 class="text-2xl font-semibold text-slate-800">Relatórios</h1>
    <p class="mt-2 text-slate-600">Cada relatório pode ser visualizado na tela e exportado em Excel ou PDF.</p>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('reports.saldo') }}" class="rounded-lg bg-white p-5 shadow-sm hover:shadow">
            <h2 class="font-semibold text-slate-800">Saldo atual</h2>
            <p class="mt-1 text-sm text-slate-500">Saldo de todos os materiais, com situação e estoque mínimo.</p>
        </a>
        <a href="{{ route('reports.abaixo') }}" class="rounded-lg bg-white p-5 shadow-sm hover:shadow">
            <h2 class="font-semibold text-slate-800">Abaixo do mínimo</h2>
            <p class="mt-1 text-sm text-slate-500">Itens que precisam de reposição e quanto falta para o mínimo.</p>
        </a>
        <a href="{{ route('reports.entradas') }}" class="rounded-lg bg-white p-5 shadow-sm hover:shadow">
            <h2 class="font-semibold text-slate-800">Entradas por período</h2>
            <p class="mt-1 text-sm text-slate-500">Recebimentos registrados em um intervalo de datas.</p>
        </a>
        <a href="{{ route('reports.consumo') }}" class="rounded-lg bg-white p-5 shadow-sm hover:shadow">
            <h2 class="font-semibold text-slate-800">Consumo por setor</h2>
            <p class="mt-1 text-sm text-slate-500">Saídas agrupadas por setor (centro de custo), com quantidade e valor por período.</p>
        </a>
        <a href="{{ route('movements.index') }}" class="rounded-lg bg-white p-5 shadow-sm hover:shadow">
            <h2 class="font-semibold text-slate-800">Histórico de movimentações</h2>
            <p class="mt-1 text-sm text-slate-500">Todas as movimentações, com filtros e exportação.</p>
        </a>
    </div>
@endsection
