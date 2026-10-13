<?php

namespace Database\Seeders;

use App\Models\Resource;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        $resources = [
            ['name' => 'Iron', 'column_letter_in_excel' => 'Y', 'tier' => 1],
            ['name' => 'Wood', 'column_letter_in_excel' => 'Z', 'tier' => 1],
            ['name' => 'Leather', 'column_letter_in_excel' => 'AA', 'tier' => 1],
            ['name' => 'Herbs', 'column_letter_in_excel' => 'AB', 'tier' => 1],
            ['name' => 'Steel', 'column_letter_in_excel' => 'AC', 'tier' => 2],
            ['name' => 'Ironwood', 'column_letter_in_excel' => 'AD', 'tier' => 2],
            ['name' => 'Fabric', 'column_letter_in_excel' => 'AE', 'tier' => 2],
            ['name' => 'Oils', 'column_letter_in_excel' => 'AF', 'tier' => 2],
            ['name' => 'Mana', 'column_letter_in_excel' => 'AG', 'tier' => 3],
            ['name' => 'Gems', 'column_letter_in_excel' => 'AH', 'tier' => 3],
            ['name' => 'Essence', 'column_letter_in_excel' => 'AI', 'tier' => 4],
            ['name' => 'Stardust', 'column_letter_in_excel' => 'AJ', 'tier' => 5],
        ];

        foreach ($resources as $resource) {
            Resource::updateOrCreate(
                ['name' => $resource['name']],
                $resource
            );
        }
    }
}
