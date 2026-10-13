<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlueprintComponent extends Model
{
    protected $fillable = [
        'blueprint_id',
        'component_id',
        'quality_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(Blueprint::class, 'blueprint_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Blueprint::class, 'component_id');
    }

    public function quality(): BelongsTo
    {
        return $this->belongsTo(Quality::class);
    }
}
