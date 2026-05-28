<?php

namespace App\Http\Controllers;

use App\Support\ReportExport;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemParameterController extends Controller
{
    public function edit(): View
    {
        return view('settings.params', [
            'pdfMaxRows' => Settings::int('pdf_max_rows', ReportExport::PDF_MAX_ROWS),
            'reportDays' => Settings::int('report_default_days', 30),
            'chartDays'  => Settings::int('dashboard_chart_days', 14),
            'topItems'   => Settings::int('dashboard_top_items', 8),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pdf_max_rows'         => ['required', 'integer', 'min:50', 'max:100000'],
            'report_default_days'  => ['required', 'integer', 'min:1', 'max:730'],
            'dashboard_chart_days' => ['required', 'integer', 'min:1', 'max:120'],
            'dashboard_top_items'  => ['required', 'integer', 'min:1', 'max:50'],
        ], [], [
            'pdf_max_rows'         => 'linhas máximas no PDF',
            'report_default_days'  => 'período padrão dos relatórios',
            'dashboard_chart_days' => 'período do gráfico do painel',
            'dashboard_top_items'  => 'top itens do painel',
        ]);

        Settings::put($data);

        return redirect()->route('settings.params.edit')->with('status', 'Parâmetros salvos com sucesso.');
    }
}
