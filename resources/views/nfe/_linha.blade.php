@php
    $p = $p ?? [];
    $qtd = isset($p['qCom']) ? rtrim(rtrim(number_format((float) $p['qCom'], 3, '.', ''), '0'), '.') : '';
    $preco = isset($p['vUnCom']) && (float) $p['vUnCom'] > 0 ? rtrim(rtrim(number_format((float) $p['vUnCom'], 4, '.', ''), '0'), '.') : '';
    $eanPrefill = $p['cEAN'] ?? '';
@endphp
<tr class="align-top">
    <td class="px-4 py-3">
        @if (! empty($p['xProd']))
            <p class="font-medium text-slate-800">{{ $p['xProd'] }}</p>
            <p class="text-xs text-slate-500">
                Cód. forn.: {{ $p['cProd'] ?? '—' }}
                @if (isset($p['qCom'])) · NF: {{ rtrim(rtrim(number_format((float) $p['qCom'], 3, ',', '.'), '0'), ',') }} {{ $p['uCom'] ?? '' }} @endif
            </p>
            <input type="hidden" name="lines[{{ $i }}][xprod]" value="{{ $p['xProd'] }}">
            <input type="hidden" name="lines[{{ $i }}][ucom]" value="{{ $p['uCom'] ?? '' }}">
        @else
            <input type="text" name="lines[{{ $i }}][xprod]" placeholder="Nome do material novo"
                   class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm">
            <input type="hidden" name="lines[{{ $i }}][ucom]" value="">
        @endif
    </td>
    <td class="px-4 py-3">
        <input name="lines[{{ $i }}][category]" list="nfe-categorias" placeholder="Categoria (existente ou nova)"
               class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <div class="mt-2 grid grid-cols-2 gap-2">
            <select name="lines[{{ $i }}][sector_id]" class="rounded border border-slate-300 px-2 py-1.5 text-sm" title="Setor responsável">
                <option value="">Setor</option>
                @foreach ($sectors as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
            <input name="lines[{{ $i }}][location]" placeholder="Localização"
                   class="rounded border border-slate-300 px-2 py-1.5 text-sm">
        </div>
        <input name="lines[{{ $i }}][barcode]" value="{{ $eanPrefill }}" placeholder="Código de barras (EAN, opcional)"
               class="mt-2 w-full rounded border border-slate-300 px-2 py-1.5 text-sm">
    </td>
    <td class="px-4 py-3 text-right">
        <input type="number" step="0.001" min="0" name="lines[{{ $i }}][amount]" value="{{ $qtd }}"
               class="w-24 rounded border border-slate-300 px-2 py-2 text-right text-sm">
    </td>
    <td class="px-4 py-3 text-right">
        <input type="number" step="0.0001" min="0" name="lines[{{ $i }}][price]" value="{{ $preco }}"
               class="w-24 rounded border border-slate-300 px-2 py-2 text-right text-sm">
    </td>
    <td class="px-4 py-3 text-center">
        <input type="checkbox" name="lines[{{ $i }}][incluir]" value="1" checked class="rounded border-slate-300" title="Incluir na entrada">
    </td>
</tr>
