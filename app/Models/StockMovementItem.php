<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stock_movement_id',
    'item_id',
    'quantity',
    'balance_after',
])]
class StockMovementItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'balance_after' => 'decimal:3',
        ];
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'stock_movement_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    protected function quantityLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $value = (float) $this->quantity;

            return ($value < 0 ? '−' : '+') . $this->formatQuantity(abs($value));
        });
    }

    protected function balanceAfterLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->formatQuantity($this->balance_after));
    }

    private function formatQuantity(int|float|string|null $value): string
    {
        $number = number_format((float) $value, 3, ',', '.');

        if (str_contains($number, ',')) {
            $number = rtrim(rtrim($number, '0'), ',');
        }

        return $number;
    }
}
