<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', [
            'categories' => Category::with('sector')->withCount('items')
                ->orderBy('sector_id')->orderBy('name')->get(),
            'sectors' => Sector::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $sectorId = $request->input('sector_id') ?: null;

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:100', $this->uniqueNameRule($sectorId)],
            'sector_id' => ['nullable', 'exists:sectors,id'],
        ], [], ['name' => 'nome', 'sector_id' => 'setor']);

        $data['sector_id'] = $sectorId;
        $data['is_weight_category'] = $request->boolean('is_weight_category');

        Category::create($data);

        return redirect()->route('categories.index')->with('status', 'Categoria criada.');
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', [
            'category' => $category,
            'sectors'  => Sector::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $sectorId = $request->input('sector_id') ?: null;

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:100', $this->uniqueNameRule($sectorId, $category)],
            'sector_id' => ['nullable', 'exists:sectors,id'],
        ], [], ['name' => 'nome', 'sector_id' => 'setor']);

        $data['sector_id'] = $sectorId;
        $data['is_weight_category'] = $request->boolean('is_weight_category');

        $wasWeight = $category->is_weight_category;
        $category->update($data);

        // Se o tipo de precificação mudou, sincroniza todos os itens já vinculados.
        // Isso garante consistência sem exigir que o operador re-salve cada item manualmente.
        if ($wasWeight !== $category->is_weight_category) {
            $this->syncItemsWeightPricing($category);
        }

        return redirect()->route('categories.index')->with('status', 'Categoria atualizada.');
    }

    /**
     * Nome único POR setor (categorias globais têm sector_id null).
     * "Consumíveis" pode existir em vários setores, mas não duas vezes no mesmo.
     */
    private function uniqueNameRule(?int $sectorId, ?Category $ignore = null): Unique
    {
        $rule = Rule::unique('categories', 'name')->where(function ($q) use ($sectorId) {
            return $sectorId === null
                ? $q->whereNull('sector_id')
                : $q->where('sector_id', $sectorId);
        });

        return $ignore ? $rule->ignore($ignore) : $rule;
    }

    /**
     * Sincroniza priced_by_weight (e unit_price quando vira peso) em todos os itens da categoria.
     * Usa saveQuietly() para não disparar o hook saving do model (evita loops e queries desnecessárias).
     */
    private function syncItemsWeightPricing(Category $category): void
    {
        $isWeight = $category->is_weight_category;

        foreach ($category->items as $item) {
            $item->priced_by_weight = $isWeight;

            if ($isWeight && $item->price_per_kg !== null) {
                $pkgVal = round((float) $item->price_per_kg, 4);
                $item->unit_price = $pkgVal;
                if ($item->max_price === null || $pkgVal > (float) $item->max_price) {
                    $item->max_price = $pkgVal;
                }
            }

            $item->saveQuietly();
        }
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('categories.index')->with('status', 'Categoria removida.');
    }
}
