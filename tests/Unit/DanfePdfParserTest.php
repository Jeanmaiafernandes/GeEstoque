<?php

namespace Tests\Unit;

use App\Support\DanfePdfParser;
use PHPUnit\Framework\TestCase;

class DanfePdfParserTest extends TestCase
{
    public function test_extrai_itens_e_cabecalho_do_texto_do_danfe(): void
    {
        $parser = new DanfePdfParser();

        $texto = implode("\n", [
            'DANFE  Nº 12345',
            'CHAVE DE ACESSO 1234 5678 9012 3456 7890 1234 5678 9012 3456 7890 1234',
            'DADOS DO PRODUTO / SERVIÇO',
            'CÓDIGO DESCRIÇÃO NCM/SH CST CFOP UN QUANT V.UNIT V.TOTAL',
            '001 PARAFUSO SEXTAVADO M8 7318.15.00 000 1102 PC 50,0000 1,5000 75,00',
            'A2 ARRUELA LISA 1/4 73182300 0 5102 KG 30,0000 0,2500 7,50',
        ]);

        $dados = $parser->parseText($texto);

        $this->assertCount(2, $dados['produtos']);

        $this->assertSame('PARAFUSO SEXTAVADO M8', $dados['produtos'][0]['xProd']);
        $this->assertSame('PC', $dados['produtos'][0]['uCom']);
        $this->assertSame(50.0, $dados['produtos'][0]['qCom']);
        $this->assertSame(1.5, $dados['produtos'][0]['vUnCom']);

        $this->assertSame('ARRUELA LISA 1/4', $dados['produtos'][1]['xProd']);
        $this->assertSame('KG', $dados['produtos'][1]['uCom']);
        $this->assertSame(30.0, $dados['produtos'][1]['qCom']);

        $this->assertSame('12345', $dados['numero']);
        $this->assertNotNull($dados['chave']);
        $this->assertSame(44, strlen($dados['chave']));
    }

    public function test_texto_sem_itens_retorna_lista_vazia(): void
    {
        $dados = (new DanfePdfParser())->parseText('Documento qualquer sem tabela de produtos.');

        $this->assertSame([], $dados['produtos']);
    }
}
