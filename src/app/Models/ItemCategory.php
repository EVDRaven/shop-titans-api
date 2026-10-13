<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemCategory extends Model
{
    protected $fillable = ['name'];

    public function blueprints(): HasMany
    {
        return $this->hasMany(Blueprint::class, 'category_id');
    }
}
