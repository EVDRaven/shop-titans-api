<?php

namespace Database\Seeders;

use App\Imports\BlueprintExcelReader;
use Illuminate\Database\Seeder;
use RuntimeException;

abstract class BlueprintCatalogSeeder extends Seeder
{
    public ?string $filePath = null;

    protected BlueprintExcelReader $reader;

    public function run(): void
    {
        $this->reader = new BlueprintExcelReader;

        $this->seed($this->resolveFilePath());
    }

    abstract protected function seed(string $filePath): void;

    private function resolveFilePath(): string
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
