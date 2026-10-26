<?php

namespace Database\Seeders\Concerns;

use RuntimeException;

/**
 * Resuelve la ruta del Excel oficial de Shop Titans usado por los seeders e importadores.
 *
 * Permite fijar una ruta explícita mediante la propiedad `$filePath`; si no se
 * establece, usa el nombre de archivo conocido y, como respaldo, el primer `.xlsx`
 * disponible en `storage/app/`. Es el único punto de resolución de la ruta para
 * evitar duplicar la lógica entre seeders.
 */
trait ResolvesBlueprintExcelPath
{
    public ?string $filePath = null;

    protected function resolveBlueprintExcelPath(): string
    {
        if ($this->filePath !== null) {
            return $this->filePath;
        }

        $default = storage_path('app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx');

        if (file_exists($default)) {
            return $default;
        }

        $candidates = glob(storage_path('app/*.xlsx'));

        if (! empty($candidates)) {
            return $candidates[0];
        }

        throw new RuntimeException('No se encontro el archivo Excel de Shop Titans en storage/app/.');
    }
}
