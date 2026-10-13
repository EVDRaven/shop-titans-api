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
- Seeders, BlueprintImporter y API aún no implementados.

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

`blueprint_components.component_id` → `blueprints.id` como FK recursiva.

`blueprints.unlock_prerequisite` → varchar(255) nullable, sin FK hacia workers.

Skills y Enchantments quedan fuera del MVP.

## Decisiones activas
- `blueprint_workers.level_required` → unsignedInteger.
- `qualities.rank` → unsignedTinyInteger NOT NULL UNIQUE.
- Sin `$with` global.
- Sin índices secundarios hasta conocer consultas reales.
- `blueprint_components.component_id` → RESTRICT.
- `resources.column_letter_in_excel` → UNIQUE.

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
