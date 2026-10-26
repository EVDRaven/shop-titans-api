<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\ResolvesBlueprintExcelPath;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

/**
 * Orquesta la construcción del conjunto de datos del MVP de Blueprints.
 *
 * Reproduce, en el orden de dependencias vigente, las etapas necesarias para
 * una instalación nueva:
 *
 *   1. Catálogos (recursos, calidades, categorías, workers, elementos, ...).
 *   2. Blueprints vía el comando existente `import:blueprints`.
 *   3. Materiales de crafteo vía `CraftingMaterialSeeder`.
 *   4. Componentes vía el comando existente `import:blueprint-components`.
 *
 * Si una etapa falla se detiene el proceso y se propaga el error junto con el
 * código de salida del comando de importación. Es idempotente mientras las
 * etapas subyacentes lo sean.
 */
class BlueprintMvpSeeder extends Seeder
{
    use ResolvesBlueprintExcelPath;

    public function run(): void
    {
        $this->call([
            ResourceSeeder::class,
            QualitySeeder::class,
            ItemCategorySeeder::class,
            WorkerSeeder::class,
            WorkerLevelSeeder::class,
            ElementSeeder::class,
            EnchantmentSeeder::class,
            SpiritAffinitySeeder::class,
            SpiritSeeder::class,
        ]);

        $filePath = $this->resolveBlueprintExcelPath();

        $this->runImportCommand('import:blueprints', $filePath);

        /** @var CraftingMaterialSeeder $materialSeeder */
        $materialSeeder = $this->resolve(CraftingMaterialSeeder::class);
        $materialSeeder->filePath = $filePath;
        $materialSeeder->__invoke();

        $this->runImportCommand('import:blueprint-components', $filePath);
    }

    /**
     * Ejecuta un comando de importación propagando su código de salida.
     *
     * @throws RuntimeException si el comando no termina con éxito.
     */
    private function runImportCommand(string $command, string $filePath): void
    {
        $exitCode = Artisan::call($command, ['file' => $filePath]);

        if ($exitCode !== Command::SUCCESS) {
            throw new RuntimeException(sprintf(
                "La etapa '%s' falló con código de salida %d. Se detiene la construcción del MVP.",
                $command,
                $exitCode
            ));
        }
    }
}
