@extends('layouts.app')

@section('title', 'Materiais')

@section('content')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-slate-800">Materiais</h1>
        @if (auth()->user()->isAdmin())
            <a href="{{ route('items.create') }}" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Novo material</a>
        @endif
    </div>

    <form method="GET" action="{{ route('items.index') }}" class="mt-4 grid gap-3 sm:grid-cols-4">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar por código, nome ou código de barras"
               class="rounded border border-slate-300 px-3 py-2 text-sm sm:col-span-2">
        <select id="filter-category" name="category_id" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">Todas as categorias</option>
            <option value="0" @selected(($filters['category_id'] ?? '') === '0')>— Sem categoria —</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" data-sector="{{ $category->sector_id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select id="filter-sector" name="sector_id" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">Todos os setores</option>
            <option value="0" @selected(($filters['sector_id'] ?? '') === '0')>— Sem setor —</option>
            @foreach ($sectors as $sector)
                <option value="{{ $sector->id }}" @selected(($filters['sector_id'] ?? '') == $sector->id)>{{ $sector->name }}</option>
            @endforeach
        </select>
        <select name="type" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">Todos os tipos</option>
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">Todos os status</option>
            <option value="below" @selected(($filters['status'] ?? '') === 'below')>Abaixo do mínimo</option>
            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Ativos</option>
            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inativos</option>
        </select>
        <div class="flex gap-2 sm:col-span-4">
            <button class="rounded bg-slate-700 px-4 py-2 text-sm text-white hover:bg-slate-600">Filtrar</button>
            <a href="{{ route('items.index') }}" class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Limpar</a>
        </div>
    </form>

    @php
        $curSort = request('sort', 'name');
        $curDir = request('dir') === 'desc' ? 'desc' : 'asc';
        $th = function ($key, $label, $alignRight = false) use ($curSort, $curDir) {
            $active = $curSort === $key;
            $nextDir = ($active && $curDir === 'asc') ? 'desc' : 'asc';
            $arrow = $active ? ($curDir === 'asc' ? '↑' : '↓') : '';
            $url = request()->fullUrlWithQuery(['sort' => $key, 'dir' => $nextDir, 'page' => null]);
            return '<th class="px-4 py-3' . ($alignRight ? ' text-right' : '') . '">'
                . '<a href="' . e($url) . '" class="inline-flex items-center gap-1 ' . ($active ? 'text-slate-700' : '') . ' hover:text-slate-700">'
                . e($label) . '<span class="text-[10px]">' . $arrow . '</span></a></th>';
        };
    @endphp
    <div class="mt-6 overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    {!! $th('code', 'Código') !!}
                    {!! $th('name', 'Nome') !!}
                    {!! $th('sector', 'Setor') !!}
                    {!! $th('category', 'Categoria') !!}
                    {!! $th('type', 'Tipo') !!}
                    {!! $th('current_stock', 'Saldo', true) !!}
                    {!! $th('min_stock', 'Mínimo', true) !!}
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($items as $item)
                    <tr class="{{ $item->isBelowMinimum() ? 'bg-amber-50' : ($item->isAboveMaxStock() ? 'bg-orange-50' : '') }}">
                        <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $item->code }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('items.show', $item) }}" class="font-medium text-slate-800 hover:underline">{{ $item->name }}</a>
                            @unless ($item->active)
                                <span class="ml-1 rounded bg-slate-200 px-1.5 py-0.5 text-xs text-slate-600">inativo</span>
                            @endunless
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $item->sector?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $item->category?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $item->type->label() }}</td>
                        <td class="px-4 py-3 text-right {{ $item->isBelowMinimum() ? 'font-semibold text-amber-700' : ($item->isAboveMaxStock() ? 'font-semibold text-orange-500' : 'text-slate-700') }}">
                            {{ $item->current_stock_label }} {{ $item->unit->abbreviation }}
                            @if ($item->priced_by_weight)
                                <br><span class="text-xs font-normal text-slate-400">{{ $item->price_per_kg_label }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-slate-500">{{ $item->min_stock_label }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            <a href="{{ route('items.show', $item) }}" class="text-sm text-slate-600 hover:underline">Ver</a>
                            @if (auth()->user()->isAdmin())
                                <a href="{{ route('items.edit', $item) }}" class="ml-3 text-sm text-slate-600 hover:underline">Editar</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-6 text-center text-slate-500">Nenhum material encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>

    <script>
    (function () {
        const selectSector = document.getElementById('filter-sector');
        const selectCat    = document.getElementById('filter-category');
        if (!selectSector || !selectCat) return;

        // Guarda as categorias reais (ignora "Todas" e "— Sem categoria —").
        const allOptions = Array.from(selectCat.options)
            .filter(o => o.value !== '' && o.value !== '0')
            .map(o => ({ id: o.value, name: o.textContent, sector: o.dataset.sector || '' }));

        // Reconstrói o select de categorias conforme o setor escolhido no filtro:
        //  - "Todos os setores" (vazio) → todas as categorias;
        //  - "— Sem setor —" (0)        → apenas as globais;
        //  - setor específico           → globais + as daquele setor.
        function rebuild(resetIfInvalid) {
            const sector = selectSector.value;             // '', '0' ou id
            const current = selectCat.value;

            // Remove só as opções de categoria reais (mantém as duas fixas do topo).
            Array.from(selectCat.options)
                .filter(o => o.value !== '' && o.value !== '0')
                .forEach(o => o.remove());

            let stillValid = (current === '' || current === '0');

            allOptions.forEach(function (cat) {
                const isGlobal = cat.sector === '';
                let show;
                if (sector === '') show = true;            // todos os setores
                else if (sector === '0') show = isGlobal;  // sem setor → só globais
                else show = isGlobal || cat.sector === sector;
                if (!show) return;

                const opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = cat.name + (isGlobal ? ' (global)' : '');
                opt.dataset.sector = cat.sector;
                if (cat.id === current) { opt.selected = true; stillValid = true; }
                selectCat.appendChild(opt);
            });

            if (resetIfInvalid && !stillValid) selectCat.value = '';
        }

        selectSector.addEventListener('change', function () { rebuild(true); });
        rebuild(false); // estado inicial preserva o que veio na URL
    })();
    </script>
@endsection
