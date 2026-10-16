<?php

namespace App\Console\Commands;

use App\Imports\BlueprintExcelReader;
use App\Imports\ComponentImportResult;
use App\Services\BlueprintImportService;
use Illuminate\Console\Command;

class ImportBlueprintComponents extends Command
{
    protected $signature = 'import:blueprint-components {file : Ruta al archivo Excel (.xlsx)}';

    protected $description = 'Importa los componentes de blueprints (blueprint_components) del Excel v26.9.1.';

    public function handle(BlueprintExcelReader $reader, BlueprintImportService $service): int
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("Archivo no encontrado: {$filePath}");

            return self::FAILURE;
        }

        $this->info('Leyendo archivo Excel...');
        $rows = $reader->read($filePath);

        $referenceCount = $rows->sum(fn ($row) => count($row->components));
        $this->info("Filas leídas (excluyendo Enchantment): {$rows->count()}");
        $this->info("Referencias de componentes encontradas: {$referenceCount}");

        $this->info('Importando componentes a la base de datos...');
        $result = $service->importComponents($rows);

        if (! $result->success) {
            $this->error('La importación de componentes falló.');
        }

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Blueprints procesados', $result->blueprintsProcessed],
                ['Componentes insertados', $result->componentsInserted],
                ['Filas omitidas', $result->componentsSkipped],
                ['Referencias sin resolver', $result->unresolvedReferences->count()],
                ['Errores', $result->errors->count()],
            ],
        );

        if ($result->unresolvedReferences->isNotEmpty()) {
            $this->warn("Referencias sin resolver: {$result->unresolvedReferences->count()}");
            $result->unresolvedReferences->each(function ($reference) {
                $blueprint = $reference['blueprint'] ?? 'N/A';
                $this->line("  [{$blueprint}] {$reference['message']}");
            });
        }

        if ($result->errors->isNotEmpty()) {
            $this->warn("Errores de importación: {$result->errors->count()}");
            $result->errors->each(function ($error) {
                $blueprint = $error['blueprint'] ?? 'N/A';
                $this->line("  [{$blueprint}] {$error['message']}");
            });
        }

        if ($result->componentsSkipped > 0) {
            $this->warn("Blueprints omitidos (no encontrados en la base de datos): {$result->componentsSkipped}");
        }

        if (! $result->success) {
            return self::FAILURE;
        }

        if ($this->hasIncidents($result)) {
            $this->error('La importación finalizó con incidencias: los datos pueden estar incompletos.');

            return self::FAILURE;
        }

        $this->info('Importación de componentes completada sin incidencias.');

        return self::SUCCESS;
    }

    private function hasIncidents(ComponentImportResult $result): bool
    {
        return $result->unresolvedReferences->isNotEmpty()
            || $result->errors->isNotEmpty()
            || $result->componentsSkipped > 0;
    }
}
