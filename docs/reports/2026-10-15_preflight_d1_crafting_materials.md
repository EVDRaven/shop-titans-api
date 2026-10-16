# Reporte Técnico — Preflight de solo lectura para D-1 `crafting_materials`

**Fecha:** 2026-10-15
**Autor:** Editor IA
**Estado:** PREPARACIÓN PARA EJECUCIÓN (solo lectura) — LISTO PARA SOLICITAR AUTORIZACIÓN

## 1. Objetivo

Comprobar el estado real del entorno y detectar posibles bloqueos antes de ejecutar las migraciones D-1, el seeder `CraftingMaterialSeeder` y el comando `import:blueprint-components`.

Todas las comprobaciones fueron de solo lectura. No se ejecutaron migraciones, seeders, importaciones ni ninguna escritura (INSERT/UPDATE/DELETE/TRUNCATE/ALTER/DROP). No se modificó código, configuración, Excel, esquema ni datos. No se hicieron cambios en Git.

## 2. Alcance

**Incluye:** verificación de Git, Docker, conexión PostgreSQL, estado de migraciones, conteos, archivo Excel y existencia del comando/seeder.
**Excluye:** cualquier operación de escritura o de ejecución real.

## 3. Comprobaciones realizadas

### 3.1 Git

```bash
git status --short
```

Resultado (sin alterar nada):

- **Modificados:** `AI_WORK_LOG.md`, `docker/php/Dockerfile`, `src/app/Models/Blueprint.php`, `src/app/Models/BlueprintComponent.php`, `src/composer.json`, `src/composer.lock`.
- **Sin trackear (nuevos):** migraciones D-1 (`2026_10_14_000001`, `2026_10_14_000002`), `src/database/seeders/CraftingMaterialSeeder.php`, `src/app/Models/CraftingMaterial.php`, `src/app/Imports/*`, `src/app/Services/*`, `src/app/Console/*`, tests D-1 (`BlueprintComponentImportTest`, `BlueprintComponentsMigrationRollbackTest`, `BlueprintImportServiceTest`, `CraftingMaterialSeederTest`, `ImportBlueprintComponentsCommandTest`, `BlueprintExcelReaderTest`), `src/tests/fixtures/`, reportes de `docs/reports/`, `docs/adr/ADR-001_crafting_materials.md`, el Excel y varios `*.txt/*.diff/*.zip` en la raíz.

No se modificó ni revirtió ningún archivo.

### 3.2 Docker

```bash
docker compose ps
```

Resultado:

| Servicio | Contenedor | Imagen | Estado | Puertos |
|---|---|---|---|---|
| app | shoptitans-app | shop-titans-api-app | Up 2 days | 9000/tcp |
| postgres | shoptitans-postgres | postgres:17 | Up 2 days | 0.0.0.0:5432->5432/tcp |

No se reinició ni recreó ningún contenedor.

### 3.3 Conexión a PostgreSQL

```bash
docker compose exec -T postgres psql -U shoptitans -d shoptitans \
  -c "SELECT current_database() AS db, current_user AS usuario, inet_server_port() AS puerto, version() AS version;"
```

Resultado:

| db | usuario | version |
|---|---|---|
| shoptitans | shoptitans | PostgreSQL 17.10 (Debian 17.10-1.pgdg13+1) on x86_64-pc-linux-gnu |

La base de datos responde. No se muestran contraseñas ni secretos.

### 3.4 Estado de las migraciones

```bash
php artisan migrate:status
```

Resultado:

- **Ejecutadas (`Ran`, batch 1 y 2):** las 3 migraciones base (`users`, `cache`, `jobs`) y las 14 del MVP Blueprints (`2026_08_28_100001` … `2026_08_28_100014`).
- **Pendientes (`Pending`):**
  - `2026_10_14_000001_create_crafting_materials_table` — **Pending**
  - `2026_10_14_000002_change_blueprint_components_component_id_foreign` — **Pending**

No se ejecutó `migrate`, `migrate:fresh` ni `rollback`.

### 3.5 Conteos actuales

```bash
docker compose exec -T postgres psql -U shoptitans -d shoptitans \
  -c "SELECT to_regclass('public.crafting_materials') AS crafting_materials_existe;" \
  -c "SELECT count(*) AS blueprints FROM blueprints;" \
  -c "SELECT count(*) AS blueprint_components FROM blueprint_components;"
```

Resultado (valores **observados**, no esperados):

| Tabla | Observado | Nota |
|---|---|---|
| `blueprints` | 1549 | Coincide con la importación de Etapa 2. |
| `blueprint_components` | 0 | Vacía (aún no importada). |
| `crafting_materials` | **No existe** (`to_regclass` = null) | Esperado; la creará la migración `000001`. |

Verificación adicional de esquema (solo lectura, `pg_constraint`) para detectar bloqueos:

- FK actual `blueprint_components.component_id` → `blueprints(id) ON DELETE RESTRICT` (estado previo esperado; lo cambiará la migración `000002`).
- FK `blueprint_components.blueprint_id` → `blueprints(id) ON DELETE CASCADE`.
- FK `blueprint_components.quality_id` → `qualities(id) ON DELETE SET NULL`.
- `blueprint_components` con 0 filas → la migración `000002` puede aplicarse sin datos que violen la nueva FK.

### 3.6 Archivo Excel

Ruta host:
```bash
ls -la "/home/draven/Documentos/shop-titans-api/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx"
stat -c '%n | %s bytes' ".../Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx"
```

Ruta que usaría el comando (contenedor, `storage/app/`):
```bash
docker compose exec -T app ls -la "/var/www/html/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx"
docker compose exec -T app php -r 'echo is_readable("...") ? "yes" : "no";'
```

Resultado:

| Ubicación | Ruta | Tamaño | Legible |
|---|---|---|---|
| Host (raíz repo) | `/home/draven/Documentos/shop-titans-api/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx` | 1,367,141 bytes | Sí |
| Contenedor app | `/var/www/html/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx` | 1,367,141 bytes | Sí (`is_readable=yes`) |

Ambas rutas coinciden en tamaño (1,367,141 bytes). No se modificó el archivo.

### 3.7 Comando y seeder

```bash
php artisan list | grep -i "import:"
php artisan tinker --execute="var_dump(class_exists('Database\Seeders\CraftingMaterialSeeder'), class_exists('App\Console\Commands\ImportBlueprintComponents'), class_exists('App\Services\BlueprintImportService'));"
```

Resultado:

- `import:blueprint-components` — presente en Artisan.
- `import:blueprints` — presente en Artisan.
- `class_exists`:
  - `Database\Seeders\CraftingMaterialSeeder` → `true`
  - `App\Console\Commands\ImportBlueprintComponents` → `true`
  - `App\Services\BlueprintImportService` → `true`

No se ejecutó el seeder ni el comando de importación.

## 4. Bloqueos y discrepancias detectados

- **Bloqueos:** ninguno.
- **Discrepancias:** ninguna nueva.
- **Observaciones (no bloquean):**
  - `blueprint_components.component_id` todavía apunta a `blueprints.id`; hasta aplicar `000002`, ejecutar `import:blueprint-components` fallaría al resolver componentes. El orden obligatorio es `migrate` → `seed` → `import`.
  - El comando `import:blueprint-components` está **pendiente de revisión humana** antes de autorizar su ejecución.
  - `crafting_materials` no existe todavía (esperado).

## 5. Conclusión

**LISTO PARA SOLICITAR AUTORIZACIÓN.**

El entorno está operativo, PostgreSQL responde, las dos migraciones D-1 están pendientes en el orden correcto, `blueprint_components` está vacía (aplicación segura de la nueva FK), el Excel es legible en la ruta del comando y el seeder/comando/servicio cargan correctamente. No se detectaron bloqueos.

D-1 **no se marca como ejecutada ni validada**: solo se completó el preflight.

## 6. Comandos ejecutados (solo lectura)

| Comando | Finalidad |
|---|---|
| `git status --short` | Estado del repositorio. |
| `docker compose ps` | Estado de contenedores. |
| `psql -c "SELECT current_database(), current_user, version()"` | Verificar conexión y identificar DB/usuario. |
| `php artisan migrate:status` | Estado de migraciones. |
| `psql -c "SELECT count(*) ..."` / `to_regclass` | Conteos observados y existencia de tablas. |
| `psql -c "SELECT ... pg_constraint ..."` | FK actual de `blueprint_components`. |
| `ls -la` / `stat -c '%s bytes'` / `php -r is_readable` | Existencia, tamaño y legibilidad del Excel. |
| `php artisan list \| grep import:` | Confirmar comandos registrados. |
| `php artisan tinker --execute="class_exists(...)"` | Confirmar cargabilidad de seeder/comando/servicio. |

## 7. Riesgos

1. Ejecución real aún no autorizada; los conteos tras la importación (186 materiales / 2747 componentes) son **esperados**, no observados.
2. El seeder lee el Excel completo (~1.37 MB); se recomienda `php -d memory_limit=512M` al ejecutarlo.

## 8. Pendientes

1. Revisión humana del comando `import:blueprint-components`.
2. Autorización para ejecutar en PostgreSQL: `migrate` → `db:seed --class=CraftingMaterialSeeder` → `import:blueprint-components`.
3. Validación de conteos reales tras la ejecución.

## 9. Próximo paso recomendado

Solicitar autorización explícita para ejecutar D-1 en PostgreSQL en el orden indicado y validar los conteos resultantes.

## 10. Confirmación

No se ejecutaron migraciones, seeders, importaciones ni ninguna escritura sobre PostgreSQL. Todos los comandos fueron de solo lectura.
