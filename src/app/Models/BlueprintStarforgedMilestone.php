<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlueprintStarforgedMilestone extends Model
{
    protected $fillable = [
        'blueprint_id',
        'milestone_number',
        'name',
        'crafts_required',
    ];

    protected function casts(): array
    {
        return [
            'milestone_number' => 'integer',
            'crafts_required' => 'integer',
        ];
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(Blueprint::class);
    }
}
