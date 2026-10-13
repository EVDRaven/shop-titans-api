<?php

namespace Database\Seeders;

use App\Models\Worker;
use Illuminate\Database\Seeder;

class WorkerSeeder extends Seeder
{
    public function run(): void
    {
        $workers = [
            [
                'worker_key' => 'Blacksmith',
                'default_person_name' => 'Wallace',
                'unlock_level_required' => null,
                'building_prerequisite' => null,
                'gold_cost' => null,
                'gem_cost' => null,
                'blueprint_unlocks_raw' => 'Squire Sword',
            ],
            [
                'worker_key' => 'Tailor',
                'default_person_name' => 'Julia',
                'unlock_level_required' => 2,
                'building_prerequisite' => null,
                'gold_cost' => null,
                'gem_cost' => null,
                'blueprint_unlocks_raw' => 'Leather Gloves, Shirt, Leather Boots',
            ],
            [
                'worker_key' => 'Carpenter',
                'default_person_name' => 'Allan',
                'unlock_level_required' => 3,
                'building_prerequisite' => null,
                'gold_cost' => 1250,
                'gem_cost' => 25,
                'blueprint_unlocks_raw' => 'Cudgel, Carved Branch',
            ],
            [
                'worker_key' => 'Herbalist',
                'default_person_name' => 'Maribel',
                'unlock_level_required' => 5,
                'building_prerequisite' => null,
                'gold_cost' => 5000,
                'gem_cost' => 100,
                'blueprint_unlocks_raw' => 'Mintyleaf Herb',
            ],
            [
                'worker_key' => 'Wizard',
                'default_person_name' => 'Grimar',
                'unlock_level_required' => 16,
                'building_prerequisite' => 'Apothecary Lv. 3',
                'gold_cost' => 50000,
                'gem_cost' => 250,
                'blueprint_unlocks_raw' => 'Scroll of Sparks, Ember Element, Bubble Element',
            ],
            [
                'worker_key' => 'Jeweler',
                'default_person_name' => 'Katarina',
                'unlock_level_required' => 12,
                'building_prerequisite' => 'Tailor Workshop Lv. 5',
                'gold_cost' => 25000,
                'gem_cost' => 150,
                'blueprint_unlocks_raw' => 'Iron Ring, Alloy Loop, Ruby Ring',
            ],
            [
                'worker_key' => 'Priestess',
                'default_person_name' => 'Freyja',
                'unlock_level_required' => 25,
                'building_prerequisite' => 'Wood Workshop Lv. 5',
                'gold_cost' => 250000,
                'gem_cost' => 500,
                'blueprint_unlocks_raw' => 'Memento, Jade Pendant, Iron Bond, Ram Spirit',
            ],
            [
                'worker_key' => 'Master',
                'default_person_name' => 'Theodore',
                'unlock_level_required' => 30,
                'building_prerequisite' => 'Smithy Lv. 7',
                'gold_cost' => 500000,
                'gem_cost' => 750,
                'blueprint_unlocks_raw' => null,
            ],
            [
                'worker_key' => 'Scholar',
                'default_person_name' => 'Evelyn',
                'unlock_level_required' => 15,
                'building_prerequisite' => 'Academy',
                'gold_cost' => null,
                'gem_cost' => null,
                'blueprint_unlocks_raw' => "Baton, Elvenwood Wand, Hexer's Wand, Chipped Runestone",
            ],
            [
                'worker_key' => 'Engineer',
                'default_person_name' => 'Roxanne',
                'unlock_level_required' => 24,
                'building_prerequisite' => 'Laboratory',
                'gold_cost' => null,
                'gem_cost' => null,
                'blueprint_unlocks_raw' => 'Light Crossbow, Hand Crossbow, Arbalest, Pellet Gun, Handgun, Smoothbore',
            ],
            [
                'worker_key' => 'Sun Dragon',
                'default_person_name' => 'Sondra',
                'unlock_level_required' => 27,
                'building_prerequisite' => 'Sol Tower',
                'gold_cost' => 375000,
                'gem_cost' => 575,
                'blueprint_unlocks_raw' => null,
            ],
            [
                'worker_key' => 'Moon Dragon',
                'default_person_name' => 'Mundra',
                'unlock_level_required' => 27,
                'building_prerequisite' => 'Luna Tower',
                'gold_cost' => null,
                'gem_cost' => null,
                'blueprint_unlocks_raw' => "Lesser Moonstone, Mundra's Amulet",
            ],
            [
                'worker_key' => 'Summoner',
                'default_person_name' => 'Yolanda',
                'unlock_level_required' => 23,
                'building_prerequisite' => "Summoner's Tent",
                'gold_cost' => null,
                'gem_cost' => null,
                'blueprint_unlocks_raw' => 'Troublin, Adventurer Cape',
            ],
            [
                'worker_key' => 'Cook',
                'default_person_name' => 'Tutu Mano',
                'unlock_level_required' => 20,
                'building_prerequisite' => 'Apothecary Lv. 7',
                'gold_cost' => 100000,
                'gem_cost' => 150,
                'blueprint_unlocks_raw' => 'Bacon and Eggs',
            ],
            [
                'worker_key' => 'Baker',
                'default_person_name' => 'Kaipo',
                'unlock_level_required' => 21,
                'building_prerequisite' => 'Bakery',
                'gold_cost' => null,
                'gem_cost' => null,
                'blueprint_unlocks_raw' => 'Cookie Plate',
            ],
            [
                'worker_key' => 'Bard',
                'default_person_name' => 'Yohan',
                'unlock_level_required' => 18,
                'building_prerequisite' => 'Amphitheatre',
                'gold_cost' => null,
                'gem_cost' => null,
                'blueprint_unlocks_raw' => 'Triangle, Gleaming Aura',
            ],
            [
                'worker_key' => 'Veteran',
                'default_person_name' => 'Roland',
                'unlock_level_required' => 32,
                'building_prerequisite' => 'Military Camp',
                'gold_cost' => null,
                'gem_cost' => null,
                'blueprint_unlocks_raw' => 'Twin Khopesh, Swamp Knapsack',
            ],
            [
                'worker_key' => 'Storm Elemental',
                'default_person_name' => 'Zephyr',
                'unlock_level_required' => 35,
                'building_prerequisite' => 'Wind Nexus',
                'gold_cost' => null,
                'gem_cost' => null,
                'blueprint_unlocks_raw' => 'Primitive Idol, Natural Toxifier',
            ],
            [
                'worker_key' => 'Reaper',
                'default_person_name' => 'Ripley',
                'unlock_level_required' => 37,
                'building_prerequisite' => 'Grim Cabana',
                'gold_cost' => null,
                'gem_cost' => null,
                'blueprint_unlocks_raw' => 'Robust Extinctor, Autumnal Mask',
            ],
        ];

        foreach ($workers as $worker) {
            Worker::updateOrCreate(
                ['worker_key' => $worker['worker_key']],
                $worker
            );
        }
    }
}
