<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlueprintCraftingUpgrade extends Model
{
    protected $fillable = [
        'blueprint_id',
        'upgrade_level',
        'name',
        'crafts_required',
    ];

    protected function casts(): array
    {
        return [
            'upgrade_level' => 'integer',
            'crafts_required' => 'integer',
        ];
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(Blueprint::class);
    }
}
