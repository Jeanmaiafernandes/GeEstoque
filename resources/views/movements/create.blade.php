@php
    use App\Enums\MovementType;
    $isSaida = $type === MovementType::Saida;
    $isAjuste = $type === MovementType::Ajuste;
    $amountLabel = $isAjuste ? 'Qtd. contada' : 'Quantidade';
    $oldLines = old('lines');
    if (! is_array($oldLines) || count($oldLines) === 0) {
        $oldLines = [[]];
    }
    $itensJson = $items->map(fn ($i) => [
        'id'       => $i->id,
        'label'    => $i->code . ' — ' . $i->name,
        'code'     => $i->code,
        'barcode'  => $i->barcode,
        'stock'    => $i->current_stock_label,
        'unit'     => $i->unit->abbreviation,
        'category' => (string) $i->category_id,
        'sector'   => (string) $i->sector_id,
    ])->values();
@endphp

@extends('layouts.app')

@section('title', 'Registrar ' . $type->label())

@section('content')
    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('movements.index') }}" class="hover:underline">Movimentações</a>
        <span>/</span>
        <span>{{ $type->label() }}</span>
    </div>

    <h1 class="text-2xl font-semibold text-slate-800">Registrar {{ $type->label() }}</h1>
    <p class="mt-1 text-sm text-slate-500">Você pode adicionar vários materiais neste mesmo documento.</p>

    <form method="POST" action="{{ route('movements.store') }}" class="mt-6 max-w-4xl rounded-lg bg-white p-6 shadow-sm">
        @csrf
        <input type="hidden" name="type" value="{{ $type->value }}">

        @if ($errors->any())
            <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($isSaida)
            <div class="mb-4 max-w-xs">
                <label for="sector_id" class="block text-sm font-medium text-slate-700">Setor de destino *</label>
                <select id="sector_id" name="sector_id" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Selecione…</option>
                    @foreach ($sectors as $sector)
                        <option value="{{ $sector->id }}" @selected(old('sector_id') == $sector->id)>{{ $sector->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($isAjuste)
            <p class="mb-4 rounded bg-amber-50 px-3 py-2 text-xs text-amber-700">
                Para cada material, informe a quantidade <strong>contada fisicamente</strong>. O sistema ajusta o saldo e registra a diferença.
            </p>
        @endif

        <div class="mb-4 grid gap-3 rounded-lg bg-slate-50 p-3 sm:grid-cols-2">
            <div>
                <label for="filtro-setor" class="block text-xs text-slate-500">Filtrar materiais por setor</label>
                <select id="filtro-setor" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Todos os setores</option>
                    @foreach ($sectors as $sector)
                        <option value="{{ $sector->id }}">{{ $sector->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filtro-categoria" class="block text-xs text-slate-500">Filtrar materiais por categoria</label>
                <select id="filtro-categoria" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Todas as categorias</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" data-sector="{{ $category->sector_id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center justify-between gap-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Itens</label>
                <p class="text-xs text-slate-400">Digite o nome ou código no campo de cada item para buscar.</p>
            </div>
            <button type="button" id="scan-open" class="inline-flex items-center gap-1.5 rounded border border-teal-600 px-3 py-1.5 text-sm font-medium text-teal-700 hover:bg-teal-50">
                <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/></svg>
                Ler com câmera
            </button>
        </div>
        <div id="linhas" class="mt-2">
            @foreach ($oldLines as $idx => $line)
                @include('movements._linha', ['idx' => $idx, 'line' => (array) $line])
            @endforeach
        </div>

        <button type="button" id="add-linha" class="mt-1 rounded border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">+ Adicionar item</button>

        <div class="mt-4">
            <label for="notes" class="block text-sm font-medium text-slate-700">Observação</label>
            <textarea id="notes" name="notes" rows="2" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">{{ old('notes') }}</textarea>
        </div>

        <div class="mt-6 flex gap-2">
            <button type="submit" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Registrar {{ $type->label() }}</button>
            <a href="{{ route('movements.index') }}" class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancelar</a>
        </div>
    </form>

    <template id="linha-template">
        @include('movements._linha', ['idx' => '__INDEX__', 'line' => []])
    </template>

    <div id="scan-modal" class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-900/60 p-4">
        <div class="w-full max-w-md rounded-lg bg-white p-4 shadow-lg">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">Ler código com a câmera</h2>
                <button type="button" id="scan-close" class="rounded px-2 py-1 text-sm text-slate-500 hover:bg-slate-100">Fechar</button>
            </div>
            <div id="scan-reader" class="mt-3 overflow-hidden rounded bg-slate-100"></div>
            <p id="scan-status" class="mt-2 text-sm text-slate-600" role="status" aria-live="polite"></p>
            <p class="mt-1 text-xs text-slate-400">Aponte para o código de barras ou QR da etiqueta. Na rede local, a câmera exige HTTPS (configuramos na implantação).</p>
        </div>
    </div>

    <script>
        window.MOV_ITENS = @json($itensJson);
    </script>
    <script>
        (function () {
            const container = document.getElementById('linhas');
            const template  = document.getElementById('linha-template');
            const filtroCat = document.getElementById('filtro-categoria');
            const filtroSet = document.getElementById('filtro-setor');
            const ITENS = window.MOV_ITENS || [];
            let index = {{ count($oldLines) }};

            function escapeHtml(s) {
                return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            }

            // Mostra no filtro de categoria apenas as do setor escolhido (+ globais).
            function filtrarCategorias() {
                const set = filtroSet.value;
                Array.from(filtroCat.options).forEach(function (o) {
                    if (!o.value) { return; }
                    const oSetor = o.dataset.sector || '';
                    o.hidden = !(!set || oSetor === '' || oSetor === set);
                });
                if (filtroCat.selectedOptions[0] && filtroCat.selectedOptions[0].hidden) {
                    filtroCat.value = '';
                }
            }

            function candidatos(q) {
                const set = filtroSet.value;
                const cat = filtroCat.value;
                q = (q || '').trim().toLowerCase();
                return ITENS.filter(function (it) {
                    if (set && it.sector !== set) { return false; }
                    if (cat && it.category !== cat) { return false; }
                    if (q && !it.label.toLowerCase().includes(q)) { return false; }
                    return true;
                }).slice(0, 50);
            }

            function renderLista(ul, q) {
                const matches = candidatos(q);
                ul.innerHTML = matches.length
                    ? matches.map(it =>
                        `<li class="opcao cursor-pointer px-3 py-1.5 text-sm hover:bg-teal-50" data-id="${it.id}" data-label="${escapeHtml(it.label)}" data-stock="${escapeHtml(it.stock)}" data-unit="${escapeHtml(it.unit)}">${escapeHtml(it.label)}</li>`
                      ).join('')
                    : '<li class="px-3 py-1.5 text-sm text-slate-400">Nenhum material encontrado.</li>';
                ul.classList.remove('hidden');
            }

            document.getElementById('add-linha').addEventListener('click', function () {
                container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index));
                index++;
            });

            container.addEventListener('click', function (e) {
                if (e.target.classList.contains('remover-linha') && container.querySelectorAll('.linha').length > 1) {
                    e.target.closest('.linha').remove();
                }
            });

            container.addEventListener('input', function (e) {
                if (!e.target.classList.contains('linha-busca')) { return; }
                const linha = e.target.closest('.linha');
                linha.querySelector('.linha-item-id').value = '';
                linha.querySelector('.linha-saldo').textContent = '';
                renderLista(linha.querySelector('.linha-opcoes'), e.target.value);
            });

            container.addEventListener('focusin', function (e) {
                if (!e.target.classList.contains('linha-busca')) { return; }
                const linha = e.target.closest('.linha');
                renderLista(linha.querySelector('.linha-opcoes'), e.target.value);
            });

            container.addEventListener('focusout', function (e) {
                if (!e.target.classList.contains('linha-busca')) { return; }
                const ul = e.target.closest('.linha').querySelector('.linha-opcoes');
                setTimeout(() => ul.classList.add('hidden'), 150);
            });

            container.addEventListener('mousedown', function (e) {
                const li = e.target.closest('.opcao');
                if (!li) { return; }
                e.preventDefault();
                const linha = li.closest('.linha');
                linha.querySelector('.linha-busca').value = li.dataset.label;
                linha.querySelector('.linha-item-id').value = li.dataset.id;
                linha.querySelector('.linha-saldo').textContent = 'Saldo atual: ' + (li.dataset.stock || '0') + ' ' + (li.dataset.unit || '');
                linha.querySelector('.linha-opcoes').classList.add('hidden');
                const amount = linha.querySelector('input[name$="[amount]"]');
                if (amount) { amount.focus(); }
            });

            filtrarCategorias();
            filtroSet.addEventListener('change', filtrarCategorias);
        })();
    </script>
@endsection
