<?php

namespace Database\Seeders;

use App\Imports\BlueprintExcelReader;
use App\Models\Blueprint;
use App\Models\CraftingMaterial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CraftingMaterialSeeder extends Seeder
{
    public string $filePath;

    public Collection $warnings;

    public function run(): void
    {
        $this->warnings = collect();

        $filePath = $this->resolveFilePath();
        $reader = new BlueprintExcelReader;

        $data = $reader->readCraftingMaterialData($filePath);
        $componentNames = $data['componentNames'];
        $blueprintNames = $data['blueprintNames'];
        $questComponents = $data['questComponents'];
        $sigils = $data['sigils'];
        $enchantmentNames = $data['enchantmentNames'];

        $blueprintsByName = Blueprint::all()->keyBy('name');

        foreach ($componentNames as $name) {
            if ($blueprintNames->has($name)) {
                $blueprint = $blueprintsByName->get($name);

                if ($blueprint === null) {
                    $this->warnings->push([
                        'component' => $name,
                        'message' => "El componente '{$name}' es un blueprint segun el Excel pero no existe en la tabla blueprints.",
                    ]);

                    continue;
                }

                CraftingMaterial::updateOrCreate(
                    ['source_key' => 'blueprint:'.$blueprint->id],
                    [
                        'name' => $name,
                        'material_type' => 'blueprint',
                        'blueprint_id' => $blueprint->id,
                        'tier' => $blueprint->tier,
                        'gold_value' => $blueprint->gold_value,
                    ]
                );
            } elseif ($questComponents->has($name)) {
                $qc = $questComponents->get($name);

                CraftingMaterial::updateOrCreate(
                    ['source_key' => 'quest_component:'.$name],
                    [
                        'name' => $name,
                        'material_type' => 'quest_component',
                        'blueprint_id' => null,
                        'tier' => $qc['tier'],
                        'gold_value' => $qc['gold_value'],
                        'quest_area' => $qc['quest_area'],
                    ]
                );
            } elseif ($sigils->has($name)) {
                $sigil = $sigils->get($name);

                CraftingMaterial::updateOrCreate(
                    ['source_key' => 'sigil:'.$name],
                    [
                        'name' => $name,
                        'material_type' => 'sigil',
                        'blueprint_id' => null,
                        'tier' => $sigil['tier'],
                        'fuse_time_seconds' => $sigil['fuse_time_seconds'],
                        'worker_level_required' => $sigil['worker_level_required'],
                        'worker_xp' => $sigil['worker_xp'],
                    ]
                );
            } elseif ($enchantmentNames->has($name)) {
                CraftingMaterial::updateOrCreate(
                    ['source_key' => 'enchantment:'.$name],
                    [
                        'name' => $name,
                        'material_type' => 'enchantment',
                        'blueprint_id' => null,
                    ]
                );
            } else {
                $this->warnings->push([
                    'component' => $name,
                    'message' => "El componente '{$name}' no se pudo clasificar en ningun tipo conocido.",
                ]);
            }
        }

        if ($this->warnings->isNotEmpty() && $this->command !== null) {
            $this->command->warn("{$this->warnings->count()} advertencia(s) durante la siembra de crafting_materials.");
        }
    }

    private function resolveFilePath(): string
    {
        if (isset($this->filePath)) {
            return $this->filePath;
        }

        $default = storage_path('app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx');

        if (file_exists($default)) {
            return $default;
        }

        $candidates = glob(storage_path('app/*.xlsx'));

        if (! empty($candidates)) {
            return $candidates[0];
        }

        throw new \RuntimeException('No se encontro el archivo Excel de Shop Titans en storage/app/.');
    }
}
