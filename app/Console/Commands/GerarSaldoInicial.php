<?php

namespace App\Console\Commands;

use App\Enums\MovementType;
use App\Models\Item;
use App\Models\StockMovement;
use Illuminate\Console\Command;

class GerarSaldoInicial extends Command
{
    protected $signature = 'almoxarifado:saldo-inicial';

    protected $description = 'Cria um documento de "saldo inicial" para itens com saldo e sem nenhuma movimentação registrada.';

    public function handle(): int
    {
        $items = Item::query()
            ->where('current_stock', '<>', 0)
            ->whereDoesntHave('movementItems')
            ->get();

        foreach ($items as $item) {
            $movement = StockMovement::create([
                'type' => MovementType::Ajuste->value,
                'sector_id' => null,
                'user_id' => null,
                'notes' => 'Saldo inicial (importação da planilha).',
            ]);

            $movement->items()->create([
                'item_id' => $item->id,
                'quantity' => $item->current_stock,
                'balance_after' => $item->current_stock,
            ]);
        }

        $this->info("Saldo inicial gerado para {$items->count()} itens.");

        return self::SUCCESS;
    }
}
