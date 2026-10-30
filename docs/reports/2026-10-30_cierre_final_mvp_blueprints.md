# Reporte Técnico — Cierre formal del MVP de Blueprints

**Fecha:** 2026-10-30
**Autor:** Editor IA
**Estado:** REVISIÓN DE CIERRE COMPLETADA — MVP DECLARADO CERRADO
**Tipo:** Revisión de cierre (solo lectura; sin cambios de código, configuración, esquema, datos ni respaldos; sin migraciones, seeders ni escrituras SQL; sin pruebas adicionales; sin commits ni push)

---

## 1. Objetivo y alcance aprobado

### 1.1 Objetivo

Realizar la revisión final de cierre del **MVP de Blueprints** de Shop Titans API, confirmar que no quedan requisitos funcionales obligatorios pendientes, registrar formalmente el cierre y documentar con precisión qué queda **fuera** del MVP y se reserva para etapas futuras.

### 1.2 Alcance aprobado del MVP

El alcance aprobado (contracto de API, etapas 1 y 2, y decisiones D-1..D-8) comprende:

- **API pública de solo lectura**, versionada bajo el prefijo `/api/v1`, **sin autenticación** en esta etapa.
- **Únicamente dos endpoints** de Blueprints: listado y detalle.
- **Listado paginado** (`page`/`per_page`, por defecto 25, máximo 100) con **filtros combinables** y **ordenamiento** por lista permitida.
- **Relaciones selectivas** mediante el parámetro `include` (lista separada por comas).
- **Respuesta JSON uniforme**: listados con `data`, `meta` y `links`; detalle con `data`.
- **Claves JSON y nombres de `include` en `snake_case`** (D-1/D-2); estadísticas en `stats` y energías en `energies` (D-3); campos derivados (D-4); exclusión de campos internos (D-7); tipos garantizados (D-8).
- **Contrato OpenAPI 3.0.3** en `docs/openapi/blueprints-v1.yaml`.
- **Dataset del MVP** cargado en PostgreSQL mediante el seeder orquestador idempotente `BlueprintMvpSeeder`.

### 1.3 Alcance de esta revisión

Revisión **documental y de coherencia** basada en evidencia ya existente en el repositorio. No se implementaron funcionalidades nuevas, no se modificó código/configuración/esquema, no se ejecutaron migraciones, seeders, importadores ni escrituras SQL, no se alteraron respaldos y no se realizaron commits ni push.

---

## 2. Funcionalidades entregadas

| # | Funcionalidad | Estado | Evidencia |
|---|---|---|---|
| F-1 | Endpoint de listado `GET /api/v1/blueprints` con paginación, filtros, orden e `include` | Entregada | [api.php](file:///home/draven/Documentos/shop-titans-api/src/routes/api.php); `CHANGELOG.md` (Added) |
| F-2 | Endpoint de detalle `GET /api/v1/blueprints/{id}` con relaciones selectivas | Entregada | [api.php](file:///home/draven/Documentos/shop-titans-api/src/routes/api.php); `CHANGELOG.md` (Added) |
| F-3 | Filtros combinables con operadores explícitos `eq`/`gt`/`gte`/`lt`/`lte` (D-6) | Entregada | `docs/openapi/blueprints-v1.yaml`; `CHANGELOG.md` (Added/Fixed) |
| F-4 | Búsqueda parcial (contiene, sin distinguir mayúsculas) en campos de texto | Entregada | `CHANGELOG.md` (Fixed H-1); `PROJECT_CONTEXT.md` (H-1) |
| F-5 | Ordenamiento determinista con desempate por `id` (H-2) | Entregada | `CHANGELOG.md` (Fixed H-2) |
| F-6 | Filtro relacional exacto `filter[component]` sobre `crafting_materials.name` (H-5) | Entregada | `CHANGELOG.md` (Added); informe `2026-10-26_implementacion_filtro_blueprints_por_componentes.md` |
| F-7 | Serialización JSON uniforme y tipos garantizados (enteros/decimales) | Entregada | `docs/openapi/blueprints-v1.yaml` (D-3/D-7/D-8) |
| F-8 | Contrato OpenAPI 3.0.3 con ejemplos de respuesta 200, 422, 404 y 500 en formato de producción | Entregada | `docs/openapi/blueprints-v1.yaml`; `CHANGELOG.md` (Added/Fixed) |
| F-9 | Seeder orquestador idempotente del dataset del MVP | Entregada | `src/database/seeders/BlueprintMvpSeeder.php`; informe `2026-10-26_ejecucion_controlada_blueprint_mvp_seeder.md` |
| F-10 | Importadores `import:blueprints` e `import:blueprint-components` | Entregada | `CHANGELOG.md`; `PROJECT_CONTEXT.md:31` |
| F-11 | Aislamiento de pruebas (SQLite `:memory:`) y guarda de comandos destructivos (P-1/P-2/P-3) | Entregada | `PROJECT_CONTEXT.md` (§Aislamiento); `CHANGELOG.md` (Added) |
| F-12 | Acceso HTTP local persistente (A1) y restricción de PostgreSQL a localhost | Entregada | `docker-compose.yml`; `README.md`; informes `2026-10-30_validacion_acceso_http_a1.md` y `2026-10-30_restriccion_postgresql_localhost.md` |

---

## 3. Endpoints y contrato API

### 3.1 Endpoints implementados

Definidos en [api.php](file:///home/draven/Documentos/shop-titans-api/src/routes/api.php) bajo el prefijo versionado `v1`:

| Método | Ruta | Acción |
|---|---|---|
| GET | `/api/v1/blueprints` | Listado paginado con filtros, orden e `include` |
| GET | `/api/v1/blueprints/{id}` | Detalle de un blueprint |

### 3.2 Contrato OpenAPI

- **Archivo:** `docs/openapi/blueprints-v1.yaml`
- **Versión OpenAPI:** 3.0.3 · **Versión del contrato:** 1.0.0
- **Servidor:** `/api/v1` (prefijo relativo al host)
- **Estado declarado en el contrato:** CONTRATO APROBADO (decisiones D-1..D-8 cerradas) e IMPLEMENTADO.
- El contrato documenta paginación, filtros por operador, `include` en lista, esquemas de respuesta y ejemplos de error en formato de producción (`APP_DEBUG=false`).

### 3.3 Decisiones de contrato aprobadas (D-1..D-8)

| ID | Decisión | Estado |
|---|---|---|
| D-1 | Claves JSON en `snake_case` | APROBADA |
| D-2 | Nombres de `include` en `snake_case` | APROBADA |
| D-3 | Estadísticas en `stats`; energías en `energies` | APROBADA |
| D-4 | Campos derivados `craft_time_formatted`, `value_per_second`, `merchant_xp_per_second` | APROBADA |
| D-5 | `per_page` por defecto 25 y máximo 100 | APROBADA |
| D-6 | Sintaxis `filter[campo][op]` y búsqueda parcial en texto | APROBADA |
| D-7 | Exclusión de `raw_value`, ids de pivotes y timestamps internos | APROBADA |
| D-8 | Tipos garantizados (enteros/decimales como números) | APROBADA |

---

## 4. Estado del dataset y evidencia de validación

| Entidad | Conteo verificado |
|---|---|
| `blueprints` | 1.549 |
| `blueprint_components` | 2.747 |
| `crafting_materials` | 186 |

- **Registro canónico de referencia:** `id=59` — "Squire Sword" (tier 1, `gold_value=50`).
- **Evidencia de validación:** conteos confirmados en la recuperación validada del 2026-10-23, en la evaluación de rendimiento del 2026-10-26 y en la respuesta HTTP del 2026-10-30 (`GET /api/v1/blueprints?per_page=1` → `total=1549`).
- **Integridad:** sin huérfanos, duplicados ni pérdida de orden registrados en los informes de validación previos.
- **Fuente:** datos cargados mediante el seeder orquestador idempotente `BlueprintMvpSeeder` a partir del Excel fuente de Shop Titans.

---

## 5. Pruebas previamente ejecutadas y sus resultados

- **Suite completa:** **90 pruebas / 645 aserciones, exit 0** (duración ~41 s), ejecutada contra SQLite `:memory:` con aislamiento verificado (P-1/P-2/P-3). Registrada en el informe `2026-10-26_evaluacion_cierre_mvp_blueprints.md`.
- **Cobertura funcional relevante:** listado, detalle, paginación, filtros combinados, ordenamiento, `include`, equivalencia `filter[campo]`/`filter[campo][eq]`, validación `422`, comportamiento con filtro sin coincidencia y sin duplicados, y el filtro `filter[component]`.
- **Archivos de prueba en el repositorio:** `src/tests/Feature/BlueprintApiTest.php`, `BlueprintMvpSeederTest.php`, `BlueprintAffinityForeignKeysRollbackTest.php`, `BlueprintImportServiceTest.php`, `ImportBlueprintComponentsCommandTest.php`, `CraftingMaterialSeederTest.php`, `BlueprintComponentImportTest.php`, `BlueprintComponentsMigrationRollbackTest.php`; `src/tests/Unit/DestructiveCommandGuardTest.php`, `BlueprintExcelReaderTest.php`.

> Nota: en esta revisión de cierre **no** se ejecutaron pruebas adicionales; se toma como evidencia la suite previamente validada.

---

## 6. Estado de Docker y acceso HTTP local

### 6.1 Estado de Docker

| Contenedor | Servicio | Estado | Publicación |
|---|---|---|---|
| `shoptitans-app` | `app` | Activo | `127.0.0.1:8000->8000/tcp` |
| `shoptitans-postgres` | `postgres` | Activo | `127.0.0.1:5432->5432/tcp` |

- **Volumen de datos:** `shop-titans-api_postgres_data` → `/var/lib/postgresql/data` (`local`, `rw`), intacto.

### 6.2 Acceso HTTP local (A1)

- **Comando del servicio `app`:** `php artisan serve --host=0.0.0.0 --port=8000` ([docker-compose.yml#L13](file:///home/draven/Documentos/shop-titans-api/docker-compose.yml#L13)).
- **Publicación:** `127.0.0.1:8000:8000` (solo loopback del host) ([docker-compose.yml#L15-L16](file:///home/draven/Documentos/shop-titans-api/docker-compose.yml#L15-L16)).
- **Validación:** `GET /api/v1/blueprints?per_page=1` → **HTTP 200**, JSON válido, `total=1549`, `per_page=1`, paginación correcta.
- **Documentación:** sección `## Acceso HTTP (desarrollo)` en [README.md](file:///home/draven/Documentos/shop-titans-api/README.md#L48-L69).
- **Estado:** **A1 IMPLEMENTADO Y VALIDADO el 2026-10-30** (informe `2026-10-30_validacion_acceso_http_a1.md`).

---

## 7. Restricción de PostgreSQL a localhost

- **Cambio aplicado:** `postgres.ports` pasó de `"5432:5432"` a **`"127.0.0.1:5432:5432"`** ([docker-compose.yml#L32-L33](file:///home/draven/Documentos/shop-titans-api/docker-compose.yml#L32-L33)).
- **Efecto:** el puerto PostgreSQL queda publicado **únicamente en loopback** del host; deja de escuchar en todas las interfaces. La app Laravel no se ve afectada porque conecta a `postgres:5432` por el **nombre interno** de la red Docker.
- **Validaciones (todas satisfactorias):** `ss -ltn` → solo `127.0.0.1:5432`; host `psql -h 127.0.0.1 -p 5432` → `shoptitans`/`shoptitans`/PostgreSQL 17.10; desde `app`, `getent hosts postgres` → `172.18.0.2`; `GET /api/v1/blueprints?per_page=1` → 200; ambos servicios activos; volumen idéntico (antes = después).
- **Estado:** **IMPLEMENTADO Y VALIDADO el 2026-10-30 con autorización expresa** (informe `2026-10-30_restriccion_postgresql_localhost.md`), cerrando D-PG-1/D-PG-2/D-PG-3.

---

## 8. Limitaciones conocidas

| ID | Limitación | Impacto | Nota |
|---|---|---|---|
| L-1 | Excel fuente no versionado en el repositorio | Reproducibilidad del seeder requiere el archivo en `src/storage/app/` | Documentado en README (requisito de `storage/app/`) |
| L-2 | `artisan serve` es un servidor de desarrollo monohilo | Adecuado solo para desarrollo local | Documentado en README; ver D-A1-1/D-A1-2 |
| L-3 | Tiempos reales del filtro `filter[component]` no medidos | Sin datos de latencia real (`EXPLAIN ANALYZE` excluido) | Solo se obtuvieron planes estimados (`EXPLAIN`); ver §9 |
| L-4 | Verificación de UFW/iptables no realizada | Sin confirmación de reglas de firewall del host | Requiere root (D-PG-4) |
| L-5 | Prueba secundaria de restauración (`pre_recuperacion`) no ejecutada | Cobertura de recuperación parcial | Opcional (DR-5); la restauración principal sí quedó probada (2026-10-26) |
| L-6 | Datos de homónimos en búsquedas | Resultados ambiguos conocidos y aceptados | Documentado como limitación aceptada |

Estas limitaciones **no** son requisitos funcionales pendientes del MVP; están acotadas por decisiones de alcance o por autorizaciones reservadas al usuario.

---

## 9. Elementos expresamente fuera del MVP

Los siguientes elementos **quedan fuera del alcance del MVP** y se reservan para etapas futuras. **No se consideran defectos del MVP ni tareas obligatorias pendientes.**

1. **Despliegue en VPS y configuración de producción.**
2. **Nginx, HTTPS y dominio.**
3. **Autenticación y autorización** (la API del MVP es pública y de solo lectura).
4. **Nuevos módulos o endpoints fuera de Blueprints** (p. ej. Skills y Enchantments, explícitamente excluidos del MVP).
5. **Nuevas importaciones o ampliaciones del catálogo.**
6. **Medición real de rendimiento del filtro por componente (`EXPLAIN ANALYZE`)** — la evaluación previa se limitó a planes estimados sin `ANALYZE`.
7. **Mejoras opcionales que no sean requisitos aprobados** (p. ej. `--no-reload`, `PHP_CLI_SERVER_WORKERS`, verificación con root de UFW/iptables, índices adicionales).

---

## 10. Validación final de coherencia documental

Se verificó la coherencia entre `README.md`, `CHANGELOG.md`, el contrato OpenAPI, la bitácora `AI_WORK_LOG.md` y este informe de cierre.

| Documento | Contenido verificado | Coherente |
|---|---|---|
| `README.md` | Estado (Seeders/BlueprintImporter/API/Tests ✅); sección de acceso HTTP `127.0.0.1:8000`; comando del seeder | Sí |
| `CHANGELOG.md` | Added: API `/api/v1`, `filter[component]`, ejemplos OpenAPI; Fixed: H-1..H-4; Validated: FK de componentes, `qualities.rank` | Sí |
| `docs/openapi/blueprints-v1.yaml` | OpenAPI 3.0.3, versión 1.0.0, dos endpoints, D-1..D-8 | Sí |
| `AI_WORK_LOG.md` | Hitos hasta 2026-10-30 (A1, diagnóstico y restricción de PostgreSQL) | Sí |
| `PROJECT_CONTEXT.md` | Stack, MVP Blueprints (14 tablas), FK de componentes corregida (`:54`), aislamiento P-1/P-2/P-3 | Sí |
| `docker-compose.yml` | `app` → `127.0.0.1:8000:8000`; `postgres` → `127.0.0.1:5432:5432` | Sí |

### Discrepancias

- **Ninguna discrepancia funcional ni de contrato.** Los endpoints, filtros, ordenamiento, paginación, relaciones, validación y el contrato OpenAPI coinciden con el alcance aprobado y con el código.
- **Observación menor (no bloqueante):** `PROJECT_CONTEXT.md` conserva en su cabecera `Fecha: 2026-08-28` y su sección "Estado validado" no menciona explícitamente el acceso HTTP local (A1) ni la restricción de PostgreSQL a localhost; ambos hitos sí están registrados en `AI_WORK_LOG.md` y en los informes correspondientes. Se deja constancia sin modificar el archivo (fuera del alcance solicitado).

---

## 11. Declaración de cierre y fecha

**Se declara CERRADO el MVP de Blueprints de Shop Titans API a fecha 2026-10-30.**

- Todos los requisitos funcionales del alcance aprobado están **implementados, documentados y validados**.
- No quedan **requisitos funcionales obligatorios** pendientes.
- La suite de pruebas previamente ejecutada pasa íntegra (90 pruebas / 645 aserciones, exit 0).
- El dataset del MVP está cargado y validado en PostgreSQL.
- El acceso HTTP local (A1) y la restricción de PostgreSQL a localhost están implementados y validados.
- Los elementos restantes están **expresamente fuera del MVP** (§9) o son **decisiones opcionales reservadas al usuario** (§8), y no constituyen defectos.

**Firma:** Editor IA
**Fecha de cierre:** 2026-10-30
