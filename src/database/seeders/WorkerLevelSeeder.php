<?php

namespace Database\Seeders;

use App\Models\WorkerLevel;
use Illuminate\Database\Seeder;

class WorkerLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['level' => 1, 'xp_needed' => null, 'crafting_speed_bonus' => null, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 2, 'xp_needed' => 20, 'crafting_speed_bonus' => 0.01, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 3, 'xp_needed' => 60, 'crafting_speed_bonus' => 0.03, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 4, 'xp_needed' => 100, 'crafting_speed_bonus' => 0.04, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 5, 'xp_needed' => 180, 'crafting_speed_bonus' => 0.06, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 6, 'xp_needed' => 275, 'crafting_speed_bonus' => 0.07, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 7, 'xp_needed' => 325, 'crafting_speed_bonus' => 0.09, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 8, 'xp_needed' => 625, 'crafting_speed_bonus' => 0.1, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 9, 'xp_needed' => 900, 'crafting_speed_bonus' => 0.12, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 10, 'xp_needed' => 1250, 'crafting_speed_bonus' => 0.13, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 11, 'xp_needed' => 1750, 'crafting_speed_bonus' => 0.15, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 12, 'xp_needed' => 2500, 'crafting_speed_bonus' => 0.16, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 13, 'xp_needed' => 3250, 'crafting_speed_bonus' => 0.18, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 14, 'xp_needed' => 4000, 'crafting_speed_bonus' => 0.19, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 15, 'xp_needed' => 5500, 'crafting_speed_bonus' => 0.21, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 16, 'xp_needed' => 7250, 'crafting_speed_bonus' => 0.22, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 17, 'xp_needed' => 9250, 'crafting_speed_bonus' => 0.24, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 18, 'xp_needed' => 10000, 'crafting_speed_bonus' => 0.25, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 19, 'xp_needed' => 12500, 'crafting_speed_bonus' => 0.27, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 20, 'xp_needed' => 15000, 'crafting_speed_bonus' => 0.28, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 21, 'xp_needed' => 17500, 'crafting_speed_bonus' => 0.3, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 22, 'xp_needed' => 20000, 'crafting_speed_bonus' => 0.31, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 23, 'xp_needed' => 22500, 'crafting_speed_bonus' => 0.33, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 24, 'xp_needed' => 27500, 'crafting_speed_bonus' => 0.34, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 25, 'xp_needed' => 32500, 'crafting_speed_bonus' => 0.36, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 26, 'xp_needed' => 37500, 'crafting_speed_bonus' => 0.37, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 27, 'xp_needed' => 42500, 'crafting_speed_bonus' => 0.39, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 28, 'xp_needed' => 47500, 'crafting_speed_bonus' => 0.4, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 29, 'xp_needed' => 55000, 'crafting_speed_bonus' => 0.42, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 30, 'xp_needed' => 62500, 'crafting_speed_bonus' => 0.43, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 31, 'xp_needed' => 72500, 'crafting_speed_bonus' => 0.45, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 32, 'xp_needed' => 80000, 'crafting_speed_bonus' => 0.46, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 33, 'xp_needed' => 90000, 'crafting_speed_bonus' => 0.48, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 34, 'xp_needed' => 100000, 'crafting_speed_bonus' => 0.49, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 35, 'xp_needed' => 125000, 'crafting_speed_bonus' => 0.51, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 36, 'xp_needed' => 125000, 'crafting_speed_bonus' => 0.52, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 37, 'xp_needed' => 150000, 'crafting_speed_bonus' => 0.54, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 38, 'xp_needed' => 175000, 'crafting_speed_bonus' => 0.55, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 39, 'xp_needed' => 200000, 'crafting_speed_bonus' => 0.57, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 40, 'xp_needed' => 225000, 'crafting_speed_bonus' => 0.6, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 41, 'xp_needed' => 240000, 'crafting_speed_bonus' => 0.62, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 42, 'xp_needed' => 260000, 'crafting_speed_bonus' => 0.64, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 43, 'xp_needed' => 280000, 'crafting_speed_bonus' => 0.66, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 44, 'xp_needed' => 300000, 'crafting_speed_bonus' => 0.68, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 45, 'xp_needed' => 320000, 'crafting_speed_bonus' => 0.7, 'crafting_speed_bonus_t15plus' => null],
            ['level' => 46, 'xp_needed' => 340000, 'crafting_speed_bonus' => 0.7, 'crafting_speed_bonus_t15plus' => 0.03],
            ['level' => 47, 'xp_needed' => 360000, 'crafting_speed_bonus' => 0.7, 'crafting_speed_bonus_t15plus' => 0.06],
            ['level' => 48, 'xp_needed' => 380000, 'crafting_speed_bonus' => 0.7, 'crafting_speed_bonus_t15plus' => 0.1],
            ['level' => 49, 'xp_needed' => 400000, 'crafting_speed_bonus' => 0.7, 'crafting_speed_bonus_t15plus' => 0.12],
            ['level' => 50, 'xp_needed' => 425000, 'crafting_speed_bonus' => 0.7, 'crafting_speed_bonus_t15plus' => 0.15],
        ];

        foreach ($levels as $level) {
            WorkerLevel::updateOrCreate(
                ['level' => $level['level']],
                $level
            );
        }
    }
}
