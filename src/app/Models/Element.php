<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Element extends Model
{
    protected $fillable = ['name'];

    public function blueprintsByElementalAffinity(): HasMany
    {
        return $this->hasMany(Blueprint::class, 'elemental_affinity_id');
    }

    public function blueprintsBySpiritAffinity(): HasMany
    {
        return $this->hasMany(Blueprint::class, 'spirit_affinity_id');
    }

    public function blueprintsByBuiltInElement(): HasMany
    {
        return $this->hasMany(Blueprint::class, 'built_in_element_id');
    }

    public function blueprintsByBuiltInSpirit(): HasMany
    {
        return $this->hasMany(Blueprint::class, 'built_in_spirit_id');
    }
}
