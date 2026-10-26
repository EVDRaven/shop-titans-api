<?php

namespace Tests\Feature;

use App\Imports\BlueprintExcelReader;
use App\Imports\BlueprintRowData;
use App\Models\Blueprint;
use App\Models\BlueprintComponent;
use App\Models\CraftingMaterial;
use App\Models\Element;
use App\Models\Enchantment;
use App\Models\ItemCategory;
use App\Models\Quality;
use App\Models\Resource;
use App\Models\Spirit;
use App\Models\SpiritAffinity;
use App\Models\Worker;
use App\Models\WorkerLevel;
use Database\Seeders\BlueprintMvpSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class BlueprintMvpSeederTest extends TestCase
{
    use RefreshDatabase;

    private string $filePath;

    protected function setUp(): void
    {
        parent::setUp();

        // Archivo real existente para satisfacer la resolución de la ruta del
        // trait; el lector se sustituye por un mock, por lo que no se parsea.
        $this->filePath = __DIR__.'/../fixtures/blueprints_test.xlsx';
    }

    /**
     * Sustituye BlueprintExcelReader por un doble que registra el orden de las
     * etapas ejecutadas y devuelve datos controlados.
     *
     * @param  array<int, array<int, BlueprintRowData>>  $readResults  Resultados sucesivos de read().
     * @param  array<int, string>  $calls  Bitácora de etapas (por referencia).
     * @param  array<string, mixed>  $materialData  Datos devueltos por readCraftingMaterialData().
     */
    private function fakeReader(array $readResults, array &$calls, array $materialData = []): void
    {
        $readIndex = 0;

        $materialData += [
            'componentNames' => collect(),
            'blueprintNames' => collect(),
            'questComponents' => collect(),
            'sigils' => collect(),
            'enchantmentNames' => collect(),
        ];

        $this->mock(BlueprintExcelReader::class, function ($mock) use ($readResults, &$calls, &$readIndex, $materialData) {
            $mock->shouldReceive('read')->andReturnUsing(function () use ($readResults, &$calls, &$readIndex) {
                // Las lecturas de los comandos alternan blueprints -> componentes.
                $calls[] = $readIndex % 2 === 0 ? 'import:blueprints' : 'import:blueprint-components';

                return collect($readResults[$readIndex++] ?? []);
            });

            $mock->shouldReceive('readCraftingMaterialData')->andReturnUsing(function () use (&$calls, $materialData) {
                $calls[] = 'crafting_materials';

                return $materialData;
            });

            $mock->shouldReceive('readElementalAffinityTokens')->andReturnUsing(function () use (&$calls) {
                $calls[] = 'catalogs';

                return collect(['Fire']);
            });

            $mock->shouldReceive('readEnchantmentCatalog')->andReturnUsing(function () use (&$calls) {
                $calls[] = 'catalogs';

                return collect(['Fire Element' => ['name' => 'Fire Element', 'type' => Enchantment::TYPE_ELEMENT]]);
            });

            $mock->shouldReceive('readSpiritAffinityNames')->andReturnUsing(function () use (&$calls) {
                $calls[] = 'catalogs';

                return collect(['Spirit Affinity A']);
            });

            $mock->shouldReceive('readBuiltInSpiritNames')->andReturnUsing(function () use (&$calls) {
                $calls[] = 'catalogs';

                return collect(['Built-in Spirit A']);
            });
        });
    }

    private function makeRow(string $name, array $components = []): BlueprintRowData
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

    /**
     * @return array<string, mixed>
     */
    private function materialData(): array
    {
        return [
            'componentNames' => collect(['Iron Ingot']),
            'blueprintNames' => collect(['Master Sword']),
            'questComponents' => collect([
                'Iron Ingot' => [
                    'name' => 'Iron Ingot',
                    'type' => 'Component',
                    'tier' => 1,
                    'gold_value' => 50,
                    'quest_area' => 'Howling Woods',
                ],
            ]),
            'sigils' => collect(),
            'enchantmentNames' => collect(),
        ];
    }

    private function swordRow(): BlueprintRowData
    {
        return $this->makeRow('Master Sword', [
            ['name' => 'Iron Ingot', 'quality_name' => 'Normal', 'amount' => 3],
        ]);
    }

    private function makeSeeder(): BlueprintMvpSeeder
    {
        $seeder = new BlueprintMvpSeeder;
        $seeder->filePath = $this->filePath;

        return $seeder;
    }

    public function test_it_runs_stages_in_dependency_order(): void
    {
        $calls = [];
        $this->fakeReader([[$this->swordRow()], [$this->swordRow()]], $calls, $this->materialData());

        $this->makeSeeder()->run();

        $this->assertSame([
            'catalogs',
            'catalogs',
            'catalogs',
            'catalogs',
            'import:blueprints',
            'crafting_materials',
            'import:blueprint-components',
        ], $calls);
    }

    public function test_it_applies_every_stage(): void
    {
        $calls = [];
        $this->fakeReader([[$this->swordRow()], [$this->swordRow()]], $calls, $this->materialData());

        $this->makeSeeder()->run();

        // 1. Catálogos
        $this->assertTrue(Resource::where('name', 'Iron')->exists());
        $this->assertTrue(Quality::where('name', 'Normal')->exists());
        $this->assertTrue(ItemCategory::where('name', 'Sword')->exists());
        $this->assertTrue(Worker::where('worker_key', 'Blacksmith')->exists());
        $this->assertTrue(WorkerLevel::where('level', 1)->exists());
        $this->assertTrue(Element::where('name', 'Fire')->exists());
        $this->assertTrue(Enchantment::where('name', 'Fire Element')->exists());
        $this->assertTrue(SpiritAffinity::where('name', 'Spirit Affinity A')->exists());
        $this->assertTrue(Spirit::where('name', 'Built-in Spirit A')->exists());

        // 2. Blueprints
        $this->assertTrue(Blueprint::where('name', 'Master Sword')->exists());

        // 3. Materiales
        $this->assertTrue(CraftingMaterial::where('name', 'Iron Ingot')->exists());

        // 4. Componentes
        $this->assertEquals(1, BlueprintComponent::count());
    }

    public function test_it_stops_when_blueprint_import_fails(): void
    {
        $calls = [];
        $this->fakeReader([], $calls, $this->materialData());

        $seeder = new BlueprintMvpSeeder;
        // Ruta inexistente: el comando import:blueprints no puede leer el archivo.
        $seeder->filePath = '/ruta/inexistente.xlsx';

        try {
            $seeder->run();
            $this->fail('Se esperaba una RuntimeException por el fallo de import:blueprints.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('import:blueprints', $e->getMessage());
            $this->assertStringContainsString('código de salida 1', $e->getMessage());
        }

        // Solo se ejecutaron los catálogos; ninguna etapa posterior.
        $this->assertSame(['catalogs', 'catalogs', 'catalogs', 'catalogs'], $calls);
        $this->assertEquals(0, Blueprint::count());
        $this->assertEquals(0, CraftingMaterial::count());
        $this->assertEquals(0, BlueprintComponent::count());
    }

    public function test_it_stops_when_component_import_fails(): void
    {
        $calls = [];
        $this->fakeReader([
            [$this->swordRow()],
            // Segundo read(): un blueprint inexistente provoca componentes omitidos
            // y, con ello, incidencias que hacen fallar la etapa de componentes.
            [$this->makeRow('Ghost Sword', [
                ['name' => 'Iron Ingot', 'quality_name' => 'Normal', 'amount' => 1],
            ])],
        ], $calls, $this->materialData());

        try {
            $this->makeSeeder()->run();
            $this->fail('Se esperaba una RuntimeException por el fallo de import:blueprint-components.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('import:blueprint-components', $e->getMessage());
            $this->assertStringContainsString('código de salida 1', $e->getMessage());
        }

        $this->assertSame([
            'catalogs',
            'catalogs',
            'catalogs',
            'catalogs',
            'import:blueprints',
            'crafting_materials',
            'import:blueprint-components',
        ], $calls);

        // Las etapas previas sí se aplicaron antes del fallo.
        $this->assertTrue(Blueprint::where('name', 'Master Sword')->exists());
        $this->assertTrue(CraftingMaterial::where('name', 'Iron Ingot')->exists());
        $this->assertEquals(0, BlueprintComponent::count());
    }

    public function test_it_is_idempotent_across_reruns(): void
    {
        $calls = [];
        $this->fakeReader([
            [$this->swordRow()],
            [$this->swordRow()],
            [$this->swordRow()],
            [$this->swordRow()],
        ], $calls, $this->materialData());

        $seeder = $this->makeSeeder();
        $seeder->run();

        $blueprints = Blueprint::count();
        $materials = CraftingMaterial::count();
        $components = BlueprintComponent::count();

        $seeder->run();

        $this->assertEquals(1, $blueprints);
        $this->assertEquals(1, $materials);
        $this->assertEquals(1, $components);

        $this->assertEquals($blueprints, Blueprint::count());
        $this->assertEquals($materials, CraftingMaterial::count());
        $this->assertEquals($components, BlueprintComponent::count());
    }
}
