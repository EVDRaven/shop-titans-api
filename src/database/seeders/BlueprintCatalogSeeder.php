<?php

namespace Database\Seeders;

use App\Imports\BlueprintExcelReader;
use Database\Seeders\Concerns\ResolvesBlueprintExcelPath;
use Illuminate\Database\Seeder;

abstract class BlueprintCatalogSeeder extends Seeder
{
    use ResolvesBlueprintExcelPath;

    protected BlueprintExcelReader $reader;

    public function run(): void
    {
        $this->reader = app(BlueprintExcelReader::class);

        $this->seed($this->resolveBlueprintExcelPath());
    }

    abstract protected function seed(string $filePath): void;
}
