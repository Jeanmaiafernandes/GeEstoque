<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class Backup extends Command
{
    protected $signature = 'almoxarifado:backup';

    protected $description = 'Faz uma cópia de segurança do banco de dados (com retenção e cópia secundária opcional).';

    public function handle(): int
    {
        $source = (string) config('backup.source');

        if (! is_file($source)) {
            $this->error("Banco não encontrado: {$source}");

            return self::FAILURE;
        }

        $nome = 'almoxarifado-' . now()->format('Y-m-d_His') . '.sqlite';
        $keep = (int) config('backup.keep', 14);

        // Destino principal (sempre).
        $this->copiarPara((string) config('backup.dir'), $source, $nome, $keep, 'principal');

        // Destino secundário (rede/pendrive) — opcional.
        $secundario = config('backup.secondary');
        if (! empty($secundario)) {
            $this->copiarPara((string) $secundario, $source, $nome, $keep, 'secundário');
        }

        $this->info("Backup concluído: {$nome}");

        return self::SUCCESS;
    }

    private function copiarPara(string $dir, string $source, string $nome, int $keep, string $rotulo): void
    {
        try {
            File::ensureDirectoryExists($dir);
            File::copy($source, rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . $nome);
            $this->prune($dir, $keep);
            $this->line("  destino {$rotulo}: {$dir}");
        } catch (\Throwable $e) {
            $this->warn("  falha no destino {$rotulo} ({$dir}): {$e->getMessage()}");
        }
    }

    /** Mantém apenas os $keep backups mais recentes na pasta. */
    private function prune(string $dir, int $keep): void
    {
        if ($keep <= 0) {
            return;
        }

        $arquivos = collect(File::glob(rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . 'almoxarifado-*.sqlite'))
            ->sort()
            ->values();

        if ($arquivos->count() > $keep) {
            $arquivos->slice(0, $arquivos->count() - $keep)->each(fn ($f) => File::delete($f));
        }
    }
}
