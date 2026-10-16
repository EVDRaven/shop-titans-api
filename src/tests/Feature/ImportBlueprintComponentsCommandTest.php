<?php

namespace Tests\Feature;

use App\Imports\BlueprintExcelReader;
use App\Imports\BlueprintRowData;
use App\Imports\ComponentImportResult;
use App\Models\Blueprint;
use App\Models\BlueprintComponent;
use App\Models\CraftingMaterial;
use App\Models\Quality;
use App\Services\BlueprintImportService;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportBlueprintComponentsCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $filePath;

    protected function setUp(): void
    {
        parent::setUp();

        // Archivo real para satisfacer la comprobación file_exists del comando.
        // El lector se sustituye por un mock, por lo que no se parsea el Excel.
        $this->filePath = __DIR__.'/../fixtures/blueprints_test.xlsx';

        Quality::create(['name' => 'Normal', 'rank' => 1]);
        Quality::create(['name' => 'Superior', 'rank' => 2]);
    }

    private function fakeReader(array $rows): void
    {
        $this->mock(BlueprintExcelReader::class, function ($mock) use ($rows) {
            $mock->shouldReceive('read')->andReturn(collect($rows));
        });
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

    public function test_command_imports_components_and_reports_success(): void
    {
        $blueprint = $this->createBlueprint('Master Sword');
        $material = $this->createMaterial('Iron Ingot');

        $this->fakeReader([
            $this->makeRow('Master Sword', [
                ['name' => 'Iron Ingot', 'quality_name' => 'Normal', 'amount' => 3],
            ]),
        ]);

        $this->artisan('import:blueprint-components', ['file' => $this->filePath])
            ->expectsOutputToContain('Blueprints procesados')
            ->expectsOutputToContain('Componentes insertados')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('blueprint_components', [
            'blueprint_id' => $blueprint->id,
            'component_id' => $material->id,
            'amount' => 3,
        ]);
    }

    public function test_command_fails_when_file_not_found(): void
    {
        $this->artisan('import:blueprint-components', ['file' => '/ruta/inexistente.xlsx'])
            ->expectsOutputToContain('Archivo no encontrado')
            ->assertExitCode(Command::FAILURE);

        $this->assertEquals(0, BlueprintComponent::count());
    }

    public function test_command_fails_when_service_reports_failure(): void
    {
        $this->fakeReader([]);

        $this->mock(BlueprintImportService::class, function ($mock) {
            $mock->shouldReceive('importComponents')->andReturn(
                ComponentImportResult::failure(collect([
                    ['blueprint' => null, 'message' => 'Error de escritura en base de datos.'],
                ]))
            );
        });

        // Aunque el servicio falle, la tabla de métricas y las incidencias deben mostrarse.
        $this->artisan('import:blueprint-components', ['file' => $this->filePath])
            ->expectsOutputToContain('falló')
            ->expectsOutputToContain('Blueprints procesados')
            ->expectsOutputToContain('Componentes insertados')
            ->expectsOutputToContain('Errores de importación')
            ->doesntExpectOutputToContain('sin incidencias')
            ->assertExitCode(Command::FAILURE);

        $this->assertEquals(0, BlueprintComponent::count());
    }

    public function test_command_fails_when_catalog_loading_fails(): void
    {
        $this->fakeReader([
            $this->makeRow('Master Sword', [
                ['name' => 'Iron Ingot', 'quality_name' => 'Normal', 'amount' => 1],
            ]),
        ]);

        // Simula un fallo al cargar los catálogos de componentes eliminando la tabla
        // en la base de datos aislada SQLite :memory: (no afecta a PostgreSQL).
        Schema::dropIfExists('crafting_materials');

        $this->artisan('import:blueprint-components', ['file' => $this->filePath])
            ->expectsOutputToContain('falló')
            ->expectsOutputToContain('Errores de importación')
            ->expectsOutputToContain('catálogos de componentes')
            ->doesntExpectOutputToContain('sin incidencias')
            ->assertExitCode(Command::FAILURE);

        $this->assertEquals(0, BlueprintComponent::count());
    }

    public function test_command_reports_unresolved_references(): void
    {
        $this->createBlueprint('Mystery Item');

        $this->fakeReader([
            $this->makeRow('Mystery Item', [
                ['name' => 'Nonexistent Material', 'quality_name' => 'Normal', 'amount' => 1],
            ]),
        ]);

        $this->artisan('import:blueprint-components', ['file' => $this->filePath])
            ->expectsOutputToContain('Referencias sin resolver')
            ->expectsOutputToContain('los datos pueden estar incompletos')
            ->assertExitCode(Command::FAILURE);

        $this->assertEquals(0, BlueprintComponent::count());
    }

    public function test_command_reports_skipped_blueprints(): void
    {
        $this->fakeReader([
            $this->makeRow('Ghost Sword', [
                ['name' => 'Iron Ingot', 'quality_name' => 'Normal', 'amount' => 1],
            ]),
        ]);

        $this->artisan('import:blueprint-components', ['file' => $this->filePath])
            ->expectsOutputToContain('Errores de importación')
            ->expectsOutputToContain('Blueprints omitidos')
            ->assertExitCode(Command::FAILURE);

        $this->assertEquals(0, BlueprintComponent::count());
    }

    public function test_command_fails_when_quality_not_found(): void
    {
        $this->createBlueprint('Qualityless Sword');
        $this->createMaterial('Iron Ingot');

        $this->fakeReader([
            $this->makeRow('Qualityless Sword', [
                ['name' => 'Iron Ingot', 'quality_name' => 'Legendary', 'amount' => 1],
            ]),
        ]);

        $this->artisan('import:blueprint-components', ['file' => $this->filePath])
            ->expectsOutputToContain('Errores de importación')
            ->assertExitCode(Command::FAILURE);

        $this->assertEquals(0, BlueprintComponent::count());
    }
}
