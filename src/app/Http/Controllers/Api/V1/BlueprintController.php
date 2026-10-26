<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlueprintIndexRequest;
use App\Http\Requests\BlueprintRequest;
use App\Http\Requests\BlueprintShowRequest;
use App\Http\Resources\BlueprintResource;
use App\Models\Blueprint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class BlueprintController extends Controller
{
    public function index(BlueprintIndexRequest $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 25);
        $page = (int) $request->query('page', 1);
        $sort = (string) $request->query('sort', 'id');
        $includes = $request->includeList();

        $query = Blueprint::query();
        $this->applyRelations($query, $includes);
        $this->applyFilters($query, $this->requestedFilters($request));
        $this->applySort($query, $sort);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => BlueprintResource::collection($paginator->items())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    public function show(BlueprintShowRequest $request): JsonResponse
    {
        $query = Blueprint::query();
        $this->applyRelations($query, $request->includeList());

        $blueprint = $query->findOrFail((int) $request->validated('id'));

        return response()->json([
            'data' => (new BlueprintResource($blueprint))->resolve(),
        ]);
    }

    /**
     * Carga explícita de relaciones para evitar N+1. `category` se incluye siempre;
     * el resto depende de la whitelist de `include`.
     *
     * @param  list<string>  $includes
     */
    private function applyRelations(Builder $query, array $includes): void
    {
        $relations = ['category'];

        foreach ($includes as $include) {
            foreach (BlueprintRequest::EAGER_LOAD_MAP[$include] ?? [] as $relation) {
                $relations[] = $relation;
            }
        }

        $query->with(array_values(array_unique($relations)));

        if (in_array('elemental_affinities', $includes, true)) {
            $query->with([
                'elementalAffinities' => fn ($relation) => $relation->with('element')->orderBy('position'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function requestedFilters(BlueprintIndexRequest $request): array
    {
        $filters = $request->query('filter', []);

        return is_array($filters) ? $filters : [];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $field => $value) {
            $operators = is_array($value) ? $value : ['eq' => $value];

            foreach ($operators as $operator => $operand) {
                $this->applyFilter($query, (string) $field, (string) $operator, $operand);
            }
        }
    }

    private function applyFilter(Builder $query, string $field, string $operator, mixed $value): void
    {
        switch ($field) {
            case 'name':
            case 'unlock_prerequisite':
                $this->applyPartialMatch($query, $field, (string) $value);
                break;

            case 'category':
                $query->whereHas('category', fn ($relation) => $relation->where('name', $value));
                break;

            case 'category_id':
                $query->where('category_id', $value);
                break;

            case 'element':
                $query->whereHas('elementalAffinities.element', fn ($relation) => $relation->where('name', $value));
                break;

            case 'spirit_affinity':
                $query->whereHas('spiritAffinity', fn ($relation) => $relation->where('name', $value));
                break;

            case 'built_in_element':
                $query->whereHas('builtInElement', fn ($relation) => $relation->where('name', $value));
                break;

            case 'built_in_spirit':
                $query->whereHas('builtInSpirit', fn ($relation) => $relation->where('name', $value));
                break;

            case 'resource':
                $query->whereHas('blueprintResources.resource', fn ($relation) => $relation->where('name', $value));
                break;

            case 'component':
                // Coincidencia exacta por nombre del material componente (H-5). Abarca
                // todos los `material_type` de `crafting_materials` (DP-D): no se acota
                // por tipo, de modo que nombres homónimos pueden producir coincidencias
                // ambiguas (documentado en el contrato OpenAPI).
                $query->whereHas('blueprintComponentsAsParent.component', fn ($relation) => $relation->where('name', $value));
                break;

            case 'worker':
                $query->whereHas('blueprintWorkers.worker', fn ($relation) => $relation->where('worker_key', $value));
                break;

            case 'available_as_antique':
                $this->applyAntiqueFilter($query, $value);
                break;

            default:
                $this->applyNumericFilter($query, $field, $operator, $value);
        }
    }

    /**
     * Búsqueda parcial sin distinguir mayúsculas. Se delega en `whereLike`
     * (H-1) en lugar de `whereRaw`: el nombre de la columna lo cita el propio
     * grammar y la insensibilidad a mayúsculas la resuelve el driver
     * (`ilike` en PostgreSQL, `like` en SQLite), sin concatenar SQL crudo.
     */
    private function applyPartialMatch(Builder $query, string $column, string $value): void
    {
        $query->whereLike($column, '%'.$value.'%', caseSensitive: false);
    }

    /**
     * Valores admitidos (validados en `BlueprintIndexRequest`): `true`/`1` →
     * disponibles como antigüedad (`available_as_antique_at` no nulo);
     * `false`/`0` → no disponibles (columna nula).
     */
    private function applyAntiqueFilter(Builder $query, mixed $value): void
    {
        $availableAsAntique = in_array((string) $value, ['true', '1'], true);

        if ($availableAsAntique) {
            $query->whereNotNull('available_as_antique_at');
        } else {
            $query->whereNull('available_as_antique_at');
        }
    }

    private function applyNumericFilter(Builder $query, string $field, string $operator, mixed $value): void
    {
        $sqlOperator = match ($operator) {
            'gt' => '>',
            'gte' => '>=',
            'lt' => '<',
            'lte' => '<=',
            default => '=',
        };

        $query->where($field, $sqlOperator, $value);
    }

    /**
     * Ordena por la columna solicitada. Se añade un desempate determinista por
     * `id` cuando la columna principal no es única (H-2), de modo que la
     * paginación no produzca duplicados ni omisiones entre páginas.
     */
    private function applySort(Builder $query, string $sort): void
    {
        $descending = str_starts_with($sort, '-');
        $column = $descending ? substr($sort, 1) : $sort;

        $query->orderBy($column, $descending ? 'desc' : 'asc');

        if ($column !== 'id') {
            $query->orderBy('id');
        }
    }
}
