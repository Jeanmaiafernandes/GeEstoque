<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Almoxarifado', 'Metalúrgica', 'Marcenaria', 'Expedição'] as $name) {
            Sector::firstOrCreate(['name' => $name]);
        }
    }
}
