# Reporte Técnico — Ejecución de la prueba real de restauración de PostgreSQL

**Fecha:** 2026-10-26
**Autor:** Editor IA
**Estado:** EJECUTADA Y VALIDADA

---

## 1. Objetivo

Ejecutar la **prueba real de restauración** del respaldo principal `backups/shoptitans_pre_blueprintmvpseeder_20261024_153512.dump` en un **entorno aislado y temporal**, validar los conteos e integridad del dataset restaurado, comprobar que la API responde **contra la base restaurada** (y no contra la base de desarrollo), limpiar todo el entorno temporal y documentar los resultados y cualquier desviación.

Esta prueba cierra la **limitación vigente** del proyecto: «la restauración real de una copia de seguridad todavía NO se ha probado».

## 2. Alcance

**Incluye:** confirmación de estado inicial y checksum del respaldo; creación de una red Docker dedicada y temporal y de un contenedor PostgreSQL 17 efímero (sin volúmenes ni puertos publicados); restauración por stdin con `docker exec -i` y `pg_restore --no-owner --no-privileges`; validación de conteos, integridad referencial y esquema (FK/CHECK); prueba de la API contra la base restaurada con el contenedor `app` conectado **exclusivamente** a la red temporal y puerto publicado **solo en `127.0.0.1:8001`**; verificación explícita del destino de conexión; limpieza de contenedores y red temporales; y verificación de que el entorno de desarrollo queda intacto.

**Excluye:** modificar `docker-compose.yml`, Dockerfiles, `.env`, `phpunit.xml` o cualquier archivo del proyecto; escribir en la base de desarrollo `shoptitans`; tocar el volumen `postgres_data`; modificar respaldos; ejecutar migraciones, seeders o importadores; commits o push.

## 3. Estado inicial (comprobado)

```bash
docker ps -a --format '{{.Names}}\t{{.Status}}\t{{.Ports}}'
md5sum backups/shoptitans_pre_blueprintmvpseeder_20261024_153512.dump \
       backups/shoptitans_pre_recuperacion_20261023_203355.dump
docker exec shoptitans-postgres psql -U shoptitans -d shoptitans -tAc "SELECT COUNT(*) FROM blueprints;"
```

| Elemento | Valor inicial | md5 / conteo |
|---|---|---|
| Respaldo principal | `backups/shoptitans_pre_blueprintmvpseeder_20261024_153512.dump` | `53170eada1bce8232cbdbddf51192d5e` (coincide) |
| Respaldo secundario | `backups/shoptitans_pre_recuperacion_20261023_203355.dump` | `61f012053aba28dcda5c6162d9bda165` |
| Base de desarrollo `shoptitans` | `blueprints` | `1549` |
| Contenedores persistentes | `shoptitans-app` (9000/tcp), `shoptitans-postgres` (0.0.0.0:5432->5432/tcp) | intactos |

El checksum del respaldo principal coincide con el esperado (`53170e…d5e`). La base de desarrollo parte de la línea base del dataset del MVP (`blueprints`=1549).

## 4. Recursos temporales creados

| Recurso | Nombre | Configuración |
|---|---|---|
| Red Docker | `shoptitans-restore-net` | bridge dedicada y temporal; **sin puertos publicados** |
| Contenedor PostgreSQL | `shoptitans-restore-pg` | `postgres:17`; `--rm`; **sin volúmenes** montados ni nombrados; **sin puertos** publicados |
| Contenedor app (prueba HTTP) | `shoptitans-restore-app` | imagen `shop-titans-api-app`; `--rm`; conectado **solo** a `shoptitans-restore-net`; `-p 127.0.0.1:8001:8001` |

Ninguno de los recursos temporales se conectó a la red de Compose (`shop-titans-api_default`) ni al servicio PostgreSQL de desarrollo.

## 5. Comandos ejecutados y resultados

### 5.1 Red y PostgreSQL temporal

```bash
docker network create shoptitans-restore-net
docker run --rm -d --name shoptitans-restore-pg --network shoptitans-restore-net \
  -e POSTGRES_USER=shoptitans -e POSTGRES_DB=restore_test -e POSTGRES_PASSWORD=secret postgres:17
docker exec shoptitans-restore-pg pg_isready -U shoptitans -d restore_test
```

Resultado: `pg_isready` → `accepting connections`; servidor y cliente internos = **PostgreSQL 17.10** (`pg_restore --version` del contenedor = `17.10`). El cliente 18.6 del host **no** se usó para restaurar.

### 5.2 Restauración por stdin

```bash
docker exec -i shoptitans-restore-pg pg_restore -U shoptitans -d restore_test \
  --no-owner --no-privileges \
  < backups/shoptitans_pre_blueprintmvpseeder_20261024_153512.dump 2> /tmp/restore_err.txt
```

| Métrica | Valor |
|---|---|
| Código de salida (`EXIT`) | **0** |
| stderr (`/tmp/restore_err.txt`) | **vacío** (sin advertencias ni errores) |
| Cliente usado | `pg_restore` **17.10** del propio contenedor |

No se ocultó ningún error: además del código de salida, se verificó que **stderr quedó vacío** y que el número de objetos restaurados es coherente con el TOC (229 entradas).

### 5.3 Conteos (`restore_test`)

```bash
docker exec shoptitans-restore-pg psql -U shoptitans -d restore_test -tA -c "<consulta de conteos>"
```

| Tabla | Esperado | Obtenido | ¿Coincide? |
|---|---|---|---|
| `blueprints` | 1549 | 1549 | ✅ |
| `blueprint_components` | 2747 | 2747 | ✅ |
| `crafting_materials` | 186 | 186 | ✅ |
| `blueprint_workers` | 3744 | 3744 | ✅ |
| `blueprint_resources` | 4135 | 4135 | ✅ |
| `blueprint_elemental_affinities` | 911 | 911 | ✅ |
| `item_categories` | 40 | 40 | ✅ |
| `workers` | 19 | 19 | ✅ |
| `worker_levels` | 50 | 50 | ✅ |
| `resources` | 12 | 12 | ✅ |
| `elements` | 8 | 8 | ✅ |
| `enchantments` | 73 | 73 | ✅ |
| `spirit_affinities` | 38 | 38 | ✅ |
| `spirits` | 1 | 1 | ✅ |
| `qualities` | 5 | 5 | ✅ |
| `users` | 0 | 0 | ✅ |
| `migrations` | 24 | 24 | ✅ |

**Todos los conteos coinciden exactamente** con la línea base del dataset del MVP.

### 5.4 Integridad referencial

| Comprobación | Resultado |
|---|---|
| Componentes huérfanos respecto a `blueprints` | **0** |
| Componentes huérfanos respecto a `crafting_materials` | **0** |

### 5.5 Esquema, FK y CHECK

| Objeto | Resultado |
|---|---|
| Tablas en `information_schema.tables` (esquema `public`) | **28** |
| Claves foráneas (`pg_constraint contype='f'`) | **18** |
| Restricciones CHECK (`pg_constraint contype='c'`) | **7** |
| Claves primarias (`pg_constraint contype='p'`) | **90** |

Estructura completa y coherente tras la restauración.

## 6. Prueba de la API contra la base restaurada

### 6.1 Contenedor app temporal (red aislada)

```bash
docker run --rm -d --name shoptitans-restore-app --network shoptitans-restore-net \
  -p 127.0.0.1:8001:8001 -v /home/draven/Documentos/shop-titans-api/src:/var/www/html -w /var/www/html \
  -e DB_CONNECTION=pgsql -e DB_HOST=shoptitans-restore-pg -e DB_PORT=5432 \
  -e DB_DATABASE=restore_test -e DB_USERNAME=shoptitans -e DB_PASSWORD=secret \
  shop-titans-api-app php artisan serve --host=0.0.0.0 --port=8001 --no-reload
```

### 6.2 Resultados HTTP (reales)

| Petición | Resultado |
|---|---|
| `GET /api/v1/blueprints?per_page=1` | **200** `{data, meta, links}`; `meta.total=1549`; primer `id=59 "Squire Sword"` |
| `GET /api/v1/blueprints/59` | **200** `{data}` (`Squire Sword`) |
| `GET /api/v1/blueprints?filter[component]=Iron Pine Cone` | **200**; `meta.total=30` |

El listado devuelve el **dataset restaurado** (`total=1549`, primer elemento `id=59` idéntico al observado en la base de desarrollo), lo que confirma que la API lee de `restore_test`.

Nota de herramienta (no de la API): las URLs con corchetes `filter[...]` requieren `curl -g` (globoff) para evitar la interpretación por parte del shell/curl.

### 6.3 Verificación del destino de conexión (no aceptado silenciosamente)

- `php -r "config('database.connections.pgsql')"` dentro del contenedor temporal → `host=shoptitans-restore-pg`, `database=restore_test`.
- El nombre `shoptitans-postgres` (servicio de desarrollo) **no es resoluble** desde `shoptitans-restore-app` (redes distintas).
- `pg_stat_activity` en los servidores: **0** conexiones desde la IP del app temporal (`172.20.0.3`) hacia la base de desarrollo `shoptitans`.
- **Prueba determinista (impacto real):** `pg_stat_database.tup_fetched` de `restore_test` pasó de **19281** a **21413** (**+2132**) tras las peticiones; la base `shoptitans` (desarrollo) mostró actividad ajena a esta prueba, sin peticiones propias del contenedor temporal. Esto demuestra de forma inequívoca que las consultas de la API impactaron en `restore_test`.

### 6.4 Enlace del puerto

```bash
ss -ltn | grep ':8001'
```

Resultado: **`LISTEN 127.0.0.1:8001`** — publicado **solo en loopback**, nunca en todas las interfaces.

## 7. Desviaciones registradas

| # | Desviación | Diagnóstico | Resolución |
|---|---|---|---|
| D-1 | La API devolvió **HTTP 500** en el primer arranque: `SQLSTATE[08006] … could not translate host name "postgres" … Database: shoptitans` | `php artisan serve` **filtra las variables de entorno** al lanzar su proceso hijo `php -S`; Laravel leía `DB_HOST=postgres`/`DB_DATABASE=shoptitans` del archivo `src/.env`, ignorando los overrides `-e DB_*` del contenedor (pese a que `php artisan config:show database.connections.pgsql` sí resolvía correctamente en el proceso padre) | Recrear el contenedor temporal con el flag **`--no-reload`** de `artisan serve`, que propaga todas las variables `$_ENV`; la API pasó a **HTTP 200** |
| D-2 | `curl` con URLs que contienen `filter[...]` | Interpretación de corchetes por el shell/curl | Uso de **`curl -g`** (globoff). Nota de herramienta, no defecto de la API |

La desviación **D-1 es de configuración de ejecución**, no del respaldo ni de la API: la restauración y los datos siempre fueron correctos; solo afectaba al arranque del servidor de pruebas.

## 8. Limpieza

```bash
docker stop shoptitans-restore-app   # → shoptitans-restore-app
docker stop shoptitans-restore-pg    # → shoptitans-restore-pg
docker network rm shoptitans-restore-net   # → shoptitans-restore-net
```

| Verificación final | Resultado |
|---|---|
| Contenedor HTTP temporal | **eliminado** |
| Contenedor PostgreSQL temporal | **eliminado** |
| Red `shoptitans-restore-net` | **eliminada** (exclusivamente esta red) |
| Puerto `8001` | **libre** (sin listeners) |
| Contenedores persistentes | `shoptitans-app` (9000/tcp) y `shoptitans-postgres` (0.0.0.0:5432->5432/tcp) **intactos** |
| Base de desarrollo `shoptitans`.`blueprints` | **1549** (sin cambios) |
| Respaldo principal md5 | `53170eada1bce8232cbdbddf51192d5e` (**sin cambios**) |
| Respaldo secundario md5 | `61f012053aba28dcda5c6162d9bda165` (**sin cambios**) |

No se usó `docker compose down`, `docker system prune`, `docker volume prune` ni ninguna limpieza global. No se eliminó ni modificó ningún otro contenedor, volumen o red.

## 9. Criterios de aceptación

| # | Criterio | Estado |
|---|---|---|
| 1 | `pg_restore` finaliza con exit 0 y sin errores | ✅ (exit 0, stderr vacío) |
| 2 | Conteos coinciden exactamente con la línea base | ✅ (16 tablas + `migrations`) |
| 3 | 0 huérfanos (vs `blueprints` y vs `crafting_materials`) | ✅ |
| 4 | `tables`=28, `migrations`=24, FK y CHECK presentes | ✅ (fks=18, checks=7) |
| 5 | API responde contra la base restaurada (no la de desarrollo) | ✅ (200; `total=1549`; destino verificado) |
| 6 | Base de desarrollo y respaldos inalterados | ✅ |
| 7 | Entorno temporal eliminado por completo | ✅ |

## 10. Resultado final

**Prueba de restauración EXITOSA y VALIDADA.** El respaldo principal `shoptitans_pre_blueprintmvpseeder_20261024_153512.dump` se restaura íntegramente en un entorno aislado: `pg_restore` **exit 0** sin advertencias, los **17 conteos coinciden** con la línea base del MVP, **0 huérfanos**, esquema completo (28 tablas, 18 FK, 7 CHECK), y la **API responde HTTP 200 contra la base restaurada** (`total=1549`), con el **destino de conexión verificado** de forma determinista (`restore_test.tup_fetched` +2132) y **0 conexiones** hacia la base de desarrollo. El entorno temporal se limpió por completo y el entorno de desarrollo queda intacto.

**Conclusión:** queda **cerrada la limitación vigente** de que la restauración real nunca se había probado. La restaurabilidad del respaldo de referencia del dataset del MVP está **demostrada**.

## 11. Riesgos y limitaciones

- **Cliente de restauración:** se usó `pg_restore` **17.10** (contenedor), nunca el 18.6 del host, respetando la política de no usar un cliente mayor que el servidor.
- **`artisan serve` filtra el entorno:** comportamiento relevante solo para pruebas con overrides de `.env` por variables de entorno del contenedor; se resolvió con `--no-reload` (ver D-1).
- **Prueba secundaria no ejecutada:** no se repitió la prueba con `pre_recuperacion` (era opcional, DR-5); queda pendiente si el usuario lo solicita.
- **Alcance de la prueba:** la validación cubre restauración, integridad y disponibilidad de la API; no cubre rendimiento ni concurrencia.

## 12. Pendientes

- **Ninguno obligatorio.** Esta tarea se completó íntegramente.
- **Opcionales (sujetos a decisión del usuario):** prueba secundaria con `pre_recuperacion` (DR-5); persistir el acceso HTTP como A1 (`ports` en `docker-compose.yml` + `README.md`); ejemplos de respuesta en OpenAPI; medición de rendimiento del filtro por componente (DP-E).
- **Restricción vigente:** mantener ambos respaldos hasta autorización expresa de eliminación.

## 13. Preguntas para ChatGPT

- ¿Se desea ejecutar la **prueba secundaria** del respaldo `pre_recuperacion` (DR-5) para cerrar también la validación del estado degradado?
- ¿Se aprueba registrar las decisiones **DR-1..DR-5** como aprobadas tras el éxito de esta ejecución?

## 14. Próximo paso recomendado

1. Dar por **cerrada la limitación vigente** de restauración no probada (informe del 2026-10-26).
2. Decidir (opcional) si se ejecuta la prueba secundaria `pre_recuperacion` y/o se persiste el acceso HTTP (A1).
3. Mantener los respaldos y el entorno de desarrollo sin cambios; **sin commits ni push** hasta autorización.
