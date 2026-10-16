<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Spirit extends Model
{
    protected $fillable = ['name'];

    public function blueprints(): HasMany
    {
        return $this->hasMany(Blueprint::class, 'built_in_spirit_id');
    }
}
