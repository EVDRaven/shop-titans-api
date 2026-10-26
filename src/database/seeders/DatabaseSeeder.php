<?php

namespace Database\Seeders;

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
        $this->call([
            ResourceSeeder::class,
            QualitySeeder::class,
            ItemCategorySeeder::class,
            WorkerSeeder::class,
            WorkerLevelSeeder::class,
            ElementSeeder::class,
            EnchantmentSeeder::class,
            SpiritAffinitySeeder::class,
            SpiritSeeder::class,
        ]);
    }
}
