<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerLevel extends Model
{
    protected $fillable = [
        'level',
        'xp_needed',
        'crafting_speed_bonus',
        'crafting_speed_bonus_t15plus',
    ];
}
