<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: "DejaVu Sans", sans-serif; }
        body { font-size: 10px; color: #1e293b; }
        h1 { font-size: 15px; margin: 0; }
        .sub { color: #64748b; font-size: 9px; margin: 2px 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 3px 5px; text-align: left; }
        th { background: #f1f5f9; }
        .warn { background: #fef3c7; border: 1px solid #f59e0b; color: #92400e;
                padding: 5px 8px; margin: 0 0 10px; font-size: 9px; }
        .company-header { width: 100%; margin: 0 0 8px; border-bottom: 1px solid #e2e8f0; }
        .company-header td { border: none; padding: 0 0 6px; vertical-align: middle; }
        .company-name { font-size: 12px; font-weight: bold; }
        .company-cnpj { font-size: 8px; color: #64748b; }
    </style>
</head>
<body>
    @php($company = $company ?? [])
    @if (! empty($company['name']) || ! empty($company['logo']))
        <table class="company-header">
            <tr>
                @if (! empty($company['logo']))
                    <td style="width: 60px;"><img src="{{ $company['logo'] }}" style="max-height: 40px; max-width: 110px;"></td>
                @endif
                <td>
                    <span class="company-name">{{ $company['name'] }}</span>
                    @if (! empty($company['cnpj']))<br><span class="company-cnpj">CNPJ: {{ $company['cnpj'] }}</span>@endif
                </td>
            </tr>
        </table>
    @endif

    <h1>{{ $title }}</h1>
    @if ($subtitle)
        <div class="sub">{{ $subtitle }}</div>
    @endif
    @if ($truncated ?? false)
        <div class="warn">
            Exibindo as primeiras {{ number_format($maxRows, 0, ',', '.') }} de
            {{ number_format($totalRows, 0, ',', '.') }} linhas.
            Para o relatório completo, exporte em <strong>Excel</strong> ou refine os filtros (período / setor).
        </div>
    @endif
    <table>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) }}">Sem dados.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
