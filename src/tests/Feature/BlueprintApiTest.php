<?php

namespace Tests\Feature;

use App\Models\Blueprint;
use App\Models\BlueprintComponent;
use App\Models\BlueprintElementalAffinity;
use App\Models\BlueprintResource;
use App\Models\BlueprintWorker;
use App\Models\CraftingMaterial;
use App\Models\Element;
use App\Models\Enchantment;
use App\Models\ItemCategory;
use App\Models\Quality;
use App\Models\Resource;
use App\Models\Spirit;
use App\Models\SpiritAffinity;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BlueprintApiTest extends TestCase
{
    use RefreshDatabase;

    private ItemCategory $swordCategory;

    private Worker $blacksmith;

    private Resource $iron;

    protected function setUp(): void
    {
        parent::setUp();

        $this->swordCategory = ItemCategory::create(['name' => 'Sword']);
        $this->blacksmith = Worker::create([
            'worker_key' => 'Blacksmith',
            'default_person_name' => 'Blacksmith',
        ]);
        $this->iron = Resource::create([
            'name' => 'Iron',
            'column_letter_in_excel' => 'Y',
            'tier' => 1,
        ]);
    }

    public function test_index_returns_paginated_payload_with_default_category(): void
    {
        $this->createBlueprint();
        $this->createBlueprint(['name' => 'Iron Sword', 'tier' => 2]);

        $response = $this->getJson('/api/v1/blueprints');

        $response->assertOk()->assertJsonStructure([
            'data' => [[
                'id', 'name', 'category' => ['id', 'name'], 'unlock_prerequisite',
                'research_scrolls', 'antique_tokens', 'tier', 'gold_value',
                'craft_time_seconds', 'craft_time_formatted', 'value_per_second',
                'merchant_xp', 'merchant_xp_per_second', 'worker_xp', 'fusion_xp',
                'favor', 'airship_power',
                'stats' => ['atk', 'def', 'hp', 'eva', 'crit', 'mana_shield'],
                'energies' => ['discount', 'surcharge', 'suggest', 'speed_up'],
            ]],
            'meta' => ['current_page', 'per_page', 'total', 'last_page', 'from', 'to'],
            'links' => ['first', 'last', 'prev', 'next'],
        ]);

        $this->assertSame(2, $response->json('meta.total'));
        $this->assertSame(25, $response->json('meta.per_page'));
        $this->assertSame(1, $response->json('meta.current_page'));

        $first = $response->json('data.0');
        $this->assertSame(1, $first['tier']);
        $this->assertIsInt($first['tier']);
        $this->assertIsFloat($first['value_per_second']);
        $this->assertSame(1.8, $first['value_per_second']);
        $this->assertSame('Sword', $first['category']['name']);
    }

    public function test_index_omits_relations_not_requested_and_internal_fields(): void
    {
        $this->createBlueprint();

        $first = $this->getJson('/api/v1/blueprints')->json('data.0');

        $this->assertArrayHasKey('category', $first);
        $this->assertArrayNotHasKey('workers', $first);
        $this->assertArrayNotHasKey('resources', $first);
        $this->assertArrayNotHasKey('components', $first);
        $this->assertArrayNotHasKey('elemental_affinities', $first);
        $this->assertArrayNotHasKey('spirit_affinity', $first);
        $this->assertArrayNotHasKey('raw_value', $first);
        $this->assertArrayNotHasKey('created_at', $first);
        $this->assertArrayNotHasKey('updated_at', $first);
    }

    public function test_index_paginates_with_per_page_and_page(): void
    {
        foreach (range(1, 5) as $tier) {
            $this->createBlueprint(['name' => "Sword {$tier}", 'tier' => $tier]);
        }

        $response = $this->getJson('/api/v1/blueprints?per_page=2&page=2');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $this->assertSame(2, $response->json('meta.current_page'));
        $this->assertSame(2, $response->json('meta.per_page'));
        $this->assertSame(5, $response->json('meta.total'));
        $this->assertSame(3, $response->json('meta.last_page'));
    }

    public function test_index_rejects_invalid_pagination(): void
    {
        $this->getJson('/api/v1/blueprints?per_page=101')->assertStatus(422);
        $this->getJson('/api/v1/blueprints?per_page=0')->assertStatus(422);
        $this->getJson('/api/v1/blueprints?page=0')->assertStatus(422);
    }

    public function test_index_rejects_disallowed_sort_and_include(): void
    {
        $this->getJson('/api/v1/blueprints?sort=category')->assertStatus(422);
        $this->getJson('/api/v1/blueprints?include=unknown')->assertStatus(422);

        $this->getJson('/api/v1/blueprints?sort=-gold_value')->assertOk();
        $this->getJson('/api/v1/blueprints?include=workers,components')->assertOk();
    }

    public function test_include_rejects_nested_or_array_structures(): void
    {
        $this->createBlueprint();

        // Estructura anidada (array dentro de array): no debe provocar 500.
        $this->getJson('/api/v1/blueprints?include[workers][0]=workers')->assertStatus(422);
        $this->getJson('/api/v1/blueprints/1?include[workers][0]=workers')->assertStatus(422);

        // Array plano: fuera del contrato (`include` es una cadena CSV).
        $this->getJson('/api/v1/blueprints?include[]=workers')->assertStatus(422);
    }

    public function test_index_rejects_disallowed_filter_field_and_operator(): void
    {
        $this->getJson('/api/v1/blueprints?filter[unknown]=1')->assertStatus(422);
        $this->getJson('/api/v1/blueprints?filter[tier][foo]=1')->assertStatus(422);
        $this->getJson('/api/v1/blueprints?filter[name][gt]=x')->assertStatus(422);
        $this->getJson('/api/v1/blueprints?filter[tier][gte]=abc')->assertStatus(422);
    }

    public function test_index_applies_numeric_operators_and_eq_default(): void
    {
        foreach ([1, 3, 5] as $tier) {
            $this->createBlueprint(['name' => "Sword T{$tier}", 'tier' => $tier]);
        }

        $this->assertSame(2, $this->getJson('/api/v1/blueprints?filter[tier][gte]=3')->json('meta.total'));
        $this->assertSame(2, $this->getJson('/api/v1/blueprints?filter[tier][gt]=1')->json('meta.total'));
        $this->assertSame(1, $this->getJson('/api/v1/blueprints?filter[tier][lt]=3')->json('meta.total'));
        $this->assertSame(1, $this->getJson('/api/v1/blueprints?filter[tier][lte]=1')->json('meta.total'));
        $this->assertSame(1, $this->getJson('/api/v1/blueprints?filter[tier]=3')->json('meta.total'));
        $this->assertSame(1, $this->getJson('/api/v1/blueprints?filter[tier][eq]=3')->json('meta.total'));
    }

    public function test_index_partial_search_is_case_insensitive(): void
    {
        $this->createBlueprint();
        $this->createBlueprint(['name' => 'Iron Hammer', 'tier' => 2]);

        $this->assertSame(1, $this->getJson('/api/v1/blueprints?filter[name]=SQUIRE')->json('meta.total'));
        $this->assertSame(2, $this->getJson('/api/v1/blueprints?filter[name]=r')->json('meta.total'));
    }

    public function test_index_combines_filters(): void
    {
        $axe = ItemCategory::create(['name' => 'Axe']);
        $this->createBlueprint(['name' => 'Squire Sword', 'tier' => 1, 'gold_value' => 27]);
        $this->createBlueprint(['name' => 'Battle Axe', 'tier' => 5, 'gold_value' => 900, 'category_id' => $axe->id]);

        $query = http_build_query(['filter' => [
            'category' => 'Axe',
            'tier' => ['gte' => 3],
            'gold_value' => ['lte' => 1000],
        ]]);

        $response = $this->getJson('/api/v1/blueprints?'.$query);

        $response->assertOk();
        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame('Battle Axe', $response->json('data.0.name'));
    }

    public function test_index_filters_by_relations(): void
    {
        $blueprint = $this->createBlueprint();

        BlueprintWorker::create([
            'blueprint_id' => $blueprint->id,
            'worker_id' => $this->blacksmith->id,
            'level_required' => 1,
        ]);
        BlueprintResource::create([
            'blueprint_id' => $blueprint->id,
            'resource_id' => $this->iron->id,
            'quantity' => 2,
        ]);
        $dark = Element::create(['name' => 'Dark']);
        BlueprintElementalAffinity::create([
            'blueprint_id' => $blueprint->id,
            'element_id' => $dark->id,
            'position' => 1,
        ]);

        $blueprint->update([
            'spirit_affinity_id' => SpiritAffinity::create(['name' => 'Wolf Ferocity'])->id,
            'built_in_element_id' => Enchantment::create([
                'name' => 'Fire Element',
                'type' => Enchantment::TYPE_ELEMENT,
            ])->id,
            'built_in_spirit_id' => Spirit::create(['name' => "Mundra's Spirit"])->id,
        ]);

        $this->assertSame(1, $this->totalFor(['worker' => 'Blacksmith']));
        $this->assertSame(1, $this->totalFor(['resource' => 'Iron']));
        $this->assertSame(1, $this->totalFor(['element' => 'Dark']));
        $this->assertSame(1, $this->totalFor(['spirit_affinity' => 'Wolf Ferocity']));
        $this->assertSame(1, $this->totalFor(['built_in_element' => 'Fire Element']));
        $this->assertSame(1, $this->totalFor(['built_in_spirit' => "Mundra's Spirit"]));
        $this->assertSame(1, $this->totalFor(['category' => 'Sword']));
        $this->assertSame(1, $this->totalFor(['category_id' => $this->swordCategory->id]));
        $this->assertSame(0, $this->totalFor(['worker' => 'Wizard']));
    }

    public function test_index_filters_by_component_exact_name(): void
    {
        $ruby = $this->createComponent('Ruby');
        $quality = Quality::create(['name' => 'Normal', 'rank' => 1]);

        $matching = $this->createBlueprint(['name' => 'Ruby Sword']);
        BlueprintComponent::create([
            'blueprint_id' => $matching->id,
            'component_id' => $ruby->id,
            'quality_id' => $quality->id,
            'amount' => 4,
        ]);

        // Sin componentes y con nombre homónimo al del material: no debe coincidir.
        $this->createBlueprint(['name' => 'Ruby', 'tier' => 2]);
        $this->createBlueprint(['name' => 'Plain Sword', 'tier' => 3]);

        // Coincidencia exacta por nombre de material componente.
        $this->assertSame(1, $this->totalFor(['component' => 'Ruby']));
        // Equivalencia de la sintaxis explícita `filter[component][eq]` (D-6).
        $this->assertSame(1, $this->totalFor(['component' => ['eq' => 'Ruby']]));
        // Ausencia de coincidencias.
        $this->assertSame(0, $this->totalFor(['component' => 'Emerald']));
        // Igualdad sensible a mayúsculas/minúsculas (según la base de datos).
        $this->assertSame(0, $this->totalFor(['component' => 'ruby']));
    }

    public function test_index_component_filter_does_not_duplicate_blueprints(): void
    {
        $ruby = $this->createComponent('Ruby');
        $normal = Quality::create(['name' => 'Normal', 'rank' => 1]);
        $superior = Quality::create(['name' => 'Superior', 'rank' => 2]);

        $blueprint = $this->createBlueprint();

        // Dos filas del mismo componente con distinta calidad (permitido por la UNIQUE
        // de `blueprint_components`); el blueprint debe aparecer una sola vez.
        BlueprintComponent::create([
            'blueprint_id' => $blueprint->id,
            'component_id' => $ruby->id,
            'quality_id' => $normal->id,
            'amount' => 2,
        ]);
        BlueprintComponent::create([
            'blueprint_id' => $blueprint->id,
            'component_id' => $ruby->id,
            'quality_id' => $superior->id,
            'amount' => 1,
        ]);

        $response = $this->getJson('/api/v1/blueprints?'.http_build_query([
            'filter' => ['component' => 'Ruby'],
        ]));

        $response->assertOk();
        $this->assertSame(1, $response->json('meta.total'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_combines_component_filter_with_other_filters(): void
    {
        $ruby = $this->createComponent('Ruby');

        foreach ([1, 5] as $tier) {
            $blueprint = $this->createBlueprint(['name' => "Sword T{$tier}", 'tier' => $tier]);
            BlueprintComponent::create([
                'blueprint_id' => $blueprint->id,
                'component_id' => $ruby->id,
                'amount' => 1,
            ]);
        }

        $this->assertSame(2, $this->totalFor(['component' => 'Ruby']));

        $combined = $this->getJson('/api/v1/blueprints?'.http_build_query([
            'filter' => [
                'component' => 'Ruby',
                'tier' => ['gte' => 3],
            ],
        ]));

        $combined->assertOk();
        $this->assertSame(1, $combined->json('meta.total'));
        $this->assertSame('Sword T5', $combined->json('data.0.name'));
    }

    public function test_index_rejects_disallowed_operators_for_component(): void
    {
        $this->getJson('/api/v1/blueprints?filter[component][gt]=Ruby')->assertStatus(422);
        $this->getJson('/api/v1/blueprints?filter[component][lte]=Ruby')->assertStatus(422);
        $this->getJson('/api/v1/blueprints?filter[component][eq]=Ruby')->assertOk();
    }

    public function test_index_filters_by_available_as_antique(): void
    {
        $this->createBlueprint(['name' => 'Plain Sword']);
        $this->createBlueprint(['name' => 'Antique A', 'available_as_antique_at' => now()]);
        $this->createBlueprint(['name' => 'Antique B', 'available_as_antique_at' => now()->subDay()]);

        $this->assertSame(3, $this->getJson('/api/v1/blueprints')->json('meta.total'));

        $this->assertSame(2, $this->totalFor(['available_as_antique' => 'true']));
        $this->assertSame(2, $this->totalFor(['available_as_antique' => '1']));
        $this->assertSame(1, $this->totalFor(['available_as_antique' => 'false']));
        $this->assertSame(1, $this->totalFor(['available_as_antique' => '0']));

        $antiqueNames = $this->getJson('/api/v1/blueprints?filter[available_as_antique]=true')
            ->json('data.*.name');
        sort($antiqueNames);
        $this->assertSame(['Antique A', 'Antique B'], $antiqueNames);

        $notAntiqueNames = $this->getJson('/api/v1/blueprints?filter[available_as_antique]=false')
            ->json('data.*.name');
        $this->assertSame(['Plain Sword'], $notAntiqueNames);
    }

    public function test_index_rejects_invalid_antique_values(): void
    {
        $this->createBlueprint();

        $this->getJson('/api/v1/blueprints?filter[available_as_antique]=yes')->assertStatus(422);
        $this->getJson('/api/v1/blueprints?filter[available_as_antique]=2')->assertStatus(422);
        $this->getJson('/api/v1/blueprints?filter[available_as_antique]=')->assertStatus(422);
        $this->getJson('/api/v1/blueprints?filter[available_as_antique][gt]=true')->assertStatus(422);
    }

    public function test_index_sorts_by_allowed_fields(): void
    {
        $this->createBlueprint(['name' => 'B Sword', 'gold_value' => 10]);
        $this->createBlueprint(['name' => 'A Sword', 'gold_value' => 50]);

        $this->assertSame(50, $this->getJson('/api/v1/blueprints?sort=-gold_value')->json('data.0.gold_value'));
        $this->assertSame('A Sword', $this->getJson('/api/v1/blueprints?sort=name')->json('data.0.name'));
    }

    public function test_index_sort_adds_deterministic_secondary_key(): void
    {
        $this->createBlueprint();
        $this->createBlueprint(['name' => 'Iron Sword', 'tier' => 2]);

        // H-2: la columna no única se acompaña de un desempate por `id`.
        $byName = $this->orderClauseFor('/api/v1/blueprints?sort=name');

        $this->assertStringContainsString('order by "name" asc', $byName);
        $this->assertStringContainsString('"id" asc', $byName);

        // Cuando `id` ya es la columna primaria no se duplica el desempate.
        $byId = $this->orderClauseFor('/api/v1/blueprints?sort=id');

        $this->assertStringNotContainsString('"id" asc, "id"', $byId);
    }

    public function test_show_returns_blueprint_with_requested_includes(): void
    {
        $blueprint = $this->createBlueprint();

        BlueprintWorker::create([
            'blueprint_id' => $blueprint->id,
            'worker_id' => $this->blacksmith->id,
            'level_required' => 3,
        ]);
        BlueprintResource::create([
            'blueprint_id' => $blueprint->id,
            'resource_id' => $this->iron->id,
            'quantity' => 2,
        ]);
        $quality = Quality::create(['name' => 'Normal', 'rank' => 1]);
        $material = CraftingMaterial::create([
            'source_key' => 'quest_component:Ruby',
            'name' => 'Ruby',
            'material_type' => 'quest_component',
        ]);
        BlueprintComponent::create([
            'blueprint_id' => $blueprint->id,
            'component_id' => $material->id,
            'quality_id' => $quality->id,
            'amount' => 4,
        ]);
        $dark = Element::create(['name' => 'Dark']);
        BlueprintElementalAffinity::create([
            'blueprint_id' => $blueprint->id,
            'element_id' => $dark->id,
            'position' => 1,
        ]);

        $response = $this->getJson(
            "/api/v1/blueprints/{$blueprint->id}?include=workers,resources,components,elemental_affinities"
        );

        $response->assertOk()->assertJsonStructure(['data' => ['id', 'name', 'category']]);

        $data = $response->json('data');
        $this->assertSame($blueprint->id, $data['id']);
        $this->assertSame(3, $data['workers'][0]['level_required']);
        $this->assertSame('Blacksmith', $data['workers'][0]['worker']['worker_key']);
        $this->assertArrayNotHasKey('id', $data['workers'][0]);
        $this->assertSame(2, $data['resources'][0]['quantity']);
        $this->assertSame('Iron', $data['resources'][0]['resource']['name']);
        $this->assertSame(4, $data['components'][0]['amount']);
        $this->assertSame('quest_component', $data['components'][0]['component']['material_type']);
        $this->assertSame('Normal', $data['components'][0]['quality']['name']);
        $this->assertArrayNotHasKey('id', $data['components'][0]);
        $this->assertSame(1, $data['elemental_affinities'][0]['position']);
        $this->assertSame('Dark', $data['elemental_affinities'][0]['element']['name']);
        $this->assertArrayNotHasKey('raw_value', $data['elemental_affinities'][0]);
    }

    public function test_show_returns_404_when_missing(): void
    {
        $this->getJson('/api/v1/blueprints/999999')
            ->assertStatus(404)
            ->assertJsonStructure(['message']);
    }

    public function test_show_rejects_invalid_id_and_include(): void
    {
        $this->getJson('/api/v1/blueprints/abc')->assertStatus(422);
        $this->getJson('/api/v1/blueprints/0')->assertStatus(422);

        $this->createBlueprint();
        $this->getJson('/api/v1/blueprints/1?include=unknown')->assertStatus(422);
    }

    public function test_index_avoids_n_plus_one_queries(): void
    {
        $url = '/api/v1/blueprints?include=workers,resources,components';

        $blueprint = $this->createBlueprint();
        $this->attachRelations($blueprint, 1);

        $queriesForOne = $this->countQueries($url);

        foreach (range(2, 6) as $index) {
            $extra = $this->createBlueprint(['name' => "Sword {$index}", 'tier' => $index]);
            $this->attachRelations($extra, $index);
        }

        $queriesForMany = $this->countQueries($url);

        $this->assertSame($queriesForOne, $queriesForMany);
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function totalFor(array $filter): int
    {
        return $this->getJson('/api/v1/blueprints?'.http_build_query(['filter' => $filter]))
            ->assertOk()
            ->json('meta.total');
    }

    private function orderClauseFor(string $url): string
    {
        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();

        $this->getJson($url)->assertOk();

        $clause = (string) collect(DB::connection()->getQueryLog())
            ->pluck('query')
            ->first(fn (string $sql): bool => str_contains($sql, 'order by'));

        DB::connection()->disableQueryLog();
        DB::connection()->flushQueryLog();

        return $clause;
    }

    private function countQueries(string $url): int
    {
        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();

        $this->getJson($url)->assertOk();

        $count = count(DB::connection()->getQueryLog());

        DB::connection()->disableQueryLog();
        DB::connection()->flushQueryLog();

        return $count;
    }

    private function attachRelations(Blueprint $blueprint, int $index): void
    {
        BlueprintWorker::create([
            'blueprint_id' => $blueprint->id,
            'worker_id' => $this->blacksmith->id,
            'level_required' => 1,
        ]);
        BlueprintResource::create([
            'blueprint_id' => $blueprint->id,
            'resource_id' => $this->iron->id,
            'quantity' => 1,
        ]);
        $material = CraftingMaterial::create([
            'source_key' => "quest_component:Ruby{$index}",
            'name' => "Ruby {$index}",
            'material_type' => 'quest_component',
        ]);
        BlueprintComponent::create([
            'blueprint_id' => $blueprint->id,
            'component_id' => $material->id,
            'amount' => 1,
        ]);
    }

    private function createComponent(string $name): CraftingMaterial
    {
        return CraftingMaterial::create([
            'source_key' => "quest_component:{$name}",
            'name' => $name,
            'material_type' => 'quest_component',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createBlueprint(array $attributes = []): Blueprint
    {
        return Blueprint::create(array_merge([
            'name' => 'Squire Sword',
            'category_id' => $this->swordCategory->id,
            'tier' => 1,
            'gold_value' => 27,
            'craft_time_seconds' => 15,
            'craft_time_formatted' => '15s',
            'value_per_second' => 1.8,
            'merchant_xp' => 27,
            'merchant_xp_per_second' => 1.8,
            'worker_xp' => 2,
            'fusion_xp' => 0,
            'favor' => 1,
            'airship_power' => 5,
        ], $attributes));
    }
}
