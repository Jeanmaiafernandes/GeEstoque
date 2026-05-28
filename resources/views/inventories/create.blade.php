@extends('layouts.app')

@section('title', 'Novo inventário')

@section('content')
    <div class="mb-4 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('inventories.index') }}" class="hover:underline">Inventário</a>
        <span>/</span>
        <span>Novo</span>
    </div>

    <h1 class="text-2xl font-semibold text-slate-800">Novo inventário</h1>
    <p class="mt-1 text-sm text-slate-500">Escolha o que contar. O sistema vai listar os materiais ativos com o saldo atual para você conferir.</p>

    <form method="POST" action="{{ route('inventories.store') }}" class="mt-6 max-w-xl rounded-lg bg-white p-6 shadow-sm">
        @csrf

        @if ($errors->any())
            <p class="mb-3 rounded border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $errors->first() }}</p>
        @endif

        <label for="sector_id" class="block text-sm font-medium text-slate-700">Setor</label>
        <select id="sector_id" name="sector_id" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">Todos os setores</option>
            @foreach ($sectors as $sector)
                <option value="{{ $sector->id }}" @selected(old('sector_id') == $sector->id)>{{ $sector->name }}</option>
            @endforeach
        </select>

        <label for="category_id" class="mt-4 block text-sm font-medium text-slate-700">Categoria (opcional)</label>
        <select id="category_id" name="category_id" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">Todas as categorias</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" data-sector="{{ $category->sector_id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-400">Restrinja para contar um grupo menor de cada vez (contagem cíclica).</p>

        <label for="notes" class="mt-4 block text-sm font-medium text-slate-700">Observação (opcional)</label>
        <input id="notes" name="notes" type="text" value="{{ old('notes') }}" maxlength="255" placeholder="Ex.: Inventário mensal Marcenaria"
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">

        <div class="mt-6 flex gap-2">
            <button type="submit" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Iniciar contagem</button>
            <a href="{{ route('inventories.index') }}" class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancelar</a>
        </div>
    </form>

    <script>
    (function () {
        const selectSector = document.getElementById('sector_id');
        const selectCat    = document.getElementById('category_id');
        if (!selectSector || !selectCat) return;

        // Guarda as categorias reais (ignora "Todas as categorias").
        const allOptions = Array.from(selectCat.options)
            .filter(o => o.value !== '')
            .map(o => ({ id: o.value, name: o.textContent, sector: o.dataset.sector || '' }));

        // Mostra: globais + as do setor escolhido. "Todos os setores" → todas.
        function rebuild(resetIfInvalid) {
            const sector  = selectSector.value; // '' ou id
            const current = selectCat.value;

            Array.from(selectCat.options).filter(o => o.value !== '').forEach(o => o.remove());

            let stillValid = (current === '');
            allOptions.forEach(function (cat) {
                const isGlobal = cat.sector === '';
                const show = sector === '' ? true : (isGlobal || cat.sector === sector);
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
        rebuild(false); // estado inicial preserva o que veio (old)
    })();
    </script>
@endsection
