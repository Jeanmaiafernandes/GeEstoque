{{-- Filtros opcionais para reduzir os candidatos da busca de material --}}
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

<label class="block text-sm font-medium text-slate-700">Itens</label>
<p class="text-xs text-slate-400">Digite o nome ou código no campo de cada item para buscar.</p>
<div id="linhas" class="mt-2">
    @foreach ($oldLines as $idx => $line)
        @include('requisitions._linha', ['idx' => $idx, 'line' => (array) $line])
    @endforeach
</div>

<button type="button" id="add-linha" class="mt-1 rounded border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">+ Adicionar item</button>

<template id="linha-template">
    @include('requisitions._linha', ['idx' => '__INDEX__', 'line' => []])
</template>
