@extends('layouts.app')

@section('title', 'Inventário #' . $inventory->id)

@php($open = $inventory->isOpen())
@php($linhas = $inventory->items->sortBy(fn ($i) => $i->item?->name))

@section('content')
    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('inventories.index') }}" class="hover:underline">Inventário</a>
        <span>/</span>
        <span>#{{ $inventory->id }}</span>
    </div>

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Inventário #{{ $inventory->id }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $inventory->sector?->name ?? 'Todos os setores' }}
                @if ($inventory->category) · {{ $inventory->category->name }} @endif
                · iniciado em {{ $inventory->created_at->format('d/m/Y H:i') }}
                @if ($inventory->user) · {{ $inventory->user->name }} @endif
            </p>
            @if ($inventory->notes)
                <p class="mt-1 text-sm text-slate-500">{{ $inventory->notes }}</p>
            @endif
        </div>
        @php($s = $inventory->status->value)
        <span class="inline-flex rounded-full px-3 py-1 text-sm font-medium
            {{ $s === 'aberto' ? 'bg-amber-50 text-amber-700' : ($s === 'finalizado' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500') }}">
            {{ $inventory->status->label() }}
        </span>
    </div>

    @if ($open)
        <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Lance a quantidade contada de cada item. <strong>Salvar</strong> guarda o progresso;
            <strong>Finalizar</strong> aplica os ajustes só onde houver divergência e encerra o inventário.
        </p>

        <form method="POST" action="{{ route('inventories.update', $inventory) }}" class="mt-4">
            @csrf
            @method('PUT')

            <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Código</th>
                            <th class="px-4 py-3">Material</th>
                            <th class="px-4 py-3">Local</th>
                            <th class="px-4 py-3 text-right">Saldo sistema</th>
                            <th class="px-4 py-3 text-right">Contado</th>
                            <th class="px-4 py-3 text-right">Divergência</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($linhas as $linha)
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs text-slate-600">{{ $linha->item?->code }}</td>
                                <td class="px-4 py-2 text-slate-800">{{ $linha->item?->name }}</td>
                                <td class="px-4 py-2 text-slate-500">{{ $linha->item?->location_label }}</td>
                                <td class="px-4 py-2 text-right text-slate-600">
                                    {{ rtrim(rtrim(number_format((float) $linha->system_qty, 3, ',', '.'), '0'), ',') }}
                                    <span class="text-xs text-slate-400">{{ $linha->item?->unit?->abbreviation }}</span>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <input type="number" step="0.001" min="0"
                                           name="counts[{{ $linha->id }}]"
                                           value="{{ $linha->counted_qty !== null ? rtrim(rtrim(number_format((float) $linha->counted_qty, 3, '.', ''), '0'), '.') : '' }}"
                                           data-system="{{ (float) $linha->system_qty }}"
                                           class="js-count w-28 rounded border border-slate-300 px-2 py-1 text-right text-sm"
                                           placeholder="—">
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <span class="js-div text-slate-400">—</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <button type="submit" name="action" value="salvar"
                        class="rounded border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Salvar contagem
                </button>
                <button type="submit" name="action" value="finalizar"
                        onclick="return confirm('Finalizar o inventário? Os saldos com divergência serão ajustados e a sessão será encerrada.');"
                        class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">
                    Finalizar e aplicar ajustes
                </button>
                {{-- Botão associado ao formulário de cancelamento (rota diferente) via atributo form. --}}
                <button type="submit" form="inv-cancel"
                        class="rounded border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
                    Cancelar inventário
                </button>
            </div>
        </form>

        <form id="inv-cancel" method="POST" action="{{ route('inventories.cancel', $inventory) }}"
              onsubmit="return confirm('Cancelar este inventário? A contagem será descartada e nenhum saldo será alterado.');">
            @csrf
        </form>

        <script>
        (function () {
            function fmt(n) {
                const v = Math.round(n * 1000) / 1000;
                return (v > 0 ? '+' : '') + String(v).replace('.', ',');
            }
            document.querySelectorAll('.js-count').forEach(function (input) {
                const cell = input.closest('tr').querySelector('.js-div');
                function update() {
                    if (input.value === '') { cell.textContent = '—'; cell.className = 'js-div text-slate-400'; return; }
                    const diff = parseFloat(input.value) - parseFloat(input.dataset.system || '0');
                    if (Math.abs(diff) < 0.0005) { cell.textContent = 'OK'; cell.className = 'js-div text-emerald-600'; }
                    else { cell.textContent = fmt(diff); cell.className = 'js-div font-medium ' + (diff < 0 ? 'text-red-600' : 'text-orange-600'); }
                }
                input.addEventListener('input', update);
                update();
            });
        })();
        </script>
    @else
        {{-- Inventário encerrado: somente leitura, mostrando as divergências apuradas. --}}
        <p class="mt-4 text-sm text-slate-500">
            Encerrado em {{ optional($inventory->finished_at)->format('d/m/Y H:i') ?? '—' }}.
            As divergências abaixo foram apuradas em relação ao saldo do sistema no momento da contagem.
        </p>

        <div class="mt-4 overflow-x-auto rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">Material</th>
                        <th class="px-4 py-3 text-right">Saldo sistema</th>
                        <th class="px-4 py-3 text-right">Contado</th>
                        <th class="px-4 py-3 text-right">Divergência</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($linhas as $linha)
                        @php($abbr = $linha->item?->unit?->abbreviation)
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs text-slate-600">{{ $linha->item?->code }}</td>
                            <td class="px-4 py-2 text-slate-800">{{ $linha->item?->name }}</td>
                            <td class="px-4 py-2 text-right text-slate-600">{{ rtrim(rtrim(number_format((float) $linha->system_qty, 3, ',', '.'), '0'), ',') }} {{ $abbr }}</td>
                            <td class="px-4 py-2 text-right text-slate-800">
                                {{ $linha->counted_qty === null ? '— não contado' : rtrim(rtrim(number_format((float) $linha->counted_qty, 3, ',', '.'), '0'), ',') . ' ' . $abbr }}
                            </td>
                            <td class="px-4 py-2 text-right">
                                @if ($linha->divergence === null)
                                    <span class="text-slate-400">—</span>
                                @elseif (abs($linha->divergence) < 0.0005)
                                    <span class="text-emerald-600">OK</span>
                                @else
                                    <span class="font-medium {{ $linha->divergence < 0 ? 'text-red-600' : 'text-orange-600' }}">
                                        {{ ($linha->divergence > 0 ? '+' : '') . rtrim(rtrim(number_format($linha->divergence, 3, ',', '.'), '0'), ',') }} {{ $abbr }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
