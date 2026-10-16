<?php

namespace App\Console\Commands;

use App\Imports\BlueprintExcelReader;
use App\Services\BlueprintImportService;
use Illuminate\Console\Command;

class ImportBlueprints extends Command
{
    protected $signature = 'import:blueprints {file : Ruta al archivo Excel (.xlsx)}';

    protected $description = 'Importa blueprints del Excel v26.9.1 al MVP (sin componentes).';

    public function handle(BlueprintExcelReader $reader, BlueprintImportService $service): int
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("Archivo no encontrado: {$filePath}");

            return self::FAILURE;
        }

        $this->info('Leyendo archivo Excel...');
        $rows = $reader->read($filePath);
        $this->info("Filas leídas (excluyendo Enchantment): {$rows->count()}");

        $this->info('Importando a la base de datos...');
        $result = $service->import($rows);

        if (! $result->success) {
            $this->error('La importación falló.');
            $this->error("Errores de validación: {$result->errors->count()}");

            $result->errors->each(function ($error) {
                $row = $error['row'] ?? 'N/A';
                $blueprint = $error['blueprint'] ?? 'N/A';
                $this->line("  [Fila {$row}] {$blueprint}: {$error['message']}");
            });

            return self::FAILURE;
        }

        $this->info('Importación completada con éxito.');
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Filas procesadas', $result->rowsProcessed],
                ['Blueprints insertados', $result->blueprintsInserted],
                ['Blueprints actualizados', $result->blueprintsUpdated],
                ['Blueprints omitidos', $result->blueprintsSkipped],
            ],
        );

        if ($result->errors->isNotEmpty()) {
            $this->warn("Advertencias: {$result->errors->count()}");
        }

        return self::SUCCESS;
    }
}
