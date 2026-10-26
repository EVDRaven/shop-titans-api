# Reporte Técnico — Etapa 1: Contrato de la API pública de Blueprints

**Fecha:** 2026-10-16
**Autor:** Editor IA
**Estado:** IMPLEMENTADA — PENDIENTE DE REVISIÓN

## 1. Objetivo

Documentar el contrato de la API pública de **solo lectura** del módulo Blueprints, aplicando las decisiones arquitectónicas aprobadas por el representante humano, **sin implementar** rutas, controladores, Resources ni lógica de negocio.

## 2. Alcance

**Incluye:**
- Definición del contrato de `GET /api/v1/blueprints` y `GET /api/v1/blueprints/{id}`.
- Verificación de cada campo y relación contra el código real (modelos y migraciones).
- Especificación OpenAPI 3.0.3 en archivo independiente.
- Registro de las decisiones D-1..D-8 (aprobadas por el representante humano).

**Excluye (prohibido en esta etapa):**
- Implementar rutas, controladores, API Resources, Form Requests o lógica de negocio.
- Ejecutar migraciones, seeders, importaciones o cualquier escritura en PostgreSQL.
- Instalar dependencias.
- Modificar decisiones arquitectónicas aprobadas.
- Crear endpoints de catálogos (fuera de alcance del MVP).

## 3. Estado inicial (Git)

```text
Rama: master (origin/master) — commit f0cb37c "feat: modelar elementos y afinidades de blueprints"
Sin cambios en archivos versionados al iniciar.
```

Nota: en esta revisión los reportes residen en `docs/reports/` (su ubicación actual) y **no se
mueven, restauran ni eliminan**. Se conserva la ubicación presente sin alterarla.

## 4. Decisiones aprobadas (aplicadas al contrato)

| Decisión | Aplicación en el contrato |
|---|---|
| Prefijo versionado `/api/v1` | `servers: [{ url: /api/v1 }]`; rutas `/blueprints` y `/blueprints/{id}`. |
| API pública, solo lectura, sin auth | Sin `security` ni esquemas de autenticación en el OpenAPI. |
| Solo dos endpoints en el MVP | Únicamente los dos `paths` indicados. Sin endpoints de catálogos. |
| Listado paginado, filtros combinables, orden por lista permitida | `page`, `per_page`, `sort` (whitelist) y 16 filtros. |
| JSON uniforme (`data`, `meta`, `links` en listados) | `BlueprintListResponse` (data+meta+links); `BlueprintResponse` (data). |
| Inclusión selectiva de relaciones | Parámetro `include` como **lista separada por comas** (representada en OpenAPI como array con `style: form`, `explode: false`); `category` por defecto. |
| Filtros con operadores (D-6) | `filter[campo]` = `eq`; campos numéricos con operadores explícitos `filter[campo][gt]`, `[gte]`, `[lt]`, `[lte]`; búsqueda parcial para campos de texto. |
| Decimales como números JSON | Tipos `number/float` para `value_per_second` y `merchant_xp_per_second` (+ conversión en presentación). |
| Evitar N+1 | `include` acotado y carga explícita mediante `with()` (documentado, no implementado). |
| Sin índices ni dependencias nuevas | No se añadió ninguna dependencia; no se creó ningún índice. |
| OpenAPI sin dependencias nuevas | Archivo YAML estático en el repositorio. |

## 5. Entrega de archivos

| Archivo | Acción | Ruta exacta |
|---|---|---|
| Especificación OpenAPI | Creado / modificado en esta revisión | `/home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml` |
| Reporte de etapa | Creado / modificado en esta revisión | `/home/draven/Documentos/shop-titans-api/docs/reports/2026-10-16_contrato_api_blueprints.md` |
| Bitácora | Modificado | `/home/draven/Documentos/shop-titans-api/AI_WORK_LOG.md` |

No se modificó ningún archivo de código, modelo, migración, seeder ni configuración.

## 6. Contrato de los endpoints

### 6.1 `GET /api/v1/blueprints`

**Parámetros de consulta:**

| Parámetro | Tipo | Default | Descripción |
|---|---|---|---|
| `page` | integer ≥ 1 | 1 | Página. |
| `per_page` | integer 1..100 | 25 | Tamaño de página (D-5: default 25, máximo 100). |
| `sort` | string | `id` | Whitelist: `id`, `name`, `tier`, `gold_value`, `craft_time_seconds`, `merchant_xp`, `airship_power`. Prefijo `-` = descendente. |
| `include` | string (CSV) | — | Lista separada por comas; whitelist de relaciones (ver 6.3). Se serializa como `include=a,b,c`. |
| `filter[name]` | string | — | Búsqueda **parcial** (contiene, sin distinguir mayúsculas). |
| `filter[unlock_prerequisite]` | string | — | Búsqueda **parcial** (texto libre, sin catálogo). |
| `filter[category]` | string | — | Nombre exacto de categoría (`eq`). |
| `filter[category_id]` | integer | — | ID exacto de categoría (`eq`). |
| `filter[element]` | string | — | Nombre exacto de elemento (AZ, cualquier posición; `eq`). |
| `filter[spirit_affinity]` | string | — | BA, nombre exacto (`eq`). |
| `filter[built_in_element]` | string | — | BB, nombre exacto (`eq`). |
| `filter[built_in_spirit]` | string | — | BC, nombre exacto (`eq`). |
| `filter[resource]` | string | — | Recurso requerido, nombre exacto (`eq`). |
| `filter[worker]` | string | — | `worker_key` que puede fabricarlo (`eq`). |
| `filter[available_as_antique]` | boolean | — | `true` = `available_as_antique_at` no nulo (`eq`). |
| `filter[campo]` | integer | — | Numéricos con `eq`: `tier`, `gold_value`, `craft_time_seconds`, `merchant_xp`, `airship_power`. |
| `filter[campo][gt]` | integer | — | Numéricos: mayor que (`gt`). |
| `filter[campo][gte]` | integer | — | Numéricos: mayor o igual que (`gte`). |
| `filter[campo][lt]` | integer | — | Numéricos: menor que (`lt`). |
| `filter[campo][lte]` | integer | — | Numéricos: menor o igual que (`lte`). |

**Operadores (D-6).** `filter[campo]=valor` aplica **igualdad exacta** (`eq`) y equivale a `filter[campo][eq]=valor`. Los campos **numéricos** (`tier`, `gold_value`, `craft_time_seconds`, `merchant_xp`, `airship_power`) exponen además los operadores **explícitos** `filter[campo][gt]`, `filter[campo][gte]`, `filter[campo][lt]` y `filter[campo][lte]` (5 nombres por campo → 25 parámetros). Los campos de texto `name` y `unlock_prerequisite` usan búsqueda **parcial** (contiene, sin distinguir mayúsculas); los campos de catálogo usan `eq`.

**Representación en OpenAPI 3.0.3.** La sintaxis `filter[campo][op]` **no** admite una representación genérica en OpenAPI 3.0.3 (`deepObject`/`explode` solo cubren un nivel de anidamiento). Por ello se **enumeran explícitamente** los nombres con corchetes como parámetros individuales de `query` de este endpoint.

**Ejemplo de solicitud (exacto):**

```text
GET /api/v1/blueprints?page=1&per_page=25&sort=-gold_value&include=workers,components&filter[tier][gte]=3&filter[gold_value][lte]=1000&filter[name]=squire
```

### 6.2 `GET /api/v1/blueprints/{id}`

| Parámetro | Ubicación | Tipo | Descripción |
|---|---|---|---|
| `id` | path | integer ≥ 1 | Identificador interno. 404 si no existe. |
| `include` | query | string (CSV) | Lista separada por comas; igual que en el listado (6.3). |

### 6.3 Relaciones incluidas (`include`)

`workers`, `resources`, `components`, `elemental_affinities`, `spirit_affinity`, `built_in_element`, `built_in_spirit`, `crafting_upgrades`, `starforged_milestones`, `ascension_upgrades`, `transcendence_upgrades`. **`category` se incluye siempre** (no requiere `include`).

`include` es un **único** parámetro con **lista separada por comas** (`include=workers,components`). En OpenAPI se representa como `type: array` con `style: form` y `explode: false`, de modo que la whitelist (`enum`) valida **cada elemento** de la lista y no el valor completo; esto elimina la incompatibilidad previa de una `enum` de valor único sobre un parámetro multi-valor.

### 6.4 Estructura de respuestas y errores

- Listado (200): `{ data: [Blueprint], meta: {current_page, per_page, total, last_page, from, to}, links: {first, last, prev, next} }`.
- Detalle (200): `{ data: Blueprint }`.
- 404: `{ message }`.
- 422: `{ message, errors: { campo: [mensajes] } }`.
- 500: `{ message }`.

### 6.5 Tipos JSON y nulabilidad (verificados contra el código)

| Campo | Tipo JSON | Nullable | Origen | Guaranteed by cast |
|---|---|---|---|---|
| `id` | integer | No | `blueprints.id` | PK |
| `name` | string | No | `blueprints.name` (unique) | — |
| `category` | object\|null | Sí | FK `item_categories` `nullOnDelete` | — |
| `unlock_prerequisite` | string\|null | Sí | columna | — |
| `research_scrolls` | integer\|null | Sí | columna | **No cast** |
| `antique_tokens` | integer\|null | Sí | columna | **No cast** |
| `tier` | integer | No | columna | Sí (`integer`) |
| `gold_value` | integer | No | columna | Sí (`integer`) |
| `craft_time_seconds` | integer | No | columna | Sí (`integer`) |
| `craft_time_formatted` | string\|null | Sí | columna (derivado) | Expuesto (D-4) |
| `value_per_second` | number | Sí | `decimal(14,4)` | Sí (`decimal:4` → convertir a float en presentación) |
| `merchant_xp` | integer | No | columna | **No cast** |
| `merchant_xp_per_second` | number\|null | Sí | `decimal(14,4)` | Sí (`decimal:4` → convertir a float) |
| `worker_xp` | integer | No | columna | **No cast** |
| `fusion_xp` | integer | No | columna | **No cast** |
| `favor` | integer | No | columna | **No cast** |
| `airship_power` | integer | No | columna | **No cast** |
| `stats.{atk,def,hp,eva,crit,mana_shield}` | integer\|null | Sí | columnas `integer` nullable | Sí (`integer`) |
| `energies.{discount,surcharge,suggest,speed_up}` | integer | No | columnas default 0 | Sí (`integer`) |
| `available_as_antique_at` | date-time\|null | Sí | timestamp nullable | Sí (`datetime`) |
| `workers[].level_required` | integer | No | `blueprint_workers` | Sí |
| `resources[].quantity` | integer | No | `blueprint_resources` | Sí |
| `components[].amount` | integer | No | `blueprint_components` | Sí |
| `components[].component.material_type` | enum | No | `crafting_materials` | — |
| `elemental_affinities[].position` | integer enum [1,2] | No | CHECK `position IN (1,2)` | Sí |

**Garantía de tipos (D-8, aprobada):** las columnas sin `cast` explícito (research_scrolls, antique_tokens, merchant_xp, worker_xp, fusion_xp, favor, airship_power y los catálogos Worker/Resource/Quality) deben forzarse a **entero** en la presentación JSON; los decimales (`value_per_second`, `merchant_xp_per_second`) a **número de punto flotante**.

## 7. Verificación de campos y relaciones contra el código real

- **`Blueprint`** (`src/app/Models/Blueprint.php`): 12 relaciones confirmadas (`category`, `elementalAffinities`, `spiritAffinity`, `builtInElement`, `builtInSpirit`, `blueprintWorkers`, `blueprintResources`, `blueprintComponentsAsParent`, `craftingUpgrades`, `starforgedMilestones`, `ascensionUpgrades`, `transcendenceUpgrades`). `casts()` confirmado (§6.5).
- **`blueprints`** (`2026_08_28_100007_create_blueprints_table.php`): columnas y nulabilidad coinciden; **no existe** `elemental_affinity_id` (eliminada en D-3).
- **Importante:** `blueprint_components.component_id` cambió de destino. La migración inicial apunta a `blueprints`, pero `2026_10_14_000002_change_blueprint_components_component_id_foreign.php` la re-apunta a **`crafting_materials`**. Por tanto `components[].component` es un `CraftingMaterial` (4 tipos: `blueprint | quest_component | sigil | enchantment`).
- **Catálogos** (`workers`, `resources`, `item_categories`, `qualities`, `elements`, `enchantments`, `spirit_affinities`, `spirits`): campos confirmados. Los modelos `Worker`, `Resource`, `Quality`, `Element`, `Enchantment`, `Spirit`, `SpiritAffinity` **no declaran `casts()`**.
- **`crafting_materials`** (`2026_10_14_000001`): `material_type` enum + CHECK; `source_key` unique; `blueprint_id` nullable solo para tipo `blueprint`.
- **`blueprint_elemental_affinities`** (`2026_10_16_000004`): `position` con CHECK `IN (1,2)`; `raw_value` marcado como columna de auditoría (no se expone).

## 8. Correcciones de esta revisión y aclaraciones

1. **Parámetro `include` (`docs/openapi/blueprints-v1.yaml`).** Antes era `type: string` con `enum` de valor único, incompatible con la lista separada por comas documentada (el ejemplo `workers,resources,...` no validaba). **Corregido:** `type: array`, `items.enum` (whitelist por elemento), `style: form`, `explode: false` → serialización `include=a,b,c`. Verificación automática: *ningún* parámetro de `query` queda como `string`+`enum` de valor único.
2. **Filtros con operadores (D-6).** Antes los operadores se describían en prosa pero **no** existían como parámetros. **Corregido:** se añadieron los parámetros explícitos `filter[campo][gt]`, `[gte]`, `[lt]`, `[lte]` para los cinco campos numéricos (`tier`, `gold_value`, `craft_time_seconds`, `merchant_xp`, `airship_power`); `filter[campo]` se documenta como `eq` (equivalente a `filter[campo][eq]`). Se añadió un **ejemplo de solicitud exacto** en la operación y en este reporte (§6.1). Justificación de la representación: OpenAPI 3.0.3 no permite expresar `filter[campo][op]` de forma genérica (`deepObject` solo cubre un nivel), por lo que se enumeran los nombres.
3. **Coherencia de ejemplos.** Se revisaron los ejemplos existentes: `filter[element]=Dark`, `filter[resource]=Iron`, `filter[worker]=Blacksmith` son valores reales (elemento AZ, recurso y `worker_key` confirmados en el código/seeders). No se inventaron valores para `spirit_affinity`/`built_in_element`/`built_in_spirit`; esos parámetros quedan sin `example`.
4. **Ubicación de reportes.** Los reportes residen en `docs/reports/` (su ubicación actual). En esta revisión **no** se movieron, restauraron ni eliminaron archivos.
5. **Convención de nombres (`include` y claves).** El reporte de auditoría usó camelCase en los ejemplos de `include`. **Resuelto:** el contrato unifica en **snake_case** por decisión **D-1/D-2 (aprobadas)**.
6. **Comentario desactualizado en migración.** `2026_08_28_100010_create_blueprint_components_table.php` aún declara `->constrained('blueprints')` para `component_id`; el destino real es `crafting_materials` (por `000002`). No se modifica (prohibido); se documenta.
7. **Tipo del ejemplo de `include`.** El esquema es `type: array`, pero `example` estaba declarado como **cadena** (`workers,resources,components,elemental_affinities`). **Corregido:** `example` pasa a ser una **lista** (`["workers", "resources", "components", "elemental_affinities"]`), coherente con el esquema. Se **conserva** la serialización CSV mediante `style: form` y `explode: false`, y **no** se alteró la whitelist (11 relaciones) ni el resto del contrato.

## 9. Decisiones D-1..D-8 (APROBADAS)

El representante humano aprobó las ocho decisiones. Estado registrado en el OpenAPI como `x-approved-decisions` (ya no hay `x-pending-decisions`).

| ID | Decisión | Resultado aprobado |
|---|---|---|
| D-1 | Convención de claves JSON | `snake_case` en todas las respuestas. |
| D-2 | Nombres de `include` | `snake_case` (workers, resources, components, elemental_affinities, spirit_affinity, built_in_element, built_in_spirit, crafting_upgrades, starforged_milestones, ascension_upgrades, transcendence_upgrades). |
| D-3 | Agrupación de stats/energías | Agrupadas en `stats` y `energies`. |
| D-4 | Campos derivados | Se exponen `craft_time_formatted`, `value_per_second`, `merchant_xp_per_second`. |
| D-5 | Paginación | `per_page` por defecto 25, máximo 100. |
| D-6 | Operadores de filtro | `filter[campo][op]` con `eq`, `gt`, `gte`, `lt`, `lte`; búsqueda parcial para campos de texto. |
| D-7 | Campos internos | Se excluyen `raw_value`, `id` de tablas pivote y `created_at`/`updated_at`. |
| D-8 | Tipos en JSON | Enteros como entero; decimales como número de punto flotante. |

## 10. Validaciones ejecutadas

| Validación | Comando | Resultado |
|---|---|---|
| Estado de Git (antes) | `git status --short`, `git log --oneline -3` | HEAD `f0cb37c`; sin cambios en archivos versionados. |
| Sintaxis YAML del OpenAPI | `python3` + `yaml.safe_load` | **OK** (parseo correcto). |
| Resolución de `$ref` locales | script Python | **75/75** resueltas, 0 sin resolver. |
| Estructura OpenAPI | script Python | 2 `paths`, 26 esquemas, 41 parámetros; cada operación con `responses` y `operationId`. |
| Parámetros del listado | script Python | 40 parámetros en `GET /blueprints` (incluye 20 operadores `gt/gte/lt/lte`); 2 en `GET /blueprints/{id}`. |
| `include` multi-valor | script Python | `type: array`, `style: form`, `explode: false`, `items.enum` de 11 relaciones. |
| Tipo del ejemplo de `include` | script Python | `example` es **lista** (`list` de 4 `str`), coherente con `type: array`; serialización CSV intacta. |
| Incompatibilidad `string`+`enum` | script Python | **0** parámetros de `query` con `string`+`enum` de valor único. |
| Cierre de decisiones | script Python | `x-pending-decisions` **ausente**; `x-approved-decisions` con **8/8** en estado `APROBADA`; `version: 1.0.0`. |
| Coherencia de texto | script Python | Sin ocurrencias de "pendiente" en la especificación. |

**Verificación estática (no ejecución):** `php -l`, Pint y tests **NO** se ejecutaron, porque esta etapa no crea ni modifica código PHP. Se declara explícitamente para no afirmar pruebas inexistentes.

## 11. Limitaciones

- No se consultó PostgreSQL en esta etapa (restricción). Los conteos y ejemplos provienen del código/esquema, no de consultas.
- El contrato está **aprobado** (`version: 1.0.0`); los endpoints aún **no** están implementados.
- No se validó la especificación con un validador OpenAPI formal (p. ej. Spectral), al no poder instalar dependencias; sí se validó el YAML, la resolución de referencias y la estructura.

## 12. Plan siguiente (no autorizado)

**Etapa 2** sería la infraestructura mínima (registrar `routes/api.php` versionado `/api/v1` y un controlador con los dos endpoints), quedando **pendiente de aprobación explícita** antes de escribir código.

## 13. Resultado final

Contrato documentado, verificado contra el código real y **cerrado** con las ocho decisiones aprobadas. En esta revisión se corrigieron el parámetro `include` (lista separada por comas) y la representación de los operadores de filtro (parámetros explícitos), y se comprobó la coherencia de descripciones, parámetros y ejemplos. **No se implementaron endpoints ni se modificó PostgreSQL.** Estado de seguimiento: **IMPLEMENTADA — PENDIENTE DE REVISIÓN**.
