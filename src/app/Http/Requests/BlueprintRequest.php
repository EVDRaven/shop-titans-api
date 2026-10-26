<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

abstract class BlueprintRequest extends FormRequest
{
    /**
     * Whitelist de relaciones incluidas vía el parámetro `include` (D-2).
     *
     * @var list<string>
     */
    public const INCLUDES = [
        'workers',
        'resources',
        'components',
        'elemental_affinities',
        'spirit_affinity',
        'built_in_element',
        'built_in_spirit',
        'crafting_upgrades',
        'starforged_milestones',
        'ascension_upgrades',
        'transcendence_upgrades',
    ];

    /**
     * Mapa relación lógica -> ruta(s) de eager loading sobre `Blueprint`.
     * `elemental_affinities` se resuelve aparte porque requiere orden por `position`.
     *
     * @var array<string, list<string>>
     */
    public const EAGER_LOAD_MAP = [
        'workers' => ['blueprintWorkers.worker'],
        'resources' => ['blueprintResources.resource'],
        'components' => ['blueprintComponentsAsParent.component', 'blueprintComponentsAsParent.quality'],
        'spirit_affinity' => ['spiritAffinity'],
        'built_in_element' => ['builtInElement'],
        'built_in_spirit' => ['builtInSpirit'],
        'crafting_upgrades' => ['craftingUpgrades'],
        'starforged_milestones' => ['starforgedMilestones'],
        'ascension_upgrades' => ['ascensionUpgrades'],
        'transcendence_upgrades' => ['transcendenceUpgrades'],
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Relaciones solicitadas, normalizadas desde la lista separada por comas
     * (`style: form`, `explode: false`).
     *
     * @return list<string>
     */
    public function includeList(): array
    {
        $raw = $this->query('include');

        if ($raw === null || $raw === '') {
            return [];
        }

        $values = is_array($raw) ? $raw : explode(',', (string) $raw);

        // Sólo se consideran elementos escalares (cadena). Una estructura anidada
        // (array dentro de array) queda fuera del contrato —`include` es una cadena
        // CSV—; se ignora aquí para no provocar un TypeError, y la regla `string`
        // de cada Form Request la rechaza con `422`.
        $normalized = [];

        foreach ($values as $value) {
            if (! is_string($value)) {
                continue;
            }

            $value = trim($value);

            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Valida que cada relación solicitada pertenezca a la whitelist aprobada.
     */
    protected function validateInclude(Validator $validator): void
    {
        foreach ($this->includeList() as $include) {
            if (! in_array($include, self::INCLUDES, true)) {
                $validator->errors()->add(
                    'include',
                    "La relación '{$include}' no está permitida en include."
                );
            }
        }
    }
}
