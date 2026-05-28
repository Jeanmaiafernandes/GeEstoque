<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Sector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorizacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_categoriza_itens_de_setor_por_palavra_chave(): void
    {
        $met = Sector::create(['name' => 'Metalúrgica']);

        $chapa = Item::factory()->create(['sector_id' => $met->id, 'category_id' => null, 'name' => 'Chapa 18 FF 1.20']);
        $tubo = Item::factory()->create(['sector_id' => $met->id, 'category_id' => null, 'name' => 'Tubo 30x30 1.5']);
        $estranho = Item::factory()->create(['sector_id' => $met->id, 'category_id' => null, 'name' => 'XYZ-9000 indefinido']);

        $this->artisan('almoxarifado:categorizar-setores')->assertSuccessful();

        $this->assertSame('Chapas', $chapa->fresh()->category->name);
        $this->assertSame('Tubos', $tubo->fresh()->category->name);
        $this->assertNull($estranho->fresh()->category_id); // sem palavra-chave → fica sem categoria
    }

    public function test_nao_sobrescreve_categoria_existente(): void
    {
        $met = Sector::create(['name' => 'Metalúrgica']);
        $item = Item::factory()->create(['sector_id' => $met->id, 'name' => 'Chapa 16']);
        // já tem categoria definida manualmente
        $item->category()->associate(\App\Models\Category::create(['name' => 'Categoria Manual']));
        $item->save();

        $this->artisan('almoxarifado:categorizar-setores')->assertSuccessful();

        $this->assertSame('Categoria Manual', $item->fresh()->category->name);
    }
}
