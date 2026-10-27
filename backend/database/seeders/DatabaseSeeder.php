<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Cliente de prueba para probar login/registro sin crear uno nuevo.
        User::firstOrCreate(
            ['email' => 'cliente@example.com'],
            [
                'name' => 'Cliente de Prueba',
                'password' => Hash::make('password123'),
            ]
        );

        $this->call(ProductSeeder::class);
    }
}
