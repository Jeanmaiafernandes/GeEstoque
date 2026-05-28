<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Item;

class CategorySectorAssigner
{
    /**
     * Atribui cada categoria ao setor que a utiliza ("pelo uso"):
     *  - usada por exatamente 1 setor  → recebe esse setor;
     *  - usada por vários setores      → permanece global (sector_id null);
     *  - sem itens (ou itens sem setor)→ permanece global.
     *
     * Só mexe em categorias que ainda estão sem setor, para ser reexecutável com segurança.
     *
     * @return array{assigned: int, global: int}
     */
    public static function run(): array
    {
        $assigned = 0;
        $global = 0;

        Category::query()->whereNull('sector_id')->each(function (Category $category) use (&$assigned, &$global): void {
            $sectorIds = Item::query()
                ->where('category_id', $category->id)
                ->whereNotNull('sector_id')
                ->distinct()
                ->pluck('sector_id');

            if ($sectorIds->count() === 1) {
                $category->sector_id = (int) $sectorIds->first();
                $category->saveQuietly();
                $assigned++;
            } else {
                $global++;
            }
        });

        return ['assigned' => $assigned, 'global' => $global];
    }
}
