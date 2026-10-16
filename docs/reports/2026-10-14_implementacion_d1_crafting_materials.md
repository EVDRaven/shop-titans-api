# Implementación D-1 — Modelo `crafting_materials`

**Fecha:** 2026-10-13
**Estado:** IMPLEMENTADA (código) — PENDIENTE DE EJECUCIÓN EN BASE DE DATOS — Rollback seguro validado en tests

## 1. Objetivo

Implementar la arquitectura aprobada para resolver la Discrepancia D-1: `blueprint_components.component_id` apunta a `blueprints.id`, pero solo el 7.5% de los componentes son blueprints reales. Se crea una tabla `crafting_materials` como catálogo unificado de los cuatro tipos de componentes.

## 2. Alcance

Exclusivamente la implementación de código (migraciones y modelos). No se ejecutan migraciones, seeders ni importaciones. No se altera la base de datos.

## 3. Estado inicial

- `blueprint_components.component_id` FK → `blueprints.id` (ON DELETE RESTRICT).
- `blueprint_components` está vacía (no se importaron componentes en Etapa 2).
- No existía la tabla `crafting_materials`.

## 4. Archivos modificados / creados

### Nuevos
| Archivo | Descripción |
|---|---|
| [2026_10_14_000001_create_crafting_materials_table.php](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_10_14_000001_create_crafting_materials_table.php) | Crea la tabla `crafting_materials` con todas las columnas, enum, FK, CHECK constraints e índice único parcial. |
| [2026_10_14_000002_change_blueprint_components_component_id_foreign.php](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_10_14_000002_change_blueprint_components_component_id_foreign.php) | Cambia la FK `component_id` de `blueprints` a `crafting_materials`. |
| [CraftingMaterial.php](file:///home/draven/Documentos/shop-titans-api/src/app/Models/CraftingMaterial.php) | Modelo Eloquent con fillable, casts y relaciones. |
| [ADR-001_crafting_materials.md](file:///home/draven/Documentos/shop-titans-api/docs/adr/ADR-001_crafting_materials.md) | Registro de la decisión arquitectónica. |

### Modificados
| Archivo | Cambio |
|---|---|
| [BlueprintComponent.php](file:///home/draven/Documentos/shop-titans-api/src/app/Models/BlueprintComponent.php) | La relación `component()` ahora apunta a `CraftingMaterial`. |
| [Blueprint.php](file:///home/draven/Documentos/shop-titans-api/src/app/Models/Blueprint.php) | Se elimina la relación `blueprintComponentsAsComponent()` (ya no semánticamente correcta, ya que `component_id` apunta a `crafting_materials.id`). |
| [2026_10_14_000001_create_crafting_materials_table.php](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_10_14_000001_create_crafting_materials_table.php) | Corrección: `$table->check()` no existe en Laravel 13. Se reemplaza por `DB::statement('ALTER TABLE ... ADD CONSTRAINT ... CHECK')` condicional al driver `pgsql`. |
| [2026_10_14_000002_change_blueprint_components_component_id_foreign.php](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_10_14_000002_change_blueprint_components_component_id_foreign.php) | `down()` seguro: verifica reversibilidad antes de modificar el esquema. |
| [2026-10-14_revision_source_id_crafting_materials.md](file:///home/draven/Documentos/shop-titans-api/docs/reports/2026-10-14_revision_source_id_crafting_materials.md) | Estado actualizado a APROBADA/IMPLEMENTADA; riesgo de nombres cambiantes documentado como ACEPTADO. |
| [AI_WORK_LOG.md](file:///home/draven/Documentos/shop-titans-api/AI_WORK_LOG.md) | Estado actualizado. |

### Tests
| Archivo | Descripción |
|---|---|
| [BlueprintComponentsMigrationRollbackTest.php](file:///home/draven/Documentos/shop-titans-api/src/tests/Feature/BlueprintComponentsMigrationRollbackTest.php) | Valida los 3 escenarios del `down()`: tabla vacía, componentes reversibles y componentes no reversibles. |

## 5. Cambios realizados

### 5.1 Tabla `crafting_materials`

Columnas:
- `id` PK
- `source_key` string UNIQUE NOT NULL — clave estable de identidad
- `name` string NOT NULL — nombre visible (actualizable)
- `material_type` enum('blueprint','quest_component','sigil','enchantment') NOT NULL
- `blueprint_id` FK → `blueprints.id` nullable, ON DELETE RESTRICT
- `tier` unsignedTinyInteger nullable
- `gold_value` unsignedBigInteger nullable
- `quest_area` string nullable
- `fuse_time_seconds` unsignedInteger nullable
- `worker_level_required` unsignedTinyInteger nullable
- `worker_xp` unsignedInteger nullable
- `created_at`, `updated_at`

**Restricciones de integridad:**
1. CHECK `chk_material_type_blueprint_id`: `blueprint` → `blueprint_id NOT NULL`; los demás → `NULL`.
2. CHECK `chk_source_key_format`: el `source_key` debe coincidir con el patrón del tipo.
3. UNIQUE en `source_key`.
4. UNIQUE INDEX parcial en `blueprint_id` (WHERE NOT NULL).
5. FK `blueprint_id` → `blueprints.id` con `ON DELETE RESTRICT`.

### 5.2 `blueprint_components`

Se cambia la FK de `component_id` de `blueprints.id` a `crafting_materials.id`, manteniendo `ON DELETE RESTRICT`. La tabla está vacía, por lo que no requiere migración de datos.

### 5.3 Modelo `CraftingMaterial`

- `fillable`: todos los atributos de datos.
- `casts`: columnas numéricas a integer.
- Relaciones: `blueprint()` (BelongsTo a `Blueprint`), `blueprintComponents()` (HasMany a `BlueprintComponent`).

### 5.4 Modelo `BlueprintComponent`

- La relación `component()` ahora retorna `BelongsTo(CraftingMaterial::class, 'component_id')`.

### 5.5 Modelo `Blueprint`

- Se elimina `blueprintComponentsAsComponent()` porque `component_id` ya no apunta a `blueprints.id`.

### 5.6 Corrección de CHECK constraints en migración `000001`

El método `$table->check()` no existe en Laravel 13 (`BadMethodCallException`). Se reemplaza por `DB::statement('ALTER TABLE crafting_materials ADD CONSTRAINT ... CHECK (...)')` ejecutado únicamente cuando el driver es `pgsql`. En SQLite (tests) los CHECK constraints se omiten, ya que SQLite no soporta `ALTER TABLE ... ADD CONSTRAINT`. Las restricciones son idénticas a las aprobadas:
- `chk_material_type_blueprint_id`: coherencia entre `material_type` y `blueprint_id`.
- `chk_source_key_format`: formato de `source_key` según el tipo.

### 5.7 Rollback seguro en migración `000002`

El `down()` original solo cambiaba la FK sin actualizar los datos, dejando `component_id` con valores de `crafting_materials.id` apuntando a `blueprints.id` (inconsistencia). El nuevo `down()`:

1. **Verificación previa (antes de cualquier DDL):** cuenta los registros de `blueprint_components` cuyo `crafting_material` asociado tiene `blueprint_id IS NULL` (tipos `quest_component`, `sigil` o `enchantment`).
2. **Abortar si no es reversible:** si el conteo es mayor que 0, lanza `RuntimeException` con un mensaje que identifica el problema y el número de registros afectados. No se modifica esquema ni datos.
3. **Restaurar datos (si es reversible):** elimina la FK actual, ejecuta `UPDATE blueprint_components SET component_id = (SELECT cm.blueprint_id FROM crafting_materials cm WHERE cm.id = blueprint_components.component_id)` y recrea la FK hacia `blueprints.id`.

**Garantía de atomicidad:** Laravel envuelve cada migración en una transacción por defecto (`$withinTransaction = true`) para bases de datos que soportan DDL transaccional (PostgreSQL y SQLite). Si el `down()` falla en cualquier punto, la transacción se revierte y el esquema/datos quedan intactos. La verificación previa se ejecuta antes de cualquier DDL, por lo que un aborto no deja cambios parciales.

**Inyectividad del mapeo:** el índice único parcial `idx_crafting_materials_blueprint_id_unique` garantiza que cada `blueprint_id` aparezca a lo sumo una vez en `crafting_materials`, por lo que el mapeo `crafting_materials.id → crafting_materials.blueprint_id` es inyectivo y no introduce duplicados en la clave única `(blueprint_id, component_id, quality_id)` de `blueprint_components`.

## 6. Hipótesis verificadas

- La tabla `blueprint_components` está vacía: confirmado en la ejecución de Etapa 2 (no se importaron componentes).
- `component_id` es `unsignedBigInteger`, compatible con `crafting_materials.id` (bigIncrements).
- Los nombres de quest components, sigils y enchantments son únicos dentro de su tipo (verificado en el reporte de estabilidad de identidad).
- El mapeo `crafting_materials.id → crafting_materials.blueprint_id` es inyectivo gracias al índice único parcial en `blueprint_id`, por lo que el rollback no introduce duplicados en `blueprint_components`.
- El método `$table->check()` no existe en Laravel 13 (verificado: `BadMethodCallException`). Se usa `DB::statement('ALTER TABLE ... ADD CONSTRAINT ... CHECK')` para PostgreSQL.

## 7. Discrepancias

Ninguna nueva. La única discrepancia abierta es D-1, que esta implementación resuelve a nivel de código.

## 8. Decisiones aprobadas

1. Opción 4 (híbrida): tabla `crafting_materials` con atributos materializados y FK opcional a `blueprints`.
2. `source_key` por tipo: `blueprint:{id}`, `quest_component:{name}`, `sigil:{name}`, `enchantment:{name}`.
3. Restricciones PostgreSQL: 2 CHECK + 2 UNIQUE + 1 FK.
4. Riesgo aceptado: los nombres de quest components, sigils y enchantments pueden cambiar entre versiones; no se implementan mitigaciones.

## 9. Decisiones pendientes

1. ⏳ Ejecución de las migraciones en base de datos (no autorizada en esta tarea).
2. ⏳ Implementación del `CraftingMaterialSeeder` (pendiente de autorización).
3. ⏳ Decisión sobre importar los 77 enchantments en `blueprints`.

## 10. Validaciones

**Estático (sin alterar la base de datos):**
- `php -l` en `000001`, `000002` y el test → sin errores.
- `vendor/bin/pint --test` en `000001`, `000002` y el test → PASS.
- Diagnósticos IDE en `000001`, `000002` y el test → sin errores.

**Tests (SQLite `:memory:`, aislados de PostgreSQL):**
- `php artisan test` → 20 tests, 104 assertions, PASS.
- `BlueprintComponentsMigrationRollbackTest` (3 tests):
  - **Tabla vacía:** `down()` se completa sin errores.
  - **Componentes reversibles (tipo `blueprint`):** `down()` actualiza `component_id` de `crafting_materials.id` a `crafting_materials.blueprint_id` correctamente.
  - **Componentes no reversibles (tipo `quest_component`):** `down()` lanza `RuntimeException` y los datos permanecen intactos (verificado en bloque `finally`).

**No ejecutado (requiere autorización):**
- Migraciones (`php artisan migrate`).
- Seeders e importaciones.

## 11. Riesgos

1. **Cambio de nombre en no-blueprints (ACEPTADO):** Si un quest component, sigil o enchantment cambia de nombre, el `source_key` cambia y se crea un registro huérfano. No se implementa detección automática.
2. **Migración no ejecutada:** El código está implementado pero la base de datos aún no refleja los cambios. Hasta que se ejecuten las migraciones, `blueprint_components.component_id` sigue apuntando a `blueprints.id`.
3. **CHECK constraints en SQLite (DOCUMENTADO):** Los CHECK constraints se aplican únicamente en PostgreSQL. En SQLite (tests) se omiten porque SQLite no soporta `ALTER TABLE ... ADD CONSTRAINT`. Las restricciones se validan en PostgreSQL al ejecutar las migraciones.
4. **Rollback de `000002` con datos no reversibles (RESUELTO):** Si existen `blueprint_components` que referencian `crafting_materials` sin `blueprint_id` válido (tipos `quest_component`, `sigil` o `enchantment`), el `down()` aborta con `RuntimeException` antes de modificar esquema o datos. Solo se revierte si todos los componentes son de tipo `blueprint` y pueden mapearse a `blueprints.id`. La atomicidad está garantizada por la transacción de Laravel.

## 11.1 Escenarios de rollback de `000002`

| Escenario | Condición | Resultado |
|---|---|---|
| Tabla vacía | `blueprint_components` sin registros | `down()` se completa. Se recrea la FK a `blueprints`. |
| Componentes reversibles | Todos los `crafting_materials` referenciados tienen `blueprint_id NOT NULL` | `down()` actualiza `component_id` al `blueprint_id` original y recrea la FK a `blueprints`. Sin pérdida de datos. |
| Componentes no reversibles | Al menos un `crafting_material` referenciado tiene `blueprint_id IS NULL` | `down()` lanza `RuntimeException` antes de cualquier modificación. Esquema y datos permanecen intactos. |

## 12. Pendientes

1. Autorizar y ejecutar `php artisan migrate`.
2. Implementar y ejecutar `CraftingMaterialSeeder`.
3. Implementar la importación de componentes en `blueprint_components` apuntando a `crafting_materials`.

## 13. Próximo paso

Esperar autorización para ejecutar las migraciones y, posteriormente, implementar el seeder y la importación de componentes.

## 14. Referencias

- Propuesta D-1: [2026-10-14_propuesta_modelado_blueprint_components.md](file:///home/draven/Documentos/shop-titans-api/docs/reports/2026-10-14_propuesta_modelado_blueprint_components.md)
- Revisión de `source_id`: [2026-10-14_revision_source_id_crafting_materials.md](file:///home/draven/Documentos/shop-titans-api/docs/reports/2026-10-14_revision_source_id_crafting_materials.md)
- Estabilidad de identidad: [2026-10-14_revision_estabilidad_identidad_materiales.md](file:///home/draven/Documentos/shop-titans-api/docs/reports/2026-10-14_revision_estabilidad_identidad_materiales.md)
- ADR: [ADR-001_crafting_materials.md](file:///home/draven/Documentos/shop-titans-api/docs/adr/ADR-001_crafting_materials.md)
