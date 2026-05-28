<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'sector_id', 'is_weight_category'])]
class Category extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_weight_category' => 'boolean',
        ];
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Categorias visíveis para um setor: as globais (sector_id null) + as do próprio setor.
     * Sem setor (null), mostra apenas as globais.
     */
    public function scopeVisibleForSector(Builder $query, ?int $sectorId): Builder
    {
        return $query->where(function (Builder $q) use ($sectorId): void {
            $q->whereNull('sector_id');
            if ($sectorId !== null) {
                $q->orWhere('sector_id', $sectorId);
            }
        });
    }

    /**
     * Categoria global = sem setor (visível a todos).
     */
    protected function isGlobal(): Attribute
    {
        return Attribute::get(fn (): bool => $this->sector_id === null);
    }
}
