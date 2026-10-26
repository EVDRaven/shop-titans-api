<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Rule;

class BlueprintIndexRequest extends BlueprintRequest
{
    /**
     * Campos de ordenamiento permitidos por el contrato.
     *
     * @var list<string>
     */
    public const SORTABLE = [
        'id',
        'name',
        'tier',
        'gold_value',
        'craft_time_seconds',
        'merchant_xp',
        'airship_power',
    ];

    /**
     * Filtros permitidos y su familia de operadores:
     * `partial` (texto contiene), `exact` (igualdad) y `numeric` (igualdad + rangos).
     *
     * @var array<string, string>
     */
    public const FILTERS = [
        'name' => 'partial',
        'unlock_prerequisite' => 'partial',
        'category' => 'exact',
        'category_id' => 'exact',
        'element' => 'exact',
        'spirit_affinity' => 'exact',
        'built_in_element' => 'exact',
        'built_in_spirit' => 'exact',
        'resource' => 'exact',
        'component' => 'exact',
        'worker' => 'exact',
        'available_as_antique' => 'exact',
        'tier' => 'numeric',
        'gold_value' => 'numeric',
        'craft_time_seconds' => 'numeric',
        'merchant_xp' => 'numeric',
        'airship_power' => 'numeric',
    ];

    /**
     * @var list<string>
     */
    public const NUMERIC_OPERATORS = ['eq', 'gt', 'gte', 'lt', 'lte'];

    /**
     * @var list<string>
     */
    public const EXACT_OPERATORS = ['eq'];

    /**
     * Valores admitidos para `filter[available_as_antique]` y su equivalencia:
     * `true`/`1` (verdadero) y `false`/`0` (falso).
     *
     * @var list<string>
     */
    public const ANTIQUE_VALUES = ['true', 'false', '1', '0'];

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string', Rule::in($this->sortableValues())],
            'include' => ['sometimes', 'string'],
            'filter' => ['sometimes', 'array'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateInclude($validator);
                $this->validateFilters($validator);
            },
        ];
    }

    /**
     * @return list<string>
     */
    private function sortableValues(): array
    {
        return array_merge(
            self::SORTABLE,
            array_map(static fn (string $field): string => '-'.$field, self::SORTABLE),
        );
    }

    private function validateFilters(Validator $validator): void
    {
        $filters = $this->query('filter');

        if ($filters === null || ! is_array($filters)) {
            return;
        }

        foreach ($filters as $field => $value) {
            if (! array_key_exists($field, self::FILTERS)) {
                $validator->errors()->add(
                    "filter[{$field}]",
                    "El filtro '{$field}' no está permitido."
                );

                continue;
            }

            $operators = is_array($value) ? $value : ['eq' => $value];

            foreach ($operators as $operator => $operand) {
                $operator = (string) $operator;

                if (! in_array($operator, $this->allowedOperators($field), true)) {
                    $validator->errors()->add(
                        "filter[{$field}][{$operator}]",
                        "El operador '{$operator}' no está permitido para '{$field}'."
                    );

                    continue;
                }

                $inner = ValidatorFacade::make(
                    ['value' => $operand],
                    ['value' => $this->valueRules($field)],
                );

                foreach ($inner->errors()->get('value') as $message) {
                    $validator->errors()->add("filter[{$field}][{$operator}]", $message);
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private function allowedOperators(string $field): array
    {
        return self::FILTERS[$field] === 'numeric'
            ? self::NUMERIC_OPERATORS
            : self::EXACT_OPERATORS;
    }

    /**
     * @return list<string>
     */
    private function valueRules(string $field): array
    {
        return match ($field) {
            'available_as_antique' => [Rule::in(self::ANTIQUE_VALUES)],
            'category_id' => ['integer', 'min:1'],
            'tier' => ['integer', 'min:1'],
            'gold_value', 'craft_time_seconds', 'merchant_xp', 'airship_power' => ['integer', 'min:0'],
            default => ['string'],
        };
    }
}
