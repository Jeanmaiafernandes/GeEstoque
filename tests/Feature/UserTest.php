<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_cria_usuario(): void
    {
        $this->actingAs($this->admin())->post(route('users.store'), [
            'name' => 'João', 'email' => 'joao@empresa.com', 'password' => 'segredo123', 'role' => 'operador',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['email' => 'joao@empresa.com', 'role' => 'operador']);
    }

    public function test_email_unico(): void
    {
        User::factory()->create(['email' => 'dup@empresa.com']);

        $this->actingAs($this->admin())->post(route('users.store'), [
            'name' => 'x', 'email' => 'dup@empresa.com', 'password' => 'segredo123', 'role' => 'operador',
        ])->assertSessionHasErrors('email');
    }

    public function test_senha_obrigatoria_no_cadastro(): void
    {
        $this->actingAs($this->admin())->post(route('users.store'), [
            'name' => 'x', 'email' => 'a@empresa.com', 'role' => 'operador',
        ])->assertSessionHasErrors('password');
    }

    public function test_atualizar_sem_senha_mantem_a_atual(): void
    {
        $user = User::factory()->operador()->create();
        $oldHash = $user->password;

        $this->actingAs($this->admin())->put(route('users.update', $user), [
            'name' => 'Novo Nome', 'email' => $user->email, 'role' => 'admin',
        ])->assertRedirect(route('users.index'));

        $fresh = $user->fresh();
        $this->assertSame($oldHash, $fresh->password);
        $this->assertSame(UserRole::Admin, $fresh->role);
        $this->assertSame('Novo Nome', $fresh->name);
    }

    public function test_nao_pode_remover_o_proprio_usuario(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->delete(route('users.destroy', $admin));

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_pode_remover_outro_usuario(): void
    {
        $admin = $this->admin();
        $other = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $other))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }
}
