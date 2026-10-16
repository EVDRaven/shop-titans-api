<?php

namespace Tests\Unit;

use App\Imports\BlueprintExcelReader;
use Tests\TestCase;

class BlueprintExcelReaderTest extends TestCase
{
    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturePath = __DIR__.'/../fixtures/blueprints_test.xlsx';
    }

    public function test_reader_returns_collection_of_dto(): void
    {
        $reader = new BlueprintExcelReader;
        $rows = $reader->read($this->fixturePath);

        $this->assertCount(2, $rows);
    }

    public function test_enchantment_rows_are_excluded(): void
    {
        $reader = new BlueprintExcelReader;
        $rows = $reader->read($this->fixturePath);

        $names = $rows->pluck('name')->toArray();
        $this->assertNotContains('Ember Element', $names);
        $this->assertContains('Squire Sword', $names);
        $this->assertContains('Enchanted Bow', $names);
    }

    public function test_dash_values_are_converted_to_null(): void
    {
        $reader = new BlueprintExcelReader;
        $rows = $reader->read($this->fixturePath);

        $squireSword = $rows->firstWhere('name', 'Squire Sword');

        $this->assertNull($squireSword->unlockPrerequisite);
        $this->assertNull($squireSword->antiqueTokens);
        $this->assertNull($squireSword->manaShield);
        $this->assertNull($squireSword->availableAsAntiqueAt);
    }

    public function test_workers_are_parsed_correctly(): void
    {
        $reader = new BlueprintExcelReader;
        $rows = $reader->read($this->fixturePath);

        $squireSword = $rows->firstWhere('name', 'Squire Sword');
        $this->assertCount(1, $squireSword->workers);
        $this->assertEquals('Blacksmith', $squireSword->workers[0]['worker_key']);
        $this->assertEquals(1, $squireSword->workers[0]['level_required']);

        $enchantedBow = $rows->firstWhere('name', 'Enchanted Bow');
        $this->assertCount(2, $enchantedBow->workers);
        $this->assertEquals('Carpenter', $enchantedBow->workers[0]['worker_key']);
        $this->assertEquals(10, $enchantedBow->workers[0]['level_required']);
        $this->assertEquals('Tailor', $enchantedBow->workers[1]['worker_key']);
        $this->assertEquals(5, $enchantedBow->workers[1]['level_required']);
    }

    public function test_resources_are_parsed_only_with_quantity_above_zero(): void
    {
        $reader = new BlueprintExcelReader;
        $rows = $reader->read($this->fixturePath);

        $squireSword = $rows->firstWhere('name', 'Squire Sword');
        $this->assertCount(2, $squireSword->resources);

        $resourceNames = array_column($squireSword->resources, 'resource_name');
        $this->assertContains('Iron', $resourceNames);
        $this->assertContains('Wood', $resourceNames);

        $iron = collect($squireSword->resources)->firstWhere('resource_name', 'Iron');
        $this->assertEquals(2, $iron['quantity']);

        $enchantedBow = $rows->firstWhere('name', 'Enchanted Bow');
        $this->assertCount(3, $enchantedBow->resources);
        $bowResourceNames = array_column($enchantedBow->resources, 'resource_name');
        $this->assertContains('Wood', $bowResourceNames);
        $this->assertContains('Steel', $bowResourceNames);
        $this->assertContains('Mana', $bowResourceNames);
    }

    public function test_crafting_upgrades_are_parsed(): void
    {
        $reader = new BlueprintExcelReader;
        $rows = $reader->read($this->fixturePath);

        $squireSword = $rows->firstWhere('name', 'Squire Sword');
        $this->assertCount(5, $squireSword->craftingUpgrades);
        $this->assertEquals(1, $squireSword->craftingUpgrades[0]['upgrade_level']);
        $this->assertEquals('Sharper Edge', $squireSword->craftingUpgrades[0]['name']);
        $this->assertEquals(10, $squireSword->craftingUpgrades[0]['crafts_required']);
        $this->assertEquals(5, $squireSword->craftingUpgrades[4]['upgrade_level']);
        $this->assertEquals(250, $squireSword->craftingUpgrades[4]['crafts_required']);
    }

    public function test_starforged_milestones_are_parsed(): void
    {
        $reader = new BlueprintExcelReader;
        $rows = $reader->read($this->fixturePath);

        $squireSword = $rows->firstWhere('name', 'Squire Sword');
        $this->assertCount(5, $squireSword->starforgedMilestones);
        $this->assertEquals(1, $squireSword->starforgedMilestones[0]['milestone_number']);
        $this->assertEquals('Starforged I', $squireSword->starforgedMilestones[0]['name']);
        $this->assertEquals(5, $squireSword->starforgedMilestones[0]['crafts_required']);
    }

    public function test_ascension_and_transcendence_upgrades_are_parsed(): void
    {
        $reader = new BlueprintExcelReader;
        $rows = $reader->read($this->fixturePath);

        $squireSword = $rows->firstWhere('name', 'Squire Sword');
        $this->assertCount(3, $squireSword->ascensionUpgrades);
        $this->assertEquals('Ascension I', $squireSword->ascensionUpgrades[0]['name']);
        $this->assertEquals(5, $squireSword->ascensionUpgrades[0]['shards_required']);

        $this->assertCount(3, $squireSword->transcendenceUpgrades);
        $this->assertEquals('Transcendence I', $squireSword->transcendenceUpgrades[0]['name']);
        $this->assertEquals(3, $squireSword->transcendenceUpgrades[0]['seals_required']);
    }

    public function test_numeric_and_string_fields_are_parsed(): void
    {
        $reader = new BlueprintExcelReader;
        $rows = $reader->read($this->fixturePath);

        $squireSword = $rows->firstWhere('name', 'Squire Sword');
        $this->assertEquals('Sword', $squireSword->categoryName);
        $this->assertEquals(1, $squireSword->tier);
        $this->assertEquals(27, $squireSword->goldValue);
        $this->assertEquals(15, $squireSword->craftTimeSeconds);
        $this->assertEquals('00:00:15', $squireSword->craftTimeFormatted);
        $this->assertEquals(5, $squireSword->atk);
        $this->assertEquals(30, $squireSword->discountEnergy);
        $this->assertEquals(60, $squireSword->surchargeEnergy);

        $enchantedBow = $rows->firstWhere('name', 'Enchanted Bow');
        $this->assertEquals('Bow', $enchantedBow->categoryName);
        $this->assertEquals(5, $enchantedBow->tier);
        $this->assertEquals(1500, $enchantedBow->goldValue);
        $this->assertNull($enchantedBow->def);
        $this->assertEquals('2020-03-23', $enchantedBow->availableAsAntiqueAt);
    }

    public function test_components_are_parsed_as_empty_array_when_not_present(): void
    {
        $reader = new BlueprintExcelReader;
        $rows = $reader->read($this->fixturePath);

        $squireSword = $rows->firstWhere('name', 'Squire Sword');
        $this->assertIsArray($squireSword->components);
        $this->assertEmpty($squireSword->components);

        $enchantedBow = $rows->firstWhere('name', 'Enchanted Bow');
        $this->assertIsArray($enchantedBow->components);
        $this->assertEmpty($enchantedBow->components);
    }
}
