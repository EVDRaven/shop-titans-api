# Reporte Técnico — Revisión correctiva de la API Blueprints (Etapa 2): DP-1 y DP-2

**Fecha:** 2026-10-22
**Autor:** Editor IA
**Estado:** CORREGIDO Y VALIDADO EN PRUEBAS — PENDIENTE DE REVISIÓN INDEPENDIENTE

> La IA editora **no declara aprobada la Etapa 2**. Este reporte implementa las correcciones autorizadas (DP-1, DP-2, conformidad OpenAPI y documentación), aporta evidencias de las validaciones ejecutadas y entrega los cambios para revisión independiente.

## 1. Objetivo

Corregir exclusivamente los puntos identificados en la revisión de la Etapa 2 de la API Blueprints:

- **DP-1:** aceptar `true`, `false`, `1` y `0` en `filter[available_as_antique]`, con conversión inequívoca a booleano, rechazo `422` de valores no admitidos y documentación explícita de la semántica en el contrato OpenAPI.
- **DP-2:** garantizar por configuración del proyecto que las pruebas automatizadas usen una base de datos aislada y no puedan conectarse accidentalmente a PostgreSQL, incluso en los modos de ejecución que antes presentaban el problema (invocación con la opción `env=testing`).
- **Conformidad OpenAPI:** verificar que `docs/openapi/blueprints-v1.yaml` refleje el comportamiento implementado y corregir las discrepancias directamente relacionadas con DP-1.
- **Documentación:** crear este reporte y actualizar `AI_WORK_LOG.md`.

## 2. Alcance

**Incluye:** la validación/normalización del filtro `available_as_antique`, sus pruebas, el parámetro OpenAPI correspondiente y la configuración de aislamiento de pruebas (`phpunit.xml`).

**Excluye (no modificado):** estructura de los endpoints; decisiones D-1..D-8 (salvo la documentación de DP-1); modelos; migraciones; índices; esquema PostgreSQL; seeders; importadores; datos existentes; relaciones Eloquent; lógica de negocio ajena; reportes históricos (no movidos, renombrados ni restaurados). Sin `git commit` ni `git push`. Sin dependencias nuevas.

## 3. Estado inicial

La Etapa 2 se entregó como **IMPLEMENTADA — PENDIENTE DE REVISIÓN** con dos puntos defectuosos:

- **DP-1:** `filter[available_as_antique]` dependía de una regla de validación (`boolean`) que no admitía las cadenas `"true"/"false"`, y la documentación OpenAPI no explicitaba la equivalencia de los cuatro valores.
- **DP-2:** la invocación estándar `php artisan test` aislaba en SQLite `:memory:`, pero la opción `env=testing` rompía el aislamiento y apuntaba a `pgsql`.

## 4. Arquitectura / diagnóstico

### 4.1 Diagnóstico DP-1 (filtro `available_as_antique`)

La regla `boolean` de Laravel no acepta las cadenas `"true"` ni `"false"` (sólo `true/false` nativos o `1/0`), por lo que el filtro era incapaz de admitir los cuatro valores aprobados. Además, la consulta Eloquent no normalizaba el valor antes de decidir `whereNotNull`/`whereNull`, y el contrato OpenAPI no documentaba la equivalencia semántica.

**Semántica final del filtro (aprobada):**

| Entrada | Interpretación | Consulta Eloquent |
|---|---|---|
| `true` | Verdadero | `whereNotNull('available_as_antique_at')` |
| `1` | Equivalente a `true` | `whereNotNull('available_as_antique_at')` |
| `false` | Falso | `whereNull('available_as_antique_at')` |
| `0` | Equivalente a `false` | `whereNull('available_as_antique_at')` |

Cualquier otro valor (`yes`, `2`, vacío, u operador distinto de `eq`) produce `422`.

### 4.2 Diagnóstico DP-2 (aislamiento de pruebas) — causa raíz

1. **`env=testing` no limpia `.env`.** `NunoMaduro\Collision\Adapters\Laravel\Commands\TestCommand::clearEnv()` sólo elimina las variables derivadas de `.env` cuando **no** se pasa la opción `env`. Con `env=testing`, los valores de `.env` (entre ellos `DB_CONNECTION=pgsql`) sobreviven al arranque de PHPUnit.
2. **`<env>` no basta: Laravel prioriza `$_SERVER`.** El handler de PHPUnit escribe las directivas `<env>` mediante `putenv()`/`$_ENV`, mientras que las directivas `<server>` escriben en `$_SERVER`. Laravel resuelve configuración leyendo `$_SERVER` con prioridad sobre `$_ENV`/`getenv`, de modo que las directivas `<env>` eran anuladas por los valores cargados desde `.env` en `$_SERVER`.
3. **Corrección persistente:** fijar las variables críticas también en `$_SERVER` mediante directivas `<server>` (que PHPUnit aplica antes de que Dotenv cargue `.env`, y Dotenv es inmutable y no sobrescribe lo ya presente).
4. **Segundo hallazgo durante la validación (misma clase de fuga):** al confirmar el aislamiento de la conexión, la suite completa en modo `env=testing` falló en `ExampleTest` (GET `/` → 500) con `SQLSTATE[HY000]: no such table: sessions (Connection: sqlite, Database: :memory:)`. Causa: `.env` define `SESSION_DRIVER=database`, valor que sobrevivía en `$_SERVER` y anulaba el `<env name="SESSION_DRIVER" value="array"/>`. **La conexión seguía siendo SQLite** (el mensaje de error lo confirma); el 500 no era una conexión a PostgreSQL, sino una fuga de configuración de sesión. Se corrigió con el mismo mecanismo (`<server>`/`force`) para `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION`, `MAIL_MAILER` y `BROADCAST_CONNECTION`.

## 5. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| `src/app/Http/Requests/BlueprintIndexRequest.php` | Modificado | Constante `ANTIQUE_VALUES = ['true','false','1','0']` y regla `available_as_antique => [Rule::in(self::ANTIQUE_VALUES)]` (sustituye a `boolean`, que no admite `"true"/"false"`). |
| `src/app/Http/Controllers/Api/V1/BlueprintController.php` | Modificado | `applyAntiqueFilter()` normaliza con `in_array((string) $value, ['true','1'], true)` → `whereNotNull`/`whereNull` inequívoco. |
| `src/tests/Feature/BlueprintApiTest.php` | Modificado | `test_index_filters_by_available_as_antique` cubre los 4 valores (con línea base = 3 y verificación de nombres devueltos, garantizando que `false`/`0` filtran y no equivalen a "sin filtro") y nuevo `test_index_rejects_invalid_antique_values` (422 para `yes`, `2`, vacío y operador `gt`). |
| `docs/openapi/blueprints-v1.yaml` | Modificado | `components.parameters.FilterAvailableAsAntique`: `enum: ['true','false','1','0']` + descripción con la equivalencia semántica de cada valor y el `422`. `example: 'true'` (string, coherente con `type: string`). |
| `src/phpunit.xml` | Modificado | Aislamiento persistente: `force="true"` + directivas `<server>` para `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `DB_URL=''` y para `SESSION_DRIVER=array`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`, `BROADCAST_CONNECTION=null`. |
| `src/tests/Feature/TmpEnvProbeTest.php` | Creado y **eliminado** | Sonda temporal y no destructiva usada para diagnosticar DP-2. Eliminada antes de entregar. |
| `docs/reports/2026-10-22_revision_correctiva_api_blueprints.md` | Creado | Este reporte. |
| `AI_WORK_LOG.md` | Modificado | Bitácora del hito y estado. |

## 6. Configuración final de aislamiento de pruebas

Bloque `<php>` de `src/phpunit.xml` (extracto relevante):

```xml
<env name="DB_CONNECTION" value="sqlite" force="true"/>
<env name="DB_DATABASE" value=":memory:" force="true"/>
<env name="DB_URL" value="" force="true"/>
<env name="SESSION_DRIVER" value="array" force="true"/>
<env name="CACHE_STORE" value="array" force="true"/>
<env name="QUEUE_CONNECTION" value="sync" force="true"/>
<env name="MAIL_MAILER" value="array" force="true"/>
<env name="BROADCAST_CONNECTION" value="null" force="true"/>
<!-- ...comentario explicativo... -->
<server name="DB_CONNECTION" value="sqlite"/>
<server name="DB_DATABASE" value=":memory:"/>
<server name="DB_URL" value=""/>
<server name="SESSION_DRIVER" value="array"/>
<server name="CACHE_STORE" value="array"/>
<server name="QUEUE_CONNECTION" value="sync"/>
<server name="MAIL_MAILER" value="array"/>
<server name="BROADCAST_CONNECTION" value="null"/>
```

Esta configuración opera por proyecto (no por convención de ejecución) y hace que ambos modos de invocación (`php artisan test` y `php artisan test env=testing`) usen SQLite `:memory:`.

## 7. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| La regla `boolean` no admitía `"true"/"false"` | Pruebas DP-1 fallaban antes con la regla `boolean`; ahora pasan con `Rule::in` | Confirmada |
| Con `env=testing` la conexión caía en `pgsql` | Sonda temporal: `$_SERVER='pgsql'` con `$_ENV/getenv='sqlite'` | Confirmada |
| Fijar las variables en `$_SERVER` (`<server>`) aísla en todos los modos | Suite completa con `DB_HOST` inexistente pasa en modo estándar y en `env=testing` | Confirmada |
| La conexión en `env=testing` es SQLite tras la corrección | Mensaje de error: `Connection: sqlite, Database: :memory:` | Confirmada |
| La fuga de `SESSION_DRIVER=database` causaba el 500 de `ExampleTest` | Corregida la fuga, la suite completa pasa (73/73) en ambos modos | Confirmada |

## 8. Validaciones ejecutadas

Todos los comandos se ejecutaron en el contenedor `app` (Docker Compose), salvo las comprobaciones de XML/YAML (host) y la verificación de PostgreSQL (contenedor `postgres`, sólo lectura).

**Sintaxis PHP (archivos modificados):**
```bash
docker compose exec -T app php -l app/Http/Requests/BlueprintIndexRequest.php
docker compose exec -T app php -l app/Http/Controllers/Api/V1/BlueprintController.php
docker compose exec -T app php -l tests/Feature/BlueprintApiTest.php
```
```text
No syntax errors detected in app/Http/Requests/BlueprintIndexRequest.php
No syntax errors detected in app/Http/Controllers/Api/V1/BlueprintController.php
No syntax errors detected in tests/Feature/BlueprintApiTest.php
```

**Estilo (Pint):**
```bash
docker compose exec -T app ./vendor/bin/pint --test
```
```text
PASS   .......................................................... 98 files
```

**Pruebas específicas de DP-1 (4 valores + inválidos):**
```bash
docker compose exec -T app php artisan test --filter=antique
```
```text
PASS  Tests\Feature\BlueprintApiTest
✓ index filters by available as antique
✓ index rejects invalid antique values
Tests:    2 passed (15 assertions)
```

**Suite completa — modo estándar:**
```bash
docker compose exec -T app php artisan test
```
```text
Tests:    73 passed (573 assertions)
Duration: 35.49s
```

**Suite completa — modo `env=testing` con sonda de aislamiento (`DB_HOST` inexistente):**
```bash
docker compose exec -T -e DB_HOST=probe.invalid.aislamiento app php artisan test --env=testing
```
```text
Tests:    73 passed (573 assertions)
Duration: 35.16s
```
> El uso de `DB_HOST` inexistente es deliberado: si el aislamiento fallara y el modo intentara conectar a `pgsql`, la conexión fallaría sin ejecutar escrituras. El resultado (todo verde) demuestra que la conexión usada es SQLite, que ignora `DB_HOST`.

**Subconjunto DP-1 en modo `env=testing` con sonda:**
```bash
docker compose exec -T -e DB_HOST=probe.invalid.aislamiento app php artisan test --filter=antique --env=testing
```
```text
Tests:    2 passed (15 assertions)
```

**XML `phpunit.xml` bien formado:**
```bash
python3 -c "import xml.dom.minidom; xml.dom.minidom.parse('src/phpunit.xml'); print('phpunit.xml: XML bien formado')"
```
```text
phpunit.xml: XML bien formado
```

**Sintaxis/integridad del contrato OpenAPI:**
```bash
python3 -c "import yaml; d=yaml.safe_load(open('docs/openapi/blueprints-v1.yaml')); ..."
```
```text
openapi: 3.0.3 | version: 1.0.0 | paths: 2 | params: 41
name: filter[available_as_antique] | in: query | enum: ['true', 'false', '1', '0'] | example: true
total $ref: 75 | refs no resueltas: 0
```

**Verificación de PostgreSQL (sólo lectura):**
```bash
docker compose exec -T postgres psql -U shoptitans -d shoptitans -tAc "SELECT ..."
```
```text
tables=28
blueprints=0
crafting_materials=0
blueprint_components=0
migrations=24 maxbatch=1
```
Coincide con la línea base observada en la revisión (28 tablas, `migrations` batch 1, tablas de datos vacías): **sin cambios atribuibles a esta tarea**.

### 8.1 Estado de las comprobaciones

| Comprobación | Estado |
|---|---|
| `php -l` (3 archivos modificados) | Ejecutada — OK |
| Pint (`--test`, 98 archivos) | Ejecutada — PASS |
| Pruebas DP-1 (`--filter=antique`) | Ejecutada — 2 passed / 15 assertions |
| Suite completa (modo estándar) | Ejecutada — 73 passed / 573 assertions |
| Suite completa (`env=testing` + sonda de aislamiento) | Ejecutada — 73 passed / 573 assertions |
| XML `phpunit.xml` | Ejecutada — bien formado |
| YAML OpenAPI (parseo + `$ref`) | Ejecutada — 75/75 `$ref` resueltas |
| Verificación PostgreSQL | Ejecutada (sólo lectura) — sin cambios |
| Suite completa en modo `env=testing` **sin** sonda de `DB_HOST` | **No ejecutada** (evita cualquier riesgo de conexión real); se usó siempre la sonda de `DB_HOST` inexistente como red de seguridad |

## 9. Resultado final

- **DP-1:** corregido. Los cuatro valores tienen la semántica aprobada, se normalizan de forma inequívoca y los valores no admitidos devuelven `422` (respuesta de validación estándar de la API). Cubierto por pruebas específicas.
- **DP-2:** corregido por configuración del proyecto (`phpunit.xml`). Ambos modos de invocación usan SQLite `:memory:`; demostrado empíricamente con la sonda de `DB_HOST` inexistente.
- **Conformidad OpenAPI:** `FilterAvailableAsAntique` documenta explícitamente `enum: ['true','false','1','0']` y la equivalencia semántica; `example` coherente con `type: string`. Sin cambios de endpoints, esquemas generales ni funcionalidad.
- **Documentación:** este reporte y `AI_WORK_LOG.md` actualizados.

## 10. Riesgos

- **R-1 (residual, informativo):** la opción `env=testing` sigue sin limpiar `.env` (comportamiento del framework). La mitigación adoptada (`<server>` + `force`) lo neutraliza para las variables enumeradas. Si en el futuro `.env` incorpora nuevas variables que deban anularse en pruebas, deberán añadirse del mismo modo.
- **R-2:** el `example` del parámetro es la cadena `'true'`; YAML lo expone como string, consistente con `type: string` y con la serialización por query string.
- **R-3 (heredado, fuera de alcance):** discrepancia de documentación sobre la FK de `blueprint_components.component_id` (ver `PROJECT_MEMORY`/reportes previos). No relacionada con DP-1/DP-2; **no corregida** en esta tarea.

## 11. Discrepancias

- **DP-1:** filtro `available_as_antique` no admitía `"true"/"false"` y contrato sin semántica explícita → **corregido**.
- **DP-2:** `env=testing` rompía el aislamiento (conexión `pgsql`) → **corregido**.
- **Nueva (relacionada con DP-2):** en `env=testing`, `.env` filtraba `SESSION_DRIVER=database` (y otras variables), provocando un `500` en `ExampleTest` aun con aislamiento de BD correcto → **corregido** con el mismo mecanismo.
- **Pendiente (no relacionada):** la discrepancia de documentación de la FK de componentes se registra como pendiente y **no se corrige** aquí (fuera de alcance).

## 12. Limitaciones y puntos pendientes

- No se ejecutó la suite en modo `env=testing` **sin** la sonda de aislamiento, por prudencia (evitar cualquier conexión potencial a PostgreSQL).
- Revisión independiente de la Etapa 2 (incluidas estas correcciones) permanece **pendiente**.
- La corrección de `phpunit.xml` se limita a las variables enumeradas; cualquier futura variable de `.env` que deba anularse en pruebas requerirá el mismo tratamiento.

## 13. Confirmación de no modificación de PostgreSQL

Se confirma expresamente que **no se ejecutaron migraciones, seeders, importadores ni comandos de escritura sobre PostgreSQL**. Todas las ejecuciones de prueba corrieron sobre SQLite `:memory:` (verificado por la sonda de `DB_HOST` inexistente y por el mensaje `Connection: sqlite, Database: :memory:`). La única interacción con PostgreSQL fue una consulta **de sólo lectura** (`SELECT`) que confirma ausencia de cambios. No se modificaron modelos, migraciones, índices, esquema, seeders, importadores, datos existentes ni relaciones Eloquent.

## 14. Preguntas para ChatGPT / revisión independiente

1. ¿Se acepta la extensión del aislamiento a `SESSION_DRIVER`/`CACHE_STORE`/`QUEUE_CONNECTION`/`MAIL_MAILER`/`BROADCAST_CONNECTION` como parte indispensable de DP-2 (misma clase de fuga de `.env` en `env=testing`), o se prefiere acotarlo estrictamente a las variables de BD?
2. ¿Se considera suficiente la sonda de `DB_HOST` inexistente como evidencia de aislamiento, o se requiere una comprobación adicional (p. ej. conteo de conexiones en PostgreSQL durante la ejecución)?
3. ¿La documentación del `example` como string (`'true'`) satisface la coherencia tipo/ejemplo exigida?

## 15. Próximo paso recomendado

Someter estas correcciones a **revisión independiente**. La IA editora **no declara aprobada la Etapa 2**.
