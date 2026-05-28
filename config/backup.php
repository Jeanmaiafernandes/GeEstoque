<?php

return [
    // Arquivo do banco a ser copiado.
    'source' => env('BACKUP_SOURCE', database_path('database.sqlite')),

    // Pasta local dos backups.
    'dir' => env('BACKUP_DIR', storage_path('app/backups')),

    // Pasta secundária (rede/pendrive) — opcional. Ex.: \\SERVIDOR\backups ou E:\backups
    'secondary' => env('BACKUP_DIR_SECONDARY'),

    // Quantos backups manter (em cada destino).
    'keep' => (int) env('BACKUP_KEEP', 14),
];
