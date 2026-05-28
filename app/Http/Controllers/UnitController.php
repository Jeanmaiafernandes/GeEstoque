<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(): View
    {
        return view('units.index', [
            'units' => Unit::withCount('items')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:100', Rule::unique('units', 'name')],
            'abbreviation' => ['nullable', 'string', 'max:20'],
        ], [], ['name' => 'nome', 'abbreviation' => 'abreviação']);

        Unit::create($data);

        return redirect()->route('units.index')->with('status', 'Unidade criada.');
    }

    public function edit(Unit $unit): View
    {
        return view('units.edit', ['unit' => $unit]);
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:100', Rule::unique('units', 'name')->ignore($unit)],
            'abbreviation' => ['nullable', 'string', 'max:20'],
        ], [], ['name' => 'nome', 'abbreviation' => 'abreviação']);

        $unit->update($data);

        return redirect()->route('units.index')->with('status', 'Unidade atualizada.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->items()->exists()) {
            return redirect()->route('units.index')
                ->with('status', 'Não é possível remover uma unidade em uso por materiais.');
        }

        $unit->delete();

        return redirect()->route('units.index')->with('status', 'Unidade removida.');
    }
}
