<?php

namespace Tests\Feature;

use App\Imports\BlueprintRowData;
use App\Models\Blueprint;
use App\Services\BlueprintImportService;
use Database\Seeders\ItemCategorySeeder;
use Database\Seeders\ResourceSeeder;
use Database\Seeders\WorkerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlueprintImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private BlueprintImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            ResourceSeeder::class,
            ItemCategorySeeder::class,
            WorkerSeeder::class,
        ]);
        $this->service = new BlueprintImportService;
    }

    public function test_import_creates_blueprints_with_relations(): void
    {
        $rows = collect([
            $this->makeSquireSwordDto(),
            $this->makeEnchantedBowDto(),
        ]);

        $result = $this->service->import($rows);

        $this->assertTrue($result->success);
        $this->assertEquals(2, $result->rowsProcessed);
        $this->assertEquals(2, $result->blueprintsInserted);
        $this->assertEquals(0, $result->blueprintsUpdated);

        $this->assertEquals(2, Blueprint::count());

        $squireSword = Blueprint::where('name', 'Squire Sword')->first();
        $this->assertNotNull($squireSword);
        $this->assertEquals(1, $squireSword->tier);
        $this->assertEquals(27, $squireSword->gold_value);
        $this->assertEquals('Sword', $squireSword->category->name);
        $this->assertNull($squireSword->elemental_affinity_id);
        $this->assertNull($squireSword->spirit_affinity_id);
        $this->assertNull($squireSword->built_in_element_id);
        $this->assertNull($squireSword->built_in_spirit_id);

        $this->assertEquals(1, $squireSword->blueprintWorkers->count());
        $this->assertEquals(2, $squireSword->blueprintResources->count());
        $this->assertEquals(5, $squireSword->craftingUpgrades->count());
        $this->assertEquals(5, $squireSword->starforgedMilestones->count());
        $this->assertEquals(3, $squireSword->ascensionUpgrades->count());
        $this->assertEquals(3, $squireSword->transcendenceUpgrades->count());

        $ironResource = $squireSword->blueprintResources->firstWhere(
            fn ($r) => $r->resource->name === 'Iron'
        );
        $this->assertEquals(2, $ironResource->quantity);
    }

    public function test_import_is_idempotent(): void
    {
        $rows = collect([$this->makeSquireSwordDto()]);

        $firstResult = $this->service->import($rows);
        $this->assertTrue($firstResult->success);
        $this->assertEquals(1, $firstResult->blueprintsInserted);

        $secondResult = $this->service->import($rows);
        $this->assertTrue($secondResult->success);
        $this->assertEquals(0, $secondResult->blueprintsInserted);
        $this->assertEquals(1, $secondResult->blueprintsUpdated);

        $this->assertEquals(1, Blueprint::count());

        $squireSword = Blueprint::where('name', 'Squire Sword')->first();
        $this->assertEquals(1, $squireSword->blueprintWorkers->count());
        $this->assertEquals(2, $squireSword->blueprintResources->count());
        $this->assertEquals(5, $squireSword->craftingUpgrades->count());
    }

    public function test_import_removes_obsolete_relations_on_reimport(): void
    {
        $original = $this->makeSquireSwordDto();
        $this->service->import(collect([$original]));

        $squireSword = Blueprint::where('name', 'Squire Sword')->first();
        $this->assertEquals(2, $squireSword->blueprintResources->count());

        $modified = $this->makeSquireSwordDto(extraResources: [
            ['resource_name' => 'Steel', 'quantity' => 5],
        ]);
        $this->service->import(collect([$modified]));

        $squireSword->refresh();
        $this->assertEquals(3, $squireSword->blueprintResources->count());

        $resourceNames = $squireSword->blueprintResources->map(fn ($r) => $r->resource->name)->toArray();
        $this->assertContains('Iron', $resourceNames);
        $this->assertContains('Wood', $resourceNames);
        $this->assertContains('Steel', $resourceNames);
    }

    public function test_import_fails_when_category_not_found(): void
    {
        $dto = $this->makeSquireSwordDto(categoryName: 'NonExistentCategory');
        $result = $this->service->import(collect([$dto]));

        $this->assertFalse($result->success);
        $this->assertEquals(0, Blueprint::count());
        $this->assertNotEmpty($result->errors);
        $this->assertStringContainsString('NonExistentCategory', $result->errors->first()['message']);
    }

    public function test_import_fails_when_worker_not_found(): void
    {
        $dto = $this->makeSquireSwordDto(workers: [
            ['worker_key' => 'nonexistent_worker', 'level_required' => 1],
        ]);
        $result = $this->service->import(collect([$dto]));

        $this->assertFalse($result->success);
        $this->assertEquals(0, Blueprint::count());
        $this->assertNotEmpty($result->errors);
        $this->assertStringContainsString('nonexistent_worker', $result->errors->first()['message']);
    }

    public function test_import_rolls_back_on_write_error(): void
    {
        $dto = $this->makeSquireSwordDto(workers: [
            ['worker_key' => 'Blacksmith', 'level_required' => 1],
            ['worker_key' => 'Blacksmith', 'level_required' => 2],
        ]);

        $result = $this->service->import(collect([$dto]));

        $this->assertFalse($result->success);
        $this->assertEquals(0, Blueprint::count());
    }

    private function makeSquireSwordDto(
        string $categoryName = 'Sword',
        array $workers = [],
        array $extraResources = [],
    ): BlueprintRowData {
        $workers = $workers ?: [
            ['worker_key' => 'Blacksmith', 'level_required' => 1],
        ];

        $resources = array_merge([
            ['resource_name' => 'Iron', 'quantity' => 2],
            ['resource_name' => 'Wood', 'quantity' => 1],
        ], $extraResources);

        return new BlueprintRowData(
            name: 'Squire Sword',
            categoryName: $categoryName,
            unlockPrerequisite: null,
            researchScrolls: 5,
            antiqueTokens: null,
            tier: 1,
            goldValue: 27,
            craftTimeSeconds: 15,
            craftTimeFormatted: '00:00:15',
            valuePerSecond: 1.8,
            merchantXp: 6,
            merchantXpPerSecond: 0.4,
            workerXp: 5,
            fusionXp: 0,
            favor: 0,
            airshipPower: 0,
            atk: 5,
            def: 0,
            hp: 0,
            eva: 0,
            crit: 0,
            manaShield: null,
            discountEnergy: 30,
            surchargeEnergy: 60,
            suggestEnergy: 30,
            speedUpEnergy: 15,
            availableAsAntiqueAt: null,
            workers: $workers,
            resources: $resources,
            craftingUpgrades: [
                ['upgrade_level' => 1, 'name' => 'Sharper Edge', 'crafts_required' => 10],
                ['upgrade_level' => 2, 'name' => 'Fine Edge', 'crafts_required' => 20],
                ['upgrade_level' => 3, 'name' => 'Master Edge', 'crafts_required' => 50],
                ['upgrade_level' => 4, 'name' => 'Flawless Edge', 'crafts_required' => 100],
                ['upgrade_level' => 5, 'name' => 'Perfect Edge', 'crafts_required' => 250],
            ],
            starforgedMilestones: [
                ['milestone_number' => 1, 'name' => 'Starforged I', 'crafts_required' => 5],
                ['milestone_number' => 2, 'name' => 'Starforged II', 'crafts_required' => 10],
                ['milestone_number' => 3, 'name' => 'Starforged III', 'crafts_required' => 20],
                ['milestone_number' => 4, 'name' => 'Starforged IV', 'crafts_required' => 50],
                ['milestone_number' => 5, 'name' => 'Starforged V', 'crafts_required' => 100],
            ],
            ascensionUpgrades: [
                ['upgrade_level' => 1, 'name' => 'Ascension I', 'shards_required' => 5],
                ['upgrade_level' => 2, 'name' => 'Ascension II', 'shards_required' => 15],
                ['upgrade_level' => 3, 'name' => 'Ascension III', 'shards_required' => 50],
            ],
            transcendenceUpgrades: [
                ['upgrade_level' => 1, 'name' => 'Transcendence I', 'seals_required' => 3],
                ['upgrade_level' => 2, 'name' => 'Transcendence II', 'seals_required' => 10],
                ['upgrade_level' => 3, 'name' => 'Transcendence III', 'seals_required' => 30],
            ],
        );
    }

    private function makeEnchantedBowDto(): BlueprintRowData
    {
        return new BlueprintRowData(
            name: 'Enchanted Bow',
            categoryName: 'Bow',
            unlockPrerequisite: 'Hunter',
            researchScrolls: 20,
            antiqueTokens: 5,
            tier: 5,
            goldValue: 1500,
            craftTimeSeconds: 1800,
            craftTimeFormatted: '00:30:00',
            valuePerSecond: 0.8333,
            merchantXp: 120,
            merchantXpPerSecond: 0.0667,
            workerXp: 80,
            fusionXp: 10,
            favor: 5,
            airshipPower: 10,
            atk: 20,
            def: null,
            hp: 50,
            eva: null,
            crit: 10,
            manaShield: null,
            discountEnergy: 30,
            surchargeEnergy: 60,
            suggestEnergy: 30,
            speedUpEnergy: 15,
            availableAsAntiqueAt: '2020-03-23',
            workers: [
                ['worker_key' => 'Carpenter', 'level_required' => 10],
                ['worker_key' => 'Tailor', 'level_required' => 5],
            ],
            resources: [
                ['resource_name' => 'Wood', 'quantity' => 5],
                ['resource_name' => 'Steel', 'quantity' => 3],
                ['resource_name' => 'Mana', 'quantity' => 2],
            ],
            craftingUpgrades: [
                ['upgrade_level' => 1, 'name' => 'Sharper Shot', 'crafts_required' => 10],
                ['upgrade_level' => 2, 'name' => 'Fine Shot', 'crafts_required' => 20],
                ['upgrade_level' => 3, 'name' => 'Master Shot', 'crafts_required' => 50],
                ['upgrade_level' => 4, 'name' => 'Flawless Shot', 'crafts_required' => 100],
                ['upgrade_level' => 5, 'name' => 'Perfect Shot', 'crafts_required' => 250],
            ],
            starforgedMilestones: [
                ['milestone_number' => 1, 'name' => 'Starforged I', 'crafts_required' => 5],
                ['milestone_number' => 2, 'name' => 'Starforged II', 'crafts_required' => 10],
                ['milestone_number' => 3, 'name' => 'Starforged III', 'crafts_required' => 20],
                ['milestone_number' => 4, 'name' => 'Starforged IV', 'crafts_required' => 50],
                ['milestone_number' => 5, 'name' => 'Starforged V', 'crafts_required' => 100],
            ],
            ascensionUpgrades: [
                ['upgrade_level' => 1, 'name' => 'Ascension I', 'shards_required' => 5],
                ['upgrade_level' => 2, 'name' => 'Ascension II', 'shards_required' => 15],
                ['upgrade_level' => 3, 'name' => 'Ascension III', 'shards_required' => 50],
            ],
            transcendenceUpgrades: [
                ['upgrade_level' => 1, 'name' => 'Transcendence I', 'seals_required' => 3],
                ['upgrade_level' => 2, 'name' => 'Transcendence II', 'seals_required' => 10],
                ['upgrade_level' => 3, 'name' => 'Transcendence III', 'seals_required' => 30],
            ],
        );
    }
}
