@extends('layouts.app')

@section('title', 'Configurações — Parâmetros')

@section('content')
    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('settings.index') }}" class="hover:underline">Configurações</a>
        <span>/</span>
        <span>Parâmetros</span>
    </div>

    <h1 class="text-2xl font-semibold text-slate-800">Parâmetros do sistema</h1>
    <p class="mt-1 text-sm text-slate-500">Ajustes gerais de relatórios e do painel.</p>

    @if ($errors->any())
        <div class="mt-4 max-w-2xl rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.params.update') }}" class="mt-6 max-w-2xl rounded-lg bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="pdf_max_rows" class="block text-sm font-medium text-slate-700">Linhas máximas no PDF</label>
                <input id="pdf_max_rows" name="pdf_max_rows" type="number" min="50" max="100000" value="{{ old('pdf_max_rows', $pdfMaxRows) }}" required
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-400">Acima disso o PDF é truncado e sugere o Excel.</p>
            </div>
            <div>
                <label for="report_default_days" class="block text-sm font-medium text-slate-700">Período padrão dos relatórios (dias)</label>
                <input id="report_default_days" name="report_default_days" type="number" min="1" max="730" value="{{ old('report_default_days', $reportDays) }}" required
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-400">Usado em Entradas e Consumo por setor quando você não escolhe um período.</p>
            </div>
            <div>
                <label for="dashboard_chart_days" class="block text-sm font-medium text-slate-700">Período do gráfico do painel (dias)</label>
                <input id="dashboard_chart_days" name="dashboard_chart_days" type="number" min="1" max="120" value="{{ old('dashboard_chart_days', $chartDays) }}" required
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-400">Dias mostrados no gráfico "Movimentações por dia".</p>
            </div>
            <div>
                <label for="dashboard_top_items" class="block text-sm font-medium text-slate-700">Qtd. no "Top itens" do painel</label>
                <input id="dashboard_top_items" name="dashboard_top_items" type="number" min="1" max="50" value="{{ old('dashboard_top_items', $topItems) }}" required
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-400">Quantos itens aparecem no ranking de mais movimentados.</p>
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Salvar</button>
        </div>
    </form>
@endsection
