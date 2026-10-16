# AI Work Log

Bitácora compartida entre Usuario, ChatGPT y Editor IA. Las propuestas no son decisiones aprobadas hasta confirmación explícita.

## Estado actual
**Fase:** MVP Blueprints — D-1 `crafting_materials` EJECUTADA Y VALIDADA POR CONTEOS. Hallazgos H-1/H-2/H-3 del importador de componentes implementados.
**Estado:** 🟠 IMPLEMENTADA — PENDIENTE DE REVISIÓN (H-1/H-2/H-3). `memory_limit=512M` efectivo; `crafting_materials`=186, `blueprint_components`=2,747.
**Siguiente tarea:** Revisión humana de H-1/H-2/H-3.

## Último hito — 2026-10-15 (cierre de hallazgos H-1, H-2, H-3)
Implementadas las tres mejoras aprobadas de la revisión final del importador. **Sin escrituras en PostgreSQL.**

- **H-1 (código):** en `BlueprintImportService::importComponents()` la carga de catálogos (`loadComponentCatalogs()`) se envuelve en `try/catch` y devuelve `ComponentImportResult::failure(...)` ante error. Se mantiene fuera de la transacción (semántica de rollback intacta).
- **H-2 (documentación):** actualizadas §11.3, §12 y §14 de `docs/reports/2026-10-15_comando_import_blueprint_components.md` (de "pendiente" a "ejecutada": 1.549/2.747/0/0/0). Se conserva la distinción tests vs. ejecución real.
- **H-3 (tests):** aserciones de que la tabla de métricas se muestra con `success = false`; nuevo test `test_command_fails_when_catalog_loading_fails` (SQLite `:memory:`).
- **H-4:** sin cambios (fallo e incidencias → `FAILURE` = 1).

**Validaciones:** `php -l` OK · Pint PASS (2 files) · `ImportBlueprintComponentsCommandTest` 7 passed (31 assertions) · relacionados (`BlueprintComponentImportTest`, `BlueprintImportServiceTest`) 22 passed (119 assertions).

**Confirmación:** sin importaciones reales, seeders ni migraciones; sin cambios de esquema/arquitectura; sin commit ni push.

**Documentación:** `docs/reports/2026-10-15_cierre_hallazgos_import_blueprint_components.md`.

**Estado:** IMPLEMENTADA — PENDIENTE DE REVISIÓN.

## Hito anterior — 2026-10-15 (revisión final de `import:blueprint-components`)
Revisión estática + tests existentes del comando. **Sin cambios de código y sin escrituras en PostgreSQL.**

**Resultado por requisito:**
1. Éxito solo sin errores/sin resolver/omitidos → CUMPLE (`!success` o `hasIncidents()` → FAILURE).
2. Errores visibles sin ocultar métricas → CUMPLE (la tabla se muestra siempre).
3. Excepciones/fallos del servicio no reportados como éxito → CUMPLE (el servicio convierte `Throwable` en `failure`).
4. Cobertura de tests y coincidencia con el contrato → CUMPLE.
5. No se confundió la importación real con los tests → CUMPLE (no ejecutada).

**Verificación ejecutada:** `php artisan test tests/Feature/ImportBlueprintComponentsCommandTest.php` → **6 passed (21 assertions)** (SQLite `:memory:`).

**Hallazgos (propuestos, no implementados):** H-1 `loadComponentCatalogs()` fuera del `try`; H-2 reporte previo desactualizado (§11.3/§12/§14 dicen "pendiente" pero la ejecución real ya se hizo: 1549/2747/0/0/0); H-3 huecos menores de cobertura; H-4 código de salida único para fallo e incidencias.

**Confirmación:** No se modificó PostgreSQL; no se ejecutaron seeders, importaciones ni migraciones; no se cambió código ni tests; sin commit ni push.

**Documentación:** `docs/reports/2026-10-15_revision_final_import_blueprint_components.md`.

**Estado:** REVISIÓN COMPLETADA — SIN DEFECTOS BLOQUEANTES.

## Hito anterior — 2026-10-15 (límite de memoria PHP permanente, propuesta B)
Se implementó la solución permanente al OOM de PhpSpreadsheet. **Sin escrituras en PostgreSQL.**

**Cambio:** en `docker/php/Dockerfile` se añadió:
```dockerfile
RUN echo 'memory_limit=512M' > /usr/local/etc/php/conf.d/zz-memory.ini
```

**Comandos ejecutados:**
- `docker compose build app` → `Image shop-titans-api-app Built` (exit 0).
- `docker compose up -d --no-deps app` → `Container shoptitans-app Recreated` y `Started` (solo `app`; `postgres` intacto).
- `docker compose exec app php -r 'echo ini_get("memory_limit"), PHP_EOL;'` → **`512M`**.

**Verificaciones extra:** `zz-memory.ini` existe con `memory_limit=512M` y aparece en `php --ini` como fichero `.ini` adicional.

**Git:** los cambios preexistentes se preservaron; el único delta nuevo en `Dockerfile` es la línea de memoria. Sin commit ni push.

**Documentación:** `docs/reports/2026-10-15_limite_memoria_php_permanente.md`.

**Estado:** IMPLEMENTADO Y VALIDADO. Pendiente: revisión humana final del comando.

## Hito anterior — 2026-10-15 (cierre de la importación de componentes de blueprints)
Se documentó el resultado real de la importación D-1. **Sin escrituras en PostgreSQL** (solo una consulta de solo lectura para confirmar conteos).

**Ejecución real:**
- `CraftingMaterialSeeder` con `memory_limit=512M` → `crafting_materials` = 186 (blueprint 99, quest_component 66, sigil 6, enchantment 15).
- `import:blueprint-components` sobre Excel v26.9.1 → 1.549 blueprints procesados, 2.747 componentes insertados, 0 filas omitidas, 0 referencias sin resolver, 0 errores.

**Verificación (solo lectura, `SELECT COUNT(*)`):** `blueprints`=1,549 · `crafting_materials`=186 · `blueprint_components`=2,747. Coincide con lo esperado.

**Limitación pendiente:** `memory_limit=128M` insuficiente (pico real 150.5MB). Solo se aplicó el ajuste puntual `-d memory_limit=512M`; la solución permanente (propuesta B: `memory_limit=512M` en la imagen + rebuild) sigue **pendiente**.

**Documentación:** `docs/reports/2026-10-15_cierre_importacion_blueprint_components.md`.

**Estado:** D-1 VALIDADA POR CONTEOS. Solución permanente de memoria y revisión humana final del comando, pendientes.

## Hito anterior — 2026-10-15 (diagnóstico del fallo de `CraftingMaterialSeeder`, exit 255)
Se diagnosticó (solo lectura) el fallo del seeder D-1. **Sin escrituras en PostgreSQL.**

**Síntoma:** `db:seed` muestra solo `INFO Seeding database.`, termina en exit 255 y deja `crafting_materials` vacía.

**Causa raíz (demostrada):** `memory_limit=128M` en el contenedor; PhpSpreadsheet requiere un pico real de **150.5 MB** al cargar el Excel. Fatal error `Allowed memory size of 134217728 bytes exhausted` en `Reader/Xlsx.php:212` / `Collection/Cells.php:448` → exit 255.

**Evidencia:**
- `php -r 'ini_get("memory_limit")'` → 128M (sin `php.ini` propio; `FROM php:8.4-fpm`).
- `php -d memory_limit=128M -r '...readCraftingMaterialData...'` → OOM, EXIT=255.
- `php -d memory_limit=512M -r '...readCraftingMaterialData...'` → EXIT=0; components=186, blueprints=1549, quest_components=66, sigils=6, enchantments=77; peak=150.5MB.
- `CraftingMaterialSeederTest` fija `ini_set('memory_limit','512M')`, por eso los tests no detectaron la diferencia.

**Solución propuesta (no implementada):** Opción A inmediata `php -d memory_limit=512M artisan db:seed ...`; Opción B permanente `RUN echo 'memory_limit=512M' > /usr/local/etc/php/conf.d/zz-memory.ini` en `docker/php/Dockerfile` + rebuild.

**Confirmación:** No se ejecutaron migraciones, seeders, importaciones ni escrituras. No se modificó código ni configuración.

**Documentación:** `docs/reports/2026-10-15_diagnostico_fallo_crafting_material_seeder.md`.

**Estado:** DIAGNÓSTICO PENDIENTE DE APROBACIÓN HUMANA.

## Hito anterior — 2026-10-15 (corrección del código de salida de `import:blueprint-components`)
Tras revisión humana, se corrigió el comando para no informar éxito cuando la importación deja datos incompletos. **Sin escrituras en PostgreSQL.**

**Cambio:** `ImportBlueprintComponents` devuelve `FAILURE` cuando el servicio informa fallo, hay referencias sin resolver, se omitieron blueprints o el resultado contiene errores; `SUCCESS` solo si la importación es completamente satisfactoria. El comando ya no retorna de forma temprana tras un fallo: siempre muestra la tabla de métricas y todas las incidencias.

**Tests:** `ImportBlueprintComponentsCommandTest` ampliado a 6 tests (se añadió `test_command_fails_when_quality_not_found`; se ajustaron los tests de referencias sin resolver y de blueprints omitidos para esperar `FAILURE`).

**Validaciones (reales):**
- `php -l` → sin errores (2 archivos).
- `pint --test` → 2 files PASS.
- `php artisan test tests/Feature/ImportBlueprintComponentsCommandTest.php` → 6 passed (21 assertions).
- `php artisan test` (suite completa) → 46 passed (382 assertions).
- Diagnósticos IDE → sin errores.

**Confirmación:** PostgreSQL NO fue modificado. No se ejecutaron migraciones, seeders, importaciones ni escrituras. No se hizo ningún commit.

**Documentación:** `docs/reports/2026-10-15_comando_import_blueprint_components.md` actualizado.

**Estado:** IMPLEMENTADA — PENDIENTE DE REVISIÓN HUMANA.

## Hito anterior — 2026-10-15 (preflight de solo lectura para D-1)
Se verificó el entorno (solo lectura) antes de autorizar la ejecución de D-1. **Sin escrituras en PostgreSQL.**

**Resultados observados:**
- Git: modificados (`AI_WORK_LOG.md`, `docker/php/Dockerfile`, modelos `Blueprint`/`BlueprintComponent`, `composer.json/lock`) y nuevos (código D-1, migraciones, tests, reportes). Nada alterado.
- Docker: `shoptitans-app` y `shoptitans-postgres` (postgres:17) `Up 2 days`.
- PostgreSQL: responde; DB `shoptitans`, usuario `shoptitans`, PostgreSQL 17.10.
- Migraciones: 14 MVP `Ran`; D-1 `2026_10_14_000001` y `2026_10_14_000002` **Pending**.
- Conteos observados: `blueprints` = 1549; `blueprint_components` = 0; `crafting_materials` = **no existe** (esperado).
- FK actual `blueprint_components.component_id` → `blueprints(id) ON DELETE RESTRICT` (lo cambiará `000002`).
- Excel: legible; 1,367,141 bytes; ruta contenedor `/var/www/html/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx`.
- Comando `import:blueprint-components` registrado; `CraftingMaterialSeeder`, `ImportBlueprintComponents` y `BlueprintImportService` cargan (`class_exists` = true).

**Bloqueos:** ninguno. **Discrepancias:** ninguna nueva.

**Confirmación:** PostgreSQL NO fue modificado. No se ejecutaron migraciones, seeders, importaciones ni escrituras.

**Documentación:**
- Reporte: `docs/reports/2026-10-15_preflight_d1_crafting_materials.md`

**Estado:** PREPARACIÓN PARA EJECUCIÓN — LISTO PARA SOLICITAR AUTORIZACIÓN. D-1 NO ejecutada ni validada.

## Hito anterior — 2026-10-15 (comando Artisan `import:blueprint-components`)
Se creó el comando Artisan dedicado para importar componentes de blueprints, reutilizando `BlueprintImportService::importComponents()` sin modificar el servicio, **sin modificar PostgreSQL**.

**Cambios de código:**
- `ImportBlueprintComponents` (nuevo): comando `import:blueprint-components {file}`; verifica el archivo, lee las filas con `BlueprintExcelReader::read()`, invoca `importComponents()`, muestra métricas reales (blueprints procesados, componentes insertados, filas omitidas, referencias sin resolver, errores), informa incidencias y devuelve código distinto de cero ante fallo del servicio.
- `ImportBlueprintComponentsCommandTest` (nuevo): 5 tests con el lector mockeado (éxito, archivo inexistente, fallo del servicio, referencias sin resolver, blueprint omitido).

**Validaciones:**
- `php -l` → sin errores (2 archivos).
- `pint --test` → 2 files PASS.
- `php artisan test tests/Feature/ImportBlueprintComponentsCommandTest.php` → 5 tests, 16 assertions, PASS.
- `php artisan list | grep import:` → comando registrado.
- Diagnósticos IDE → sin errores.

**Confirmación:** PostgreSQL NO fue modificado. No se ejecutaron migraciones, seeders ni el comando de importación contra la base de datos real.

**Documentación:**
- Reporte: `docs/reports/2026-10-15_comando_import_blueprint_components.md`

**Estado:** IMPLEMENTADA — PENDIENTE DE REVISIÓN HUMANA.

## Hito anterior — 2026-10-13 (preparación de seeder e importador de componentes D-1)
Se implementó el `CraftingMaterialSeeder` y el importador de componentes (`BlueprintImportService::importComponents()`) para completar D-1, **sin modificar PostgreSQL**.

**Cambios de código:**
- `BlueprintExcelReader`: columnas AL-AQ (Component, Quality, Amount), `parseComponents()`, `readCraftingMaterialData()` (lectura única del Excel para optimizar memoria), métodos `readQuestComponents()`, `readSigils()`, `readEnchantmentNames()`, `readBlueprintNames()`, `readComponentNames()`.
- `BlueprintRowData`: propiedad `components` (array, default `[]`).
- `CraftingMaterialSeeder` (nuevo): clasifica los 186 componentes únicos en 4 tipos (99 blueprint, 66 quest_component, 6 sigil, 15 enchantment), crea `crafting_materials` con `source_key` por tipo, idempotente vía `updateOrCreate`, reporta blueprints faltantes y tipos no clasificados.
- `BlueprintImportService::importComponents()` (nuevo): resuelve componentes contra `crafting_materials` por nombre, calidades contra `qualities`, delete-then-insert idempotente, transacción con rollback, reporta referencias sin resolver.
- `ComponentImportResult` (nuevo): resultado con métricas y referencias sin resolver.
- Tests: `CraftingMaterialSeederTest` (10 tests), `BlueprintComponentImportTest` (9 tests), actualización de `BlueprintExcelReaderTest`.

**Conteos verificados del Excel v26.9.1:**
- 186 componentes únicos (99 blueprint + 66 quest_component + 6 sigil + 15 enchantment), 0 unknown.
- 2747 referencias de componentes (blueprints no-Enchantment).
- Calidades: Normal (127), Superior (35), Flawless (1).
- Discrepancia: los 190/2901 esperados incluyen filas Enchantment; al excluirlas (decisión Etapa 2) quedan 186/2747.

**Validaciones:**
- `php -l` → sin errores.
- `pint --test` → 8 files PASS.
- `php artisan test` → 40 tests, 361 assertions, PASS.
- Diagnósticos IDE → sin errores.
- `composer dump-autoload` → 7408 classes.

**Confirmación:** PostgreSQL NO fue modificado. No se ejecutaron migraciones, seeders ni importaciones contra la base de datos real.

**Documentación:**
- Reporte: `docs/reports/2026-10-13_preparacion_ejecucion_d1.md`

**Estado:** IMPLEMENTADA Y VALIDADA EN TESTS — PENDIENTE DE EJECUCIÓN EN BD.

## Hito anterior — 2026-10-13 (rollback seguro de `blueprint_components`)
Se implementó un `down()` seguro para la migración `2026_10_14_000002` que evita pérdida de datos o estados inconsistentes.

**Problema:** El `down()` original solo cambiaba la FK sin actualizar los datos, dejando `component_id` con valores de `crafting_materials.id` apuntando a `blueprints.id`. Además, si existían componentes de tipo `quest_component`, `sigil` o `enchantment` (sin `blueprint_id` válido), no podían reconstruirse en el esquema anterior.

**Solución aplicada:**
1. **Verificación previa (antes de cualquier DDL):** cuenta los `blueprint_components` cuyo `crafting_material` tiene `blueprint_id IS NULL`.
2. **Abortar si no es reversible:** si el conteo > 0, lanza `RuntimeException` con mensaje claro. No modifica esquema ni datos.
3. **Restaurar datos (si es reversible):** `UPDATE blueprint_components SET component_id = (SELECT cm.blueprint_id FROM crafting_materials cm WHERE cm.id = blueprint_components.component_id)` y recrea la FK a `blueprints`.

**Atomicidad:** Laravel envuelve cada migración en una transacción (`$withinTransaction = true`) para PostgreSQL y SQLite. Cualquier fallo revierte todo.

**Corrección adicional en `000001`:** El método `$table->check()` no existe en Laravel 13. Se reemplazó por `DB::statement('ALTER TABLE ... ADD CONSTRAINT ... CHECK')` condicional al driver `pgsql`. En SQLite (tests) los CHECK constraints se omiten.

**Validaciones:**
- `php -l` y `pint --test` en `000001`, `000002` y el test → PASS.
- `php artisan test` → 20 tests, 104 assertions, PASS.
- `BlueprintComponentsMigrationRollbackTest` (3 tests): tabla vacía, componentes reversibles, componentes no reversibles.

**Estado:** IMPLEMENTADA Y VALIDADA EN TESTS — PENDIENTE DE EJECUCIÓN EN BD.

## Hito anterior — 2026-10-13 (implementación D-1 — `crafting_materials`)
Se implementó la arquitectura aprobada (Opción 4 híbrida) para resolver D-1.

**Cambios de código:**
- Nueva migración `2026_10_14_000001_create_crafting_materials_table.php`: tabla con `source_key` UNIQUE, `material_type` enum, `blueprint_id` FK nullable (RESTRICT), 2 CHECK constraints, índice único parcial en `blueprint_id`.
- Nueva migración `2026_10_14_000002_change_blueprint_components_component_id_foreign.php`: `component_id` ahora FK → `crafting_materials.id`.
- Nuevo modelo `CraftingMaterial` con relaciones `blueprint()` y `blueprintComponents()`.
- `BlueprintComponent.component()` ahora apunta a `CraftingMaterial`.
- Se eliminó `Blueprint.blueprintComponentsAsComponent()` (relación rota).

**source_key por tipo:**
- `blueprint:{blueprints.id}` (con `blueprint_id` NOT NULL)
- `quest_component:{name}`, `sigil:{name}`, `enchantment:{name}` (con `blueprint_id` NULL)

**Riesgo aceptado:** los nombres de quest components, sigils y enchantments pueden cambiar entre versiones; no se implementan mitigaciones.

**Documentación:**
- ADR: `docs/adr/ADR-001_crafting_materials.md`
- Reporte de implementación: `docs/reports/2026-10-14_implementacion_d1_crafting_materials.md`
- Reporte D-1 actualizado: `docs/reports/2026-10-14_revision_source_id_crafting_materials.md`

**Estado:** IMPLEMENTADA (código) — PENDIENTE DE EJECUCIÓN EN BD.

## Hito anterior — 2026-10-13 (revisión de estabilidad de identidad de materiales)
Se investigó si los nombres de Quest Components, Sigils y Enchantments pueden usarse como parte de `source_key`.

**Hallazgos comprobados en el Excel v26.9.1:**
- **Ninguna hoja tiene columna de ID/Key/Code.** El `Name` es el único identificador disponible.
- **Unicidad:** 66 Quest Components, 6 Sigils, 77 Enchantments — 0 duplicados cada uno.
- **Referencias cruzadas:** todos los QC y Sigils referenciados en `Blueprints` por nombre exacto. 15 de 77 Enchantments referenciados por no-Enchantments.
- **Sin inconsistencias** de mayúsculas/minúsculas entre hojas.
- **Changelog:** 1027 filas, 0 menciones de "rename".

**Conclusión:**
- ✅ El nombre es el único identificador disponible y es único y consistente en esta versión.
- ❌ No se puede afirmar estabilidad entre versiones (solo una versión del Excel; Changelog genérico).
- ⚠️ Recomendación: usar `quest_component:{name}`, `sigil:{name}`, `enchantment:{name}` con riesgo documentado de cambio de nombre.

Reporte:
`docs/reports/2026-10-14_revision_estabilidad_identidad_materiales.md`

**Estado:** PROPUESTA — PENDIENTE DE APROBACIÓN HUMANA.

## Hito anterior — 2026-10-14 (cierre de decisiones de identidad y restricciones en `crafting_materials`)
Se actualizó el reporte de `source_id` para cerrar dos decisiones:

**1. Identidad y sincronización (clave estable `source_key`):**
- Se reemplaza `name UNIQUE` por `source_key UNIQUE`. El `name` pasa a ser dato actualizable.
- `source_key` es inmutable y se construye por tipo:
  - `blueprint` → `"blueprint:{blueprint_id}"` (estable, basado en PK)
  - `enchantment` → `"enchantment:{name}"`
  - `quest_component` → `"quest_component:{name}"`
  - `sigil` → `"sigil:{name}"`
- **Actualización de nombre sin duplicados:** `updateOrCreate(['source_key' => ...], ['name' => $newName])`. El `source_key` no cambia aunque cambie el `name`.
- **Idempotencia:** el seeder usa `updateOrCreate` por `source_key`.

**2. Restricciones por tipo (5 constraints PostgreSQL):**
- `chk_material_type_blueprint_id`: `blueprint` → `blueprint_id NOT NULL`; los demás → `NULL`.
- `chk_source_key_format`: el `source_key` debe coincidir con el patrón del tipo.
- `uq_crafting_materials_source_key`: UNIQUE en `source_key`.
- `idx_crafting_materials_blueprint_id_unique`: UNIQUE parcial en `blueprint_id WHERE NOT NULL`.
- `fk_crafting_materials_blueprint`: FK `blueprint_id` → `blueprints.id` con `ON DELETE RESTRICT`.

**Decisiones CERRADAS:** identidad/sincronización y restricciones por tipo.
**Decisiones PENDIENTES:** aprobación de la Opción 4 actualizada; importar o no los 77 enchantments en `blueprints`.

Reporte actualizado:
`docs/reports/2026-10-14_revision_source_id_crafting_materials.md`

**Estado:** PROPUESTA — PENDIENTE DE APROBACIÓN HUMANA.

## Hito anterior — 2026-10-14 (revisión de `source_id` en `crafting_materials`)
Se analizó la Discrepancia D-1: `blueprint_components.component_id` es FK a `blueprints.id`, pero solo el 7.5% de los componentes son blueprints reales.

**Datos verificados del Excel v26.9.1 (2,901 referencias, 190 únicos):**
| Categoría | Únicos | Referencias | % |
|---|---|---|---|
| Blueprint | 103 | 217 | 7.5% |
| Quest Component | 66 | 2,424 | 83.6% |
| Sigil | 6 | 237 | 8.2% |
| Enchantment (D-2) | 15 | 23 | 0.8% |

**Tres alternativas evaluadas:**
- **A. Tabla unificada `crafting_materials`** — ✅ RECOMENDADA. Integridad referencial estricta, una sola tabla, resuelve D-1 y D-2.
- **B. Relación polimórfica** — ❌ Pierde FK estricta, JOINs complejos.
- **C. Registros sintéticos en `blueprints`** — ⚠️ Sin cambios de esquema pero contamina `blueprints` con items que no son blueprints.

**Recomendación:** Alternativa A. Crear tabla `crafting_materials` con `material_type` (enum) y `source_id`, modificar la FK de `blueprint_components.component_id` para apuntar a ella.

**D-2:** La Alternativa A resuelve automáticamente las 23 referencias a Enchantments (15 únicos) insertándolos en `crafting_materials` con `material_type = 'enchantment'`.

Reporte:
`docs/reports/2026-10-14_propuesta_modelado_blueprint_components.md`

**Estado:** PROPUESTA — PENDIENTE DE APROBACIÓN HUMANA.

## Hito anterior — 2026-10-13 (ejecución de importación en PostgreSQL)
Se ejecutó `php artisan import:blueprints` contra PostgreSQL con el Excel v26.9.1.

**Comando ejecutado:**
```bash
docker compose exec -T app php -d memory_limit=512M artisan import:blueprints "/var/www/html/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx"
```

**Resultado de la ejecución:**
```text
Filas leídas (excluyendo Enchantment): 1549
Blueprints insertados: 1549
Blueprints actualizados: 0
```

**Validación en PostgreSQL (conteos):**
| Tabla | Count |
|---|---|
| blueprints | 1549 |
| blueprint_workers | 3744 |
| blueprint_resources | 4135 |
| blueprint_crafting_upgrades | 7745 (1549 × 5) |
| blueprint_starforged_milestones | 7745 (1549 × 5) |
| blueprint_ascension_upgrades | 4647 (1549 × 3) |
| blueprint_transcendence_upgrades | 4647 (1549 × 3) |

**Verificaciones de integridad:**
- 0 Enchantments importados (77 excluidos correctamente).
- 1549/1549 blueprints con las 4 FK de elements en `NULL`.
- Muestra "Squire Sword": tier=1, gold_value=50, craft_time=15s, merchant_xp=135, worker_xp=2, worker=Blacksmith(nivel 1), recurso=Iron×5. Coincide con el Excel.
- **Idempotencia:** segunda ejecución = 0 insertados, 1549 actualizados. Conteos idénticos.

**Estado:** EJECUTADA Y VALIDADA.

## Hito anterior — 2026-10-13 (revisión puntual y final)
Se realizó la revisión puntual y final del BlueprintImporter Etapa 2 contra el Excel fuente v26.9.1 (1626 filas). **Sin modificar código.** Tres puntos revisados:

1. **Campos numéricos obligatorios (8):** 0 vacíos y 0 `"---"` en las 1626 filas. El fallback `?? 0` nunca se activa; es defensivo y no altera datos. Las energías solo tienen `"---"` en "Fresh Spirit" (fila 1584), que es Enchantment y se excluye.
2. **Mapeo de columnas:** Todas las columnas verificadas coinciden con los encabezados reales (workers R-W, recursos Y-AJ, 4 tablas de upgrades, estadísticas AS-AX, fecha CS). Nota menor: header de R tiene espacio final, sin impacto.
3. **Validación y rollback:** La validación previa impide escribir (`ImportResult::failure` antes de la transacción). La transacción global con `catch (Throwable)` revierte todo ante errores de persistencia. Tests verifican `Blueprint::count() === 0` tras errores.

**Resultado:** No se detectaron problemas. El código está listo para aprobación.

Reporte:
`docs/reports/2026-10-13_revision_puntual_blueprint_importer.md`

**Estado:** REVISADA — PENDIENTE DE APROBACIÓN HUMANA.

## Hito anterior — 2026-10-13 (implementación BlueprintImporter Etapa 2)
Se implementó el comando `php artisan import:blueprints {file}` en 4 capas:
- `BlueprintExcelReader` — lee el Excel v26.9.1, convierte `"---"`/vacío a `null`, excluye `Enchantment`.
- `BlueprintRowData` — DTO con `toBlueprintAttributes()`.
- `BlueprintImportService` — transacción global, idempotencia (`updateOrCreate` + `delete-then-insert`), validación previa, rollback ante errores.
- `ImportBlueprints` — comando Artisan con métricas y reporte de errores.

Decisiones aplicadas: excluir Enchantment, 4 FK de elements en `null`, no importar `blueprint_components`, no modificar migraciones/modelos.

Validaciones:
- `php -l` en 5 archivos → sin errores.
- `pint --test` → 7 files PASS.
- `php artisan test` → 17 passed (99 assertions).
- Comando `import:blueprints` registrado en Artisan.

Bug corregido: variable `$wasCreated` sin inicializar causaba error de tipado estricto en PHP 8.4.

Discrepancias D-1, D-2, D-3 permanecen pendientes (fuera de alcance de esta etapa).

**NO se ejecutó contra PostgreSQL real** por restricción del protocolo.

Reporte:
`docs/reports/2026-10-13_implementacion_blueprint_importer_etapa2.md`

**Estado:** IMPLEMENTADA — PENDIENTE DE REVISIÓN.

## Hito anterior — 2026-10-13 (análisis BlueprintImporter)
Se completó el análisis de diseño del `BlueprintImporter` para el MVP Blueprints. **Sin implementar código.** Hallazgos críticos:

- **Discrepancia D-1:** `blueprint_components.component_id` (FK a `blueprints.id`) solo resuelve el 8.3% de las referencias. El 91.7% de los componentes NO son blueprints: 66 son Quest Components (83.6%) y 6 son Sigils de Full Moon Fusions (8.2%).
- **Discrepancia D-2:** 23 blueprints no-Enchantment dependen de componentes Enchantment. Si se excluyen los 77 Enchantments, estas referencias quedan huérfanas.
- **Discrepancia D-3:** `elements` sin poblar (101 valores únicos en cols AZ-BC). Las 4 FK de afinidad quedarían en `null`.

Reporte:
`docs/reports/2026-10-13_analisis_blueprint_importer_mvp.md`

**Estado:** PROPUESTA — PENDIENTE DE REVISIÓN.

## Hito anterior — 2026-10-13 (ejecución y validación)
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
- Análisis BlueprintImporter: 🟢 PROPUESTA APROBADA
- BlueprintImporter Etapa 2 (sin componentes): 🟢 EJECUTADA Y VALIDADA — 1549 blueprints en PostgreSQL
- D-1 `crafting_materials`: 🟢 EJECUTADA Y VALIDADA POR CONTEOS. `crafting_materials`=186 (99/66/6/15) y `blueprint_components`=2,747. Memoria PHP configurada de forma permanente (`memory_limit=512M` en la imagen).
- Comando `import:blueprint-components`: 🟢 EJECUTADO sobre Excel v26.9.1 → 1.549 procesados, 2.747 insertados, 0 incidencias. Revision final completada; H-1/H-2/H-3 implementados (H-4 sin cambios). Pendiente de revisión.
- Preflight D-1 (solo lectura): 🟢 COMPLETADO — LISTO PARA SOLICITAR AUTORIZACIÓN (sin bloqueos; migraciones D-1 Pending; `blueprints`=1549, `blueprint_components`=0, `crafting_materials` no existe).
- API: ⏳ PENDIENTE
- Tests: 🟢 46 passed (382 assertions) — 45 previos + 1 nuevo del comando

## Próximo paso
D-1 está ejecutada y validada por conteos, el límite de memoria PHP quedó configurado de forma permanente (`memory_limit=512M`) y los hallazgos H-1/H-2/H-3 del importador de componentes están implementados. Queda pendiente:

1. Revisión humana de H-1/H-2/H-3 (estado actual: IMPLEMENTADA — PENDIENTE DE REVISIÓN).
2. (Opcional, no autorizado) decidir si se activa `log_errors`/`error_log` para fatales.
3. (Opcional, no autorizado) H-4: valorar un segundo código de salida para "completado con incidencias" (actualmente sin cambios).

Validación ya obtenida: `crafting_materials`=186 (99 blueprint, 66 quest_component, 6 sigil, 15 enchantment), `blueprint_components`=2,747 y `memory_limit=512M` efectivo. Tests: `ImportBlueprintComponentsCommandTest` 7 passed; relacionados 22 passed.

## Regla
El detalle de cada tarea vive en `docs/reports/`. Este archivo resume estado, decisiones y próximos pasos.
