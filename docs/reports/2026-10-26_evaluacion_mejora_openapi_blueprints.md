# Reporte Técnico — Evaluación de mejora de OpenAPI (Blueprints MVP)

**Fecha:** 2026-10-26
**Autor:** Editor IA
**Estado:** PROPUESTA (evaluación completada; **sin implementar**)

---

## 1. Objetivo

Evaluar cómo mejorar la documentación **OpenAPI 3.0.3** del MVP de Blueprints (`docs/openapi/blueprints-v1.yaml`) para que los consumidores comprendan las **respuestas reales** de la API, **sin ampliar el alcance funcional ni modificar la implementación**. Se identifican las respuestas documentadas, las que carecen de ejemplos concretos, los ejemplos recomendados (con su origen verificable) y los archivos que se modificarían en una futura implementación.

## 2. Alcance

**Incluye:** inspección de solo lectura del contrato OpenAPI, rutas, controlador, *Form Requests*, recurso JSON, modelo `Blueprint`, pruebas de feature y documentación (`README.md`, `CHANGELOG.md`, `PROJECT_CONTEXT.md`); verificaciones **no destructivas** del contrato y del comportamiento de serialización del framework; matriz de cobertura; ejemplos recomendados; riesgos y criterios de validación.

**Excluye:** editar o crear archivos de implementación; modificar el YAML OpenAPI; migraciones, seeders, importadores o escrituras en PostgreSQL; dependencias nuevas; cambios en `.env`, Docker Compose, pruebas o configuración; commits y push.

## 3. Estado actual del contrato

| Atributo | Valor | Evidencia |
|---|---|---|
| Versión OpenAPI | **3.0.3** | [blueprints-v1.yaml#L1](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L1) |
| `info.version` | 1.0.0 | [blueprints-v1.yaml#L5](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L5) |
| `servers` | `/api/v1` (relativo al host) | [blueprints-v1.yaml#L37-L39](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L37-L39) |
| Endpoints | 2 (`GET /blueprints`, `GET /blueprints/{id}`) | [blueprints-v1.yaml#L84-L188](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L84-L188) |
| Esquemas | 26 en `components.schemas` | [blueprints-v1.yaml#L667-L1102](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L667-L1102) |
| Parámetros | 41 en `components.parameters` | [blueprints-v1.yaml#L191-L665](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L191-L665) |
| Respuestas reutilizables | 3 (`NotFound`, `ValidationError`, `ServerError`) | [blueprints-v1.yaml#L1104-L1121](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L1104-L1121) |

**Lo que ya está documentado:** esquemas completos de las respuestas; `example` a nivel de **campo** en algunos esquemas (`id`, `name`, `tier`, `gold_value`, etc.); `example` a nivel de **parámetro** (`sort`, `include`, filtros); un **ejemplo de solicitud** en texto (`GET /api/v1/blueprints?…`) en la descripción del listado ([blueprints-v1.yaml#L103-L107](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L103-L107)).

**Lo que NO está documentado:** **ejemplos a nivel de respuesta** (`example`/`examples` en el objeto *media type*) para ninguna de las respuestas: 200 listado, 200 detalle, 422, 404 ni 500. Tampoco hay un ejemplo concreto del payload de `errors`, ni valores concretos de `meta`/`links`.

## 4. Inventario de endpoints y respuestas revisadas

| Endpoint | Respuesta | Esquema referenciado | Ejemplo a nivel de respuesta |
|---|---|---|---|
| `GET /api/v1/blueprints` | 200 | `BlueprintListResponse` (`data`,`meta`,`links`) | ❌ Ausente |
| `GET /api/v1/blueprints` | 422 | `ValidationErrorResponse` | ❌ Ausente |
| `GET /api/v1/blueprints` | 500 | `Error` | ❌ Ausente |
| `GET /api/v1/blueprints/{id}` | 200 | `BlueprintResponse` (`data`) | ❌ Ausente |
| `GET /api/v1/blueprints/{id}` | 404 | `Error` | ❌ Ausente |
| `GET /api/v1/blueprints/{id}` | 422 | `ValidationErrorResponse` | ❌ Ausente |
| `GET /api/v1/blueprints/{id}` | 500 | `Error` | ❌ Ausente |

## 5. Matriz de cobertura

Leyenda de evidencia: **T** = aserción directa en pruebas; **C** = código fuente; **F** = código del framework (Laravel 13.8); **R** = respuesta real capturada en la validación HTTP previa (`docs/reports/2026-10-26_validacion_acceso_http_docker_blueprints.md` y `/tmp/api1.json`).

| Respuesta | Evidencia disponible | Documentación actual | Mejora propuesta |
|---|---|---|---|
| 200 listado (`data`,`meta`,`links`) | **T** `BlueprintApiTest::test_index_returns_paginated_payload_with_default_category`; **R** respuesta real | Esquema `BlueprintListResponse` sin ejemplo | Añadir `example` con `data` (1 ítem) + `meta` + `links` reales |
| 200 detalle (`data`) | **T** `test_show_returns_blueprint_with_requested_includes`; **R** | Esquema `BlueprintResponse` sin ejemplo | Añadir `example` de `{data: Blueprint}` |
| 200 detalle con `include` | **T** mismas aserciones de relaciones (`workers`, `resources`, `components`, `elemental_affinities`) | Descripciones de relación; sin ejemplo | Ejemplo (opcional) de `include` con las formas reales |
| Filtros / orden | **T** `test_index_applies_numeric_operators_and_eq_default`, `..._partial_search_...`, `..._combines_filters`, `..._filters_by_relations`, `..._filters_by_component_exact_name`, `test_index_sorts_by_allowed_fields` | Descripciones por parámetro + ejemplo de solicitud textual | Ejemplos en el `example` de cada parámetro (algunos ya existen) + ejemplo de solicitud como `parameters[]` |
| 422 (validación) | **T** varios `assertStatus(422)`; **C** `BlueprintIndexRequest`; **R** cuerpo real de `filter[no_existe]`; **F** `ValidationException::summarize()` | `ValidationErrorResponse` con `message.example` **inexacto** (ver H-1) | Corregir `message` y añadir `example` con `errors` |
| 404 (inexistente) | **T** `test_show_returns_404_when_missing` (solo estructura `[message]`); **F** `Handler::prepareException` + `convertExceptionToArray` | `Error` (`{message}`) sin ejemplo | Añadir `example` con el mensaje real; documentar diferencia debug/producción |
| 500 | **C** (no hay catch explícito); **F** `convertExceptionToArray` | `Error` (`{message}`) sin ejemplo | Añadir `example` `{"message":"Server Error"}` (no-debug) |
| `include` inválido → 422 | **T** `test_index_rejects_disallowed_sort_and_include`, `test_include_rejects_nested_or_array_structures`; **C** `BlueprintRequest::validateInclude` | Restricciones en descripción de `Include` | Cubierto por el ejemplo 422 |

## 6. Ejemplos recomendados (con origen verificable)

> Convención: todos los ejemplos respetan el formato vigente (`{data,meta,links}` / `{data}`), claves `snake_case` (D-1) y tipos D-8. Los *hosts* de `links` son **absolutos y derivados de la petición**; en el ejemplo se muestran tal como los produjo la API real.

### 6.1 `GET /api/v1/blueprints` → 200 (verificable — **R**)

Origen: respuesta real capturada (`per_page=1`) en la validación HTTP previa.

```json
{
  "data": [
    {
      "id": 59,
      "name": "Squire Sword",
      "category": { "id": 536, "name": "Sword" },
      "unlock_prerequisite": "Blacksmith",
      "research_scrolls": null,
      "antique_tokens": null,
      "tier": 1,
      "gold_value": 50,
      "craft_time_seconds": 15,
      "craft_time_formatted": "00:00:15",
      "value_per_second": 3.33,
      "merchant_xp": 135,
      "merchant_xp_per_second": 9,
      "worker_xp": 2,
      "fusion_xp": 10,
      "favor": 1,
      "airship_power": 13,
      "stats": { "atk": 16, "def": null, "hp": null, "eva": null, "crit": null, "mana_shield": null },
      "energies": { "discount": 5, "surcharge": 15, "suggest": 5, "speed_up": 15 },
      "available_as_antique_at": null
    }
  ],
  "meta": { "current_page": 1, "per_page": 1, "total": 1549, "last_page": 1549, "from": 1, "to": 1 },
  "links": {
    "first": "http://127.0.0.1:8001/api/v1/blueprints?page=1",
    "last": "http://127.0.0.1:8001/api/v1/blueprints?page=1549",
    "prev": null,
    "next": "http://127.0.0.1:8001/api/v1/blueprints?page=2"
  }
}
```

### 6.2 `GET /api/v1/blueprints/{id}` → 200 (verificable — **T**/**R**)

Origen: estructura verificada por `test_show_returns_blueprint_with_requested_includes` y `BlueprintResource::toArray()`; valores del dataset real (`id=59`) del listado anterior. Sin `include`, las relaciones no aparecen (solo `category`).

```json
{
  "data": {
    "id": 59,
    "name": "Squire Sword",
    "category": { "id": 536, "name": "Sword" },
    "unlock_prerequisite": "Blacksmith",
    "research_scrolls": null,
    "antique_tokens": null,
    "tier": 1,
    "gold_value": 50,
    "craft_time_seconds": 15,
    "craft_time_formatted": "00:00:15",
    "value_per_second": 3.33,
    "merchant_xp": 135,
    "merchant_xp_per_second": 9,
    "worker_xp": 2,
    "fusion_xp": 10,
    "favor": 1,
    "airship_power": 13,
    "stats": { "atk": 16, "def": null, "hp": null, "eva": null, "crit": null, "mana_shield": null },
    "energies": { "discount": 5, "surcharge": 15, "suggest": 5, "speed_up": 15 },
    "available_as_antique_at": null
  }
}
```

### 6.3 `include` — forma de las relaciones (verificable — **T**)

Origen: aserciones de `test_show_returns_blueprint_with_requested_includes` (los **nombres/valores** provienen del *fixture* de prueba, no del dataset real; los **nombres de campo** provienen de `BlueprintResource`). Se recomienda ilustrar solo la forma de cada elemento de relación:

```json
{
  "workers": [ { "worker": { "id": 1, "worker_key": "Blacksmith", "default_person_name": "Blacksmith" }, "level_required": 3 } ],
  "resources": [ { "resource": { "id": 1, "name": "Iron", "tier": 1 }, "quantity": 2 } ],
  "components": [
    {
      "component": { "id": 1, "name": "Ruby", "material_type": "quest_component", "tier": null, "gold_value": null, "fuse_time_seconds": null, "worker_level_required": null, "worker_xp": null },
      "quality": { "id": 1, "name": "Normal", "rank": 1 },
      "amount": 4
    }
  ],
  "elemental_affinities": [ { "position": 1, "element": { "id": 1, "name": "Dark" } } ]
}
```

Notas verificadas por pruebas: el elemento de `workers`/`resources`/`components` **no** incluye `id` propio (solo el de la referencia anidada); `components[].quality` puede ser `null`; `elemental_affinities[].position` es `1` = prioritario.

### 6.4 `GET /api/v1/blueprints?filter[no_existe]=x` → 422 (verificable — **R**/**C**)

Origen: **respuesta real capturada** en la validación HTTP previa; el mensaje lo genera `BlueprintIndexRequest::validateFilters()`.

```json
{
  "message": "El filtro 'no_existe' no está permitido.",
  "errors": { "filter[no_existe]": ["El filtro 'no_existe' no está permitido."] }
}
```

### 6.5 `GET /api/v1/blueprints/{id}` con id inexistente → 404 (verificable — **F**, requiere confirmación en runtime)

Origen: `Handler::prepareException()` (`ModelNotFoundException` → `NotFoundHttpException($e->getMessage())`) y `ModelNotFoundException` (mensaje `No query results for model [<Model>] <id>`). Con `APP_DEBUG=false` (producción):

```json
{ "message": "No query results for model [App\\Models\\Blueprint] 999999" }
```

⚠️ **No verificado en runtime** (no se levantó servidor). Además, con `APP_DEBUG=true` (valor actual de `src/.env`) el mismo 404 añade claves de diagnóstico (`exception`, `file`, `line`, `trace`); conviene **decidir** si se documenta solo la forma de producción.

### 6.6 500 → (verificable — **F**)

Origen: `convertExceptionToArray()` (rama no-debug). Forma documentable: `{ "message": "Server Error" }`.

## 7. Hallazgos

> Clasificación de la evaluación (no constituyen cambios aprobados). Severidad: **A**lta / **M**edia / **B**aja.

| ID | Sev. | Hallazgo | Evidencia |
|---|---|---|---|
| H-1 | **M** | `ValidationErrorResponse.message.example = "The given data was invalid."` es **inexacto** en Laravel 13: el mensaje superior es el **primer** mensaje de validación (`ValidationException::summarize()`), no la frase genérica (que solo aplica cuando no hay mensajes). | `vendor/.../Validation/ValidationException.php` (`summarize()`); `Handler::invalidJson()` (`message = $exception->getMessage()`) |
| H-2 | **M** | El ejemplo de `Blueprint.id`/`BlueprintId` es `1`, pero **el id 1 no existe** en el dataset real (`/api/v1/blueprints/1` → 404). Un ejemplo de respuesta exitosa con `id: 1` induce a error. | `docs/reports/2026-10-26_validacion_acceso_http_docker_blueprints.md` (id 1 → 404; id 59 → 200) |
| H-3 | **B** | `craft_time_formatted.example = 15s` corresponde al *fixture* de las pruebas; el **valor real** del dataset es `"00:00:15"`. | `/tmp/api1.json`; `BlueprintApiTest::createBlueprint()` (`craft_time_formatted => '15s'`); `Blueprint::$fillable` (columna real) |
| H-4 | **B** | Los decimales con parte fraccionaria cero se serializan **sin** `.0` (p. ej. `merchant_xp_per_second: 9`), aunque el esquema es `number/float`. Es correcto (JSON no distingue int/float), pero conviene reflejarlo en el ejemplo. | Verificación `php json_encode((float)"9.0000") → 9`; `BlueprintResource::floatOrNull()` |
| H-5 | **M** | El 404 no tiene ejemplo y su mensaje real **expone la clase del modelo** (`No query results for model [App\Models\Blueprint] <id>`). Decisión pendiente: documentar la realidad o normalizar el mensaje (esto último **requeriría cambio de código**, fuera de alcance). | `Handler::prepareException()` (`ModelNotFoundException => NotFoundHttpException($e->getMessage())`); `ModelNotFoundException::setModel()` |
| H-6 | **M** | Los `422` pueden mezclar mensajes **en español** (personalizados: «El filtro '…' no está permitido.», «La relación '…' no está permitida en include.») e **inglés** (reglas estándar de Laravel, p. ej. `per_page`/`page`), porque no existe `src/lang/`. Afecta a la redacción de los ejemplos. | `BlueprintIndexRequest::validateFilters/validateInclude`; ausencia de `src/lang/**` |
| H-7 | **B** | `BlueprintResponse`/`BlueprintListResponse` no declaran `additionalProperties: false`; con `APP_DEBUG=true` las respuestas de error pueden incluir claves extra sin violar el contrato. | `convertExceptionToArray()`; `src/.env` (`APP_DEBUG=true`) |

Ningún hallazgo implica un **cambio de alcance funcional**; todos se resuelven en el propio documento OpenAPI (los de decisión H-5 quedan sujetos a aprobación).

## 8. Archivos que se modificarían y propósito

| Archivo | Acción | Propósito |
|---|---|---|
| `docs/openapi/blueprints-v1.yaml` | Modificar | **Único cambio necesario.** Añadir `example` a las respuestas 200 (listado y detalle), 422, 404 y 500; corregir `ValidationErrorResponse.message.example`; opcionalmente actualizar `example` de `Blueprint.id`/`BlueprintId`/`craft_time_formatted` |
| `CHANGELOG.md` | Modificar (si se aprueba) | Registrar la mejora documental del contrato (sección *Changed*) |
| `docs/reports/2026-10-26_mejora_openapi_blueprints.md` | Crear (si se implementa) | Informe de la implementación |
| `AI_WORK_LOG.md` | Actualizar | Bitácora del hito de implementación |

**No** se modifican: controladores, *Form Requests*, recursos, modelos, rutas, migraciones, pruebas, `.env`, `docker-compose.yml`, `phpunit.xml` ni `README.md` (el `README` no enlaza el detalle de respuestas).

## 9. Riesgos, ambigüedades y no verificables

- **No verificable sin runtime:** el **cuerpo exacto** del 404 y del `422` de reglas estándar (mensajes en inglés) no se capturó; se derivan del código del framework. Deben **confirmarse** en la implementación (levantando el servidor o con una prueba que capture el JSON, sin modificar pruebas existentes).
- **Ambigüedad debug/producción:** con `APP_DEBUG=true` los cuerpos de error incluyen claves extra. Definir qué forma se documenta (recomendado: **producción**).
- **Ejemplos de relaciones:** los **valores** del ejemplo `include` provienen del *fixture* de prueba (no del dataset real). Alternativa: generar el ejemplo desde el dataset real (requiere runtime).
- **`links` dependientes del host:** los ejemplos muestran `http://127.0.0.1:8001/...`; en un contrato de API conviene un *host* genérico o una nota explícita.
- **Duplicación:** evitar repetir el ejemplo completo de `Blueprint` en list y detalle; considerar reutilizar mediante `components.examples`.
- **Riesgo nulo de implementación:** se trata de documentación; no altera comportamiento.

## 10. Criterios de validación de una futura implementación

1. El YAML sigue siendo **OpenAPI 3.0.3** y **parsea** sin errores; `$ref` resueltas al 100 % (recuento estable).
2. Se mantienen **2 endpoints, 41 parámetros y 26 esquemas** (sin altas/bajas de alcance).
3. Cada respuesta (`200` listado, `200` detalle, `422`, `404`, `500`) tiene **ejemplo**.
4. Los ejemplos **coinciden con la estructura** declarada por el esquema (`additionalProperties` conforme) y con **aserción de pruebas** o **respuesta real**.
5. `ValidationErrorResponse.message.example` **ya no** usa la frase genérica como valor por defecto; el ejemplo de `errors` usa claves reales (`include`, `filter[...]`).
6. Los ejemplos **no inventan** campos: cada clave aparece en `BlueprintResource`/esquema o en una prueba.
7. Coherencia tipo↔valor: decimales como número (D-8), `snake_case` (D-1), `{data,meta,links}`/`{data}`.
8. `git diff` limitado a `docs/openapi/blueprints-v1.yaml` (+ `CHANGELOG.md` si se aprueba); sin cambios en código.

## 11. Recomendación de alcance mínimo

**Implementar únicamente en `docs/openapi/blueprints-v1.yaml`**, reutilizando los esquemas y las respuestas existentes:

1. `components.responses.NotFound` → añadir `example` (forma de producción, §6.5).
2. `components.responses.ValidationError` → añadir `example` (§6.4) y **corregir** `ValidationErrorResponse.message.example` (H-1).
3. `components.responses.ServerError` → añadir `example` (§6.6).
4. `paths./blueprints.get.responses.200` → añadir `example` (§6.1).
5. `paths./blueprints/{id}.get.responses.200` → añadir `example` (§6.2).
6. (Opcional) ejemplo de relaciones `include` (§6.3) y actualización de `example` de `id` (H-2) y `craft_time_formatted` (H-3).

**Evitar:** nuevos parámetros/endpoints, `components` redundantes, migrar de versión OpenAPI, o introducir manejadores de excepciones (H-5 queda solo **documentado**, no implementado).

## 12. Declaración de no implementación

**No se implementó ningún cambio.** Esta tarea fue **estrictamente de solo lectura**: no se editó ni creó ningún archivo de implementación, **no se modificó el YAML OpenAPI**, no se ejecutaron migraciones, seeders, importadores ni operaciones de escritura en PostgreSQL, no se añadieron dependencias, no se alteraron `.env`, Docker Compose, pruebas ni configuración, y **no se realizaron commits ni push**. Las únicas verificaciones ejecutadas fueron **no destructivas** (lectura de código/pruebas, `grep`, y una comprobación de serialización `json_encode` en el contenedor `app`).

## 13. Preguntas para ChatGPT

- ¿Se aprueba el **alcance mínimo** (§11) limitado a `docs/openapi/blueprints-v1.yaml`?
- ¿Se documenta la forma de error de **producción** (`APP_DEBUG=false`) o también la de desarrollo?
- ¿Se acepta el ejemplo de relaciones basado en el *fixture* de pruebas, o se prefiere generarlo desde el dataset real (requiere runtime)?
- Respecto a H-5 (mensaje 404 que expone la clase del modelo): ¿se documenta tal cual o se evalúa (en una tarea aparte, con cambio de código) normalizarlo?

## 14. Próximo paso recomendado

1. Revisar y aprobar (o corregir) el alcance mínimo y los ejemplos propuestos.
2. Si se aprueba, implementar **solo** los cambios del §11 en `docs/openapi/blueprints-v1.yaml` y validar según §10.
3. **No** iniciar la implementación sin autorización del usuario.
