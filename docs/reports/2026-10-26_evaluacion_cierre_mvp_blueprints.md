# Reporte Técnico — Evaluación de cierre del MVP de Blueprints

**Fecha:** 2026-10-26
**Autor:** Editor IA
**Estado:** EVALUACIÓN COMPLETADA (solo lectura; sin cambios de código, configuración, datos ni estructura)

---

## Resumen ejecutivo

El MVP de Blueprints está **funcionalmente completo**: los dos endpoints (`GET /api/v1/blueprints`, `GET /api/v1/blueprints/{id}`) están implementados, el contrato OpenAPI 3.0.3 vigente es válido y exhaustivo, el dataset del MVP está cargado en PostgreSQL y la suite de pruebas pasa íntegra (**90 pruebas / 645 aserciones, exit 0**).

No se identificó **ningún requisito funcional explícito** pendiente de implementación. Lo que impide declarar el MVP *terminado* son **dos inconsistencias documentales** (README raíz desactualizado y CHANGELOG con una línea obsoleta y sin los hitos de la Etapa 2/H-5) y **dos limitaciones de reproducibilidad** (el Excel fuente no está versionado y el servicio `app` de Docker no publica puerto HTTP, por lo que la API no es alcanzable sobre HTTP sin pasos adicionales).

**Estimación de avance: ~89 %** (método y limitaciones en §14).

La siguiente tarea recomendada es la **reconciliación documental del cierre** (README.md + CHANGELOG.md), por ser lo único que separa al MVP de un estado "terminado" verificable y por su bajo riesgo (no toca código ni datos).

---

## 1. Objetivo

Determinar qué falta para considerar **funcional y terminado** el MVP de Blueprints de Shop Titans API, a partir del estado real del repositorio, la documentación y las pruebas, sin asumir que los informes previos garantizan el estado actual.

## 2. Alcance

**Incluye:** inspección de solo lectura de documentos rectores, código de la API (rutas, controller, requests, resources, modelos), contrato OpenAPI, pruebas y seeders; verificación de disponibilidad del dataset mediante consulta de **solo lectura** a PostgreSQL; una única ejecución de la suite completa como evidencia.

**Excluye:** toda modificación de código, pruebas, configuración, migraciones o estructura/datos de PostgreSQL; migraciones, seeders, importadores, restauraciones y operaciones destructivas; instalación de dependencias; commits y push; manipulación de backups.

## 3. Estado inicial

- `PROJECT_CONTEXT.md` (2026-08-28, actualizado) declara implementados los seeders de catálogo, importadores y la API pública `/api/v1` ([PROJECT_CONTEXT.md#L27-L31](file:///home/draven/Documentos/shop-titans-api/PROJECT_CONTEXT.md#L27-L31)).
- `README.md` raíz mantiene una tabla de estado **obsoleta** ([README.md#L11-L21](file:///home/draven/Documentos/shop-titans-api/README.md#L11-L21)).
- `CHANGELOG.md` documenta H-1..H-4 como cambios, pero **no** registra la implementación de la API ni H-5, y conserva una línea "FK recursiva de componentes" ya inválida ([CHANGELOG.md#L16-L26](file:///home/draven/Documentos/shop-titans-api/CHANGELOG.md#L16-L26)).
- El filtro `filter[component]` **está presente** en el código actual ([BlueprintIndexRequest.php#L42](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Requests/BlueprintIndexRequest.php#L42), [BlueprintController.php#L146-L152](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php#L146-L152)).

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| — | — | **Ninguno.** Tarea de inspección (solo lectura). |

Artefactos escritos por esta tarea (autorizados por el encargo): este informe y el hito correspondiente en `AI_WORK_LOG.md`.

## 5. Evidencia recopilada (inspección)

### 5.1 Código de la API
- Rutas: `Route::prefix('v1')` con `/blueprints` y `/blueprints/{id}` ([api.php#L6-L9](file:///home/draven/Documentos/shop-titans-api/src/routes/api.php#L6-L9)). Sin middleware de autenticación ([bootstrap/app.php#L15-L21](file:///home/draven/Documentos/shop-titans-api/src/bootstrap/app.php#L15-L21)); render JSON para `api/*`.
- Controller: `index()` (paginación + filtros + orden) y `show()` (detalle) ([BlueprintController.php#L16-L59](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php#L16-L59)). `applyRelations()` con `category` por defecto ([#L67-L84](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php#L67-L84)); `applyFilter()` con 16 filtros incluido `component` ([#L110-L165](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php#L110-L165)); `applySort()` con desempate por `id` (H-2) ([#L212-L222](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php#L212-L222)).
- Requests: whitelist de filtros/orden/include y validación ([BlueprintIndexRequest.php#L16-L168](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Requests/BlueprintIndexRequest.php#L16-L168)); whitelist de `include` y mapa de eager loading ([BlueprintRequest.php#L15-L46](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Requests/BlueprintRequest.php#L15-L46)); validación del detalle ([BlueprintShowRequest.php#L14-L27](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Requests/BlueprintShowRequest.php#L14-L27)).
- Resource: tipos garantizados (enteros y floats), omisión de campos internos (D-7/D-8) ([BlueprintResource.php#L10-L125](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Resources/BlueprintResource.php#L10-L125)).

### 5.2 Contrato OpenAPI
- `openapi: 3.0.3`, versión `1.0.0`, servidor `/api/v1`, decisiones D-1..D-8 declaradas ([blueprints-v1.yaml#L1-L83](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L1-L83)).
- 2 operaciones con respuestas `200`, `404`, `422`, `500` ([#L153-L188](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L153-L188)).
- 41 parámetros; `FilterComponent` definido y referenciado ([#L124](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L124), [#L625-L640](file:///home/draven/Documentos/shop-titans-api/docs/openapi/blueprints-v1.yaml#L625-L640)).
- Comprobación de validez: parseo con PyYAML → "YAML OK, params count: 41; FilterComponent referenced: 1; defined: True".

### 5.3 Pruebas (una única ejecución completa)
```
docker compose exec -T app php artisan test
→ Tests: 90 passed (645 assertions)   Duration: 41.13s   exit=0
```
Cobertura relevante: API (23 pruebas, incluidos filtros por componente, duplicados, combinación `AND`, operadores inválidos, orden determinista, N+1), importadores y comandos, seeders (incluida idempotencia), rollback de migraciones y guarda de comandos destructivos.

### 5.4 Datos (consulta de SOLO LECTURA a PostgreSQL)
```
blueprints=1549  blueprint_components=2747  crafting_materials=186
blueprint_workers=3744  blueprint_resources=4135  blueprint_elemental_affinities=911
item_categories=40  workers=19  resources=12  elements=8  enchantments=73
spirit_affinities=38  spirits=1  qualities=5
```
Coincide con el estado documentado tras la ejecución controlada de `BlueprintMvpSeeder`.

### 5.5 Entorno
- `docker-compose.yml`: servicios `app` (build local, `working_dir=/var/www/html`, **sin `ports`**) y `postgres:17` (puerto 5432 publicado) ([docker-compose.yml](file:///home/draven/Documentos/shop-titans-api/docker-compose.yml)).
- `src/.env.example` con valores coherentes con el compose ([.env.example#L23-L28](file:///home/draven/Documentos/shop-titans-api/src/.env.example#L23-L28)).
- Excel fuente presente: `src/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx`, pero **excluido de Git** por [storage/app/.gitignore](file:///home/draven/Documentos/shop-titans-api/src/storage/app/.gitignore#L1-L4) (`*`).

## 6. Evaluación por áreas

| # | Área | Estado | Evidencia concreta |
|---|---|---|---|
| 1 | Consulta y estructura de respuesta | **Completo** | `index()`/`show()` devuelven `{data, meta, links}` / `{data}` ([BlueprintController.php#L30-L58](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php#L30-L58)); Resource con tipos garantizados; 23 pruebas de API en verde |
| 2 | Filtros disponibles y requisitos faltantes | **Completo** | 16 filtros implementados y validados ([BlueprintIndexRequest.php#L32-L50](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Requests/BlueprintIndexRequest.php#L32-L50)); `filter[component]` presente ([#L146-L152](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php#L146-L152)); no hay requisito explícito de más filtros |
| 3 | Inclusión de relaciones (`include`) | **Completo** | Whitelist de 11 relaciones + mapa de eager loading ([BlueprintRequest.php#L15-L46](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Requests/BlueprintRequest.php#L15-L46)); orden por `position` en afinidades; prueba de N+1 |
| 4 | Ordenamiento y paginación | **Completo** | `SORTABLE` + desempate por `id` (H-2) ([BlueprintIndexRequest.php#L16-L24](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Requests/BlueprintIndexRequest.php#L16-L24)); `per_page` 1..100 (D-5); pruebas de paginación y orden determinista |
| 5 | Validación de parámetros y errores | **Completo** | Validación de `page/per_page/sort/include/filter` y operadores por familia ([BlueprintIndexRequest.php#L70-L168](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Requests/BlueprintIndexRequest.php#L70-L168)); `422`/`404` en JSON, documentados y probados. *(El `500` es genérico y no se prueba explícitamente; no es un requisito.)* |
| 6 | Integridad y disponibilidad de datos | **Completo** | Conteos verificados en PostgreSQL (§5.4) coherentes con lo documentado; dataset del MVP presente |
| 7 | Documentación OpenAPI y ejemplos de uso | **Parcial** | OpenAPI completo y válido (§5.2), pero: (a) no hay `examples` a nivel de respuesta (solo a nivel de campo y un ejemplo textual de request); (b) la tabla de estado del repositorio está desactualizada ([README.md#L11-L21](file:///home/draven/Documentos/shop-titans-api/README.md#L11-L21)); (c) CHANGELOG obsoleto (§3) |
| 8 | Cobertura de pruebas y casos límite | **Completo** | 90 pruebas / 645 aserciones (§5.3); casos límite del filtro por componente (exacto, sin coincidencia, equivalencia `[eq]`, `422`, sin duplicados, combinación `AND`, mayúsculas/minúsculas) |
| 9 | Instalación y ejecución reproducible | **Parcial** | Docker Compose + `.env.example` + README del seeder existen; pero (a) el servicio `app` no publica puerto HTTP, por lo que la API no es alcanzable sobre HTTP tras `docker compose up`; (b) el Excel fuente exigido no está versionado, así que un clon limpio no regenera el dataset sin aportar el archivo |

## 7. Discrepancias

```text
DISCREPANCIA 1
Estado documentado: README.md "Estado": Seeders ⏳, BlueprintImporter ⏳, API ⏳, Tests ⏳ (README.md:11-21)
Estado real:        Seeders, importadores, API /api/v1 y pruebas IMPLEMENTADOS (PROJECT_CONTEXT.md:31; código; 90 pruebas en verde)
Evidencia:          src/routes/api.php; src/app/Http/**; src/database/seeders/BlueprintMvpSeeder.php; docker compose exec -T app php artisan test → 90 passed
Impacto:            Documentación engañosa sobre el estado del MVP
Propuesta:          Actualizar la tabla de estado del README raíz
Requiere aprobación: No (corrección documental)

DISCREPANCIA 2
Estado documentado: CHANGELOG.md "Validated": "FK recursiva de componentes" (CHANGELOG.md:26) y sin entradas de la Etapa 2 / H-5
Estado real:        blueprint_components.component_id → crafting_materials.id (no recursiva), migración 2026_10_14_000002; API y filter[component] implementados
Evidencia:          migrations/2026_10_14_000002_change_blueprint_components_component_id_foreign.php; docs/reports/2026-10-26_correccion_documental_fk_blueprint_components.md
Impacto:            Changelog inexacto/incompleto
Propuesta:          Corregir la línea obsoleta y registrar Etapa 2 y H-5
Requiere aprobación: No (corrección documental)
```

## 8. Decisiones

### Propuestas
- **DC-1:** reconciliar `README.md` (tabla de estado) y `CHANGELOG.md` (línea "FK recursiva" + entradas de Etapa 2 y H-5).
- **DC-2:** documentar y/o habilitar el acceso HTTP a la API en el entorno Docker (p. ej. publicar puerto para `php artisan serve`), solo si se considera requisito de uso.
- **DC-3:** documentar explícitamente el requisito del Excel fuente en el flujo de instalación (ya está en README; reforzar como limitación de reproducibilidad).

### Aprobadas
- Ninguna nueva. Se mantienen vigentes H-5, DP-D y DP-E (2026-10-26).

### Reemplazadas/Rechazadas
- Ninguna.

## 9. Bloqueos o riesgos

- **Sin bloqueos técnicos.** Nada impide que la API funcione; el dataset y las pruebas están en verde.
- **R-1 (documental):** README/CHANGELOG desactualizados → riesgo de decisiones basadas en estado erróneo.
- **R-2 (reproducibilidad):** el Excel fuente no versionado y la ausencia de puerto HTTP publican una brecha entre "el código funciona" y "un tercero lo ejecuta y consulta".
- **R-3 (rendimiento no medido):** `EXISTS` del filtro por componente sin `EXPLAIN ANALYZE` (DP-E); no se añaden índices.

## 10. Funcionalidades completadas y pendientes

**Completadas**
- Endpoints `GET /api/v1/blueprints` y `GET /api/v1/blueprints/{id}` (solo lectura, sin auth).
- 16 filtros combinables, `include` (11 relaciones), ordenamiento con desempate y paginación.
- Validación y errores JSON (`422`, `404`).
- Contrato OpenAPI 3.0.3 con decisiones D-1..D-8 y `filter[component]`.
- Seeders/importadores idempotentes y dataset del MVP cargado (verificado).
- Suite de pruebas completa en verde (90/645).

**Pendientes**
- Reconciliación documental (README, CHANGELOG).
- (Opcional) Ejemplos de respuesta completos en OpenAPI.
- (Opcional) Acceso HTTP reproducible a la API y refuerzo de la dependencia del Excel.

## 11. Lista priorizada de tareas restantes

1. **Reconciliar `README.md` y `CHANGELOG.md`** con el estado real (documental, riesgo nulo).
2. **(Opcional)** Publicar/documentar el acceso HTTP a la API en Docker Compose.
3. **(Opcional)** Añadir `examples` de respuesta completos en OpenAPI.
4. **(Opcional, requiere decisión)** Medir rendimiento del filtro por componente (`EXPLAIN ANALYZE`) antes de considerar índices.

## 12. Criterios de aceptación verificables para declarar el MVP terminado

1. `GET /api/v1/blueprints` y `GET /api/v1/blueprints/{id}` responden `200` con la estructura `{data, meta, links}` / `{data}` documentada. **Verificable:** pruebas de API (ya en verde).
2. La validación devuelve `422`/`404` en JSON para parámetros inválidos y recurso inexistente. **Verificable:** pruebas de API (ya en verde).
3. El contrato `docs/openapi/blueprints-v1.yaml` es válido y cubre los 2 endpoints, sus parámetros, respuestas y `filter[component]`. **Verificable:** parseo YAML + revisión (ya cumplido).
4. El dataset del MVP está cargado y es consultable (conteos > 0 en las tablas del MVP). **Verificable:** consulta de conteos (ya cumplida).
5. La suite de pruebas pasa íntegra. **Verificable:** `docker compose exec -T app php artisan test` (90/645, ya cumplido).
6. **La documentación de estado es consistente con el código** (`README.md` y `CHANGELOG.md` sin afirmaciones obsoletas). **Verificable:** revisión documental. **← Criterio aún no cumplido.**

## 13. Recomendación de la siguiente tarea de implementación

**Reconciliación documental del cierre del MVP**: actualizar la tabla "Estado" de [README.md](file:///home/draven/Documentos/shop-titans-api/README.md#L11-L21), corregir la línea obsoleta "FK recursiva de componentes" de [CHANGELOG.md](file:///home/draven/Documentos/shop-titans-api/CHANGELOG.md#L21-L26) y registrar en el CHANGELOG la implementación de la API (Etapa 2) y el filtro `filter[component]` (H-5).

**Justificación:** es el único criterio de aceptación (§12.6) no satisfecho; no requiere tocar código, pruebas ni datos (riesgo mínimo) y es lo que permite declarar el MVP *terminado* con documentación fiable. No se recomienda implementar funcionalidad nueva por costumbre: no hay requisito funcional explícito pendiente.

## 14. Estimación de avance

- **Método:** promedio simple y equitativo de las 9 áreas de §6, puntuando **Completo = 1** y **Parcial = 0.5**. Resultado: (7 × 1 + 2 × 0.5) / 9 = **8 / 9 ≈ 88.9 %**.
- **Limitaciones de la cifra:**
  1. Las ponderaciones son **subjetivas y equiparables**; no reflejan la criticidad real de cada área (p. ej., el núcleo funcional pesa más que la documentación).
  2. No mide calidad de implementación más allá de la evidencia disponible (pruebas, contrato y datos).
  3. **No** contabiliza como "pendiente" ninguna mejora opcional (acceso HTTP, ejemplos de respuesta, medición de rendimiento), por no ser requisito explícito.
  4. Se basa en el estado del repositorio y de la base en la fecha de la evaluación; el dataset podría cambiar.

## 15. Limitaciones de la evaluación

- Los puntos marcados como **Parcial** implican juicio sobre completitud documental/de entorno, no fallos funcionales.
- **No verificable sin modificar el entorno:** el comportamiento de la API servida por HTTP real (no vía pruebas ni `artisan serve`) no se comprobó, porque el servicio `app` no publica puerto y esta tarea prohíbe cambios de configuración. Se marca como limitación de reproducibilidad, no como defecto del código.
- No se ejecutaron `EXPLAIN ANALYZE`; el rendimiento del filtro por componente no está medido (DP-E).
- La verificación de datos fue de solo lectura (conteos), sin validar la semántica campo a campo.

## 16. Preguntas para ChatGPT

- ¿Se considera el acceso HTTP a la API un requisito del MVP o una mejora opcional?
- ¿Debe el `CHANGELOG.md` registrar la Etapa 2 (API) completa, o solo los elementos aprobados explícitamente?
- ¿Se desea medir el rendimiento del filtro por componente antes o después del cierre?

## 17. Próximo paso

1. Revisar y aprobar (o corregir) este informe y las decisiones DC-1..DC-3.
2. Si se aprueba, ejecutar la reconciliación documental (README + CHANGELOG) en una tarea separada.
3. **No** se inicia la implementación siguiente sin aprobación del usuario.

## 18. Confirmaciones de seguridad

- **Sin modificaciones de código, pruebas, configuración ni migraciones.**
- **PostgreSQL intacto:** solo se ejecutó una consulta de **solo lectura** (conteos). Sin escrituras, migraciones, seeders, importadores ni operaciones destructivas.
- **Backups intactos:** no se crearon, modificaron ni eliminaron.
- **Sin dependencias nuevas, sin commits, sin push.**
- Única ejecución de pruebas: la suite completa, con aislamiento SQLite `:memory:` (`phpunit.xml` sin cambios).
