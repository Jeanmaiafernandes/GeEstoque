<?php

namespace App\Models;

use App\Enums\ItemType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'barcode',
    'name',
    'description',
    'category_id',
    'sector_id',
    'unit_id',
    'type',
    'location',
    'aisle',
    'shelf',
    'position',
    'min_stock',
    'max_stock',
    'current_stock',
    'unit_price',
    'max_price',
    'active',
    'priced_by_weight',
    'price_per_kg',
])]
class Item extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type'             => ItemType::class,
            'active'           => 'boolean',
            'priced_by_weight' => 'boolean',
            'min_stock'        => 'decimal:3',
            'max_stock'        => 'decimal:3',
            'current_stock'    => 'decimal:3',
            'unit_price'       => 'decimal:4',
            'max_price'        => 'decimal:4',
            'price_per_kg'     => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Item $item): void {
            // Sincroniza priced_by_weight a partir da categoria quando esta muda.
            // Assim a lógica de peso é sempre definida pela categoria, não pelo item.
            if ($item->isDirty('category_id')) {
                $category = $item->category_id
                    ? Category::find($item->category_id)
                    : null;
                $item->priced_by_weight = (bool) ($category?->is_weight_category ?? false);
            }

            // Para itens por peso: unit_price = price_per_kg.
            // A unidade já é KG, então o preço por peça = preço por kg.
            // Isso mantém compatibilidade total com relatórios e valor em estoque existentes.
            if ($item->priced_by_weight && $item->price_per_kg !== null) {
                $item->unit_price = round((float) $item->price_per_kg, 4);
            }

            // Mantém o maior preço unitário já registrado (nunca diminui).
            if ($item->unit_price !== null
                && ($item->max_price === null || (float) $item->unit_price > (float) $item->max_price)) {
                $item->max_price = $item->unit_price;
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function movementItems(): HasMany
    {
        return $this->hasMany(StockMovementItem::class);
    }

    // ──────────────────────────────────────────────────────────────
    // Scopes e métodos de negócio
    // ──────────────────────────────────────────────────────────────

    /**
     * Itens com saldo igual ou abaixo do estoque mínimo.
     */
    public function scopeBelowMinimum(Builder $query): Builder
    {
        return $query->where('min_stock', '>', 0)
            ->whereColumn('current_stock', '<=', 'min_stock');
    }

    public function isBelowMinimum(): bool
    {
        return (float) $this->min_stock > 0
            && (float) $this->current_stock <= (float) $this->min_stock;
    }

    /**
     * Itens acima do estoque máximo definido (apenas itens por peso com max_stock).
     */
    public function isAboveMaxStock(): bool
    {
        return $this->max_stock !== null
            && (float) $this->max_stock > 0
            && (float) $this->current_stock > (float) $this->max_stock;
    }

    // ──────────────────────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────────────────────

    protected function currentStockLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->formatQuantity($this->current_stock));
    }

    protected function minStockLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->formatQuantity($this->min_stock));
    }

    protected function maxStockLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->max_stock === null
            ? '—'
            : $this->formatQuantity($this->max_stock));
    }

    protected function unitPriceLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->unit_price === null
            ? '—'
            : 'R$ ' . $this->formatMoney((float) $this->unit_price));
    }

    protected function maxPriceLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->max_price === null
            ? '—'
            : 'R$ ' . $this->formatMoney((float) $this->max_price));
    }

    protected function pricePerKgLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->price_per_kg === null
            ? '—'
            : 'R$ ' . $this->formatMoney((float) $this->price_per_kg) . '/kg');
    }

    /**
     * Valor em estoque (null se não houver preço cadastrado).
     *
     * - Item normal:       saldo × unit_price.
     * - Item por peso (unidade = KG): unit_price = price_per_kg (auto-setado no hook),
     *   portanto: saldo (kg) × price_per_kg = valor correto, sem multiplicação dupla.
     */
    protected function stockValue(): Attribute
    {
        return Attribute::get(fn (): ?float => $this->unit_price === null
            ? null
            : (float) $this->current_stock * (float) $this->unit_price);
    }

    protected function stockValueLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->stock_value === null
            ? '—'
            : 'R$ ' . number_format($this->stock_value, 2, ',', '.'));
    }

    /**
     * Endereço de localização: monta corredor/prateleira/posição (partes preenchidas)
     * e acrescenta o complemento (campo livre). Cai no texto livre se não houver estrutura.
     */
    protected function locationLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $parts = array_filter([
                filled($this->aisle) ? 'Corredor ' . $this->aisle : null,
                filled($this->shelf) ? 'Prateleira ' . $this->shelf : null,
                filled($this->position) ? 'Posição ' . $this->position : null,
            ]);

            $structured = implode(' · ', $parts);
            $complement = trim((string) $this->location);

            if ($structured !== '' && $complement !== '') {
                return $structured . ' · ' . $complement;
            }

            if ($structured !== '') {
                return $structured;
            }

            return $complement !== '' ? $complement : '—';
        });
    }

    // ──────────────────────────────────────────────────────────────
    // Formatadores privados
    // ──────────────────────────────────────────────────────────────

    /**
     * Formata uma quantidade em pt-BR removendo casas decimais desnecessárias.
     */
    private function formatQuantity(int|float|string|null $value): string
    {
        $number = number_format((float) $value, 3, ',', '.');

        if (str_contains($number, ',')) {
            $number = rtrim(rtrim($number, '0'), ',');
        }

        return $number;
    }

    /**
     * Formata preço em pt-BR: até 4 casas, mínimo 2.
     */
    private function formatMoney(float $value): string
    {
        $number = rtrim(number_format($value, 4, ',', '.'), '0');

        if ((strlen($number) - strpos($number, ',') - 1) < 2) {
            $number = str_pad($number, strpos($number, ',') + 3, '0');
        }

        return $number;
    }
}
