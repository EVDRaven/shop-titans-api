<?php

namespace Tests\Feature;

use App\Imports\BlueprintRowData;
use App\Models\Blueprint;
use App\Models\BlueprintComponent;
use App\Models\CraftingMaterial;
use App\Models\Quality;
use App\Services\BlueprintImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlueprintComponentImportTest extends TestCase
{
    use RefreshDatabase;

    private BlueprintImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BlueprintImportService;

        Quality::create(['name' => 'Normal', 'rank' => 1]);
        Quality::create(['name' => 'Superior', 'rank' => 2]);
        Quality::create(['name' => 'Flawless', 'rank' => 3]);
    }

    private function createBlueprint(string $name): Blueprint
    {
        return Blueprint::create([
            'name' => $name,
            'tier' => 1,
            'gold_value' => 100,
            'craft_time_seconds' => 10,
            'merchant_xp' => 10,
            'worker_xp' => 5,
            'fusion_xp' => 0,
            'favor' => 0,
            'airship_power' => 0,
        ]);
    }

    private function createMaterial(string $name, string $type = 'quest_component', ?int $blueprintId = null): CraftingMaterial
    {
        return CraftingMaterial::create([
            'source_key' => $type.':'.($blueprintId ?? $name),
            'name' => $name,
            'material_type' => $type,
            'blueprint_id' => $blueprintId,
        ]);
    }

    private function makeRow(string $name, array $components): BlueprintRowData
    {
        return new BlueprintRowData(
            name: $name,
            categoryName: 'Sword',
            unlockPrerequisite: null,
            researchScrolls: null,
            antiqueTokens: null,
            tier: 1,
            goldValue: 100,
            craftTimeSeconds: 10,
            craftTimeFormatted: null,
            valuePerSecond: null,
            merchantXp: 10,
            merchantXpPerSecond: null,
            workerXp: 5,
            fusionXp: 0,
            favor: 0,
            airshipPower: 0,
            atk: null,
            def: null,
            hp: null,
            eva: null,
            crit: null,
            manaShield: null,
            discountEnergy: null,
            surchargeEnergy: null,
            suggestEnergy: null,
            speedUpEnergy: null,
            availableAsAntiqueAt: null,
            workers: [],
            resources: [],
            craftingUpgrades: [],
            starforgedMilestones: [],
            ascensionUpgrades: [],
            transcendenceUpgrades: [],
            components: $components,
        );
    }

    public function test_import_components_resolves_and_persists(): void
    {
        $blueprint = $this->createBlueprint('Master Sword');
        $material = $this->createMaterial('Iron Ingot', 'quest_component');

        $rows = collect([
            $this->makeRow('Master Sword', [
                ['name' => 'Iron Ingot', 'quality_name' => 'Normal', 'amount' => 3],
            ]),
        ]);

        $result = $this->service->importComponents($rows);

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->blueprintsProcessed);
        $this->assertEquals(1, $result->componentsInserted);
        $this->assertEquals(0, $result->unresolvedReferences->count());

        $component = BlueprintComponent::first();
        $this->assertNotNull($component);
        $this->assertEquals($blueprint->id, $component->blueprint_id);
        $this->assertEquals($material->id, $component->component_id);
        $this->assertEquals(3, $component->amount);
        $this->assertNotNull($component->quality_id);
        $this->assertEquals('Normal', $component->quality->name);
    }

    public function test_import_components_preserves_quantity_and_quality(): void
    {
        $this->createBlueprint('Epic Blade');
        $material = $this->createMaterial('Steel Bar', 'quest_component');

        $rows = collect([
            $this->makeRow('Epic Blade', [
                ['name' => 'Steel Bar', 'quality_name' => 'Superior', 'amount' => 5],
            ]),
        ]);

        $result = $this->service->importComponents($rows);

        $this->assertTrue($result->success);
        $component = BlueprintComponent::first();
        $this->assertEquals(5, $component->amount);
        $this->assertEquals('Superior', $component->quality->name);
    }

    public function test_import_components_handles_nullable_quality(): void
    {
        $this->createBlueprint('Basic Tool');
        $this->createMaterial('Wood', 'quest_component');

        $rows = collect([
            $this->makeRow('Basic Tool', [
                ['name' => 'Wood', 'quality_name' => null, 'amount' => 2],
            ]),
        ]);

        $result = $this->service->importComponents($rows);

        $this->assertTrue($result->success);
        $component = BlueprintComponent::first();
        $this->assertNull($component->quality_id);
    }

    public function test_import_components_reports_unresolved_references(): void
    {
        $this->createBlueprint('Mystery Item');

        $rows = collect([
            $this->makeRow('Mystery Item', [
                ['name' => 'Nonexistent Material', 'quality_name' => 'Normal', 'amount' => 1],
            ]),
        ]);

        $result = $this->service->importComponents($rows);

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->unresolvedReferences->count());
        $this->assertEquals('Nonexistent Material', $result->unresolvedReferences->first()['component']);
        $this->assertEquals(0, BlueprintComponent::count());
    }

    public function test_import_components_skips_blueprint_not_in_database(): void
    {
        $this->createMaterial('Iron Ingot', 'quest_component');

        $rows = collect([
            $this->makeRow('NonExistent Blueprint', [
                ['name' => 'Iron Ingot', 'quality_name' => 'Normal', 'amount' => 1],
            ]),
        ]);

        $result = $this->service->importComponents($rows);

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->componentsSkipped);
        $this->assertEquals(0, $result->blueprintsProcessed);
        $this->assertNotEmpty($result->errors);
        $this->assertEquals(0, BlueprintComponent::count());
    }

    public function test_import_components_is_idempotent(): void
    {
        $this->createBlueprint('Repeat Sword');
        $this->createMaterial('Iron Ingot', 'quest_component');

        $rows = collect([
            $this->makeRow('Repeat Sword', [
                ['name' => 'Iron Ingot', 'quality_name' => 'Normal', 'amount' => 2],
            ]),
        ]);

        $firstResult = $this->service->importComponents($rows);
        $this->assertTrue($firstResult->success);
        $this->assertEquals(1, $firstResult->componentsInserted);
        $this->assertEquals(1, BlueprintComponent::count());

        $secondResult = $this->service->importComponents($rows);
        $this->assertTrue($secondResult->success);
        $this->assertEquals(1, $secondResult->componentsInserted);
        $this->assertEquals(1, BlueprintComponent::count());

        $component = BlueprintComponent::first();
        $this->assertEquals(2, $component->amount);
    }

    public function test_import_components_replaces_existing_on_reimport(): void
    {
        $blueprint = $this->createBlueprint('Changeable Sword');
        $material1 = $this->createMaterial('Iron Ingot', 'quest_component');
        $material2 = $this->createMaterial('Steel Bar', 'quest_component');

        $firstRows = collect([
            $this->makeRow('Changeable Sword', [
                ['name' => 'Iron Ingot', 'quality_name' => 'Normal', 'amount' => 2],
            ]),
        ]);
        $this->service->importComponents($firstRows);
        $this->assertEquals(1, BlueprintComponent::count());
        $this->assertEquals($material1->id, BlueprintComponent::first()->component_id);

        $secondRows = collect([
            $this->makeRow('Changeable Sword', [
                ['name' => 'Steel Bar', 'quality_name' => 'Superior', 'amount' => 5],
            ]),
        ]);
        $this->service->importComponents($secondRows);

        $this->assertEquals(1, BlueprintComponent::count());
        $component = BlueprintComponent::first();
        $this->assertEquals($material2->id, $component->component_id);
        $this->assertEquals(5, $component->amount);
    }

    public function test_import_components_handles_multiple_components(): void
    {
        $this->createBlueprint('Multi Component Item');
        $this->createMaterial('Iron Ingot', 'quest_component');
        $this->createMaterial('Wood', 'quest_component');

        $rows = collect([
            $this->makeRow('Multi Component Item', [
                ['name' => 'Iron Ingot', 'quality_name' => 'Normal', 'amount' => 3],
                ['name' => 'Wood', 'quality_name' => null, 'amount' => 1],
            ]),
        ]);

        $result = $this->service->importComponents($rows);

        $this->assertTrue($result->success);
        $this->assertEquals(2, $result->componentsInserted);
        $this->assertEquals(2, BlueprintComponent::count());
    }

    public function test_import_components_resolves_blueprint_type_materials(): void
    {
        $componentBlueprint = $this->createBlueprint('Simple Sword');
        $parentBlueprint = $this->createBlueprint('Better Sword');
        $this->createMaterial('Simple Sword', 'blueprint', $componentBlueprint->id);

        $rows = collect([
            $this->makeRow('Better Sword', [
                ['name' => 'Simple Sword', 'quality_name' => 'Flawless', 'amount' => 1],
            ]),
        ]);

        $result = $this->service->importComponents($rows);

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->componentsInserted);
        $this->assertEquals(0, $result->unresolvedReferences->count());

        $component = BlueprintComponent::first();
        $this->assertEquals($parentBlueprint->id, $component->blueprint_id);
        $this->assertEquals('Flawless', $component->quality->name);
    }
}
