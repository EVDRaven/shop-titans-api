# AI Work Log

Bitácora compartida entre Usuario, ChatGPT y Editor IA. Las propuestas no son decisiones aprobadas hasta confirmación explícita.

## Estado actual
**Fase:** MVP Blueprints — Seeders de catálogos (ejecutados y validados).
**Estado:** 🟢 Seeders ejecutados y validados en PostgreSQL. `elements` pendiente de decisión.
**Siguiente tarea:** Resolver modelado de `elements` antes del `BlueprintImporter`.

## Último hito — 2026-10-13 (ejecución y validación)
Se ejecutó `php artisan db:seed` correctamente y se validaron los 5 catálogos en PostgreSQL:
- `resources` = 12 ✅
- `qualities` = 5 ✅
- `item_categories` = 40 ✅ (excluye `Enchantment`)
- `workers` = 19 ✅
- `worker_levels` = 50 ✅

`elements` se mantiene como pendiente de decisión (sin resolver).

Reporte:
`docs/reports/2026-10-13_seeders_catalogos_mvp_blueprints.md`

## Hito anterior — 2026-10-13 (revisión)
Se corrigieron los seeders según observaciones de la IA arquitecta:
- `ItemCategorySeeder`: se excluyó `Enchantment` del catálogo MVP (40 categorías).
- `resources.tier`: clarificado como clasificación propia del recurso (T1–T5), no el tier de blueprint.
- Reporte actualizado con evidencias de Excel v26.9.1.
- Documentadas dudas sobre `xp_needed` duplicado (niveles 35/36) y calidades Epic/Legendary.
- Confirmado orden de `DatabaseSeeder` sin dependencias de FK entre los 5 catálogos.
- **Restaurado `User::factory()`** en `DatabaseSeeder` (la eliminación no tenía justificación técnica concreta).

Reporte:
`docs/reports/2026-10-13_seeders_catalogos_mvp_blueprints.md`

## Hito previo — 2026-10-13
Se implementaron 5 seeders de catálogos (resources, qualities, item_categories, workers, worker_levels) con datos reales del Excel. Validados con `php -l`, `composer dump-autoload` y `pint --test`. **No ejecutados.** `elements` registrado como pendiente opcional.

## Hito previo — 2026-08-28
Se ejecutaron las 14 migraciones del MVP Blueprints. PostgreSQL quedó con 23 tablas totales y 16 FK validadas.

Reporte:
`docs/reports/2026-08-28_migrate_real_mvp_blueprints.md`

## Estado de tareas
- Análisis Excel: 🟢 VALIDADO
- Modelo Blueprints: 🟢 APROBADO
- Migraciones/modelos: 🟢 APROBADO
- Migración PostgreSQL: 🟢 EJECUTADA / VALIDADA
- Seeders catálogos: 🟢 EJECUTADA / VALIDADA (resources=12, qualities=5, item_categories=40, workers=19, worker_levels=50)
- Elements: ⏳ PENDIENTE DE DECISIÓN (no resuelto)
- BlueprintImporter: ⏳ PENDIENTE
- API: ⏳ PENDIENTE
- Tests: ⏳ PENDIENTE

## Próximo paso
Resolver el modelado de `elements` (pendiente de decisión) antes de implementar:
- `BlueprintImporter` (2 pasadas para componentes recursivos)
- Validar `qualities` en contexto Epic/Legendary
- Revisar `worker_levels.xp_needed` duplicado en niveles 35/36

## Regla
El detalle de cada tarea vive en `docs/reports/`. Este archivo resume estado, decisiones y próximos pasos.
