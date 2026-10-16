<?php

namespace App\Imports;

use Illuminate\Support\Collection;

class ComponentImportResult
{
    public function __construct(
        public readonly int $blueprintsProcessed,
        public readonly int $componentsInserted,
        public readonly int $componentsSkipped,
        public readonly Collection $unresolvedReferences,
        public readonly Collection $errors,
        public readonly bool $success,
    ) {}

    public static function failure(Collection $errors): self
    {
        return new self(
            blueprintsProcessed: 0,
            componentsInserted: 0,
            componentsSkipped: 0,
            unresolvedReferences: collect(),
            errors: $errors,
            success: false,
        );
    }
}
