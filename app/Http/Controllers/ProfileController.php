<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
        ], [], ['name' => 'nome', 'email' => 'e-mail']);

        $user->name  = $data['name'];
        $user->email = $data['email'];

        // Troca de senha é opcional; quando informada, exige a senha atual correta.
        if ($request->filled('password')) {
            $request->validate([
                'current_password' => ['required', 'current_password'],
                'password'         => ['required', 'confirmed', 'min:8'],
            ], [], ['current_password' => 'senha atual', 'password' => 'nova senha']);

            $user->password = $request->input('password'); // cast 'hashed' faz o hash
        }

        // O perfil de acesso (role) NUNCA é alterado por aqui — só por um admin na tela de Usuários.
        $user->save();

        return redirect()->route('profile.edit')->with('status', 'Perfil atualizado com sucesso.');
    }
}
