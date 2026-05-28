<?php

namespace App\Console\Commands;

use App\Models\Item;
use Illuminate\Console\Command;

class RecalcularSaldo extends Command
{
    protected $signature = 'almoxarifado:recalcular-saldo';

    protected $description = 'Recalcula o saldo de cada material a partir do histórico de movimentações.';

    public function handle(): int
    {
        $corrigidos = 0;

        foreach (Item::all() as $item) {
            $running = 0.0;

            $lines = $item->movementItems()
                ->join('stock_movements', 'stock_movements.id', '=', 'stock_movement_items.stock_movement_id')
                ->orderBy('stock_movements.created_at')
                ->orderBy('stock_movement_items.id')
                ->select('stock_movement_items.*')
                ->get();

            foreach ($lines as $line) {
                $running += (float) $line->quantity;

                if ((float) $line->balance_after !== $running) {
                    $line->balance_after = $running;
                    $line->save();
                }
            }

            if ((float) $item->current_stock !== $running) {
                $item->current_stock = $running;
                $item->save();
                $corrigidos++;
            }
        }

        $this->info("Recálculo concluído. {$corrigidos} item(ns) tiveram o saldo corrigido.");

        return self::SUCCESS;
    }
}
