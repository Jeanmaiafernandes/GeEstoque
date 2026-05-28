@extends('layouts.app')

@section('title', 'Etiquetas')

@section('content')
    <h1 class="text-2xl font-semibold text-slate-800">Etiquetas de código de barras / QR</h1>
    <p class="mt-1 text-slate-600">Filtre os materiais e gere uma folha A4 com várias etiquetas para imprimir.</p>

    <form method="GET" action="{{ route('labels.index') }}" class="mt-4 grid gap-3 sm:grid-cols-4">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar por código ou nome"
               class="rounded border border-slate-300 px-3 py-2 text-sm sm:col-span-2">
        <select id="filter-sector" name="sector_id" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">Todos os setores</option>
            @foreach ($sectors as $sector)
                <option value="{{ $sector->id }}" @selected(($filters['sector_id'] ?? '') == $sector->id)>{{ $sector->name }}</option>
            @endforeach
        </select>
        <select id="filter-category" name="category_id" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">Todas as categorias</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" data-sector="{{ $category->sector_id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <div class="flex flex-wrap gap-2 sm:col-span-4">
            <button class="rounded bg-slate-700 px-4 py-2 text-sm text-white hover:bg-slate-600">Filtrar</button>
            <a href="{{ route('labels.index') }}" class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Limpar</a>
            @if ($items->isNotEmpty())
                <a href="{{ route('labels.print', $filters) }}" target="_blank" rel="noopener"
                   class="ml-auto rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">
                    Imprimir etiquetas ({{ $items->count() }})
                </a>
            @endif
        </div>
    </form>

    <div class="mt-6 overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Código</th>
                    <th class="px-4 py-3">Material</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($items as $item)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $item->code }}</td>
                        <td class="px-4 py-3 text-slate-800">{{ $item->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="px-4 py-6 text-center text-slate-500">Nenhum material encontrado para os filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($items->count() >= 500)
        <p class="mt-3 text-sm text-amber-600">Exibindo as 500 primeiras. Refine os filtros para imprimir em lotes menores.</p>
    @endif

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
