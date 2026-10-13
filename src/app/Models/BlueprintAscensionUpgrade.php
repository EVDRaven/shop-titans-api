<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlueprintAscensionUpgrade extends Model
{
    protected $fillable = [
        'blueprint_id',
        'upgrade_level',
        'name',
        'shards_required',
    ];

    protected function casts(): array
    {
        return [
            'upgrade_level' => 'integer',
            'shards_required' => 'integer',
        ];
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(Blueprint::class);
    }
}
