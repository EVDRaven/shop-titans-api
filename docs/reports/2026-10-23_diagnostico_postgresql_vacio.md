# Reporte Técnico — Diagnóstico de PostgreSQL vacío (pérdida de datos en tablas de dominio)

**Fecha:** 2026-10-23
**Autor:** Editor IA
**Estado:** DIAGNÓSTICO COMPLETADO — SIN ESCRITURAS EN POSTGRESQL — PENDIENTE DE AUTORIZACIÓN PARA RECUPERACIÓN

## 1. Objetivo

Determinar la causa más probable de la discrepancia entre el historial documentado (`AI_WORK_LOG.md` y `docs/reports/` registran cargas exitosas: 1.549 blueprints, 2.747 componentes, catálogos y afinidades) y el estado actual de PostgreSQL, donde **todas las tablas de datos están en 0 registros**.

Objetivos específicos solicitados:
1. Configuración efectiva de Docker Compose y montaje del volumen PostgreSQL.
2. Reconstruir, con Git/reportes/bitácora, cuándo se cargaron los datos y qué operaciones posteriores pudieron afectar su persistencia.
3. Buscar evidencia de otro volumen, instancia, base de datos, reinicialización o eliminación de registros.
4. Disponibilidad del Excel original, importadores y seeders para una eventual recuperación.

## 2. Alcance

**Incluye:** diagnóstico de **solo lectura** sobre Docker, el sistema de archivos del contenedor PostgreSQL, catálogos/estadísticas de PostgreSQL, logs de PostgreSQL, logs de Laravel, Git y documentación.

**Excluye (prohibido en esta tarea, no ejecutado):** cualquier escritura en PostgreSQL (`migrate`, `seed`, importadores, `INSERT/UPDATE/DELETE/TRUNCATE`, `DROP`); creación/eliminación/recreación de volúmenes o contenedores; modificación de archivos de código/configuración; commits o pushes.

## 3. Estado inicial

- Contenedores activos: `shoptitans-app` y `shoptitans-postgres` (`postgres:17`, PostgreSQL **17.10**).
- Base de datos: `shoptitans`, usuario `shoptitans`.
- Volumen Docker: `shop-titans-api_postgres_data`.
- `migrations` = 24 filas, **todas en batch 1**.
- Tablas de datos en 0 (`blueprints`, `blueprint_components`, `crafting_materials`, `workers`, `worker_levels`, `resources`, `item_categories`, `elements`, `qualities`, `enchantments`, `spirit_affinities`, `spirits`, `blueprint_elemental_affinities`).
- Git: `HEAD = f0cb37c` (`master`, sincronizado con `origin/master`); cambios locales sin commit (API, docs, `src/phpunit.xml`, `src/bootstrap/app.php`).

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| `docs/reports/2026-10-23_diagnostico_postgresql_vacio.md` | Creado | Este reporte de diagnóstico. |
| `AI_WORK_LOG.md` | Modificado | Registrar el hito del diagnóstico, su estado y el próximo paso. |

**Ningún archivo de código, migración, modelo, seeder o configuración fue modificado. Ninguna tabla de PostgreSQL fue alterada.**

## 5. Cambios realizados

Ninguno sobre el sistema. Esta tarea es **exclusivamente de diagnóstico de lectura**. Se ejecutaron únicamente comandos de inspección (`docker compose config/ps/inspect`, `docker logs`, `ls`/`stat` de archivos, `psql SELECT`, `grep`/lectura de código y logs, Git de solo lectura).

## 6. Hipótesis verificadas

| # | Hipótesis | Evidencia | Resultado |
|---|---|---|---|
| H-1 | **Reconstrucción total del esquema** de base de datos ejecutada contra PostgreSQL el **2026-10-23 ~03:28 UTC**, con las inserciones posteriores **revertidas**. | 24 migraciones en batch 1 (histórico documentado tenía batches separados); mtime de los **28 archivos de tabla = 2026-10-23 03:28–03:33** (no 2026-08-28); `n_live_tup=0` con `n_tup_del=0` en tablas principales; secuencias avanzadas; log `drop table if exists "crafting_materials"` (forma exacta de `Schema::dropIfExists`, i.e. un `down()` de migración) fallando por FK a las 03:28:47. | **CONFIRMADA (el hecho)** — el ejecutor exacto queda como hipótesis (H-1a/H-1b) |
| H-1a | El ejecutor fue la **suite de tests PHPUnit ejecutada contra PostgreSQL** (por el defecto de aislamiento documentado `--env=testing` → pgsql), con `RefreshDatabase` (reconstruye el esquema) y transacciones por test revertidas. | `laravel.log` con ejecuciones de tests el 2026-10-23 (03:44–03:46); conteos de inserción pequeños y revertidos, coherentes con datos creados por tests (58 blueprints vs. 1.549 de la importación real); `src/phpunit.xml` modificado a las **03:47 UTC** (corrección del aislamiento), inmediatamente **después** del evento de 03:28. | **PROBABLE (alta)** |
| H-1b | El ejecutor fue un comando manual `migrate:fresh` / `migrate:refresh` contra la BD de desarrollo. | La sentencia logueada `drop table if exists "crafting_materials"` proviene de un `down()` de migración (propio de un *rollback*/`refresh`), no del *wipe* de `migrate:fresh` (que emite `drop table "a", "b", ... cascade`). | **PROBABLE (media)** |
| H-2 | Pérdida/recreación del volumen Docker. | Volumen `shop-titans-api_postgres_data` creado **2026-07-24T06:20:25Z**, nunca recreado; contenedor `shoptitans-postgres` creado la misma marca de tiempo (mismo `Created` que el volumen); montaje correcto `volume → /var/lib/postgresql/data`; `pg_database` con `shoptitans` de 9.8 MB (esquema intacto). | **RECHAZADA** |
| H-3 | Eliminación de datos por `DELETE`/`TRUNCATE`. | `n_tup_del=0` en `blueprints`, `crafting_materials`, `workers`, `resources`, `item_categories`, `elements`, `enchantments`, `spirit_affinities`, `spirits`; patrón compatible con reversión de transacciones + reconstrucción, no con borrado. | **RECHAZADA** |
| H-4 | Existe otra instancia/base de datos/volumen con los datos. | Únicas bases: `postgres`, `shoptitans`, `template0`, `template1`. Único volumen del proyecto: `shop-titans-api_postgres_data`. El volumen alterno `twitch_rpg_postgres_data` pertenece a **otro** proyecto. | **RECHAZADA** |
| H-5 | Restauración desde un dump/backup existente. | No se encontró ningún `.sql`/`.dump`/`.backup`; `src/database/database.sqlite` es un **residuo obsoleto** (3 migraciones, sin tabla `blueprints`). No hay archivado WAL ni PITR configurados. | **RECHAZADA** |

## 7. Discrepancias

**D-1 (central).** El estado documentado (`AI_WORK_LOG.md`: importación de 1.549 blueprints / 2.747 componentes / catálogos / afinidades, validada) **no coincide** con el estado real (todas las tablas de datos en 0). **Evidencia:** `pg_stat_user_tables` (todas las tablas en `n_live_tup=0`; `migrations=24`), `migrations` (24/24 en batch 1), mtimes de los archivos de tabla (2026-10-23 03:28–03:33), log de PostgreSQL (rollback/`drop table` el 03:28). **Impacto:** pérdida total de los datos importados. **Propuesta:** ver §10 y §14; **requiere autorización** independiente antes de cualquier recuperación.

**D-2.** `migrations` tiene las 24 migraciones en **un solo batch (1)**, mientras la bitácora documenta ejecuciones escalonadas (`2026-08-28` → 14 migraciones MVP; `2026-10-14/15` → D-1; `2026-10-16` → D-3 en **batch [4]**). Un histórico con múltiples batches es incompatible con un único batch 1 → **el esquema fue reconstruido desde cero**. **Impacto:** refuerza H-1 (el esquema actual no es el histórico). **Propuesta:** ninguna (evidencia de diagnóstico).

**D-3 (documental).** El riesgo R-1 / DP-2 ya registrado (`--env=testing` apuntaba a PostgreSQL en lugar de SQLite) es **consistente temporalmente** con el evento de vaciado y debe considerarse la ruta más probable del incidente. Ya existe una corrección aplicada en `src/phpunit.xml` (ver §9), pero **no se verificó en esta tarea que dicha corrección sea suficiente** en todas las invocaciones.

## 8. Decisiones

### Propuestas
- **P-1:** Antes de recargar datos, **blindar el aislamiento de pruebas y de comandos** para que ninguna ejecución automática pueda reconstruir/vaciar PostgreSQL (revisión del mecanismo `--env=testing`/`env`, guardas de entorno, y una verificación explícita con sonda de conexión).
- **P-2:** Recargar los datos exclusivamente desde el Excel original mediante los seeders por clase + `import:blueprints` + `import:blueprint-components` + seeders de D-3 (operaciones idempotentes), con conteos de validación posteriores.
- **P-3:** Registrar un ADR con la política de "los tests nunca tocan PostgreSQL" y un respaldo preventivo (`pg_dump`) como paso previo a cualquier operación destructiva futura.

### Aprobadas
- Ninguna. Este reporte es un **diagnóstico**; no se aprueba ni ejecuta ninguna recuperación.

### Reemplazadas/Rechazadas
- H-2, H-3, H-4, H-5 (rechazadas con evidencia, ver §6).

## 9. Validaciones

Todas las consultas fueron **de solo lectura**. No se ejecutó ninguna operación de escritura.

### 9.1 Docker Compose y volumen
```bash
docker compose config
docker volume ls
docker volume inspect shop-titans-api_postgres_data
docker inspect shoptitans-postgres --format '{{json .Mounts}}'
docker compose ps
```
Resultado (extracto):
```text
name: shop-titans-api
  postgres:  image: postgres:17  container_name: shoptitans-postgres
    volumes: - type: volume  source: postgres_data  target: /var/lib/postgresql/data
volumes:
  postgres_data:  name: shop-titans-api_postgres_data

shop-titans-api_postgres_data  CreatedAt: 2026-07-24T06:20:25Z  Mountpoint: .../_data
Mount: [{"Type":"volume","Name":"shop-titans-api_postgres_data","Destination":"/var/lib/postgresql/data","RW":true}]
shoptitans-postgres  ContainerCreated=2026-07-24T06:20:25Z  Image=postgres:17   (Up 16 hours)
```
Conclusión: volumen y contenedor **nunca recreados**; el volumen se monta correctamente.

### 9.2 Estadísticas de tablas (solo lectura)
```bash
docker exec shoptitans-postgres psql -U shoptitans -d shoptitans -c \
 "SELECT relname, n_live_tup, n_tup_ins, n_tup_upd, n_tup_del, last_autovacuum FROM pg_stat_user_tables ORDER BY relname;"
```
Resultado (extracto):
```text
blueprints                     | n_live=0 | ins=58  | upd=8 | del=0 | last_autovacuum=2026-10-23 03:29:10
crafting_materials             | n_live=0 | ins=896 | upd=0 | del=0 | last_autovacuum=2026-10-23 03:29:10
workers                        | n_live=0 | ins=244 | upd=0 | del=0 | last_autovacuum=2026-10-23 03:29:10
resources                      | n_live=0 | ins=160 | upd=0 | del=0
item_categories                | n_live=0 | ins=497 | upd=0 | del=0
qualities                      | n_live=0 | ins=42  | upd=0 | del=0
elements                       | n_live=0 | ins=7   | upd=0 | del=0
enchantments                   | n_live=0 | ins=3   | upd=0 | del=0
spirit_affinities              | n_live=0 | ins=3   | upd=0 | del=0
spirits                        | n_live=0 | ins=2   | upd=0 | del=0
blueprint_workers              | n_live=0 | ins=21  | del=3
blueprint_resources            | n_live=0 | ins=30  | del=6
blueprint_components           | n_live=0 | ins=20  | upd=1 | del=2
blueprint_elemental_affinities | n_live=0 | ins=9   | del=2
migrations                     | n_live=24
sessions                       | n_live=1
worker_levels                  | n_live=0 | ins=0
```
Conclusión: **ninguna tabla de datos conserva filas**; las tablas principales tienen `n_tup_del=0` (no hubo `DELETE`), y los conteos de inserción son bajos (58 blueprints vs. 1.549 importados) → actividad posterior a la reconstrucción del esquema, revertida.

### 9.3 Secuencias y migraciones
```bash
docker exec shoptitans-postgres psql -U shoptitans -d shoptitans -c \
 "SELECT sequencename, last_value FROM pg_sequences WHERE schemaname='public' ORDER BY 1;"
docker exec shoptitans-postgres psql -U shoptitans -d shoptitans -c \
 "SELECT batch, COUNT(*) FROM migrations GROUP BY batch;"
```
Resultado (extracto):
```text
blueprints_id_seq=58 · crafting_materials_id_seq=896 · workers_id_seq=244 · item_categories_id_seq=497
resources_id_seq=160 · qualities_id_seq=42 · elements_id_seq=7 · migrations_id_seq=24
batch 1 | 24
```
Conclusión: secuencias **avanzadas** con tablas vacías (las secuencias no son transaccionales) → hubo inserciones reales que después no quedaron en la tabla; y **todas** las migraciones en un único batch 1.

### 9.4 Reconstrucción del esquema: `relfilenode` y archivos físicos
```bash
docker exec shoptitans-postgres psql -U shoptitans -d shoptitans -c \
 "SELECT relname, relfilenode FROM pg_class WHERE relnamespace='public'::regnamespace AND relkind='r' ORDER BY relfilenode;"
docker exec shoptitans-postgres bash -c 'cd /var/lib/postgresql/data/base/16384 && ls -la --time-style=full-iso <28 relfilenodes>'
```
Resultado (extracto):
```text
migrations=40966 · workers=41046 · elements=41088 · blueprints=41108 · blueprint_components=41186
crafting_materials=41266 · blueprint_elemental_affinities=41321   (28 tablas, nodos contiguos 40966–41321)

40973 users             size=0    2026-10-23 03:28:10
41046 workers           size=0    2026-10-23 03:29:10
41088 elements          size=8192 2026-10-23 03:28:34
41097 qualities         size=8192 2026-10-23 03:33:33
41108 blueprints        size=0    2026-10-23 03:29:10
41186 blueprint_components size=8192 2026-10-23 03:33:34
41266 crafting_materials size=0   2026-10-23 03:29:10
41321 blueprint_elemental_affinities size=8192 2026-10-23 03:28:43
```
Conclusión **decisiva**: los **28 archivos** de tabla tienen `mtime` entre **2026-10-23 03:28:10 y 03:33:36 UTC**, no el 2026-08-28 (creación original). El almacenamiento físico actual fue creado/escrito ese día.

### 9.5 Log de PostgreSQL
```bash
docker logs --timestamps shoptitans-postgres 2>&1 | grep -E "2026-10-2[123]"
```
Resultado (extracto):
```text
2026-10-23T03:18:13.824  LOG:  database system was interrupted; last known up at 2026-10-16 22:57:47 UTC
2026-10-23T03:18:13.900  LOG:  database system was not properly shut down; automatic recovery in progress
2026-10-23T03:18:13.960  LOG:  database system is ready to accept connections
2026-10-23T03:28:12.398  ERROR: duplicate key value violates unique constraint "blueprint_workers_blueprint_id_worker_id_unique"
2026-10-23T03:28:12.398  STATEMENT: insert into "blueprint_workers" (...) values (...) returning "id"
2026-10-23T03:28:47.794  ERROR: cannot drop table crafting_materials because other objects depend on it
2026-10-23T03:28:47.794  DETAIL: constraint blueprint_components_component_id_foreign on table blueprint_components depends on table crafting_materials
2026-10-23T03:28:47.794  STATEMENT: drop table if exists "crafting_materials"
```
Conclusión: el 2026-10-23 03:18 el servidor arrancó con **recuperación tras caída** (último checkpoint limpio: 2026-10-16 22:57:47). A las **03:28:12** hubo **inserciones** (error de clave duplicada en `blueprint_workers`, propio de datos de test/carga) y a las **03:28:47** una **operación destructiva de esquema** (`drop table if exists "crafting_materials"`).

**Verificación del origen de la sentencia:** en `vendor/laravel/.../Schema/Grammars/PostgresGrammar.php`:
- `compileDropIfExists()` → `drop table if exists "crafting_materials"` (forma **exacta** de la sentencia logueada) → proviene de un **`down()` de migración** (`2026_10_14_000001_create_crafting_materials_table`, línea 70).
- `compileDropAllTables()` → `drop table "a", "b", ... cascade` (forma que produce `migrate:fresh`/`db:wipe`).

→ La sentencia logueada **no** es la del *wipe* de `migrate:fresh`, sino la de un **rollback de migración** (familia `migrate:rollback`/`reset`/`refresh`).

### 9.6 Log de Laravel
```bash
ls -la --time-style=full-iso src/storage/logs/laravel.log
grep -n "sessions\|sqlite" src/storage/logs/laravel.log | tail
```
Resultado (extracto):
```text
laravel.log  mtime 2026-10-22 23:07:56 -0600  (= 2026-10-23 05:07:56 UTC)
[2026-10-23 03:44:46] local.ERROR: ... no such table: sessions (Connection: sqlite, Database: :memory:, ...)
[2026-10-23 03:45:27] local.ERROR: ... no such table: sessions (Connection: sqlite, Database: :memory:, ...)
[2026-10-23 03:46:39] local.ERROR: ... no such table: sessions (Connection: sqlite, Database: :memory:, ...)
```
Conclusión: hubo **ejecuciones de tests** el 2026-10-23 (una ya en SQLite `:memory:`), y la corrección de `src/phpunit.xml` (mtime **03:47:47 UTC**) es inmediatamente posterior a estos eventos.

### 9.7 Configuración de pruebas y variables de entorno (sin secretos)
```bash
grep -nE "^(DB_CONNECTION|DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|SESSION_DRIVER|APP_ENV|APP_DEBUG|CACHE_STORE|QUEUE_CONNECTION)=" src/.env
```
Resultado (extracto, `DB_PASSWORD` **omitido** intencionalmente):
```text
APP_ENV=local   APP_DEBUG=true
DB_CONNECTION=pgsql   DB_HOST=postgres   DB_PORT=5432   DB_DATABASE=shoptitans   DB_USERNAME=shoptitans
SESSION_DRIVER=database   CACHE_STORE=database   QUEUE_CONNECTION=database
```
`src/phpunit.xml` ya contiene `force="true"` + directivas `<server>` para `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `DB_URL=''`, `SESSION_DRIVER=array`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`, `BROADCAST_CONNECTION=null` (corrección del aislamiento).

### 9.8 Bases y volúmenes disponibles
```bash
docker exec shoptitans-postgres psql -U shoptitans -d shoptitans -c \
 "SELECT datname, pg_size_pretty(pg_database_size(datname)) FROM pg_database ORDER BY 1;"
docker volume ls
```
Resultado:
```text
postgres 7353 kB · shoptitans 9862 kB · template0 7353 kB · template1 7425 kB
shop-titans-api_postgres_data · twitch_rpg_postgres_data   (este último: otro proyecto)
```

### 9.9 Git (solo lectura)
```bash
git log -n 5 --date=iso --pretty='%h %ad %s'
git reflog -n 5 --date=iso
```
Resultado (extracto):
```text
f0cb37c 2026-10-16 17:04:03 -0600  feat: modelar elementos y afinidades de blueprints   (HEAD -> master, origin/master)
```
Sin commits posteriores al 2026-10-16; los cambios de API/docs siguen **sin commit**.

### 9.10 Disponibilidad para recuperación
```bash
ls -la "src/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx"
ls -la src/database/
find . -maxdepth 4 \( -name "*.sql" -o -name "*.dump" -o -name "*.backup" \) -not -path "*/vendor/*"
```
Resultado:
```text
Excel presente: 1.367.141 bytes (2026-10-13)
src/database/database.sqlite (98304 bytes, Jul 25): 10 tablas, migrations=3, SIN tabla/blueprints  → residuo obsoleto
No se encontraron archivos .sql / .dump / .backup
```
Importadores y seeders presentes: `ImportBlueprints`, `ImportBlueprintComponents`, `BlueprintImportService`, `DatabaseSeeder`, `ElementSeeder`, `EnchantmentSeeder`, `SpiritAffinitySeeder`, `SpiritSeeder`, `CraftingMaterialSeeder`, `BlueprintCatalogSeeder` (idempotentes por diseño).

## 10. Resultado final

**Causa confirmada (con evidencia):** los datos **no** se perdieron por pérdida/recreación del volumen Docker, ni por `DELETE`/`TRUNCATE`, ni quedaron en otra instancia o base de datos. El **esquema completo de PostgreSQL fue reconstruido el 2026-10-23 entre 03:28:10 y 03:33:36 UTC** (las 24 migraciones quedaron en un único **batch 1**; los 28 archivos físicos de tabla tienen `mtime` de ese día) y las inserciones posteriores quedaron **revertidas** (`n_live_tup=0` con `n_tup_del=0`, secuencias avanzadas). El log de PostgreSQL registra a las 03:28:47 un `drop table if exists "crafting_materials"` (forma de `down()` de migración) que **falló por dependencia FK**, evidencia de una operación destructiva de la familia `migrate:*`.

**Causa más probable del disparador (hipótesis, no demostrada):** una ejecución automática contra PostgreSQL el 2026-10-23 ~03:28 UTC — muy probablemente la **suite de tests** (o un `migrate:fresh`/`refresh`) que, por el defecto de aislamiento ya documentado (`--env=testing` seleccionaba `pgsql`), reconstruyó/vació la BD de desarrollo. Esto es consistente con: los conteos pequeños y revertidos (datos de test, no la importación de 1.549), las ejecuciones de tests registradas en `laravel.log`, y la corrección del aislamiento en `src/phpunit.xml` **17 minutos después** (03:47 UTC).

**No se modificó PostgreSQL**; todas las operaciones fueron de solo lectura.

## 11. Riesgos

- **R-1 (reincidencia, crítico):** mientras exista cualquier ruta por la que una ejecución de tests/comandos pueda conectar a PostgreSQL, una nueva reconstrucción del esquema puede volver a vaciar los datos. **Debe cerrarse antes de recargar.**
- **R-2 (recuperabilidad):** no hay `dump`/backup ni PITR; los datos importados **no son recuperables desde PostgreSQL**. La única fuente válida es el Excel original.
- **R-3 (fidelidad de la recarga):** la recuperación depende de la idempotencia y cobertura de los seeders/importadores; debe revalidarse la integridad (0 huérfanos, orden AZ, conteos) como en D-1/D-3.
- **R-4 (documental):** `AI_WORK_LOG.md` afirmaba cargas exitosas sin advertencia del incidente; conviene que el estado real quede reflejado (este reporte + hito en la bitácora).

## 12. Pendientes

1. **Autorización humana** para ejecutar la recuperación (no iniciada).
2. **Cerrar/validar el aislamiento de pruebas** de forma definitiva (verificar en todas las invocaciones que la conexión es SQLite `:memory:` antes de cualquier recarga).
3. Recargar catálogos + blueprints + componentes + afinidades desde el Excel y revalidar integridad (con autorización).
4. Decidir si se adopta un `pg_dump` preventivo y/o restricciones de entorno (p. ej. abortar comandos destructivos si `DB_DATABASE=shoptitans` y `APP_ENV != testing`).
5. Registrar el incidente y su lección en la bitácora/ADR.

## 13. Preguntas para ChatGPT

1. ¿Se confirma que la estrategia correcta es **recargar desde el Excel** (seeders por clase + `import:blueprints` + `import:blueprint-components`), dado que no hay backup?
2. ¿Se aprueba añadir una **guarda de seguridad** que impida ejecutar operaciones destructivas (`migrate:fresh`/`refresh`/`db:wipe`) cuando la conexión apunte a `DB_DATABASE=shoptitans` en `APP_ENV=local`?
3. ¿Se adopta un `pg_dump` previo obligatorio a operaciones de esquema, y una verificación del aislamiento de tests como *gate* de ejecución?

## 14. Próximo paso recomendado

1. **Detenerse aquí** (ya realizado): no se ejecutó ninguna escritura en PostgreSQL.
2. **Solicitar autorización independiente** para dos cosas separadas:
   - **(a) Verificación de aislamiento (solo lectura/solo tests):** ejecutar la suite en modo estándar y con `env=testing` **con una sonda** que demuestre que la conexión es SQLite `:memory:` y que **no** hay conexión a `postgres`/`shoptitans`.
   - **(b) Recarga de datos (escritura, requiere autorización explícita):** tras (a), re-ejecutar seeders por clase + `import:blueprints` + `import:blueprint-components` + seeders de D-3 desde el Excel, y validar conteos e integridad.
3. No ejecutar (b) hasta que (a) se demuestre y el usuario lo autorice por separado.

> **Restricción vigente:** este reporte no autoriza ni ejecuta ninguna operación que escriba en PostgreSQL. Se solicita autorización independiente antes de cualquier recuperación.
