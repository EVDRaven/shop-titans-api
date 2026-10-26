<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlueprintResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'category' => $this->whenLoaded('category', fn ($category) => self::catalogRef($category)),
            'unlock_prerequisite' => $this->unlock_prerequisite,
            'research_scrolls' => self::intOrNull($this->research_scrolls),
            'antique_tokens' => self::intOrNull($this->antique_tokens),
            'tier' => (int) $this->tier,
            'gold_value' => (int) $this->gold_value,
            'craft_time_seconds' => (int) $this->craft_time_seconds,
            'craft_time_formatted' => $this->craft_time_formatted,
            'value_per_second' => self::floatOrNull($this->value_per_second),
            'merchant_xp' => (int) $this->merchant_xp,
            'merchant_xp_per_second' => self::floatOrNull($this->merchant_xp_per_second),
            'worker_xp' => (int) $this->worker_xp,
            'fusion_xp' => (int) $this->fusion_xp,
            'favor' => (int) $this->favor,
            'airship_power' => (int) $this->airship_power,
            'stats' => [
                'atk' => self::intOrNull($this->atk),
                'def' => self::intOrNull($this->def),
                'hp' => self::intOrNull($this->hp),
                'eva' => self::intOrNull($this->eva),
                'crit' => self::intOrNull($this->crit),
                'mana_shield' => self::intOrNull($this->mana_shield),
            ],
            'energies' => [
                'discount' => (int) $this->discount_energy,
                'surcharge' => (int) $this->surcharge_energy,
                'suggest' => (int) $this->suggest_energy,
                'speed_up' => (int) $this->speed_up_energy,
            ],
            'available_as_antique_at' => $this->available_as_antique_at?->toIso8601String(),

            'workers' => $this->whenLoaded('blueprintWorkers', fn ($items) => $items->map(fn ($item) => [
                'worker' => $item->worker === null ? null : [
                    'id' => (int) $item->worker->id,
                    'worker_key' => $item->worker->worker_key,
                    'default_person_name' => $item->worker->default_person_name,
                ],
                'level_required' => (int) $item->level_required,
            ])->all()),

            'resources' => $this->whenLoaded('blueprintResources', fn ($items) => $items->map(fn ($item) => [
                'resource' => $item->resource === null ? null : [
                    'id' => (int) $item->resource->id,
                    'name' => $item->resource->name,
                    'tier' => self::intOrNull($item->resource->tier),
                ],
                'quantity' => (int) $item->quantity,
            ])->all()),

            'components' => $this->whenLoaded('blueprintComponentsAsParent', fn ($items) => $items->map(fn ($item) => [
                'component' => $item->component === null ? null : [
                    'id' => (int) $item->component->id,
                    'name' => $item->component->name,
                    'material_type' => $item->component->material_type,
                    'tier' => self::intOrNull($item->component->tier),
                    'gold_value' => self::intOrNull($item->component->gold_value),
                    'fuse_time_seconds' => self::intOrNull($item->component->fuse_time_seconds),
                    'worker_level_required' => self::intOrNull($item->component->worker_level_required),
                    'worker_xp' => self::intOrNull($item->component->worker_xp),
                ],
                'quality' => $item->quality === null ? null : [
                    'id' => (int) $item->quality->id,
                    'name' => $item->quality->name,
                    'rank' => (int) $item->quality->rank,
                ],
                'amount' => (int) $item->amount,
            ])->all()),

            'elemental_affinities' => $this->whenLoaded('elementalAffinities', fn ($items) => $items->map(fn ($item) => [
                'position' => (int) $item->position,
                'element' => $item->element === null ? null : [
                    'id' => (int) $item->element->id,
                    'name' => $item->element->name,
                ],
            ])->all()),

            'spirit_affinity' => $this->whenLoaded('spiritAffinity', fn ($affinity) => self::catalogRef($affinity)),

            'built_in_element' => $this->whenLoaded('builtInElement', fn ($element) => $element === null ? null : [
                'id' => (int) $element->id,
                'name' => $element->name,
                'type' => $element->type,
            ]),

            'built_in_spirit' => $this->whenLoaded('builtInSpirit', fn ($spirit) => self::catalogRef($spirit)),

            'crafting_upgrades' => $this->whenLoaded('craftingUpgrades', fn ($items) => $items->map(fn ($item) => [
                'upgrade_level' => (int) $item->upgrade_level,
                'name' => $item->name,
                'crafts_required' => (int) $item->crafts_required,
            ])->all()),

            'starforged_milestones' => $this->whenLoaded('starforgedMilestones', fn ($items) => $items->map(fn ($item) => [
                'milestone_number' => (int) $item->milestone_number,
                'name' => $item->name,
                'crafts_required' => (int) $item->crafts_required,
            ])->all()),

            'ascension_upgrades' => $this->whenLoaded('ascensionUpgrades', fn ($items) => $items->map(fn ($item) => [
                'upgrade_level' => (int) $item->upgrade_level,
                'name' => $item->name,
                'shards_required' => (int) $item->shards_required,
            ])->all()),

            'transcendence_upgrades' => $this->whenLoaded('transcendenceUpgrades', fn ($items) => $items->map(fn ($item) => [
                'upgrade_level' => (int) $item->upgrade_level,
                'name' => $item->name,
                'seals_required' => (int) $item->seals_required,
            ])->all()),
        ];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private static function catalogRef(mixed $model): ?array
    {
        if ($model === null) {
            return null;
        }

        return [
            'id' => (int) $model->id,
            'name' => $model->name,
        ];
    }

    private static function intOrNull(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    private static function floatOrNull(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
