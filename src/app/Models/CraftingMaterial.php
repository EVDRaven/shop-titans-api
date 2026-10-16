<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CraftingMaterial extends Model
{
    protected $fillable = [
        'source_key',
        'name',
        'material_type',
        'blueprint_id',
        'tier',
        'gold_value',
        'quest_area',
        'fuse_time_seconds',
        'worker_level_required',
        'worker_xp',
    ];

    protected function casts(): array
    {
        return [
            'tier' => 'integer',
            'gold_value' => 'integer',
            'fuse_time_seconds' => 'integer',
            'worker_level_required' => 'integer',
            'worker_xp' => 'integer',
        ];
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(Blueprint::class);
    }

    public function blueprintComponents(): HasMany
    {
        return $this->hasMany(BlueprintComponent::class, 'component_id');
    }
}
