<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'inventory_id',
    'item_id',
    'system_qty',
    'counted_qty',
])]
class InventoryItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'system_qty'  => 'decimal:3',
            'counted_qty' => 'decimal:3',
        ];
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Divergência = contado − saldo do sistema (null se ainda não contado).
     */
    protected function divergence(): Attribute
    {
        return Attribute::get(fn (): ?float => $this->counted_qty === null
            ? null
            : (float) $this->counted_qty - (float) $this->system_qty);
    }
}
