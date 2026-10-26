# Reporte Técnico — Evaluación de la búsqueda de Blueprints por componentes

**Fecha:** 2026-10-26
**Autor:** Editor IA
**Estado:** PENDIENTE DE REVISIÓN (evaluación completada; decisión de implementación pendiente de aprobación humana)

## 1. Objetivo

Determinar la viabilidad de añadir un filtro por **componentes** al endpoint existente `GET /api/v1/blueprints`, evitando duplicar funcionalidades ya presentes (en particular el `include=components` y el filtro `filter[resource]`) y manteniendo el alcance del MVP.

La evaluación es de **solo lectura**: no se implementa el filtro, no se modifica código ni el contrato, y no se ejecutan escrituras en PostgreSQL.

## 2. Alcance

**Incluye:**
- Inspección del controlador, modelos, relaciones, migraciones, pruebas y contrato OpenAPI vigentes.
- Confirmación (comprobada, no asumida) de si ya existe búsqueda por componentes.
- Distinción entre el `include=components` y los filtros relacionales actuales.
- Verificación de la cadena `blueprints` ↔ `blueprint_components` ↔ `crafting_materials`, incluyendo los tipos de material.
- Evaluación de tres alternativas: nombre exacto, búsqueda parcial y búsqueda por identificador.
- Recomendación con sintaxis, semántica, validaciones, comportamiento con filtros combinados y tratamiento de duplicados.
- Análisis de rendimiento (sin proponer índices sin evidencia).
- Pruebas necesarias y cambios requeridos en OpenAPI.

**Excluye:**
- Implementación del filtro (prohibida en esta tarea).
- Cambios en código PHP, pruebas, contrato OpenAPI, modelos o migraciones.
- Nuevos índices (no se proponen sin medición previa).
- Migraciones, seeders, importadores, restauraciones y cualquier escritura en PostgreSQL.
- Nuevas dependencias, commits o push.

## 3. Estado inicial

- Endpoint público existente: `GET /api/v1/blueprints` y `GET /api/v1/blueprints/{id}` ([api.php](file:///home/draven/Documentos/shop-titans-api/src/routes/api.php#L6-L9)).
- Filtros vigentes declarados en la whitelist `BlueprintIndexRequest::FILTERS` ([BlueprintIndexRequest.php](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Requests/BlueprintIndexRequest.php#L32-L49)): `name`, `unlock_prerequisite` (parcial); `category`, `category_id`, `element`, `spirit_affinity`, `built_in_element`, `built_in_spirit`, `resource`, `worker`, `available_as_antique` (exacto); `tier`, `gold_value`, `craft_time_seconds`, `merchant_xp`, `airship_power` (numérico con `eq/gt/gte/lt/lte`). **No existe `component`.**
- El único mecanismo relacionado con componentes es el `include=components` (carga diferida por eager loading), no un filtro.

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| — | — | **Ninguno.** Evaluación de solo lectura. No se modificó ningún archivo de código, prueba, contrato, modelo o migración. |

> Los únicos artefactos escritos por esta tarea son este informe y el hito correspondiente en `AI_WORK_LOG.md`, autorizados explícitamente por el encargo.

## 5. Cambios realizados

Ninguno. Esta sección se documenta como "sin cambios" por trazabilidad del protocolo.

Detalle de la **investigación realizada** (solo lectura):

1. **Controlador** — `BlueprintController::applyFilter()` es un `switch` por campo ([BlueprintController.php](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php#L110-L157)). **No** existe caso para componentes. El caso más cercano es el filtro relacional `resource`:
   ```php
   case 'resource':
       $query->whereHas('blueprintResources.resource', fn ($relation) => $relation->where('name', $value));
       break;
   ```
   ([BlueprintController.php](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php#L142-L144))
2. **Request del listado** — `FILTERS`, `SORTABLE` ([BlueprintIndexRequest.php#L16-L49](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Requests/BlueprintIndexRequest.php#L16-L49)) y `valueRules()` ([BlueprintIndexRequest.php#L158-L167](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Requests/BlueprintIndexRequest.php#L158-L167)). No hay entrada de componente.
3. **Modelo `Blueprint`** — relación padre de componentes: `blueprintComponentsAsParent()` ([Blueprint.php#L101-L104](file:///home/draven/Documentos/shop-titans-api/src/app/Models/Blueprint.php#L101-L104)).
4. **Modelo `BlueprintComponent`** — `component(): belongsTo(CraftingMaterial::class, 'component_id')` ([BlueprintComponent.php#L29-L32](file:///home/draven/Documentos/shop-titans-api/src/app/Models/BlueprintComponent.php#L29-L32)).
5. **Modelo `CraftingMaterial`** — `blueprintComponents(): hasMany(BlueprintComponent::class, 'component_id')` ([CraftingMaterial.php#L40-L43](file:///home/draven/Documentos/shop-titans-api/src/app/Models/CraftingMaterial.php#L40-L43)); incluye la columna `material_type` ([CraftingMaterial.php#L11-L22](file:///home/draven/Documentos/shop-titans-api/src/app/Models/CraftingMaterial.php#L11-L22)).
6. **Migraciones** — creación de `blueprint_components` ([2026_08_28_100010](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_08_28_100010_create_blueprint_components_table.php#L11-L32)), cambio de FK de `component_id` ([2026_10_14_000002](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_10_14_000002_change_blueprint_components_component_id_foreign.php)) y creación de `crafting_materials` con el `enum material_type` ([2026_10_14_000001#L18-L23](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_10_14_000001_create_crafting_materials_table.php#L18-L23)).
7. **Pruebas** — `BlueprintApiTest` cubre filtros relacionales ([BlueprintApiTest.php#L193-L232](file:///home/draven/Documentos/shop-titans-api/src/tests/Feature/BlueprintApiTest.php#L193-L232)), serialización de componentes ([BlueprintApiTest.php#L326-L346](file:///home/draven/Documentos/shop-titans-api/src/tests/Feature/BlueprintApiTest.php#L326-L346)) y ausencia de N+1 ([BlueprintApiTest.php#L364-L381](file:///home/draven/Documentos/shop-titans-api/src/tests/Feature/BlueprintApiTest.php#L364-L381)). **No** existe ninguna prueba que filtre por componente.
8. **Contrato OpenAPI** — parámetros del endpoint ([blueprints-v1.yaml#L108-L151](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L108-L151)) y `FilterResource` como análogo ([blueprints-v1.yaml#L616-L623](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L616-L623)). **No** existe parámetro de filtro por componente.

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| Ya existe búsqueda/filtro por componentes | `FILTERS` ([BlueprintIndexRequest.php#L32-L49](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Requests/BlueprintIndexRequest.php#L32-L49)) y `switch` de `applyFilter()` ([BlueprintController.php#L110-L157](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php#L110-L157)) no contienen ningún caso de componente; OpenAPI no declara parámetro alguno | **Rechazada** (no existe) |
| El `include=components` ya "resuelve" el caso | `INCLUDES` lo declara para eager loading ([BlueprintRequest.php#L15-L27](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Requests/BlueprintRequest.php#L15-L27)) y `EAGER_LOAD_MAP` lo mapea a `blueprintComponentsAsParent.component` ([BlueprintRequest.php#L38](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Requests/BlueprintRequest.php#L38)); se resuelve en `applyRelations()` ([BlueprintController.php#L67-L84](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php#L67-L84)) | **Confirmada, pero es carga, no filtrado**: no restringe el conjunto de blueprints |
| El filtro por componentes sería un duplicado del filtro `resource` | `resource` opera sobre `blueprintResources.resource` → tabla `resources`; componentes operan sobre `blueprint_components.component` → tabla `crafting_materials`. Tablas, entidades y semántica distintas | **Rechazada** (no es duplicado) |
| `component_id` referencia a `crafting_materials` (no a `blueprints`) | Migración de cambio de FK ([2026_10_14_000002](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_10_14_000002_change_blueprint_components_component_id_foreign.php)) + `BlueprintComponent::component()` ([BlueprintComponent.php#L29-L32](file:///home/draven/Documentos/shop-titans-api/src/app/Models/BlueprintComponent.php#L29-L32)) + `CraftingMaterial::blueprintComponents()` ([CraftingMaterial.php#L40-L43](file:///home/draven/Documentos/shop-titans-api/src/app/Models/CraftingMaterial.php#L40-L43)) | **Confirmada** |
| `crafting_materials.name` es único | La migración solo declara `unique()` en `source_key`; `name` es `string` sin restricción única ([2026_10_14_000001#L15-L16](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_10_14_000001_create_crafting_materials_table.php#L15-L16)) | **Rechazada**: el nombre **no** es único |
| El camino relacional genera duplicados de blueprint | El patrón vigente usa `whereHas` (subconsulta `EXISTS`), no `join` ([BlueprintController.php#L118-L148](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php#L118-L148)) | **Rechazada**: una fila de blueprint aparece a lo sumo una vez |

### 6.1 Cadena de relación verificada

```text
blueprints.id
  └─ blueprint_components.blueprint_id           (FK, cascadeOnDelete)      [2026_08_28_100010#L14-L16]
       └─ blueprint_components.component_id      (FK, restrictOnDelete)     [2026_10_14_000002]
            └─ crafting_materials.id             (enum material_type)       [2026_10_14_000001#L18-L23]
```

Tipos de material admitidos (`material_type`): `blueprint`, `quest_component`, `sigil`, `enchantment` ([2026_10_14_000001#L18-L23](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_10_14_000001_create_crafting_materials_table.php#L18-L23)), reforzados por `chk_material_type_blueprint_id` y `chk_source_key_format` ([mismo archivo#L41-L56](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_10_14_000001_create_crafting_materials_table.php#L41-L56)). El contrato ya documenta esta enumeración en `CraftingMaterialRef` ([blueprints-v1.yaml#L868-L878](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L868-L878)).

Restricción de unicidad de la tabla pivote: `unique(['blueprint_id', 'component_id', 'quality_id'])` ([2026_08_28_100010#L29](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_08_28_100010_create_blueprint_components_table.php#L29)). Un blueprint puede tener varias filas de componentes distintas.

## 7. Discrepancias

Ninguna. El estado documentado coincide con el código: no hay filtro por componentes declarado en código, pruebas ni contrato, y no se halló documentación que afirme lo contrario.

## 8. Decisiones

### Propuestas

> Ninguna de las siguientes es una decisión aprobada; todas requieren confirmación humana explícita.

- **DP-A — Naturaleza del filtro:** añadir un filtro **relacional** `filter[component]` sobre `crafting_materials.name` mediante `whereHas('blueprintComponentsAsParent.component', ...)`, análogo al filtro `resource`.
- **DP-B — Semántica de coincidencia:** exacta (recomendada, ver §12) o parcial.
- **DP-C — Nombre del parámetro:** `filter[component]` (recomendado) frente a alternativas como `filter[component_name]`.
- **DP-D — Acotación por tipo de material:** si el filtro debe aceptar opcionalmente `material_type` para desambiguar nombres homónimos, o mantenerse simple en el MVP.
- **DP-E — Índices:** **no** se propone ningún índice nuevo en esta tarea; solo se deja constancia de que su necesidad debe medirse antes de decidir (§11).

### Aprobadas

- Ninguna.

### Reemplazadas/Rechazadas

- Ninguna.

## 9. Validaciones

No se ejecutaron comandos de escritura. La investigación fue de inspección estática (lectura de archivos). No se ejecutaron migraciones, seeders, importadores ni consultas contra PostgreSQL; no se ejecutaron pruebas (no se modificó nada que lo requiriera).

## 10. Alternativas comparadas

### Alternativa A — Búsqueda por **nombre exacto**
- **Sintaxis:** `filter[component]=Ruby` (equivalente a `filter[component][eq]=Ruby`).
- **Implementación:** `$query->whereHas('blueprintComponentsAsParent.component', fn ($r) => $r->where('name', $value));`
- **A favor:** plenamente coherente con los filtros relacionales de catálogo ya existentes (`category`, `resource`, `worker`, `built_in_element`, `built_in_spirit`, `spirit_affinity`), todos con `where('name', $value)`. El nombre es el identificador legible que el propio Excel y el `include=components` exponen.
- **En contra:** exige conocer el nombre exacto.

### Alternativa B — Búsqueda **parcial** (contiene, sin distinguir mayúsculas)
- **Sintaxis:** `filter[component]=rub`.
- **Implementación:** reutilizar `applyPartialMatch()` ([BlueprintController.php#L165-L168](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php#L165-L168)) dentro del `whereHas` sobre `name`.
- **A favor:** más cómoda de usar; coherente con la familia `partial` (`name`, `unlock_prerequisite`).
- **En contra:** rompe la coherencia semántica con el resto de filtros relacionales de catálogo (todos exactos); amplía resultados de forma implícita; dificulta el encadenamiento predecible en el MVP.

### Alternativa C — Búsqueda por **identificador** (`crafting_materials.id`)
- **Sintaxis:** `filter[component_id]=42` (con `minimum: 1`, como `category_id`).
- **Implementación:** `$query->whereHas('blueprintComponentsAsParent.component', fn ($r) => $r->where('id', $value));`
- **A favor:** precisa; el `id` del material ya se expone en el `include=components` ([BlueprintResource.php#L64-L81](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Resources/BlueprintResource.php#L64-L81)), por lo que es descubrible; coherente con `category_id`.
- **En contra:** obliga a un viaje previo para descubrir el `id`; menos legible; sin catálogo público de materiales en esta etapa del MVP.

### Cuadro comparativo

| Criterio | A (nombre exacto) | B (parcial) | C (por id) |
|---|---|---|---|
| Coherencia con filtros vigentes | Alta (filtros relacionales exactos) | Media (familia `partial`) | Alta (`category_id`) |
| Legibilidad de la consulta | Alta | Alta | Baja |
| Precisión | Alta | Media | Alta |
| Riesgo de homónimos de `name` | Bajo (documentado) | Mayor | Nulo |
| Cambios necesarios | Mínimos | Mínimos | Mínimos + validación `min:1` |
| Compatible con MVP | Sí | Sí | Sí |

## 11. Análisis de rendimiento

- El patrón `whereHas` genera una **subconsulta correlacionada `EXISTS`** (no un `join`), por lo que no introduce filas duplicadas ni requiere `distinct` en el listado ([BlueprintController.php#L118-L148](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php#L118-L148)).
- La tabla `blueprint_components` ya tiene un índice por la restricción `unique(['blueprint_id', 'component_id', 'quality_id'])` ([2026_08_28_100010#L29](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_08_28_100010_create_blueprint_components_table.php#L29)), cuyo prefijo útil es `blueprint_id`.
- Para un filtro por `component_id`/nombre de material, la búsqueda óptima recorre `blueprint_components` por su columna de componente; **no hay evidencia medida** (no se ejecutó `EXPLAIN ANALYZE`) que demuestre que falte un índice dedicado.
- **Conclusión:** no se propone ningún índice. Cualquier propuesta de índice debe sustentarse primero en una medición con `EXPLAIN (ANALYZE, BUFFERS)` sobre datos reales, y quedar como tarea separada con aprobación humana.

## 12. Recomendación

**Alternativa A — filtro relacional por nombre exacto de componente**, con la siguiente definición (sujeta a aprobación):

- **Sintaxis:** `filter[component]=<nombre>`; la variante `filter[component][eq]=<nombre>` es equivalente (consistente con D-6).
- **Semántica:** devuelve los blueprints que tienen **al menos una** fila en `blueprint_components` cuyo material asociado (`crafting_materials.name`) sea exactamente igual a `<nombre>`. Implementación: `whereHas('blueprintComponentsAsParent.component', fn ($r) => $r->where('name', $value))`.
- **Validaciones:** añadir `'component' => 'exact'` a `FILTERS` ([BlueprintIndexRequest.php#L32-L49](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Requests/BlueprintIndexRequest.php#L32-L49)). El operador permitido sería solo `eq` (por `allowedOperators()`); el valor cae a la regla por defecto `['string']` ([BlueprintIndexRequest.php#L158-L167](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Requests/BlueprintIndexRequest.php#L158-L167)). Se recomienda exigir valor no vacío para no producir coincidencias triviales.
- **Filtros combinados:** se compone con los filtros vigentes mediante `AND` (cada filtro añade su propia condición), igual que el resto ([BlueprintApiTest.php#L174-L191](file:///home/draven/Documentos/shop-titans-api/src/tests/Feature/BlueprintApiTest.php#L174-L191)).
- **Resultados duplicados:** **ninguno**, porque `whereHas` usa `EXISTS`; un blueprint se lista una sola vez aunque tenga varios componentes coincidentes o varias filas del mismo componente con distinta `quality`.
- **Justificación:** es el filtro de menor complejidad y de máxima coherencia con el patrón ya aprobado para filtros relacionales de catálogo, sin ampliar el alcance del MVP. El riesgo de homónimos (§13) se documenta y se mitiga opcionalmente con DP-D.

## 13. Riesgos

- **R-1 — Homónimos de nombre:** `crafting_materials.name` **no** es único ([2026_10_14_000001#L15-L16](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_10_14_000001_create_crafting_materials_table.php#L15-L16)). Un filtro por nombre exacto puede casar materiales de distinto `material_type` con el mismo nombre. Impacto: resultados más amplios de lo esperado. Mitigación opcional: acotar por `material_type` (DP-D) o recomendar el `id` (Alternativa C) como vía precisa.
- **R-2 — Descubribilidad:** el usuario desconoce que el filtro existe si no se actualiza el contrato OpenAPI; de ahí la exigencia de actualizar `docs/openapi/blueprints-v1.yaml` (§14).
- **R-3 — Rendimiento no medido:** no se ha medido el coste del `EXISTS` sobre datos reales. Impacto: potencial lentitud en el listado. Mitigación: medición previa con `EXPLAIN ANALYZE` si se observa degradación; **no** añadir índices a ciegas.
- **R-4 — Ambigüedad semántica si se elige parcial (Alternativa B):** inconsistencias con los filtros de catálogo vigentes. Se evita eligiendo la Alternativa A.
- **R-5 — Deriva de alcance:** implementar el filtro durante una evaluación de solo lectura violaría las restricciones. Mitigación: implementación en tarea separada con aprobación.

## 14. Pruebas propuestas y cambios en OpenAPI

### Pruebas propuestas (a ejecutar solo tras aprobación; aquí **no** implementadas)

1. **Coincidencia por nombre exacto:** con un material `Ruby` asociado a un blueprint, `filter[component]=Ruby` → `meta.total = 1`.
2. **Sin coincidencia:** `filter[component]=Nonexistent` → `meta.total = 0`.
3. **Coherencia `[eq]`:** `filter[component][eq]=Ruby` produce el mismo resultado que `filter[component]=Ruby`.
4. **Operador no permitido:** `filter[component][gt]=Ruby` → `422` (análogo a [BlueprintApiTest.php#L143-L149](file:///home/draven/Documentos/shop-titans-api/src/tests/Feature/BlueprintApiTest.php#L143-L149)).
5. **Ausencia de duplicados:** un blueprint con dos filas de componente coincidentes (mismo material, distinta `quality`) aparece **una sola vez**.
6. **Combinación con otros filtros:** `filter[component]=Ruby` + `filter[tier][gte]=N` respeta la semántica `AND`.
7. **Sensibilidad a mayúsculas:** documentar/validar el comportamiento esperado (exacto ⇒ sensible) para fijar el contrato.
8. **Regresión:** la suite existente (`BlueprintApiTest`, 19 pruebas) debe seguir en verde.

### Cambios requeridos en el contrato OpenAPI (a aplicar solo tras aprobación)

- **Nuevo parámetro** `FilterComponent`, `name: filter[component]`, `in: query`, `required: false`, `schema.type: string`, con descripción «nombre exacto de un material componente requerido por el blueprint (relación `blueprint_components.component.name`, operador `eq`)» y `example: Ruby`. Modelarlo a partir de `FilterResource` ([blueprints-v1.yaml#L616-L623](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L616-L623)).
- **Referencia** `- $ref: '#/components/parameters/FilterComponent'` en la lista de parámetros del endpoint, en el grupo de «Catálogos y texto (igualdad exacta)», junto a `FilterResource` ([blueprints-v1.yaml#L116-L125](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L116-L125)).
- *(Opcional)* incluir el filtro en el ejemplo de solicitud ([blueprints-v1.yaml#L103-L107](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L103-L107)).

### Decisiones que requieren aprobación humana

- **H-5 (nueva, propuesta):** aprobar la incorporación del filtro por componentes al MVP y su sintaxis `filter[component]` (Alternativa A: nombre exacto).
- **DP-D:** decidir si se acota además por `material_type` (mitigación de R-1).
- **DP-E:** mantener la decisión de **no** crear índices hasta disponer de medición con evidencia.

## 15. Resultado final

- **Confirmado por código, pruebas y contrato:** **no** existe búsqueda ni filtro por componentes en `GET /api/v1/blueprints`. Lo único relacionado es el `include=components`, que es **carga por eager loading**, no filtrado, y no debe confundirse con un filtro.
- **Confirmado:** el filtro por componentes **no** duplica el filtro `resource` (tablas y entidades distintas) ni el `include=components` (semánticas distintas).
- **Recomendación:** Alternativa A (nombre exacto, `filter[component]`), mínima y coherente con el patrón vigente; duplicados descartados por el uso de `EXISTS`.
- **Sin cambios en código, pruebas, contrato, modelos, migraciones ni PostgreSQL; sin índices propuestos sin evidencia; sin commit ni push.**

## 16. Próximo paso recomendado

1. Revisar y aprobar (o corregir) la recomendación y las decisiones DP-A..DP-E.
2. Si se aprueba: implementar el filtro, actualizar OpenAPI y añadir las pruebas de §14 en una **tarea de implementación separada**.
3. Registrar el resultado en `AI_WORK_LOG.md`.
