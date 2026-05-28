<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConfiguracaoEmpresaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function operador(): User
    {
        return User::factory()->operador()->create();
    }

    public function test_admin_ve_pagina_de_configuracoes(): void
    {
        $this->actingAs($this->admin())
            ->get(route('settings.company.edit'))
            ->assertOk()
            ->assertSee('Dados da empresa');
    }

    public function test_operador_e_bloqueado(): void
    {
        $this->actingAs($this->operador())
            ->get(route('settings.company.edit'))
            ->assertForbidden();
    }

    public function test_admin_salva_dados_da_empresa(): void
    {
        $this->actingAs($this->admin())->put(route('settings.company.update'), [
            'company_name'  => 'Empresa Exemplo',
            'company_cnpj'  => '12.345.678/0001-90',
            'company_phone' => '(11) 1234-5678',
            'company_email' => 'contato@exemplo.com',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'company_name', 'value' => 'Empresa Exemplo']);
        $this->assertDatabaseHas('settings', ['key' => 'company_cnpj', 'value' => '12.345.678/0001-90']);
        $this->assertDatabaseHas('settings', ['key' => 'company_email', 'value' => 'contato@exemplo.com']);
    }

    public function test_email_invalido_rejeitado(): void
    {
        $this->actingAs($this->admin())->put(route('settings.company.update'), [
            'company_name'  => 'X',
            'company_email' => 'nao-e-email',
        ])->assertSessionHasErrors('company_email');
    }

    public function test_admin_faz_upload_de_logo(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->put(route('settings.company.update'), [
            'company_name' => 'Empresa Exemplo',
            'logo'         => UploadedFile::fake()->image('logo.png', 200, 80),
        ])->assertRedirect();

        $path = Setting::where('key', 'company_logo_path')->value('value');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_arquivo_de_logo_invalido_rejeitado(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->put(route('settings.company.update'), [
            'company_name' => 'X',
            'logo'         => UploadedFile::fake()->create('documento.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('logo');
    }

    public function test_logo_aparece_na_tela_de_login(): void
    {
        Setting::updateOrCreate(['key' => 'company_logo_path'], ['value' => 'company/logo.png']);
        $url = Storage::disk('public')->url('company/logo.png');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee($url, false);
    }
}
