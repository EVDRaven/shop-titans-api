<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlueprintElementalAffinity extends Model
{
    protected $fillable = [
        'blueprint_id',
        'element_id',
        'position',
        'raw_value',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(Blueprint::class);
    }

    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class);
    }
}
