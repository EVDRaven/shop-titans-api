<?php

namespace Database\Seeders;

use App\Models\Enchantment;

class EnchantmentSeeder extends BlueprintCatalogSeeder
{
    protected function seed(string $filePath): void
    {
        foreach ($this->reader->readEnchantmentCatalog($filePath) as $entry) {
            Enchantment::updateOrCreate(
                ['name' => $entry['name']],
                ['name' => $entry['name'], 'type' => $entry['type']]
            );
        }
    }
}
