@extends('layouts.app')

@section('title', 'Painel')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Painel</h1>
            <p class="mt-1 text-slate-600">Bem-vindo(a), {{ auth()->user()->name }}.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('movements.create', 'entrada') }}" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">+ Entrada</a>
            <a href="{{ route('movements.create', 'saida') }}" class="rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">− Saída</a>
            <a href="{{ route('movements.create', 'ajuste') }}" class="rounded-lg bg-amber-600 px-3 py-2 text-sm font-medium text-white hover:bg-amber-700">Ajuste</a>
        </div>
    </div>

    {{-- Cartões de indicadores --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('items.index') }}" class="flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm hover:shadow">
            <span class="grid h-12 w-12 place-items-center rounded-lg bg-teal-50 text-teal-700" aria-hidden="true">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
            </span>
            <div>
                <p class="text-sm text-slate-500">Materiais ativos</p>
                <p class="text-2xl font-semibold text-slate-800">{{ $itemsCount }}</p>
            </div>
        </a>

        <a href="{{ route('items.index', ['status' => 'below']) }}" class="flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm hover:shadow">
            <span class="grid h-12 w-12 place-items-center rounded-lg bg-amber-50 text-amber-700" aria-hidden="true">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
            </span>
            <div>
                <p class="text-sm text-slate-500">Abaixo do mínimo</p>
                <p class="text-2xl font-semibold {{ $belowMinimumCount > 0 ? 'text-amber-600' : 'text-slate-800' }}">{{ $belowMinimumCount }}</p>
            </div>
        </a>

        <a href="{{ route('movements.index') }}" class="flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm hover:shadow">
            <span class="grid h-12 w-12 place-items-center rounded-lg bg-teal-50 text-teal-700" aria-hidden="true">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
            </span>
            <div>
                <p class="text-sm text-slate-500">Movimentações hoje</p>
                <p class="text-2xl font-semibold text-slate-800">{{ $todayMovements }}</p>
            </div>
        </a>

        <a href="{{ route('reports.saldo') }}" class="flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm hover:shadow">
            <span class="grid h-12 w-12 place-items-center rounded-lg bg-teal-50 text-teal-700" aria-hidden="true">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
            <div>
                <p class="text-sm text-slate-500">Valor em estoque</p>
                <p class="text-2xl font-semibold text-slate-800">R$ {{ number_format($stockValue, 2, ',', '.') }}</p>
            </div>
        </a>
    </div>

    {{-- Gráficos de KPI --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <section class="rounded-xl bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-700">Movimentações por dia (14 dias)</h2>
            <div class="relative mt-3 h-64">
                <canvas id="chart-mov" role="img" aria-label="Gráfico de barras de entradas e saídas por dia nos últimos 14 dias."></canvas>
            </div>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-700">Consumo por setor</h2>
            <div class="relative mt-3 h-64">
                @if (count($charts['consumoSetor']['labels']) > 0)
                    <canvas id="chart-setor" role="img" aria-label="Gráfico de rosca do consumo (saídas) por setor."></canvas>
                @else
                    <p class="grid h-full place-items-center text-sm text-slate-400">Sem saídas registradas ainda.</p>
                @endif
            </div>
            <ul class="sr-only">
                @foreach ($charts['consumoSetor']['labels'] as $i => $label)
                    <li>{{ $label }}: {{ $charts['consumoSetor']['data'][$i] }}</li>
                @endforeach
            </ul>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-700">Materiais por categoria</h2>
            <div class="relative mt-3 h-64">
                @if (count($charts['categorias']['labels']) > 0)
                    <canvas id="chart-cat" role="img" aria-label="Gráfico de barras de quantidade de materiais por categoria."></canvas>
                @else
                    <p class="grid h-full place-items-center text-sm text-slate-400">Nenhum material cadastrado.</p>
                @endif
            </div>
            <ul class="sr-only">
                @foreach ($charts['categorias']['labels'] as $i => $label)
                    <li>{{ $label }}: {{ $charts['categorias']['data'][$i] }}</li>
                @endforeach
            </ul>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-700">Top itens mais movimentados</h2>
            <div class="relative mt-3 h-64">
                @if (count($charts['topItens']['labels']) > 0)
                    <canvas id="chart-top" role="img" aria-label="Gráfico de barras dos itens com mais movimentações."></canvas>
                @else
                    <p class="grid h-full place-items-center text-sm text-slate-400">Nenhuma movimentação ainda.</p>
                @endif
            </div>
            <ul class="sr-only">
                @foreach ($charts['topItens']['labels'] as $i => $label)
                    <li>{{ $label }}: {{ $charts['topItens']['data'][$i] }}</li>
                @endforeach
            </ul>
        </section>
    </div>

    {{-- Últimas movimentações --}}
    <h2 class="mt-8 text-lg font-semibold text-slate-800">Últimas movimentações</h2>
    <div class="mt-3 overflow-x-auto rounded-xl bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Data/hora</th>
                    <th class="px-4 py-3">Material</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3 text-right">Qtd.</th>
                    <th class="px-4 py-3">Setor</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($recentLines as $line)
                    @php($color = match ($line->movement->type) {
                        \App\Enums\MovementType::Entrada => 'text-emerald-700',
                        \App\Enums\MovementType::Saida => 'text-red-700',
                        \App\Enums\MovementType::Ajuste => 'text-amber-700',
                    })
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-500">
                            <a href="{{ route('movements.show', $line->stock_movement_id) }}" class="hover:underline">{{ $line->created_at->format('d/m/Y H:i') }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('items.show', $line->item_id) }}" class="text-slate-800 hover:underline">{{ $line->item->name }}</a>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $line->movement->type->label() }}</td>
                        <td class="px-4 py-3 text-right font-medium {{ $color }}">{{ $line->quantity_label }} {{ $line->item->unit->abbreviation }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $line->movement->sector?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Nenhuma movimentação registrada ainda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script id="kpi-data" type="application/json">@json($charts)</script>
@endsection
