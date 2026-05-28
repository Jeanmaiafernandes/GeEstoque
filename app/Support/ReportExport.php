<?php

namespace App\Support;

use App\Support\Settings;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ReportExport
{
    /**
     * Máximo de linhas em uma exportação PDF.
     *
     * O dompdf monta toda a tabela em memória e estiliza cada célula — tabelas
     * muito grandes estouram o limite de memória do PHP. Acima deste limite o PDF
     * é truncado com aviso; para o relatório completo use o Excel ou refine os filtros.
     */
    public const PDF_MAX_ROWS = 2000;

    /**
     * Devolve a resposta de exportação para o formato pedido, ou null para HTML.
     *
     * @param  array<int, string>  $columns
     * @param  array<int, array<int, mixed>>  $rows
     */
    public static function make(string $format, string $slug, string $title, ?string $subtitle, array $columns, array $rows): ?Response
    {
        return match ($format) {
            'xlsx' => SpreadsheetExporter::download("{$slug}.xlsx", $columns, $rows),
            'pdf' => self::pdf($slug, $title, $subtitle, $columns, $rows),
            default => null,
        };
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, array<int, mixed>>  $rows
     */
    private static function pdf(string $slug, string $title, ?string $subtitle, array $columns, array $rows): Response
    {
        $maxRows = Settings::int('pdf_max_rows', self::PDF_MAX_ROWS);
        $totalRows = count($rows);
        $truncated = $totalRows > $maxRows;

        if ($truncated) {
            $rows = array_slice($rows, 0, $maxRows);
        }

        // Garante memória suficiente para o dompdf renderizar tabelas grandes.
        @ini_set('memory_limit', '512M');

        return Pdf::loadView('reports.pdf', [
            'title'     => $title,
            'subtitle'  => $subtitle,
            'columns'   => $columns,
            'rows'      => $rows,
            'truncated' => $truncated,
            'maxRows'   => $maxRows,
            'totalRows' => $totalRows,
            'company'   => [
                'name' => Settings::get('company_name'),
                'cnpj' => Settings::get('company_cnpj'),
                'logo' => Settings::companyLogoBase64(),
            ],
        ])
            ->setPaper('a4', 'landscape')
            ->download("{$slug}.pdf");
    }
}
