<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Blueprint extends Model
{
    protected $fillable = [
        'name',
        'category_id',
        'unlock_prerequisite',
        'research_scrolls',
        'antique_tokens',
        'tier',
        'gold_value',
        'craft_time_seconds',
        'craft_time_formatted',
        'value_per_second',
        'merchant_xp',
        'merchant_xp_per_second',
        'worker_xp',
        'fusion_xp',
        'favor',
        'airship_power',
        'atk',
        'def',
        'hp',
        'eva',
        'crit',
        'mana_shield',
        'elemental_affinity_id',
        'spirit_affinity_id',
        'built_in_element_id',
        'built_in_spirit_id',
        'discount_energy',
        'surcharge_energy',
        'suggest_energy',
        'speed_up_energy',
        'available_as_antique_at',
    ];

    protected function casts(): array
    {
        return [
            'available_as_antique_at' => 'datetime',
            'tier' => 'integer',
            'gold_value' => 'integer',
            'craft_time_seconds' => 'integer',
            'value_per_second' => 'decimal:4',
            'merchant_xp_per_second' => 'decimal:4',
            'atk' => 'integer',
            'def' => 'integer',
            'hp' => 'integer',
            'eva' => 'integer',
            'crit' => 'integer',
            'mana_shield' => 'integer',
            'discount_energy' => 'integer',
            'surcharge_energy' => 'integer',
            'suggest_energy' => 'integer',
            'speed_up_energy' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function elementalAffinity(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'elemental_affinity_id');
    }

    public function spiritAffinity(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'spirit_affinity_id');
    }

    public function builtInElement(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'built_in_element_id');
    }

    public function builtInSpirit(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'built_in_spirit_id');
    }

    public function blueprintWorkers(): HasMany
    {
        return $this->hasMany(BlueprintWorker::class);
    }

    public function blueprintResources(): HasMany
    {
        return $this->hasMany(BlueprintResource::class);
    }

    public function blueprintComponentsAsParent(): HasMany
    {
        return $this->hasMany(BlueprintComponent::class, 'blueprint_id');
    }

    public function craftingUpgrades(): HasMany
    {
        return $this->hasMany(BlueprintCraftingUpgrade::class);
    }

    public function starforgedMilestones(): HasMany
    {
        return $this->hasMany(BlueprintStarforgedMilestone::class);
    }

    public function ascensionUpgrades(): HasMany
    {
        return $this->hasMany(BlueprintAscensionUpgrade::class);
    }

    public function transcendenceUpgrades(): HasMany
    {
        return $this->hasMany(BlueprintTranscendenceUpgrade::class);
    }
}
