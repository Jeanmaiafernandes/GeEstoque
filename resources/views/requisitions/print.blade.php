<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Requisição #{{ $requisition->id }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4; margin: 14mm; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="bg-white p-6 text-slate-800">
    <div class="no-print mx-auto mb-5 flex max-w-3xl items-center justify-between">
        <p class="text-sm text-slate-500">Use Ctrl+P ou o botão ao lado para imprimir.</p>
        <div class="flex gap-2">
            <button onclick="window.print()" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Imprimir</button>
            <button onclick="window.close()" class="rounded border border-slate-300 bg-white px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Fechar</button>
        </div>
    </div>

    <div class="mx-auto max-w-3xl">
        {{-- Cabeçalho: empresa + título do documento --}}
        <div class="flex items-start justify-between border-b border-slate-300 pb-4">
            <div class="flex items-center gap-3">
                @if (! empty($company['logo']))
                    <img src="{{ $company['logo'] }}" alt="Logo" class="h-12 w-12 object-contain">
                @endif
                <div>
                    <p class="text-lg font-bold">{{ $company['name'] ?: config('app.name') }}</p>
                    @if (! empty($company['cnpj']))
                        <p class="text-xs text-slate-500">CNPJ: {{ $company['cnpj'] }}</p>
                    @endif
                </div>
            </div>
            <div class="text-right">
                <p class="text-lg font-bold">Requisição de Material</p>
                <p class="text-sm text-slate-600">Nº {{ $requisition->id }}</p>
                <p class="text-xs text-slate-500">{{ $requisition->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        {{-- Dados --}}
        <dl class="mt-4 grid grid-cols-2 gap-y-2 text-sm">
            <div>
                <dt class="text-slate-500">Setor solicitante</dt>
                <dd class="font-medium">{{ $requisition->sector?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Situação</dt>
                <dd class="font-medium">{{ $requisition->status->label() }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Solicitante</dt>
                <dd class="font-medium">{{ $requisition->requester?->name ?? '—' }}</dd>
            </div>
            @if ($requisition->approver)
                <div>
                    <dt class="text-slate-500">Aprovado/decidido por</dt>
                    <dd class="font-medium">
                        {{ $requisition->approver->name }}
                        @if ($requisition->decided_at) · {{ $requisition->decided_at->format('d/m/Y H:i') }} @endif
                    </dd>
                </div>
            @endif
        </dl>

        @if ($requisition->notes)
            <p class="mt-3 text-sm"><span class="text-slate-500">Justificativa:</span> {{ $requisition->notes }}</p>
        @endif

        {{-- Itens --}}
        <table class="mt-5 w-full border-collapse text-sm">
            <thead>
                <tr class="border-y border-slate-300 text-left">
                    <th class="py-2 pr-2">Código</th>
                    <th class="py-2 pr-2">Material</th>
                    <th class="py-2 text-right">Qtd. solicitada</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($requisition->items as $line)
                    @php($abbr = $line->item?->unit?->abbreviation)
                    <tr class="border-b border-slate-100">
                        <td class="py-2 pr-2 font-mono text-xs text-slate-600">{{ $line->item?->code }}</td>
                        <td class="py-2 pr-2">{{ $line->item?->name }}</td>
                        <td class="py-2 text-right">{{ rtrim(rtrim(number_format((float) $line->quantity, 3, ',', '.'), '0'), ',') }} {{ $abbr }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Assinaturas --}}
        <div class="mt-20 grid grid-cols-2 gap-12 text-center text-sm text-slate-600">
            <div class="border-t border-slate-400 pt-1">Solicitante</div>
            <div class="border-t border-slate-400 pt-1">Responsável pelo almoxarifado</div>
        </div>
    </div>
</body>
</html>
