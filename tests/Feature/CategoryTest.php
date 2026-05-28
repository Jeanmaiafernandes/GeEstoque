<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_cria_categoria(): void
    {
        $this->actingAs($this->admin())
            ->post(route('categories.store'), ['name' => 'Aço'])
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'Aço']);
    }

    public function test_nome_obrigatorio(): void
    {
        $this->actingAs($this->admin())
            ->post(route('categories.store'), [])
            ->assertSessionHasErrors('name');
    }

    public function test_nome_duplicado_rejeitado(): void
    {
        Category::factory()->create(['name' => 'Aço']);

        $this->actingAs($this->admin())
            ->post(route('categories.store'), ['name' => 'Aço'])
            ->assertSessionHasErrors('name');
    }

    public function test_admin_atualiza_categoria(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('categories.update', $category), ['name' => 'Renomeada'])
            ->assertRedirect(route('categories.index'));

        $this->assertSame('Renomeada', $category->fresh()->name);
    }

    public function test_remover_categoria_mantem_materiais(): void
    {
        $category = Category::factory()->create();
        $item = Item::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->admin())
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertNull($item->fresh()->category_id);
    }
}
