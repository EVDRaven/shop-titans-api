# Reporte Técnico — Verificación de aislamiento de PHPUnit respecto a PostgreSQL

**Fecha:** 2026-10-23
**Autor:** Editor IA
**Estado:** VALIDADA
**Conclusión:** **AISLAMIENTO VERIFICADO**

## 1. Objetivo

Verificar que PHPUnit utiliza SQLite en memoria (`:memory:`) y que **no puede** modificar la base
PostgreSQL de desarrollo, tanto en ejecución normal como con `--env=testing`.

Esta tarea se autorizó explícitamente como continuación del diagnóstico
`docs/reports/2026-10-23_diagnostico_postgresql_vacio.md`, donde se identificó como hipótesis de
alta probabilidad que una ejecución de pruebas hubiera operado contra PostgreSQL por un problema
previo de configuración de `--env=testing`. **Esta tarea no investiga ni confirma la causa del
incidente**; solo verifica el estado actual del aislamiento.

## 2. Alcance

**Incluye:**
- Inspección estática de la configuración de PHPUnit, Laravel y entorno.
- Comprobación de la configuración **efectiva** (no solo de los archivos) en ambos modos.
- Prueba de aislamiento mediante sonda segura (sin `RefreshDatabase`, sin escrituras).
- Ejecución de la suite completa en ambos modos, **solo tras demostrar el aislamiento**.
- Verificación de que PostgreSQL permaneció sin cambios.

**Excluye:**
- Recuperación o recarga de datos.
- Cualquier escritura en PostgreSQL (migraciones, seeders, importadores, DML/DDL).
- Cambios de código o configuración (solo se entregó documentación).
- Correcciones de guardas preventivas (requieren autorización separada).

## 3. Estado inicial

- Contenedores activos: `shoptitans-app` (imagen `shop-titans-api-app`) y `shoptitans-postgres`
  (`postgres:17`), ambos `Up 23 hours`.
- `src/phpunit.xml` ya contiene directivas `force="true"` y el bloque `<server>` (mtime observado
  en el contenedor: `Oct 23 03:47`).
- No existe `src/.env.testing` (solo `.env` y `.env.example`).
- No existe caché de configuración (`src/bootstrap/cache/` contiene únicamente `.gitignore`).

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| `docs/reports/2026-10-23_verificacion_aislamiento_phpunit.md` | Creado | Entregable de esta tarea |
| `AI_WORK_LOG.md` | Modificado | Registro del resultado y estado |

**No se modificó ningún archivo de código ni de configuración.** No hubo commits ni cambios de Git.

Instrumentación transitoria (fuera del árbol del proyecto, ya eliminada):
- Host: `/tmp/isolation_probe/IsolationProbeTest.php` (eliminado al finalizar).
- Contenedor: `/tmp/isolation_probe/` y `/tmp/isolation_probe_result.json` (eliminados al finalizar).

## 5. Cambios realizados

Ninguno en código o configuración. Se ejecutaron únicamente inspecciones de solo lectura,
una sonda de diagnóstico sin escrituras y la suite de pruebas ya existente.

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| H-1: La conexión por defecto resuelta por Laravel bajo PHPUnit es SQLite `:memory:` en modo normal | Sonda: `config_default=sqlite`, `resolved_driver=sqlite`, `resolved_database=:memory:`, `pdo_driver=sqlite` | **Confirmada** |
| H-2: La conexión por defecto resuelta bajo `php artisan test --env=testing` es SQLite `:memory:` | Sonda con `--env=testing`: mismos valores `sqlite`/`:memory:` | **Confirmada** |
| H-3: No se establece ninguna conexión PostgreSQL durante la ejecución | Sonda: `connections_established=[sqlite => sqlite]`, `connections_after_query=[sqlite]`, sin clave `pgsql` | **Confirmada** |
| H-4: La suite completa no produce escrituras en PostgreSQL | `n_tup_ins/upd/del` y `last_value` de secuencias idénticos antes/después (diff sin diferencias) | **Confirmada** |
| H-5: Los valores de conexión provienen exclusivamente de `.env` (no del entorno del contenedor) | `env` del contenedor no contiene variables `DB_*` ni `APP_ENV`; boot plano sin PHPUnit resuelve `pgsql` | **Confirmada** |
| H-6: Las directivas `<server>` son necesarias además de `<env force="true">` para el caso `--env=testing` | Inferencia a partir de H-7 (no se probó el contrafactual; ver §11) | **Probable (no probada directamente)** |
| H-7: Valores presentes en `$_SERVER` prevalecen sobre `<env>` sin `force` | `app_env` difiere entre modos (`testing` en normal, `local` con `--env=testing`), probando la herencia y prioridad de `$_SERVER` | **Confirmada** |

## 7. Discrepancias

| # | Estado documentado | Estado real | Evidencia | Impacto | Propuesta |
|---|---|---|---|---|---|
| D-1 | (previo) Se asumía aislamiento por el flag `--env=testing` | El aislamiento real lo garantizan `force="true"` + `<server>`; `--env=testing` por sí solo **no** aísla | `--env=testing` no limpia el entorno (Collision omite `clearEnv()` cuando hay opción `env`), y `APP_ENV` resuelto es `local`, no `testing` | Medio (documental; no afecta seguridad de datos porque el aislamiento se cumple) | Documentar el mecanismo real en la bitácora |

## 8. Decisiones

### Propuestas
- **P-1:** Mantener y no retirar el bloque `<server>` de `phpunit.xml`: es el mecanismo que
  garantiza el aislamiento en el modo `--env=testing`.
- **P-2:** Al documentar el aislamiento, no atribuirlo al flag `--env=testing` sino a
  `force="true"` + `<server>`.
- **P-3:** Considerar (fuera de esta tarea) una guarda que impida `migrate:fresh`/`migrate:refresh`/
  `db:wipe` contra `DB_DATABASE=shoptitans` en `APP_ENV=local`.

### Aprobadas
- Ninguna. Las propuestas requieren autorización independiente.

### Reemplazadas/Rechazadas
- Ninguna.

## 9. Validaciones

### 9.1 Inspección estática

| Archivo | Hallazgo relevante |
|---|---|
| `src/phpunit.xml` | `<env>` con `force="true"` para `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `DB_URL=""`, `SESSION_DRIVER=array`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`; bloque `<server>` con los mismos valores |
| `src/bootstrap/app.php` | Sin providers ni arranque adicional; sin conexión a BD en el bootstrap |
| `src/bootstrap/providers.php` | Solo `AppServiceProvider` |
| `src/config/database.php` | `'default' => env('DB_CONNECTION', 'pgsql')` (fallback a pgsql si falta la variable) |
| `src/.env` | `APP_ENV=local`, `DB_CONNECTION=pgsql`, `DB_HOST=postgres`, `DB_PORT=5432`, `DB_DATABASE=shoptitans`, `DB_USERNAME=shoptitans`, `DB_PASSWORD` presente (valor no revelado) |
| `src/.env.testing` | **No existe** |
| `src/bootstrap/cache/` | Solo `.gitignore` → **sin caché de configuración** |
| `src/tests/**` | 9 clases usan `RefreshDatabase`; ninguna referencia a `pgsql` ni a conexiones con nombre |

### 9.2 Sonda de aislamiento (modo normal)

```bash
docker exec shoptitans-app php artisan test --without-tty /tmp/isolation_probe/IsolationProbeTest.php
```

Salida relevante:
```json
{
  "app_env": "testing",
  "config_is_cached": false,
  "config_default": "sqlite",
  "resolved_driver": "sqlite",
  "resolved_database": ":memory:",
  "DB_getDefaultConnection": "sqlite",
  "SERVER_DB_CONNECTION": "sqlite",
  "ENV_DB_CONNECTION": "sqlite",
  "getenv_DB_CONNECTION": "sqlite",
  "connections_before_query": ["sqlite"],
  "pdo_driver": "sqlite",
  "select_one": 1,
  "connection_drivers": {"sqlite": "sqlite"},
  "connections_established": ["sqlite => sqlite"],
  "queries_connection_names": ["sqlite"]
}
```
Resultado: `PASS` — 1 prueba, 6 aserciones.

### 9.3 Sonda de aislamiento (modo `--env=testing`)

```bash
docker exec shoptitans-app php artisan test --without-tty --env=testing /tmp/isolation_probe/IsolationProbeTest.php
```

Salida relevante:
```json
{
  "app_env": "local",
  "config_is_cached": false,
  "config_default": "sqlite",
  "resolved_driver": "sqlite",
  "resolved_database": ":memory:",
  "DB_getDefaultConnection": "sqlite",
  "SERVER_DB_CONNECTION": "sqlite",
  "ENV_DB_CONNECTION": "sqlite",
  "getenv_DB_CONNECTION": "sqlite",
  "connections_before_query": ["sqlite"],
  "pdo_driver": "sqlite",
  "select_one": 1,
  "connection_drivers": {"sqlite": "sqlite"},
  "connections_established": ["sqlite => sqlite"],
  "queries_connection_names": ["sqlite"]
}
```
Resultado: `PASS` — 1 prueba, 6 aserciones.

Notas de evidencia:
- `connections_established` se obtuvo escuchando el evento `Illuminate\Database\Events\ConnectionEstablished`;
  solo se registró la conexión `sqlite` como `sqlite`.
- `queries_connection_names` (vía `DB::listen`) contiene únicamente `sqlite`.
- La sonda afirma `assertSame('sqlite', config('database.default'))` **antes** de solicitar cualquier
  conexión: si el aislamiento fallara, la prueba abortaría sin abrir PostgreSQL.
- La sonda **no** usa `RefreshDatabase` ni ejecuta escrituras (solo `select 1` tras verificar).

### 9.4 Boot plano sin PHPUnit (evidencia del origen de los valores)

```bash
docker exec shoptitans-app php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo "config.default=".config("database.default")." getenv(DB_CONNECTION)=".(getenv("DB_CONNECTION") ?: "(unset)")
  ." SERVER_DB_CONNECTION=".($_SERVER["DB_CONNECTION"] ?? "(unset)")
  ." .env.testing=".(is_file(".env.testing")?"existe":"NO existe").PHP_EOL;'
```
Salida:
```text
config.default=pgsql getenv(DB_CONNECTION)=pgsql SERVER_DB_CONNECTION=pgsql .env.testing=NO existe
```
Interpretación: sin el aislamiento de PHPUnit, Laravel resuelve `pgsql` y además lo propaga al
entorno del proceso vía `putenv` (`getenv` = `pgsql`), de modo que un proceso hijo lo hereda.

### 9.5 Entorno del contenedor

```bash
docker exec shoptitans-app env | grep -E '^(DB_|APP_ENV)'
```
Salida: sin coincidencias → los valores `DB_*` provienen **exclusivamente** de `.env`.

### 9.6 Suite completa — modo normal

```bash
docker exec shoptitans-app php artisan test --without-tty
```
Resultado: `Tests: 74 passed (576 assertions)` — `Duration: 36.12s` — exit code `0`.

### 9.7 Suite completa — modo `--env=testing`

```bash
docker exec shoptitans-app php artisan test --without-tty --env=testing
```
Resultado: `Tests: 74 passed (576 assertions)` — `Duration: 36.55s` — exit code `0`.

### 9.8 Versiones

```bash
docker exec shoptitans-app vendor/bin/phpunit --version   # PHPUnit 12.5.32
docker exec shoptitans-app php -v                         # PHP 8.4.26 (cli)
```

### 9.9 Verificación de que PostgreSQL no cambió

Línea base (antes de ejecutar la suite) y estado posterior (tras ambos modos):

```bash
docker exec shoptitans-postgres psql -U shoptitans -d shoptitans -At \
  -c "select relname||'|'||n_tup_ins||'|'||n_tup_upd||'|'||n_tup_del from pg_stat_user_tables order by relname;"
docker exec shoptitans-postgres psql -U shoptitans -d shoptitans -At \
  -c "select sequencename||'|'||coalesce(last_value::text,'NULL') from pg_sequences order by sequencename;"
```
Resultado:
```text
== diff tables ==
IDENTICO
== diff sequences ==
IDENTICO
```
Es decir: contadores de inserción/actualización/borrado y `last_value` de todas las secuencias
**sin ningún cambio** entre antes y después de ejecutar la suite en ambos modos.

### 9.10 Observación adicional (no concluyente, fuera del alcance)

En `/tmp` del contenedor `shoptitans-app` existen cachés de **Laravel Pint** con `mtime`
`2026-10-23 03:31` y `2026-10-23 05:12` UTC (formato `{"php":"8.4.26","version":"3.95.8","rules":{...}}`).
La primera cae dentro de la ventana de reconstrucción del esquema (03:28:10–03:33:36 UTC) descrita en
el diagnóstico previo. Se registra como observación; **no** se investigó su relación con el incidente.

## 10. Resultado final

**AISLAMIENTO VERIFICADO.**

1. El modo normal resuelve SQLite `:memory:` ✔
2. El modo `--env=testing` resuelve SQLite `:memory:` ✔
3. No se estableció ninguna conexión a PostgreSQL durante las comprobaciones ✔
   (`connections_established` = solo `sqlite`; `connection_drivers` sin `pgsql`)
4. No se ejecutó ninguna escritura en PostgreSQL ✔ (contadores y secuencias idénticos)
5. La suite completa finalizó sin utilizar PostgreSQL en ambos modos ✔ (74 pruebas, 576 aserciones)
6. Comandos, resultados y limitaciones documentados ✔

Mecanismo observado: el aislamiento depende de `force="true"` en `<env>` **y** del bloque `<server>`
de `phpunit.xml`. En modo `--env=testing` el entorno hereda `DB_CONNECTION=pgsql` (propagado por
`putenv` desde `.env`); la directiva `<server>` es la que sobrescribe `$_SERVER` y, dado que el
repositorio de entorno de Laravel es inmutable, impide que `.env` vuelva a imponer `pgsql`.

## 11. Riesgos

| # | Riesgo | Estado |
|---|---|---|
| R-1 | El aislamiento depende de la **presencia** del bloque `<server>`; su eliminación reintroduciría el riesgo en el modo `--env=testing` | Mitigado por H-6/P-1; no probado por contrafactual (habría requerido modificar configuración) |
| R-2 | `APP_ENV` resuelto bajo `--env=testing` es `local` (no `testing`), porque no existe `.env.testing` | Observado; sin impacto en la conexión de BD, pero altera el entorno de la app durante las pruebas |
| R-3 | La verificación cubre el punto de entrada PHPUnit; **no** cubre comandos manuales (`migrate:fresh`, `refresh`, `db:wipe`) ejecutados con `APP_ENV=local` | Fuera de alcance; requiere guarda (P-3) |
| R-4 | La sonda verificó solo la conexión por defecto; no puede descartar por sí sola una conexión pgsql creada por código externo a los tests | Mitigado: ningún test referencia `pgsql`; los contadores de PostgreSQL no cambiaron |
| R-5 | Esta verificación **no** confirma la causa del incidente; solo demuestra que, en el estado actual, esta vía no puede escribir en PostgreSQL | Explícito |

## 12. Pendientes

- Confirmar que los datos (1.549 blueprints, 2.747 componentes y catálogos) siguen ausentes y que
  la única fuente de recuperación es el Excel + importadores/seeders idempotentes.
- Decidir sobre P-1/P-2/P-3 (documentación y guardas).
- Autorizar (por separado) la recarga de datos.

## 13. Preguntas para ChatGPT

1. ¿Conviene crear `src/.env.testing` con `APP_ENV=testing` para que el modo `--env=testing` no
   resuelva `local`, o basta con el aislamiento de BD ya garantizado?
2. ¿Es preferible endurecer `phpunit.xml` (por ejemplo, `force="true"` en `APP_ENV`) en lugar de
   introducir `.env.testing`?
3. ¿Qué forma de guarda es menos invasiva para impedir `migrate:fresh`/`refresh`/`db:wipe` contra
   `shoptitans` en `APP_ENV=local`?

## 14. Próximo paso recomendado

1. **Revisión humana de este informe** y aprobación (o rechazo) de las propuestas P-1..P-3.
2. En caso de aprobarse P-2, corregir la redacción de la bitácora para no atribuir el aislamiento
   al flag `--env=testing`.
3. **Detenerse.** La recuperación de datos sigue **no autorizada** y requiere una autorización
   independiente y explícita (ver `docs/reports/2026-10-23_diagnostico_postgresql_vacio.md`).
