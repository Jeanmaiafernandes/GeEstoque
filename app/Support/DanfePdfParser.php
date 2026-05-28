<?php

namespace App\Support;

use RuntimeException;
use Smalot\PdfParser\Parser;

class DanfePdfParser
{
    /**
     * Lê um DANFE em PDF (texto) e retorna cabeçalho + itens (melhor esforço).
     *
     * @return array{numero: ?string, fornecedor: ?string, chave: ?string, produtos: array<int, array<string, mixed>>}
     */
    public function parse(string $path): array
    {
        try {
            $text = (new Parser())->parseFile($path)->getText();
        } catch (\Throwable $e) {
            throw new RuntimeException('Não foi possível ler o PDF (ele pode ser uma imagem digitalizada, que exigiria OCR).');
        }

        if (trim($text) === '') {
            throw new RuntimeException('O PDF não tem texto selecionável (parece digitalizado/foto). Para esse caso seria necessário OCR.');
        }

        return $this->parseText($text);
    }

    /**
     * Faz a extração a partir do texto já lido (separado para ser testável).
     *
     * @return array{numero: ?string, fornecedor: ?string, chave: ?string, produtos: array<int, array<string, mixed>>}
     */
    public function parseText(string $text): array
    {
        $produtos = [];

        // Linha típica de item do DANFE: CÓDIGO DESCRIÇÃO NCM CST CFOP UN QTD V.UNIT V.TOTAL ...
        $regex = '/^\s*(\S+)\s+(.+?)\s+(?:\d{4}\.\d{2}\.\d{2}|\d{8})\s+\d{1,3}\s+\d{4}\s+([A-Za-z0-9²]{1,6})\s+([\d.]*,\d+)\s+([\d.]*,\d+)\s+([\d.]*,\d+)/u';

        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $linha) {
            if (preg_match($regex, trim($linha), $m)) {
                $produtos[] = [
                    'cProd' => $m[1],
                    'xProd' => trim($m[2]),
                    'cEAN' => null,
                    'uCom' => mb_strtoupper($m[3]),
                    'qCom' => $this->num($m[4]),
                    'vUnCom' => $this->num($m[5]),
                ];
            }
        }

        $compacto = preg_replace('/\s+/', '', $text) ?? '';
        $chave = preg_match('/\d{44}/', $compacto, $mc) ? $mc[0] : null;

        $numero = null;
        if (preg_match('/N[ºo°]\.?\s*[:.]?\s*0*([\d.]{1,12})/u', $text, $mn)) {
            $numero = preg_replace('/\D/', '', $mn[1]) ?: null;
        }

        return [
            'numero' => $numero,
            'fornecedor' => null,
            'chave' => $chave,
            'produtos' => $produtos,
        ];
    }

    private function num(string $value): float
    {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float) $value : 0.0;
    }
}
