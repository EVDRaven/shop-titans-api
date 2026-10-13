<?php

namespace Database\Seeders;

use App\Models\Quality;
use Illuminate\Database\Seeder;

class QualitySeeder extends Seeder
{
    public function run(): void
    {
        $qualities = [
            ['name' => 'Normal', 'rank' => 1],
            ['name' => 'Superior', 'rank' => 2],
            ['name' => 'Flawless', 'rank' => 3],
            ['name' => 'Epic', 'rank' => 4],
            ['name' => 'Legendary', 'rank' => 5],
        ];

        foreach ($qualities as $quality) {
            Quality::updateOrCreate(
                ['name' => $quality['name']],
                $quality
            );
        }
    }
}
