<?php

namespace Tests\Feature;

use App\Models\Blueprint;
use App\Models\BlueprintComponent;
use App\Models\CraftingMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class BlueprintComponentsMigrationRollbackTest extends TestCase
{
    use RefreshDatabase;

    private function migration()
    {
        return require database_path(
            'migrations/2026_10_14_000002_change_blueprint_components_component_id_foreign.php'
        );
    }

    private function createBlueprint(array $attributes = []): Blueprint
    {
        return Blueprint::create(array_merge([
            'name' => 'Test Blueprint '.uniqid(),
            'tier' => 1,
            'gold_value' => 100,
            'craft_time_seconds' => 10,
            'merchant_xp' => 10,
            'worker_xp' => 5,
            'fusion_xp' => 0,
            'favor' => 0,
            'airship_power' => 0,
        ], $attributes));
    }

    public function test_down_with_empty_table_succeeds(): void
    {
        $this->assertEquals(0, BlueprintComponent::count());

        $this->migration()->down();

        $this->assertEquals(0, BlueprintComponent::count());
    }

    public function test_down_with_reversible_components_restores_blueprint_ids(): void
    {
        $blueprint = $this->createBlueprint();
        $componentBlueprint = $this->createBlueprint(['name' => 'Component Blueprint']);

        $material = CraftingMaterial::create([
            'source_key' => 'blueprint:'.$componentBlueprint->id,
            'name' => $componentBlueprint->name,
            'material_type' => 'blueprint',
            'blueprint_id' => $componentBlueprint->id,
        ]);

        BlueprintComponent::create([
            'blueprint_id' => $blueprint->id,
            'component_id' => $material->id,
            'amount' => 1,
        ]);

        $this->migration()->down();

        $component = BlueprintComponent::first();
        $this->assertEquals($componentBlueprint->id, $component->component_id);
    }

    public function test_down_with_non_reversible_components_aborts(): void
    {
        $blueprint = $this->createBlueprint();

        $material = CraftingMaterial::create([
            'source_key' => 'quest_component:Test QC',
            'name' => 'Test QC',
            'material_type' => 'quest_component',
            'blueprint_id' => null,
        ]);

        BlueprintComponent::create([
            'blueprint_id' => $blueprint->id,
            'component_id' => $material->id,
            'amount' => 1,
        ]);

        $componentIdBefore = BlueprintComponent::first()->component_id;

        $this->expectException(RuntimeException::class);

        try {
            $this->migration()->down();
        } finally {
            $this->assertEquals($componentIdBefore, BlueprintComponent::first()->component_id);
        }
    }
}
