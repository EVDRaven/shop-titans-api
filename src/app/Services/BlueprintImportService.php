<?php

namespace App\Services;

use App\Imports\BlueprintRowData;
use App\Imports\ComponentImportResult;
use App\Imports\ImportResult;
use App\Models\Blueprint;
use App\Models\CraftingMaterial;
use App\Models\Element;
use App\Models\Enchantment;
use App\Models\ItemCategory;
use App\Models\Quality;
use App\Models\Resource;
use App\Models\Spirit;
use App\Models\SpiritAffinity;
use App\Models\Worker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class BlueprintImportService
{
    private Collection $categoriesByName;

    private Collection $workersByKey;

    private Collection $resourcesByName;

    private Collection $materialsByName;

    private Collection $qualitiesByName;

    private Collection $elementsByName;

    private Collection $spiritAffinitiesByName;

    private Collection $enchantmentsByName;

    private Collection $spiritsByName;

    public function import(Collection $rows): ImportResult
    {
        $this->loadCatalogs();

        $errors = collect();
        $validRows = collect();

        foreach ($rows as $index => $row) {
            $rowErrors = $this->validateRow($row, $index + 2);
            if ($rowErrors->isNotEmpty()) {
                $errors = $errors->merge($rowErrors);

                continue;
            }
            $validRows->push($row);
        }

        if ($errors->isNotEmpty()) {
            return ImportResult::failure($errors);
        }

        $inserted = 0;
        $updated = 0;

        try {
            DB::transaction(function () use ($validRows, &$inserted, &$updated) {
                foreach ($validRows as $row) {
                    $wasCreated = false;
                    $blueprint = $this->persistBlueprint($row, $wasCreated);

                    if ($wasCreated) {
                        $inserted++;
                    } else {
                        $updated++;
                    }

                    $this->persistWorkers($blueprint, $row);
                    $this->persistResources($blueprint, $row);
                    $this->persistAffinities($blueprint, $row);
                    $this->persistCraftingUpgrades($blueprint, $row);
                    $this->persistStarforgedMilestones($blueprint, $row);
                    $this->persistAscensionUpgrades($blueprint, $row);
                    $this->persistTranscendenceUpgrades($blueprint, $row);
                }
            });
        } catch (Throwable $e) {
            return ImportResult::failure(collect([
                ['row' => null, 'blueprint' => null, 'message' => 'Error de escritura en base de datos: '.$e->getMessage()],
            ]));
        }

        return new ImportResult(
            rowsProcessed: $validRows->count(),
            blueprintsInserted: $inserted,
            blueprintsUpdated: $updated,
            blueprintsSkipped: $rows->count() - $validRows->count(),
            errors: $errors,
            success: true,
        );
    }

    public function importComponents(Collection $rows): ComponentImportResult
    {
        try {
            $this->loadComponentCatalogs();
        } catch (Throwable $e) {
            return ComponentImportResult::failure(collect([
                ['blueprint' => null, 'message' => 'Error al cargar los catálogos de componentes: '.$e->getMessage()],
            ]));
        }

        $unresolved = collect();
        $errors = collect();
        $inserted = 0;
        $skipped = 0;
        $processed = 0;

        try {
            DB::transaction(function () use ($rows, &$unresolved, &$errors, &$inserted, &$skipped, &$processed) {
                foreach ($rows as $row) {
                    $blueprint = Blueprint::where('name', $row->name)->first();

                    if ($blueprint === null) {
                        $errors->push([
                            'blueprint' => $row->name,
                            'message' => "Blueprint no encontrado en la base de datos: '{$row->name}'.",
                        ]);
                        $skipped++;

                        continue;
                    }

                    $processed++;
                    $componentsData = [];

                    foreach ($row->components as $component) {
                        $material = $this->materialsByName->get($component['name']);

                        if ($material === null) {
                            $unresolved->push([
                                'blueprint' => $row->name,
                                'component' => $component['name'],
                                'message' => "Componente no encontrado en crafting_materials: '{$component['name']}'.",
                            ]);

                            continue;
                        }

                        $qualityId = null;
                        if ($component['quality_name'] !== null) {
                            $quality = $this->qualitiesByName->get($component['quality_name']);
                            if ($quality === null) {
                                $errors->push([
                                    'blueprint' => $row->name,
                                    'component' => $component['name'],
                                    'message' => "Calidad no encontrada en catalogo: '{$component['quality_name']}'.",
                                ]);

                                continue;
                            }
                            $qualityId = $quality->id;
                        }

                        $componentsData[] = [
                            'component_id' => $material->id,
                            'quality_id' => $qualityId,
                            'amount' => $component['amount'],
                        ];
                    }

                    $blueprint->blueprintComponentsAsParent()->delete();

                    foreach ($componentsData as $data) {
                        $blueprint->blueprintComponentsAsParent()->create($data);
                        $inserted++;
                    }
                }
            });
        } catch (Throwable $e) {
            return ComponentImportResult::failure(collect([
                ['blueprint' => null, 'message' => 'Error de escritura en base de datos: '.$e->getMessage()],
            ]));
        }

        return new ComponentImportResult(
            blueprintsProcessed: $processed,
            componentsInserted: $inserted,
            componentsSkipped: $skipped,
            unresolvedReferences: $unresolved,
            errors: $errors,
            success: true,
        );
    }

    private function loadCatalogs(): void
    {
        $this->categoriesByName = ItemCategory::all()->keyBy('name');
        $this->workersByKey = Worker::all()->keyBy('worker_key');
        $this->resourcesByName = Resource::all()->keyBy('name');
        $this->elementsByName = Element::all()->keyBy('name');
        $this->spiritAffinitiesByName = SpiritAffinity::all()->keyBy('name');
        $this->enchantmentsByName = Enchantment::all()->keyBy('name');
        $this->spiritsByName = Spirit::all()->keyBy('name');
    }

    private function loadComponentCatalogs(): void
    {
        $this->materialsByName = CraftingMaterial::all()->keyBy('name');
        $this->qualitiesByName = Quality::all()->keyBy('name');
    }

    private function validateRow(BlueprintRowData $row, int $excelRow): Collection
    {
        $errors = collect();

        if ($row->categoryName === null) {
            $errors->push([
                'row' => $excelRow,
                'blueprint' => $row->name,
                'message' => 'Categoría (Type) vacía.',
            ]);
        } elseif (! $this->categoriesByName->has($row->categoryName)) {
            $errors->push([
                'row' => $excelRow,
                'blueprint' => $row->name,
                'message' => "Categoría no encontrada en catálogo: '{$row->categoryName}'.",
            ]);
        }

        foreach ($row->workers as $worker) {
            if (! $this->workersByKey->has($worker['worker_key'])) {
                $errors->push([
                    'row' => $excelRow,
                    'blueprint' => $row->name,
                    'message' => "Worker no encontrado en catálogo: '{$worker['worker_key']}'.",
                ]);
            }
        }

        foreach ($row->resources as $resource) {
            if (! $this->resourcesByName->has($resource['resource_name'])) {
                $errors->push([
                    'row' => $excelRow,
                    'blueprint' => $row->name,
                    'message' => "Recurso no encontrado en catálogo: '{$resource['resource_name']}'.",
                ]);
            }
        }

        foreach ($row->elementalAffinities as $elementName) {
            if (! $this->elementsByName->has($elementName)) {
                $errors->push([
                    'row' => $excelRow,
                    'blueprint' => $row->name,
                    'message' => "Elemento de afinidad no encontrado en catálogo: '{$elementName}'.",
                ]);
            }
        }

        if ($row->spiritAffinityName !== null && ! $this->spiritAffinitiesByName->has($row->spiritAffinityName)) {
            $errors->push([
                'row' => $excelRow,
                'blueprint' => $row->name,
                'message' => "Afinidad espiritual no encontrada en catálogo: '{$row->spiritAffinityName}'.",
            ]);
        }

        if ($row->builtInElementName !== null) {
            $enchantment = $this->enchantmentsByName->get($row->builtInElementName);

            if ($enchantment === null) {
                $errors->push([
                    'row' => $excelRow,
                    'blueprint' => $row->name,
                    'message' => "Elemento incorporado no encontrado en catálogo de encantamientos: '{$row->builtInElementName}'.",
                ]);
            } elseif ($enchantment->type !== Enchantment::TYPE_ELEMENT) {
                $errors->push([
                    'row' => $excelRow,
                    'blueprint' => $row->name,
                    'message' => "Elemento incorporado '{$row->builtInElementName}' no es de tipo Element (tipo real: '{$enchantment->type}').",
                ]);
            }
        }

        if ($row->builtInSpiritName !== null && ! $this->spiritsByName->has($row->builtInSpiritName)) {
            $errors->push([
                'row' => $excelRow,
                'blueprint' => $row->name,
                'message' => "Espíritu incorporado no encontrado en catálogo: '{$row->builtInSpiritName}'.",
            ]);
        }

        return $errors;
    }

    private function persistBlueprint(BlueprintRowData $row, bool &$wasCreated): Blueprint
    {
        $attributes = $row->toBlueprintAttributes();
        $attributes['category_id'] = $this->categoriesByName->get($row->categoryName)->id;

        $blueprint = Blueprint::updateOrCreate(
            ['name' => $row->name],
            $attributes,
        );

        $wasCreated = $blueprint->wasRecentlyCreated;

        return $blueprint;
    }

    private function persistWorkers(Blueprint $blueprint, BlueprintRowData $row): void
    {
        $blueprint->blueprintWorkers()->delete();

        foreach ($row->workers as $worker) {
            $blueprint->blueprintWorkers()->create([
                'worker_id' => $this->workersByKey->get($worker['worker_key'])->id,
                'level_required' => $worker['level_required'],
            ]);
        }
    }

    private function persistResources(Blueprint $blueprint, BlueprintRowData $row): void
    {
        $blueprint->blueprintResources()->delete();

        foreach ($row->resources as $resource) {
            $blueprint->blueprintResources()->create([
                'resource_id' => $this->resourcesByName->get($resource['resource_name'])->id,
                'quantity' => $resource['quantity'],
            ]);
        }
    }

    private function persistAffinities(Blueprint $blueprint, BlueprintRowData $row): void
    {
        // AZ: se reconstruye la relacion 1:N preservando el orden original de la celda.
        $blueprint->elementalAffinities()->delete();

        foreach ($row->elementalAffinities as $index => $elementName) {
            $blueprint->elementalAffinities()->create([
                'element_id' => $this->elementsByName->get($elementName)->id,
                'position' => $index + 1,
                'raw_value' => $row->elementalAffinityRaw,
            ]);
        }

        // BA / BB / BC: relaciones unicas resueltas contra sus catalogos.
        $blueprint->spirit_affinity_id = $row->spiritAffinityName !== null
            ? $this->spiritAffinitiesByName->get($row->spiritAffinityName)->id
            : null;

        $blueprint->built_in_element_id = $row->builtInElementName !== null
            ? $this->enchantmentsByName->get($row->builtInElementName)->id
            : null;

        $blueprint->built_in_spirit_id = $row->builtInSpiritName !== null
            ? $this->spiritsByName->get($row->builtInSpiritName)->id
            : null;

        $blueprint->save();
    }

    private function persistCraftingUpgrades(Blueprint $blueprint, BlueprintRowData $row): void
    {
        $blueprint->craftingUpgrades()->delete();

        foreach ($row->craftingUpgrades as $upgrade) {
            $blueprint->craftingUpgrades()->create($upgrade);
        }
    }

    private function persistStarforgedMilestones(Blueprint $blueprint, BlueprintRowData $row): void
    {
        $blueprint->starforgedMilestones()->delete();

        foreach ($row->starforgedMilestones as $milestone) {
            $blueprint->starforgedMilestones()->create($milestone);
        }
    }

    private function persistAscensionUpgrades(Blueprint $blueprint, BlueprintRowData $row): void
    {
        $blueprint->ascensionUpgrades()->delete();

        foreach ($row->ascensionUpgrades as $upgrade) {
            $blueprint->ascensionUpgrades()->create($upgrade);
        }
    }

    private function persistTranscendenceUpgrades(Blueprint $blueprint, BlueprintRowData $row): void
    {
        $blueprint->transcendenceUpgrades()->delete();

        foreach ($row->transcendenceUpgrades as $upgrade) {
            $blueprint->transcendenceUpgrades()->create($upgrade);
        }
    }
}
