<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resource extends Model
{
    protected $fillable = [
        'name',
        'column_letter_in_excel',
        'tier',
    ];

    public function blueprintResources(): HasMany
    {
        return $this->hasMany(BlueprintResource::class);
    }
}
