<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SectorSeeder::class,
            UnitSeeder::class,
        ]);

        User::firstOrCreate(
            ['email' => 'admin@almoxarifado.local'],
            [
                'name' => 'Administrador',
                'password' => 'Almox@2026',
                'role' => UserRole::Admin,
            ],
        );
    }
}
