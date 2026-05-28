@extends('layouts.app')

@section('title', 'Ajuda')

@section('content')
    <h1 class="text-2xl font-semibold text-slate-800">Ajuda</h1>
    <p class="mt-1 text-sm text-slate-500">Guia rápido das principais funções do sistema.</p>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-800">Materiais</h2>
            <p class="mt-1 text-sm text-slate-600">Cadastre materiais com código, categoria, setor, unidade, endereço (corredor/prateleira/posição), estoque mínimo/máximo e preço. O saldo só muda por movimentações.</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-800">Movimentações</h2>
            <p class="mt-1 text-sm text-slate-600">Registre entradas, saídas (com setor de destino) e ajustes. Cada documento pode ter vários itens. O histórico de cada material fica na Ficha Kardex.</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-800">Requisições</h2>
            <p class="mt-1 text-sm text-slate-600">Um setor solicita materiais; um administrador aprova e o sistema dá baixa no estoque automaticamente, ou recusa o pedido.</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-800">Inventário</h2>
            <p class="mt-1 text-sm text-slate-600">Conte um grupo de materiais; o sistema mostra as divergências e ajusta os saldos de uma vez ao finalizar.</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-800">Relatórios</h2>
            <p class="mt-1 text-sm text-slate-600">Saldo atual, itens abaixo do mínimo, entradas por período e consumo por setor — todos com exportação em Excel e PDF.</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-800">Etiquetas</h2>
            <p class="mt-1 text-sm text-slate-600">Gere e imprima etiquetas com código de barras e QR para identificar os materiais nas prateleiras.</p>
        </div>
    </div>

    <p class="mt-6 text-sm text-slate-500">
        A documentação completa do sistema está no arquivo <strong>DOCUMENTACAO.md</strong>, na pasta do projeto.
    </p>
@endsection
