<?php

namespace Database\Seeders;

use App\Models\Element;

class ElementSeeder extends BlueprintCatalogSeeder
{
    protected function seed(string $filePath): void
    {
        foreach ($this->reader->readElementalAffinityTokens($filePath) as $name) {
            Element::updateOrCreate(
                ['name' => $name],
                ['name' => $name]
            );
        }
    }
}
