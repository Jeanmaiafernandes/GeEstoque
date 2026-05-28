<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Peça/Unidade', 'abbreviation' => 'un'],
            ['name' => 'Litro', 'abbreviation' => 'L'],
            ['name' => 'Pacote', 'abbreviation' => 'pct'],
            ['name' => 'Metro', 'abbreviation' => 'm'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['name' => $unit['name']], $unit);
        }
    }
}
