# Reporte Técnico — Preparación de la prueba de restauración de PostgreSQL

**Fecha:** 2026-10-26
**Autor:** Editor IA
**Estado:** PROPUESTA (inspección y planificación completadas; **restauración NO ejecutada**, pendiente de aprobación)

---

## 1. Objetivo

Preparar una **prueba real de restauración** de los respaldos del proyecto Shop Titans API en un **entorno aislado**, sin tocar la base de desarrollo. Esta fase es **solo de inspección y planificación**: se identifican los respaldos, su formato, las versiones compatibles, un destino aislado y el procedimiento exacto para su posterior aprobación.

## 2. Alcance

**Incluye:** verificación de existencia, tamaño y formato de ambos respaldos; inspección de solo lectura con `pg_restore --list`; identificación de las versiones de PostgreSQL; revisión de `docker-compose.yml`, `.env` y nombres de servicios/red; propuesta de un destino temporal aislado; y preparación de los comandos exactos de creación, restauración y validación.

**Excluye:** ejecutar la restauración real; crear, borrar o modificar bases de datos o contenedores; migraciones, seeders o importadores; modificar respaldos; cambiar la base de desarrollo; modificar archivos del proyecto; commits.

## 3. Estado inicial y hallazgos

### 3.1 Respaldos (existencia, tamaño, formato)

```bash
ls -l backups/ && file backups/*.dump && md5sum backups/*.dump
```

| Respaldo | Tamaño | Formato | md5 |
|---|---|---|---|
| `backups/shoptitans_pre_blueprintmvpseeder_20261024_153512.dump` | 432.554 B | PostgreSQL custom (`v1.16-0`) | `53170eada1bce8232cbdbddf51192d5e` |
| `backups/shoptitans_pre_recuperacion_20261023_203355.dump` | 83.238 B | PostgreSQL custom (`v1.16-0`) | `61f012053aba28dcda5c6162d9bda165` |

Ambos existen, están legibles y en formato **`CUSTOM`** (apto para `pg_restore`). Los md5 coinciden con los registrados en los informes previos (`532.554 B`, md5 `53170e…d5e`).

### 3.2 Inspección de solo lectura (`pg_restore --list`)

```bash
pg_restore --list backups/shoptitans_pre_blueprintmvpseeder_20261024_153512.dump
pg_restore --list backups/shoptitans_pre_recuperacion_20261023_203355.dump
```

Cabeceras (idénticas en estructura):

| Campo | pre_blueprintmvpseeder | pre_recuperacion |
|---|---|---|
| Archive created at | 2026-10-24 15:35:24 (UTC) / 09:35 CST | 2026-10-24 02:33:55 (UTC) / 2026-10-23 20:33 CST |
| dbname | `shoptitans` | `shoptitans` |
| TOC Entries | 229 | 229 |
| Compression | gzip | gzip |
| Dump Version | 1.16-0 | 1.16-0 |
| Format | CUSTOM | CUSTOM |
| Dumped from/by | PostgreSQL **17.10** | PostgreSQL **17.10** |

**Estructura idéntica:** ambos contienen las **mismas 28 tablas** (incluida `migrations`), las mismas secuencias y objetos. La diferencia relevante es el **tamaño** (432 KB vs 83 KB), indicativo de que el primero contiene **el dataset completo** y el segundo un **estado degradado** (véase §4).

Tablas presentes (28): `blueprint_ascension_upgrades`, `blueprint_components`, `blueprint_crafting_upgrades`, `blueprint_elemental_affinities`, `blueprint_resources`, `blueprints`, `blueprint_starforged_milestones`, `blueprint_transcendence_upgrades`, `blueprint_workers`, `cache`, `cache_locks`, `crafting_materials`, `elements`, `enchantments`, `failed_jobs`, `item_categories`, `job_batches`, `jobs`, `migrations`, `password_reset_tokens`, `qualities`, `resources`, `sessions`, `spirit_affinities`, `spirits`, `users`, `worker_levels`, `workers`.

**No verificable sin restaurar:** el número de **filas** de cada tabla. El TOC de un dump `CUSTOM` lista entradas `TABLE DATA` con independencia del número de filas, por lo que el conteo de entradas no distingue un respaldo poblado de uno vacío.

### 3.3 Versiones de PostgreSQL

```bash
pg_restore --version                         # host
docker compose exec -T postgres postgres --version
docker compose exec -T postgres pg_restore --version
```

| Entorno | Versión |
|---|---|
| Host (`pg_restore`) | **18.6** (Ubuntu 18.6-0ubuntu0.26.04.1) |
| Contenedor `shoptitans-postgres` (servidor) | **17.10** (Debian 17.10-1.pgdg13+1) |
| Contenedor `shoptitans-postgres` (`pg_restore`) | **17.10** |

- Los respaldos fueron **generados con `pg_dump` 17.10** desde un servidor **17.10**.
- **Versión compatible recomendada para restaurar: PostgreSQL 17.x (idealmente 17.10)**, usando el **`pg_restore` del contenedor `postgres:17`** (no el 18.6 del host), porque la política de PostgreSQL recomienda que `pg_restore` **no sea de versión mayor que el servidor de destino**. El host 18.6 puede **listar** (y así se hizo, sin errores), pero no debe ser el cliente de restauración contra un servidor 17.

### 3.4 Compose, variables y nombres (destino aislado)

[docker-compose.yml](file:///home/draven/Documentos/shop-titans-api/docker-compose.yml#L1-L36):
- Servicios: **`postgres`** (contenedor `shoptitans-postgres`, imagen `postgres:17`, `restart: unless-stopped`) y **`app`** (contenedor `shoptitans-app`).
- `postgres` publica `5432:5432` (en todas las interfaces) y usa el volumen nombrado **`postgres_data`**.
- Red de Compose: **`shop-titans-api_default`** (bridge), sin red dedicada declarada.

[src/.env](file:///home/draven/Documentos/shop-titans-api/src/.env) (real) y [.env.example](file:///home/draven/Documentos/shop-titans-api/src/.env.example#L23-L28):
```
DB_CONNECTION=pgsql   DB_HOST=postgres   DB_PORT=5432
DB_DATABASE=shoptitans   DB_USERNAME=shoptitans   DB_PASSWORD=secret
```
La base de desarrollo actual es **`shoptitans`** con rol **`shoptitans`**.

### 3.5 Línea base del dataset (consulta de solo lectura)

`SELECT count(*)` sobre la base de desarrollo `shoptitans` (no modifica nada):

| Tabla | Filas | Tabla | Filas |
|---|---|---|---|
| `blueprints` | 1549 | `resources` | 12 |
| `blueprint_components` | 2747 | `elements` | 8 |
| `crafting_materials` | 186 | `enchantments` | 73 |
| `blueprint_workers` | 3744 | `spirit_affinities` | 38 |
| `blueprint_resources` | 4135 | `spirits` | 1 |
| `blueprint_elemental_affinities` | 911 | `qualities` | 5 |
| `item_categories` | 40 | `users` | 0 |
| `workers` | 19 | `migrations` | 24 |
| `worker_levels` | 50 | | |

Estos valores son los **esperados** si la restauración de `pre_blueprintmvpseeder` es correcta.

## 4. Procedencia de los respaldos y cuál probar primero

| Respaldo | Fecha (UTC) | Contenido esperado | Evidencia |
|---|---|---|---|
| `pre_blueprintmvpseeder_20261024_153512` | 2026-10-24 15:35 | **Dataset completo del MVP** (línea base §3.5) | `docs/reports/2026-10-26_ejecucion_controlada_blueprint_mvp_seeder.md` (§ contexto: “respaldo fresco del 2026-10-24 disponible y verificado”; 432.554 B) |
| `pre_recuperacion_20261023_203355` | 2026-10-23 20:33 (CST) | **Estado degradado previo a la recuperación** (esquema con datos escasos/ausentes) | `docs/reports/2026-10-23_recuperacion_datos_postgresql.md` (§ estado inicial: “respaldo lógico **previo** a la recuperación”, 83.238 B) |

**Recomendación: probar PRIMERO `shoptitans_pre_blueprintmvpseeder_20261024_153512.dump`.**
Motivos:
1. Es el **respaldo de referencia del dataset completo del MVP**; su restaurabilidad es la que importa y la que figura como **limitación vigente** del proyecto (“la restauración real nunca se ha probado”).
2. Ejercita la restauración de **datos reales** en las 28 tablas (no solo el esquema).
3. Restaurar `pre_recuperacion` valida principalmente el **esquema** (estado degradado) y no cubre el caso crítico; puede hacerse como **prueba secundaria** opcional después.

## 5. Alternativas de destino aislado

| Criterio | **Alt. 1 — Contenedor PostgreSQL 17 efímero (recomendada)** | Alt. 2 — BD temporal en el contenedor existente |
|---|---|---|
| Aislamiento | Total: instancia separada, sin puerto publicado, sin tocar el volumen de desarrollo | Parcial: misma instancia/volumen que desarrollo; requiere borrar la BD al final |
| Impacto en la base de desarrollo | **Ninguno** | Ninguno en datos, pero escribe en `postgres_data` |
| Imagen/versión | Reutiliza `postgres:17` **ya presente** (sin descargas) | Reutiliza `shoptitans-postgres` |
| Complejidad | Baja (un `docker run`) | Muy baja (`createdb` + `pg_restore`) |
| Limpieza | `docker stop` (el `--rm` elimina el contenedor; datos no persisten) | `dropdb` explícito |
| Riesgo de contaminar desarrollo | **Nulo** | Bajo (nombre distinto, pero misma instancia) |

Se recomienda la **Alternativa 1**. La Alternativa 2 se incluye como camino simple alternativo.

## 6. Procedimiento propuesto (comandos exactos, **sin ejecutar**)

> Todos los comandos se ejecutan **desde la raíz del proyecto** `/home/draven/Documentos/shop-titans-api`. No se publican puertos, no se toca `shoptitans`, ni el volumen `postgres_data`, ni el contenedor `shoptitans-postgres`.

### 6.1 Opción recomendada — Contenedor efímero aislado

```bash
# P0. (Solo lectura) Confirmar estado previo
docker compose ps
md5sum backups/shoptitans_pre_blueprintmvpseeder_20261024_153512.dump   # 53170eada1bce8232cbdbddf51192d5e

# P1. Crear el destino temporal AISLADO (sin publicar puertos; reutiliza la imagen postgres:17)
docker run --rm -d --name shoptitans_restore_test \
  -e POSTGRES_PASSWORD=restore_test \
  -e POSTGRES_USER=shoptitans \
  -e POSTGRES_DB=restore_test \
  postgres:17

# P2. Esperar readiness
until docker exec shoptitans_restore_test pg_isready -U shoptitans -d restore_test; do sleep 1; done

# P3. Restaurar el respaldo (CUSTOM) por stdin con el pg_restore 17.10 del propio contenedor
docker exec -i shoptitans_restore_test pg_restore -U shoptitans -d restore_test \
  --no-owner --no-privileges \
  < backups/shoptitans_pre_blueprintmvpseeder_20261024_153512.dump
# exit esperado: 0

# P4. Validar (véase §7)

# P5. Limpiar el entorno temporal
docker stop shoptitans_restore_test     # --rm elimina el contenedor; no persiste datos
```

**Notas:**
- `--no-owner --no-privileges` evita problemas si el rol propietario no coincidiera; aun así `POSTGRES_USER=shoptitans` crea el rol esperado.
- No se pasa archivo a `pg_restore`: lee el `CUSTOM` desde **stdin** (por eso se usa `docker exec -i`).
- No se publica ningún puerto ni se usa `--network`, por lo que el contenedor no es accesible desde el host ni desde la red de Compose.

### 6.2 Prueba secundaria (opcional) — mismo procedimiento sobre el otro respaldo

```bash
# (con el mismo contenedor temporal, o recreándolo)
docker exec -i shoptitans_restore_test pg_restore -U shoptitans -d restore_test \
  --no-owner --no-privileges --clean --if-exists \
  < backups/shoptitans_pre_recuperacion_20261023_203355.dump
```

### 6.3 Alternativa simple — BD temporal en el contenedor existente

```bash
docker compose exec -T postgres createdb -U shoptitans shoptitans_restore_test
docker compose exec -T postgres pg_restore -U shoptitans -d shoptitans_restore_test \
  --no-owner --no-privileges < backups/shoptitans_pre_blueprintmvpseeder_20261024_153512.dump
# ... validar ...
docker compose exec -T postgres dropdb -U shoptitans shoptitans_restore_test   # limpieza
```
(No modifica la base `shoptitans`; solo crea y elimina una BD aparte.)

## 7. Validación propuesta y valores esperados

### 7.1 Conteos (deben igualar §3.5)

```bash
docker exec -T shoptitans_restore_test psql -U shoptitans -d restore_test -tA -c "
select 'blueprints='||count(*) from blueprints
union all select 'blueprint_components='||count(*) from blueprint_components
union all select 'crafting_materials='||count(*) from crafting_materials
union all select 'blueprint_workers='||count(*) from blueprint_workers
union all select 'blueprint_resources='||count(*) from blueprint_resources
union all select 'blueprint_elemental_affinities='||count(*) from blueprint_elemental_affinities
union all select 'item_categories='||count(*) from item_categories
union all select 'workers='||count(*) from workers
union all select 'worker_levels='||count(*) from worker_levels
union all select 'resources='||count(*) from resources
union all select 'elements='||count(*) from elements
union all select 'enchantments='||count(*) from enchantments
union all select 'spirit_affinities='||count(*) from spirit_affinities
union all select 'spirits='||count(*) from spirits
union all select 'qualities='||count(*) from qualities
union all select 'users='||count(*) from users;"
```
Esperado: `1549 / 2747 / 186 / 3744 / 4135 / 911 / 40 / 19 / 50 / 12 / 8 / 73 / 38 / 1 / 5 / 0`.

### 7.2 Integridad referencial (huérfanos = 0)

```bash
docker exec -T shoptitans_restore_test psql -U shoptitans -d restore_test -tA -c "
select 'orphan_components='||count(*) from blueprint_components bc
  left join blueprints b on b.id = bc.blueprint_id where b.id is null;"
docker exec -T shoptitans_restore_test psql -U shoptitans -d restore_test -tA -c "
select 'orphan_components_material='||count(*) from blueprint_components bc
  left join crafting_materials cm on cm.id = bc.component_id where cm.id is null;"
```

### 7.3 Esquema/objetos (FK y CHECK presentes; migraciones)

```bash
docker exec -T shoptitans_restore_test psql -U shoptitans -d restore_test -tA -c "
select 'fks='||count(*) from pg_constraint where contype='f';"
docker exec -T shoptitans_restore_test psql -U shoptitans -d restore_test -tA -c "
select 'checks='||count(*) from pg_constraint where contype='c';"
docker exec -T shoptitans_restore_test psql -U shoptitans -d restore_test -tA -c "
select 'migrations='||count(*) from migrations;"    # 24
docker exec -T shoptitans_restore_test psql -U shoptitans -d restore_test -tA -c "
select 'tables='||count(*) from information_schema.tables where table_schema='public';"   # 28
```

### 7.4 No regresión del entorno de desarrollo

```bash
docker compose ps            # shoptitans-postgres y shoptitans-app intactos
docker compose exec -T postgres psql -U shoptitans -d shoptitans -tA -c "select count(*) from blueprints;"  # 1549
ls -l backups/               # respaldos y md5 sin cambios
```

### 7.5 Criterios de aceptación

1. `pg_restore` finaliza con **exit 0** (sin errores fatales; avisos menores de `--no-owner` son aceptables).
2. Los **conteos** de §7.1 **coinciden exactamente** con §3.5.
3. **0 huérfanos** en las comprobaciones de §7.2.
4. `migrations`=24, `tables`=28 y FKs/CHECK presentes (§7.3).
5. La **base de desarrollo** y los **respaldos** permanecen **inalterados** (§7.4).
6. El contenedor temporal queda **eliminado** al final (sin residuos).

## 8. Riesgos y limitaciones

- **Cliente/servidor:** usar `pg_restore` **17.10** (contenedor) evita el riesgo de un cliente 18.6 contra un servidor 17; el `pg_restore` 18.6 del host solo se usó para **listar**.
- **No verificable sin restaurar:** el número de filas contenido en cada dump (el TOC no lo revela). La hipótesis “pre_blueprintmvpseeder = dataset completo / pre_recuperacion = degradado” se sustenta en los informes y en los tamaños, y se **confirmará** con los conteos de §7.1 durante la prueba.
- **Aislamiento:** la Alt. 1 no publica puertos ni comparte volumen con desarrollo; la Alt. 2 escribe en `postgres_data` (requiere limpieza con `dropdb`).
- **Sin cambios funcionales:** esta fase no modifica archivos ni datos; la ejecución propuesta es reversible (contenedor efímero).

## 9. Discrepancias

Ninguna. Las versiones, rutas y tamaños coinciden con la documentación previa. Se observa que el host tiene `pg_restore` 18.6 mientras el servidor es 17.10: no es discrepancia del proyecto, pero **condiciona** la elección del cliente de restauración (§3.3).

## 10. Decisiones propuestas

- **DR-1:** probar **primero** `shoptitans_pre_blueprintmvpseeder_20261024_153512.dump` (dataset completo).
- **DR-2:** usar como destino aislado un **contenedor PostgreSQL 17 efímero** (Alt. 1), sin publicar puertos.
- **DR-3:** restaurar con el **`pg_restore` 17.10** del propio contenedor y flags `--no-owner --no-privileges`.
- **DR-4:** **no** tocar la base de desarrollo `shoptitans`, el volumen `postgres_data`, los respaldos ni `docker-compose.yml`.
- **DR-5:** (opcional) repetir la prueba con `pre_recuperacion` como verificación secundaria.

### Aprobadas
- Ninguna (pendiente de aprobación del usuario).

## 11. Resultado

- **Inspección completada (solo lectura):** ambos respaldos existen, son `CUSTOM` (v1.16-0), creados con `pg_dump` **17.10**, estructura idéntica (229 TOC, 28 tablas). El respaldo `pre_blueprintmvpseeder` (432 KB) contiene el **dataset completo**; `pre_recuperacion` (83 KB) captura el **estado degradado** previo a la recuperación.
- **Versión compatible:** restaurar con **PostgreSQL 17 (17.10)**, idealmente con el `pg_restore` del contenedor `postgres:17`.
- **Destino propuesto:** contenedor **efímero y aislado** `shoptitans_restore_test` (sin puertos, sin volumen compartido).
- **Procedimiento exacto** de creación, restauración y validación: §6 y §7, listo para aprobación.
- **No se ejecutó ninguna restauración** ni se alteró dato, archivo, contenedor o respaldo. **Sin commit.**

## 12. Pendientes

- **Aprobación del usuario** de DR-1..DR-5 y del procedimiento (§6).
- Tras la aprobación: ejecutar la prueba (Alt. 1), aplicar los criterios de aceptación (§7.5), detener/eliminar el contenedor temporal y registrar los resultados en un informe de ejecución + `AI_WORK_LOG.md`.
- Mantener ambos respaldos hasta autorización expresa de eliminación.

## 13. Preguntas para ChatGPT

- ¿Se prefiere la **Alt. 1** (contenedor efímero) o la **Alt. 2** (BD temporal en el contenedor existente)?
- ¿Basta con validar el respaldo principal, o se desea también la prueba secundaria de `pre_recuperacion`?
- ¿Debe repetirse la validación de la API (`GET /api/v1/blueprints`) contra el entorno restaurado, o basta con conteos e integridad en SQL?

## 14. Próximo paso recomendado

1. Revisar y aprobar (o corregir) la recomendación y las decisiones DR-1..DR-5.
2. Si se aprueba, ejecutar el procedimiento de §6 en una tarea separada, con validación según §7 y criterios de aceptación §7.5.
3. **No** iniciar la restauración real sin autorización del usuario.
