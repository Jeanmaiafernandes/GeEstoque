<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtiquetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_de_etiquetas_abre(): void
    {
        $this->actingAs(User::factory()->operador()->create())
            ->get(route('labels.index'))
            ->assertOk();
    }

    public function test_impressao_gera_etiqueta_com_codigo_e_svg(): void
    {
        $item = Item::factory()->create(['code' => 'ALM-XYZ-0001', 'name' => 'Material Etiqueta']);

        $this->actingAs(User::factory()->operador()->create())
            ->get(route('labels.print', ['item_id' => $item->id]))
            ->assertOk()
            ->assertSee('ALM-XYZ-0001')
            ->assertSee('Material Etiqueta')
            ->assertSee('<svg', false); // código de barras / QR renderizados
    }
}
