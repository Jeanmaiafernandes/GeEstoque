<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_ve_proprio_perfil(): void
    {
        $user = User::factory()->create(['name' => 'Maria Operadora']);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Maria Operadora');
    }

    public function test_usuario_atualiza_proprio_nome(): void
    {
        $user = User::factory()->create(['name' => 'Antigo']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name'  => 'Novo Nome',
            'email' => $user->email,
        ])->assertRedirect();

        $this->assertSame('Novo Nome', $user->fresh()->name);
    }

    public function test_usuario_troca_senha_com_senha_atual_correta(): void
    {
        $user = User::factory()->create(['password' => 'senha-atual-123']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name'                  => $user->name,
            'email'                 => $user->email,
            'current_password'      => 'senha-atual-123',
            'password'              => 'nova-senha-456',
            'password_confirmation' => 'nova-senha-456',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('nova-senha-456', $user->fresh()->password));
    }

    public function test_troca_senha_exige_senha_atual_correta(): void
    {
        $user = User::factory()->create(['password' => 'senha-atual-123']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name'                  => $user->name,
            'email'                 => $user->email,
            'current_password'      => 'errada',
            'password'              => 'nova-senha-456',
            'password_confirmation' => 'nova-senha-456',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('senha-atual-123', $user->fresh()->password));
    }

    public function test_usuario_nao_pode_alterar_proprio_perfil_de_acesso(): void
    {
        // Garante que o perfil (role) não é alterável pelo próprio usuário via esta tela.
        $user = User::factory()->operador()->create();

        $this->actingAs($user)->put(route('profile.update'), [
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => 'admin', // tentativa de escalar privilégio
        ])->assertRedirect();

        $this->assertFalse($user->fresh()->isAdmin());
    }
}
