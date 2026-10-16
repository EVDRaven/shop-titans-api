<?php

namespace Database\Seeders;

use App\Models\Spirit;

class SpiritSeeder extends BlueprintCatalogSeeder
{
    protected function seed(string $filePath): void
    {
        foreach ($this->reader->readBuiltInSpiritNames($filePath) as $name) {
            Spirit::updateOrCreate(
                ['name' => $name],
                ['name' => $name]
            );
        }
    }
}
