# Reporte Técnico — Diagnóstico de PostgreSQL (exposición del `5432`) y runtime de A1

**Fecha:** 2026-10-30
**Autor:** Editor IA
**Estado:** DIAGNÓSTICO COMPLETADO (estrictamente de solo lectura; sin cambios)
**Objeto:** (Tarea 1) publicación actual del puerto PostgreSQL `5432`; (Tarea 2) conexión efectiva de Laravel bajo `php artisan serve` en A1.

---

## 0. Alcance, método y restricciones

Dos diagnósticos **independientes y de solo lectura**. No se modificó ningún archivo ni configuración; no se ejecutaron migraciones, seeders, importaciones ni escrituras en PostgreSQL; no se ejecutó `docker compose down`/`down -v`; no se reiniciaron, recrearon ni eliminaron contenedores ni volúmenes; no se modificaron reglas de firewall; no se hicieron pruebas desde otros equipos; no se añadió código ni rutas de diagnóstico al proyecto.

Todas las consultas SQL realizadas son de lectura (`pg_stat_*`, `current_*`) y las peticiones HTTP son `GET` (solo lectura). **No se exponen contraseñas ni tokens** (valores de `DB_PASSWORD`/`POSTGRES_PASSWORD` enmascarados).

## 1. Estado actual

| Elemento | Estado |
|---|---|
| `shoptitans-app` | `Up`, comando `php artisan serve --host=0.0.0.0 --port=8000`, publicación `127.0.0.1:8000->8000/tcp` |
| `shoptitans-postgres` | `Up` (7 días), `postgres:17`, publicación `0.0.0.0:5432->5432/tcp, [::]:5432->5432/tcp` |
| Red Compose | `shop-titans-api_default` = `172.18.0.0/16` |
| Config Laravel | `default=pgsql`, `host=postgres`, `port=5432`, `database=shoptitans`, `username=shoptitans`, **config NO cacheada** |
| API HTTP | `GET /api/v1/blueprints?per_page=1` → **200** (datos del MVP) |

---

# Tarea 1 — Exposición de PostgreSQL

## 1.1 Publicación declarada y efectiva

**Declarada** ([docker-compose.yml#L32-L33](file:///home/draven/Documentos/shop-titans-api/docker-compose.yml#L32-L33)):

```yaml
    ports:
      - "5432:5432"
```

**Efectiva** (`docker compose ps`, `docker port shoptitans-postgres`):

```text
shoptitans-postgres | Up | 0.0.0.0:5432->5432/tcp, [::]:5432->5432/tcp
5432/tcp -> 0.0.0.0:5432
5432/tcp -> [::]:5432
```

La entrada **no declara `host_ip`**, por lo que Docker la publica en **todas las interfaces** (IPv4 `0.0.0.0` e IPv6 `[::]`). Contraste: `docker port shoptitans-app` → `8000/tcp -> 127.0.0.1:8000` (solo loopback).

## 1.2 Redes de Docker y conexión por el nombre interno `postgres`

```text
docker network inspect shop-titans-api_default →
  shoptitans-postgres  172.18.0.2/16
  shoptitans-app       172.18.0.3/16

docker inspect shoptitans-postgres → Aliases: ["shoptitans-postgres","postgres"]
docker inspect shoptitans-app      → Aliases: ["shoptitans-app","app"]

docker compose exec app: getent hosts postgres → 172.18.0.2  postgres
```

**Sí**: el servicio Laravel resuelve y alcanza `postgres` **por el nombre interno** (`172.18.0.2`) a través de la red de Compose `172.18.0.0/16`. La configuración de Laravel usa `DB_HOST=postgres` (nombre interno), por lo que **la comunicación `app`↔`postgres` no depende de la publicación del puerto**.

## 1.3 ¿Algún componente del proyecto necesita el puerto publicado en el host?

- El servicio `app` se conecta por el **nombre interno** (`DB_HOST=postgres`), no por `127.0.0.1`/`localhost`. El `app` **no** necesita el puerto publicado.
- Búsqueda en el repositorio de referencias a `localhost:5432` / `127.0.0.1:5432`: **solo** aparecen en `docker-compose.yml` (la propia publicación) y en `docs/reports/` (documentación). **No existe** script, config o comando del proyecto que consuma el `5432` publicado del host.
- **Conclusión (limitada al proyecto):** ningún componente del proyecto requiere el puerto publicado.
- **No se presupone uso exclusivamente local:** si el **usuario** necesita conectarse desde el host (p. ej. un cliente gráfico) o desde **otro dispositivo de la LAN**, eso **no consta en el proyecto** y se deja como **decisión pendiente** (ver §5).

## 1.4 Alternativas evaluadas (sin aplicar ninguna)

| Alt. | Declaración | Acceso desde el propio equipo | Acceso desde otros dispositivos | Efecto en `app` |
|---|---|---|---|---|
| **A** | `127.0.0.1:5432:5432` | Sí (por loopback) | **No** (no escucha en la interfaz de LAN) | Sin cambios (usa red interna) |
| **B** | `5432:5432` (actual) | Sí | Potencialmente sí (sujeto al firewall; ver riesgo §4) | Sin cambios |
| **C** | Sin `ports` (solo red interna) | **No** por TCP del host | **No** | Sin cambios (usa red interna) |

**Consecuencias detalladas:**

- **Alternativa A (`127.0.0.1:5432:5432`)** — replica el patrón ya usado en `app`. El puerto solo se publica en loopback: las herramientas del **propio equipo** siguen accediendo por `127.0.0.1:5432`; los **demás dispositivos** de la LAN **no** pueden alcanzarlo; `app` **no** se ve afectado (usa la red interna). Elimina la escucha en todas las interfaces.
- **Alternativa B (mantener `5432:5432`)** — no requiere cambios; el puerto queda publicado en todas las interfaces del anfitrión. El acceso desde el propio equipo funciona; el acceso desde otros dispositivos **depende** de si UFW/iptables lo filtran (no verificable sin root; ver §4).
- **Alternativa C (eliminar `ports`, solo red interna)** — máxima restricción: `app` y cualquier otro contenedor siguen accediendo por `postgres:5432`; **pero** las herramientas del **propio equipo** pierden el acceso directo por TCP del host (`127.0.0.1:5432`). Adecuada solo si nadie necesita un cliente desde el host.

---

# Tarea 2 — Conexión efectiva de Laravel bajo `artisan serve` (A1)

## 2.1 Comando que ejecuta el contenedor

```text
docker inspect shoptitans-app → Config.Cmd = ["php","artisan","serve","--host=0.0.0.0","--port=8000"]
```

Procesos dentro del contenedor (`/proc`, ya que `ps` no está instalado en la imagen):

```text
PID 1  -> php artisan serve --host=0.0.0.0 --port=8000
PID 9  -> /usr/local/bin/php -S 0.0.0.0:8000 …/resources/server.php
```

Es decir, `artisan serve` (PID 1) **lanza un proceso hijo** `php -S` (PID 9) que atiende las peticiones. **Un solo worker** (no hay `PHP_CLI_SERVER_WORKERS` efectivo).

## 2.2 Configuración Laravel cacheada (`config:cache`)

```text
ls -la bootstrap/cache → .gitignore, packages.php, services.php   (NO existe config.php)
php artisan tinker → configurationIsCached() = false
```

**No hay configuración cacheada.** Consecuencia: `LoadEnvironmentVariables` **no** retorna temprano y **carga el `.env` en cada arranque de la aplicación** (cada petición HTTP lee `.env`). Si se usara `config:cache`, `.env` dejaría de leerse y regirían los valores horneados en `bootstrap/cache/config.php`.

## 2.3 Variables `DB_*` en el proceso servidor vs el proceso hijo

```text
PID 1 (/proc/1/environ, entorno del SO): 14 variables — NINGUNA DB_* (solo PATH, PHP_*, GPG_KEYS…)
PID 9 (/proc/9/environ, entorno del SO):  2 variables — incluye APP_ENV=local; NINGUNA DB_*
```

**Mecanismo** ([ServeCommand.php#L181-L202](file:///home/draven/Documentos/shop-titans-api/src/vendor/laravel/framework/src/Illuminate/Foundation/Console/ServeCommand.php#L181-L202)): sin `--no-reload`, al construir el proceso hijo solo se **propagan** las variables de `static::$passthroughVariables` (`APP_ENV`, `PATH`, `LARAVEL_SAIL`, `PHP_IDE_CONFIG`, `XDEBUG_*`, `SYSTEMROOT`, `HERD_*`, `IGNITION_*`); **el resto se elimina** (`[$key => false]`). El `$_ENV` del padre incluye los valores del `.env` (los añade Dotenv), pero **DB_\* se filtra** al hijo.

> **Aclaración clave:** las variables `DB_*` **no** llegan al hijo por el entorno del SO; el hijo las obtiene **re-leyendo el archivo `.env`** al arrancar el framework (ver §2.4).

## 2.4 Configuración efectiva de conexión durante una petición HTTP

- El hijo (`php -S`, PID 9) arranca el framework y ejecuta `LoadEnvironmentVariables`: como **no hay config cacheada**, carga `src/.env` (montado en `/var/www/html`).
- El repositorio de Dotenv es **immutable** ([Env.php#L89](file:///home/draven/Documentos/shop-titans-api/src/vendor/laravel/framework/src/Illuminate/Support/Env.php#L89) → `$builder->immutable()->make()`): **una variable ya presente en el entorno del proceso NO se sobrescribe** por el `.env`. Como `DB_*` **no** están en el entorno del hijo (filtradas en §2.3), el `.env` **las aporta**.
- Config efectiva resuelta (CLI, mismo `.env` y mismos archivos que el servidor; **sin secretos**):

```json
{"default":"pgsql","host":"postgres","port":"5432","database":"shoptitans","username":"shoptitans","cached":false}
```

## 2.5 Identificación de la base de datos/usuario/servidor usados por la aplicación HTTP

Como no existe un endpoint de diagnóstico ni se permite modificar código/reiniciar, la conexión **de la petición HTTP** se identificó por **inferencia instrumental de solo lectura**:

1. **Atribución por actividad de base de datos** — se midió `pg_stat_database.xact_commit` de `shoptitans` antes/después de ráfagas de peticiones HTTP:

```text
t0=10245        (reposo)
idle+5s=10247   (deriva ≈ 0 en reposo)
tras 5 GET=10294   (+47 → ≈ 9 transacciones por petición)
tras 5 GET=10343   (+47 → ≈ 9 transacciones por petición)

pg_stat_database: shoptitans=10241 ; postgres=0 ; template0=0 ; template1=0
```

Las peticiones HTTP **incrementan las transacciones solo en `shoptitans`** (≈9/petición) y **no** en las demás bases → la aplicación HTTP **usa `shoptitans`**.

2. **Servidor y usuario** — resueltos por configuración (§2.4): servidor `postgres` (`172.18.0.2`, red interna), puerto `5432`, base `shoptitans`, usuario `shoptitans`.
3. **Identidad de sesión SQL** (sesión CLI de control): `current_database()=shoptitans`, `current_user=shoptitans`.

**Limitación:** no fue posible leer el `usename`/`client_addr` **exactos de la conexión HTTP** (es transitoria y requiere root o `log_connections` o instrumentación de la app). La atribución se apoya en el delta de `xact_commit` y en la configuración, no en la lectura directa de esa conexión.

## 2.6 ¿Coincide con la base de desarrollo esperada `shoptitans`?

**Sí.** La base efectiva es `shoptitans` (en el servidor `postgres`), la esperada para desarrollo. Evidencia convergente: (a) config `database=shoptitans`; (b) delta de `xact_commit` sobre `shoptitans` con peticiones HTTP; (c) la API devuelve los **datos del MVP** (`id=59 "Squire Sword"`, `meta.total=1549`).

## 2.7 ¿Es necesario añadir `--no-reload`?

**No es necesario para la conectividad de BD en la configuración actual de A1.** El hijo obtiene la config de BD del archivo `.env` (no del entorno del SO), y el funcionamiento está **probado** (`GET` → 200 con datos; `xact_commit` sobre `shoptitans`).

**Por qué el informe de restauración del 26-10-2026 sí lo usó:** aquella prueba **inyectaba** `DB_*` por **entorno del SO** (`docker compose run … -e DB_HOST=shoptitans-restore-pg …`). Sin `--no-reload`, `artisan serve` **elimina** esas variables en el hijo (§2.3) y el hijo caería al `DB_HOST=postgres` del `.env` → apuntaría a la **base de desarrollo** en lugar de a la restaurada. Con `--no-reload`, todas las variables del padre pasan al hijo y el override `-e` prevalece (Dotenv es immutable). Es decir, `--no-reload` es **crítico cuando se depende de overrides por entorno**, no en A1, cuyo valor proviene del `.env`.

**Consecuencias de añadir `--no-reload`:**

| Aspecto | Sin `--no-reload` (actual) | Con `--no-reload` |
|---|---|---|
| Propagación de `env` del padre al hijo | Solo `passthroughVariables`; `DB_*` filtradas | **Todas** las variables pasan |
| Overrides vía `-e` (p. ej. pruebas aisladas) | **No** surten efecto (el hijo usa `.env`) | **Sí** surten efecto |
| Recarga automática al cambiar `.env` | **Sí** (reinicia el servidor al detectar cambio de mtime) | **No** (habría que reiniciar el contenedor) |
| `PHP_CLI_SERVER_WORKERS >= 2` | Ignorado (1 worker) | Respetado (multiproceso) |

---

## 3. Riesgos y limitaciones

- **R-EXP-1 (publicación amplia):** el `5432` escucha en **todas las interfaces**; es **más amplio de lo necesario** para uso local (a diferencia de `app`, en `127.0.0.1`).
- **R-EXP-2 (riesgo conocido Docker/UFW):** es un comportamiento **documentado** que los puertos publicados pueden **eludir las reglas de entrada de UFW** (cadenas NAT/FILTER del motor evaluadas antes de `INPUT`). **No verificable aquí** (iptables requiere root). Ver informe [2026-10-30_diagnostico_exposicion_postgresql.md](file:///home/draven/Documentos/shop-titans-api/docs/reports/2026-10-30_diagnostico_exposicion_postgresql.md).
- **L1 — UFW no legible** (requiere root); **L2 — iptables no legible** (requiere root).
- **L3 —** la conexión HTTP es transitoria: no se pudo leer su `usename`/`client_addr` sin root o instrumentación.
- **L4 —** el `xact_commit` incluye las sesiones `psql` de control (deriva en reposo ≈ 0, contabilizada).
- **L5 —** no se hicieron pruebas desde otros equipos (prohibido) → accesibilidad LAN/Internet **no** confirmada empíricamente.
- **L6 —** el análisis de A1 aplica a un `artisan serve` **sin** config cacheada; con `config:cache` el `.env` dejaría de leerse (cambiaría el análisis).

## 4. Recomendación técnica

**Tarea 1 (exposición de PostgreSQL):**
1. **No mantener `0.0.0.0` salvo requisito explícito.** Recomendado: **Alternativa A** (`127.0.0.1:5432:5432`), replicando el patrón de `app`; preserva el acceso del propio equipo y `app`/contenedores no se ven afectados (red interna).
2. Si se requiere acceso desde **otros dispositivos**, optar por un binding controlado o políticas explícitas (tarea aparte), y **verificar antes con root** el estado real de UFW/iptables.
3. **No implementar** ningún cambio sin aprobación (restricción vigente prohíbe modificar la publicación del `5432`).

**Tarea 2 (runtime de A1):**
1. **Mantener** `php artisan serve --host=0.0.0.0 --port=8000` **sin** `--no-reload` para el desarrollo local de A1: la configuración proviene del `.env` y está probada.
2. **Documentar** que `--no-reload` es **necesario** cuando se dependa de overrides por entorno (`-e`) — p. ej. pruebas de restauración aisladas — y que, con él, cambiar `.env` exigirá **reiniciar** el contenedor.
3. Si se desea paralelismo, evaluar `PHP_CLI_SERVER_WORKERS` junto con `--no-reload` (fuera del alcance de A1).

## 5. Decisiones que requieren aprobación del usuario

- **D-PG-1:** ¿Se acota la publicación a `127.0.0.1:5432:5432` (Alternativa A)? Es la recomendación.
- **D-PG-2:** ¿Se mantiene `5432:5432` (B) o se elimina la publicación (C)?
- **D-PG-3:** ¿Se **necesita** acceso directo a PostgreSQL desde el **propio equipo** o desde **otro dispositivo de la LAN**? (No presupuesto; determina A/B/C.)
- **D-PG-4:** ¿Se autoriza verificación **con root** (lectura de UFW e iptables) para cerrar L1/L2?
- **D-A1-1:** ¿Se añade `--no-reload` al comando de `artisan serve`? (No necesario hoy; relevante para overrides por entorno.)
- **D-A1-2:** ¿Se adopta `PHP_CLI_SERVER_WORKERS` para permitir múltiples workers?

---

## 6. Comandos ejecutados (solo lectura) y resultados relevantes

| # | Comando | Resultado relevante |
|---|---|---|
| 1 | `docker compose ps -a` | `app` `127.0.0.1:8000->8000/tcp`; `postgres` `0.0.0.0:5432->5432/tcp, [::]:5432->5432/tcp` |
| 2 | `docker port shoptitans-postgres` / `shoptitans-app` | `5432/tcp -> 0.0.0.0:5432`, `[::]:5432`; `8000/tcp -> 127.0.0.1:8000` |
| 3 | `docker network inspect shop-titans-api_default` | `postgres 172.18.0.2/16`, `app 172.18.0.3/16` |
| 4 | `docker inspect shoptitans-postgres` (aliases) | `["shoptitans-postgres","postgres"]` |
| 5 | `docker compose exec app getent hosts postgres` | `172.18.0.2  postgres` |
| 6 | `docker inspect shoptitans-app --format '{{json .Config.Cmd}}'` | `["php","artisan","serve","--host=0.0.0.0","--port=8000"]` |
| 7 | `for p in /proc/[0-9]*; do tr '\0' ' ' < $p/cmdline; done` (en `app`) | PID 1 `artisan serve…`; PID 9 `php -S 0.0.0.0:8000 …/server.php` |
| 8 | `tr '\0' '\n' < /proc/{1,9}/environ` (en `app`, filtrado) | PID 1: 14 vars (sin `DB_*`); PID 9: 2 vars (`APP_ENV=local`, sin `DB_*`) |
| 9 | `ls -la bootstrap/cache` (en `app`) | `packages.php`, `services.php` — **sin `config.php`** |
| 10 | `php artisan tinker --execute="…config('database…')…"` | `{default:pgsql, host:postgres, port:5432, database:shoptitans, username:shoptitans, cached:false}` |
| 11 | `psql -tAc "SELECT xact_commit … pg_stat_database"` (antes/después de ráfagas GET) | `shoptitans` +47/5 GET (≈9/petición); `postgres`/`template1` = 0 |
| 12 | `psql -tAc "SELECT datname,usename,client_addr,state,application_name FROM pg_stat_activity …"` | Solo sesión `psql` de control (`shoptitans`/`shoptitans`) |
| 13 | `curl -g "http://127.0.0.1:8000/api/v1/blueprints?per_page=1"` | `200`; `id=59 "Squire Sword"`; `total=1549` |
| 14 | `grep` de `5432`/`localhost`/`127.0.0.1` en el repo | Solo `docker-compose.yml` y `docs/reports/` (sin consumidores del puerto publicado) |

> Valores de `DB_PASSWORD`/`POSTGRES_PASSWORD` **no** incluidos en ningún momento.

## 7. Confirmaciones

- Diagnóstico **estrictamente de solo lectura**; **sin** modificar `docker-compose.yml`, `.env`, reglas de firewall ni ningún archivo de configuración.
- **No** se ejecutaron migraciones, seeders, importaciones ni escrituras en PostgreSQL; **no** se tocaron respaldos.
- **No** se detuvieron, reiniciaron, recrearon ni eliminaron contenedores ni volúmenes.
- **No** se añadió código ni rutas de diagnóstico al proyecto; **sin** commit ni push.
