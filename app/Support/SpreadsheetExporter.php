<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpreadsheetExporter
{
    /**
     * Gera um .xlsx para download a partir de cabeçalhos e linhas.
     *
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public static function download(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->fromArray($headings, null, 'A1');

            $line = 2;
            foreach ($rows as $row) {
                $sheet->fromArray(array_values($row), null, 'A' . $line);
                $line++;
            }

            $lastColumn = Coordinate::stringFromColumnIndex(max(1, count($headings)));
            $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);

            for ($col = 1; $col <= count($headings); $col++) {
                $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            }

            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
