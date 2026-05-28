@extends('layouts.app')

@section('title', $title)

@section('content')
    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('reports.index') }}" class="hover:underline">Relatórios</a>
        <span>/</span>
        <span>{{ $title }}</span>
    </div>

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="flex gap-2">
            <a href="{{ request()->fullUrlWithQuery(['format' => 'xlsx']) }}" class="rounded bg-emerald-700 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-800">Exportar Excel</a>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="rounded bg-red-700 px-3 py-2 text-sm font-medium text-white hover:bg-red-800">Exportar PDF</a>
        </div>
    </div>

    @if (! empty($categories) || ! empty($sectors) || ! empty($period))
        <form method="GET" action="{{ request()->url() }}" class="mt-4 flex flex-wrap items-end gap-3">
            @if (! empty($sectors))
                <div>
                    <label class="block text-xs text-slate-500">Setor</label>
                    <select id="filter-sector" name="sector_id" class="rounded border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Todos os setores</option>
                        @foreach ($sectors as $sector)
                            <option value="{{ $sector->id }}" @selected((string) ($selectedSector ?? '') === (string) $sector->id)>{{ $sector->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if (! empty($categories))
                <div>
                    <label class="block text-xs text-slate-500">Categoria</label>
                    <select id="filter-category" name="category_id" class="rounded border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Todas as categorias</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" data-sector="{{ $category->sector_id }}" @selected((string) ($selectedCategory ?? '') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if (! empty($period))
                <div>
                    <label class="block text-xs text-slate-500">De</label>
                    <input type="date" name="from" value="{{ $period['from'] }}" class="rounded border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-slate-500">Até</label>
                    <input type="date" name="to" value="{{ $period['to'] }}" class="rounded border border-slate-300 px-3 py-2 text-sm">
                </div>
            @endif
            <button class="rounded bg-slate-700 px-4 py-2 text-sm text-white hover:bg-slate-600">Aplicar</button>
            <a href="{{ request()->url() }}" class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Limpar</a>
        </form>
    @endif

    <div class="mt-6 overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    @foreach ($columns as $column)
                        <th class="px-4 py-3">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rows as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td class="px-4 py-3 text-slate-700">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) }}" class="px-4 py-6 text-center text-slate-500">Sem dados para os filtros selecionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
    (function () {
        const s = document.getElementById('filter-sector');
        const c = document.getElementById('filter-category');
        if (!s || !c) return;
        function filtrar() {
            const set = s.value;
            Array.from(c.options).forEach(function (o) {
                if (!o.value) return;
                const os = o.dataset.sector || '';
                o.hidden = !(!set || os === '' || os === set);
            });
            if (c.selectedOptions[0] && c.selectedOptions[0].hidden) c.value = '';
        }
        s.addEventListener('change', filtrar);
        filtrar();
    })();
    </script>
@endsection
