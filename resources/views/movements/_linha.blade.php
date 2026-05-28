@php
    $selItem = (isset($line['item_id']) && $line['item_id'])
        ? $items->firstWhere('id', (int) $line['item_id'])
        : null;
@endphp
<div class="linha mb-3 grid items-start gap-3 border-b border-slate-100 pb-3 sm:grid-cols-12">
    <div class="relative sm:col-span-7">
        <label class="block text-xs text-slate-500">Material</label>
        <input type="text" class="linha-busca mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm"
               placeholder="Digite o nome ou código…" autocomplete="off"
               value="{{ $selItem ? $selItem->code . ' — ' . $selItem->name : '' }}">
        <input type="hidden" name="lines[{{ $idx }}][item_id]" class="linha-item-id" value="{{ $line['item_id'] ?? '' }}">
        <ul class="linha-opcoes absolute left-0 right-0 z-20 mt-1 hidden max-h-56 overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg"></ul>
        <span class="linha-saldo mt-1 block text-xs text-slate-500">{{ $selItem ? 'Saldo atual: ' . $selItem->current_stock_label . ' ' . $selItem->unit->abbreviation : '' }}</span>
    </div>
    <div class="sm:col-span-3">
        <label class="block text-xs text-slate-500">{{ $amountLabel }}</label>
        <input type="number" step="0.001" min="0" name="lines[{{ $idx }}][amount]" value="{{ $line['amount'] ?? '' }}"
               class="mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div class="sm:col-span-2">
        <label class="block text-xs text-slate-500">&nbsp;</label>
        <button type="button" class="remover-linha mt-1 w-full rounded border border-slate-300 px-3 py-2 text-sm text-red-600 hover:bg-red-50">Remover</button>
    </div>
</div>
