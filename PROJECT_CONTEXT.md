# Shop Titans API — Project Context v2.1

**Estado:** Activo | **Fecha:** 2026-08-28

## Fuente de verdad
Este archivo contiene únicamente arquitectura y decisiones técnicas aprobadas. Las propuestas del Editor IA no son oficiales hasta aprobación explícita.

Si contradice al código real, la discrepancia debe reportarse y verificarse.

## Objetivo
Construir una API para consultar datos estructurados de Shop Titans.

`Excel → Importación → Transformación → Validación → PostgreSQL → Laravel → API`

## Stack
- Laravel
- PHP 8.4
- PostgreSQL 17
- Docker / Docker Compose
- Git

No usar Laravel Sail salvo aprobación explícita.

## Filosofía
Que funcione, sea entendible, mantenible y escalable, evitando sobrearquitectura.

## Estado validado
- Docker Compose operativo.
- Laravel → PostgreSQL validado.
- MVP Blueprints: 14 migraciones ejecutadas y PostgreSQL validado.
- Seeders de catálogos del MVP, importadores (`import:blueprints`, `import:blueprint-components`) y API pública `/api/v1` de Blueprints: implementados.

## MVP Blueprints
Catálogos:
- workers
- worker_levels
- resources
- item_categories
- elements
- qualities

Principal:
- blueprints

Relaciones:
- blueprint_workers
- blueprint_resources
- blueprint_components
- blueprint_crafting_upgrades
- blueprint_starforged_milestones
- blueprint_ascension_upgrades
- blueprint_transcendence_upgrades

`blueprint_components.component_id` → `crafting_materials.id` (FK, `restrictOnDelete`); antes apuntaba a `blueprints.id` (migración `2026_10_14_000002`).

`blueprints.unlock_prerequisite` → varchar(255) nullable, sin FK hacia workers.

Skills y Enchantments quedan fuera del MVP.

## Decisiones activas
- `blueprint_workers.level_required` → unsignedInteger.
- `qualities.rank` → unsignedTinyInteger NOT NULL UNIQUE.
- Sin `$with` global.
- Sin índices secundarios hasta conocer consultas reales.
- `blueprint_components.component_id` → RESTRICT.
- `resources.column_letter_in_excel` → UNIQUE.

### Aislamiento de pruebas y protección de PostgreSQL (aprobado 2026-10-23)
- **P-1:** se conserva el bloque `<server>` de `src/phpunit.xml`.
- **P-2:** el aislamiento de PHPUnit depende de `force="true"` en `<env>` **junto con** el bloque `<server>`; **no** depende del flag `--env=testing` por sí solo.
- **P-3:** guarda contra comandos destructivos: `migrate:fresh`, `migrate:refresh` y `db:wipe` se bloquean (exit code 1) cuando la conexión efectiva resuelve a driver `pgsql` y base `shoptitans` (`App\Console\DestructiveCommandGuard`, registrada como listener de `CommandStarting` en `AppServiceProvider`).

## Reglas
Una hipótesis sobre datos externos debe verificarse contra datos reales antes de convertirse en FK, enum, restricción, clasificación, relación o lógica de importación.

Una propuesta no es una decisión.

Estados:
`PROPUESTA → EN IMPLEMENTACIÓN → IMPLEMENTADA → PENDIENTE DE REVISIÓN → APROBADA → EJECUTADA → VALIDADA`

También puede quedar `BLOQUEADA`.

Distinguir siempre aprobación para implementar, aprobación para ejecutar, ejecución y validación.

## Documentación
- `PROJECT_CONTEXT.md`: decisiones oficiales.
- `AI_EDITOR_INSTRUCTIONS.md`: reglas operativas.
- `AI_WORK_LOG.md`: estado/comunicación.
- `CHANGELOG.md`: cambios aprobados.
- `docs/reports/`: reportes detallados.
- `docs/adr/`: decisiones arquitectónicas.
- `README.md`: guía humana.
- `CONTRIBUTING.md`: colaboración.
