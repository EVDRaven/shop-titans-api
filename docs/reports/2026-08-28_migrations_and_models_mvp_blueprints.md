# Reporte Técnico — Creación Migraciones y Modelos MVP Blueprints

**Fecha:** 2026-08-28
**Autor:** Editor IA
**Estado:** MIGRACIONES EJECUTADAS — POSTGRESQL VALIDADO
**Estado histórico (antes de migrate):** LISTO PARA MIGRATE — PENDIENTE DE APROBACIÓN FINAL (cumplido 2026-08-28).

---

## 1. Objetivo

Implementar la capa de persistencia del **MVP del módulo Blueprints (14 tablas)**, a partir del diseño aprobado en `DATA_MODEL_ANALYSIS.md` y los ajustes finales del Usuario:

- Aplicar **Opción A** para `Unlock Prerequisite`: `blueprints.unlock_prerequisite varchar nullable` (sin FK a workers).
- Conservar **FK recursiva** en `blueprint_components.component_id` → `blueprints.id`.
- Conservar relaciones 1:N para workers, recursos y componentes.
- Conservar las 6 estadísticas como columnas directas.
- Conservar las 4 afinidades/elementos como FK a `elements`.
- **NO** crear tablas para `skills` ni `enchantments`.
- **NO** ejecutar migraciones reales contra PostgreSQL; validar únicamente con `migrate --pretend`.

---

## 2. Archivos modificados / creados

| Archivo | Acción |
|---|---|
| `DATA_MODEL_ANALYSIS.md` | Modificado. Actualizada sección 2.3 (decisión Opción A para unlock_prerequisite) y campo correspondiente en §3.6. |
| `src/database/migrations/2026_08_28_100001_create_workers_table.php` | Creado. |
| `src/database/migrations/2026_08_28_100002_create_worker_levels_table.php` | Creado. |
| `src/database/migrations/2026_08_28_100003_create_resources_table.php` | Creado. |
| `src/database/migrations/2026_08_28_100004_create_item_categories_table.php` | Creado. |
| `src/database/migrations/2026_08_28_100005_create_elements_table.php` | Creado. |
| `src/database/migrations/2026_08_28_100006_create_qualities_table.php` | Modificado. `qualities.rank` cambiado a `unsignedTinyInteger NOT NULL UNIQUE` (decisión de revisión 3). |
| `src/database/migrations/2026_08_28_100007_create_blueprints_table.php` | Creado. |
| `src/database/migrations/2026_08_28_100008_create_blueprint_workers_table.php` | Creado. |
| `src/database/migrations/2026_08_28_100009_create_blueprint_resources_table.php` | Creado. |
| `src/database/migrations/2026_08_28_100010_create_blueprint_components_table.php` | Creado. Incluye FK recursiva. |
| `src/database/migrations/2026_08_28_100011_create_blueprint_crafting_upgrades_table.php` | Creado. |
| `src/database/migrations/2026_08_28_100012_create_blueprint_starforged_milestones_table.php` | Creado. |
| `src/database/migrations/2026_08_28_100013_create_blueprint_ascension_upgrades_table.php` | Creado. |
| `src/database/migrations/2026_08_28_100014_create_blueprint_transcendence_upgrades_table.php` | Creado. |
| `src/app/Models/Worker.php` | Creado. |
| `src/app/Models/WorkerLevel.php` | Creado. |
| `src/app/Models/Resource.php` | Creado. |
| `src/app/Models/ItemCategory.php` | Creado. |
| `src/app/Models/Element.php` | Creado. |
| `src/app/Models/Quality.php` | Creado. |
| `src/app/Models/Blueprint.php` | Creado. Incluye 4 FK belongsTo a Element y las 8 relaciones hasMany. |
| `src/app/Models/BlueprintWorker.php` | Creado. |
| `src/app/Models/BlueprintResource.php` | Creado. |
| `src/app/Models/BlueprintComponent.php` | Creado. Incluye 2 belongsTo a Blueprint (blueprint_id / component_id) + belongsTo a Quality. |
| `src/app/Models/BlueprintCraftingUpgrade.php` | Creado. |
| `src/app/Models/BlueprintStarforgedMilestone.php` | Creado. |
| `src/app/Models/BlueprintAscensionUpgrade.php` | Creado. |
| `src/app/Models/BlueprintTranscendenceUpgrade.php` | Creado. |
| `UNLOCK_PREREQUISITE_ANALYSIS.md` | Generado previamente. Referencia del análisis Ajuste 3. |

---

## 3. Cambios realizados

### 3.1 Migraciones (14 tablas, orden por dependencias)

1. `workers` — Catálogo clases/tipos worker. `worker_key` unique, no es FK a nada en este MVP.
2. `worker_levels` — Progresión global (level unique, xp, speed bonus). Sin FK.
3. `resources` — 12 recursos. `name` unique, además `column_letter_in_excel` unique (para BlueprintImporter mapear cols Y..AJ).
4. `item_categories` — Tipos (Sword, Axe, Heavy Armor…). `name` unique.
5. `elements` — Catálogo afinidades/built-ins (Fire, Water, …, None). `name` unique.
6. `qualities` — Calidades. `name` unique, `rank` unique nullable (para orden: Normal 1…Legendary 5).
7. `blueprints` — Tabla principal. Contiene:
   - name (unique)
   - `category_id` → FK item_categories (nullOnDelete)
   - `unlock_prerequisite` varchar nullable (**Opción A: sin FK a workers**)
   - Scrolls / Tokens / Tier / Value / Crafting Time / XP cols / Fusion XP / Favor / Airship Power
   - 6 columnas stats directas: `atk, def, hp, eva, crit, mana_shield` (todas nullable)
   - 4 FK a elements: `elemental_affinity_id`, `spirit_affinity_id`, `built_in_element_id`, `built_in_spirit_id` (todas nullable, nullOnDelete)
   - 4 energías (default 0): discount / surcharge / suggest / speed_up
   - `available_as_antique_at` timestamp nullable
8. `blueprint_workers` — 1:N workers requeridos + `level_required` int (sin FK a worker_levels, por decisión).
9. `blueprint_resources` — 1:N recursos + `quantity` int.
10. `blueprint_components` — 1:N componentes con FK **RECURSIVA**:
    - `blueprint_id` → blueprints.id (ON DELETE CASCADE)
    - `component_id` → blueprints.id (ON DELETE RESTRICT, para no borrar un BP usado como componente)
    - `quality_id` → qualities.id (nullable, nullOnDelete)
    - `amount` int
11. `blueprint_crafting_upgrades` — 1:N, crafts_required, UNIQUE(bp,upgrade_level)
12. `blueprint_starforged_milestones` — 1:N, crafts_required, UNIQUE(bp,milestone_number)
13. `blueprint_ascension_upgrades` — 1:N, shards_required, UNIQUE(bp,upgrade_level)
14. `blueprint_transcendence_upgrades` — 1:N, seals_required, UNIQUE(bp,upgrade_level)

### 3.2 Modelos Eloquent (14 modelos)

Todos usan `$fillable` explícito, `casts()` donde aplica (ints, decimales, timestamps). Claves:

- `Blueprint::category()` belongsTo.
- `Blueprint::elementalAffinity / spiritAffinity / builtInElement / builtInSpirit` (4 × belongsTo a `Element`).
- `Blueprint::blueprintWorkers`, `blueprintResources`, `blueprintComponentsAsParent`, `blueprintComponentsAsComponent`, `craftingUpgrades`, `starforgedMilestones`, `ascensionUpgrades`, `transcendenceUpgrades` (8 × hasMany).
- **`BlueprintComponent`** contiene las 2 relaciones recursivas:
  - `blueprint()` belongsTo(Blueprint::class, 'blueprint_id').
  - `component()` belongsTo(Blueprint::class, 'component_id').
  - `quality()` belongsTo(Quality::class).

---

## 4. Decisiones

### Propuestas
- Usar timestamps en TODAS las tablas (convenciones Laravel).
- Campos de conteo/XP/costos usar `unsigned` cuando lógicamente no admiten negativos.
- `blueprint_components.component_id` RESTRICT on delete (previene borrar blueprint que es componente de otro).
- `qualities.rank unsignedTinyInteger NOT NULL UNIQUE` (las 5 calidades tienen rank 1..5).
- `resources.column_letter_in_excel` unique (evita errores BlueprintImporter).
- `worker_levels.level` unique sin FK. `blueprint_workers.level_required` es unsignedInteger (Ajuste 2 y 4 Usuario).

### Decisiones aprobadas / explícitas del Usuario
- **`unlock_prerequisite` Opción A:** varchar nullable, sin FK hacia workers.
- **No Skills ni Enchantments tablas por ahora.**
- **6 stats directas** en `blueprints`.
- **FK recursiva** blueprint_components.component_id.
- NO ejecutar migraciones reales todavía.

#### Revisiones finales de diseño (aprobadas 2026-08-28)
Estas son las 5 decisiones de revisión tomadas por el Usuario tras analizar este reporte:

1. **`blueprints.unlock_prerequisite`** → mantener `varchar(255) nullable`, sin FK hacia `workers`.
2. **`blueprint_workers.level_required`** → mantener `unsignedInteger` (no cambiar a `unsignedTinyInteger`).
3. **`qualities.rank`** → **CAMBIADO** a `unsignedTinyInteger NOT NULL UNIQUE`. El catálogo contiene exactamente 5 calidades con rank 1..5, por lo que no es necesario NULL.
4. **Eager loading** → NO agregar `$with` global en ningún modelo; mantener carga explícita `with()` según necesidad.
5. **Índices secundarios** → NO agregarlos todavía; evaluar después de conocer las consultas reales de la API.

---

## 5. Validaciones

### 5.1 `migrate --pretend -v` (inicial)
Comando:
```bash
docker compose exec app php artisan migrate --pretend -v
```

Resultado (inicial, antes del ajuste `qualities.rank`):
- **Exit code: 0** (sin errores).
- 17 migraciones listadas (3 por defecto de Laravel 0001_01_01_* + 14 nuevas del módulo Blueprints).
- **Orden correcto de creación FKs:**
  1. Primero catálogos (workers, worker_levels, resources, item_categories, elements, qualities).
  2. Luego blueprints, que sólo referencia catálogos creados en pasos anteriores.
  3. Finalmente tablas 1:N (blueprint_workers, blueprint_resources, blueprint_components + 4 upgrades) que referencian `blueprints` + catálogos ya creados.
- **FK recursiva en `blueprint_components`** es VÁLIDA: se define un ALTER TABLE después del CREATE TABLE, y la tabla `blueprints` existe desde el paso #7 (antes de la #10).
- **Todas las constraints UNIQUE** reportadas: `workers.worker_key`, `worker_levels.level`, `resources.{name,column_letter}`, `item_categories.name`, `elements.name`, `qualities.{name,rank}`, `blueprints.name`, `blueprint_workers.{bp,worker}`, `blueprint_resources.{bp,resource}`, `blueprint_components.{bp,component,quality}`, y las de upgrades.
- No se tocó PostgreSQL (pretend no ejecuta DDL real, la tabla `migrations` NO insertó filas).

### 5.2 `migrate --pretend -v` (final, tras ajuste qualities.rank)
Se re-ejecutó el mismo comando DESPUÉS de aplicar la decisión 3 de revisión:
- **Resultado:** Exit code 0.
- SQL reportado para `qualities`: `rank` **smallint not null unique** (sin `null`). Coincide con el diseño aprobado.
- Resto de migraciones sin cambios respecto a la validación inicial.

### 5.3 Revisión final manual (14 migraciones × 14 modelos)
Se realizó una inspección línea a línea de los 14 archivos de migración y los 14 modelos Eloquent verificando:
- Nombres de tablas: correctos.
- Nombres de columnas: correctos.
- Tipos: correctos (con unsigned aplicado en números no-negativos).
- Nullable: correcto en cada columna.
- Foreign keys: correctas y con nombre correcto (`foreignId` + constrained + onDelete).
- onDelete: blueprint_id CASCADE; worker_id/resources_id/component_id RESTRICT; category_id / 4 elements FK / quality_id SET NULL.
- Unique constraints: correctas en todas las tablas (incluidas las composite UNIQUE).
- Relaciones Eloquent: todas las relaciones hasMany/belongsTo definidas.
- Relación **recursiva de BlueprintComponent**:
  - `blueprint()` belongsTo(Blueprint::class, 'blueprint_id') → padre.
  - `component()` belongsTo(Blueprint::class, 'component_id') → hijo (componente).
  - `quality()` belongsTo(Quality::class).
- Casts: presentes en los modelos que requieren tipado fuerte (integers, decimals, datetime).
- `$fillable`: cada modelo declara los campos que se asignan masivamente en el importador.
- Ningún modelo tiene `$with` global (decisión 4).
- No hay índices secundarios extra más allá de FK y Unique (decisión 5).

**Conclusión de la revisión final:** no se detectaron desviaciones respecto a la arquitectura aprobada en [PROJECT_CONTEXT.md](file:///home/draven/Documentos/shop-titans-api/PROJECT_CONTEXT.md) §37-60.

---

## 6. Riesgos

1. **Ciclos en blueprint_components:** La FK recursiva no valida ciclos (A→B→C→A). Sólo el BlueprintImporter (o aplicación) debe validar que no haya referencias circulares.
2. **Orden inserción BlueprintImporter:** componentes requieren que el `component_id` exista ANTES. Requiere 2 pasadas (primero todos los blueprints base, luego `blueprint_components`), o inserción por `tier ASC` + fallback 2 pasadas si no es suficiente. (Ya documentado en `DATA_MODEL_ANALYSIS.md`).
3. **Límite varchar(255) en `unlock_prerequisite`:** los nombres más largos del Excel ("Content Pass: Year of the Rabbit", "7th Anniversary Superior Pack", "Year of the Dragon Mega Pack") caben en 255. Si aparece un valor más largo en versiones futuras → ALTER a `text`.
4. **Límite varchar(255) en nombres de BPs/categorías:** actual Excel no supera 255. Si se supera → ALTER individual.
5. **Sin índices secundarios explícitos (a parte FK / unique):** por ahora se depende de índices automáticos de `foreignId` y `unique`. Cuando la API tenga consultas reales por (tier, category_id, gold_value, atk, element_affinity…) habrá que agregar índices adicionales. Esto es normal en MVP y no una limitación.

---

## 7. Pendientes

- ~~**Paso 6:** Ejecutar migraciones reales `php artisan migrate` contra PostgreSQL (pendiente aprobación Usuario).~~ ✅ **EJECUTADO 2026-08-28.** 17 migraciones ran (14 MVP + 3 Laravel). Ver §10 y `docs/reports/2026-08-28_migrate_real_mvp_blueprints.md`.
- **Paso 7:** Seeders / BlueprintImporter: poblar catálogos (resources n=12 con cols Y..AJ, qualities ranks, elements, workers) e importar 1.613 filas de la hoja Blueprints con estrategia de 2 pasadas para components.
- **Paso 8:** Implementar `routes/api.php` y endpoints MVP.
- **Paso 9:** Pruebas (Feature tests para imports / endpoints).
- Más adelante, si aparece caso real: tablas `skills` y `enchantments` con sus relaciones a blueprints o heroes.

---

## 8. Preguntas para ChatGPT

1. ¿Conviene ampliar `blueprints.unlock_prerequisite` a `text` por si acaso, o mantener `varchar(255)`?
2. ¿Mantener `unsignedInteger` para `worker_levels.level_required` o cambiar a `unsignedTinyInteger` sabiendo que los niveles actuales de worker raramente superan 50?
3. Para `qualities.rank`, ¿es mejor `unique nullable` (actual) o `unique not null` y poblar obligatoriamente los 5 ranks en seeder?
4. Falta algo en los modelos: `with = [...]` eager-load por defecto en algunos? O mantener carga explícita.
5. ¿Añadir `index()` adicional en `blueprints.tier` + `blueprints.category_id` ya desde el principio? O esperar a que aparezcan consultas lentas.

---

## 9. Próximo paso recomendado

~~Tras aprobación del Usuario y revisión ChatGPT:~~ ✅ Aprobación Usuario concedida 2026-08-28, migrate ejecutado.
1. ~~Ejecutar `php artisan migrate` (crea tablas + 14 filas en `migrations`).~~ ✅ EJECUTADO.
2. Crear seeders para catálogos (`resources` n=12 con `column_letter_in_excel`, `qualities` con ranks, `elements` con "None" default, `workers` desde hoja Workers).
3. Implementar BlueprintImporter que use el Excel de raíz, aplique lógica `"---"` a null/skip, y use 2 pasadas para resolver la FK recursiva en componentes.

---

## 10. Migración real ejecutada (2026-08-28)

Sección actualizada tras aprobación EXPLÍCITA del Usuario. Ejecutado: `docker compose exec app php artisan migrate --force -v`.

### Resumen
- **Exit code:** 0.
- **Migraciones RAN:** 17 totales.
  - Batch 1: 3 migraciones Laravel (users, cache, jobs) — pre-existente.
  - Batch 2: 14 migraciones MVP Blueprints — **NUEVAS en esta ejecución.**
- **Tablas PostgreSQL (schema public):** 23 creadas.
  - 14 tablas MVP Blueprints: `workers, worker_levels, resources, item_categories, elements, qualities, blueprints, blueprint_workers, blueprint_resources, blueprint_components, blueprint_crafting_upgrades, blueprint_starforged_milestones, blueprint_ascension_upgrades, blueprint_transcendence_upgrades`.
  - 9 tablas Laravel: `users, cache, cache_locks, jobs, job_batches, failed_jobs, password_reset_tokens, sessions, migrations`.
- **FKs detectadas:** 16. Todas con onDelete correcto (CASCADE / RESTRICT / SET NULL según diseño). FK RECURSIVA `blueprint_components.component_id → blueprints.id (RESTRICT)` confirmada.
- **`qualities.rank` validado:** `smallint not null` + constraint `qualities_rank_unique` presente (decisión 3 revisión).
- **No ejecutado:** seeders, BlueprintImporter, routes/api.php.

### Reporte detallado
Ver: [2026-08-28_migrate_real_mvp_blueprints.md](file:///home/draven/Documentos/shop-titans-api/docs/reports/2026-08-28_migrate_real_mvp_blueprints.md)

**Estado final de este documento:** MIGRACIONES EJECUTADAS — POSTGRESQL VALIDADO.
