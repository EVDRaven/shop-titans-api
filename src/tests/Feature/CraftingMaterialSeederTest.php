<?php

namespace Tests\Feature;

use App\Models\Blueprint;
use App\Models\CraftingMaterial;
use Database\Seeders\CraftingMaterialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CraftingMaterialSeederTest extends TestCase
{
    use RefreshDatabase;

    private string $excelPath;

    protected function setUp(): void
    {
        parent::setUp();
        ini_set('memory_limit', '512M');
        $this->excelPath = storage_path('app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx');
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

    public function test_seeder_creates_all_four_material_types(): void
    {
        $this->createBlueprint('Squire Sword');

        $seeder = new CraftingMaterialSeeder;
        $seeder->filePath = $this->excelPath;
        $seeder->run();

        $this->assertEquals(1, CraftingMaterial::where('material_type', 'blueprint')->count());
        $this->assertEquals(66, CraftingMaterial::where('material_type', 'quest_component')->count());
        $this->assertEquals(6, CraftingMaterial::where('material_type', 'sigil')->count());
        $this->assertEquals(15, CraftingMaterial::where('material_type', 'enchantment')->count());
        $this->assertEquals(88, CraftingMaterial::count());
    }

    public function test_blueprint_material_links_to_real_blueprint(): void
    {
        $blueprint = $this->createBlueprint('Squire Sword');

        $seeder = new CraftingMaterialSeeder;
        $seeder->filePath = $this->excelPath;
        $seeder->run();

        $material = CraftingMaterial::where('material_type', 'blueprint')->first();
        $this->assertNotNull($material);
        $this->assertEquals('blueprint:'.$blueprint->id, $material->source_key);
        $this->assertEquals($blueprint->id, $material->blueprint_id);
        $this->assertEquals('Squire Sword', $material->name);
        $this->assertEquals($blueprint->tier, $material->tier);
        $this->assertEquals($blueprint->gold_value, $material->gold_value);
    }

    public function test_non_blueprint_materials_have_null_blueprint_id(): void
    {
        $this->createBlueprint('Squire Sword');

        $seeder = new CraftingMaterialSeeder;
        $seeder->filePath = $this->excelPath;
        $seeder->run();

        $nonBlueprint = CraftingMaterial::where('material_type', '!=', 'blueprint')->get();
        $this->assertGreaterThan(0, $nonBlueprint->count());
        foreach ($nonBlueprint as $material) {
            $this->assertNull($material->blueprint_id, "Material {$material->name} ({$material->material_type}) should have null blueprint_id");
        }
    }

    public function test_source_key_format_matches_material_type(): void
    {
        $this->createBlueprint('Squire Sword');

        $seeder = new CraftingMaterialSeeder;
        $seeder->filePath = $this->excelPath;
        $seeder->run();

        $materials = CraftingMaterial::all();
        foreach ($materials as $material) {
            $prefix = $material->material_type.':';
            $this->assertStringStartsWith(
                $prefix,
                $material->source_key,
                "source_key '{$material->source_key}' should start with '{$prefix}'"
            );
        }
    }

    public function test_quest_component_materials_have_attributes(): void
    {
        $seeder = new CraftingMaterialSeeder;
        $seeder->filePath = $this->excelPath;
        $seeder->run();

        $elvenWood = CraftingMaterial::where('name', 'Elven Wood')->first();
        $this->assertNotNull($elvenWood);
        $this->assertEquals('quest_component', $elvenWood->material_type);
        $this->assertEquals(1, $elvenWood->tier);
        $this->assertEquals(100, $elvenWood->gold_value);
        $this->assertEquals('Howling Woods', $elvenWood->quest_area);
    }

    public function test_sigil_materials_have_attributes(): void
    {
        $seeder = new CraftingMaterialSeeder;
        $seeder->filePath = $this->excelPath;
        $seeder->run();

        $sigil = CraftingMaterial::where('name', 'Sigil of Might')->first();
        $this->assertNotNull($sigil);
        $this->assertEquals('sigil', $sigil->material_type);
        $this->assertEquals(28, $sigil->worker_level_required);
        $this->assertEquals(7200, $sigil->fuse_time_seconds);
        $this->assertEquals(2529, $sigil->worker_xp);
    }

    public function test_enchantment_materials_exist_without_blueprint_id(): void
    {
        $seeder = new CraftingMaterialSeeder;
        $seeder->filePath = $this->excelPath;
        $seeder->run();

        $ember = CraftingMaterial::where('name', 'Ember Element')->first();
        $this->assertNotNull($ember);
        $this->assertEquals('enchantment', $ember->material_type);
        $this->assertNull($ember->blueprint_id);
        $this->assertEquals('enchantment:Ember Element', $ember->source_key);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->createBlueprint('Squire Sword');

        $seeder = new CraftingMaterialSeeder;
        $seeder->filePath = $this->excelPath;

        $seeder->run();
        $firstCount = CraftingMaterial::count();

        $seeder->run();
        $secondCount = CraftingMaterial::count();

        $this->assertEquals($firstCount, $secondCount);
        $this->assertEquals(88, $secondCount);

        $this->assertEquals(88, CraftingMaterial::distinct()->count('source_key'));
    }

    public function test_missing_blueprints_are_reported_as_warnings(): void
    {
        $seeder = new CraftingMaterialSeeder;
        $seeder->filePath = $this->excelPath;
        $seeder->run();

        $this->assertNotNull($seeder->warnings);
        $this->assertGreaterThan(0, $seeder->warnings->count());

        $squireSwordWarning = $seeder->warnings->firstWhere('component', 'Squire Sword');
        $this->assertNotNull($squireSwordWarning);
        $this->assertStringContainsString('no existe en la tabla blueprints', $squireSwordWarning['message']);
    }

    public function test_no_unknown_component_types(): void
    {
        $seeder = new CraftingMaterialSeeder;
        $seeder->filePath = $this->excelPath;
        $seeder->run();

        $unknownWarnings = $seeder->warnings->filter(fn ($w) => str_contains($w['message'] ?? '', 'no se pudo clasificar'));
        $this->assertEquals(0, $unknownWarnings->count());
    }
}
