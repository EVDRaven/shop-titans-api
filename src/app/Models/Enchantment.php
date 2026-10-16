<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enchantment extends Model
{
    public const TYPE_ELEMENT = 'Element';

    public const TYPE_SPIRIT = 'Spirit';

    protected $fillable = ['name', 'type'];

    public function blueprintsByBuiltInElement(): HasMany
    {
        return $this->hasMany(Blueprint::class, 'built_in_element_id');
    }
}
