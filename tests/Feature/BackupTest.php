<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupTest extends TestCase
{
    public function test_backup_cria_copia_do_banco(): void
    {
        $source = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bkp_src_' . uniqid() . '.sqlite';
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bkp_dir_' . uniqid();
        file_put_contents($source, 'conteudo-do-banco');

        config(['backup.source' => $source, 'backup.dir' => $dir, 'backup.secondary' => null, 'backup.keep' => 14]);

        $this->artisan('almoxarifado:backup')->assertSuccessful();

        $copias = File::glob($dir . DIRECTORY_SEPARATOR . 'almoxarifado-*.sqlite');
        $this->assertCount(1, $copias);
        $this->assertSame('conteudo-do-banco', file_get_contents($copias[0]));

        File::deleteDirectory($dir);
        @unlink($source);
    }

    public function test_backup_falha_quando_banco_nao_existe(): void
    {
        config(['backup.source' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'inexistente_' . uniqid() . '.sqlite']);

        $this->artisan('almoxarifado:backup')->assertFailed();
    }
}
