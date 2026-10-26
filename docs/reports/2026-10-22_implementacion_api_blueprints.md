# Reporte Técnico — Etapa 2: Implementación de la API pública de Blueprints

**Fecha:** 2026-10-22
**Autor:** Editor IA
**Estado:** IMPLEMENTADA — PENDIENTE DE REVISIÓN

## 1. Objetivo

Implementar los dos endpoints de lectura del contrato OpenAPI aprobado
(`docs/openapi/blueprints-v1.yaml`, 1.0.0), respetando las ocho decisiones
arquitectónicas D-1 a D-8: `GET /api/v1/blueprints` (listado paginado con
filtros, operadores, orden y `include`) y `GET /api/v1/blueprints/{id}`
(detalle con 404 coherente). La API es pública, de solo lectura y sin
autenticación.

## 2. Alcance

**Incluye:**
- Registro de la infraestructura API versionada `/api/v1` (`src/routes/api.php` + grupo `api` en `bootstrap/app.php`).
- Los dos endpoints autorizados y sólo esos dos.
- Form Requests con whitelist estricta de `sort`, `include` y familias de filtros/operadores.
- API Resource conforme a los esquemas `Blueprint`, `BlueprintListResponse` y `BlueprintResponse`.
- Serialización `snake_case`, agrupación `stats`/`energies`, derivados expuestos, tipos JSON garantizados (D-8) y exclusión de campos internos (D-7).
- Carga explícita de relaciones para evitar N+1, verificada por conteo de consultas.
- Suite de pruebas Feature sobre SQLite `:memory:`.

**Excluye (prohibido en esta etapa):**
- Ejecutar `migrate`, `db:seed`, importadores o cualquier escritura/DDL en PostgreSQL.
- Cambiar migraciones, modelos, relaciones, restricciones o índices.
- Instalar/actualizar dependencias.
- Añadir endpoints de catálogos, autenticación, CORS o rate limiting.
- Modificar el contrato OpenAPI o las decisiones D-1..D-8.
- Commit, push o alteración del historial de Git.
- Mover/restaurar/renombrar/eliminar reportes existentes.

## 3. Estado inicial

```text
Rama: master (origin/master) — commit f0cb37c "feat: modelar elementos y afinidades de blueprints"
Cambios versionados al iniciar: AI_WORK_LOG.md (M), docker/php/Dockerfile (M, preexistente).
Infraestructura API: inexistente (sin routes/api.php, sin grupo api, sin controladores/Resources/Requests).
Contenedores: shoptitans-app y shoptitans-postgres Up.
```

Discrepancia de ruta: la tarea cita los reportes de Etapa 1 en `docs/reports/reprotes/`,
pero esa carpeta **no existe**. Los reportes residen en `docs/reports/` (ubicación
actual, no alterada). Ver §7.

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| `src/routes/api.php` | Creado | Registra `/api/v1/blueprints` y `/api/v1/blueprints/{id}`. |
| `src/bootstrap/app.php` | Modificado | Añade `api: __DIR__.'/../routes/api.php'` en `withRouting()`. |
| `src/app/Http/Requests/BlueprintRequest.php` | Creado | Base abstracta: whitelist `INCLUDES`, `EAGER_LOAD_MAP`, `includeList()`, `validateInclude()`. |
| `src/app/Http/Requests/BlueprintIndexRequest.php` | Creado | Validación del listado (`page`, `per_page`, `sort`, `include`, `filter` + operadores). |
| `src/app/Http/Requests/BlueprintShowRequest.php` | Creado | Validación del detalle (`id`, `include`). |
| `src/app/Http/Controllers/Api/V1/BlueprintController.php` | Creado | `index()` y `show()` con paginación, filtros, orden y eager loading. |
| `src/app/Http/Resources/BlueprintResource.php` | Creado | Serialización conforme al contrato (D-1..D-8). |
| `src/tests/Feature/BlueprintApiTest.php` | Creado | 16 pruebas Feature (SQLite `:memory:`). |
| `docs/reports/2026-10-22_implementacion_api_blueprints.md` | Creado | Este reporte. |
| `AI_WORK_LOG.md` | Modificado | Nuevo hito de Etapa 2. |

No se modificó ningún modelo, migración, seeder, restricción ni índice.

## 5. Cambios realizados

### 5.1 Infraestructura de rutas
- `src/routes/api.php` define el grupo `prefix('v1')` con `GET /blueprints` y `GET /blueprints/{id}`
  apuntando a `BlueprintController::index|show`. El grupo `api` hereda el middleware por defecto
  de Laravel (`SubstituteBindings`), sin Sanctum ni `throttle` (API pública de solo lectura).
- `src/bootstrap/app.php` incorpora `api:` a `withRouting()`. Se conservan `web`, `commands`,
  `health: /up` y el manejador de excepciones existente (`shouldRenderJsonWhen` para `api/*`).

### 5.2 Validación (Form Requests)
- `BlueprintRequest` (base): `INCLUDES` con las 11 relaciones aprobadas; `EAGER_LOAD_MAP`;
  `includeList()` que parsea la lista separada por comas (`style: form`, `explode: false`) y
  normaliza; `validateInclude()` que rechaza relaciones fuera de la whitelist.
- `BlueprintIndexRequest`: `SORTABLE` (7 campos del contrato, con prefijo `-`); `FILTERS`
  clasificado en familias `partial` (`name`, `unlock_prerequisite`), `exact` (catálogos +
  `available_as_antique`) y `numeric` (5 campos); operadores `NUMERIC_OPERATORS`
  (`eq/gt/gte/lt/lte`) y `EXACT_OPERATORS` (`eq`). `page ≥ 1`, `per_page` 1..100.
  El hook `after()` valida campo, operador y operando (rechazo 422).
- `BlueprintShowRequest`: fusiona `id` desde la ruta y valida `id` (`integer`, `min:1`) e `include`.

### 5.3 Controlador
- `index()`: aplica eager loading, filtros y orden, paginación (`page`, `per_page` por defecto 25),
  y responde `{ data, meta, links }` con `BlueprintResource::collection(...)->resolve()`.
- `show()`: `findOrFail` (404 nativo) y responde `{ data }`.
- `applyRelations()`: `category` siempre; añade mapeos de `EAGER_LOAD_MAP`; `elementalAffinities`
  se carga con `->with('element')->orderBy('position')` para preservar el orden.
- `applyFilter()`: coincidencia parcial `LOWER(col) LIKE ?` para texto; `whereHas` para catálogos;
  `whereNotNull/whereNull` para `available_as_antique`; operadores SQL en los numéricos.
- `applySort()`: prefijo `-` = descendente.

### 5.4 Serialización (API Resource)
- Claves en `snake_case` (D-1); `category` y demás catálogos como `{id, name}`.
- `stats` (atk/def/hp/eva/crit/mana_shield) y `energies` (discount/surcharge/suggest/speed_up) agrupados (D-3).
- Derivados expuestos: `craft_time_formatted`, `value_per_second`, `merchant_xp_per_second` (D-4).
- Enteros forzados a `int`; decimales (`decimal:4`) convertidos a `float` (D-8).
- Excluidos `raw_value`, `id` de tablas pivote y `created_at`/`updated_at` (D-7).
- Relaciones expuestas sólo si fueron cargadas (`whenLoaded`), conforme a la whitelist.

### 5.5 Pruebas
- `BlueprintApiTest` (16 pruebas): estructura paginada y tipos; omisión de relaciones no
  solicitadas y de campos internos; paginación; 422 en paginación/`sort`/`include`/filtro/operador
  inválidos; operadores numéricos y `eq` por defecto; búsqueda parcial insensible a mayúsculas;
  filtros combinados; filtros por relaciones; `available_as_antique`; orden; detalle con `include`;
  404; 422 del detalle; y **prevención de N+1** (mismo número de consultas con 1 y con 6 blueprints).

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| El grupo `api` de Laravel provee enrutado + `SubstituteBindings` sin auth/throttle | `routes/api.php` + `bootstrap/app.php`; rutas responden en las pruebas | Confirmada |
| `include=components` requiere mapeo a `blueprintComponentsAsParent` | `EAGER_LOAD_MAP`; prueba de detalle con `include=components` | Confirmada |
| `elemental_affinities` exige orden por `position` | carga especial `->orderBy('position')` | Confirmada |
| `decimal:4` serializa como string y debe convertirse a float (D-8) | `floatOrNull()`; prueba de tipos (`value_per_second` = float) | Confirmada |
| La whitelist evita `sort`/`include`/filtros/operadores no aprobados | 4 pruebas de rechazo (422) | Confirmada |
| La carga explícita evita N+1 | prueba `index avoids n plus one queries` | Confirmada |
| Las pruebas se ejecutan aisladas en SQLite `:memory:` con la invocación estándar | sonda con `DB_HOST` inexistente (ver §10) | Confirmada |

## 7. Discrepancias

1. **Ubicación de reportes (documentación vs. repositorio).**
   - Estado documentado: la tarea cita los reportes de Etapa 1 en `docs/reports/reprotes/`.
   - Estado real: `docs/reports/reprotes/` **no existe**; los reportes residen en `docs/reports/`.
   - Evidencia: listado de `docs/reports/` (24 archivos, incluido el reporte de Etapa 1).
   - Impacto: bajo (documental). Acción tomada: crear el reporte en `docs/reports/` respetando
     la organización existente; no se movió/restauró/eliminó ningún reporte.
   - Aprobación requerida: confirmar la convención de ubicación definitiva.

2. **`PROJECT_CONTEXT.md` vs. esquema real (`blueprint_components.component_id`).**
   - Estado documentado (memoria de proyecto): `component_id → blueprints.id`.
   - Estado real: `2026_10_14_000002_change_blueprint_components_component_id_foreign.php`
     re-apunta la FK a `crafting_materials`. El contrato ya lo refleja.
   - Impacto: nulo en esta etapa (la implementación sigue el esquema real y el contrato).
   - Aprobación requerida: corregir `PROJECT_CONTEXT.md`.

3. **Comentario desactualizado en migración.**
   - `2026_08_28_100010_create_blueprint_components_table.php` aún declara
     `->constrained('blueprints')` para `component_id`; el destino real es `crafting_materials`.
   - No se modifica (prohibido). Se documenta.

D-7 (exclusión de `raw_value`) se verificó por prueba; no hay discrepancias en ese punto.

## 8. Decisiones propuestas

- **DP-1.** Interpretar `filter[available_as_antique]` como booleano textual de query string,
  aceptando `true`, `false`, `1`, `0` (la regla `boolean` de Laravel no acepta `"true"/"false"`
  como cadena; el contrato sólo describe el caso `true`). Ver §10 (corrección aplicada).
- **DP-2.** Adoptar la invocación de pruebas con aislamiento explícito
  (`-e DB_CONNECTION=sqlite -e DB_DATABASE=:memory:`) como forma canónica, y **no** usar el flag
  `--env=testing` (rompe el aislamiento; ver §10).

## 9. Decisiones aprobadas

- **D-1..D-8** (aprobadas previamente por el representante humano): `snake_case`; nombres de
  `include` en `snake_case`; agrupación `stats`/`energies`; derivados expuestos; `per_page`
  25/100; operadores `filter[campo][op]` con búsqueda parcial en texto; exclusión de campos
  internos; tipos JSON garantizados. La implementación las respeta íntegramente.
- Alcance autorizado de la Etapa 2: sólo los dos endpoints de lectura autorizados.

## 10. Validaciones, comandos y códigos de salida

Todas las validaciones se ejecutaron dentro del contenedor `app`.

| # | Validación | Comando | Resultado | Exit |
|---|---|---|---|---|
| 1 | Sintaxis PHP (8 archivos) | `docker compose exec -T app php -l <archivo>` | `No syntax errors detected` en los 8 | 0 |
| 2 | Estilo (suite completa) | `docker compose exec -T app ./vendor/bin/pint --test` | `PASS — 98 files` | 0 |
| 3 | Estilo (archivo corregido) | `docker compose exec -T app ./vendor/bin/pint --test app/Http/Requests/BlueprintIndexRequest.php` | `PASS — 1 file` | 0 |
| 4 | Pruebas del endpoint | `docker compose exec -T -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e DB_URL= app php artisan test tests/Feature/BlueprintApiTest.php` | **16 passed (141 assertions)** | 0 |
| 5 | Suite completa | `docker compose exec -T -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e DB_URL= app php artisan test` | **72 passed (562 assertions)** | 0 |
| 6 | Sonda de aislamiento (estándar) | `... -e DB_HOST=host-invalido-sonda app php artisan test tests/Feature/BlueprintApiTest.php --filter=test_index_returns_paginated` | **PASS** (no usa el host de BD → SQLite aislado) | 0 |
| 7 | Sonda de aislamiento (con `--env=testing`) | `... -e DB_HOST=host-invalido-sonda app php artisan test --env=testing ...` | **FAILED**: `Connection: pgsql, Host: host-invalido-sonda` | 0 |
| 8 | Estado de PostgreSQL (solo lectura) | `psql -c "select count(*) from pg_tables ..."; select batch,count(*) from migrations` | 28 tablas; `migrations` batch 1 = 24 | 0 |

**Corrección aplicada durante las validaciones.** La primera ejecución del test de
`available_as_antique` falló (422) porque se usó la regla `boolean`, que no acepta las cadenas
`"true"/"false"` de query string. Se cambió `valueRules('available_as_antique')` a
`['in:true,false,1,0']` (el controlador ya normalizaba con `filter_var`). Tras la corrección:
16/16 en el archivo y 72/72 en la suite.

**Incidente de aislamiento (documentado).** Una ejecución previa con el flag `--env=testing`
(no la invocación estándar) hizo que PHPUnit apuntara a **pgsql**: el proceso padre carga
`.env` (`DB_CONNECTION=pgsql`) y el `--env=testing` provoca que las variables de phpunit.xml
no aíslen al proceso hijo. Las sondas #6 y #7 demuestran que **la invocación estándar
`php artisan test` sí aísla en SQLite** y que `--env=testing` es el desencadenante. La sonda #7
falló en la conexión (no escribió nada). Verificación posterior de PostgreSQL: 28 tablas,
`migrations` batch 1/24, todas las tablas de datos vacías (la BD nunca fue sembrada) → sin
pérdida de datos ni cambios de esquema atribuibles a esta etapa.

**Pruebas NO ejecutadas y motivo:**
- Ninguna prueba de la suite quedó sin ejecutar; las 72 pasan en SQLite.
- No se ejecutó ninguna prueba que requiera escritura/DDL real en PostgreSQL (prohibido).
- No se instaló ninguna dependencia ni se usó validador OpenAPI externo (p. ej. Spectral).

## 11. Resultado

Implementación completa y funcional de los dos endpoints conforme al contrato:
enrutado versionado `/api/v1`, validación estricta por whitelist, serialización conforme a
D-1..D-8, carga explícita de relaciones sin N+1 y cobertura de pruebas 16/16 (archivo) y
72/72 (suite) en SQLite. **Sin** migraciones, seeders, importadores ni escrituras en PostgreSQL;
**sin** cambios en modelos/migraciones/restricciones/índices; **sin** dependencias nuevas;
**sin** commit ni push. Estado de seguimiento: **IMPLEMENTADA — PENDIENTE DE REVISIÓN**.

## 12. Riesgos

- **R-1 (alto, preexistente del protocolo de pruebas).** El flag `--env=testing` en
  `php artisan test` anula el aislamiento de phpunit.xml y apunta a pgsql. Mitigación
  adoptada: usar la invocación estándar o forzar `-e DB_CONNECTION=sqlite`. Requiere
  revisión humana (posible ajuste de `phpunit.xml` a `force="true"`).
- **R-2 (medio).** `filter[available_as_antique]` acepta `true/false/1/0`; el contrato sólo
  documenta el caso `true`. Ver DP-1.
- **R-3 (bajo).** Los `links` de paginación se generan con la URL de la petición; en despliegue
  tras proxy inverso podrían requerir configuración de `trusted proxies`/host (fuera de alcance).
- **R-4 (bajo, preexistente).** PostgreSQL no indexa FKs automáticamente; los filtros por
  relaciones podrían degradar con volumen. No se crean índices en esta etapa (prohibido).

## 13. Pendientes

- Revisión arquitectónica humana de esta etapa (autoridad final).
- Decidir DP-1 (semántica booleana de `available_as_antique`) y DP-2 (política de aislamiento de pruebas).
- Corregir `PROJECT_CONTEXT.md` (discrepancia §7.2) y evaluar la nota de migración §7.3.
- Definir la política de índices secundarios para filtros de la API (etapa futura, no autorizada).
- Confirmación de la ubicación definitiva de reportes (§7.1).

## 14. Preguntas para ChatGPT

1. ¿Se aprueba DP-1 (aceptar `true/false/1/0` en `filter[available_as_antique]`)
   o se restringe a `true` estrictamente como describe el contrato?
2. ¿Debe ajustarse `phpunit.xml` a `force="true"` para neutralizar el riesgo R-1
   (flag `--env=testing` apuntando a pgsql), o basta con la convención de invocación?
3. ¿La convención de nombres y la estructura `{data, meta, links}` satisfacen el contrato
   `BlueprintListResponse`/`BlueprintResponse` sin observaciones?
4. ¿Se requiere documentar en un ADR el mapeo `include=components → blueprintComponentsAsParent`?

## 15. Próximo paso

Someter esta etapa a revisión arquitectónica. Tras aprobación: (a) cerrar DP-1/DP-2,
(b) corregir la documentación desactualizada (`PROJECT_CONTEXT.md`), y (c) planificar la
siguiente etapa (no autorizada) concerniente a índices de filtrado y/o endpoints de catálogos,
sin modificar el contrato vigente.
