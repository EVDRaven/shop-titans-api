# Reporte Técnico — Implementación del modelado de elementos y afinidades (AZ–BC)

**Fecha:** 2026-10-16
**Autor:** Editor IA
**Estado:** IMPLEMENTADO — PENDIENTE DE REVISIÓN

## 1. Objetivo

Implementar en el código del proyecto `shop-titans-api` la propuesta de modelado **Alternativa C** para las columnas AZ–BC de la hoja `Blueprints` del Excel fuente v26.9.1, aprobada por el usuario como base de diseño.

Aclaraciones semánticas aportadas por el usuario:
- `Spirit Affinity` (BA) representa una afinidad que permite a un objeto crafteado ganar más puntos de un elemento o espíritu.
- En `Elemental Affinity` (AZ), cuando hay dos elementos, **el orden puede representar prioridad** (el primero es prioritario respecto al segundo).

## 2. Alcance

**Incluye:**
- Migraciones incrementales nuevas para crear los catálogos `enchantments`, `spirit_affinities`, `spirits` y la tabla intermedia `blueprint_elemental_affinities`, y para re-apuntar las FKs de afinidad de `blueprints`.
- Modelos Eloquent nuevos y ajuste de modelos existentes (`Blueprint`, `Element`).
- Extensión del DTO `BlueprintRowData`, del lector `BlueprintExcelReader` y del servicio `BlueprintImportService` para resolver y persistir AZ–BC.
- Seeders idempotentes para los catálogos nuevos y registro en `DatabaseSeeder`.
- Ampliación de tests automatizados.
- Actualización de `DATA_MODEL_ANALYSIS.md` y de `AI_WORK_LOG.md`, y creación de este reporte.

**Excluye (prohibido en esta tarea):**
- Ejecutar `migrate`, `db:seed`, importadores o cualquier escritura SQL en PostgreSQL.
- Modificar retroactivamente migraciones ya aplicadas.
- Eliminar tablas o datos existentes.
- Inventar fórmulas de puntuación, lógica de juego o asociaciones no respaldadas por la fuente.

## 3. Estado inicial

- D-1 (`crafting_materials`) ejecutada y validada por conteos: `crafting_materials`=186, `blueprint_components`=2.747, `blueprints`=1.549.
- 14 migraciones del MVP Blueprints **ya aplicadas** en PostgreSQL; `elements` existía como catálogo (`id`, `name UNIQUE`, `timestamps`) pero **sin poblar**.
- `blueprints` contenía 4 FKs creadas apuntando a `elements`: `elemental_affinity_id` (AZ), `spirit_affinity_id` (BA), `built_in_element_id` (BB), `built_in_spirit_id` (BC). Las 4 quedaron en `NULL` tras la importación (1.549/1.549) porque el importador nunca las pobló.
- El catálogo `enchantments` **no existía** (ni migración ni modelo); solo aparecía como valor del enum `material_type` de `crafting_materials` y como nombres de materiales. `DATA_MODEL_ANALYSIS.md` §3.4 documentaba una hipótesis de `elements = {Fire, Water, Earth, Air, Light, Dark, Spirit, Soul, None}`, refutada por el análisis D-3.
- `PROJECT_CONTEXT.md` indicaba que "Skills y Enchantments quedan fuera del MVP".

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| `src/database/migrations/2026_10_16_000001_create_enchantments_table.php` | Creado | Catálogo `enchantments` (name UNIQUE, type + CHECK `Element|Spirit`). |
| `src/database/migrations/2026_10_16_000002_create_spirit_affinities_table.php` | Creado | Catálogo `spirit_affinities` (name UNIQUE) para BA. |
| `src/database/migrations/2026_10_16_000003_create_spirits_table.php` | Creado | Catálogo `spirits` (name UNIQUE) para BC. |
| `src/database/migrations/2026_10_16_000004_create_blueprint_elemental_affinities_table.php` | Creado | Tabla 1:N AZ con `position` (CHECK 1..2), `raw_value`, dobles UNIQUE. |
| `src/database/migrations/2026_10_16_000005_change_blueprints_affinity_foreign_keys.php` | Creado | Re-apunta BA/BB/BC y elimina AZ; aborta si las columnas tienen datos. |
| `src/app/Models/Enchantment.php` | Creado | Modelo del catálogo BB. |
| `src/app/Models/SpiritAffinity.php` | Creado | Modelo del catálogo BA. |
| `src/app/Models/Spirit.php` | Creado | Modelo del catálogo BC. |
| `src/app/Models/BlueprintElementalAffinity.php` | Creado | Modelo de la relación 1:N AZ. |
| `src/app/Models/Blueprint.php` | Modificado | Se elimina `elemental_affinity_id` de `$fillable`; nuevas relaciones de afinidad. |
| `src/app/Models/Element.php` | Modificado | Nueva relación `blueprintElementalAffinities()`. |
| `src/app/Imports/BlueprintRowData.php` | Modificado | 5 campos nuevos de afinidad (con defaults, compatibles con tests). |
| `src/app/Imports/BlueprintExcelReader.php` | Modificado | Constantes AZ–BC, parser que preserva orden, lectores de catálogos. |
| `src/app/Services/BlueprintImportService.php` | Modificado | Resolución/validación/persistencia de afinidades; catálogos nuevos. |
| `src/database/seeders/BlueprintCatalogSeeder.php` | Creado | Clase base abstracta para seeders que leen el Excel. |
| `src/database/seeders/ElementSeeder.php` | Creado | Puebla `elements` con los tokens base de AZ. |
| `src/database/seeders/EnchantmentSeeder.php` | Creado | Puebla `enchantments` desde la hoja `Enchantments`. |
| `src/database/seeders/SpiritAffinitySeeder.php` | Creado | Puebla `spirit_affinities` desde BA. |
| `src/database/seeders/SpiritSeeder.php` | Creado | Puebla `spirits` desde BC. |
| `src/database/seeders/DatabaseSeeder.php` | Modificado | Registra los 4 seeders nuevos. |
| `src/tests/Feature/BlueprintImportServiceTest.php` | Modificado | 6 tests de afinidades + DTO ampliado. |
| `src/tests/Feature/BlueprintAffinityForeignKeysRollbackTest.php` | Creado | Pruebas del `down()` seguro de `2026_10_16_000005`. |
| `DATA_MODEL_ANALYSIS.md` | Modificado | Corrige hipótesis refutadas y documenta el modelo D-3. |
| `AI_WORK_LOG.md` | Modificado | Estado de la tarea D-3. |

## 5. Cambios realizados

### 5.1 AZ — `Elemental Affinity` (tabla 1:N)
- `blueprint_elemental_affinities`: `blueprint_id` (FK `blueprints`, `cascadeOnDelete`), `element_id` (FK `elements`, `restrictOnDelete`), `position` (`unsignedTinyInteger`), `raw_value` (valor literal de la celda AZ, para auditoría).
- UNIQUE `(blueprint_id, element_id)` y UNIQUE `(blueprint_id, position)`; CHECK PostgreSQL `position IN (1, 2)`.
- `Blueprint::elementalAffinities()` (`hasMany`); `Element::blueprintElementalAffinities()` (`hasMany`).
- AZ se reconstruye en cada importación (delete-then-insert) y **preserva el orden original** de la celda: posición 1 = prioritario, posición 2 = secundario. `raw_value` conserva el literal del Excel.

### 5.2 BA — `Spirit Affinity` (FK directa)
- Catálogo independiente `spirit_affinities`; `blueprints.spirit_affinity_id` re-apuntado a `spirit_affinities.id` (`nullOnDelete`).
- `Blueprint::spiritAffinity()`, `SpiritAffinity::blueprints()`.
- No se relaciona automáticamente con `enchantments`.

### 5.3 BB — `Built-In Element` (FK a `enchantments`)
- Catálogo nuevo `enchantments` (`name UNIQUE`, `type`, CHECK `type IN ('Element','Spirit')`); `blueprints.built_in_element_id` re-apuntado a `enchantments.id` (`nullOnDelete`).
- `Blueprint::builtInElement()`, `Enchantment::blueprintsByBuiltInElement()`.

### 5.4 BC — `Built-In Spirit` (FK directa)
- Catálogo independiente `spirits`; `blueprints.built_in_spirit_id` re-apuntado a `spirits.id` (`nullOnDelete`).
- Incluye `Mundra's Spirit`. No se asume equivalencia con los encantamientos de tipo Spirit.

### 5.5 Migración de cambio de FKs (seguridad)
`2026_10_16_000005`:
- Antes de cualquier DDL ejecuta `assertAffinityColumnsAreEmpty()`: recorre las 4 columnas, y si alguna contiene valores no nulos lanza `RuntimeException` abortando la migración (evita pérdida silenciosa).
- Ejecuta 4 sentencias `Schema::table` separadas para AZ/BA/BB/BC (drop + recreate por bloque; en Laravel no funciona drop y create de FK en la misma closure).
- `down()`: **revisión correctiva** — antes de cualquier DDL ejecuta la misma guarda `assertAffinityColumnsAreEmpty()` y una nueva `assertElementalAffinitiesTableIsEmpty()`. Motivo: tras un import, los IDs de BA/BB/BC pertenecen a `spirit_affinities`/`enchantments`/`spirits` y no pueden reinterpretarse como IDs de `elements` (mismo número, catálogo distinto) sin corromper los datos en silencio; y la relación 1:N de AZ no puede colapsarse de forma fiel en una única columna. Si hay datos, la reversión **aborta antes de tocar el esquema**. Después restaura las 3 FKs hacia `elements` y recrea `elemental_affinity_id`.

### 5.6 Lectura del Excel (`BlueprintExcelReader`)
- Constantes: `COLUMN_ELEMENTAL_AFFINITY='AZ'`, `COLUMN_SPIRIT_AFFINITY='BA'`, `COLUMN_BUILT_IN_ELEMENT='BB'`, `COLUMN_BUILT_IN_SPIRIT='BC'`, `ENCHANTMENTS_SHEET='Enchantments'`.
- `parseElementalAffinities()`: divide AZ por comas, aplica `trim`, **filtra tokens vacíos y preserva el orden**.
- Lectores: `readElementalAffinityTokens()` (AZ, split comas), `readSpiritAffinityNames()` (BA), `readBuiltInElementNames()` (BB), `readBuiltInSpiritNames()` (BC) y `readEnchantmentCatalog()` (hoja `Enchantments`, `A`=name, `B`=type).
- `parseRow()` alimenta el DTO con `elementalAffinityRaw`, `elementalAffinities`, `spiritAffinityName`, `builtInElementName`, `builtInSpiritName`.
- Los valores vacíos `---` se convierten a `null` por la convención centralizada `getString()`; AZ nulo → `[]`.

### 5.7 Importación (`BlueprintImportService`)
- `loadCatalogs()` carga además `elementsByName`, `spiritAffinitiesByName`, `enchantmentsByName`, `spiritsByName` (`keyBy('name')`).
- `validateRow()` reporta errores explícitos por cada valor no resuelto (elemento de AZ, afinidad espiritual BA, elemento incorporado BB, espíritu incorporado BC). **Revisión correctiva:** para BB valida además que el encantamiento resuelto sea de tipo `Element` (`Enchantment::TYPE_ELEMENT`); si es de tipo `Spirit`, la fila se reporta como error (integridad de `built_in_element_id`).
- `persistAffinities()` (invocado entre `persistResources` y `persistCraftingUpgrades`): reconstruye AZ y asigna `spirit_affinity_id`/`built_in_element_id`/`built_in_spirit_id`. Idempotente ante reimportación.

### 5.8 Seeders
- `BlueprintCatalogSeeder` (base abstracta: resuelve la ruta del Excel y expone `$reader`).
- `ElementSeeder`, `EnchantmentSeeder`, `SpiritAffinitySeeder`, `SpiritSeeder`, todos idempotentes vía `updateOrCreate`.
- Registrados en `DatabaseSeeder` tras `WorkerLevelSeeder`. `CraftingMaterialSeeder` se mantiene fuera de `DatabaseSeeder` (se ejecuta por clase, como en D-1).

### 5.9 Tests
6 tests en `BlueprintImportServiceTest`: resolución de AZ preservando orden, reporte de afinidad AZ no resuelta, no duplicación de AZ en reimportación, resolución de BA/BB/BC, reporte de BB (`built_in_element`) no resuelto y **revisión correctiva:** reporte de BB con tipo incorrecto (`Spirit`). Se sustituyó la antigua aserción sobre `elemental_affinity_id` (columna eliminada) por el conteo de `elementalAffinities()`.
Nuevo `BlueprintAffinityForeignKeysRollbackTest` (3 tests): `down()` con tablas vacías revierte; `down()` aborta si las columnas de afinidad tienen datos; `down()` aborta si `blueprint_elemental_affinities` tiene filas.

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| BB coincide al 100 % con la hoja `Enchantments` | Análisis D-3 (30/30 nombres) | Confirmada |
| BC (`Mundra's Spirit`) NO está en `Enchantments` | Análisis D-3 | Confirmada → catálogo `spirits` independiente |
| AZ tiene 1–2 elementos con orden significativo | Datos D-3 (compuestos con orden no canónico) | Confirmada → tabla 1:N con `position` |
| `elements` no estaba poblado y las 4 FK de `blueprints` eran `NULL` | `AI_WORK_LOG.md` (importación D-1: "1549/1549 con las 4 FK en NULL") | Confirmada → migración de cambio de FK segura |
| El catálogo `enchantments` no existía | `grep`/`LS` sobre migraciones y modelos | Confirmada → se creó (aprobado por el usuario) |
| La hipótesis previa `elements = {..., Spirit, Soul, None}` era correcta | Datos reales AZ–BC | **Rechazada** (no hay `Spirit`/`Soul`/`None`) |

## 7. Discrepancias

1. **`enchantments` inexistente (bloqueante).**
   - *Estado documentado:* el diseño BB ordenaba "reutilizar el catálogo `enchantments`".
   - *Estado real:* no existía migración ni modelo de `enchantments`; `PROJECT_CONTEXT.md` decía que Enchantments quedaba fuera del MVP.
   - *Evidencia:* ausencia en 19 migraciones y 16 modelos; solo aparecía como valor del enum `material_type` y como nombres de material.
   - *Impacto:* sin catálogo, BB no podía resolverse.
   - *Propuesta y aprobación:* se consultó al usuario; **aprobó crear el catálogo `enchantments` ahora**.

2. **`DATA_MODEL_ANALYSIS.md` §3.4 refutada.** Documentaba `elements = {Fire, Water, Earth, Air, Light, Dark, Spirit, Soul, None}`; los datos no contienen `Spirit`/`Soul`/`None`. Corregido en el propio documento.

3. **`Gold` y `All` en `elements`.** Se mantienen provisionalmente como valores diferenciados, coherente con el diseño aprobado. Su significado de juego queda pendiente de investigación.

4. **Sin discrepancias de datos pendientes que bloqueen.** Las 4 columnas estaban vacías, por lo que el cambio de FK no requiere migración de datos.

## 8. Decisiones

### Propuestas
- Crear el catálogo `enchantments` (necesario para BB).
- Registrar los seeders de catálogos en `DatabaseSeeder`.

### Aprobadas
- **Alternativa C** (por el usuario) como base de diseño para AZ–BC.
- **Creación del catálogo `enchantments`** ahora (aprobación explícita en esta sesión).
- Orden de prioridad en AZ vía `position` (aclaración del usuario).
- `Spirit Affinity` como afinidad de objeto contra catálogo propio, sin asociarla a `enchantments`.

### Reemplazadas/Rechazadas
- Hipótesis de `elements` con `Spirit`/`Soul`/`None` (rechazada por datos).
- Uso de un único catálogo `elements` para las 4 columnas (reemplazado por el modelo multi-catálogo).

## 9. Validaciones

**Ejecutadas en la revisión correctiva (2026-10-16)** — dentro del contenedor `app` (Docker Desktop iniciado en esta sesión). Los tests usan SQLite `:memory:` (`phpunit.xml`), por lo que **no escriben en PostgreSQL**.

```bash
docker compose exec -T app php -l <21 archivos D-3>
docker compose exec -T app vendor/bin/pint --test <21 archivos D-3>
docker compose exec -T app php artisan test
```

Resultado / exit code:
```text
php -l:  "No syntax errors detected" en 21/21 archivos → EXIT 0
pint:    PASS (21 files) → EXIT 0
php artisan test:  Tests: 56 passed (421 assertions) → EXIT 0
  - Tests\Feature\BlueprintImportServiceTest: 12 passed (incluye tipo incorrecto de BB)
  - Tests\Feature\BlueprintAffinityForeignKeysRollbackTest: 3 passed
```

Validaciones adicionales:
- Inspección estática de las 5 migraciones, los 4 modelos nuevos, los 2 modificados, el DTO, el lector, el servicio, los 5 seeders y los tests.
- Diagnósticos IDE (servidor de lenguaje PHP) sobre los archivos tocados: **sin errores**.

**No ejecutado:** no se ejecutaron `migrate`, `db:seed`, importadores ni escrituras SQL en PostgreSQL. No se afirma que las migraciones funcionen en PostgreSQL: solo se validó su paso por la suite sobre SQLite `:memory:`.

## 10. Resultado final

**IMPLEMENTADO — PENDIENTE DE REVISIÓN.** El modelado AZ–BC está implementado en código (migraciones, modelos, seeders, lector, servicio y tests) y **validado por la suite automatizada sobre SQLite `:memory:` (56 passed, 421 assertions)**. **No está EJECUTADO ni VALIDADO en PostgreSQL**: no se afirma que las migraciones funcionen en PostgreSQL porque no se han ejecutado en un entorno autorizado.

## 11. Riesgos

- **CADENA no ejecutada en PostgreSQL.** El paso de las migraciones por SQLite `:memory:` (suite de tests) no garantiza que el `down()`/`up()` de cambio de FK funcione idénticamente en PostgreSQL; debe validarse al autorizar la ejecución. Mitigado con guardas que abortan ante datos.
- **Restricción de unicidad en AZ.** UNIQUE `(blueprint_id, element_id)` impediría persistir un mismo elemento repetido (p. ej. `"Fire, Fire"`); el CHECK `position IN (1,2)` impediría un tercer elemento. Los datos observados no muestran esos casos; si aparecieran, la importación fallaría y debería revisarse.
- **Integridad de `built_in_element_id`.** Garantizada a nivel de **aplicación** (el importador rechaza encantamientos de tipo `Spirit`). No hay constraint a nivel de base de datos (ver §12).
- **`Gold`/`All`** siguen sin semántica confirmada.
- **Identidad por nombre.** Los catálogos nuevos usan `name UNIQUE` como identidad, con el mismo riesgo de estabilidad entre versiones del Excel ya documentado en D-1.

## 12. Pendientes

- Ejecutar (tras autorización): `migrate` → seeders de catálogos → reimportación de blueprints para poblar AZ–BC.
- Investigar el significado de `Gold` y `All`.
- Decidir si los catálogos de afinidad deben exponerse vía API.
- **Integridad de BB a nivel de base de datos (diferida).** La garantía `built_in_element_id → enchantments(type='Element')` se implementó en la aplicación por ser la solución sencilla compatible con PostgreSQL y SQLite. La alternativa a nivel de motor sería un FK compuesto `(built_in_element_id, built_in_element_type)` → `enchantments(id, type)` con una columna generada `built_in_element_type = 'Element'` y `UNIQUE(id, type)`; se considera desproporcionada para el estado actual y queda como propuesta, no implementada.

## 13. Preguntas para ChatGPT

1. ¿Se acepta la integridad de BB solo a nivel de aplicación, o se prefiere el FK compuesto con columna generada descrito en §12?
2. ¿El UNIQUE `(blueprint_id, position)` y el CHECK `position IN (1,2)` son suficientes o conviene relajarlos preventivamente?
3. ¿`Gold`/`All` deberían modelarse como elementos de pleno derecho o como un catálogo auxiliar aparte?

## 14. Próximo paso recomendado

Revisión humana de este reporte y del código D-3. Si se aprueba, autorizar la ejecución ordenada de las 5 migraciones, los seeders de catálogos y la reimportación de blueprints para poblar AZ–BC, y validar por conteos en PostgreSQL.

## 15. Revisión correctiva previa a la ejecución (2026-10-16)

| Punto | Hallazgo | Acción |
|---|---|---|
| 1. Reversibilidad de `000005` | `down()` restauraba FKs hacia `elements` sin comprobar datos: tras un import, IDs de otros catálogos se reinterpretarían como IDs de `elements` (corrupción silenciosa); la tabla 1:N tampoco se colapsa fielmente. | Se añadieron guardas en `down()` (`assertAffinityColumnsAreEmpty()` + nueva `assertElementalAffinitiesTableIsEmpty()`) que **abortan antes de tocar el esquema** si hay datos. Nuevo test `BlueprintAffinityForeignKeysRollbackTest` (3 tests). |
| 2. Integridad de `Built-In Element` | No se garantizaba que `built_in_element_id` fuese de tipo `Element`. | Validación en la aplicación: `BlueprintImportService::validateRow()` rechaza encantamientos de tipo `Spirit`. Solución sencilla y compatible con PostgreSQL y SQLite. FK compuesto a nivel de motor documentado como alternativa diferida (§12). Nuevo test de tipo incorrecto. |
| 3. Pruebas | En la creación no había entorno disponible. | Docker Desktop se inició y el contenedor `app` quedó operativo. `php -l` OK (21/21), Pint PASS (21 files), suite **56 passed (421 assertions)** sobre SQLite `:memory:`. |
| 4. Integridad de importación/seeders | Revisión de cobertura AZ–BC. | Seeders y importador derivan de las mismas columnas con la misma exclusión de filas `Enchantment`; AZ preserva el orden (`position`); los valores no resueltos se reportan y provocan `failure`. Sin hallazgos que requieran cambios. |

**Limitación persistente:** nada de lo anterior se ejecutó en PostgreSQL. El resultado sigue siendo **IMPLEMENTADO — PENDIENTE DE REVISIÓN**, no EJECUTADO ni VALIDADO en PostgreSQL.
