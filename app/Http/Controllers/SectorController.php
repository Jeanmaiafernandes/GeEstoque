<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SectorController extends Controller
{
    public function index(): View
    {
        return view('sectors.index', [
            'sectors' => Sector::withCount('items')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('sectors', 'name')],
        ], [], ['name' => 'nome']);

        $data['active'] = $request->boolean('active');

        Sector::create($data);

        return redirect()->route('sectors.index')->with('status', 'Setor criado.');
    }

    public function edit(Sector $sector): View
    {
        return view('sectors.edit', ['sector' => $sector]);
    }

    public function update(Request $request, Sector $sector): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('sectors', 'name')->ignore($sector)],
        ], [], ['name' => 'nome']);

        $data['active'] = $request->boolean('active');

        $sector->update($data);

        return redirect()->route('sectors.index')->with('status', 'Setor atualizado.');
    }

    public function destroy(Sector $sector): RedirectResponse
    {
        if ($sector->items()->exists()) {
            return redirect()->route('sectors.index')
                ->with('status', 'Não é possível remover um setor com materiais. Desative-o ou reatribua os materiais antes.');
        }

        $sector->delete();

        return redirect()->route('sectors.index')->with('status', 'Setor removido.');
    }
}
