<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Etiquetas — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4; margin: 8mm; }
        .etiqueta { width: 62mm; min-height: 42mm; }
        .etiqueta svg { display: block; }
        .barcode-svg svg { width: 100%; height: 12mm; }
        .qr-svg svg { width: 16mm; height: 16mm; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-slate-100 p-4 text-slate-800">
    <div class="no-print mx-auto mb-4 flex max-w-4xl items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold">Etiquetas ({{ $labels->count() }})</h1>
            <p class="text-sm text-slate-500">Use Ctrl+P ou o botão ao lado. Cada etiqueta tem código de barras e QR.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="rounded bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Imprimir</button>
            <button onclick="window.close()" class="rounded border border-slate-300 bg-white px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Fechar</button>
        </div>
    </div>

    <div class="mx-auto flex max-w-4xl flex-wrap gap-2 print:max-w-none print:gap-1">
        @forelse ($labels as $label)
            <div class="etiqueta flex flex-col rounded border border-slate-300 bg-white p-2 print:break-inside-avoid">
                <p class="text-[11px] font-bold leading-snug text-slate-900">{{ $label['item']->name }}</p>
                <p class="mt-0.5 font-mono text-[10px] text-slate-500">{{ $label['item']->code }}</p>
                <div class="mt-auto flex items-end justify-between gap-2 pt-1.5">
                    <div class="barcode-svg min-w-0 flex-1">{!! $label['barcode'] !!}</div>
                    <div class="qr-svg shrink-0">{!! $label['qr'] !!}</div>
                </div>
            </div>
        @empty
            <p class="text-slate-500">Nenhum material para gerar etiquetas.</p>
        @endforelse
    </div>
</body>
</html>
