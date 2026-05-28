<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\Item;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    /**
     * Registra um documento de movimentação com uma ou mais linhas (itens),
     * atualizando o saldo de cada item de forma atômica.
     *
     * @param  array<int, array{item_id: int|string, amount: int|float|string}>  $lines
     *         Para entrada/saída: amount = quantidade movimentada (>0).
     *         Para ajuste: amount = quantidade contada (saldo físico).
     */
    public function register(
        MovementType $type,
        array $lines,
        ?int $sectorId = null,
        ?string $notes = null,
        ?int $userId = null,
    ): StockMovement {
        return DB::transaction(function () use ($type, $lines, $sectorId, $notes, $userId) {
            $movement = StockMovement::create([
                'type' => $type->value,
                'sector_id' => $sectorId,
                'user_id' => $userId,
                'notes' => $notes,
            ]);

            foreach ($lines as $line) {
                $amount = (float) $line['amount'];

                // Trava a linha do item para evitar condição de corrida entre operadores.
                $item = Item::whereKey($line['item_id'])->lockForUpdate()->firstOrFail();
                $current = (float) $item->current_stock;

                if ($type === MovementType::Saida && $amount > $current) {
                    throw ValidationException::withMessages([
                        'lines' => "Saldo insuficiente para {$item->name}. Disponível: {$item->current_stock_label} {$item->unit->abbreviation}.",
                    ]);
                }

                $delta = match ($type) {
                    MovementType::Entrada => $amount,
                    MovementType::Saida => -$amount,
                    MovementType::Ajuste => $amount - $current,
                };

                $balanceAfter = $current + $delta;

                $item->current_stock = $balanceAfter;
                $item->save();

                $movement->items()->create([
                    'item_id' => $item->id,
                    'quantity' => $delta,
                    'balance_after' => $balanceAfter,
                ]);
            }

            return $movement->load('items');
        });
    }
}
