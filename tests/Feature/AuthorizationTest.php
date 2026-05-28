<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_e_redirecionado_para_login(): void
    {
        foreach (['dashboard', 'items.index', 'movements.index', 'reports.index'] as $name) {
            $this->get(route($name))->assertRedirect('/login');
        }
    }

    public function test_operador_acessa_paginas_compartilhadas(): void
    {
        $item = Item::factory()->create();
        $this->actingAs(User::factory()->operador()->create());

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('items.index'))->assertOk();
        $this->get(route('items.show', $item))->assertOk();
        $this->get(route('movements.index'))->assertOk();
        $this->get(route('movements.create', 'entrada'))->assertOk();
        $this->get(route('reports.index'))->assertOk();
        $this->get(route('reports.saldo'))->assertOk();
    }

    public function test_operador_e_bloqueado_nas_areas_administrativas(): void
    {
        $item = Item::factory()->create();
        $this->actingAs(User::factory()->operador()->create());

        $this->get(route('items.create'))->assertForbidden();
        $this->get(route('items.edit', $item))->assertForbidden();
        $this->get(route('categories.index'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('users.create'))->assertForbidden();
    }

    public function test_admin_acessa_areas_administrativas(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('items.create'))->assertOk();
        $this->get(route('categories.index'))->assertOk();
        $this->get(route('users.index'))->assertOk();
    }
}
