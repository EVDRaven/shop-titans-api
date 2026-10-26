# Reporte Técnico — Auditoría de la API de Blueprints

**Fecha:** 2026-10-16
**Autor:** Editor IA
**Estado:** PROPUESTA (AUDITORÍA COMPLETADA — IMPLEMENTACIÓN PENDIENTE DE APROBACIÓN)

## 1. Objetivo

Auditar el código existente del proyecto `shop-titans-api` para definir una API de Blueprints completa, reutilizable y preparada para alimentar herramientas futuras (timers de fabricación, calculadoras de eventos y buscadores avanzados).

Esta tarea es **exclusivamente de auditoría y documentación**: no implementa endpoints, no modifica la arquitectura y no toca PostgreSQL.

## 2. Alcance

**Incluye:**
- Inspección del estado real de la infraestructura API (rutas, arranque, middleware, autenticación, dependencias).
- Inventario de modelos, campos, relaciones, migraciones e índices realmente existentes.
- Propuesta (no implementada) de contrato REST, filtros, ordenamiento, paginación, JSON y OpenAPI.
- Evaluación del soporte para herramientas futuras, separando datos almacenados, derivados y reglas de negocio desconocidas.
- Identificación de riesgos de rendimiento e integridad.
- Plan de implementación por etapas.

**Excluye (prohibido en esta tarea):**
- Implementar controladores, rutas, API Resources, Form Requests o endpoints.
- Modificar modelos, migraciones, servicios o configuración.
- Ejecutar migraciones, seeders o importadores.
- Escribir en PostgreSQL.
- Añadir dependencias.
- Crear ADR o modificar el changelog.
- Hacer commit o push.

## 3. Estado inicial

- D-3 (elementos/afinidades AZ–BC) **EJECUTADA Y VALIDADA** en PostgreSQL (reporte `docs/reports/2026-10-16_ejecucion_d3_postgresql.md`).
- Último commit: `f0cb37c` "feat: modelar elementos y afinidades de blueprints" (rama `master`, sincronizada con `origin/master`).
- Sin cambios en archivos versionados; solo artefactos untracked en la raíz (`.xlsx`, `*.txt`, `*.diff`, `.zip`).
- `PROJECT_CONTEXT.md` declara **"API aún no implementada"** y `README.md` marca la API como ⏳. La auditoría confirma ambas afirmaciones.

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| `docs/reports/2026-10-16_auditoria_api_blueprints.md` | Creado | Reporte de la auditoría. |
| `AI_WORK_LOG.md` | Modificado | Registrar la tarea, hallazgos, estado y próximo paso. |

Ningún archivo de código, migración, modelo, seeder o configuración fue modificado.

## 5. Resumen ejecutivo

1. **No existe ninguna API.** No hay `routes/api.php`, no hay controladores (solo la clase base `Controller`), no hay API Resources, Form Requests ni middleware propios. El único punto de entrada HTTP es la ruta `/` de `routes/web.php`.
2. **No hay autenticación de API.** `config/auth.php` solo define el guard `web` (sesión). No hay Sanctum, Passport ni tokens. Cualquier endpoint de solo lectura (los datos son un catálogo público) no requeriría autenticación en el MVP.
3. **Los datos del dominio ya están completos y normalizados.** 9 catálogos + `blueprints` + 8 tablas de relación 1:N cubren prácticamente todas las columnas del Excel. El modelo de datos está listo para exponerse.
4. **Ya existe un gancho para JSON.** `bootstrap/app.php` configura `shouldRenderJsonWhen($request->is('api/*'))`, por lo que hay una preparación parcial para separar respuestas JSON de las web, pero `withRouting()` **no registra** rutas `api` ni prefijo.
5. **La mayoría del trabajo de exposición es "de lectura y presentación", no de modelado.** El principal esfuerzo será: registrar rutas versionadas, definir API Resources, aplicar `with()` para evitar N+1, y decidir qué campos derivados se exponen.
6. **Riesgo principal:** los `DECIMAL` (`value_per_second`, `merchant_xp_per_second`) se serializan como **string** por el cast `decimal:4`, lo que rompe la consistencia de tipos del JSON si no se trata explícitamente.
7. **Reglas de negocio desconocidas:** no hay datos (ni fórmulas) para calcular tiempos de fabricación reales con bonus de worker, ni reglas de eventos. La API podrá exponer los insumos, pero no los resultados finales de esas calculadoras hasta que las reglas se documenten.

## 6. Estado real de la infraestructura API

| Componente | Estado real | Evidencia |
|---|---|---|
| `routes/api.php` | **No existe** | `LS src/routes/` solo lista `console.php` y `web.php`. |
| `routes/web.php` | Existe; una sola ruta `GET /` → `view('welcome')` | `src/routes/web.php`. |
| Registro de rutas | `withRouting(web, commands, health:'/up')`; **sin** `api:` ni `apiPrefix` | `src/bootstrap/app.php#L9-L13`. |
| Prefijo `/api` | No configurado | `src/bootstrap/app.php`. |
| Render JSON | `shouldRenderJsonWhen(fn ($r) => $r->is('api/*'))` | `src/bootstrap/app.php#L17-L20`. |
| Controladores | Solo `App\Http\Controllers\Controller` (base abstracta) | `LS src/app/Http/Controllers/`. |
| API Resources (`app/Http/Resources`) | **No existen** | Ausente en `src/app/Http/`. |
| Form Requests (`app/Http/Requests`) | **No existen** | Ausente en `src/app/Http/`. |
| Middleware propio | **No existe**; `withMiddleware` vacío | `src/bootstrap/app.php#L14-L16`. |
| Autenticación API | **No existe**; solo guard `web` (session) | `src/config/auth.php#L40-L45`. |
| Sanctum / Passport | **No instalados** | `src/composer.json` (sin paquetes de auth API). |
| Versionado (`/api/v1`) | **No existe** | No hay rutas api. |
| Documentación OpenAPI | **No existe**; sin Scribe/Swagger | `src/composer.json` (sin dependencias de docs). |
| Query builder avanzado | No instalado (sin `spatie/laravel-query-builder`) | `src/composer.json`. |
| `AppServiceProvider` | Vacío (sin bindings, sin rate limiter, sin `JsonResource::withoutWrapping`) | `src/app/Providers/AppServiceProvider.php`. |
| Config CORS publicada | **No** (sin `config/cors.php`); se usaría el default del framework | Ausente en `src/config/`. |
| Tests de endpoints | **No existen** | `src/tests/Feature/` solo contiene tests de importación/rollback. |

**Conclusión:** la infraestructura API está en estado **cero (greenfield)**. Como efecto colateral positivo, la decisión de arquitectura (versionado, auth, presentación) está totalmente abierta y sin deuda técnica previa.

## 7. Inventario de modelos, campos y relaciones relevantes

### 7.1 Modelo núcleo `Blueprint` (`src/app/Models/Blueprint.php`)

**Campos expuestos candidatos (61 columnas en `blueprints`):**

| Grupo | Campos | Tipo real | Nullable | Notas |
|---|---|---|---|---|
| Identificación | `id`, `name` | bigint / string | No | `name` UNIQUE. |
| Clasificación | `category_id` → `category.name` | bigint FK | Sí | FK a `item_categories` `nullOnDelete`. |
| Desbloqueo | `unlock_prerequisite` | string | Sí | Valor LITERAL (workers, packs, offers, chests, content passes). **Sin FK.** |
| Costes de desbloqueo | `research_scrolls`, `antique_tokens` | unsignedInteger | Sí | `---` → null. |
| Economía/tiempo | `tier`, `gold_value`, `craft_time_seconds`, `craft_time_formatted`, `value_per_second` | int / string / decimal(14,4) | `craft_time_formatted`/`value_per_second` sí | Derivados redundantes (ver 7.3). |
| XP/bonus | `merchant_xp`, `merchant_xp_per_second`, `worker_xp`, `fusion_xp`, `favor`, `airship_power` | int / decimal | No (excepto `_per_second`) | |
| Estadísticas | `atk`, `def`, `hp`, `eva`, `crit`, `mana_shield` | integer | Sí | `---` → null. |
| Energías | `discount_energy`, `surcharge_energy`, `suggest_energy`, `speed_up_energy` | unsignedInteger | No (default 0) | `null` en origen → `0`. |
| Antique | `available_as_antique_at` | timestamp | Sí | Cast `datetime`. |
| Afinidades FK | `spirit_affinity_id`, `built_in_element_id`, `built_in_spirit_id` | bigint FK | Sí | BA/BB/BC (AZ es 1:N). |
| Auditoría | `created_at`, `updated_at` | timestamp | No | |

> **Nota:** `blueprints` **no** tiene columna `elemental_affinity_id` (fue eliminada en D-3; AZ vive en `blueprint_elemental_affinities`).

**Relaciones Eloquent reales de `Blueprint`:**

| Método | Tipo | Destino | Cardinalidad real |
|---|---|---|---|
| `category()` | BelongsTo | `ItemCategory` | 0..1 |
| `elementalAffinities()` | HasMany | `BlueprintElementalAffinity` | 0..2 (AZ) |
| `spiritAffinity()` | BelongsTo | `SpiritAffinity` | 0..1 (BA) |
| `builtInElement()` | BelongsTo | `Enchantment` | 0..1 (BB) |
| `builtInSpirit()` | BelongsTo | `Spirit` | 0..1 (BC) |
| `blueprintWorkers()` | HasMany | `BlueprintWorker` | 0..3 (Excel) |
| `blueprintResources()` | HasMany | `BlueprintResource` | 0..12 |
| `blueprintComponentsAsParent()` | HasMany | `BlueprintComponent` | 0..2 (Excel) |
| `craftingUpgrades()` | HasMany | `BlueprintCraftingUpgrade` | 0..5 |
| `starforgedMilestones()` | HasMany | `BlueprintStarforgedMilestone` | 0..5 |
| `ascensionUpgrades()` | HasMany | `BlueprintAscensionUpgrade` | 0..3 |
| `transcendenceUpgrades()` | HasMany | `BlueprintTranscendenceUpgrade` | 0..3 |

### 7.2 Catálogos y tablas 1:N

| Tabla / Modelo | Columnas | Relaciones | Constraint destacada |
|---|---|---|---|
| `item_categories` / `ItemCategory` | `id`, `name` | `blueprints()` HasMany | `name` UNIQUE |
| `workers` / `Worker` | `id`, `worker_key`, `default_person_name`, `unlock_level_required`, `building_prerequisite`, `gold_cost`, `gem_cost`, `blueprint_unlocks_raw` | `blueprintWorkers()` | `worker_key` UNIQUE |
| `worker_levels` / `WorkerLevel` | `id`, `level`, `xp_needed`, `crafting_speed_bonus`, `crafting_speed_bonus_t15plus` | **Ninguna** (catálogo global) | `level` UNIQUE |
| `resources` / `Resource` | `id`, `name`, `column_letter_in_excel`, `tier` | `blueprintResources()` | `name` y `column_letter_in_excel` UNIQUE |
| `qualities` / `Quality` | `id`, `name`, `rank` | `blueprintComponents()` | `name` y `rank` UNIQUE, `rank` NOT NULL |
| `elements` / `Element` | `id`, `name` | `blueprintElementalAffinities()` | `name` UNIQUE |
| `enchantments` / `Enchantment` | `id`, `name`, `type` | `blueprintsByBuiltInElement()` | `name` UNIQUE; CHECK `type IN ('Element','Spirit')` (solo pgsql) |
| `spirit_affinities` / `SpiritAffinity` | `id`, `name` | `blueprints()` | `name` UNIQUE |
| `spirits` / `Spirit` | `id`, `name` | `blueprints()` | `name` UNIQUE |
| `blueprint_workers` / `BlueprintWorker` | `id`, `blueprint_id`, `worker_id`, `level_required` | `blueprint()`, `worker()` | UNIQUE `(blueprint_id, worker_id)` |
| `blueprint_resources` / `BlueprintResource` | `id`, `blueprint_id`, `resource_id`, `quantity` | `blueprint()`, `resource()` | UNIQUE `(blueprint_id, resource_id)` |
| `blueprint_elemental_affinities` / `BlueprintElementalAffinity` | `id`, `blueprint_id`, `element_id`, `position`, `raw_value` | `blueprint()`, `element()` | UNIQUE `(blueprint_id, element_id)` y `(blueprint_id, position)`; CHECK `position IN (1,2)` (solo pgsql) |
| `blueprint_components` / `BlueprintComponent` | `id`, `blueprint_id`, `component_id`, `quality_id`, `amount` | `blueprint()`, `component()` → `CraftingMaterial`, `quality()` | UNIQUE `(blueprint_id, component_id, quality_id)` |
| `blueprint_crafting_upgrades` / `BlueprintCraftingUpgrade` | `id`, `blueprint_id`, `upgrade_level`, `name`, `crafts_required` | `blueprint()` | UNIQUE `(blueprint_id, upgrade_level)` |
| `blueprint_starforged_milestones` / `BlueprintStarforgedMilestone` | `id`, `blueprint_id`, `milestone_number`, `name`, `crafts_required` | `blueprint()` | UNIQUE `(blueprint_id, milestone_number)` |
| `blueprint_ascension_upgrades` / `BlueprintAscensionUpgrade` | `id`, `blueprint_id`, `upgrade_level`, `name`, `shards_required` | `blueprint()` | UNIQUE `(blueprint_id, upgrade_level)` |
| `blueprint_transcendence_upgrades` / `BlueprintTranscendenceUpgrade` | `id`, `blueprint_id`, `upgrade_level`, `name`, `seals_required` | `blueprint()` | UNIQUE `(blueprint_id, upgrade_level)` |
| `crafting_materials` / `CraftingMaterial` | `id`, `source_key`, `name`, `material_type`, `blueprint_id`, `tier`, `gold_value`, `quest_area`, `fuse_time_seconds`, `worker_level_required`, `worker_xp` | `blueprint()`, `blueprintComponents()` | `source_key` UNIQUE; CHECK tipo/blueprint_id y formato de `source_key` (solo pgsql); índice único parcial en `blueprint_id` |

### 7.3 Campos derivados, ausentes o ambiguos

**Derivados (redundantes, recalculables):**
- `craft_time_formatted` — formateo legible de `craft_time_seconds`.
- `value_per_second` — `gold_value / craft_time_seconds`.
- `merchant_xp_per_second` — `merchant_xp / craft_time_seconds`.

**Campos inexistentes / no modelados:**
- **`unlock_prerequisite` sin estructura.** Es texto libre; en la práctica contiene workers, packs, offers, chests, content passes, tutorial, collection. No hay tabla ni catálogo de prerequisites. Filtrable solo por coincidencia de texto.
- **Sin columna de imagen/icono** ni identificador externo (`external_id`, `game_id`). La identidad pública es el `name` y el `id` interno.
- **`enchantments.value`** (columna C de la hoja `Enchantments`) **no** fue modelada (documentado como pendiente en D-3).
- **No existe relación N:M** `blueprint_skills` ni mapeo Enchantments↔Blueprint más allá de `built_in_element_id`.

**Ambiguos / requieren validación:**
- **`elements` (`Gold`, `All`):** semántica pendiente (documentado en D-3).
- **`spirit_affinities` (BA) ↔ `enchantments`:** relación no establecida (solo 3 de 38 nombres coinciden con Enchantments); decisión de mapeo pendiente.
- **`worker_levels` sin vínculo con `workers`:** no se puede resolver el `crafting_speed_bonus` aplicable sin decidir la regla de negocio (¿el bonus es global por nivel de worker?).
- **`blueprint_components.component_id`** apunta a `crafting_materials`, que puede ser `blueprint | quest_component | sigil | enchantment`; la narrativa de "componentes" mezcla conceptos y exige exponerse con `material_type` explícito.

## 8. Tabla de endpoints existentes y propuestos

**Existentes (ninguno):**

| Método | Ruta | Estado |
|---|---|---|
| — | — | No existe ningún endpoint de API. Solo `GET /` (vista web). |

**Propuestos (NO implementados en esta tarea):**

| Método | Ruta | Descripción | Auth |
|---|---|---|---|
| `GET` | `/api/v1/blueprints` | Listado paginado con filtros, ordenamiento e `include`. | Pública (solo lectura) |
| `GET` | `/api/v1/blueprints/{id}` | Detalle por ID (con relaciones opcionales). | Pública |
| `GET` | `/api/v1/blueprints/by-name/{name}` | Detalle por nombre exacto (identidad natural UNIQUE). | Pública |
| `GET` | `/api/v1/categories` | Catálogo `item_categories`. | Pública |
| `GET` | `/api/v1/workers` | Catálogo `workers`. | Pública |
| `GET` | `/api/v1/resources` | Catálogo `resources` (12). | Pública |
| `GET` | `/api/v1/qualities` | Catálogo `qualities`. | Pública |
| `GET` | `/api/v1/elements` | Catálogo `elements`. | Pública |
| `GET` | `/api/v1/enchantments` | Catálogo `enchantments` (filtro por `type`). | Pública |
| `GET` | `/api/v1/spirit-affinities` | Catálogo `spirit_affinities`. | Pública |
| `GET` | `/api/v1/spirits` | Catálogo `spirits`. | Pública |
| `GET` | `/api/v1/workers/{worker_key}/levels` | Progresión desde `worker_levels` (según regla pendiente). | Pública |

> La búsqueda por nombre se resuelve como filtro del listado (`?filter[name]=squire`), no requiere un endpoint dedicado. `by-name` se propone solo si se quiere una URL canónica compartible.

## 9. Ejemplos JSON propuestos

### 9.1 Listado — `GET /api/v1/blueprints?filter[category]=Sword&filter[tier]=1&sort=tier&page=1&per_page=25`

```json
{
  "data": [
    {
      "id": 1,
      "name": "Squire Sword",
      "category": { "id": 1, "name": "Sword" },
      "tier": 1,
      "unlock_prerequisite": null,
      "gold_value": 27,
      "craft_time_seconds": 15,
      "value_per_second": 1.8,
      "merchant_xp": 27,
      "worker_xp": 1,
      "fusion_xp": 1,
      "favor": 1,
      "airship_power": 5,
      "stats": { "atk": 16, "def": null, "hp": null, "eva": null, "crit": null, "mana_shield": null },
      "energies": { "discount": 0, "surcharge": 0, "suggest": 0, "speed_up": 0 },
      "available_as_antique_at": null
    }
  ],
  "meta": { "current_page": 1, "per_page": 25, "total": 1549, "last_page": 62 },
  "links": { "first": "…", "prev": null, "next": "…", "last": "…" }
}
```

### 9.2 Detalle — `GET /api/v1/blueprints/1?include=workers,resources,components,elementalAffinities,upgrades`

```json
{
  "data": {
    "id": 1,
    "name": "Squire Sword",
    "category": { "id": 1, "name": "Sword" },
    "unlock_prerequisite": null,
    "research_scrolls": null,
    "antique_tokens": null,
    "tier": 1,
    "gold_value": 27,
    "craft_time_seconds": 15,
    "craft_time_formatted": "15s",
    "value_per_second": 1.8,
    "merchant_xp": 27,
    "merchant_xp_per_second": 1.8,
    "worker_xp": 1,
    "fusion_xp": 1,
    "favor": 1,
    "airship_power": 5,
    "stats": { "atk": 16, "def": null, "hp": null, "eva": null, "crit": null, "mana_shield": null },
    "energies": { "discount": 0, "surcharge": 0, "suggest": 0, "speed_up": 0 },
    "available_as_antique_at": null,
    "workers": [
      { "worker": { "id": 1, "worker_key": "Blacksmith" }, "level_required": 1 }
    ],
    "resources": [
      { "resource": { "id": 1, "name": "Iron", "tier": 1 }, "quantity": 5 }
    ],
    "components": [],
    "elemental_affinities": [],
    "spirit_affinity": null,
    "built_in_element": null,
    "built_in_spirit": null,
    "crafting_upgrades": [
      { "upgrade_level": 1, "name": "Sturdy", "crafts_required": 25 }
    ],
    "starforged_milestones": [
      { "milestone_number": 1, "name": "…", "crafts_required": 50 }
    ],
    "ascension_upgrades": [ { "upgrade_level": 1, "name": "…", "shards_required": 10 } ],
    "transcendence_upgrades": [ { "upgrade_level": 1, "name": "…", "seals_required": 5 } ]
  }
}
```

### 9.3 Ejemplo de AZ con dos elementos (orden/prioridad preservado)

```json
"elemental_affinities": [
  { "position": 1, "element": { "id": 3, "name": "Dark" } },
  { "position": 2, "element": { "id": 2, "name": "Earth" } }
]
```

### 9.4 Error de validación (HTTP 422)

```json
{
  "message": "The given data was invalid.",
  "errors": { "filter.tier": ["The filter.tier must be an integer."] }
}
```

> **Consistencia numérica:** para que `value_per_second`/`merchant_xp_per_second` sean números JSON (no string), los API Resources deben convertir el cast `decimal:4` a `float` explícitamente (ver riesgo R-1 en §13).

## 10. Filtros, ordenamiento y paginación recomendados

**Filtros (combinables) sobre columnas y relaciones reales:**

| Parámetro | Campo | Tipo | Nota |
|---|---|---|---|
| `filter[name]` | `blueprints.name` | string (parcial) | Búsqueda por nombre. |
| `filter[category]` | `category.name` (o `category_id`) | string/int | Relación existente. |
| `filter[tier]` | `blueprints.tier` | int / rango | |
| `filter[gold_value_min]`, `filter[gold_value_max]` | `gold_value` | int | |
| `filter[craft_time_max]` | `craft_time_seconds` | int | Útil para timers. |
| `filter[unlock_prerequisite]` | `unlock_prerequisite` | string | Texto libre (sin catálogo). |
| `filter[element]` | vía `elementalAffinities.element.name` | string | Requiere join. |
| `filter[spirit_affinity]` | BA `name` | string | |
| `filter[built_in_element]` | BB `name` | string | |
| `filter[resource]` | vía `blueprintResources.resource.name` | string | Requiere join. |
| `filter[worker]` | vía `blueprintWorkers.worker.worker_key` | string | Requiere join. |
| `filter[available_as_antique]` | `available_as_antique_at` no nulo | bool | |

**Ordenamiento permitido (whitelist):** `name`, `tier`, `gold_value`, `craft_time_seconds`, `merchant_xp`, `airship_power`, `id`. Formato: `?sort=-gold_value`. **No** permitir ordenar por campos no indexados sin advertirlo (ver R-2).

**Paginación:** por defecto `per_page=25`, máximo `100`. Formato `?page=N&per_page=M`. Usar la paginación de longitud de Laravel para mantener `data` + `meta` + `links`. Devolver 1549 registros exige paginación obligatoria (no listar todo sin límite).

**Inclusión selectiva (`include`):** whitelist de relaciones: `category` (siempre), `workers`, `resources`, `components`, `elementalAffinities`, `spiritAffinity`, `builtInElement`, `builtInSpirit`, `craftingUpgrades`, `starforgedMilestones`, `ascensionUpgrades`, `transcendenceUpgrades`. En el listado, incluir por defecto solo `category`; cargar el resto bajo demanda con `with()`.

**Errores HTTP:** `200` OK, `404` no encontrado (incluye formato JSON por el hook `api/*`), `422` validación de parámetros, `429` si se activa rate limiting, `500` error interno.

## 11. Datos disponibles para herramientas futuras

### 11.1 Datos almacenados (listos para exponer)

- **Tiempo base de fabricación:** `blueprints.craft_time_seconds` (+ `craft_time_formatted`).
- **Recursos y cantidades:** `blueprint_resources` (12 recursos, `quantity`).
- **Componentes, materiales y calidades:** `blueprint_components` → `crafting_materials` (`material_type`, `name`, `tier`, `gold_value`, `fuse_time_seconds`, `worker_level_required`, `worker_xp`) + `qualities`.
- **Estadísticas y valores:** 6 stats + `gold_value`, `merchant_xp`, `worker_xp`, `fusion_xp`, `favor`, `airship_power`, 4 energías.
- **Requisitos de trabajadores:** `blueprint_workers` (`worker_id`, `level_required`) + catálogo `workers`.
- **Progresión:** `blueprint_crafting_upgrades`, `blueprint_starforged_milestones`, `blueprint_ascension_upgrades`, `blueprint_transcendence_upgrades`.
- **Afinidades:** AZ (1:N con `position`), BA/BB/BC (FK a catálogos).
- **Antique:** `available_as_antique_at`.
- **Progresión de workers:** `worker_levels` (`level`, `xp_needed`, `crafting_speed_bonus`, `crafting_speed_bonus_t15plus`).

### 11.2 Datos derivados (calculables por el cliente o por la API)

- Tiempo real de fabricación **aplicando bonus de velocidad** → **requiere regla de negocio** (ver 11.3).
- `value_per_second`, `merchant_xp_per_second` (ya en BD, redundantes).
- Coste total de materiales (suma de `quantity` × valor de recurso/material).
- XP total por cadena de crafting (si las reglas de upgrade lo definen).

### 11.3 Reglas de negocio todavía desconocidas (NO inventar)

- **Cómo se aplica `crafting_speed_bonus` / `crafting_speed_bonus_t15plus`** al tiempo de fabricación (por nivel de worker, global, tier 15+). Sin esto, el "timer de fabricación" no puede calcularse.
- **Semántica de `Gold` y `All`** en `elements`.
- **Reglas de eventos** (bonificaciones temporales, cálculo de puntos): no hay datos en el modelo ni documentación disponible.
- **Relación `spirit_affinities` (BA) ↔ `enchantments`** y su efecto en la fabricación.
- **Interpretación de `unlock_prerequisite`** (mezcla workers/packs/chests/passes sin estructura).

**Recomendación:** exponer en la API **solo datos almacenados** en el MVP; no derivar reglas no documentadas. Las calculadoras las implementarán los clientes con sus propias reglas hasta que el proyecto las formalice.

## 12. Carencias, riesgos y decisiones pendientes

### Carencias (bloqueantes para implementar, no para auditar)
- C-1: No existe `routes/api.php` ni registro del grupo `api` en `bootstrap/app.php`.
- C-2: No existen API Resources ni Form Requests.
- C-3: No hay catálogo estructurado para `unlock_prerequisite`.
- C-4: Sin política de autenticación/rate limiting definida (aunque los datos son públicos de solo lectura).
- C-5: Sin documentación OpenAPI ni librería de generación.

### Riesgos (§13 del proyecto)
- **R-1 (consistencia de tipos):** los casts `decimal:4` serializan como **string** (`"1.8000"`). Sin conversión en el Resource, el JSON mezcla números y strings. Definir política (float vs string) y aplicarla uniformemente.
- **R-2 (N+1 y rendimiento):** el listado con `include` de relaciones 1:N (hasta 12 recursos × 1549 blueprints) puede degradar. Cargar por defecto solo `category` y usar `with()` explícito; nunca `$with` global (decisión vigente del proyecto).
- **R-3 (índices):** además de las PK y los UNIQUE, **no hay índices secundarios**. En PostgreSQL, `foreignId()->constrained()` **no crea índice** en la columna referenciante, por lo que filtrar/ordenar por `category_id`, `tier`, `gold_value` hará *seq scan*. `PROJECT_CONTEXT.md` lo declara deliberado ("sin índices secundarios hasta conocer consultas reales"); la auditoría recomienda decidir índices **al** aprobar el contrato, no antes.
- **R-4 (paginación):** 1549 filas; prohibido un listado sin paginación y con `per_page` sin tope.
- **R-5 (integridad expuesta):** `component_id` → `crafting_materials` (4 tipos). Exponer `components` sin `material_type` confunde "blueprint"/"quest_component"/"sigil"/"enchantment".
- **R-6 (nulabilidad):** stats nullable, energías con default 0, `unlock_prerequisite`/fechas nullable. La API debe documentar qué `null` significa por campo (ausencia real vs. no aplica).
- **R-7 (acceso/exposición):** no exponer `raw_value` (auditoría interna) ni `source_key` salvo necesidad; considerar ocultar `created_at`/`updated_at` si no aportan.
- **R-8 (CORS):** sin `config/cors.php` ni allowlist; definir política antes de exponer a navegadores.

### Decisiones pendientes (requieren aprobación)
1. **Estrategia de versionado y estructura de rutas** (`routes/api.php` + `apiPrefix: 'api'` + grupo `v1`).
2. **Uso o no de una librería de query filtering** (p. ej. `spatie/laravel-query-builder`) o filtros manuales. *(Añadir dependencias requiere aprobación.)*
3. **Política de autenticación** (datos públicos → endpoint abierto; confirmar).
4. **Política de tipos JSON para `DECIMAL`** (float vs string).
5. **Qué campos derivados exponer** (`craft_time_formatted`, `value_per_second`, `merchant_xp_per_second`).
6. **Índices secundarios** a crear (fuera del alcance de esta auditoría; no se crea ninguno).
7. **Formato de errores y envoltura de respuesta** (`data` wrapper de Laravel Resources vs. `JsonResource::withoutWrapping`).
8. **Documentación OpenAPI** (librería o spec manual).

## 13. Plan de implementación por etapas (propuesto, no autorizado)

> Cada etapa requiere aprobación humana antes de ejecutarse. Ninguna se inicia en esta tarea.

- **Etapa 0 — ADR y contrato.** Registrar en ADR la estrategia de versionado, auth, formato de respuesta y política de tipos. Congelar el contrato (§8–§10).
- **Etapa 1 — Infraestructura.** Crear `routes/api.php`, registrar `api:` en `bootstrap/app.php` con `apiPrefix: 'api'`, grupo `v1`. Sin lógica de negocio.
- **Etapa 2 — Catálogos.** `Controller` + API Resources para los 9 catálogos (endpoints triviales de solo lectura) + tests.
- **Etapa 3 — Listado de blueprints.** `GET /api/v1/blueprints` con paginación, filtros whitelist y `category` incluida; tests (pag inación, filtros, validación 422).
- **Etapa 4 — Detalle.** `GET /api/v1/blueprints/{id}` + `include` selectivo con `with()`; test de no-N+1 (contador de queries).
- **Etapa 5 — Endurecimiento.** Rate limiting, CORS, casos `404`/`422`, consistencia de tipos, ocultado de campos internos.
- **Etapa 6 — Contrato OpenAPI.** Generar/servir la especificación OpenAPI alineada con los Resources.
- **Etapa 7 — Revisión cruzada.** Revisar con el arquitecto las decisiones de §12 y actualizar `PROJECT_CONTEXT.md`/`README.md`.

## 14. Archivos inspeccionados y evidencias concretas

**Contexto/documentación:**
- `AI_EDITOR_INSTRUCTIONS.md`, `README.md`, `PROJECT_CONTEXT.md`, `AI_WORK_LOG.md`, `CHANGELOG.md`, `DATA_MODEL_ANALYSIS.md`, `docs/reports/TEMPLATE_REPORT.md`, `docs/reports/2026-10-16_ejecucion_d3_postgresql.md`.

**Infraestructura API:**
- `src/routes/web.php` (solo `GET /`), `src/routes/console.php`, `src/bootstrap/app.php`, `src/bootstrap/providers.php`, `src/config/auth.php`, `src/composer.json`, `src/app/Providers/AppServiceProvider.php`, `src/app/Http/Controllers/Controller.php`.

**Modelos:**
- `Blueprint`, `BlueprintWorker`, `BlueprintResource`, `BlueprintComponent`, `BlueprintCraftingUpgrade`, `BlueprintStarforgedMilestone`, `BlueprintAscensionUpgrade`, `BlueprintTranscendenceUpgrade`, `BlueprintElementalAffinity`, `CraftingMaterial`, `Element`, `Enchantment`, `Spirit`, `SpiritAffinity`, `ItemCategory`, `Quality`, `Resource`, `Worker`, `WorkerLevel`, `User`.

**Migraciones (esquema real):**
- `2026_08_28_100001..100014` (catálogos, `blueprints`, 8 tablas 1:N), `2026_10_14_000001..000002`, `2026_10_16_000001..000005`.

**Servicios / comandos / lectura:**
- `src/app/Services/BlueprintImportService.php`, `src/app/Imports/BlueprintRowData.php`, `src/app/Console/Commands/ImportBlueprints.php`, `src/tests/Feature/BlueprintImportServiceTest.php`.

**Git (evidencia):**
```text
HEAD: f0cb37c (master, origin/master) "feat: modelar elementos y afinidades de blueprints"
Untracked:   "Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx",
             blueprint_components.txt, blueprints.txt, d1-crafting-materials.diff,
             shop-titans-esquema.zip
Archivos versionados modificados: ninguno
```

## 15. Validaciones realizadas y limitaciones de la auditoría

**Validaciones realizadas:**
```bash
git status --short
git log --oneline -5
```
```text
Resultado: sin archivos versionados modificados; 5 artefactos untracked.
HEAD = f0cb37c. No se ejecutaron migraciones, seeders, importadores ni consultas.
```
- Inspección estática de código (`Read`/`LS`/`Grep`) de rutas, arranque, auth, modelos y migraciones.

**Limitaciones (declaradas explícitamente):**
- **No se consultó PostgreSQL** en esta auditoría (restricción de la tarea). Los conteos citados (p. ej. `blueprints`=1549) provienen de los reportes D-1/D-3, **no** fueron re-verificados aquí.
- No se ejecutaron tests ni `php -l`/Pint: la tarea no modifica código.
- El contrato propuesto (§8–§10) es una **propuesta**; requiere aprobación y puede cambiar tras revisión del arquitecto.
- Los nombres de relaciones en las respuestas JSON (`workers`, `resources`, `components`) son etiquetas de presentación y pueden diferir de los nombres de métodos Eloquent (`blueprintWorkers`, etc.); se definirán al crear los API Resources.

## 16. Resultado final

Auditoría **COMPLETADA**. Hallazgo central: **no existe infraestructura API ni endpoints**; el modelo de datos, en cambio, está completo y listo para exponerse. Se identificaron 8 riesgos, 5 carencias y 8 decisiones pendientes de aprobación. **La API NO fue implementada; PostgreSQL no fue modificado; no se añadieron dependencias ni se hizo commit/push.**

## 17. Riesgos (resumen)

Ver §12 (R-1 a R-8). Los más relevantes: **R-1** (tipos `DECIMAL` como string), **R-2** (N+1), **R-3** (ausencia de índices secundarios) y **R-5** (componentes polimórficos vía `crafting_materials`).

## 18. Pendientes

1. Aprobar o ajustar las 8 decisiones de §12.
2. Aprobar el contrato REST (§8–§10) y el plan por etapas (§13).
3. Decidir si se crea un ADR (probable: versionado + auth + formato de respuesta).
4. Formalizar las reglas de negocio de §11.3 antes de ofrecer cálculos derivados.

## 19. Preguntas para ChatGPT

1. ¿Se aprueba exponer la API como **pública de solo lectura** sin autenticación en el MVP?
2. ¿Se prefiere filtrado manual o una librería (`spatie/laravel-query-builder`)?
3. ¿Política de `DECIMAL` en JSON: `float` o `string`?
4. ¿Se exponen los campos derivados o se recalculan en el cliente?
5. ¿Se crea un ADR de "estrategia de API"?

## 20. Próximo paso recomendado

**Revisión humana** de este reporte y decisión sobre §12 (decisiones pendientes). Si se aprueba, el primer paso ejecutable es la **Etapa 0–1** (ADR de estrategia + registro de `routes/api.php` versionado con `/api/v1`), que debe quedar **pendiente de aprobación explícita** antes de escribir código.
