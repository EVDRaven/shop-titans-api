<?php

namespace App\Imports;

use Illuminate\Support\Collection;

class ImportResult
{
    public function __construct(
        public readonly int $rowsProcessed,
        public readonly int $blueprintsInserted,
        public readonly int $blueprintsUpdated,
        public readonly int $blueprintsSkipped,
        public readonly Collection $errors,
        public readonly bool $success,
    ) {}

    public static function failure(Collection $errors): self
    {
        return new self(
            rowsProcessed: 0,
            blueprintsInserted: 0,
            blueprintsUpdated: 0,
            blueprintsSkipped: 0,
            errors: $errors,
            success: false,
        );
    }
}
