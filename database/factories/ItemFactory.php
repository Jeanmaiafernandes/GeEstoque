<?php

namespace Database\Factories;

use App\Enums\ItemType;
use App\Models\Item;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'ALM-' . strtoupper(fake()->unique()->bothify('???-####')),
            'barcode' => null,
            'name' => ucfirst(fake()->words(3, true)),
            'category_id' => null,
            'unit_id' => Unit::factory(),
            'type' => ItemType::MateriaPrima->value,
            'location' => null,
            'min_stock' => 0,
            'current_stock' => 0,
            'active' => true,
        ];
    }

    /** Item abaixo do estoque mínimo. */
    public function below(): static
    {
        return $this->state(fn () => ['min_stock' => 10, 'current_stock' => 2]);
    }

    public function withStock(float $quantity): static
    {
        return $this->state(fn () => ['current_stock' => $quantity]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
