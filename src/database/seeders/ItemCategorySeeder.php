<?php

namespace Database\Seeders;

use App\Models\ItemCategory;
use Illuminate\Database\Seeder;

class ItemCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Amulet',
            'Aurasong',
            'Axe',
            'Bow',
            'Catalyst',
            'Cloak',
            'Clothes',
            'Crossbow',
            'Dagger',
            'Dessert',
            'Dual Wield',
            'Familiar',
            'Gauntlets',
            'Gloves',
            'Gun',
            'Heavy Armor',
            'Heavy Footwear',
            'Helmet',
            'Herbal Medicine',
            'Idol',
            'Instrument',
            'Light Armor',
            'Light Footwear',
            'Mace',
            'Magician Hat',
            'Mask',
            'Meal',
            'Moonstone',
            'Potion',
            'Quiver',
            'Ring',
            'Rogue Hat',
            'Runestone',
            'Scythe',
            'Shield',
            'Spear',
            'Spell',
            'Staff',
            'Sword',
            'Wand',
        ];

        foreach ($categories as $name) {
            ItemCategory::firstOrCreate(['name' => $name]);
        }
    }
}
