# Reporte Técnico — Implementación del filtro de Blueprints por componentes

**Fecha:** 2026-10-26
**Autor:** Editor IA
**Estado:** IMPLEMENTADA Y VALIDADA

## 1. Objetivo

Añadir el filtro `filter[component]` al endpoint existente `GET /api/v1/blueprints`, con **coincidencia exacta por nombre** del material componente, respetando el contrato vigente, manteniendo el alcance del MVP y sin alterar los datos existentes.

## 2. Alcance

**Incluye:**
- Alta de `component` en la whitelist de filtros (`BlueprintIndexRequest`).
- Filtro relacional en `BlueprintController` siguiendo el patrón `whereHas` existente, sobre la relación `blueprintComponentsAsParent.component`, con igualdad exacta sobre `crafting_materials.name`.
- Operador único `eq`, admitiendo las dos formas del contrato: `filter[component]=Ruby` y `filter[component][eq]=Ruby`.
- Documentación del parámetro en `docs/openapi/blueprints-v1.yaml`.
- Pruebas en `BlueprintApiTest`: coincidencia exacta, ausencia de coincidencias, equivalencia de sintaxis, rechazo de operadores no permitidos (`422`), ausencia de duplicados, combinación `AND` con otros filtros, comportamiento ante mayúsculas/minúsculas y no regresión.
- Informe físico y actualización de `AI_WORK_LOG.md`.

**Excluye:**
- Filtro por `material_type` (DP-D: no se añade en esta etapa).
- Índices nuevos (DP-E: no se crean sin evidencia de rendimiento).
- Cambios en modelos, relaciones o migraciones.
- Escrituras en PostgreSQL, migraciones, seeders, importadores, restauraciones, commits o push.

## 3. Estado inicial

- `component` no existía en `BlueprintIndexRequest::FILTERS` ni en el `switch` de `BlueprintController::applyFilter()`; el contrato OpenAPI no declaraba el parámetro.
- La relación padre de componentes ya existía: `Blueprint::blueprintComponentsAsParent()` ([Blueprint.php#L101-L104](file:///home/draven/Documentos/shop-titans-api/src/app/Models/Blueprint.php#L101-L104)), y `BlueprintComponent::component()` → `belongsTo(CraftingMaterial::class, 'component_id')` ([BlueprintComponent.php#L29-L32](file:///home/draven/Documentos/shop-titans-api/src/app/Models/BlueprintComponent.php#L29-L32)).
- Filtro análogo vigente: `resource` ([BlueprintController.php#L142-L144](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php#L142-L144)).

## 4. Archivos modificados / creados

| Archivo | Acción | Motivo |
|---|---|---|
| `src/app/Http/Requests/BlueprintIndexRequest.php` | Modificado | Alta de `'component' => 'exact'` en `FILTERS` |
| `src/app/Http/Controllers/Api/V1/BlueprintController.php` | Modificado | Caso `component` con `whereHas` + igualdad exacta por `name` |
| `src/tests/Feature/BlueprintApiTest.php` | Modificado | 4 pruebas nuevas del filtro + helper `createComponent()` |
| `docs/openapi/blueprints-v1.yaml` | Modificado | Parámetro `FilterComponent` (definición y referencia) |
| `docs/reports/2026-10-26_implementacion_filtro_blueprints_por_componentes.md` | Creado | Informe de esta tarea |
| `AI_WORK_LOG.md` | Modificado | Registro del hito |

**No** se modificaron modelos, relaciones, migraciones, seeders, importadores, `phpunit.xml` ni copias de seguridad.

## 5. Cambios realizados

1. **Whitelist de filtros** — [BlueprintIndexRequest.php#L41-L43](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Requests/BlueprintIndexRequest.php#L41-L43):
   ```php
   'resource' => 'exact',
   'component' => 'exact',
   'worker' => 'exact',
   ```
   Al ser familia `exact`, `allowedOperators()` restringe a `eq`; el valor cae en la regla por defecto `['string']` ([valueRules(), #L158-L167](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Requests/BlueprintIndexRequest.php#L158-L167)).

2. **Filtro relacional** — [BlueprintController.php#L146-L152](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php#L146-L152):
   ```php
   case 'component':
       // Coincidencia exacta por nombre del material componente (H-5). Abarca
       // todos los `material_type` de `crafting_materials` (DP-D): no se acota
       // por tipo, de modo que nombres homónimos pueden producir coincidencias
       // ambiguas (documentado en el contrato OpenAPI).
       $query->whereHas('blueprintComponentsAsParent.component', fn ($relation) => $relation->where('name', $value));
       break;
   ```
   Es idéntico en forma al filtro `resource`, garantizando coherencia con el patrón vigente. El uso de `whereHas` (subconsulta `EXISTS`) evita duplicados de blueprint.

3. **Contrato OpenAPI** — nuevo parámetro `FilterComponent` ([blueprints-v1.yaml#L625-L640](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L625-L640)) y su referencia en la lista de parámetros del endpoint ([blueprints-v1.yaml#L124](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L124)). La descripción documenta: coincidencia **exacta** por nombre, equivalencia `[eq]` (D-6), cobertura de **todos** los `material_type` (DP-D), posible ambigüedad ante homónimos y sensibilidad a mayúsculas/minúsculas.

4. **Pruebas** — en [BlueprintApiTest.php](file:///home/draven/Documentos/shop-titans-api/src/tests/Feature/BlueprintApiTest.php#L234-L325):
   - `test_index_filters_by_component_exact_name`: coincidencia exacta, equivalencia `[eq]`, ausencia de coincidencias, insensibilidad descartada (mayúsculas/minúsculas) y no confusión con el nombre del blueprint.
   - `test_index_component_filter_does_not_duplicate_blueprints`: dos filas del mismo componente con distinta `quality` → el blueprint aparece una sola vez.
   - `test_index_combines_component_filter_with_other_filters`: combinación `AND` con `filter[tier][gte]`.
   - `test_index_rejects_disallowed_operators_for_component`: `gt`/`lte` → `422`; `eq` → `200`.
   - Helper `createComponent()` ([BlueprintApiTest.php#L542-L549](file:///home/draven/Documentos/shop-titans-api/src/tests/Feature/BlueprintApiTest.php#L542-L549)).

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| El filtro exacto por nombre devuelve solo los blueprints con ese componente | `test_index_filters_by_component_exact_name` → `total = 1` | Confirmada |
| `filter[component][eq]` equivale a `filter[component]` | `test_index_filters_by_component_exact_name` → `total = 1` | Confirmada |
| `whereHas` no duplica blueprints con múltiples filas coincidentes | `test_index_component_filter_does_not_duplicate_blueprints` → `total = 1`, `data` con 1 elemento | Confirmada |
| Se compone con otros filtros mediante `AND` | `test_index_combines_component_filter_with_other_filters` → `total = 1` | Confirmada |
| Los operadores distintos de `eq` se rechazan con `422` | `test_index_rejects_disallowed_operators_for_component` | Confirmada |
| La comparación es sensible a mayúsculas/minúsculas | `filter[component]=ruby` → `total = 0` sobre `Ruby` | Confirmada |
| No hay regresión en la suite de la API | `23 passed (179 assertions)` | Confirmada |

## 7. Discrepancias

Ninguna.

## 8. Decisiones

### Propuestas
- Ninguna nueva.

### Aprobadas
- **H-5 (2026-10-26):** añadir `filter[component]` (coincidencia exacta por nombre).
- **DP-D (2026-10-26):** no añadir filtro por `material_type` en esta etapa; documentar la ambigüedad por homónimos.
- **DP-E (2026-10-26):** no crear índices sin evidencia de rendimiento.

### Reemplazadas/Rechazadas
- Ninguna.

## 9. Validaciones

Única ronda de validación (según el alcance).

```bash
docker compose exec -T app php artisan test --filter=BlueprintApiTest
docker compose exec -T app php -l app/Http/Controllers/Api/V1/BlueprintController.php
docker compose exec -T app php -l app/Http/Requests/BlueprintIndexRequest.php
docker compose exec -T app php -l tests/Feature/BlueprintApiTest.php
docker compose exec -T app vendor/bin/pint --test app/Http/Controllers/Api/V1/BlueprintController.php app/Http/Requests/BlueprintIndexRequest.php tests/Feature/BlueprintApiTest.php
python3 -c "import yaml; ..."   # parseo y verificación de FilterComponent
```

Resultados:

```text
# php artisan test --filter=BlueprintApiTest
Tests:    23 passed (179 assertions)
Duration: 1.18s
exit=0

# php -l (3 archivos)
No syntax errors detected in app/Http/Controllers/Api/V1/BlueprintController.php
No syntax errors detected in app/Http/Requests/BlueprintIndexRequest.php
No syntax errors detected in tests/Feature/BlueprintApiTest.php

# vendor/bin/pint --test (3 archivos)
PASS ........................................................... 3 files
exit=0

# Parseo YAML (PyYAML)
YAML OK, params count: 41
FilterComponent referenced: 1
FilterComponent defined: True
name: filter[component]
```

**Aislamiento:** las pruebas se ejecutaron con `src/phpunit.xml` sin cambios (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, con `force="true"` en `<env>` y `<server>`). No se alteró la configuración de aislamiento.

## 10. Resultado final

- Filtro `filter[component]` operativo con coincidencia exacta por nombre de material componente, abarcando todos los `material_type` (DP-D).
- Contrato OpenAPI actualizado y coherente (`FilterComponent` definido y referenciado; YAML válido).
- Suite de la API en verde: **23 passed (179 assertions)**.
- Sin cambios en modelos, relaciones, migraciones ni configuración.

## 11. Riesgos

- **R-1 — Ambigüedad por homónimos:** `crafting_materials.name` no es único; materiales de distinto `material_type` con el mismo nombre producirían coincidencias simultáneas. Aceptado por DP-D y **documentado** en el contrato OpenAPI.
- **R-2 — Sensibilidad a mayúsculas/minúsculas:** la igualdad exacta depende de la base de datos (sensible por defecto en PostgreSQL y SQLite). Cubierto por una prueba y documentado.
- **R-3 — Rendimiento no medido:** no se midió `EXPLAIN ANALYZE`; por DP-E no se añaden índices. Queda como medición pendiente si se observa degradación.

## 12. Pendientes

- Ninguno obligatorio para esta tarea.
- Opcional/futuro (requiere decisión): filtro por `material_type`, medición de rendimiento con `EXPLAIN ANALYZE` y eventual índice.

## 13. Preguntas para ChatGPT

- ¿Se desea, en una etapa posterior, acotar `filter[component]` por `material_type` o habilitar búsqueda parcial?
- ¿Existe un caso de uso que justifique medir el rendimiento del `EXISTS` sobre el dataset real?

## 14. Próximo paso recomendado

1. Revisar el informe y, si procede, aprobarlo.
2. Mantener el filtro en el MVP tal como está; no crear índices hasta disponer de medición.

## 15. Confirmaciones de seguridad

- **PostgreSQL no fue alterado:** no se ejecutaron migraciones, seeders, importadores, restauraciones ni comandos destructivos; no hubo escrituras ni modificaciones de datos. Las pruebas corrieron aisladas en SQLite `:memory:`.
- **Copias de seguridad intactas:** no se crearon, modificaron ni eliminaron respaldos.
- **Sin dependencias nuevas, sin commits y sin push.**
- **Modelos, relaciones y migraciones sin cambios.**

## 16. Desviaciones o bloqueos

- **Desviación operativa (no de alcance):** durante la edición del contrato OpenAPI, dos cambios enviados en paralelo sobre el mismo archivo provocaron que la inserción de la referencia `FilterComponent` se perdiera y fuera sobrescrita. Se detectó al validar el número de parámetros (40 en lugar de 41) y se reaplicó secuencialmente; la validación final confirma **41 parámetros** y la referencia presente. Impacto: ninguno en el resultado final. Lección: no editar el mismo archivo con dos llamadas de edición paralelas.
- No se identificó ningún bloqueo real que requiriera ampliar el alcance.
