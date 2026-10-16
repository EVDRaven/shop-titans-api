<?php

namespace Tests\Feature;

use App\Models\Blueprint;
use App\Models\BlueprintElementalAffinity;
use App\Models\Element;
use App\Models\SpiritAffinity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class BlueprintAffinityForeignKeysRollbackTest extends TestCase
{
    use RefreshDatabase;

    private function migration()
    {
        return require database_path(
            'migrations/2026_10_16_000005_change_blueprints_affinity_foreign_keys.php'
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

    public function test_down_with_empty_tables_succeeds(): void
    {
        $this->assertEquals(0, Blueprint::count());

        $this->migration()->down();

        // La columna escalar AZ se restaura al revertir.
        $this->assertTrue(Schema::hasColumn('blueprints', 'elemental_affinity_id'));
    }

    public function test_down_aborts_when_affinity_columns_have_data(): void
    {
        $spiritAffinity = SpiritAffinity::create(['name' => 'Wolf Ferocity']);

        $blueprint = $this->createBlueprint([
            'spirit_affinity_id' => $spiritAffinity->id,
        ]);

        $spiritAffinityIdBefore = $blueprint->fresh()->spirit_affinity_id;

        $this->expectException(RuntimeException::class);

        try {
            $this->migration()->down();
        } finally {
            // La guarda aborta antes de tocar el esquema: el dato persiste.
            $this->assertEquals(
                $spiritAffinityIdBefore,
                Blueprint::find($blueprint->id)->spirit_affinity_id
            );
        }
    }

    public function test_down_aborts_when_elemental_affinities_table_has_rows(): void
    {
        $element = Element::create(['name' => 'Earth']);
        $blueprint = $this->createBlueprint();

        BlueprintElementalAffinity::create([
            'blueprint_id' => $blueprint->id,
            'element_id' => $element->id,
            'position' => 1,
            'raw_value' => 'Earth',
        ]);

        $this->expectException(RuntimeException::class);

        try {
            $this->migration()->down();
        } finally {
            $this->assertEquals(1, BlueprintElementalAffinity::count());
        }
    }
}
