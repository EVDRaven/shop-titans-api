<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpiritAffinity extends Model
{
    protected $fillable = ['name'];

    public function blueprints(): HasMany
    {
        return $this->hasMany(Blueprint::class, 'spirit_affinity_id');
    }
}
