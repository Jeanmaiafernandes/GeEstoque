<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Sector;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use App\Models\User;
use Tests\TestCase;

class NfeImportTest extends TestCase
{
    use RefreshDatabase;

    private function xml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<nfeProc xmlns="http://www.portalfiscal.inf.br/nfe"><NFe><infNFe>'
            . '<ide><nNF>12345</nNF></ide>'
            . '<emit><xNome>Fornecedor Teste Ltda</xNome></emit>'
            . '<det nItem="1"><prod><cProd>F123</cProd><cEAN>SEM GTIN</cEAN><xProd>Parafuso Importado M8</xProd><uCom>PC</uCom><qCom>50.0000</qCom><vUnCom>1.5000</vUnCom></prod></det>'
            . '<det nItem="2"><prod><cProd>A456</cProd><cEAN>7891234567895</cEAN><xProd>Arruela Importada</xProd><uCom>PC</uCom><qCom>30.0000</qCom><vUnCom>0.2500</vUnCom></prod></det>'
            . '</infNFe></NFe></nfeProc>';
    }

    private function operador(): User
    {
        return User::factory()->operador()->create();
    }

    public function test_preview_lista_produtos_da_nota(): void
    {
        $file = UploadedFile::fake()->createWithContent('nota.xml', $this->xml());

        $this->actingAs($this->operador())
            ->post(route('nfe.preview'), ['arquivo' => $file])
            ->assertOk()
            ->assertSee('Parafuso Importado M8')
            ->assertSee('Arruela Importada')
            ->assertSee('Fornecedor Teste Ltda')
            ->assertSee('12345')
            ->assertSee('Categoria');
    }

    public function test_store_cadastra_itens_novos_com_categoria_setor_local_e_preco(): void
    {
        $sector = Sector::factory()->create();

        $this->actingAs($this->operador())->post(route('nfe.store'), [
            'numero' => '500',
            'fornecedor' => 'Fornecedor X',
            'lines' => [
                ['xprod' => 'Pino Sextavado Novo', 'barcode' => '7899999999990', 'ucom' => 'PC', 'category' => 'Pinos', 'sector_id' => $sector->id, 'location' => 'Prateleira X', 'amount' => 20, 'price' => 3.5, 'incluir' => '1'],
                ['xprod' => 'Item Ignorado', 'ucom' => 'PC', 'category' => 'X', 'amount' => 5, 'price' => 1], // sem "incluir" → pulado
            ],
        ]);

        $item = Item::with(['category', 'sector', 'unit'])->firstWhere('name', 'Pino Sextavado Novo');
        $this->assertNotNull($item);
        $this->assertSame('Pinos', $item->category->name);
        $this->assertSame($sector->id, $item->sector_id);
        $this->assertSame('Prateleira X', $item->location);
        $this->assertSame('7899999999990', $item->barcode);
        $this->assertSame('PC', $item->unit->abbreviation);
        $this->assertSame('3.5000', (string) $item->unit_price);
        $this->assertSame('20.000', (string) $item->fresh()->current_stock);

        $this->assertNull(Item::firstWhere('name', 'Item Ignorado'));
    }

    public function test_store_reaproveita_item_existente_por_codigo_de_barras(): void
    {
        $item = Item::factory()->withStock(0)->create(['barcode' => '7890000000017', 'unit_price' => null]);

        $this->actingAs($this->operador())->post(route('nfe.store'), [
            'numero' => '7',
            'lines' => [['xprod' => 'Qualquer', 'barcode' => '7890000000017', 'ucom' => 'PC', 'category' => '', 'amount' => 5, 'price' => 9.9, 'incluir' => '1']],
        ]);

        $this->assertSame(1, Item::count()); // não duplicou
        $item->refresh();
        $this->assertSame('9.9000', (string) $item->unit_price);
        $this->assertSame('5.000', (string) $item->current_stock);
    }

    public function test_store_redireciona_quando_nenhum_item(): void
    {
        $this->actingAs($this->operador())
            ->post(route('nfe.store'), ['lines' => [['xprod' => 'x', 'amount' => 0, 'incluir' => '1']]])
            ->assertRedirect(route('nfe.create'))
            ->assertSessionHasErrors('arquivo');
    }

    public function test_xml_invalido_retorna_erro(): void
    {
        $file = UploadedFile::fake()->createWithContent('nota.xml', 'isto nao e um xml valido');

        $this->actingAs($this->operador())
            ->post(route('nfe.preview'), ['arquivo' => $file])
            ->assertSessionHasErrors('arquivo');
    }
}
