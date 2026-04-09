<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin APEX',
            'email' => 'admin@apex-xdr.com',
            'password' => bcrypt('Admin@2024!'),
        ]);

        $this->call([
            DetectionRulesSeeder::class,
        ]);
    }
}
