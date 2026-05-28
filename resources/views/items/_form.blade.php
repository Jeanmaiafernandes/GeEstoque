@php($item = $item ?? null)

@if ($errors->any())
    <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
        <p class="font-medium">Corrija os campos abaixo:</p>
        <ul class="mt-1 list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="code" class="block text-sm font-medium text-slate-700">Código interno *</label>
        <input id="code" name="code" type="text" value="{{ old('code', $item?->code) }}" required
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label for="barcode" class="block text-sm font-medium text-slate-700">Código de barras / QR</label>
        <input id="barcode" name="barcode" type="text" value="{{ old('barcode', $item?->barcode) }}"
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
</div>

<div class="mt-4">
    <label for="name" class="block text-sm font-medium text-slate-700">Nome *</label>
    <input id="name" name="name" type="text" value="{{ old('name', $item?->name) }}" required
           class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
</div>

<div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div>
        <label for="sector_id" class="block text-sm font-medium text-slate-700">Setor responsável</label>
        <select id="sector_id" name="sector_id" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">— Nenhum —</option>
            @foreach ($sectors as $sector)
                <option value="{{ $sector->id }}" @selected(old('sector_id', $item?->sector_id) == $sector->id)>{{ $sector->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="category_id" class="block text-sm font-medium text-slate-700">Categoria</label>
        <select id="category_id" name="category_id" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">— Sem categoria —</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $item?->category_id) == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-400">Mostra as categorias do setor escolhido + as globais.</p>
    </div>
    <div>
        <label for="unit_id" class="block text-sm font-medium text-slate-700">Unidade *</label>
        <select id="unit_id" name="unit_id" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">Selecione…</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}"
                        data-abbreviation="{{ strtolower($unit->abbreviation) }}"
                        @selected(old('unit_id', $item?->unit_id) == $unit->id)>{{ $unit->name }} ({{ $unit->abbreviation }})</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="type" class="block text-sm font-medium text-slate-700">Tipo *</label>
        <select id="type" name="type" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $item?->type?->value) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="mt-4">
    <p class="block text-sm font-medium text-slate-700">Localização (endereço)</p>
    <div class="mt-1 grid gap-4 sm:grid-cols-3">
        <div>
            <label for="aisle" class="block text-xs text-slate-500">Corredor</label>
            <input id="aisle" name="aisle" type="text" maxlength="30" value="{{ old('aisle', $item?->aisle) }}" placeholder="Ex.: A"
                   class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="shelf" class="block text-xs text-slate-500">Prateleira</label>
            <input id="shelf" name="shelf" type="text" maxlength="30" value="{{ old('shelf', $item?->shelf) }}" placeholder="Ex.: 3"
                   class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="position" class="block text-xs text-slate-500">Posição</label>
            <input id="position" name="position" type="text" maxlength="30" value="{{ old('position', $item?->position) }}" placeholder="Ex.: 02"
                   class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
        </div>
    </div>
    <div class="mt-2">
        <label for="location" class="block text-xs text-slate-500">Complemento (opcional)</label>
        <input id="location" name="location" type="text" value="{{ old('location', $item?->location) }}" placeholder="Ex.: galpão dos fundos"
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
</div>

<div class="mt-4 grid gap-4 sm:grid-cols-2">
    <div>
        <label for="min_stock" class="block text-sm font-medium text-slate-700">Estoque mínimo</label>
        <input id="min_stock" name="min_stock" type="number" step="0.001" min="0" value="{{ old('min_stock', $item?->min_stock ?? 0) }}" required
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <p class="mt-1 text-xs text-slate-400">0 = sem alerta de reposição.</p>
    </div>
    <div>
        <label for="max_stock" class="block text-sm font-medium text-slate-700">Estoque máximo</label>
        <input id="max_stock" name="max_stock" type="number" step="0.001" min="0"
               value="{{ old('max_stock', $item?->max_stock) }}"
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <p class="mt-1 text-xs text-slate-400">Deixe em branco = sem limite de estoque.</p>
    </div>
</div>

{{-- ── Precificação ──────────────────────────────────────────── --}}
<div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
    <p class="text-sm font-medium text-slate-700">Precificação</p>

    {{-- Campos para item normal (preço unitário direto) --}}
    <div id="price-normal" class="mt-3">
        <label for="unit_price" class="block text-sm font-medium text-slate-700">Preço unitário (R$)</label>
        <input id="unit_price" name="unit_price" type="number" step="0.0001" min="0"
               value="{{ old('unit_price', $item && !$item->priced_by_weight ? $item->unit_price : '') }}"
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm sm:max-w-xs">
        <p class="mt-1 text-xs text-slate-400">Último custo por peça — usado no valor em estoque.</p>
    </div>

    {{-- Campos para item precificado por peso (visível quando categoria é "Por peso") --}}
    <div id="price-weight" class="mt-3">
        <label for="price_per_kg" class="block text-sm font-medium text-slate-700">Preço (R$/kg)</label>
        <input id="price_per_kg" name="price_per_kg" type="number" step="0.0001" min="0"
               value="{{ old('price_per_kg', $item?->price_per_kg) }}"
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm sm:max-w-xs">
        <p class="mt-1 text-xs text-slate-400">Último custo por kg — <strong>valor em estoque = saldo × R$/kg</strong>.</p>
    </div>

    <p id="price-weight-info" class="mt-2 text-xs text-teal-600" style="display:none">
        Categoria por peso: certifique-se de que a unidade selecionada é <strong>KG</strong>.
    </p>

    <p id="unit-kg-warning" class="mt-2 flex items-center gap-1 text-xs font-medium text-amber-700" style="display:none">
        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
        A unidade selecionada não é KG. Verifique se está correto para esta categoria por peso.
    </p>
</div>

<div class="mt-4">
    <label for="description" class="block text-sm font-medium text-slate-700">Descrição</label>
    <textarea id="description" name="description" rows="3"
              class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">{{ old('description', $item?->description) }}</textarea>
</div>

<label class="mt-4 flex items-center gap-2 text-sm text-slate-700">
    <input type="checkbox" name="active" value="1" class="rounded border-slate-300" @checked(old('active', $item?->active ?? true))>
    Ativo
</label>

@if ($item)
    <p class="mt-4 rounded bg-slate-50 px-3 py-2 text-xs text-slate-500">
        Saldo atual: <strong>{{ $item->current_stock_label }} {{ $item->unit->abbreviation }}</strong> — alterado apenas por movimentações.
    </p>
@else
    {{-- Saldo inicial: só exibido no cadastro. Gera um ajuste de inventário automaticamente. --}}
    <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
        <label for="initial_stock" class="block text-sm font-medium text-slate-700">
            Saldo atual em estoque
        </label>
        <input id="initial_stock" name="initial_stock" type="number" step="0.001" min="0"
               value="{{ old('initial_stock', '') }}"
               placeholder="0"
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm sm:max-w-xs">
        <p class="mt-1 text-xs text-slate-400">
            Informe a quantidade já existente no almoxarifado.
            Será registrado como ajuste de inventário inicial — pode ser alterado depois por movimentações.
            Deixe em branco ou <strong>0</strong> para começar zerado.
        </p>
    </div>
@endif

<script>
(function () {
    // Todas as categorias (id, nome, setor) e quais são "por peso".
    const allCategories = @json($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'sector_id' => $c->sector_id])->values());
    const weightIds = new Set(@json($categories->where('is_weight_category', true)->pluck('id')->values()));
    const initialCategoryId = {{ (int) old('category_id', $item?->category_id ?? 0) }};

    const selectSector = document.getElementById('sector_id');
    const selectCat    = document.getElementById('category_id');
    const selectUnit   = document.getElementById('unit_id');
    const panelNormal  = document.getElementById('price-normal');
    const panelWeight  = document.getElementById('price-weight');
    const infoWeight   = document.getElementById('price-weight-info');
    const warnKg       = document.getElementById('unit-kg-warning');

    function currentSectorId() {
        const v = parseInt(selectSector.value, 10);
        return isNaN(v) ? null : v;
    }

    // Reconstrói as opções de categoria conforme o setor escolhido:
    // mostra as globais + as do setor. Em edição (preserveCurrent), mantém a
    // categoria atual mesmo que seja de outro setor (não perde o que já estava salvo).
    function rebuildCategories(preserveCurrent) {
        const sectorId = currentSectorId();
        const selected = parseInt(selectCat.value, 10) || (preserveCurrent ? initialCategoryId : 0);

        selectCat.innerHTML = '<option value="">— Sem categoria —</option>';

        allCategories.forEach(function (cat) {
            const isGlobal = cat.sector_id === null;
            const matches  = sectorId !== null && cat.sector_id === sectorId;
            const keepCurr = preserveCurrent && cat.id === initialCategoryId;
            if (!isGlobal && !matches && !keepCurr) return;

            const opt = document.createElement('option');
            opt.value = cat.id;
            let suffix = '';
            if (isGlobal) suffix = ' (global)';
            else if (!matches && keepCurr) suffix = ' (outro setor)';
            opt.textContent = cat.name + suffix;
            if (cat.id === selected) opt.selected = true;
            selectCat.appendChild(opt);
        });

        togglePanels();
    }

    function isWeightCategory() {
        const val = parseInt(selectCat.value, 10);
        return !isNaN(val) && weightIds.has(val);
    }

    function selectedUnitAbbr() {
        const opt = selectUnit.options[selectUnit.selectedIndex];
        return opt ? (opt.dataset.abbreviation || '') : '';
    }

    function togglePanels() {
        const byWeight = isWeightCategory();
        panelNormal.style.display = byWeight ? 'none' : '';
        panelWeight.style.display = byWeight ? ''     : 'none';
        infoWeight.style.display  = byWeight ? ''     : 'none';

        // Aviso: categoria por peso mas unidade não é KG
        if (warnKg) {
            warnKg.style.display = (byWeight && selectedUnitAbbr() !== 'kg') ? '' : 'none';
        }
    }

    selectSector.addEventListener('change', function () { rebuildCategories(false); });
    selectCat.addEventListener('change', togglePanels);
    selectUnit.addEventListener('change', togglePanels);

    // Estado inicial: preserva a categoria salva (mesmo de outro setor, ao editar).
    rebuildCategories(true);
})();
</script>
