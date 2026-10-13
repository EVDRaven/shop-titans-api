<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Worker extends Model
{
    protected $fillable = [
        'worker_key',
        'default_person_name',
        'unlock_level_required',
        'building_prerequisite',
        'gold_cost',
        'gem_cost',
        'blueprint_unlocks_raw',
    ];

    public function blueprintWorkers(): HasMany
    {
        return $this->hasMany(BlueprintWorker::class);
    }
}
