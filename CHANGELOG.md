# Changelog

Registra cambios aprobados e integrados.

## [Unreleased]

### Added
- Protocolo de colaboración v2.1.
- Estados formales de tareas.
- Verificación obligatoria de hipótesis contra datos reales.
- Protocolo de discrepancias.
- Distinción entre aprobación, ejecución y validación.
- Plantillas de reportes y ADR.
- Guarda `App\Console\DestructiveCommandGuard` (listener de `CommandStarting`) que bloquea `migrate:fresh`, `migrate:refresh` y `db:wipe` contra la base de desarrollo protegida (`pgsql` / `shoptitans`). Decisiones P-1/P-2/P-3 aprobadas el 2026-10-23.
- API pública `/api/v1` de Blueprints (Etapa 2): `GET /api/v1/blueprints` (listado con paginación `page`/`per_page`, filtros, orden `sort` e `include`) y `GET /api/v1/blueprints/{id}` (detalle), con contrato OpenAPI 3.0.3 en `docs/openapi/blueprints-v1.yaml`. Implementada el 2026-10-22; revisada y aprobada el 2026-10-23.
- Filtro `filter[component]` en `GET /api/v1/blueprints`: coincidencia exacta por nombre de material componente (`crafting_materials.name`, operador `eq`, abarcando todos los `material_type`). Decisión H-5 aprobada e implementada el 2026-10-26.
- Contrato OpenAPI de Blueprints: ejemplos de respuesta HTTP para el listado (200 `{data, meta, links}`) y el detalle (200 `{data}`), y para los errores 422, 404 y 500 en formato de producción. Mejora documental incorporada el 2026-10-26.

### Fixed
- API Blueprints: `applyPartialMatch()` deja de concatenar el nombre de columna en SQL crudo y usa `whereLike` (H-1).
- API Blueprints: `applySort()` añade desempate determinista por `id` cuando la columna de orden no es única (H-2).
- Contrato OpenAPI: se documenta la variante `filter[campo][eq]` en los filtros de texto (H-3) y `minimum: 1` en `filter[category_id]` (H-4).
- Contrato OpenAPI de Blueprints: corregidos ejemplos inconsistentes (`Blueprint.id` y `BlueprintId` de `1` a `59`, `craft_time_formatted` de `15s` a `00:00:15`, valores numéricos reales del recurso) y `ValidationErrorResponse.message.example` (ya no usa la frase genérica «The given data was invalid.»). Mejora documental el 2026-10-26.

### Validated
- Laravel → PostgreSQL.
- Migraciones MVP Blueprints.
- 14 tablas MVP.
- 16 foreign keys.
- `blueprint_components.component_id` → `crafting_materials.id` (FK, `restrictOnDelete`); no recursiva (migración `2026_10_14_000002`).
- `qualities.rank` NOT NULL UNIQUE.
