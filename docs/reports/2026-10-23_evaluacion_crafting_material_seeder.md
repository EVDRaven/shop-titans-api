# Reporte Técnico — Evaluación de `CraftingMaterialSeeder` en `DatabaseSeeder`

**Fecha:** 2026-10-23
**Autor:** Editor IA
**Estado:** PROPUESTA (investigación de solo lectura completada; decisión de implementación pendiente de aprobación humana)

## 1. Objetivo

Determinar si conviene incorporar `Database\Seeders\CraftingMaterialSeeder` a `src/database/seeders/DatabaseSeeder.php` en el proyecto Shop Titans API, evaluando idempotencia, dependencias, efectos de `db:seed` repetido y coherencia con una instalación nueva; y comparar alternativas de implementación.

## 2. Alcance

**Incluye:** inspección estática de seeders, importadores, comandos, migraciones, modelo de datos y pruebas existentes; análisis de dependencias; evaluación de idempotencia; matriz de alternativas.

**Excluye:** cualquier modificación de código, configuración, esquema o datos; ejecución de `db:seed`, seeders individuales, importadores, migraciones o comandos destructivos; escrituras en PostgreSQL o cualquier base; commits o pushes. La decisión final y la implementación corresponden al usuario.

## 3. Estado inicial

- La API pública de Blueprints está implementada y sus observaciones H-1..H-4 están corregidas y aprobadas (no se revisan aquí).
- PostgreSQL de desarrollo contiene 186 filas en `crafting_materials` (99 `blueprint` + 66 `quest_component` + 6 `sigil` + 15 `enchantment`).
- `DatabaseSeeder` **no** incluye `CraftingMaterialSeeder` y termina creando un usuario de prueba.

## 4. Archivos inspeccionados (ninguno modificado)

| Archivo | Motivo |
|---|---|
| `src/database/seeders/DatabaseSeeder.php` | Seeder global y su orden actual |
| `src/database/seeders/CraftingMaterialSeeder.php` | Objeto de la evaluación |
| `src/database/seeders/BlueprintCatalogSeeder.php` | Clase base de los seeders de catálogo (resolución de Excel) |
| `src/database/seeders/{Resource,Quality,ItemCategory,Worker,WorkerLevel,Element,Enchantment,SpiritAffinity,Spirit}Seeder.php` | Verificar idempotencia y dependencias |
| `src/app/Imports/BlueprintExcelReader.php` | `readCraftingMaterialData()`: origen de datos y dependencias |
| `src/app/Console/Commands/ImportBlueprints.php`, `ImportBlueprintComponents.php` | Importadores (no seeders) y su rol en el orden |
| `src/database/migrations/2026_10_14_000001_create_crafting_materials_table.php` | Claves, CHECK e índice único parcial |
| `src/database/migrations/2026_10_14_000002_change_blueprint_components_component_id_foreign.php` | FK `blueprint_components.component_id → crafting_materials.id` |
| `src/database/migrations/2026_08_28_100010_create_blueprint_components_table.php` | Estructura y FKs de componentes |
| `src/database/migrations/0001_01_01_000000_create_users_table.php` | `users.email` UNIQUE (efecto de `db:seed` repetido) |
| `src/tests/Feature/CraftingMaterialSeederTest.php` | Pruebas existentes (idempotencia, advertencias, conteos) |
| `src/tests/Feature/BlueprintImportServiceTest.php` | Uso de `$this->seed([...])` con seeders de catálogo |
| `docker/php/Dockerfile` | `memory_limit=512M` requerido por los seeders que leen Excel |

## 5. Hallazgos de la investigación

### 5.1 Seeders que ejecuta actualmente `DatabaseSeeder` y su orden

`DatabaseSeeder::run()` ([DatabaseSeeder.php](file:///home/draven/Documentos/shop-titans-api/src/database/seeders/DatabaseSeeder.php#L16-L34)) invoca, en este orden:

1. `ResourceSeeder`
2. `QualitySeeder`
3. `ItemCategorySeeder`
4. `WorkerSeeder`
5. `WorkerLevelSeeder`
6. `ElementSeeder`
7. `EnchantmentSeeder`
8. `SpiritAffinitySeeder`
9. `SpiritSeeder`

Después, fuera del bloque `call()`, ejecuta `User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com'])`. **`CraftingMaterialSeeder` no está registrado.** Usa `WithoutModelEvents` (no suprime la creación de usuario).

**Hecho verificado.**

### 5.2 Qué hace `CraftingMaterialSeeder`

`CraftingMaterialSeeder::run()` ([CraftingMaterialSeeder.php](file:///home/draven/Documentos/shop-titans-api/src/database/seeders/CraftingMaterialSeeder.php#L17-L105)):

- Lee el Excel mediante `BlueprintExcelReader::readCraftingMaterialData()` (hojas `Blueprints`, `Quest Components`, `Full Moon Fusions`), devolviendo `componentNames`, `blueprintNames`, `questComponents`, `sigils`, `enchantmentNames`.
- Carga `Blueprint::all()->keyBy('name')` y, por cada nombre de componente, lo clasifica en **4 tipos** mediante `CraftingMaterial::updateOrCreate()` con clave `source_key`:
  - `blueprint:` → requiere que exista un blueprint con ese nombre (si no, registra advertencia y lo omite).
  - `quest_component:` → datos de la hoja *Quest Components*.
  - `sigil:` → datos de *Full Moon Fusions* (nombre que empieza por `Sigil of`).
  - `enchantment:` → nombres con tipo `Enchantment` en la hoja *Blueprints*.
- **Modifica exclusivamente la tabla `crafting_materials`.** No toca `blueprints`, `blueprint_components` ni catálogos.
- Objeto de base `BlueprintCatalogSeeder` **no** lo extiende: implementa su propio `resolveFilePath()` (busca el `.xlsx` en `storage/app/`).

**Idempotencia:** ✔ **Sí**, por diseño (`updateOrCreate` sobre `source_key`, que es UNIQUE en el esquema). Confirmado por `test_seeder_is_idempotent` (dos ejecuciones → 88 filas y 88 `source_key` distintos). **No** hace *prune*: no elimina filas que ya no provengan del Excel.

**Hechos verificados.**

### 5.3 Dependencias entre materiales, blueprints, componentes y catálogos

- `crafting_materials.blueprint_id` → FK a `blueprints.id` (`restrictOnDelete`), **nullable**, obligatoriamente no nulo solo cuando `material_type = 'blueprint'` (CHECK `chk_material_type_blueprint_id`, solo en PostgreSQL).
- Índice único parcial `idx_crafting_materials_blueprint_id_unique` (`WHERE blueprint_id IS NOT NULL`): como máximo **un** material por blueprint.
- `blueprint_components.component_id` → FK a `crafting_materials.id` (`restrictOnDelete`), tras la migración `2026_10_14_000002`. Es decir, **`blueprint_components` depende de `crafting_materials`**.
- Los importadores (`import:blueprints`, `import:blueprint-components`) son **comandos Artisan, no seeders**.

**Orden de dependencias resultante (hecho):** catálogos → `import:blueprints` → `CraftingMaterialSeeder` → `import:blueprint-components`.

**Consecuencia inferida:** `CraftingMaterialSeeder` **no es autosuficiente** en una base limpia: sin `blueprints` importados, las 99 filas de tipo `blueprint` no se crean (se emiten advertencias) y `import:blueprint-components` no puede resolver `component_id`. Confirmado indirectamente por `test_missing_blueprints_are_reported_as_warnings`.

### 5.4 Qué ocurre al ejecutar `db:seed` varias veces

- Los 9 seeders de catálogo son **idempotentes** (`updateOrCreate`/`firstOrCreate`): ✔ seguros de re-ejecutar.
- `User::factory()->create(['email' => 'test@example.com'])` en `DatabaseSeeder`: la columna `users.email` es **UNIQUE**. En la **segunda** ejecución se produce una violación de unicidad → excepción `QueryException` → el comando termina con código de error. Como la creación del usuario es **lo último**, los seeders previos ya se aplicaron (éxito parcial, sin rollback global).
- `db:seed` global **nunca** siembra `crafting_materials` → el conjunto de datos del MVP queda incompleto.
- Efecto en `users`: cada ejecución «exitosa» añade/duplica el usuario de prueba respecto a un entorno limpio.

**Hechos verificados** (por lectura del código y del esquema). El fallo exacto de la segunda ejecución es una **inferencia** estándar del comportamiento de PostgreSQL/UNIQUE (no se ejecutó, por prohibición de la tarea); ya había sido documentado en `docs/reports/2026-10-23_preflight_recuperacion_postgresql.md` (R-1).

### 5.5 ¿Es seguro y coherente incorporar `CraftingMaterialSeeder` al seeder global para una instalación nueva?

- **Seguridad de re-ejecución:** ✔ alta (idempotente).
- **Coherencia en instalación nueva:** ✘ **no por sí sola**. `migrate --seed` (o `db:seed`) ejecuta seeders, pero **no** los importadores `import:blueprints` / `import:blueprint-components`. Con `blueprints` vacío, `CraftingMaterialSeeder` crearía solo 87 filas (66 + 6 + 15) y omitiría las 99 de tipo `blueprint` con advertencias, dejando `blueprint_components` irresoluble.
- **Requisito de entorno:** necesita el `.xlsx` presente en `storage/app/` (presente: `Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx`) y `memory_limit=512M` (ya configurado en la imagen, `docker/php/Dockerfile:24`).
- **Performance:** el seeder global ya lee el Excel 4 veces (catalog seeders) y añadiría una 5.ª lectura pesada.

**Conclusión (hecho + inferencia):** incorporarlo **solo** a `DatabaseSeeder` no produce una instalación nueva coherente; el problema de fondo es que los importadores no son seeders.

### 5.6 Alternativas

| Alternativa | Descripción | Ventajas | Riesgos / consecuencias |
|---|---|---|---|
| **A. Mantenerlo independiente (statu quo)** | No tocar `DatabaseSeeder`; ejecutar `db:seed --class=CraftingMaterialSeeder` en el orden de las 5 fases | Sin cambios de código; coherente con D-1 y con la recuperación validada; no fuerza IDs ni usuarios | No hay un único comando reproducible para una instalación nueva; el orden depende de conocimiento externo/documentación |
| **B. Incorporarlo a `DatabaseSeeder`** | Añadir `CraftingMaterialSeeder::class` al bloque `call()` | Un solo `db:seed` siembra catálogos + materiales; idempotente | **Incoherente**: sin `import:blueprints` previo, faltan las 99 filas `blueprint`; no arregla el `User::factory()`; recalca que el seeder global no es el lugar de los datos de Excel |
| **C. Seeder orquestador del MVP (p. ej. `BlueprintMvpSeeder`)** | Nuevo seeder que invoque catálogos + `Artisan::call('import:blueprints')` + `CraftingMaterialSeeder` + `Artisan::call('import:blueprint-components')`, y que decida explícitamente el asunto de `User::factory()` | Instalación nueva reproducible en un comando; el orden de dependencias queda codificado; idempotente de extremo a extremo | Mayor superficie de código; acopla seeders a comandos Artisan; requiere decidir el `User::factory()`; nuevo artefacto a mantener |

## 6. Hechos verificados vs. inferencias

**Hechos verificados (por lectura de código/esquema/pruebas):**

1. `DatabaseSeeder` invoca 9 seeders de catálogo y luego `User::factory()->create(...)`; no incluye `CraftingMaterialSeeder`.
2. Los 9 seeders de catálogo son idempotentes (`updateOrCreate`/`firstOrCreate`).
3. `CraftingMaterialSeeder` es idempotente, escribe solo `crafting_materials`, y depende de `blueprints` (por nombre) para el tipo `blueprint`.
4. `blueprint_components.component_id` referencia `crafting_materials.id` (RESTRICT); `crafting_materials.blueprint_id` referencia `blueprints.id` (RESTRICT, nullable, CHECK de tipo).
5. `users.email` es UNIQUE.
6. Los importadores son comandos Artisan, no seeders.
7. `CraftingMaterialSeederTest` cubre 4 tipos, atributos, formato de `source_key`, idempotencia (88 filas) y advertencias por blueprints ausentes.
8. El `.xlsx` está presente en `storage/app/` y la imagen define `memory_limit=512M`.

**Inferencias (razonamiento sobre los hechos, sin ejecución):**

1. Ejecutar `db:seed` dos veces fallaría en la creación del usuario por UNIQUE(`email`), después de aplicar los seeders.
2. Añadir `CraftingMaterialSeeder` a `DatabaseSeeder` sin importar blueprints primero dejaría incompletas las 99 filas `blueprint`.
3. Un `migrate --seed` sobre base limpia no reproduciría el dataset del MVP sin invocar los importadores.

## 7. Discrepancias

**EC-1 (documental, menor).** `PROJECT_CONTEXT.md` (línea 31) afirma «Seeders, BlueprintImporter y API aún no implementados», mientras que el repositorio ya contiene 11 seeders, 2 importadores y la API implementada. **Estado documentado:** pendiente. **Estado real:** implementado. **Impacto:** informativo; puede inducir a error en la planificación. **Propuesta:** actualizar esa línea (fuera del alcance de esta tarea; requiere aprobación).

## 8. Decisiones

### Propuestas
- **EP-1:** No incorporar `CraftingMaterialSeeder` aislado a `DatabaseSeeder` (rechazar B).
- **EP-2:** Si se desea reproducibilidad de instalación nueva, adoptar un **seeder orquestador del MVP** (C) que codifique el orden catálogos → blueprints → materiales → componentes.
- **EP-3:** Resolver el asunto de `User::factory()` en `DatabaseSeeder` (retirarlo o condicionarlo a entorno) de forma independiente a EP-2.

### Aprobadas
- Ninguna (esta tarea es de evaluación; la decisión corresponde al usuario).

### Reemplazadas/Rechazadas
- Ninguna.

## 9. Validaciones

**No se ejecutó ningún comando** (ni seeders, ni importadores, ni migraciones, ni escrituras). La validación fue **inspección estática de solo lectura** (lectura de archivos y búsquedas en el repositorio). La coherencia funcional del seeder se respalda en la suite existente `CraftingMaterialSeederTest` (ya presente en el repositorio), no ejecutada en esta tarea por no ser necesaria para la evaluación.

## 10. Resultado final

`CraftingMaterialSeeder` es **idempotente y seguro de re-ejecutar**, pero **no es coherente incorporarlo aislado** a `DatabaseSeeder`: en una base limpia faltarían las 99 filas `blueprint` (requieren `import:blueprints`) y `blueprint_components` quedaría irresoluble, además de persistir el problema de idempotencia por `User::factory()`. La recomendación es **rechazar la incorporación aislada (B)** y, si se busca instalación nueva reproducible, crear un **seeder orquestador del MVP (C)**.

## 11. Riesgos

| # | Riesgo | Mitigación |
|---|---|---|
| R-1 | Incorporarlo aislado produce datos incompletos (99 filas `blueprint` ausentes) | Exigir `import:blueprints` antes (opción C) |
| R-2 | `User::factory()` rompe la idempotencia de `db:seed` por UNIQUE(`email`) | Decidir el tratamiento del usuario de prueba (EP-3) |
| R-3 | Acoplar seeders a comandos Artisan (opción C) añade superficie y dependencia de orden | Encapsular y documentar; pruebas de integración |
| R-4 | Estabilidad de `source_key` `blueprint:<id>` depende de que los IDs de blueprint se conserven al reimportar | `import:blueprints` idempotente por nombre con IDs estables (comportamiento ya observado en la recuperación) |

## 12. Pendientes

- Decisión de aprobación de EP-1..EP-3 (§8).
- Actualización de EC-1 (§7) en `PROJECT_CONTEXT.md`, si se aprueba.

## 13. Preguntas para ChatGPT / aprobación humana

1. ¿Se aprueba **no** incorporar `CraftingMaterialSeeder` aislado a `DatabaseSeeder` (EP-1)?
2. ¿Se desea un **seeder orquestador del MVP** (EP-2, opción C) para instalación nueva reproducible? ¿O se mantiene el statu quo (A)?
3. ¿Cómo se trata `User::factory()` en `DatabaseSeeder` (retirar, condicionar a entorno, o dejar como está)?

## 14. Próximo paso recomendado

Recibir la decisión sobre EP-1..EP-3. **No modificar `DatabaseSeeder` en esta tarea.** Si se aprueba C, planificar el `BlueprintMvpSeeder` como tarea separada con su propio informe y validación.
