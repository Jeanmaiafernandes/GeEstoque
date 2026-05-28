<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Item;
use App\Models\Sector;
use Illuminate\Console\Command;

class CategorizarSetores extends Command
{
    protected $signature = 'almoxarifado:categorizar-setores';

    protected $description = 'Atribui categorias por palavra-chave aos itens dos setores que ainda estão sem categoria.';

    /**
     * Setor => regras [ [palavras-chave], 'Categoria' ] (avaliadas em ordem; a 1ª que casar vence).
     */
    private const REGRAS = [
        'Metalúrgica' => [
            [['chapa'], 'Chapas'],
            [['tubo'], 'Tubos'],
        ],
        'Marcenaria' => [
            [['mdf', 'formica', 'fórmica', 'color core', 'compensado'], 'Painéis (MDF/Fórmica)'],
            [['fita'], 'Fitas de Borda'],
            [['corrediça', 'corredica'], 'Corrediças'],
            [['dobradiça', 'dobr'], 'Dobradiças'],
            [['pino', 'cavilha'], 'Pinos e Cavilhas'],
            [['cola', 'thinner', 'tingilack', 'verniz', 'selador'], 'Colas e Químicos'],
            [['lixa', 'disco'], 'Abrasivos'],
            [['fechad', 'puxador', 'kit', 'passa', 'tapa', 'paraf'], 'Ferragens'],
        ],
        'Expedição' => [
            [['etiqueta'], 'Etiquetas'],
            [['lacre', 'romaneio'], 'Material de Envio'],
            [['caixa', 'filme', 'plástico', 'plastico', 'palete', 'cantoneira', 'bolha', 'papel', 'fita'], 'Embalagens'],
        ],
    ];

    public function handle(): int
    {
        $categoryCache = [];
        $atribuidos = 0;
        $semCategoria = 0;
        $resumo = [];

        foreach (self::REGRAS as $setorNome => $regras) {
            $sector = Sector::firstWhere('name', $setorNome);
            if ($sector === null) {
                continue;
            }

            $itens = Item::where('sector_id', $sector->id)->whereNull('category_id')->get();

            foreach ($itens as $item) {
                $nome = mb_strtolower($item->name);
                $categoria = null;

                foreach ($regras as [$palavras, $catNome]) {
                    foreach ($palavras as $palavra) {
                        if (str_contains($nome, $palavra)) {
                            $categoria = $catNome;
                            break 2;
                        }
                    }
                }

                if ($categoria === null) {
                    $semCategoria++;
                    continue;
                }

                $categoryId = $categoryCache[$categoria] ??= Category::firstOrCreate(['name' => $categoria])->id;
                $item->update(['category_id' => $categoryId]);
                $atribuidos++;
                $resumo[$categoria] = ($resumo[$categoria] ?? 0) + 1;
            }
        }

        ksort($resumo);
        foreach ($resumo as $cat => $n) {
            $this->line("  {$cat}: {$n}");
        }

        $this->info("Categorias atribuídas a {$atribuidos} itens. {$semCategoria} ficaram sem categoria (revise no filtro 'Sem categoria').");

        return self::SUCCESS;
    }
}
