<?php

namespace Database\Seeders;

use App\Models\SpiritAffinity;

class SpiritAffinitySeeder extends BlueprintCatalogSeeder
{
    protected function seed(string $filePath): void
    {
        foreach ($this->reader->readSpiritAffinityNames($filePath) as $name) {
            SpiritAffinity::updateOrCreate(
                ['name' => $name],
                ['name' => $name]
            );
        }
    }
}
