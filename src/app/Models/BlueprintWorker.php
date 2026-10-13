<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlueprintWorker extends Model
{
    protected $fillable = [
        'blueprint_id',
        'worker_id',
        'level_required',
    ];

    protected function casts(): array
    {
        return [
            'level_required' => 'integer',
        ];
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(Blueprint::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
