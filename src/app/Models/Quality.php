<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quality extends Model
{
    protected $fillable = ['name', 'rank'];

    public function blueprintComponents(): HasMany
    {
        return $this->hasMany(BlueprintComponent::class);
    }
}
