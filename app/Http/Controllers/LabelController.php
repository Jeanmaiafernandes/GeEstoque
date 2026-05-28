<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Sector;
use App\Support\BarcodeService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabelController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['sector_id', 'category_id', 'q']);

        $items = $this->query($filters)->orderBy('code')->limit(500)->get(['id', 'code', 'name']);

        return view('labels.index', [
            'items' => $items,
            'sectors' => Sector::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function print(Request $request, BarcodeService $barcodes): View
    {
        $filters = $request->only(['sector_id', 'category_id', 'q', 'item_id']);

        $items = $this->query($filters)->orderBy('code')->limit(500)->get(['id', 'code', 'name', 'barcode']);

        $labels = $items->map(fn (Item $item) => [
            'item' => $item,
            'barcode' => $barcodes->code128Svg($item->code),
            'qr' => $barcodes->qrSvg($item->code),
        ]);

        return view('labels.print', ['labels' => $labels]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(array $filters): Builder
    {
        return Item::query()
            ->where('active', true)
            ->when($filters['item_id'] ?? null, fn ($q, $id) => $q->where('id', $id))
            ->when($filters['sector_id'] ?? null, fn ($q, $id) => $q->where('sector_id', $id))
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(function ($inner) use ($term) {
                $inner->where('code', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%");
            }));
    }
}
