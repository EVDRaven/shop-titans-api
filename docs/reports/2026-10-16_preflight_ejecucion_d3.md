# Reporte Técnico — Preflight previo a la ejecución en PostgreSQL (D-3)

**Fecha:** 2026-10-16
**Autor:** Editor IA
**Estado:** IMPLEMENTADO — PENDIENTE DE REVISIÓN (resultado: LISTO PARA EJECUCIÓN)

## 1. Objetivo

Verificar el estado real de PostgreSQL antes de ejecutar las cinco migraciones nuevas de D-3, los seeders de catálogos y la reimportación de blueprints para poblar AZ–BC. Únicamente consultas de lectura.

## 2. Alcance

**Incluye:** estado del repo, estado de contenedores, versión y conexión de PostgreSQL, `migrate:status`, existencia de tablas, inspección de las 4 columnas de afinidad de `blueprints` (tipos, FKs, conteos no nulos), conteos de registros y confirmación del Excel fuente en el contenedor.

**Excluye (prohibido en esta fase):** `migrate`, `db:seed`, `import:blueprints`, `import:blueprint-components`, cualquier INSERT/UPDATE/DELETE/DROP/ALTER, modificar archivos del proyecto, commit/push.

## 3. Estado inicial

`git status --short` (sin modificar nada). Modificados: `AI_WORK_LOG.md`, `DATA_MODEL_ANALYSIS.md`, `src/app/Imports/BlueprintExcelReader.php`, `src/app/Imports/BlueprintRowData.php`, `src/app/Models/Blueprint.php`, `src/app/Models/Element.php`, `src/app/Services/BlueprintImportService.php`, `src/database/seeders/DatabaseSeeder.php`, `src/tests/Feature/BlueprintImportServiceTest.php`.
Nuevos (untracked) relevantes: las 5 migraciones `2026_10_16_00000{1..5}_*`, los modelos `Enchantment`/`Spirit`/`SpiritAffinity`/`BlueprintElementalAffinity`, los seeders `BlueprintCatalogSeeder`/`ElementSeeder`/`EnchantmentSeeder`/`SpiritAffinitySeeder`/`SpiritSeeder`, el test `BlueprintAffinityForeignKeysRollbackTest`, los reportes del 2026-10-16 y artefactos previos (`blueprints.txt`, `blueprint_components.txt`, `d1-crafting-materials.diff`, `shop-titans-esquema.zip`, el `.xlsx` en raíz).

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| — | Ninguno | El preflight no modifica archivos del proyecto. |

> Nota: este reporte (`docs/reports/2026-10-16_preflight_ejecucion_d3.md`) y el resumen en `AI_WORK_LOG.md` son la entrega documental de la fase.

## 5. Cambios realizados

Ninguno sobre el código ni el esquema. Solo lecturas.

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| `app` y `postgres` disponibles | `docker compose ps`: ambos `Up` | Confirmada |
| PostgreSQL 17.x conectado a `shoptitans` | `SELECT version()` → 17.10; `current_database()`=shoptitans | Confirmada |
| Las 5 migraciones D-3 están pendientes | `migrate:status` → `Pending` (5) | Confirmada |
| Las 4 columnas de afinidad están vacías (guarda de `000005` pasa) | `count(*)=1549`, no-null = 0/0/0/0 | Confirmada |
| Las FKs actuales apuntan a `elements` | `pg_constraint` → 4 FKs `REFERENCES elements(id) ON DELETE SET NULL` | Confirmada |
| Los catálogos nuevos no existen aún | `to_regclass` → NULL para los 4 | Confirmada |
| `elements` sin poblar | `count(elements)=0` | Confirmada |
| El Excel fuente está en el contenedor | `storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx` (1.367.141 bytes) | Confirmada |

## 7. Discrepancias

- **`elements` = 0 filas.** No es un bloqueo, pero implica que el catálogo de elementos debe poblarse **antes** de la reimportación; de lo contrario todos los valores de AZ se reportarían como no resueltos.
- **Ejecutar `db:seed` completo tiene un efecto colateral:** `DatabaseSeeder::run()` termina con `User::factory()->create([...])`, que crearía un usuario de prueba adicional. Recomendación: sembrar por clase (`db:seed --class=...`) en lugar del seeder global, o revisar/ajustar esa línea antes de autorizar.
- **Artefactos untracked en raíz** (`.xlsx`, `*.txt`, `*.diff`, `.zip`): no afectan la ejecución, pero conviene depurarlos.

## 8. Decisiones

### Propuestas
- Sembrar los catálogos por clase (no `db:seed` global) para evitar el usuario de prueba.
- Orden de ejecución: `migrate` → seeders de catálogos → `import:blueprints`.

### Aprobadas
- Ninguna nueva (el preflight no aprueba ejecución por sí mismo).

## 9. Validaciones

```bash
git status --short
docker compose ps
docker compose exec -T postgres psql -U shoptitans -d shoptitans -c "SELECT version();"
docker compose exec -T app php artisan migrate:status
docker compose exec -T postgres psql -U shoptitans -d shoptitans -c "SELECT tablename FROM pg_tables WHERE schemaname='public' ORDER BY tablename;"
docker compose exec -T postgres psql -U shoptitans -d shoptitans -c "SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name='blueprints' AND column_name IN (...);"
docker compose exec -T postgres psql -U shoptitans -d shoptitans -c "SELECT conname, pg_get_constraintdef(oid) FROM pg_constraint WHERE conrelid='blueprints'::regclass AND contype='f';"
docker compose exec -T postgres psql -U shoptitans -d shoptitans -c "SELECT count(*), count(elemental_affinity_id), count(spirit_affinity_id), count(built_in_element_id), count(built_in_spirit_id) FROM blueprints;"
docker compose exec -T postgres psql -U shoptitans -d shoptitans -c "SELECT (SELECT count(*) FROM blueprints), (SELECT count(*) FROM elements), (SELECT count(*) FROM crafting_materials), (SELECT count(*) FROM blueprint_components);"
docker compose exec -T postgres psql -U shoptitans -d shoptitans -c "SELECT to_regclass('public.enchantments'), to_regclass('public.spirit_affinities'), to_regclass('public.spirits'), to_regclass('public.blueprint_elemental_affinities');"
docker compose exec -T app bash -c 'ls -1 storage/app/*.xlsx'
```

Resultado / exit code:
```text
docker compose ps:
  shoptitans-app      Up 6 hours   9000/tcp
  shoptitans-postgres Up 6 hours   0.0.0.0:5432->5432/tcp

PostgreSQL: 17.10 (Debian 17.10-1.pgdg13+1) | db=shoptitans | usr=shoptitans

migrate:status (D-3):
  2026_10_16_000001_create_enchantments_table ....................... Pending
  2026_10_16_000002_create_spirit_affinities_table .................. Pending
  2026_10_16_000003_create_spirits_table ............................ Pending
  2026_10_16_000004_create_blueprint_elemental_affinities_table ..... Pending
  2026_10_16_000005_change_blueprints_affinity_foreign_keys ......... Pending
  (históricas 0001_01_01..2026_10_14_000002: Ran)

Tablas public (24): las 14 del MVP + users/cache/cache_locks/failed_jobs/job_batches/jobs/
  password_reset_tokens/sessions/migrations/crafting_materials.
  NO existen: enchantments, spirit_affinities, spirits, blueprint_elemental_affinities.

Columnas de afinidad en blueprints:
  built_in_element_id   | bigint | YES
  built_in_spirit_id    | bigint | YES
  elemental_affinity_id | bigint | YES
  spirit_affinity_id    | bigint | YES

FKs de blueprints:
  blueprints_elemental_affinity_id_foreign | FK (elemental_affinity_id) REFERENCES elements(id) ON DELETE SET NULL
  blueprints_spirit_affinity_id_foreign    | FK (spirit_affinity_id)    REFERENCES elements(id) ON DELETE SET NULL
  blueprints_built_in_element_id_foreign   | FK (built_in_element_id)   REFERENCES elements(id) ON DELETE SET NULL
  blueprints_built_in_spirit_id_foreign    | FK (built_in_spirit_id)    REFERENCES elements(id) ON DELETE SET NULL
  blueprints_category_id_foreign           | FK (category_id) REFERENCES item_categories(id) ON DELETE SET NULL

Conteos no nulos en blueprints:
  total=1549 | az=0 | ba=0 | bb=0 | bc=0

Conteos de registros:
  blueprints=1549 | elements=0 | crafting_materials=186 | blueprint_components=2747

Existencia de tablas nuevas (to_regclass): enchantments=NULL, spirit_affinities=NULL,
  spirits=NULL, blueprint_elemental_affinities=NULL

Excel en contenedor app:
  storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx (1367141 bytes)
```

Todo exit code 0. Ningún comando de escritura.

## 10. Resultado final

**LISTO PARA EJECUCIÓN** — con condiciones:
1. La guarda de `2026_10_16_000005` pasa (las 4 columnas de afinidad están en `NULL`).
2. Las 4 tablas nuevas no existen; las migraciones las crearán en orden.
3. `elements` está vacío → **es obligatorio** sembrar los catálogos antes de reimportar.
4. Evitar `db:seed` global (crea un `User` de prueba); sembrar por clase.
5. El Excel fuente está disponible en el contenedor.

Estado global de D-3: **IMPLEMENTADO — PENDIENTE DE REVISIÓN** (no EJECUTADO ni VALIDADO en PostgreSQL).

## 11. Riesgos

- **Dependencia de orden:** si se reimporta antes de sembrar `elements`/`enchantments`/`spirit_affinities`/`spirits`, habrá valores no resueltos y el comando fallará (por diseño, devuelve FAILURE).
- **Efecto colateral de `db:seed`:** `User::factory()->create()` en `DatabaseSeeder`.
- **Reimportación sobre datos existentes:** `import:blueprints` debe ser idempotente y preservar los IDs de `blueprints` para no romper `blueprint_components` (2.747 filas). Cubierto por el test `test_import_is_idempotent`.
- **No validado en PostgreSQL:** la suite corre en SQLite `:memory:`; el comportamiento de `up()`/`down()` en PostgreSQL debe confirmarse al ejecutar.

## 12. Pendientes

- Autorización humana para ejecutar.
- Decidir tratamiento del `User::factory()` en `DatabaseSeeder` (o sembrar por clase).
- Definir el nombre exacto del Excel a pasar a `import:blueprints`.

## 13. Preguntas para ChatGPT

1. ¿Se autoriza sembrar por clase (evitando el `User` de prueba) en lugar de `db:seed`?
2. ¿Se reimporta `import:blueprints` sobre los 1.549 blueprints existentes (idempotente) o se prefiere otra estrategia?

## 14. Próximo paso recomendado

Solicitar autorización explícita para ejecutar, en este orden:
```bash
docker compose exec -T app php artisan migrate
docker compose exec -T app php artisan db:seed --class=ElementSeeder
docker compose exec -T app php artisan db:seed --class=EnchantmentSeeder
docker compose exec -T app php artisan db:seed --class=SpiritAffinitySeeder
docker compose exec -T app php artisan db:seed --class=SpiritSeeder
docker compose exec -T app php artisan import:blueprints storage/app/<archivo>.xlsx
```
y validar por conteos posteriores en PostgreSQL.
