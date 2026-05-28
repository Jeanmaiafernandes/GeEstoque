<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use RuntimeException;

class NfeParser
{
    /**
     * Lê o XML de uma NF-e e retorna o cabeçalho e os produtos.
     *
     * @return array{numero: ?string, fornecedor: ?string, produtos: array<int, array<string, mixed>>}
     */
    public function parse(string $xml): array
    {
        $dom = new DOMDocument();
        if (! @$dom->loadXML($xml)) {
            throw new RuntimeException('Arquivo XML inválido ou ilegível.');
        }

        $dets = $dom->getElementsByTagName('det');
        if ($dets->length === 0) {
            throw new RuntimeException('Não parece um XML de NF-e (nenhum produto encontrado).');
        }

        $produtos = [];
        foreach ($dets as $det) {
            $prod = $det->getElementsByTagName('prod')->item(0);
            if (! $prod instanceof DOMElement) {
                continue;
            }

            $get = fn (string $tag): ?string => $prod->getElementsByTagName($tag)->item(0)?->nodeValue;

            $ean = $get('cEAN');
            if ($ean !== null && ! ctype_digit($ean)) {
                $ean = null; // "SEM GTIN" e afins
            }

            $produtos[] = [
                'cProd' => $get('cProd'),
                'xProd' => $get('xProd'),
                'cEAN' => $ean,
                'qCom' => (float) str_replace(',', '.', (string) $get('qCom')),
                'uCom' => $get('uCom'),
                'vUnCom' => (float) str_replace(',', '.', (string) $get('vUnCom')),
            ];
        }

        $emit = $dom->getElementsByTagName('emit')->item(0);
        $fornecedor = $emit instanceof DOMElement
            ? $emit->getElementsByTagName('xNome')->item(0)?->nodeValue
            : null;

        return [
            'numero' => $dom->getElementsByTagName('nNF')->item(0)?->nodeValue,
            'fornecedor' => $fornecedor,
            'produtos' => $produtos,
        ];
    }
}
