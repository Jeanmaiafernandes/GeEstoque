<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup diário do banco (requer 'php artisan schedule:run' a cada minuto OU usar backup.bat agendado).
Schedule::command('almoxarifado:backup')->dailyAt('20:00');
