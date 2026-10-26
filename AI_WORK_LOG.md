# AI Work Log

Bitácora compartida entre Usuario, ChatGPT y Editor IA. Las propuestas no son decisiones aprobadas hasta confirmación explícita.

## Estado actual
**Fase:** MVP Blueprints — Investigación de datos cerrada (D-1, D-3) y contrato de API cerrado (Etapa 1, D-1..D-8). **Etapa 2 (API pública `/api/v1` de Blueprints) IMPLEMENTADA Y REVISADA; observaciones H-1..H-4 CORREGIDAS (2026-10-23) y APROBADAS (H-2/H-3/H-4, 2026-10-24).**
**Estado:** 🟢 **PostgreSQL de desarrollo RECUPERADO Y VALIDADO (2026-10-23).** Se ejecutó la recuperación autorizada (5 fases, `exit=0` en todas) y todas las validaciones coinciden: `blueprints`=1.549, `blueprint_components`=2.747, `crafting_materials`=186, `blueprint_elemental_affinities`=911, `elements`=8, `enchantments`=73, `spirit_affinities`=38, `spirits`=1, `users`=0; sin huérfanos, duplicados ni pérdida de orden AZ. Respaldo previo en `backups/shoptitans_pre_recuperacion_20261023_203355.dump`. Antecedente: el esquema había sido **reconstruido** el 2026-10-23 ~03:28 UTC y las inserciones revertidas (causa más probable: suite de tests / `migrate:fresh|refresh` contra PostgreSQL por el defecto `--env=testing` → pgsql, ya corregido en `src/phpunit.xml` y con **aislamiento VERIFICADO**). Además: Etapa 2 (API pública `/api/v1`) **REVISADA INDEPENDIENTEMENTE el 2026-10-23 → APROBADA CON OBSERVACIONES**, y sus observaciones **H-1..H-4 CORREGIDAS el 2026-10-23** (H-1: `whereLike` sin SQL crudo; H-2: desempate por `id`; H-3/H-4: contrato OpenAPI alineado); correcciones DP-1/DP-2 y la regresión del `include` anidado confirmadas; `BlueprintApiTest` **19 passed (160 assertions)** en SQLite `:memory:`. **El 2026-10-26 queda CERRADO formalmente el incidente de datos de PostgreSQL** (ver hito más reciente); el respaldo se conserva como evidencia y la **restauración real del respaldo quedó PROBADA Y VALIDADA el 2026-10-26** (en entorno aislado; ver hito más reciente). **EP-3 (alternativa A: retirado el usuario automático de `DatabaseSeeder`) y EC-1 (`PROJECT_CONTEXT.md:31`) quedaron IMPLEMENTADAS el 2026-10-26** (ver hito más reciente). **Corregida también el 2026-10-26 la documentación de la FK `blueprint_components.component_id` → `crafting_materials.id` (`PROJECT_CONTEXT.md:54`).** **Reconciliado el 2026-10-26: los dos puntos de §17.5 (documentación del contrato en H-3/H-4 y desempate de H-2 por `id` ascendente) fueron APROBADOS por el usuario el 2026-10-24** (ver hito más reciente); no queda ninguna decisión H-1..H-4 pendiente de aprobación. **El 2026-10-26 se evaluó y luego se IMPLEMENTÓ (H-5) el filtro `filter[component]` en `GET /api/v1/blueprints`** (coincidencia exacta por nombre de material componente; DP-D: sin `material_type`; DP-E: sin índices) — ver hito más reciente. **El 2026-10-26 se evaluó también (solo lectura) el acceso HTTP en Docker** — **no existe hoy ningún proceso HTTP** (el servicio `app` es una shell CLI inerte por `command: tail -f /dev/null`; sin Nginx/Apache; el `9000/tcp` visible es solo `EXPOSE`) — y se recomendó la **Opción A** (`php artisan serve` ligado a `127.0.0.1`); **el 2026-10-26 se ejecutó y VALIDÓ la Opción A2** (contenedor temporal; listado/detalle/filtro → 200, filtro inválido → 422, `/up` → 200; puerto ligado solo a loopback; servidor detenido y entorno restaurado) — ver hito más reciente. **El 2026-10-26 se IMPLEMENTÓ (solo documentación) la mejora de OpenAPI del contrato**: ejemplos de respuesta (200 listado/detalle, 422, 404 y 500 de producción) y corrección de ejemplos inconsistentes en `docs/openapi/blueprints-v1.yaml` — ver hito más reciente.
**Siguiente tarea:** **No hay requisitos funcionales explícitos pendientes de implementación.** La reconciliación documental recomendada por la evaluación de cierre **se completó el 2026-10-26** (`README.md` + `CHANGELOG.md`), satisfaciendo el único criterio obligatorio que faltaba. **El acceso HTTP temporal (Opción A2) quedó IMPLEMENTADO Y VALIDADO el 2026-10-26** (procedimiento ad-hoc, sin cambios de archivos). Quedan únicamente **opcionales** (sujetos a decisión del usuario): persistir el acceso HTTP como A1 (`ports` en `docker-compose.yml` + documentación en `README.md`) y medición de rendimiento del filtro por componente (DP-E). **Ejecutada y VALIDADA el 2026-10-26 la prueba real de restauración de PostgreSQL** en entorno aislado (respaldo del MVP restaurado con `pg_restore` 17.10: **exit 0**, conteos exactos, 0 huérfanos, API **200** contra la base restaurada y destino de conexión verificado); esto **cierra la limitación vigente** de que la restauración nunca se había probado — ver hito más reciente. No se asume ninguna tarea sin aprobación. **La mejora de OpenAPI (ejemplos de respuesta) quedó EVALUADA (propuesta, 2026-10-26) y luego IMPLEMENTADA (2026-10-26)** —solo documentación del contrato— en `docs/openapi/blueprints-v1.yaml` (informe `docs/reports/2026-10-26_implementacion_mejora_openapi_blueprints.md`; base `docs/reports/2026-10-26_evaluacion_mejora_openapi_blueprints.md`) — ver hito más reciente.
- **Restricción vigente (no es una tarea):** conservar las copias de seguridad `backups/shoptitans_pre_blueprintmvpseeder_20261024_153512.dump` (432.554 B) y `backups/shoptitans_pre_recuperacion_20261023_203355.dump` (83.238 B) hasta que el usuario autorice **expresamente** su eliminación. No eliminar ni modificar respaldos.
- **Limitación CERRADA (2026-10-26):** la **restauración real de una copia de seguridad quedó PROBADA Y VALIDADA** en entorno aislado (respaldo del MVP: `pg_restore` exit 0, conteos exactos, 0 huérfanos, API 200 contra la base restaurada y destino de conexión verificado). La prueba **secundaria** del respaldo `pre_recuperacion` (estado degradado) permanece **no ejecutada** (opcional, DR-5).
- **Completadas (retiradas de la lista activa; historial conservado en los hitos siguientes y en los informes):** formalización y ejecución controlada de `BlueprintMvpSeeder`; decisión EP-1..EP-3 (con EP-3 implementada: retirada la creación automática de usuarios de prueba en `DatabaseSeeder`); corrección documental EC-1 (`PROJECT_CONTEXT.md:31`) y de la FK `blueprint_components.component_id` (`PROJECT_CONTEXT.md:54`); correcciones H-1..H-4 y su aprobación (H-2/H-3/H-4) el 2026-10-24; y su reconciliación documental.
- **Referencias (informes de hitos completados):** `docs/reports/2026-10-26_ejecucion_controlada_blueprint_mvp_seeder.md`, `docs/reports/2026-10-24_verificacion_previa_ejecucion_blueprint_mvp_seeder.md`, `docs/reports/2026-10-24_formalizacion_blueprint_mvp_seeder_punto_entrada.md`, `docs/reports/2026-10-24_implementacion_blueprint_mvp_seeder.md`, `docs/reports/2026-10-23_evaluacion_crafting_material_seeder.md`, `docs/reports/2026-10-26_evaluacion_ep3_user_factory_database_seeder.md`, `docs/reports/2026-10-26_ep3_ec1_database_seeder_context.md`, `docs/reports/2026-10-26_correccion_documental_fk_blueprint_components.md`, `docs/reports/2026-10-26_reconciliacion_estado_h2_h3_h4.md`, `docs/reports/2026-10-26_limpieza_siguiente_tarea_ai_work_log.md`, `docs/reports/2026-10-26_evaluacion_busqueda_blueprints_por_componentes.md`, `docs/reports/2026-10-26_implementacion_filtro_blueprints_por_componentes.md`, `docs/reports/2026-10-26_evaluacion_cierre_mvp_blueprints.md`, `docs/reports/2026-10-26_reconciliacion_documental_cierre_mvp_blueprints.md`, `docs/reports/2026-10-26_evaluacion_acceso_http_docker_blueprints.md`, `docs/reports/2026-10-26_validacion_acceso_http_docker_blueprints.md`, `docs/reports/2026-10-26_preparacion_prueba_restauracion_postgresql.md`, `docs/reports/2026-10-26_ejecucion_prueba_restauracion_postgresql.md`, `docs/reports/2026-10-26_evaluacion_mejora_openapi_blueprints.md`, `docs/reports/2026-10-26_implementacion_mejora_openapi_blueprints.md`, `docs/reports/2026-10-23_formalizacion_aislamiento_y_proteccion_postgresql.md`, `docs/reports/2026-10-23_revision_independiente_api_blueprints.md`, `docs/reports/2026-10-23_recuperacion_datos_postgresql.md`, `docs/reports/2026-10-23_preflight_recuperacion_postgresql.md`, `docs/reports/2026-10-23_verificacion_aislamiento_phpunit.md`, `docs/reports/2026-10-23_diagnostico_postgresql_vacio.md`, `docs/reports/2026-10-22_revision_correctiva_api_blueprints.md` y `docs/reports/2026-10-22_revision_final_etapa2_api_blueprints.md`.

## Último hito — 2026-10-26 (Implementación de la mejora de OpenAPI del MVP de Blueprints — IMPLEMENTADA)
**Cambio exclusivamente DOCUMENTAL. Sin modificar código PHP, pruebas, `.env`, Docker Compose, `phpunit.xml`, migraciones ni archivos de configuración; sin migraciones, seeders, importadores ni escrituras en PostgreSQL; sin dependencias nuevas; sin commit ni push. Base autorizada: `docs/reports/2026-10-26_evaluacion_mejora_openapi_blueprints.md`.**
- **Alcance:** incorporar ejemplos de respuesta y correcciones de ejemplos al contrato `docs/openapi/blueprints-v1.yaml` (único archivo de contrato), documentando **solo el formato de producción** (`APP_DEBUG=false`) para los errores, **sin** ejemplos de relaciones `include` y **sin** normalizar el mensaje del 404.
- **Cambios en `docs/openapi/blueprints-v1.yaml`:** (1) `example` en la respuesta **200 del listado** (`{data, meta, links}`) y **200 del detalle** (`{data}`); (2) `example` en `ValidationError` (**422**, capturado en runtime), `NotFound` (**404**, expone la clase del modelo) y `ServerError` (**500**, `{"message":"Server Error"}`); (3) corrección de `ValidationErrorResponse.message.example` (H-1: ya no usa «The given data was invalid.»); (4) `Blueprint.id`/`BlueprintId` `1`→`59` (H-2); (5) `craft_time_formatted` `15s`→`00:00:15` (H-3); (6) valores numéricos reales del recurso (`gold_value` 50, `value_per_second` 3.33, `merchant_xp` 135, `merchant_xp_per_second` 9) (H-4); (7) nota de que el host de `links` depende de la petición (ilustrado con `https://example.com`).
- **`CHANGELOG.md`:** dos entradas breves (*Added*: ejemplos de respuesta; *Fixed*: corrección de ejemplos inconsistentes y de `message.example`). Sin reescribir historial ni afirmar cambios funcionales.
- **Verificaciones ejecutadas (todas OK):** parseo YAML con PyYAML 6.0.3 (herramienta de sistema, sin añadir dependencias); `openapi`=`3.0.3`; `info.version`=`1.0.0`; 2 `paths`; 26 `schemas`; 3 respuestas reutilizables; **76/76 `$ref` resueltas**; validación **estructural** OpenAPI 3.0.3 (0 errores); ejemplos coincidentes (`craft_time_formatted`=`00:00:15`; `merchant_xp_per_second` entero `9`; 404 con `[App\Models\Blueprint]`; 422 real; 500 `Server Error`; `Blueprint.id`/`BlueprintId`=`59`). **No** se ejecutó un validador de esquema OpenAPI por librería (no instalado; añadirlo estaba fuera de alcance).
- **Discrepancia detectada (no causada por esta tarea):** `components.parameters` contiene **42** entradas (la evaluación previa y el criterio de aceptación decían 41). El **endpoint de listado referencia 41** parámetros y el de detalle 2. La tarea **no alteró** ese número (solo cambió un valor `example`). Documentada, sin modificar.
- **Limitaciones:** el cuerpo exacto del **404** y del **500** se deriva del framework Laravel 13.8 (`Handler`, `ModelNotFoundException`), **no** fue capturado en runtime; el host de `links` es ilustrativo.
- **Resultado:** mejora documental **IMPLEMENTADA**. Sin cambios de código, pruebas, configuración ni datos. **Sin commit ni push.**
- **Informe:** `docs/reports/2026-10-26_implementacion_mejora_openapi_blueprints.md` (base: `docs/reports/2026-10-26_evaluacion_mejora_openapi_blueprints.md`).

## Hito previo — 2026-10-26 (Evaluación de mejora de OpenAPI del MVP de Blueprints — PROPUESTA, sin implementar)
**Inspección de SOLO LECTURA. Sin editar ni crear archivos de implementación; sin modificar `docs/openapi/blueprints-v1.yaml` ni ningún archivo del proyecto; sin migraciones, seeders, importadores ni escrituras en PostgreSQL; sin dependencias nuevas; sin alterar `.env`, Docker Compose, pruebas ni configuración; sin commit ni push.**
- **Estado del contrato:** OpenAPI **3.0.3**, `info.version` 1.0.0; 2 paths, 26 esquemas, 41 parámetros, 3 respuestas reutilizables (`NotFound`, `ValidationError`, `ServerError`). Ya documenta esquemas, `example` a nivel de campo y de parámetro, y un ejemplo de solicitud textual. **No** documenta ningún **ejemplo a nivel de respuesta** (200 listado, 200 detalle, 422, 404, 500) ni un payload concreto de `errors`.
- **Archivos inspeccionados (solo lectura):** `docs/openapi/blueprints-v1.yaml`; `src/routes/api.php`; `src/app/Http/Controllers/Api/V1/BlueprintController.php`; `src/app/Http/Requests/{BlueprintRequest,BlueprintIndexRequest,BlueprintShowRequest}.php`; `src/app/Http/Resources/BlueprintResource.php`; `src/app/Models/Blueprint.php`; `src/tests/Feature/BlueprintApiTest.php`; `src/phpunit.xml`; `src/bootstrap/app.php`; `src/.env` (solo `APP_ENV`/`APP_DEBUG`); `src/composer.json`; `README.md`; `CHANGELOG.md`; `PROJECT_CONTEXT.md`; y código del framework en `src/vendor/laravel/framework/**` (ValidationException, Handler, ModelNotFoundException).
- **Hallazgos (severidad M/B):** **H-1** `ValidationErrorResponse.message.example` («The given data was invalid.») es **inexacto** en Laravel 13 (el mensaje superior es el **primer** error, `ValidationException::summarize()`); **H-2** el ejemplo `id=1` induce a error (el id 1 **no existe** en el dataset real: `/blueprints/1` → 404; existe `id=59`); **H-3** `craft_time_formatted.example=15s` es del *fixture*, el real es `00:00:15`; **H-4** decimales con parte fraccionaria cero se serializan sin `.0` (`merchant_xp_per_second: 9`); **H-5** el 404 real expone la clase del modelo («No query results for model [App\Models\Blueprint] <id>») y carece de ejemplo; **H-6** los `422` mezclan mensajes en español (personalizados) e inglés (reglas estándar, sin `src/lang/`); **H-7** con `APP_DEBUG=true` los errores incluyen claves extra.
- **Recomendación (alcance mínimo):** modificar **únicamente** `docs/openapi/blueprints-v1.yaml`, reutilizando esquemas y respuestas existentes: añadir `example` a las respuestas 200 (listado y detalle), 422, 404 y 500; corregir `ValidationErrorResponse.message.example`; opcionalmente, ejemplo de `include` y ajuste de `example` de `id`/`craft_time_formatted`. Sin nuevos endpoints/parámetros, sin migrar de versión OpenAPI y sin cambios de código (H-5 solo se documentaría).
- **Ejemplos recomendados con origen verificable:** `200` listado desde respuesta real capturada; `200` detalle y forma de `include` desde el recurso y las aserciones de `BlueprintApiTest`; `422` desde respuesta real (`filter[no_existe]`); `404`/`500` derivados del código del framework (**pendientes de confirmación en runtime**).
- **No verificado:** el cuerpo exacto del 404 y del 422 de reglas estándar (se derivan del framework); no se levantó servidor.
- **Sin cambios** en código, contrato, datos ni configuración. **Sin commit ni push.**
- **Informe:** `docs/reports/2026-10-26_evaluacion_mejora_openapi_blueprints.md`.

## Hito previo — 2026-10-26 (Ejecución de la prueba real de restauración de PostgreSQL — VALIDADA)
**Ejecución autorizada en entorno AISLADO y temporal. Sin modificar `docker-compose.yml`, Dockerfiles, `.env`, `phpunit.xml` ni archivos del proyecto; sin escribir en la base de desarrollo `shoptitans`; sin tocar el volumen `postgres_data`; sin modificar respaldos; sin migraciones, seeders ni importadores; sin `docker compose down` ni limpiezas globales; sin commit ni push.**
- **Respaldo probado:** `backups/shoptitans_pre_blueprintmvpseeder_20261024_153512.dump` (md5 `53170eada1bce8232cbdbddf51192d5e`, verificado y sin cambios). Restaurado con el **`pg_restore` 17.10 del contenedor `postgres:17`** (nunca el 18.6 del host).
- **Aislamiento:** red dedicada y temporal `shoptitans-restore-net` (sin puertos publicados); contenedor efímero `shoptitans-restore-pg` (`postgres:17`, **sin volúmenes**, sin puertos); base temporal `restore_test`; contenedor `shoptitans-restore-app` conectado **solo** a esa red, HTTP publicado **únicamente en `127.0.0.1:8001`**. En ningún momento se conectó a la red de Compose ni al PostgreSQL de desarrollo.
- **Restauración (`docker exec -i … pg_restore --no-owner --no-privileges`):** **exit 0** y **stderr vacío** (sin advertencias ni errores).
- **Conteos exactos (todos coinciden con la línea base del MVP):** `blueprints`=1549, `blueprint_components`=2747, `crafting_materials`=186, `blueprint_workers`=3744, `blueprint_resources`=4135, `blueprint_elemental_affinities`=911, `item_categories`=40, `workers`=19, `worker_levels`=50, `resources`=12, `elements`=8, `enchantments`=73, `spirit_affinities`=38, `spirits`=1, `qualities`=5, `users`=0, `migrations`=24.
- **Integridad y esquema:** 0 huérfanos respecto a `blueprints` y 0 respecto a `crafting_materials`; `tables`=28, `fks`=18, `checks`=7, `pks`=90.
- **API contra la base restaurada:** `GET /api/v1/blueprints?per_page=1` → **200** (`meta.total=1549`; primer `id=59 "Squire Sword"`); `GET /api/v1/blueprints/59` → **200**; `GET …?filter[component]=Iron Pine Cone` → **200** (`total=30`). `ss -ltn` → `LISTEN 127.0.0.1:8001` (solo loopback).
- **Destino de conexión verificado (no asumido):** `config('database.connections.pgsql')` → `host=shoptitans-restore-pg`, `database=restore_test`; `shoptitans-postgres` **no resoluble** desde el app temporal; **0** conexiones desde el app (`172.20.0.3`) hacia la base de desarrollo; prueba determinista `restore_test.tup_fetched` 19281→21413 (**+2132**).
- **Desviación (resuelta):** el primer arranque de la API dio **500** (`could not translate host name "postgres" … Database: shoptitans`) porque `php artisan serve` **filtra las variables de entorno** al lanzar su proceso hijo `php -S`, de modo que Laravel leía `DB_*` del `src/.env`. Se corrigió recreando el contenedor con el flag **`--no-reload`** → **200**. Nota de herramienta: `curl -g` requerido para URLs con `filter[...]`. Diagnóstico no destructivo.
- **Limpieza verificada:** contenedores temporales **eliminados**, red `shoptitans-restore-net` **eliminada** (exclusivamente esa), puerto **8001 libre**. Entorno de desarrollo **intacto** (`shoptitans-app` y `shoptitans-postgres` sin cambios; `shoptitans`.`blueprints`=**1549**); ambos respaldos conservan sus md5 originales.
- **Resultado:** **prueba EXITOSA y VALIDADA.** Queda **CERRADA la limitación vigente** de que la restauración real nunca se había probado; la restaurabilidad del respaldo de referencia del MVP está **demostrada**.
- **Sin cambios** en archivos, datos, contenedores persistentes ni respaldos. **Sin commit ni push.**
- **Informe:** `docs/reports/2026-10-26_ejecucion_prueba_restauracion_postgresql.md` (antecedente: `docs/reports/2026-10-26_preparacion_prueba_restauracion_postgresql.md`).

## Hito previo — 2026-10-26 (Preparación de la prueba de restauración de PostgreSQL — PROPUESTA, sin ejecutar)
**Inspección y planificación de SOLO LECTURA. Sin ejecutar restauración (`pg_restore` de datos); sin crear/borrar/modificar bases de datos ni contenedores; sin migraciones, seeders ni importadores; sin modificar respaldos, la base de desarrollo, `docker-compose.yml` ni archivos del proyecto; sin commit.**
- **Respaldos (existentes, legibles, formato `CUSTOM` v1.16-0, `pg_dump` 17.10):** `backups/shoptitans_pre_blueprintmvpseeder_20261024_153512.dump` (432.554 B; md5 `53170eada1bce8232cbdbddf51192d5e`; 229 TOC; **dataset completo del MVP**) y `backups/shoptitans_pre_recuperacion_20261023_203355.dump` (83.238 B; md5 `61f012053aba28dcda5c6162d9bda165`; 229 TOC; **estado degradado previo a la recuperación**). Estructura idéntica: 28 tablas (incl. `migrations`).
- **Versiones:** host `pg_restore` **18.6** (solo se usó para listar); servidor y `pg_restore` del contenedor = **17.10**. **Cliente de restauración recomendado: `pg_restore` 17.10 del contenedor `postgres:17`** (no un cliente mayor que el servidor).
- **Destino aislado recomendado:** contenedor **efímero** `shoptitans_restore_test` (`docker run --rm -d`, imagen `postgres:17` ya presente, **sin publicar puertos** ni compartir volumen), restaurando por `docker exec -i … pg_restore … --no-owner --no-privileges`. Alternativa simple: BD temporal en el contenedor existente (`createdb`/`dropdb`).
- **Recomendación:** probar **primero** `pre_blueprintmvpseeder` (dataset completo; es la limitación vigente del proyecto). Prueba secundaria opcional con `pre_recuperacion`.
- **Procedimiento exacto y validación** (conteos esperados 1549/2747/186/3744/4135/911/40/19/50/12/8/73/38/1/5/0, huérfanos=0, migraciones=24, tablas=28) preparados en el informe; **no ejecutados**.
- **Decisiones propuestas (pendientes de aprobación):** DR-1 probar primero el respaldo completo; DR-2 destino efímero aislado; DR-3 `pg_restore` 17.10 con `--no-owner --no-privileges`; DR-4 no tocar desarrollo/volumen/respaldos/compose; DR-5 prueba secundaria opcional.
- **No verificable sin restaurar:** el número de filas contenido en cada dump (el TOC no lo revela); se confirmará con los conteos durante la prueba.
- **Sin cambios** en datos, archivos, contenedores ni respaldos. **Sin commit ni push.**
- **Informe:** `docs/reports/2026-10-26_preparacion_prueba_restauracion_postgresql.md`.

## Hito previo — 2026-10-26 (Validación del acceso HTTP temporal a la API — Opción A2 — VALIDADA)
**Ejecución autorizada de la Opción A2 (contenedor temporal). Sin modificar `docker-compose.yml`, Dockerfiles, archivos de Laravel, `.env`, `phpunit.xml` ni otros archivos de implementación; sin migraciones, seeders, importadores, restauraciones ni escrituras en PostgreSQL; sin cambios en la publicación de PostgreSQL ni en respaldos; sin dependencias nuevas; sin reconstruir imágenes; sin commit ni push.**
- **Procedimiento:** servidor de desarrollo efímero con `docker compose run --rm -p 127.0.0.1:8000:8000 app php artisan serve --host=0.0.0.0 --port=8000`. El `command` del servicio `app` (`tail -f /dev/null`) **no se alteró**; el `9000/tcp` de `app` permaneció **sin publicar**.
- **Resultado HTTP (real, sin asumir):** `GET /api/v1/blueprints?per_page=1` → **200** `{data, meta, links}` (`meta.total=1549`; enlaces con host `127.0.0.1:8000`); `GET /api/v1/blueprints/1` → **404** (el ID 1 **no existe**); `GET /api/v1/blueprints/59` → **200** `{data}` (`Squire Sword`); `GET …?filter[component]=Iron Pine Cone` y `…?filter[component][eq]=Iron Pine Cone` → **200** con `meta.total=30` (ambas formas equivalentes); `…?filter[component]=Ruby` → **200** con `total=0`; `…?filter[no_existe]=x` → **422** con `{"message":"El filtro 'no_existe' no está permitido.", …}`; `GET /up` → **200** (HTML "Application up").
- **Red:** `ss -ltn` → `LISTEN 127.0.0.1:8000` (**solo loopback**, no `0.0.0.0:8000`). Contenedor efímero `shop-titans-api-app-run-2e1435aa3236` con mapeo `127.0.0.1:8000->8000/tcp`. `docker compose ps` idéntico: `shoptitans-app` `9000/tcp` (sin mapeo) y `shoptitans-postgres` `0.0.0.0:5432->5432/tcp` (**sin cambios**).
- **Cierre y estado final:** servidor detenido y contenedor efímero eliminado (requirió `docker stop` explícito: el cierre del cliente `run` no lo eliminó por sí solo). Verificado: **sin listeners en 8000**, y contenedores persistentes **intactos**. Entorno restaurado.
- **Limitaciones:** `artisan serve` es el servidor embebido de PHP (**monohilo, no apto para producción**); PostgreSQL sigue publicado en `0.0.0.0:5432` (fuera de alcance; no se modificó). Nota de herramienta (no de la API): `curl` requiere `-g` para URLs con `filter[...]`.
- **Sin cambios** en archivos de implementación, PostgreSQL ni respaldos. **Sin commit ni push.**
- **Informes:** `docs/reports/2026-10-26_validacion_acceso_http_docker_blueprints.md` (validación) y su antecedente `docs/reports/2026-10-26_evaluacion_acceso_http_docker_blueprints.md` (evaluación).

## Hito previo — 2026-10-26 (Evaluación de acceso HTTP a la API de Blueprints mediante Docker — PROPUESTA, sin implementar)
**Investigación de SOLO LECTURA. Sin modificar `docker-compose.yml`, Dockerfiles, configuración de Laravel, `.env`, `phpunit.xml` ni ningún archivo de implementación; sin reconstruir ni reiniciar contenedores; sin cambios de estado en Docker ni en el sistema; sin migraciones, seeders, importadores, restauraciones ni cambios en PostgreSQL; sin dependencias nuevas; sin tocar respaldos; sin commit ni push.**
- **Objetivo:** determinar la alternativa más sencilla, segura y coherente para acceder por HTTP a la API de Blueprints desde el equipo anfitrión.
- **Estado actual comprobado (evidencia por archivo):** **NO existe ningún proceso HTTP**. El servicio `app` (imagen `php:8.4-fpm`) **sobrescribe el arranque** con `command: tail -f /dev/null` ([docker-compose.yml#L13](file:///home/draven/Documentos/shop-titans-api/docker-compose.yml#L13)) → shell CLI inerte; `php-fpm` existe en la imagen pero **no está arrancado**. **No hay Nginx ni Apache** (solo `docker/php/Dockerfile`; `src/public/.htaccess` es inerte). El `9000/tcp` que muestra `docker compose ps` es **solo `EXPOSE`** (metadatos de la imagen), **no un puerto publicado** y no es alcanzable desde el host. `app`↔`postgres` se comunican por la **red por defecto de Compose** (`DB_HOST=postgres`, [.env.example#L23-L28](file:///home/draven/Documentos/shop-titans-api/src/.env.example#L23-L28)). `postgres` **sí** está publicado en todas las interfaces (`0.0.0.0:5432->5432/tcp`; `ss -ltn` → `*:5432`). Puertos **80, 8000, 8080 y 9000 libres** en el anfitrión (`ss -ltn`). Entorno **solo de desarrollo** (`APP_ENV=local`, `APP_DEBUG=true`, credenciales en claro; sin TLS ni servidor web).
- **Alternativas comparadas:** **A** (`php artisan serve`; A1 persistente con `ports` en Compose / A2 sin cambios de archivos vía `docker compose run --rm -p 127.0.0.1:8000:8000 app …`), **B** (Nginx + PHP-FPM; nuevo servicio + `docker/nginx/default.conf` + cambio del `command` de `app`) y **C** (FastCGI directo / proxy FastCGI del host / solo vía pruebas; descartadas).
- **Recomendación principal:** **Opción A** con enlace **solo a `127.0.0.1`**. **A2** (cero cambios de archivos) para uso inmediato; **A1** si se desea persistencia documentada. URL esperada: `http://127.0.0.1:8000/api/v1/blueprints`. **Opción B descartada** en esta fase por sobrearquitectura ([PROJECT_CONTEXT.md#L24-L25](file:///home/draven/Documentos/shop-titans-api/PROJECT_CONTEXT.md#L24-L25)) y ausencia de requisito de despliegue. **No se modifica** la publicación de PostgreSQL (5432) ni su red.
- **Archivos que cambiarían en la implementación:** A1 → únicamente `docker-compose.yml` (bloque `ports` en `app`) y, opcionalmente, `README.md`; A2 → **ninguno**. No se tocan Dockerfile, `.env`/`.env.example`, `bootstrap/app.php`, rutas, `phpunit.xml` ni PostgreSQL (los `links` de paginación usan el host de la petición, por lo que `APP_URL` no requiere cambios).
- **Decisiones propuestas (pendientes de aprobación):** **DH-1** adoptar Opción A ligada a `127.0.0.1`; **DH-2** modalidad A2 (inmediata) o A1 (persistente); **DH-3** no introducir Nginx/PHP-FPM en esta fase; **DH-4** no modificar la publicación ni la red de PostgreSQL.
- **Datos no verificables / bloqueos:** la **respuesta HTTP real** no se verificó (no se arrancó ningún servidor, prohibido por el alcance); queda como validación **post-implementación** (§13 del informe). Dentro del contenedor no hay `ps` (`executable file not found`); se suplió con el `command` declarado y `docker compose ps`.
- **Sin efectos en código, Docker, PostgreSQL ni respaldos.** **Sin commit ni push.**
- **Informe:** `docs/reports/2026-10-26_evaluacion_acceso_http_docker_blueprints.md`.

## Hito previo — 2026-10-26 (Reconciliación documental para el cierre del MVP — IMPLEMENTADA Y VALIDADA)
**Cambios documentales autorizados. Sin modificar código, pruebas, configuración, migraciones, datos ni estructura; sin operaciones destructivas; sin tocar respaldos; sin dependencias nuevas; sin commit ni push.**
- **`README.md`:** la tabla "Estado" deja de marcar como pendientes (⏳) `Seeders`, `BlueprintImporter`, `API` y `Tests`; ahora ✅ (evidencia: `BlueprintMvpSeeder`, comandos `import:*`, `src/routes/api.php` + `docs/openapi/blueprints-v1.yaml`, `src/tests/**`).
- **`CHANGELOG.md`:** se corrige la afirmación obsoleta *"FK recursiva de componentes"* → `blueprint_components.component_id` → `crafting_materials.id` (FK, `restrictOnDelete`; migración `2026_10_14_000002`); se añaden dos entradas en *Added*: API pública `/api/v1` de Blueprints (Etapa 2; implementada 2026-10-22, aprobada 2026-10-23) y filtro `filter[component]` (H-5, 2026-10-26).
- **Validaciones documentales:** `git --no-pager diff -- README.md CHANGELOG.md` y `git status --short`. Comprobado: README sin funcionalidades implementadas marcadas como pendientes; CHANGELOG sin la afirmación obsoleta de la FK; entradas nuevas respaldadas por informes y código; solo se escribieron `README.md`, `CHANGELOG.md`, el informe y este log. Los demás archivos con cambios en `git status` provienen de tareas anteriores y **no** fueron tocados.
- **Sin efectos en código ni en PostgreSQL.** **Sin commit ni push.**
- **Alcance:** se satisface el único criterio obligatorio pendiente (consistencia documental). **No** se abordan mejoras opcionales (DC-2 acceso HTTP; DC-3/DP-E) y **no** se declara el proyecto "completamente terminado".
- **Informe:** `docs/reports/2026-10-26_reconciliacion_documental_cierre_mvp_blueprints.md`.

## Hito previo — 2026-10-26 (Evaluación de cierre del MVP de Blueprints)
**Inspección de SOLO LECTURA. Sin modificar código, pruebas, configuración, migraciones, datos ni estructura de PostgreSQL; sin migraciones, seeders, importadores, restauraciones ni operaciones destructivas; sin dependencias nuevas; sin commit ni push.**
- **Resultado:** el MVP está **funcionalmente completo**. 2 endpoints implementados, contrato OpenAPI 3.0.3 válido (41 parámetros; `FilterComponent` definido y referenciado), dataset del MVP presente y suite completa en verde: `docker compose exec -T app php artisan test` → **90 passed (645 assertions)**, exit 0. Conteos verificados por consulta **de solo lectura**: `blueprints`=1549, `blueprint_components`=2747, `crafting_materials`=186, `blueprint_workers`=3744, `blueprint_resources`=4135, `blueprint_elemental_affinities`=911, `item_categories`=40, `workers`=19, `resources`=12, `elements`=8, `enchantments`=73, `spirit_affinities`=38, `spirits`=1, `qualities`=5.
- **Estado por área (9):** Completo 1–6 y 8; **Parcial** 7 (documentación: README/CHANGELOG desactualizados; sin ejemplos de respuesta a nivel de respuesta) y 9 (reproducibilidad: el servicio `app` no publica puerto HTTP y el Excel fuente no está versionado).
- **Discrepancias registradas:** (1) `README.md:11-21` marca Seeders/Importer/API/Tests como ⏳ estando implementados; (2) `CHANGELOG.md` mantiene "FK recursiva de componentes" (obsoleta) y no registra la Etapa 2 ni H-5.
- **Ningún requisito funcional explícito pendiente.** Bloqueos: ninguno.
- **Siguiente tarea RECOMENDADA (requiere aprobación):** reconciliación documental (`README.md` + `CHANGELOG.md`) para satisfacer el único criterio de aceptación no cumplido. Opcionales: acceso HTTP en Docker; ejemplos de respuesta en OpenAPI; medición de rendimiento (DP-E).
- **Estimación de avance:** ~89 % (7 Completo × 1 + 2 Parcial × 0.5) / 9; método y limitaciones en el informe.
- **Sin efectos en código ni en PostgreSQL.** **Sin commit ni push.**
- **Informe:** `docs/reports/2026-10-26_evaluacion_cierre_mvp_blueprints.md`.

## Hito previo — 2026-10-26 (Implementación del filtro `filter[component]` — IMPLEMENTADA Y VALIDADA)
**Cambios de código, pruebas y contrato autorizados por H-5. Sin migraciones, seeders, importadores, restauraciones ni comandos destructivos; sin escrituras en PostgreSQL (pruebas aisladas en SQLite `:memory:`); sin cambios en modelos, relaciones, migraciones, `phpunit.xml` ni respaldos; sin dependencias nuevas; sin commit ni push.**
- **Decisiones aplicadas:** H-5 (añadir `filter[component]`), DP-D (sin filtro por `material_type`; se documenta la ambigüedad por homónimos) y DP-E (sin índices nuevos).
- **Cambios:** `src/app/Http/Requests/BlueprintIndexRequest.php` (alta de `'component' => 'exact'` en `FILTERS`); `src/app/Http/Controllers/Api/V1/BlueprintController.php` (caso `component`: `whereHas('blueprintComponentsAsParent.component', fn ($r) => $r->where('name', $value))`, igualdad exacta, operador único `eq`); `docs/openapi/blueprints-v1.yaml` (parámetro `FilterComponent` definido y referenciado en `/blueprints`); `src/tests/Feature/BlueprintApiTest.php` (4 pruebas nuevas + helper `createComponent()`).
- **Comportamiento:** coincidencia **exacta** por `crafting_materials.name`, abarcando todos los `material_type`; admite `filter[component]=Ruby` y `filter[component][eq]=Ruby`; sin duplicados de blueprint (subconsulta `EXISTS`); comparable sensible a mayúsculas/minúsculas.
- **Validaciones (exit 0):** `docker compose exec -T app php artisan test --filter=BlueprintApiTest` → **23 passed (179 assertions)**; `php -l` en los 3 archivos PHP → sin errores; `vendor/bin/pint --test` → **PASS (3 files)**; parseo del contrato con PyYAML → **YAML OK, 41 parámetros, `FilterComponent` definido y referenciado**.
- **Sin efectos en PostgreSQL ni en respaldos.** **Sin commit ni push.**
- **Desviación operativa (sin impacto):** una edición paralela sobre el YAML sobrescribió la referencia `FilterComponent`; se detectó por el conteo de parámetros y se reaplicó secuencialmente.
- **Informe:** `docs/reports/2026-10-26_implementacion_filtro_blueprints_por_componentes.md`.

## Hito previo — 2026-10-26 (Evaluación: búsqueda de Blueprints por componentes)
**Investigación de SOLO LECTURA. Sin modificar código PHP, pruebas, contrato OpenAPI, modelos ni migraciones; sin ejecutar migraciones, seeders, importadores ni escrituras en PostgreSQL; sin nuevas dependencias; sin commit ni push. El filtro NO se implementó.**
- **Objetivo:** determinar la viabilidad de añadir un filtro por componentes a `GET /api/v1/blueprints`, evitando duplicar funcionalidades vigentes y manteniendo el alcance del MVP.
- **Comprobado (no asumido):** **NO existe** búsqueda ni filtro por componentes. `BlueprintIndexRequest::FILTERS` (`src/app/Http/Requests/BlueprintIndexRequest.php:32-49`) y el `switch` de `BlueprintController::applyFilter()` (`src/app/Http/Controllers/Api/V1/BlueprintController.php:110-157`) no contienen ningún caso de componente; el contrato OpenAPI no declara parámetro alguno (`docs/openapi/blueprints-v1.yaml:108-151`). El único mecanismo relacionado es `include=components`, que es **carga por eager loading** (`src/app/Http/Requests/BlueprintRequest.php:18,38`), no filtrado.
- **No es duplicado:** el filtro `resource` opera sobre `blueprintResources.resource` → tabla `resources`; los componentes operan sobre `blueprint_components.component` → tabla `crafting_materials` (entidades y semántica distintas).
- **Cadena verificada:** `blueprints.id` → `blueprint_components.blueprint_id` (FK, `cascadeOnDelete`) → `blueprint_components.component_id` (FK, `restrictOnDelete`, migración `2026_10_14_000002`) → `crafting_materials.id`. `material_type` ∈ {`blueprint`, `quest_component`, `sigil`, `enchantment`} (migración `2026_10_14_000001:18-23`). `crafting_materials.name` **no** es único (solo `source_key`).
- **Alternativas evaluadas:** (A) nombre exacto — `whereHas('blueprintComponentsAsParent.component', fn ($r) => $r->where('name', $value))`; (B) búsqueda parcial reutilizando `applyPartialMatch()`; (C) por identificador `crafting_materials.id` con `minimum: 1`.
- **Recomendación (Alternativa A):** filtro relacional `filter[component]=<nombre>` (equivalente a `filter[component][eq]`), solo operador `eq`, valor `string` no vacío; coherente con los filtros relacionales de catálogo vigentes. **Sin duplicados** de blueprint (subconsulta `EXISTS`). Riesgo documentado: homónimos de `name` entre tipos de material (mitigación opcional por `material_type`).
- **Rendimiento:** no se propone ningún índice; cualquier propuesta debe sustentarse primero en medición con `EXPLAIN (ANALYZE, BUFFERS)`.
- **Decisiones pendientes de aprobación humana:** H-5 (incorporar el filtro y su sintaxis `filter[component]`), DP-D (acotar por `material_type`) y DP-E (mantener la decisión de no crear índices sin evidencia).
- **Sin efectos en código, pruebas, OpenAPI ni PostgreSQL.** **Sin commit ni push.**
- **Informe:** `docs/reports/2026-10-26_evaluacion_busqueda_blueprints_por_componentes.md`.

## Hito previo — 2026-10-26 (Depuración de la sección «Siguiente tarea» — limpieza documental)
**Documentación únicamente; revisión de SOLO LECTURA de la bitácora y los informes. Sin modificar código PHP, pruebas, OpenAPI, modelos, migraciones ni `PROJECT_CONTEXT.md`; sin ejecutar migraciones, seeders, importaciones ni escrituras en PostgreSQL; sin eliminar ni modificar copias de seguridad; sin commit ni push.**
- **Revisión:** se contrastó la lista activa de «Siguiente tarea» con los hitos e informes de `docs/reports/`, confirmando el estado de cada actividad.
- **Retiradas de la lista activa (por estar completadas):** formalización y ejecución controlada de `BlueprintMvpSeeder`; decisión EP-1..EP-3 (con EP-3 implementada); corrección documental EC-1 y de la FK `blueprint_components.component_id`; correcciones H-1..H-4 y su aprobación (H-2/H-3/H-4) el 2026-10-24; y su reconciliación documental. El historial se conserva íntegro en los hitos siguientes y en los informes referenciados.
- **Conservado como restricción (no como tarea):** política de retención de copias de seguridad hasta autorización expresa del usuario.
- **Conservado como limitación vigente:** la restauración real de una copia de seguridad **todavía NO se ha probado**.
- **Estado resultante:** «Siguiente tarea» declara que **no existe una tarea técnica priorizada pendiente**; la siguiente prioridad queda **pendiente de decisión del usuario** (no se inventan tareas ni prioridades funcionales).
- **Prohibiciones respetadas:** sin cambios en código, configuración, base de datos ni copias de seguridad; **sin commit ni push**.
- **Informe:** `docs/reports/2026-10-26_limpieza_siguiente_tarea_ai_work_log.md`.

## Hito previo — 2026-10-26 (Reconciliación del estado de aprobación de H-2/H-3/H-4)
**Documentación únicamente; revisión de SOLO LECTURA. Sin modificar código PHP, pruebas ni el contrato OpenAPI; sin ejecutar migraciones, seeders, importadores ni comandos que escriban en PostgreSQL; sin commit ni push.**
- **Contexto aprobado:** el usuario aprobó el **2026-10-24** los dos puntos de §17.5 de `docs/reports/2026-10-23_revision_independiente_api_blueprints.md`: (1) documentación en el contrato OpenAPI de la variante `[eq]` (H-3) y de `minimum: 1` para `category_id` (H-4); (2) desempate determinista por **`id` ascendente** en `applySort()` (H-2).
- **Corrección documental:** se actualizaron en `AI_WORK_LOG.md` las afirmaciones que daban H-2/H-3/H-4 por «pendientes de aprobación humana» (encabezado «Fase»/«Estado», «Siguiente tarea» §4, «Pendientes vigentes» del hito de cierre del incidente y el hito del 2026-10-23), marcándolas como **APROBADAS el 2026-10-24**. El historial se conserva (las notas de pendiente previas se anotan con su resolución).
- **Addendum:** se añadió la sección **§18** al informe de revisión, registrando la aprobación de forma aditiva (sin reescribir §17).
- **`PROJECT_CONTEXT.md`:** no contenía referencias a H-2/H-3/H-4 → **sin cambios**.
- **Resultado:** **no quedan decisiones pendientes de aprobación sobre H-1..H-4**; no se dio por aprobada ninguna decisión adicional.
- **Sin efectos en código, pruebas, OpenAPI ni PostgreSQL.** **Sin commit ni push.**
- **Informe:** `docs/reports/2026-10-26_reconciliacion_estado_h2_h3_h4.md`.

## Hito previo — 2026-10-26 (Corrección documental de la FK `blueprint_components.component_id`)
**Documentación únicamente; verificación de SOLO LECTURA. Sin modificar código, modelos ni relaciones; sin ejecutar seeders, `db:seed`, importadores ni migraciones; sin escrituras en PostgreSQL; sin commit ni push.**
- **Verificación:** la FK real de `blueprint_components.component_id` referencia **`crafting_materials.id`** (`restrictOnDelete`), confirmado por tres fuentes: migración `2026_10_14_000002_change_blueprint_components_component_id_foreign.php` (`up()`), `BlueprintComponent::component()` → `belongsTo(CraftingMaterial::class, 'component_id')` y `CraftingMaterial::blueprintComponents()` → `hasMany(BlueprintComponent::class, 'component_id')`. La migración original `2026_08_28_100010` la creó apuntando a `blueprints.id` (estado ya obsoleto).
- **Corrección:** en `PROJECT_CONTEXT.md` (línea 54) se sustituyó «`blueprint_components.component_id` → `blueprints.id` como FK recursiva» por «`blueprint_components.component_id` → `crafting_materials.id` (FK, `restrictOnDelete`); antes apuntaba a `blueprints.id` (migración `2026_10_14_000002`)». No se modificó ningún otro apartado.
- **Sin efectos en PostgreSQL y sin cambios de código.** **Sin commit ni push.**
- **Informe:** `docs/reports/2026-10-26_correccion_documental_fk_blueprint_components.md`.

## Hito previo — 2026-10-26 (EP-3 + EC-1 implementadas)
**Cambios de código y documentación autorizados; sin ejecutar seeders, `db:seed`, importadores, migraciones ni restauraciones; sin escrituras en PostgreSQL; sin commit ni push.**
- **EP-3 (alternativa A, aprobada):** se retiró de `src/database/seeders/DatabaseSeeder.php` la llamada `User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com'])` y el import `use App\Models\User;` (sin uso). **No** se añadió lógica de creación de usuarios; se conservan los 9 seeders de catálogo y su orden.
- **EC-1:** en `PROJECT_CONTEXT.md` («Estado validado», línea 31) se sustituyó «Seeders, BlueprintImporter y API aún no implementados» por la descripción de capacidades implementadas y verificables (seeders de catálogos del MVP, `import:blueprints`, `import:blueprint-components`, API pública `/api/v1` de Blueprints).
- **Validaciones (exit 0):** `php -l database/seeders/DatabaseSeeder.php` → sin errores de sintaxis; `vendor/bin/pint --test database/seeders/DatabaseSeeder.php` → **PASS (1 file)**; `composer dump-autoload` → OK (7431 clases); diagnósticos IDE (intelephense) → **0** en el archivo modificado. No se ejecutaron tests: **ningún test cubre ni referencia `DatabaseSeeder`**.
- **Sin efectos en PostgreSQL:** no se ejecutaron seeders, `db:seed`, importadores, migraciones ni restauraciones; sin cambios de esquema, datos, configuración ni credenciales. **Sin commit ni push.**
- **Observación pendiente (fuera de alcance, NO corregida en ese hito):** `PROJECT_CONTEXT.md` (~línea 54) afirma que `blueprint_components.component_id → blueprints.id` es «FK recursiva», pero la migración `2026_10_14_000002` la cambió a `crafting_materials.id`. Requiere tarea separada con aprobación. → **RESUELTA el 2026-10-26** (ver hito más reciente).
- **Informe:** `docs/reports/2026-10-26_ep3_ec1_database_seeder_context.md`.

## Hito previo — 2026-10-26 (CIERRE DEL INCIDENTE DE DATOS DE PostgreSQL — CERRADO)
**Cierre exclusivamente documental. Sin migraciones, restauraciones, seeders ni escrituras adicionales en PostgreSQL; sin modificar el respaldo; sin reescribir ni eliminar antecedentes históricos; sin commit ni push.**

- **Base del cierre:** ejecución controlada de `Database\Seeders\BlueprintMvpSeeder` del **2026-10-26** (ver `docs/reports/2026-10-26_ejecucion_controlada_blueprint_mvp_seeder.md`). Se confirmó la **coincidencia informe ↔ bitácora**: los resultados del informe (§9–§10) y el hito de ejecución registrado aquí son consistentes.
- **Ejecución correcta:** `docker compose exec -T app php artisan db:seed --class="Database\\Seeders\\BlueprintMvpSeeder"` → **`exit 0`**; las 4 etapas (catálogos → `import:blueprints` → `CraftingMaterialSeeder` → `import:blueprint-components`) se ejecutaron.
- **Conteos antes = después (Δ=0):** `blueprints`=1549, `blueprint_components`=2747, `crafting_materials`=186, `blueprint_elemental_affinities`=911, `elements`=8, `enchantments`=73, `spirit_affinities`=38, `spirits`=1, `resources`=12, `qualities`=5, `item_categories`=40, `workers`=19, `worker_levels`=50, `users`=0. **Sin diferencias.**
- **Integridad:** **cero huérfanos y cero duplicados** en las 10 comprobaciones reportadas (componentes, materiales, afinidades, nombres de planos); restricciones FK/UNIQUE/CHECK presentes.
- **Respaldo conservado:** `backups/shoptitans_pre_blueprintmvpseeder_20261024_153512.dump` (432.554 B, md5 `53170eada1bce8232cbdbddf51192d5e`) — **no** sobrescrito ni eliminado. Respaldo previo del 23 (`shoptitans_pre_recuperacion_20261023_203355.dump`, 83.238 B, md5 `61f012053aba28dcda5c6162d9bda165`) también intacto.
- **Limitación declarada:** la **restauración real del respaldo sigue SIN probarse** (solo se verificó legibilidad estructural con `pg_restore --list`). La observabilidad de las etapas de importación está limitada (`Artisan::call()` captura su salida).
- **Evidencia conservada:** `docs/reports/2026-10-26_ejecucion_controlada_blueprint_mvp_seeder.md` (y addenda del informe de verificación previa) se mantienen como evidencia; **no** se reescriben antecedentes históricos.
- **Estado resultante:** incidente de pérdida/recuperación de datos de PostgreSQL **CERRADO**. Pendientes vigentes: EP-3 (tratamiento de `User::factory()` en `DatabaseSeeder`), EC-1 (discrepancia documental) y aprobación humana de H-1..H-4 (§17.5; **H-2/H-3/H-4 aprobadas el 2026-10-24**).

## Hito del mismo día — 2026-10-26 (Evaluación de EP-3: `User::factory()` en `DatabaseSeeder` — completada)
**Investigación de SOLO LECTURA. Sin modificar código, configuración, esquema ni datos; sin ejecutar `db:seed`, seeders, importadores, migraciones ni comandos destructivos; sin escrituras en PostgreSQL; sin commit ni push.**

- **Objetivo:** recomendar cómo debe comportarse `DatabaseSeeder` respecto a `User::factory()->create()`, según cuatro criterios: C1 idempotencia de `db:seed`, C2 no crear usuarios en BD real/desarrollo, C3 seguridad de credenciales, C4 reutilizar `UserFactory` atándolo a entorno.
- **Hallazgos (hechos verificados):** `users.email` es UNIQUE y `User::factory()->create()` **no** es idempotente (2.ª ejecución → `QueryException`); `UserFactory` usa password fijo `'password'`; `APP_ENV=local` es el entorno efectivo de desarrollo; **ningún** test depende de `DatabaseSeeder` ni del usuario de prueba (la suite usa `RefreshDatabase` + `$this->seed([...])` con seeders de catálogo); la API `v1` **no** tiene autenticación (rutas públicas, sin middleware de auth, `User` sin `HasApiTokens`); `users` en PostgreSQL = **0 filas**.
- **Recomendación (alternativa B):** crear el usuario de prueba **solo** en `app()->environment('testing')`, con patrón **idempotente** (`firstOrCreate`/guarda por `email`) y **reutilizando `UserFactory`**; así se cumplen C1–C4 (el password fijo queda confinado a SQLite `:memory:`). Alternativa A (retirar por completo) satisface C1–C3 pero no C4; alternativa C (statu quo) no se recomienda.
- **Estado:** evaluación completada; **decisión de implementación pendiente de aprobación humana**. `DatabaseSeeder.php` **no** se modificó.
- **Informe:** `docs/reports/2026-10-26_evaluacion_ep3_user_factory_database_seeder.md`.

## Hito previo — 2026-10-26 (Ejecución controlada de `BlueprintMvpSeeder` — VALIDADA)
**Ejecución autorizada: `db:seed --class=BlueprintMvpSeeder` sobre `shoptitans`. Sin migraciones, sin `db:seed` global, sin restauraciones; sin modificar código/esquema/respaldos; sin commit ni push.**

- **Base verificada:** `shoptitans` (usuario `shoptitans`) en `shoptitans-postgres`, PostgreSQL `17.10 (Debian 17.10-1.pgdg13+1)`.
- **Pre-verificaciones:** servicios `app`/`postgres` `Up 3 days`; conexión = `shoptitans`; conteos iniciales **idénticos a la línea base**; respaldo `shoptitans_pre_blueprintmvpseeder_20261024_153512.dump` disponible (432.554 B, md5 `53170eada1bce8232cbdbddf51192d5e`) y legible (`pg_restore --list` exit 0).
- **Comando:** `docker compose exec -T app php artisan db:seed --class="Database\\Seeders\\BlueprintMvpSeeder"` → **exit 0**. Las 4 etapas se ejecutaron (catálogos → `import:blueprints` → `CraftingMaterialSeeder` → `import:blueprint-components`). La salida de los comandos de importación no se imprime (capturada por `Artisan::call()`).
- **Conteos antes = después:** `blueprints`=1549, `blueprint_components`=2747, `crafting_materials`=186, `blueprint_elemental_affinities`=911, `elements`=8, `enchantments`=73, `spirit_affinities`=38, `spirits`=1, `resources`=12, `qualities`=5, `item_categories`=40, `workers`=19, `worker_levels`=50, `users`=0 (**Δ=0 en todas**).
- **Integridad referencial:** huérfanos y duplicados = **0** en las 10 comprobaciones (componentes, materiales, afinidades, nombres de planos); restricciones FK/UNIQUE/CHECK presentes. Distribución de `crafting_materials`: blueprint 99, quest_component 66, sigil 6, enchantment 15. Evidencia de ejecución de etapas: `blueprint_components.max(created_at)=2026-10-26 16:26:45 UTC`.
- **Conclusión:** comando finalizado correctamente **e** integridad funcional confirmada; re-aplicación **idempotente** sobre base poblada.
- **Limitaciones:** la salida de las etapas de importación no es visible; la **restauración** del respaldo **no** fue probada (solo `pg_restore --list`).
- **Informe:** `docs/reports/2026-10-26_ejecucion_controlada_blueprint_mvp_seeder.md`.

## Hito previo — 2026-10-26 (Vigencia del respaldo del día 24 — verificada)
**Verificación no destructiva; sin restaurar, sin escribir en PostgreSQL, sin crear/sobrescribir/eliminar respaldos; sin commit ni push.**

- **Pregunta:** ¿sigue vigente `backups/shoptitans_pre_blueprintmvpseeder_20261024_153512.dump` respecto a la base actual? **Respuesta: SÍ.**
- **Método:** volcado fresco `pg_dump -Fc` a `/tmp` (exit 0, **mismo tamaño 432.554 bytes**) y comparación contra el respaldo del 24 vía `pg_restore`:
  - **Datos** (`--data-only`): **38.739 líneas** c/u; `diff` → **solo 4 líneas distintas** = par de tokens aleatorios `\restrict`/`\unrestrict` de PG 17.10.
  - **Esquema** (`--schema-only`): **1.769 líneas** c/u; `diff` → solo los mismos tokens.
  - **TOC** (`--list`, sin la línea «Archive created at»): **idéntico**.
- **Interpretación:** datos y esquema idénticos (salvo tokens efímeros) ⇒ **la base no ha cambiado desde el 2026-10-24 15:35:24 UTC**; el respaldo del 24 representa fielmente el estado actual.
- **Acción:** al quedar garantizada la vigencia, **NO se creó respaldo nuevo** (instrucción condicional). `backups/` sin cambios (24: `53170eada1bce8232cbdbddf51192d5e`; 23: `61f012053aba28dcda5c6162d9bda165`). Temporales de `/tmp` eliminados.
- **Alcance:** garantía sobre **datos y esquema**; **no** se verificó una **restauración real** (requiere autorización y destino).
- **Informe:** `docs/reports/2026-10-24_verificacion_previa_ejecucion_blueprint_mvp_seeder.md` (Addendum 2).

## Hito previo — 2026-10-24 (Respaldo fresco previo a `BlueprintMvpSeeder` — autorizado)
**Acción autorizada: se creó un respaldo nuevo. Sin ejecutar el seeder, seeders, importadores, migraciones ni restauraciones; sin modificar datos ni estructura de PostgreSQL; sin sobrescribir ni eliminar respaldos; sin commit ni push.**

- **Respaldo nuevo:** `backups/shoptitans_pre_blueprintmvpseeder_20261024_153512.dump` — **432.554 bytes**, `file`: `PostgreSQL custom database dump - v1.16-0`, `md5sum 53170eada1bce8232cbdbddf51192d5e`, creado `2026-10-24 15:35:24 UTC`. Comando: `pg_dump -U "$POSTGRES_USER" -Fc -d "$POSTGRES_DB"` en `shoptitans-postgres` → **exit 0**.
- **Verificación de inspección:** `pg_restore --list` por *stdin* → **exit 0**; `dbname shoptitans`, `Format CUSTOM`, `Compression gzip`, **TOC Entries 229**, `Dump Version 1.16-0`, desde PG 17.10. **Advertencia:** esto **no** demuestra que la restauración funcione; la integridad de restauración real **no** está verificada.
- **Respaldo previo intacto:** `backups/shoptitans_pre_recuperacion_20261023_203355.dump` — 83.238 bytes, `md5sum 61f012053aba28dcda5c6162d9bda165` (sin cambios).
- **Conteos previos (solo lectura):** `blueprints`=1549, `blueprint_components`=2747, `crafting_materials`=186, `blueprint_elemental_affinities`=911, `elements`=8, `enchantments`=73, `spirit_affinities`=38, `spirits`=1, `resources`=12, `qualities`=5, `item_categories`=40, `workers`=19, `worker_levels`=50, `users`=0.
- **Incidencias:** ninguna. **Conclusión:** entorno **preparado** para solicitar autorización de ejecución de `BlueprintMvpSeeder`.
- **Informe:** `docs/reports/2026-10-24_verificacion_previa_ejecucion_blueprint_mvp_seeder.md` (Addendum).

## Hito previo — 2026-10-24 (Verificación previa a la primera ejecución de `BlueprintMvpSeeder`)
**Verificación de SOLO LECTURA; sin ejecutar el seeder, seeders individuales, importadores, migraciones ni restauraciones; sin crear/sobrescribir/eliminar respaldos; sin escrituras en PostgreSQL; sin cambios de código/configuración; sin commit ni push.**

- **Resultado: SIN BLOQUEOS para una primera ejecución controlada.**
- **A. Excel de entrada:** el trait `ResolvesBlueprintExcelPath` selecciona el archivo por defecto `storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx` (contenedor: `/var/www/html/storage/app/...`); **1.367.141 bytes**, mtime `2026-10-14 00:48:40 UTC`, `Microsoft Excel 2007+`; **único `.xlsx`** en `storage/app` (sin ambigüedad). No se abrió ni procesó el archivo.
- **B. Respaldo:** `backups/shoptitans_pre_recuperacion_20261023_203355.dump` presente (host; `backups/` no está montado en contenedores), **83.238 bytes**, `PostgreSQL custom database dump - v1.16-0`. Integridad **estructural** verificada con `pg_restore --list` por *stdin* → exit 0, `CUSTOM`/`gzip`, **229 TOC entries**, `dbname shoptitans`, desde PG 17.10. **Integridad de restauración real NO verificada** (requiere un restore). No se modificó el archivo.
- **C. Docker y configuración:** `docker compose ps` → `shoptitans-app` (servicio `app`) y `shoptitans-postgres` (servicio `postgres`, `postgres:17`, `0.0.0.0:5432->5432/tcp`), ambos **Up 36 hours**. Config efectiva de Laravel (sin credenciales): `app_env=local`, `default=pgsql`, `driver=pgsql`, `host=postgres`, `port=5432`, `database=shoptitans`, `username=shoptitans`; `class_exists("Database\Seeders\BlueprintMvpSeeder")` = **true**.
- **D. PostgreSQL (solo lectura):** `current_database()=shoptitans`, `current_user=shoptitans`, `version()=PostgreSQL 17.10 (Debian 17.10-1.pgdg13+1)`; `inet_server_port()` vacío (escucha por socket; puerto efectivo 5432). Conteos: `blueprints`=1549, `blueprint_components`=2747, `crafting_materials`=186, `blueprint_elemental_affinities`=911, `elements`=8, `enchantments`=73, `spirit_affinities`=38, `spirits`=1, `resources`=12, `qualities`=5, `item_categories`=40, `workers`=19, `worker_levels`=50, `users`=0.
- **Puntos de atención (no bloqueantes):** (1) la base **ya contiene el dataset completo del MVP** → la primera ejecución sería una **re-aplicación** sobre base poblada, no una instalación desde cero (idempotencia validada solo en SQLite, no contra PostgreSQL real); (2) `backups/` solo existe en el host; (3) integridad de restauración no comprobada.
- **Informe:** `docs/reports/2026-10-24_verificacion_previa_ejecucion_blueprint_mvp_seeder.md`.

## Hito previo — 2026-10-24 (Formalización de `BlueprintMvpSeeder` como punto de entrada oficial)
**Cambios exclusivamente documentales; sin cambios de código, esquema, migraciones ni dependencias; sin ejecutar el seeder, `db:seed` global, importadores ni escrituras en PostgreSQL; sin commit ni push.**

- **Decisión aprobada:** `Database\Seeders\BlueprintMvpSeeder` queda formalizado como el **procedimiento oficial** para preparar el conjunto de datos del MVP de Blueprints.
- **Comando oficial documentado:** `php artisan db:seed --class="Database\\Seeders\\BlueprintMvpSeeder"` (en Docker: `docker compose exec app php artisan db:seed --class="Database\\Seeders\\BlueprintMvpSeeder"`).
- **Cambios realizados:** nuevo apartado «Preparación de datos (MVP de Blueprints)» en `README.md` con el comando oficial, el orden de etapas (catálogos → `import:blueprints` → `CraftingMaterialSeeder` → `import:blueprint-components`), el requisito del Excel en `storage/app/` (`src/storage/app/`) y la aclaración de alcance (prepara el MVP de Blueprints, no la instalación completa de la aplicación). Se mantienen disponibles los seeders individuales para tareas específicas.
- **Sin cambios fuera de alcance:** `DatabaseSeeder.php` no se modificó (EP-1/EP-3 siguen pendientes); firmas y comportamiento de los comandos de importación intactos.
- **Validaciones (realizadas):** la clase resuelve por PSR-4 (`Database\Seeders\` → `database/seeders/` en `src/composer.json`) y `class_exists('Database\Seeders\BlueprintMvpSeeder')` → **clase OK** (sin tocar la base de datos). No se ejecutó el seeder ni `db:seed`.
- **Informe:** `docs/reports/2026-10-24_formalizacion_blueprint_mvp_seeder_punto_entrada.md`.

## Hito previo — 2026-10-24 (Implementación de `BlueprintMvpSeeder`)
**Implementación de código y pruebas; sin migraciones, `db:seed` global, importadores contra PostgreSQL real ni comandos destructivos; sin escrituras en PostgreSQL; sin cambios de esquema; sin commit ni push.** El aislamiento de PHPUnit (`force="true"` + `<server>` en `src/phpunit.xml`) y la guarda `DestructiveCommandGuard` se mantienen intactos.

- **Objetivo (EP-2):** seeder orquestador reproducible para una instalación nueva, en el orden de dependencias real: **catálogos → `import:blueprints` → `CraftingMaterialSeeder` → `import:blueprint-components`**.
- **Implementado:** `src/database/seeders/BlueprintMvpSeeder.php` (orquesta las 4 etapas; resuelve la ruta del Excel una vez y la propaga; detiene el proceso si una etapa falla). `runImportCommand()` usa `Artisan::call($command, ['file' => $filePath])`, compara contra `Command::SUCCESS` y lanza `RuntimeException` con el nombre del comando y el código de salida (propagación de errores). `CraftingMaterialSeeder` se ejecuta vía `$this->resolve(...)` + `filePath` + `__invoke()`, sin duplicar su lógica.
- **Refactor de apoyo:** se extrajo el trait `src/database/seeders/Concerns/ResolvesBlueprintExcelPath.php` (elimina la duplicación de resolución de la ruta del Excel); `CraftingMaterialSeeder` y `BlueprintCatalogSeeder` ahora usan el trait y resuelven el lector por contenedor (`app(BlueprintExcelReader::class)`), lo que permite mockearlo en pruebas; se eliminó el método muerto `resolveFilePath()`.
- **Decisiones:** EP-1 (no incorporar `CraftingMaterialSeeder` aislado a `DatabaseSeeder`) respetada — `DatabaseSeeder.php` **no** se modificó. EP-2 implementada. **EP-3 (tratamiento de `User::factory()`) NO resuelta** (fuera de alcance).
- **Pruebas:** `src/tests/Feature/BlueprintMvpSeederTest.php` (5 pruebas): orden de etapas, aplicación completa de todas las etapas, detención ante fallo de `import:blueprints` (solo corren los catálogos), detención ante fallo de `import:blueprint-components` (las etapas previas sí se aplican) e idempotencia en re-ejecución.
- **Validaciones (exit 0):** `php -l` OK (5 archivos); `php artisan test --filter=BlueprintMvpSeederTest` → **5 passed (31 assertions)**; `vendor/bin/pint --test` → **PASS (103 files)**; `php artisan test` → **86 passed (626 assertions)** en SQLite `:memory:` (sin regresiones: `CraftingMaterialSeederTest` 10/10, `BlueprintApiTest` 19/19). Diagnósticos IDE limpios en los archivos nuevos.
- **Limitación:** el orquestador **no** se ejecutó contra PostgreSQL real (prohibido en esta tarea); su comportamiento se infiere de las etapas subyacentes ya validadas.
- **Informe:** `docs/reports/2026-10-24_implementacion_blueprint_mvp_seeder.md`.

## Hito previo — 2026-10-23 (Evaluación de `CraftingMaterialSeeder` en `DatabaseSeeder`)
**Investigación de SOLO LECTURA. Sin modificar código ni configuración; sin ejecutar `db:seed`, seeders, importadores, migraciones ni comandos destructivos; sin escrituras en PostgreSQL; sin commit ni push.**

- **Pregunta:** ¿conviene incorporar `CraftingMaterialSeeder` a `DatabaseSeeder`?
- **Hechos verificados:** `DatabaseSeeder` ejecuta 9 seeders de catálogo (idempotentes) y luego `User::factory()->create()` —sin `CraftingMaterialSeeder`—; `CraftingMaterialSeeder` es **idempotente** (`updateOrCreate` sobre `source_key` UNIQUE) y escribe **solo** `crafting_materials`; depende de `blueprints` (por nombre) para las filas de tipo `blueprint`; `blueprint_components.component_id` → FK a `crafting_materials.id`; `users.email` es UNIQUE; los importadores (`import:blueprints`, `import:blueprint-components`) son comandos Artisan, **no** seeders; el `.xlsx` está presente y la imagen define `memory_limit=512M`.
- **Resultado:** incorporarlo **aislado** a `DatabaseSeeder` es **incoherente** para instalación nueva: sin `import:blueprints` previo faltarían las 99 filas `blueprint` (se emiten advertencias) y `blueprint_components` quedaría irresoluble; además `db:seed` repetido fallaría por UNIQUE(`email`). Idempotente y seguro de re-ejecutar, sí; suficiente por sí solo para el dataset del MVP, no.
- **Recomendación:** **rechazar** la incorporación aislada (opción B). Mantenerlo independiente con `--class` (A) o, si se desea instalación nueva reproducible, crear un **seeder orquestador del MVP** (C) que codifique el orden catálogos → `import:blueprints` → `CraftingMaterialSeeder` → `import:blueprint-components`, y resolver aparte el `User::factory()`.
- **Archivos inspeccionados:** `src/database/seeders/DatabaseSeeder.php`, `CraftingMaterialSeeder.php`, `BlueprintCatalogSeeder.php` y los 9 seeders de catálogo; `src/app/Imports/BlueprintExcelReader.php`; `src/app/Console/Commands/ImportBlueprints.php` e `ImportBlueprintComponents.php`; migraciones de `crafting_materials`, `blueprint_components` y `users`; `src/tests/Feature/CraftingMaterialSeederTest.php` y `BlueprintImportServiceTest.php`; `docker/php/Dockerfile`.
- **Discrepancia documental (EC-1):** `PROJECT_CONTEXT.md:31` dice que seeders/importador/API «aún no implementados» (ya lo están). Propuesta de corrección pendiente de aprobación.
- **Validaciones:** ninguna ejecución de comandos; solo inspección estática de lectura. La idempotencia se respalda en la prueba existente `CraftingMaterialSeederTest::test_seeder_is_idempotent`.
- **Informe:** `docs/reports/2026-10-23_evaluacion_crafting_material_seeder.md`.
- **Decisión pendiente de aprobación humana (EP-1..EP-3):** (1) no incorporarlo aislado; (2) adoptar opción C o mantener A; (3) tratamiento de `User::factory()` en `DatabaseSeeder`. **No se modificó `DatabaseSeeder`.**

## Hito previo — 2026-10-23 (Corrección de las observaciones H-1..H-4 de la API de Blueprints)
**Cambios mínimos de código y de contrato. Sin migraciones, seeders, importadores ni operaciones destructivas; sin escrituras en PostgreSQL; sin commit ni push.** El aislamiento de PHPUnit y la guarda `DestructiveCommandGuard` se mantienen intactos.

- **H-1 (BAJA) — Interpolación de columna en SQL crudo.** ✅ Corregida: `BlueprintController::applyPartialMatch()` ya no usa `whereRaw('LOWER('.$column.') LIKE ?')`; ahora usa `whereLike($column, '%'.$value.'%', caseSensitive: false)`. El nombre de columna lo cita el grammar y la insensibilidad a mayúsculas la resuelve el driver (`ilike` en PostgreSQL, `like` en SQLite). Semántica preservada.
- **H-2 (BAJA) — Ordenamiento sin desempate.** ✅ Corregida: `applySort()` añade `orderBy('id')` como criterio secundario cuando la columna primaria no es `id`, garantizando paginación determinista.
- **H-3 (INFORMATIVA) — Variante `[eq]` en filtros de texto.** ✅ Alineada en el contrato: `FilterName` y `FilterUnlockPrerequisite` documentan que `filter[campo][eq]` se admite y equivale a la búsqueda parcial (D-6). Sin cambio funcional.
- **H-4 (INFORMATIVA) — `category_id` más estricto.** ✅ Alineada en el contrato: `FilterCategoryId` declara `minimum: 1` (código sin cambios; consistente con `BlueprintId` y `filter[tier]`).

**Archivos modificados:** `src/app/Http/Controllers/Api/V1/BlueprintController.php`; `src/tests/Feature/BlueprintApiTest.php` (test de regresión `test_index_sort_adds_deterministic_secondary_key`); `docs/openapi/blueprints-v1.yaml`; `CHANGELOG.md`; este registro y `docs/reports/2026-10-23_revision_independiente_api_blueprints.md` (§17 Addendum).

**Validaciones (exit 0):** `php -l` OK (2 archivos); `vendor/bin/pint --test` **PASS**; `php artisan test --filter=BlueprintApiTest` → **19 passed (160 assertions)** en SQLite `:memory:`.

**Pendiente de aprobación humana:** (1) el cambio de documentación del contrato OpenAPI en H-3/H-4 (alineación al comportamiento implementado, no cambio de alcance); (2) el criterio de desempate de H-2 (`id` ascendente fijo). → **APROBADO por el usuario el 2026-10-24** (H-2 con `id` ascendente fijo; H-3/H-4 con la documentación del contrato OpenAPI). Se conserva como registro histórico.

## Hito previo — 2026-10-23 (Formalización del aislamiento de PHPUnit y protección de PostgreSQL)
**Decisiones aprobadas por el usuario e implementadas.** No se ejecutaron migraciones, seeders, importadores, `migrate:fresh`, `migrate:refresh` ni `db:wipe`; no se escribió ni modificó nada en PostgreSQL; sin commit ni push. El aislamiento de PHPUnit ya validado se mantiene **intacto**.

- **P-1 (Aprobada) — Conservar el bloque `<server>` de `src/phpunit.xml`.** ✅ Sin cambios: `phpunit.xml` NO se modificó. El bloque `<server>` (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `DB_URL=""`) se conserva como mecanismo de aislamiento en el modo `--env=testing`.
- **P-2 (Aprobada) — Documentar el aislamiento real.** ✅ Se documenta que el aislamiento depende de **`force="true"` en `<env>` + el bloque `<server>`**, y **no** del flag `--env=testing` por sí solo (el entorno heredado propaga `DB_CONNECTION=pgsql` vía `putenv`). Registrado en `PROJECT_CONTEXT.md`, en el informe de esta tarea y aquí.
- **P-3 (Aprobada) — Protección contra comandos destructivos.** ✅ Implementada como **listener del evento `CommandStarting`** (mecanismo menos invasivo: sin vendor hack, sin duplicar comandos): `src/app/Console/DestructiveCommandGuard.php`, registrado en `src/app/Providers/AppServiceProvider.php`. Bloquea `migrate:fresh`, `migrate:refresh` y `db:wipe` cuando la conexión efectiva (`--database` o la de por defecto) resuelve a **driver `pgsql` + base `shoptitans`**, arrojando `Symfony\Component\Console\Exception\RuntimeException` → exit code 1 antes de tocar la BD. En cualquier otro caso (p. ej. SQLite `:memory:` en pruebas) no interviene.

**Validaciones (exit 0):** `php -l` OK (3 archivos); `vendor/bin/pint --test` **PASS** (3 archivos); `php artisan test --filter=DestructiveCommandGuardTest` → **6 passed (14 assertions)**; `php artisan test --filter=BlueprintApiTest` → **18 passed (155 assertions)** (no interferencia: `RefreshDatabase` ejecuta `migrate:fresh` interno sobre SQLite y **no** se bloquea). Sondas de solo lectura: `app_env=local`, `default=pgsql`, `pgsql.database=shoptitans`, `listener_registered=true`, `guard_blocks_default=true`; despacho del evento (sin ejecutar comandos): `migrate` → ALLOWED; `migrate:fresh` / `migrate:refresh` / `db:wipe` → BLOCKED.

**Limitación:** la guarda **no** se validó invocando realmente `migrate:fresh`/`refresh`/`db:wipe` (prohibido en esta tarea); la intercepción se infiere del despacho real del evento + la resolución real de configuración, ambos verificados.

**Documentación:** `docs/reports/2026-10-23_formalizacion_aislamiento_y_proteccion_postgresql.md`. Sin abordar H-1..H-4 ni `CraftingMaterialSeeder` (siguen pendientes de decisión).

## Hito previo — 2026-10-23 (Revisión independiente de la API pública de Blueprints — Etapa 2)
**Revisión independiente de SOLO LECTURA. Sin migraciones, seeders, importadores ni escrituras en PostgreSQL; sin cambios de código/contrato; sin commit ni push.** Única ejecución: el subconjunto `BlueprintApiTest`, aislado en SQLite `:memory:`.

**Veredicto: APROBADA CON OBSERVACIONES** (4 observaciones no bloqueantes; ninguna afecta al contrato D-1..D-8).

**Correspondencia OpenAPI ↔ código: sin discrepancias de contrato.** Prefijo `/api/v1` y 2 endpoints coincidentes; `include` 11/11; filtros 16/16; `sort` 7/7; operadores `eq/gt/gte/lt/lte` (exactos solo `eq`); `per_page` def. 25 máx 100 (D-5); `available_as_antique` enum `true/false/1/0`; estructuras `{data,meta,links}` y `{data}`; códigos 200/404/422/500; D-7 (sin `raw_value`, ids de pivote ni timestamps) y D-8 (enteros `int`, decimales `float`) conformes.

**Correcciones confirmadas:** DP-1 (`available_as_antique` admite `true/false/1/0`; otros → 422), DP-2 (aislamiento por `src/phpunit.xml` con `force="true"` + `<server>`; verificado previamente en `docs/reports/2026-10-23_verificacion_aislamiento_phpunit.md`) y la regresión del `include` anidado (`include[workers][0]`/`include[]` → 422, no 500).

**Hallazgos (todos no bloqueantes):** H-1 BAJA — `applyPartialMatch()` interpola el nombre de columna en SQL crudo (no explotable hoy; patrón frágil). H-2 BAJA — `applySort()` sin desempate determinista (inestabilidad de paginación con `sort=name`; el contrato no lo exige). H-3 INFORMATIVA — `filter[name][eq]`/`filter[unlock_prerequisite][eq]` aceptados (equivalentes a la búsqueda parcial de D-6). H-4 INFORMATIVA — `category_id` exige `min:1` (más estricto que el contrato).

**Validación ejecutada:** `docker compose exec -T app php artisan test --filter=BlueprintApiTest` → **18 passed (155 assertions)**, exit 0.

**Documentación:** `docs/reports/2026-10-23_revision_independiente_api_blueprints.md`.

## Hito previo — 2026-10-23 (Recuperación de datos de PostgreSQL — ejecutada y validada)
**Recuperación AUTORIZADA y EJECUTADA. Escrituras en PostgreSQL limitadas a datos (seeders + importadores); sin cambios de esquema ni de código; sin commit ni push.**
**Estado: RECUPERACIÓN EJECUTADA Y VALIDADA — EXITOSA.**

**Respaldo previo:** `backups/shoptitans_pre_recuperacion_20261023_203355.dump` (CUSTOM, 83.238 bytes, 229 TOC entries; legible con `pg_restore --list`, exit 0).
**Guarda de conexión:** `current_database()/current_user = shoptitans/shoptitans`; Laravel `pgsql / shoptitans / shoptitans` → coincidente.

**Fases ejecutadas (todas `exit=0`):**
1. Catálogos en código: `ResourceSeeder` (12), `QualitySeeder` (5), `ItemCategorySeeder` (40), `WorkerSeeder` (19), `WorkerLevelSeeder` (50).
2. Catálogos del Excel: `ElementSeeder` (8), `EnchantmentSeeder` (73), `SpiritAffinitySeeder` (38), `SpiritSeeder` (1).
3. `import:blueprints` → 1.549 procesados/insertados, 0 omitidos.
4. `CraftingMaterialSeeder` → 186 (99 `blueprint` + 66 `quest_component` + 6 `sigil` + 15 `enchantment`), sin advertencias.
5. `import:blueprint-components` → 2.747 insertados, 0 omitidos, 0 referencias sin resolver, 0 errores.

**Validaciones (todas OK):** conteos exactos (§ informe); 11 comprobaciones de huérfanos = 0; 5 de duplicados = 0; AZ: 802 blueprints (109 con 2 elementos), `invalid_position`=0, orden `position` 1 = primer token = sin discrepancias; distribución por elemento Dark 177 · Earth 160 · Light 139 · Water 118 · Gold 103 · Air 101 · Fire 87 · All 26; sin blueprints de categoría `Enchantment`; `built_in_element` siempre tipo `Element`; `users`=0 (no se usó `db:seed` global).

**Documentación:** `docs/reports/2026-10-23_recuperacion_datos_postgresql.md`.

## Hito previo — 2026-10-23 (Preflight de recuperación de PostgreSQL — solo lectura)
**Solo inspección de código/Excel y consultas de lectura; sin escrituras en PostgreSQL; sin cambios de código/configuración; sin ejecutar seeders/importadores/migraciones; sin commit ni push.**
**Estado: PREPARACIÓN COMPLETADA — RECUPERACIÓN NO AUTORIZADA NI EJECUTADA.**

**Fuentes verificadas:** Excel en `src/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx` (1.367.141 bytes); hojas usadas `Blueprints`, `Quest Components`, `Full Moon Fusions`, `Enchantments` (presentes); columnas A..CS mapeadas. 12 seeders + 2 comandos (`import:blueprints`, `import:blueprint-components`) inventariados; dependencias e idempotencia (`updateOrCreate`/`firstOrCreate`, delete-then-create acotado al padre) confirmadas por inspección.

**Estado base (solo lectura):** todas las tablas de datos = **0**; solo `migrations` = **24**.

**Conteos derivados del Excel (coinciden con las referencias históricas):** blueprints no-`Enchantment` = **1.549**; filas útiles de `Enchantments` = **73**; tokens `AZ` = **8**; tokens `BA` = **38**; tokens `BC` = **1**; nombres de componente únicos = **186**; `Quest Components` = 66; `Sigils` = 6. (La hoja `Blueprints` tiene 77 filas tipo `Enchantment`, excluidas por diseño.)

**Procedimiento propuesto (5 fases, NO ejecutado):** (1) catálogos en código — `ResourceSeeder`, `QualitySeeder`, `ItemCategorySeeder`, `WorkerSeeder`, `WorkerLevelSeeder`; (2) catálogos del Excel — `ElementSeeder`, `EnchantmentSeeder`, `SpiritAffinitySeeder`, `SpiritSeeder`; (3) `import:blueprints`; (4) `CraftingMaterialSeeder`; (5) `import:blueprint-components`. Sembrar **por clase** (`--class`); **no** usar `db:seed` global.

**Riesgos clave:** `DatabaseSeeder` ejecuta `User::factory()->create()` (crea un usuario; y `email` es UNIQUE) y **no** siembra `crafting_materials` → evitar `db:seed` global; delete-then-create acotado al blueprint padre; reasignación de IDs de relación; dependencia de entorno/conexión (guarda previa `current_database()`/`current_user`); memoria PHP (imagen con `memory_limit=512M`); ausencia de respaldo (recomendado `pg_dump -Fc` antes de escribir, no generado).

**Validaciones definidas:** conteos (1.549/2.747/186/8/73/38/1/911), FK/huérfanos, duplicados, orden AZ (`position` 1 = primer token de `raw_value`; distribución por elemento), exclusiones (sin `Enchantment`; `built_in_element` tipo `Element`), y control de incidencias de importación (exit 0).

**Documentación:** `docs/reports/2026-10-23_preflight_recuperacion_postgresql.md`.

## Hito previo — 2026-10-23 (Verificación: aislamiento de PHPUnit respecto a PostgreSQL)
**Solo inspección y pruebas; sin escrituras en PostgreSQL; sin cambios de código/configuración; sin commit ni push. Conclusión: AISLAMIENTO VERIFICADO.**

**Método:** sonda transitoria de solo lectura (fuera del árbol del proyecto, en `/tmp` del host y del contenedor; eliminada al finalizar) que falla de forma segura *antes* de solicitar cualquier conexión, más comparación de contadores/secuencias de PostgreSQL antes y después.

**Evidencia:**
- Modo normal (`php artisan test --without-tty`): `config('database.default')=sqlite`, `driver=sqlite`, `database=:memory:`, `pdo_driver=sqlite`. Resultado de la suite: **74 passed (576 assertions)**.
- Modo `--env=testing` (`php artisan test --without-tty --env=testing`): idénticos valores SQLite `:memory:`. Resultado: **74 passed (576 assertions)**.
- Sin conexión PostgreSQL: `ConnectionEstablished` solo registró `sqlite => sqlite`; `DB::getConnections()` sin clave `pgsql`; `DB::listen` con solo `sqlite`.
- PostgreSQL intacto: `n_tup_ins/upd/del` y `last_value` de **todas** las secuencias idénticos antes/después (`diff` sin diferencias).
- El entorno del contenedor **no** define variables `DB_*` ni `APP_ENV`; un boot plano sin PHPUnit resuelve `pgsql` y lo propaga vía `putenv` (`getenv(DB_CONNECTION)=pgsql`).
- No existe `src/.env.testing` ni caché de configuración (`bootstrap/cache/` solo `.gitignore`).

**Mecanismo:** el aislamiento depende de `force="true"` en `<env>` **y** del bloque `<server>` de `phpunit.xml`. En `--env=testing` el proceso hereda `DB_CONNECTION=pgsql` (Collision omite `clearEnv()` cuando hay opción `env`) y `<server>` es lo que sobrescribe `$_SERVER`; como el repositorio de entorno de Laravel es inmutable, `.env` ya no puede reimponer `pgsql`. Nota: bajo `--env=testing` el `APP_ENV` resuelto es `local`, no `testing`.

**Alcance:** verifica el aislamiento **actual**; **no** confirma la causa del incidente, y **no** cubre comandos manuales (`migrate:fresh`/`refresh`/`db:wipe`).

**Documentación:** `docs/reports/2026-10-23_verificacion_aislamiento_phpunit.md`.

## Hito previo — 2026-10-23 (Diagnóstico: PostgreSQL de desarrollo vacío)
**Diagnóstico de SOLO LECTURA. Sin migraciones, seeders, importadores ni escrituras en PostgreSQL; sin recrear volúmenes/contenedores; sin commit ni push.**

**Hallazgo central:** los datos (1.549 blueprints, 2.747 componentes y catálogos documentados) **no están** en PostgreSQL. Todas las tablas de datos en `n_live_tup=0`. El **esquema fue reconstruido** el **2026-10-23 entre 03:28:10 y 03:33:36 UTC**.

**Evidencia decisiva:**
- `migrations` = **24 filas en un único batch 1** (el histórico documentado tenía batches separados: 2026-08-28, 2026-10-14/15, 2026-10-16 batch [4]) → reconstrucción del esquema.
- `pg_stat_user_tables`: `blueprints` `n_live=0/ins=58/del=0`; `crafting_materials` `ins=896/del=0`; `workers` `ins=244`; `item_categories` `ins=497`; tablas principales con **`n_tup_del=0`** (sin `DELETE`/`TRUNCATE`).
- `pg_sequences`: secuencias **avanzadas** con tablas vacías (no transaccionales).
- Archivos físicos: los **28** `relfilenode` (40966–41321, contiguos) con `mtime` **2026-10-23 03:28–03:33** (no 2026-08-28).
- Log PostgreSQL: arranque con **recuperación tras caída** el 03:18:13 (último checkpoint limpio 2026-10-16 22:57:47); **inserciones** con error de clave duplicada en `blueprint_workers` (03:28:12); **`drop table if exists "crafting_materials"`** fallando por FK (03:28:47) — forma exacta de `Schema::dropIfExists` (un `down()` de migración), no del *wipe* de `migrate:fresh`.
- Volumen `shop-titans-api_postgres_data` y contenedor `shoptitans-postgres` creados **2026-07-24**, **nunca recreados**.

**Hipótesis (ordenadas):** H-1 **confirmada** (reconstrucción de esquema + rollback el 2026-10-23 ~03:28 UTC); **H-1a probable-alta** (ejecutor = suite PHPUnit contra PostgreSQL por el defecto `--env=testing` → pgsql, con `RefreshDatabase`; `laravel.log` con tests el 2026-10-23 y `phpunit.xml` corregido 03:47 UTC); **H-1b probable-media** (`migrate:fresh`/`refresh` manual). **Rechazadas con evidencia:** pérdida de volumen, `DELETE`/`TRUNCATE`, otra BD/instancia, restauración desde dump.

**Recuperabilidad:** sin `dump`/backup ni PITR. `src/database/database.sqlite` es un residuo obsoleto (3 migraciones, sin `blueprints`). **Fuente válida:** Excel `Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx` (1.367.141 bytes) + importadores y seeders (idempotentes).

**Confirmación:** solo lecturas (`docker compose config/ps/inspect`, `docker logs`, `ls`/`stat`, `psql SELECT`, `grep`/lectura); sin escrituras SQL; sin `migrate`/`db:seed`/importadores; sin recrear volúmenes/contenedores; sin commit ni push.

**Documentación:** `docs/reports/2026-10-23_diagnostico_postgresql_vacio.md`.

**Estado:** DIAGNÓSTICO COMPLETADO — RECUPERACIÓN **NO** AUTORIZADA NI EJECUTADA. Se requiere autorización independiente (primero verificar aislamiento de pruebas; después, recarga desde el Excel).

## Hito anterior — 2026-10-22 (Revisión final de la Etapa 2: 4 objetivos)
Revisión final acotada de la Etapa 2. **Sin migraciones, seeders, importadores ni escrituras en PostgreSQL.**

**Objetivo 1 — aislamiento de pruebas (verificado, sin cambios en este tramo):** la suite completa pasa en modo estándar y en `env=testing` con la sonda `DB_HOST=probe.invalid.aislamiento` → conexión **SQLite `:memory:`** garantizada por `src/phpunit.xml` (`force="true"` + `<server>`), corregido en la revisión correctiva previa.

**Objetivo 2 — 500 con `include` anidado (CONFIRMADO y CORREGIDO):** `BlueprintRequest::includeList()` aplicaba `trim()` a todo elemento, provocando `TypeError` (`trim(): Argument #1 must be of type string, array given`) con `include=estructura anidada` → **500**. Corrección: se descartan los elementos no escalares antes de `trim()`; el rechazo queda a cargo de la regla `'include' => ['sometimes','string']` → **422**. Prueba de regresión `test_include_rejects_nested_or_array_structures` (3 assertions, PASS) en `BlueprintApiTest`.

**Objetivo 3 — descripción obsoleta del OpenAPI:** `info.description` de `docs/openapi/blueprints-v1.yaml` actualizada de "los endpoints aún NO están implementados" a **CONTRATO APROBADO (D-1..D-8) e IMPLEMENTADO (Etapa 2)**. Sin cambios de esquemas, parámetros, respuestas, ejemplos ni `x-approved-decisions`.

**Objetivo 4 — correspondencia OpenAPI ↔ código (verificada):** `INCLUDES` 11/11 = enum de `include`; `SORTABLE` 7/7 documentado en `Sort`; `FILTERS` 16/16 = parámetros `filter[...]`; `ANTIQUE_VALUES` = enum de `FilterAvailableAsAntique`; operadores `gt/gte/lt/lte` presentes en los 5 campos numéricos; `show` params `id,include`; respuestas listado `200/422/500` y detalle `200/404/422/500`; rutas y controlador coherentes.

**Validaciones (contenedor `app`):** `php -l` OK (4/4) · Pint PASS (98 files) · suite completa **74 passed (576 assertions)** en modo estándar **y** en `env=testing` (con sonda de `DB_HOST` inexistente) · OpenAPI parsea (3.0.3, 2 paths, 70/70 `$ref` resueltas). PostgreSQL verificada **solo lectura**: 28 tablas, `blueprints`=0, `migrations`=24 → sin cambios.

**Archivos:** `src/app/Http/Requests/BlueprintRequest.php`, `src/tests/Feature/BlueprintApiTest.php`, `docs/openapi/blueprints-v1.yaml`.

**Confirmación:** sin migraciones/seeders/importadores/escrituras en PostgreSQL; sin cambios de esquema/modelos/rutas/lógica funcional ni de decisiones aprobadas; sin dependencias; sin commit ni push; reportes históricos no movidos/renombrados/restaurados.

**Documentación:** `docs/reports/2026-10-22_revision_final_etapa2_api_blueprints.md`.

**Estado:** CORREGIDO Y VALIDADO EN PRUEBAS — PENDIENTE DE REVISIÓN INDEPENDIENTE. (Observación fuera de alcance: la base de desarrollo reporta `blueprints`=0.)

## Hito anterior — 2026-10-22 (Revisión correctiva Etapa 2: DP-1 y DP-2)
Corrección acotada de los dos puntos defectuosos detectados en la revisión. **Sin migraciones, seeders, importadores ni escrituras en PostgreSQL.**

**DP-1 — `filter[available_as_antique]`:** se sustituyó la regla `boolean` (que no admitía `"true"/"false"`) por `Rule::in(['true','false','1','0'])` en `BlueprintIndexRequest` (`const ANTIQUE_VALUES`); `BlueprintController::applyAntiqueFilter()` normaliza con `in_array((string) $value, ['true','1'], true)` → `whereNotNull`/`whereNull` de `available_as_antique_at`. `true`/`1` = disponibles; `false`/`0` = no disponibles; cualquier otro valor → `422`. Pruebas de los 4 valores (con verificación de que `false`/`0` filtran y no equivalen a "sin filtro") y de valores inválidos.

**DP-2 — aislamiento de pruebas:** causa raíz = con la opción `env=testing`, `TestCommand::clearEnv()` no limpia `.env` y las directivas `<env>` de PHPUnit no escriben en `$_SERVER`, que Laravel prioriza. Corrección persistente en `src/phpunit.xml`: `force="true"` + directivas `<server>` para `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `DB_URL=''` y (misma clase de fuga) `SESSION_DRIVER=array`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`, `BROADCAST_CONNECTION=null`. Demostrado con sonda de `DB_HOST` inexistente: la suite completa pasa en modo estándar y en `env=testing` → la conexión usada es SQLite.

**Hallazgo adicional (relacionado):** en `env=testing`, `.env` filtraba `SESSION_DRIVER=database` (anulaba el `<env>` de phpunit) y provocaba un `500` en `ExampleTest` con `no such table: sessions (Connection: sqlite)`; la conexión seguía siendo SQLite. Corregido con el mismo mecanismo.

**OpenAPI:** `components.parameters.FilterAvailableAsAntique` documenta `enum: ['true','false','1','0']` con la equivalencia semántica de cada valor y el `422`; `example: 'true'` (string, coherente con `type: string`). Sin cambios de endpoints, esquemas generales ni funcionalidad.

**Validaciones (contenedor `app`):** `php -l` OK (3/3) · Pint PASS (98 files) · `--filter=antique` 2 passed (15 assertions) · suite completa **73 passed (573 assertions)** en modo estándar y en `env=testing` (con sonda de `DB_HOST` inexistente) · `phpunit.xml` bien formado · OpenAPI parsea (3.0.3, 75/75 `$ref` resueltas). PostgreSQL verificada **solo lectura**: 28 tablas, `migrations`=24 batch 1, tablas de datos vacías → sin cambios.

**Archivos:** `src/phpunit.xml`, `src/app/Http/Requests/BlueprintIndexRequest.php`, `src/app/Http/Controllers/Api/V1/BlueprintController.php`, `src/tests/Feature/BlueprintApiTest.php`, `docs/openapi/blueprints-v1.yaml`. Sonda temporal `src/tests/Feature/TmpEnvProbeTest.php` creada y eliminada.

**Confirmación:** sin migraciones/seeders/importadores/escrituras en PostgreSQL; sin cambios de modelos/migraciones/índices/esquema/relaciones; sin dependencias; sin commit ni push; reportes históricos no movidos/renombrados/restaurados.

**Documentación:** `docs/reports/2026-10-22_revision_correctiva_api_blueprints.md`.

**Estado:** CORREGIDO Y VALIDADO EN PRUEBAS — PENDIENTE DE REVISIÓN INDEPENDIENTE.

## Hito anterior — 2026-10-22 (Etapa 2: implementación de la API pública de Blueprints)
Implementación de código y pruebas de los dos endpoints del contrato OpenAPI aprobado. **Sin ejecutar migraciones, seeders, importadores ni escrituras en PostgreSQL.**

**Endpoints implementados:** `GET /api/v1/blueprints` (paginado, filtros combinables con operadores `eq/gt/gte/lt/lte`, búsqueda parcial, orden por whitelist, `data`+`meta`+`links`, `category` por defecto, `include` con whitelist) y `GET /api/v1/blueprints/{id}` (`data`, 404 coherente).

**Archivos:** `src/routes/api.php` (nuevo) · `src/bootstrap/app.php` (grupo `api`) · `src/app/Http/Requests/{BlueprintRequest,BlueprintIndexRequest,BlueprintShowRequest}.php` (nuevos) · `src/app/Http/Controllers/Api/V1/BlueprintController.php` (nuevo) · `src/app/Http/Resources/BlueprintResource.php` (nuevo) · `src/tests/Feature/BlueprintApiTest.php` (nuevo).

**Decisiones aplicadas:** D-1/D-2 `snake_case`; D-3 `stats`/`energies`; D-4 derivados (`craft_time_formatted`, `value_per_second`, `merchant_xp_per_second`); D-5 `per_page` 25/100; D-6 operadores + búsqueda parcial; D-7 exclusión de `raw_value`, IDs pivote y timestamps; D-8 enteros/decimales como números JSON (`decimal:4` → `float`).

**Mapeos relevantes:** `include=components` → relación real `blueprintComponentsAsParent`; `elementalAffinities` cargada con `->orderBy('position')`; `category` siempre cargada; eager loading explícito verificado con test de conteo de consultas (sin N+1).

**Validaciones ejecutadas (contenedor `app`):** `php -l` OK (8/8) · Pint PASS (98 files) · `BlueprintApiTest` **16 passed (141 assertions)** · suite completa **72 passed (562 assertions)** sobre SQLite `:memory:`. Corrección aplicada: `valueRules('available_as_antique')` de `boolean` a `in:true,false,1,0`.

**Hallazgo de entorno (riesgo R-1):** la invocación estándar `php artisan test` **sí** aísla en SQLite `:memory:`; el flag `--env=testing` **rompe** el aislamiento y apunta a pgsql (demostrado con sonda de `DB_HOST` inexistente). Verificación posterior de PostgreSQL (solo lectura): 28 tablas, `migrations` batch 1/24, tablas de datos vacías → sin cambios atribuibles a esta etapa.

**Discrepancias documentadas:** `docs/reports/reprotes/` no existe (reportes en `docs/reports/`); `PROJECT_CONTEXT.md` declara una FK que el esquema real re-apuntó a `crafting_materials`; comentario desactualizado en la migración `2026_08_28_100010`.

**Confirmación:** sin `migrate`/`db:seed`/importadores/escrituras SQL; sin cambios de modelos/migraciones/restricciones/índices; sin dependencias; sin commit ni push; reportes no movidos.

**Documentación:** `docs/reports/2026-10-22_implementacion_api_blueprints.md`.

**Estado:** IMPLEMENTADA — PENDIENTE DE REVISIÓN.

## Hito anterior — 2026-10-16 (Revisión del contrato: `include` y operadores de filtro)
Revisión **exclusivamente documental**. **Sin implementar endpoints; sin tocar PostgreSQL.**

**Correcciones aplicadas al OpenAPI** (`docs/openapi/blueprints-v1.yaml`):
- **`include`:** de `string`+`enum` de valor único (incompatible con multi-valor) a `type: array`, `items.enum` (whitelist por elemento), `style: form`, `explode: false` → serialización `include=a,b,c`. El `example` del parámetro se corrigió de **cadena** a **lista** para que sea coherente con `type: array` (se conserva la serialización CSV).
- **Filtros (D-6):** se añadieron los parámetros **explícitos** `filter[campo][gt]`, `[gte]`, `[lt]`, `[lte]` para los cinco campos numéricos (`tier`, `gold_value`, `craft_time_seconds`, `merchant_xp`, `airship_power`); `filter[campo]` = `eq`. La sintaxis `filter[campo][op]` no es representable de forma genérica en OpenAPI 3.0.3 (`deepObject` solo cubre un nivel); por ello se enumeran.
- **Coherencia:** descripciones, parámetros y ejemplos revisados, con un ejemplo de solicitud exacto. Ejemplos existentes (`Dark`, `Iron`, `Blacksmith`) confirmados como valores reales; no se inventaron valores para `spirit_affinity`/`built_in_element`/`built_in_spirit`.

**Validaciones ejecutadas:** YAML parsea (`yaml.safe_load`); **75/75** `$ref` resueltas; 2 paths, 26 esquemas, **41** parámetros (40 en el listado, incl. 20 operadores); `include` = array/`explode:false` con `example` de tipo **lista** coherente; **0** parámetros `query` con `string`+`enum` de valor único; `x-pending-decisions` ausente, `x-approved-decisions` 8/8 `APROBADA`; sin "pendiente" en el spec. **No** se ejecutaron `php -l`/Pint/tests (sin código PHP nuevo).

**Reportes:** residen en `docs/reports/`; en esta revisión **no se movieron, restauraron ni eliminaron**.

**Confirmación:** sin rutas/controladores; sin cambios de modelos/migraciones/seeders/config; sin escrituras SQL; sin dependencias; sin commit ni push.

**Estado:** IMPLEMENTADA — PENDIENTE DE REVISIÓN.

## Hito anterior — 2026-10-16 (Cierre de Etapa 1: decisiones D-1..D-8 aprobadas)
Etapa **exclusivamente documental**. **Sin implementar endpoints; sin tocar PostgreSQL.**

**Decisiones aprobadas y reflejadas en el OpenAPI** (`x-approved-decisions`, se eliminó `x-pending-decisions`; `version: 1.0.0`):
- **D-1/D-2** claves JSON y nombres de `include` en `snake_case`.
- **D-3** estadísticas en `stats` y energías en `energies`.
- **D-4** se exponen `craft_time_formatted`, `value_per_second`, `merchant_xp_per_second`.
- **D-5** `per_page` por defecto 25, máximo 100.
- **D-6** operadores `filter[campo][op]` con `eq`/`gt`/`gte`/`lt`/`lte`; búsqueda parcial en campos de texto.
- **D-7** se excluyen `raw_value`, `id` de tablas pivote y timestamps internos.
- **D-8** tipos garantizados en JSON (enteros y decimales).

**Reportes:** se conserva la ubicación actual en `docs/reports/`; no se restauran, mueven ni eliminan.

**Entregables:**
- `docs/openapi/blueprints-v1.yaml` — OpenAPI 3.0.3 `1.0.0` (contrato aprobado).
- `docs/reports/2026-10-16_contrato_api_blueprints.md` — reporte de Etapa 1 con las ocho decisiones cerradas.

**Verificación contra código real:** 12 relaciones de `Blueprint`; `blueprint_components.component_id` apunta a **`crafting_materials`**; `position` de AZ con CHECK `IN (1,2)`.

**Confirmación:** sin rutas/controladores/Resources/Form Requests; sin cambios de modelos/migraciones/seeders/config; sin migraciones/seeders/importadores; sin escrituras SQL; sin dependencias; reportes no movidos; sin commit ni push.

**Estado:** IMPLEMENTADA — PENDIENTE DE REVISIÓN.

## Hito anterior — 2026-10-16 (auditoría de la API de Blueprints)
Auditoría **solo de lectura** (código y esquema). **Sin escribir código; sin tocar PostgreSQL.**

**Hallazgo central:** **no existe infraestructura API.** Sin `routes/api.php`, sin registro del grupo `api` en `bootstrap/app.php` (solo `web`/`commands`/`health`), sin controladores (solo la base `Controller`), sin API Resources/Form Requests/middleware propios, y **sin auth de API** (solo guard `web`; sin Sanctum/Passport). `shouldRenderJsonWhen($request->is('api/*'))` ya está configurado, pero no hay rutas `api`.

**Inventario:** 9 catálogos + `blueprints` (61 columnas) + 8 tablas 1:N. `Blueprint` expone 12 relaciones reales. Campos derivados redundantes: `craft_time_formatted`, `value_per_second`, `merchant_xp_per_second`.

**Riesgos/decisiones:** R-1 `DECIMAL` se serializa como string; R-2 N+1; R-3 sin índices secundarios (PostgreSQL no indexa FKs automáticamente); R-5 componentes vía `crafting_materials` (4 tipos). 8 decisiones pendientes (versionado, auth pública, tipos, librería de filtros, OpenAPI, índices).

**Contrato propuesto (NO implementado):** `GET /api/v1/blueprints` (paginado, filtros, `include`), `/blueprints/{id}`, `/blueprints/by-name/{name}`, y 8 endpoints de catálogos. Plan por etapas (0–7) pendiente de aprobación.

**Documentación:** `docs/reports/2026-10-16_auditoria_api_blueprints.md`.

**Confirmación:** sin controladores/rutas/Resources; sin cambios de modelos/migraciones/config; sin migraciones, seeders ni importadores; sin escrituras SQL; sin dependencias; sin commit ni push.

**Estado:** AUDITORÍA COMPLETADA — IMPLEMENTACIÓN PENDIENTE DE APROBACIÓN.

## Hito anterior — 2026-10-16 (ejecución D-3 en PostgreSQL)
Ejecución autorizada. **Escrituras realizadas únicamente vía `migrate`, seeders por clase e `import:blueprints`.**

**Migraciones (batch [4]):** `2026_10_16_000001..000005` → `Ran`. Creadas `enchantments`, `spirit_affinities`, `spirits`, `blueprint_elemental_affinities`; eliminada `blueprints.elemental_affinity_id`; re-apuntadas BA/BB/BC.

**Seeders (por clase, EXIT 0):** `ElementSeeder`, `EnchantmentSeeder`, `SpiritAffinitySeeder`, `SpiritSeeder`.

**Importación (`import:blueprints`, EXIT 0):** 1.549 procesadas · 0 insertados · 1.549 actualizados · 0 omitidos.

**Validación (solo lectura):**
- `blueprints`=1549 (IDs preservados: distinct=1549, min 1, max 1549) · `elements`=8 · `enchantments`=73 (Element 33/Spirit 40) · `spirit_affinities`=38 · `spirits`=1 · `blueprint_elemental_affinities`=911 · `blueprint_components`=2747 · `crafting_materials`=186.
- No nulos: BA=330 · BB=85 · BC=10; AZ en 802 blueprints (pos1=802, pos2=109 → 109 filas con 2 elementos).
- AZ por elemento: Dark177 · Earth160 · Light139 · Water118 · Gold103 · Air101 · Fire87 · All26 (Σ=911).
- BB: 30 elementos, 30/30 tipo `Element`. BC: `Mundra's Spirit`=10.
- Integridad: 0 huérfanos (AZ/BA/BB/BC), 0 pares duplicados, 0 posiciones duplicadas, 0 blueprints con >2 elementos.
- Orden AZ preservado (coincide `ordered_az` con `raw_value`; coexisten `Dark, Earth` y `Earth, Dark`).

**Documentación:** `docs/reports/2026-10-16_ejecucion_d3_postgresql.md`.

**Confirmación:** se ejecutaron `migrate`, 4 seeders por clase e `import:blueprints` (autorizados); sin `db:seed` global, sin reparaciones destructivas, sin commit ni push.

**Estado:** EJECUTADA Y VALIDADA.

## Hito anterior — 2026-10-16 (preflight previo a la ejecución en PostgreSQL — D-3)
Preflight de **solo lectura**. **Sin escrituras en PostgreSQL; sin cambios de código.**

**Contenedores:** `app` y `postgres` `Up`. **PostgreSQL 17.10**; `current_database()`=shoptitans.

**Migraciones:** las 5 de D-3 en `Pending`; históricas `Ran`.

**Estado del esquema:**
- No existen las tablas nuevas (`enchantments`, `spirit_affinities`, `spirits`, `blueprint_elemental_affinities`).
- `blueprints` tiene las 4 columnas de afinidad (`bigint`, nullable) con FKs a `elements(id) ON DELETE SET NULL`.
- Conteos no nulos en `blueprints`: **0/0/0/0** → la guarda de `2026_10_16_000005` **pasa**.
- Conteos: `blueprints`=1549 · `elements`=**0** · `crafting_materials`=186 · `blueprint_components`=2747.
- Excel presente en el contenedor: `storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx`.

**Hallazgos:** (1) `elements`=0 obliga a sembrar catálogos **antes** de reimportar; (2) el `db:seed` global ejecuta `User::factory()->create()`, por lo que se recomienda sembrar **por clase**; (3) artefactos untracked en raíz (`.xlsx`, `*.txt`, `*.diff`, `.zip`).

**Resultado:** **LISTO PARA EJECUCIÓN** (con las condiciones anteriores).

**Documentación:** `docs/reports/2026-10-16_preflight_ejecucion_d3.md`.

**Confirmación:** solo consultas de lectura (`git status`, `docker compose ps`, `migrate:status`, `psql SELECT`); sin `migrate`/`db:seed`/importadores; sin commit ni push.

**Estado:** LISTO PARA EJECUCIÓN — PENDIENTE DE AUTORIZACIÓN.

## Hito anterior — 2026-10-16 (implementación del modelado D-3 — elementos y afinidades AZ–BC)
Implementación en código de la **Alternativa C**. **Sin ejecutar migraciones, seeders, importadores ni escrituras en PostgreSQL.**

**Diseño aplicado:**
- **AZ `Elemental Affinity`:** tabla 1:N `blueprint_elemental_affinities` (`blueprint_id`, `element_id`, `position` 1–2 con CHECK PostgreSQL, `raw_value`); preserva el orden/prioridad original; UNIQUE `(blueprint_id, element_id)` y `(blueprint_id, position)`.
- **BA `Spirit Affinity`:** catálogo independiente `spirit_affinities`; `blueprints.spirit_affinity_id` re-apuntado.
- **BB `Built-In Element`:** catálogo `enchantments` (name UNIQUE, type + CHECK `Element|Spirit`); `blueprints.built_in_element_id` re-apuntado.
- **BC `Built-In Spirit`:** catálogo independiente `spirits`; `blueprints.built_in_spirit_id` re-apuntado; incluye `Mundra's Spirit`.

**Migraciones nuevas (NO ejecutadas):** `2026_10_16_000001` (enchantments), `..._000002` (spirit_affinities), `..._000003` (spirits), `..._000004` (blueprint_elemental_affinities), `..._000005` (cambio de FKs de `blueprints`, con guarda que aborta si las 4 columnas contienen datos).

**Modelos:** nuevos `Enchantment`, `SpiritAffinity`, `Spirit`, `BlueprintElementalAffinity`; modificados `Blueprint` (relaciones + `$fillable`) y `Element`.

**Importador:** `BlueprintRowData` (5 campos nuevos), `BlueprintExcelReader` (constantes AZ–BC, parser que preserva orden, lectores de catálogos, hoja `Enchantments`), `BlueprintImportService` (validación de no resueltos + `persistAffinities()` idempotente).

**Seeders:** `BlueprintCatalogSeeder` (base), `ElementSeeder`, `EnchantmentSeeder`, `SpiritAffinitySeeder`, `SpiritSeeder` (idempotentes); registrados en `DatabaseSeeder`.

**Tests:** 6 tests en `BlueprintImportServiceTest` (incluye tipo incorrecto de BB) + nuevo `BlueprintAffinityForeignKeysRollbackTest` (3 tests).

**Discrepancia resuelta:** el catálogo `enchantments` no existía; se creó con **aprobación explícita del usuario**.

**Validaciones (ejecutadas en el contenedor `app`, SQLite `:memory:`):** `php -l` OK (21/21 archivos) · Pint PASS (21 files) · `php artisan test` → **56 passed (421 assertions)**. Diagnósticos IDE sin errores. **NO se ejecutaron migraciones, seeders ni importadores en PostgreSQL.**

**Revisión correctiva (2026-10-16):** (1) `down()` de `000005` ahora aborta antes de tocar el esquema si hay datos en las columnas de afinidad o filas en `blueprint_elemental_affinities`; (2) `built_in_element_id` se valida en la aplicación para exigir un `enchantment` de tipo `Element`; (3) validaciones ejecutadas (ver arriba); (4) cobertura AZ–BC de seeders/importador revisada sin hallazgos.

**Documentación:** `docs/reports/2026-10-16_implementacion_modelado_elements_afinidades.md`.

**Confirmación:** sin migraciones, seeders, importadores ni escrituras en PostgreSQL; sin commit ni push.

**Estado:** IMPLEMENTADO — PENDIENTE DE REVISIÓN.

## Hito anterior — 2026-10-16 (análisis de modelado D-3 — elementos y afinidades AZ–BC)
Análisis de **solo lectura** del Excel v26.9.1. **Sin escrituras en PostgreSQL; sin cambios de código/esquema.**

**Hallazgo principal:** las 4 columnas (AZ `Elemental Affinity`, BA `Spirit Affinity`, BB `Built-In Element`, BC `Built-In Spirit`) pertenecen a **3–4 dominios distintos**, por lo que un único catálogo `elements` NO es suficiente:
- AZ: 31 valores no vacíos = 8 tokens base (Dark126, Earth114, Light103, Gold101, Water89, Fire68, Air66, All26) + **23 cadenas compuestas** (2 elementos; `"Earth, Dark"` vs `"Dark, Earth"` → orden no canónico; 15 pares canónicos). 824 `---`.
- BA: 38 nombres `<Ser> <Cualidad>` (Wolf Ferocity, Bear Vitality…); solo 3 coinciden con `Enchantments`. 1296 `---`.
- BB: 30 nombres `X Element` → **100 % coinciden con la hoja `Enchantments`** (33 `Element`). 1541 `---`.
- BC: 1 valor `Mundra's Spirit` (10 filas; ligado al worker Mundra, no está en `Enchantments`). 1616 `---`.

**Discrepancia:** `DATA_MODEL_ANALYSIS.md` §3.4 documentaba `elements = {Fire, Water, Earth, Air, Light, Dark, Spirit, Soul, None}`; los datos **refutan** esa hipótesis (no hay `Spirit`/`Soul`/`None` en AZ–BC).

**Recomendación (Alternativa C):** `elements` (tokens base, pendiente aclarar `Gold`/`All`); nueva tabla 1:N `blueprint_elemental_affinities` para AZ (soporta compuestos); nuevo catálogo `spirit_affinities` para BA; **reutilizar `enchantments`** para BB; nuevo catálogo `spirits` para BC. Alternativas A/B/D comparadas en el reporte.

**Documentación:** `docs/reports/2026-10-16_analisis_modelado_elements_afinidades.md`.

**Confirmación:** solo lecturas del Excel con Python estándar (el daemon de Docker no estaba disponible); sin migraciones, seeders, importadores ni escrituras en PostgreSQL; sin commit ni push.

**Estado:** PROPUESTA — PENDIENTE DE APROBACIÓN.

## Hito anterior — 2026-10-15 (cierre de hallazgos H-1, H-2, H-3)
Implementadas las tres mejoras aprobadas de la revisión final del importador. **Sin escrituras en PostgreSQL.**

- **H-1 (código):** en `BlueprintImportService::importComponents()` la carga de catálogos (`loadComponentCatalogs()`) se envuelve en `try/catch` y devuelve `ComponentImportResult::failure(...)` ante error. Se mantiene fuera de la transacción (semántica de rollback intacta).
- **H-2 (documentación):** actualizadas §11.3, §12 y §14 de `docs/reports/2026-10-15_comando_import_blueprint_components.md` (de "pendiente" a "ejecutada": 1.549/2.747/0/0/0). Se conserva la distinción tests vs. ejecución real.
- **H-3 (tests):** aserciones de que la tabla de métricas se muestra con `success = false`; nuevo test `test_command_fails_when_catalog_loading_fails` (SQLite `:memory:`).
- **H-4:** sin cambios (fallo e incidencias → `FAILURE` = 1).

**Validaciones:** `php -l` OK · Pint PASS (2 files) · `ImportBlueprintComponentsCommandTest` 7 passed (31 assertions) · relacionados (`BlueprintComponentImportTest`, `BlueprintImportServiceTest`) 22 passed (119 assertions).

**Confirmación:** sin importaciones reales, seeders ni migraciones; sin cambios de esquema/arquitectura; sin commit ni push.

**Documentación:** `docs/reports/2026-10-15_cierre_hallazgos_import_blueprint_components.md`.

**Estado:** IMPLEMENTADA — PENDIENTE DE REVISIÓN.

## Hito anterior — 2026-10-15 (revisión final de `import:blueprint-components`)
Revisión estática + tests existentes del comando. **Sin cambios de código y sin escrituras en PostgreSQL.**

**Resultado por requisito:**
1. Éxito solo sin errores/sin resolver/omitidos → CUMPLE (`!success` o `hasIncidents()` → FAILURE).
2. Errores visibles sin ocultar métricas → CUMPLE (la tabla se muestra siempre).
3. Excepciones/fallos del servicio no reportados como éxito → CUMPLE (el servicio convierte `Throwable` en `failure`).
4. Cobertura de tests y coincidencia con el contrato → CUMPLE.
5. No se confundió la importación real con los tests → CUMPLE (no ejecutada).

**Verificación ejecutada:** `php artisan test tests/Feature/ImportBlueprintComponentsCommandTest.php` → **6 passed (21 assertions)** (SQLite `:memory:`).

**Hallazgos (propuestos, no implementados):** H-1 `loadComponentCatalogs()` fuera del `try`; H-2 reporte previo desactualizado (§11.3/§12/§14 dicen "pendiente" pero la ejecución real ya se hizo: 1549/2747/0/0/0); H-3 huecos menores de cobertura; H-4 código de salida único para fallo e incidencias.

**Confirmación:** No se modificó PostgreSQL; no se ejecutaron seeders, importaciones ni migraciones; no se cambió código ni tests; sin commit ni push.

**Documentación:** `docs/reports/2026-10-15_revision_final_import_blueprint_components.md`.

**Estado:** REVISIÓN COMPLETADA — SIN DEFECTOS BLOQUEANTES.

## Hito anterior — 2026-10-15 (límite de memoria PHP permanente, propuesta B)
Se implementó la solución permanente al OOM de PhpSpreadsheet. **Sin escrituras en PostgreSQL.**

**Cambio:** en `docker/php/Dockerfile` se añadió:
```dockerfile
RUN echo 'memory_limit=512M' > /usr/local/etc/php/conf.d/zz-memory.ini
```

**Comandos ejecutados:**
- `docker compose build app` → `Image shop-titans-api-app Built` (exit 0).
- `docker compose up -d --no-deps app` → `Container shoptitans-app Recreated` y `Started` (solo `app`; `postgres` intacto).
- `docker compose exec app php -r 'echo ini_get("memory_limit"), PHP_EOL;'` → **`512M`**.

**Verificaciones extra:** `zz-memory.ini` existe con `memory_limit=512M` y aparece en `php --ini` como fichero `.ini` adicional.

**Git:** los cambios preexistentes se preservaron; el único delta nuevo en `Dockerfile` es la línea de memoria. Sin commit ni push.

**Documentación:** `docs/reports/2026-10-15_limite_memoria_php_permanente.md`.

**Estado:** IMPLEMENTADO Y VALIDADO. Pendiente: revisión humana final del comando.

## Hito anterior — 2026-10-15 (cierre de la importación de componentes de blueprints)
Se documentó el resultado real de la importación D-1. **Sin escrituras en PostgreSQL** (solo una consulta de solo lectura para confirmar conteos).

**Ejecución real:**
- `CraftingMaterialSeeder` con `memory_limit=512M` → `crafting_materials` = 186 (blueprint 99, quest_component 66, sigil 6, enchantment 15).
- `import:blueprint-components` sobre Excel v26.9.1 → 1.549 blueprints procesados, 2.747 componentes insertados, 0 filas omitidas, 0 referencias sin resolver, 0 errores.

**Verificación (solo lectura, `SELECT COUNT(*)`):** `blueprints`=1,549 · `crafting_materials`=186 · `blueprint_components`=2,747. Coincide con lo esperado.

**Limitación pendiente:** `memory_limit=128M` insuficiente (pico real 150.5MB). Solo se aplicó el ajuste puntual `-d memory_limit=512M`; la solución permanente (propuesta B: `memory_limit=512M` en la imagen + rebuild) sigue **pendiente**.

**Documentación:** `docs/reports/2026-10-15_cierre_importacion_blueprint_components.md`.

**Estado:** D-1 VALIDADA POR CONTEOS. Solución permanente de memoria y revisión humana final del comando, pendientes.

## Hito anterior — 2026-10-15 (diagnóstico del fallo de `CraftingMaterialSeeder`, exit 255)
Se diagnosticó (solo lectura) el fallo del seeder D-1. **Sin escrituras en PostgreSQL.**

**Síntoma:** `db:seed` muestra solo `INFO Seeding database.`, termina en exit 255 y deja `crafting_materials` vacía.

**Causa raíz (demostrada):** `memory_limit=128M` en el contenedor; PhpSpreadsheet requiere un pico real de **150.5 MB** al cargar el Excel. Fatal error `Allowed memory size of 134217728 bytes exhausted` en `Reader/Xlsx.php:212` / `Collection/Cells.php:448` → exit 255.

**Evidencia:**
- `php -r 'ini_get("memory_limit")'` → 128M (sin `php.ini` propio; `FROM php:8.4-fpm`).
- `php -d memory_limit=128M -r '...readCraftingMaterialData...'` → OOM, EXIT=255.
- `php -d memory_limit=512M -r '...readCraftingMaterialData...'` → EXIT=0; components=186, blueprints=1549, quest_components=66, sigils=6, enchantments=77; peak=150.5MB.
- `CraftingMaterialSeederTest` fija `ini_set('memory_limit','512M')`, por eso los tests no detectaron la diferencia.

**Solución propuesta (no implementada):** Opción A inmediata `php -d memory_limit=512M artisan db:seed ...`; Opción B permanente `RUN echo 'memory_limit=512M' > /usr/local/etc/php/conf.d/zz-memory.ini` en `docker/php/Dockerfile` + rebuild.

**Confirmación:** No se ejecutaron migraciones, seeders, importaciones ni escrituras. No se modificó código ni configuración.

**Documentación:** `docs/reports/2026-10-15_diagnostico_fallo_crafting_material_seeder.md`.

**Estado:** DIAGNÓSTICO PENDIENTE DE APROBACIÓN HUMANA.

## Hito anterior — 2026-10-15 (corrección del código de salida de `import:blueprint-components`)
Tras revisión humana, se corrigió el comando para no informar éxito cuando la importación deja datos incompletos. **Sin escrituras en PostgreSQL.**

**Cambio:** `ImportBlueprintComponents` devuelve `FAILURE` cuando el servicio informa fallo, hay referencias sin resolver, se omitieron blueprints o el resultado contiene errores; `SUCCESS` solo si la importación es completamente satisfactoria. El comando ya no retorna de forma temprana tras un fallo: siempre muestra la tabla de métricas y todas las incidencias.

**Tests:** `ImportBlueprintComponentsCommandTest` ampliado a 6 tests (se añadió `test_command_fails_when_quality_not_found`; se ajustaron los tests de referencias sin resolver y de blueprints omitidos para esperar `FAILURE`).

**Validaciones (reales):**
- `php -l` → sin errores (2 archivos).
- `pint --test` → 2 files PASS.
- `php artisan test tests/Feature/ImportBlueprintComponentsCommandTest.php` → 6 passed (21 assertions).
- `php artisan test` (suite completa) → 46 passed (382 assertions).
- Diagnósticos IDE → sin errores.

**Confirmación:** PostgreSQL NO fue modificado. No se ejecutaron migraciones, seeders, importaciones ni escrituras. No se hizo ningún commit.

**Documentación:** `docs/reports/2026-10-15_comando_import_blueprint_components.md` actualizado.

**Estado:** IMPLEMENTADA — PENDIENTE DE REVISIÓN HUMANA.

## Hito anterior — 2026-10-15 (preflight de solo lectura para D-1)
Se verificó el entorno (solo lectura) antes de autorizar la ejecución de D-1. **Sin escrituras en PostgreSQL.**

**Resultados observados:**
- Git: modificados (`AI_WORK_LOG.md`, `docker/php/Dockerfile`, modelos `Blueprint`/`BlueprintComponent`, `composer.json/lock`) y nuevos (código D-1, migraciones, tests, reportes). Nada alterado.
- Docker: `shoptitans-app` y `shoptitans-postgres` (postgres:17) `Up 2 days`.
- PostgreSQL: responde; DB `shoptitans`, usuario `shoptitans`, PostgreSQL 17.10.
- Migraciones: 14 MVP `Ran`; D-1 `2026_10_14_000001` y `2026_10_14_000002` **Pending**.
- Conteos observados: `blueprints` = 1549; `blueprint_components` = 0; `crafting_materials` = **no existe** (esperado).
- FK actual `blueprint_components.component_id` → `blueprints(id) ON DELETE RESTRICT` (lo cambiará `000002`).
- Excel: legible; 1,367,141 bytes; ruta contenedor `/var/www/html/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx`.
- Comando `import:blueprint-components` registrado; `CraftingMaterialSeeder`, `ImportBlueprintComponents` y `BlueprintImportService` cargan (`class_exists` = true).

**Bloqueos:** ninguno. **Discrepancias:** ninguna nueva.

**Confirmación:** PostgreSQL NO fue modificado. No se ejecutaron migraciones, seeders, importaciones ni escrituras.

**Documentación:**
- Reporte: `docs/reports/2026-10-15_preflight_d1_crafting_materials.md`

**Estado:** PREPARACIÓN PARA EJECUCIÓN — LISTO PARA SOLICITAR AUTORIZACIÓN. D-1 NO ejecutada ni validada.

## Hito anterior — 2026-10-15 (comando Artisan `import:blueprint-components`)
Se creó el comando Artisan dedicado para importar componentes de blueprints, reutilizando `BlueprintImportService::importComponents()` sin modificar el servicio, **sin modificar PostgreSQL**.

**Cambios de código:**
- `ImportBlueprintComponents` (nuevo): comando `import:blueprint-components {file}`; verifica el archivo, lee las filas con `BlueprintExcelReader::read()`, invoca `importComponents()`, muestra métricas reales (blueprints procesados, componentes insertados, filas omitidas, referencias sin resolver, errores), informa incidencias y devuelve código distinto de cero ante fallo del servicio.
- `ImportBlueprintComponentsCommandTest` (nuevo): 5 tests con el lector mockeado (éxito, archivo inexistente, fallo del servicio, referencias sin resolver, blueprint omitido).

**Validaciones:**
- `php -l` → sin errores (2 archivos).
- `pint --test` → 2 files PASS.
- `php artisan test tests/Feature/ImportBlueprintComponentsCommandTest.php` → 5 tests, 16 assertions, PASS.
- `php artisan list | grep import:` → comando registrado.
- Diagnósticos IDE → sin errores.

**Confirmación:** PostgreSQL NO fue modificado. No se ejecutaron migraciones, seeders ni el comando de importación contra la base de datos real.

**Documentación:**
- Reporte: `docs/reports/2026-10-15_comando_import_blueprint_components.md`

**Estado:** IMPLEMENTADA — PENDIENTE DE REVISIÓN HUMANA.

## Hito anterior — 2026-10-13 (preparación de seeder e importador de componentes D-1)
Se implementó el `CraftingMaterialSeeder` y el importador de componentes (`BlueprintImportService::importComponents()`) para completar D-1, **sin modificar PostgreSQL**.

**Cambios de código:**
- `BlueprintExcelReader`: columnas AL-AQ (Component, Quality, Amount), `parseComponents()`, `readCraftingMaterialData()` (lectura única del Excel para optimizar memoria), métodos `readQuestComponents()`, `readSigils()`, `readEnchantmentNames()`, `readBlueprintNames()`, `readComponentNames()`.
- `BlueprintRowData`: propiedad `components` (array, default `[]`).
- `CraftingMaterialSeeder` (nuevo): clasifica los 186 componentes únicos en 4 tipos (99 blueprint, 66 quest_component, 6 sigil, 15 enchantment), crea `crafting_materials` con `source_key` por tipo, idempotente vía `updateOrCreate`, reporta blueprints faltantes y tipos no clasificados.
- `BlueprintImportService::importComponents()` (nuevo): resuelve componentes contra `crafting_materials` por nombre, calidades contra `qualities`, delete-then-insert idempotente, transacción con rollback, reporta referencias sin resolver.
- `ComponentImportResult` (nuevo): resultado con métricas y referencias sin resolver.
- Tests: `CraftingMaterialSeederTest` (10 tests), `BlueprintComponentImportTest` (9 tests), actualización de `BlueprintExcelReaderTest`.

**Conteos verificados del Excel v26.9.1:**
- 186 componentes únicos (99 blueprint + 66 quest_component + 6 sigil + 15 enchantment), 0 unknown.
- 2747 referencias de componentes (blueprints no-Enchantment).
- Calidades: Normal (127), Superior (35), Flawless (1).
- Discrepancia: los 190/2901 esperados incluyen filas Enchantment; al excluirlas (decisión Etapa 2) quedan 186/2747.

**Validaciones:**
- `php -l` → sin errores.
- `pint --test` → 8 files PASS.
- `php artisan test` → 40 tests, 361 assertions, PASS.
- Diagnósticos IDE → sin errores.
- `composer dump-autoload` → 7408 classes.

**Confirmación:** PostgreSQL NO fue modificado. No se ejecutaron migraciones, seeders ni importaciones contra la base de datos real.

**Documentación:**
- Reporte: `docs/reports/2026-10-13_preparacion_ejecucion_d1.md`

**Estado:** IMPLEMENTADA Y VALIDADA EN TESTS — PENDIENTE DE EJECUCIÓN EN BD.

## Hito anterior — 2026-10-13 (rollback seguro de `blueprint_components`)
Se implementó un `down()` seguro para la migración `2026_10_14_000002` que evita pérdida de datos o estados inconsistentes.

**Problema:** El `down()` original solo cambiaba la FK sin actualizar los datos, dejando `component_id` con valores de `crafting_materials.id` apuntando a `blueprints.id`. Además, si existían componentes de tipo `quest_component`, `sigil` o `enchantment` (sin `blueprint_id` válido), no podían reconstruirse en el esquema anterior.

**Solución aplicada:**
1. **Verificación previa (antes de cualquier DDL):** cuenta los `blueprint_components` cuyo `crafting_material` tiene `blueprint_id IS NULL`.
2. **Abortar si no es reversible:** si el conteo > 0, lanza `RuntimeException` con mensaje claro. No modifica esquema ni datos.
3. **Restaurar datos (si es reversible):** `UPDATE blueprint_components SET component_id = (SELECT cm.blueprint_id FROM crafting_materials cm WHERE cm.id = blueprint_components.component_id)` y recrea la FK a `blueprints`.

**Atomicidad:** Laravel envuelve cada migración en una transacción (`$withinTransaction = true`) para PostgreSQL y SQLite. Cualquier fallo revierte todo.

**Corrección adicional en `000001`:** El método `$table->check()` no existe en Laravel 13. Se reemplazó por `DB::statement('ALTER TABLE ... ADD CONSTRAINT ... CHECK')` condicional al driver `pgsql`. En SQLite (tests) los CHECK constraints se omiten.

**Validaciones:**
- `php -l` y `pint --test` en `000001`, `000002` y el test → PASS.
- `php artisan test` → 20 tests, 104 assertions, PASS.
- `BlueprintComponentsMigrationRollbackTest` (3 tests): tabla vacía, componentes reversibles, componentes no reversibles.

**Estado:** IMPLEMENTADA Y VALIDADA EN TESTS — PENDIENTE DE EJECUCIÓN EN BD.

## Hito anterior — 2026-10-13 (implementación D-1 — `crafting_materials`)
Se implementó la arquitectura aprobada (Opción 4 híbrida) para resolver D-1.

**Cambios de código:**
- Nueva migración `2026_10_14_000001_create_crafting_materials_table.php`: tabla con `source_key` UNIQUE, `material_type` enum, `blueprint_id` FK nullable (RESTRICT), 2 CHECK constraints, índice único parcial en `blueprint_id`.
- Nueva migración `2026_10_14_000002_change_blueprint_components_component_id_foreign.php`: `component_id` ahora FK → `crafting_materials.id`.
- Nuevo modelo `CraftingMaterial` con relaciones `blueprint()` y `blueprintComponents()`.
- `BlueprintComponent.component()` ahora apunta a `CraftingMaterial`.
- Se eliminó `Blueprint.blueprintComponentsAsComponent()` (relación rota).

**source_key por tipo:**
- `blueprint:{blueprints.id}` (con `blueprint_id` NOT NULL)
- `quest_component:{name}`, `sigil:{name}`, `enchantment:{name}` (con `blueprint_id` NULL)

**Riesgo aceptado:** los nombres de quest components, sigils y enchantments pueden cambiar entre versiones; no se implementan mitigaciones.

**Documentación:**
- ADR: `docs/adr/ADR-001_crafting_materials.md`
- Reporte de implementación: `docs/reports/2026-10-14_implementacion_d1_crafting_materials.md`
- Reporte D-1 actualizado: `docs/reports/2026-10-14_revision_source_id_crafting_materials.md`

**Estado:** IMPLEMENTADA (código) — PENDIENTE DE EJECUCIÓN EN BD.

## Hito anterior — 2026-10-13 (revisión de estabilidad de identidad de materiales)
Se investigó si los nombres de Quest Components, Sigils y Enchantments pueden usarse como parte de `source_key`.

**Hallazgos comprobados en el Excel v26.9.1:**
- **Ninguna hoja tiene columna de ID/Key/Code.** El `Name` es el único identificador disponible.
- **Unicidad:** 66 Quest Components, 6 Sigils, 77 Enchantments — 0 duplicados cada uno.
- **Referencias cruzadas:** todos los QC y Sigils referenciados en `Blueprints` por nombre exacto. 15 de 77 Enchantments referenciados por no-Enchantments.
- **Sin inconsistencias** de mayúsculas/minúsculas entre hojas.
- **Changelog:** 1027 filas, 0 menciones de "rename".

**Conclusión:**
- ✅ El nombre es el único identificador disponible y es único y consistente en esta versión.
- ❌ No se puede afirmar estabilidad entre versiones (solo una versión del Excel; Changelog genérico).
- ⚠️ Recomendación: usar `quest_component:{name}`, `sigil:{name}`, `enchantment:{name}` con riesgo documentado de cambio de nombre.

Reporte:
`docs/reports/2026-10-14_revision_estabilidad_identidad_materiales.md`

**Estado:** PROPUESTA — PENDIENTE DE APROBACIÓN HUMANA.

## Hito anterior — 2026-10-14 (cierre de decisiones de identidad y restricciones en `crafting_materials`)
Se actualizó el reporte de `source_id` para cerrar dos decisiones:

**1. Identidad y sincronización (clave estable `source_key`):**
- Se reemplaza `name UNIQUE` por `source_key UNIQUE`. El `name` pasa a ser dato actualizable.
- `source_key` es inmutable y se construye por tipo:
  - `blueprint` → `"blueprint:{blueprint_id}"` (estable, basado en PK)
  - `enchantment` → `"enchantment:{name}"`
  - `quest_component` → `"quest_component:{name}"`
  - `sigil` → `"sigil:{name}"`
- **Actualización de nombre sin duplicados:** `updateOrCreate(['source_key' => ...], ['name' => $newName])`. El `source_key` no cambia aunque cambie el `name`.
- **Idempotencia:** el seeder usa `updateOrCreate` por `source_key`.

**2. Restricciones por tipo (5 constraints PostgreSQL):**
- `chk_material_type_blueprint_id`: `blueprint` → `blueprint_id NOT NULL`; los demás → `NULL`.
- `chk_source_key_format`: el `source_key` debe coincidir con el patrón del tipo.
- `uq_crafting_materials_source_key`: UNIQUE en `source_key`.
- `idx_crafting_materials_blueprint_id_unique`: UNIQUE parcial en `blueprint_id WHERE NOT NULL`.
- `fk_crafting_materials_blueprint`: FK `blueprint_id` → `blueprints.id` con `ON DELETE RESTRICT`.

**Decisiones CERRADAS:** identidad/sincronización y restricciones por tipo.
**Decisiones PENDIENTES:** aprobación de la Opción 4 actualizada; importar o no los 77 enchantments en `blueprints`.

Reporte actualizado:
`docs/reports/2026-10-14_revision_source_id_crafting_materials.md`

**Estado:** PROPUESTA — PENDIENTE DE APROBACIÓN HUMANA.

## Hito anterior — 2026-10-14 (revisión de `source_id` en `crafting_materials`)
Se analizó la Discrepancia D-1: `blueprint_components.component_id` es FK a `blueprints.id`, pero solo el 7.5% de los componentes son blueprints reales.

**Datos verificados del Excel v26.9.1 (2,901 referencias, 190 únicos):**
| Categoría | Únicos | Referencias | % |
|---|---|---|---|
| Blueprint | 103 | 217 | 7.5% |
| Quest Component | 66 | 2,424 | 83.6% |
| Sigil | 6 | 237 | 8.2% |
| Enchantment (D-2) | 15 | 23 | 0.8% |

**Tres alternativas evaluadas:**
- **A. Tabla unificada `crafting_materials`** — ✅ RECOMENDADA. Integridad referencial estricta, una sola tabla, resuelve D-1 y D-2.
- **B. Relación polimórfica** — ❌ Pierde FK estricta, JOINs complejos.
- **C. Registros sintéticos en `blueprints`** — ⚠️ Sin cambios de esquema pero contamina `blueprints` con items que no son blueprints.

**Recomendación:** Alternativa A. Crear tabla `crafting_materials` con `material_type` (enum) y `source_id`, modificar la FK de `blueprint_components.component_id` para apuntar a ella.

**D-2:** La Alternativa A resuelve automáticamente las 23 referencias a Enchantments (15 únicos) insertándolos en `crafting_materials` con `material_type = 'enchantment'`.

Reporte:
`docs/reports/2026-10-14_propuesta_modelado_blueprint_components.md`

**Estado:** PROPUESTA — PENDIENTE DE APROBACIÓN HUMANA.

## Hito anterior — 2026-10-13 (ejecución de importación en PostgreSQL)
Se ejecutó `php artisan import:blueprints` contra PostgreSQL con el Excel v26.9.1.

**Comando ejecutado:**
```bash
docker compose exec -T app php -d memory_limit=512M artisan import:blueprints "/var/www/html/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx"
```

**Resultado de la ejecución:**
```text
Filas leídas (excluyendo Enchantment): 1549
Blueprints insertados: 1549
Blueprints actualizados: 0
```

**Validación en PostgreSQL (conteos):**
| Tabla | Count |
|---|---|
| blueprints | 1549 |
| blueprint_workers | 3744 |
| blueprint_resources | 4135 |
| blueprint_crafting_upgrades | 7745 (1549 × 5) |
| blueprint_starforged_milestones | 7745 (1549 × 5) |
| blueprint_ascension_upgrades | 4647 (1549 × 3) |
| blueprint_transcendence_upgrades | 4647 (1549 × 3) |

**Verificaciones de integridad:**
- 0 Enchantments importados (77 excluidos correctamente).
- 1549/1549 blueprints con las 4 FK de elements en `NULL`.
- Muestra "Squire Sword": tier=1, gold_value=50, craft_time=15s, merchant_xp=135, worker_xp=2, worker=Blacksmith(nivel 1), recurso=Iron×5. Coincide con el Excel.
- **Idempotencia:** segunda ejecución = 0 insertados, 1549 actualizados. Conteos idénticos.

**Estado:** EJECUTADA Y VALIDADA.

## Hito anterior — 2026-10-13 (revisión puntual y final)
Se realizó la revisión puntual y final del BlueprintImporter Etapa 2 contra el Excel fuente v26.9.1 (1626 filas). **Sin modificar código.** Tres puntos revisados:

1. **Campos numéricos obligatorios (8):** 0 vacíos y 0 `"---"` en las 1626 filas. El fallback `?? 0` nunca se activa; es defensivo y no altera datos. Las energías solo tienen `"---"` en "Fresh Spirit" (fila 1584), que es Enchantment y se excluye.
2. **Mapeo de columnas:** Todas las columnas verificadas coinciden con los encabezados reales (workers R-W, recursos Y-AJ, 4 tablas de upgrades, estadísticas AS-AX, fecha CS). Nota menor: header de R tiene espacio final, sin impacto.
3. **Validación y rollback:** La validación previa impide escribir (`ImportResult::failure` antes de la transacción). La transacción global con `catch (Throwable)` revierte todo ante errores de persistencia. Tests verifican `Blueprint::count() === 0` tras errores.

**Resultado:** No se detectaron problemas. El código está listo para aprobación.

Reporte:
`docs/reports/2026-10-13_revision_puntual_blueprint_importer.md`

**Estado:** REVISADA — PENDIENTE DE APROBACIÓN HUMANA.

## Hito anterior — 2026-10-13 (implementación BlueprintImporter Etapa 2)
Se implementó el comando `php artisan import:blueprints {file}` en 4 capas:
- `BlueprintExcelReader` — lee el Excel v26.9.1, convierte `"---"`/vacío a `null`, excluye `Enchantment`.
- `BlueprintRowData` — DTO con `toBlueprintAttributes()`.
- `BlueprintImportService` — transacción global, idempotencia (`updateOrCreate` + `delete-then-insert`), validación previa, rollback ante errores.
- `ImportBlueprints` — comando Artisan con métricas y reporte de errores.

Decisiones aplicadas: excluir Enchantment, 4 FK de elements en `null`, no importar `blueprint_components`, no modificar migraciones/modelos.

Validaciones:
- `php -l` en 5 archivos → sin errores.
- `pint --test` → 7 files PASS.
- `php artisan test` → 17 passed (99 assertions).
- Comando `import:blueprints` registrado en Artisan.

Bug corregido: variable `$wasCreated` sin inicializar causaba error de tipado estricto en PHP 8.4.

Discrepancias D-1, D-2, D-3 permanecen pendientes (fuera de alcance de esta etapa).

**NO se ejecutó contra PostgreSQL real** por restricción del protocolo.

Reporte:
`docs/reports/2026-10-13_implementacion_blueprint_importer_etapa2.md`

**Estado:** IMPLEMENTADA — PENDIENTE DE REVISIÓN.

## Hito anterior — 2026-10-13 (análisis BlueprintImporter)
Se completó el análisis de diseño del `BlueprintImporter` para el MVP Blueprints. **Sin implementar código.** Hallazgos críticos:

- **Discrepancia D-1:** `blueprint_components.component_id` (FK a `blueprints.id`) solo resuelve el 8.3% de las referencias. El 91.7% de los componentes NO son blueprints: 66 son Quest Components (83.6%) y 6 son Sigils de Full Moon Fusions (8.2%).
- **Discrepancia D-2:** 23 blueprints no-Enchantment dependen de componentes Enchantment. Si se excluyen los 77 Enchantments, estas referencias quedan huérfanas.
- **Discrepancia D-3:** `elements` sin poblar (101 valores únicos en cols AZ-BC). Las 4 FK de afinidad quedarían en `null`.

Reporte:
`docs/reports/2026-10-13_analisis_blueprint_importer_mvp.md`

**Estado:** PROPUESTA — PENDIENTE DE REVISIÓN.

## Hito anterior — 2026-10-13 (ejecución y validación)
Se ejecutó `php artisan db:seed` correctamente y se validaron los 5 catálogos en PostgreSQL:
- `resources` = 12 ✅
- `qualities` = 5 ✅
- `item_categories` = 40 ✅ (excluye `Enchantment`)
- `workers` = 19 ✅
- `worker_levels` = 50 ✅

`elements` se mantiene como pendiente de decisión (sin resolver).

Reporte:
`docs/reports/2026-10-13_seeders_catalogos_mvp_blueprints.md`

## Hito anterior — 2026-10-13 (revisión)
Se corrigieron los seeders según observaciones de la IA arquitecta:
- `ItemCategorySeeder`: se excluyó `Enchantment` del catálogo MVP (40 categorías).
- `resources.tier`: clarificado como clasificación propia del recurso (T1–T5), no el tier de blueprint.
- Reporte actualizado con evidencias de Excel v26.9.1.
- Documentadas dudas sobre `xp_needed` duplicado (niveles 35/36) y calidades Epic/Legendary.
- Confirmado orden de `DatabaseSeeder` sin dependencias de FK entre los 5 catálogos.
- **Restaurado `User::factory()`** en `DatabaseSeeder` (la eliminación no tenía justificación técnica concreta).

Reporte:
`docs/reports/2026-10-13_seeders_catalogos_mvp_blueprints.md`

## Hito previo — 2026-10-13
Se implementaron 5 seeders de catálogos (resources, qualities, item_categories, workers, worker_levels) con datos reales del Excel. Validados con `php -l`, `composer dump-autoload` y `pint --test`. **No ejecutados.** `elements` registrado como pendiente opcional.

## Hito previo — 2026-08-28
Se ejecutaron las 14 migraciones del MVP Blueprints. PostgreSQL quedó con 23 tablas totales y 16 FK validadas.

Reporte:
`docs/reports/2026-08-28_migrate_real_mvp_blueprints.md`

## Estado de tareas
- Análisis Excel: 🟢 VALIDADO
- Modelo Blueprints: 🟢 APROBADO
- Migraciones/modelos: 🟢 APROBADO
- Migración PostgreSQL: 🟢 EJECUTADA / VALIDADA
- Seeders catálogos: 🟢 EJECUTADA / VALIDADA (resources=12, qualities=5, item_categories=40, workers=19, worker_levels=50)
- D-3 elementos/afinidades AZ–BC: 🟢 **EJECUTADA Y VALIDADA EN POSTGRESQL**. 5 migraciones `Ran` (batch [4]); 4 seeders de catálogos; `import:blueprints` (1549/0/1549/0, EXIT 0). Conteos: `elements`=8, `enchantments`=73 (Element 33/Spirit 40), `spirit_affinities`=38, `spirits`=1, `blueprint_elemental_affinities`=911. 0 huérfanos, orden AZ preservado.
- Análisis BlueprintImporter: 🟢 PROPUESTA APROBADA
- BlueprintImporter Etapa 2 (sin componentes): 🟢 EJECUTADA Y VALIDADA — 1549 blueprints en PostgreSQL
- D-1 `crafting_materials`: 🟢 EJECUTADA Y VALIDADA POR CONTEOS. `crafting_materials`=186 (99/66/6/15) y `blueprint_components`=2,747. Memoria PHP configurada de forma permanente (`memory_limit=512M` en la imagen).
- Comando `import:blueprint-components`: 🟢 EJECUTADO sobre Excel v26.9.1 → 1.549 procesados, 2.747 insertados, 0 incidencias. Revision final completada; H-1/H-2/H-3 implementados (H-4 sin cambios). Pendiente de revisión.
- Preflight D-1 (solo lectura): 🟢 COMPLETADO — LISTO PARA SOLICITAR AUTORIZACIÓN (sin bloqueos; migraciones D-1 Pending; `blueprints`=1549, `blueprint_components`=0, `crafting_materials` no existe).
- API de Blueprints (auditoría): 🟢 **AUDITORÍA COMPLETADA — IMPLEMENTACIÓN PENDIENTE DE APROBACIÓN**. Sin infraestructura API existente (sin rutas `api`, controladores, Resources ni auth). Contrato propuesto en `docs/reports/2026-10-16_auditoria_api_blueprints.md`; veredicto: modelo de datos listo para exponerse.
- API de Blueprints (Etapa 2, implementación): 🟡 **IMPLEMENTADA — PENDIENTE DE REVISIÓN** (2026-10-22). Dos endpoints `/api/v1` implementados conforme al contrato (D-1..D-8), sin N+1, y probados. Sin escrituras en PostgreSQL.
- API de Blueprints (Etapa 2, revisión correctiva DP-1/DP-2): 🟢 **CORREGIDO Y VALIDADO EN PRUEBAS — PENDIENTE DE REVISIÓN INDEPENDIENTE** (2026-10-22). DP-1 (`available_as_antique` admite `true/false/1/0`) y DP-2 (aislamiento por `phpunit.xml` en modo estándar y `env=testing`) cerrados; OpenAPI actualizado.
- API de Blueprints (Etapa 2, revisión final): 🟢 **CORREGIDO Y VALIDADO EN PRUEBAS — PENDIENTE DE REVISIÓN INDEPENDIENTE** (2026-10-22). 500 con `include` anidado confirmado y corregido (+ prueba de regresión); descripción del OpenAPI actualizada a "IMPLEMENTADO"; correspondencia OpenAPI↔código verificada (include 11/11, filtros 16/16, sort 7/7, operadores, params y códigos de respuesta).
- API de Blueprints (Etapa 2, revisión independiente): 🟢 **APROBADA CON OBSERVACIONES** (2026-10-23). Revisión de solo lectura; sin discrepancias de contrato D-1..D-8; DP-1/DP-2 y la regresión del `include` anidado confirmadas; `BlueprintApiTest` 18 passed (155 assertions). Hallazgos H-1..H-4 (baja/informativos, no bloqueantes). Informe: `docs/reports/2026-10-23_revision_independiente_api_blueprints.md`.
- API de Blueprints (Etapa 2, corrección de observaciones H-1..H-4): 🟢 **CORREGIDA Y VALIDADA EN PRUEBAS** (2026-10-23). H-1: `applyPartialMatch()` usa `whereLike` (sin `whereRaw` ni concatenación de columna). H-2: `applySort()` añade desempate por `id`. H-3/H-4: contrato OpenAPI alineado (`filter[campo][eq]` en texto; `minimum: 1` en `category_id`). `BlueprintApiTest` 19 passed (160 assertions). Pendiente de aprobación humana de los dos puntos del §17.5 del informe de revisión. Informe: `docs/reports/2026-10-23_revision_independiente_api_blueprints.md` (§17 Addendum).
- `CraftingMaterialSeeder` en `DatabaseSeeder` (evaluación): 🟡 **INVESTIGACIÓN DE SOLO LECTURA COMPLETADA — DECISIÓN PENDIENTE DE APROBACIÓN HUMANA** (2026-10-23). Idempotente y seguro de re-ejecutar, pero **incorporarlo aislado es incoherente** para instalación nueva (faltarían las 99 filas `blueprint` sin `import:blueprints` previo; `db:seed` repetido rompe por UNIQUE(`email`)). Recomendación: opción C (seeder orquestador del MVP) o mantener independiente (A). Informe: `docs/reports/2026-10-23_evaluacion_crafting_material_seeder.md`. **No se modificó `DatabaseSeeder`.**
- Tests: 🟢 **74 passed (576 assertions)** — incluye `BlueprintApiTest` (18; Etapa 2, incl. regresión de `include`), los tests de afinidades de D-3 (`BlueprintImportServiceTest` 12, `BlueprintAffinityForeignKeysRollbackTest` 3) y el resto de la suite, sobre SQLite `:memory:`; verificado en modo estándar y `env=testing`.
- Recuperación de PostgreSQL: 🟢 **EJECUTADA Y VALIDADA — EXITOSA** (2026-10-23). 5 fases con `exit=0`; conteos exactos (1.549/2.747/186/8/73/38/1/911), sin huérfanos ni duplicados, orden AZ preservado, `users`=0. Respaldo previo en `backups/shoptitans_pre_recuperacion_20261023_203355.dump`. Informe: `docs/reports/2026-10-23_recuperacion_datos_postgresql.md`.
- Aislamiento de PHPUnit y protección de PostgreSQL: 🟢 **DECISIONES P-1/P-2/P-3 APROBADAS E IMPLEMENTADAS** (2026-10-23). P-1: se conserva el bloque `<server>` (`phpunit.xml` sin cambios). P-2: el aislamiento se documenta como `force="true"` + `<server>` (no `--env=testing`). P-3: guarda `App\Console\DestructiveCommandGuard` (listener de `CommandStarting`) que bloquea `migrate:fresh`/`migrate:refresh`/`db:wipe` contra la base pgsql `shoptitans` (exit 1). Validado: `DestructiveCommandGuardTest` 6 passed (14 assertions); no interferencia con `BlueprintApiTest` 18 passed (155 assertions). Informe: `docs/reports/2026-10-23_formalizacion_aislamiento_y_proteccion_postgresql.md`.

## Próximo paso
**Recuperación de datos: EJECUTADA Y VALIDADA (2026-10-23).** Plan de 5 fases del preflight ejecutado con respaldo previo y guarda de conexión; validaciones completas con resultado exitoso. Pendientes: conservar el respaldo hasta cerrar el incidente; decidir si `CraftingMaterialSeeder` se añade a `DatabaseSeeder`. Informe: `docs/reports/2026-10-23_recuperacion_datos_postgresql.md`.

**Aislamiento de PHPUnit y protección de PostgreSQL: FORMALIZADO (2026-10-23).** Propuestas P-1/P-2/P-3 aprobadas e implementadas: se conserva el bloque `<server>` de `phpunit.xml` sin cambios (P-1); el aislamiento se documenta como `force="true"` + `<server>` y no como efecto de `--env=testing` (P-2); y se añadió la guarda `App\Console\DestructiveCommandGuard` que bloquea `migrate:fresh`/`migrate:refresh`/`db:wipe` cuando la conexión efectiva es pgsql sobre `shoptitans` (P-3). Informe: `docs/reports/2026-10-23_formalizacion_aislamiento_y_proteccion_postgresql.md`.

**API de Blueprints: Etapa 1 (contrato) CERRADA (D-1..D-8) → Etapa 2 (implementación `/api/v1`) IMPLEMENTADA (2026-10-22) → REVISADA INDEPENDIENTEMENTE (2026-10-23): APROBADA CON OBSERVACIONES → OBSERVACIONES H-1..H-4 CORREGIDAS (2026-10-23).** Los dos endpoints de lectura están implementados y probados (`BlueprintApiTest` 19 passed/160 assertions tras la corrección, en SQLite `:memory:`). Sin discrepancias de contrato; DP-1 (`filter[available_as_antique]` admite `true/false/1/0`), DP-2 (aislamiento de pruebas frente a `env=testing`) y el 500 con `include` anidado **corregidos y confirmados**. Corrección de H-1..H-4: `whereLike` sin SQL crudo (H-1), desempate por `id` en el ordenamiento (H-2), y contrato OpenAPI alineado (`filter[campo][eq]` en texto y `minimum: 1` en `category_id`; H-3/H-4). Pendiente de aprobación humana: los dos puntos del §17.5 del informe de revisión. Ver `docs/reports/2026-10-23_revision_independiente_api_blueprints.md` (§17 Addendum), `docs/reports/2026-10-22_implementacion_api_blueprints.md`, `docs/reports/2026-10-22_revision_correctiva_api_blueprints.md` y `docs/reports/2026-10-22_revision_final_etapa2_api_blueprints.md`.

D-1 está ejecutada y validada por conteos, el límite de memoria PHP quedó configurado de forma permanente (`memory_limit=512M`) y los hallazgos H-1/H-2/H-3 del importador de componentes están implementados.

**D-3 (elementos/afinidades AZ–BC): EJECUTADA Y VALIDADA EN POSTGRESQL (2026-10-16).** Se ejecutaron (autorizados por el usuario): las 5 migraciones nuevas (`2026_10_16_000001..000005`, batch [4], `Ran`), los seeders de catálogos por clase (`ElementSeeder`, `EnchantmentSeeder`, `SpiritAffinitySeeder`, `SpiritSeeder`, sin `db:seed` global) y `import:blueprints` (EXIT 0). Validación de solo lectura sin discrepancias bloqueantes. Ver `docs/reports/2026-10-16_ejecucion_d3_postgresql.md`.

Pendiente de decisión humana (no autorizado, no iniciado):
1. Revisión humana del reporte de ejecución D-3.
2. Decisiones de modelado abiertas: semántica de `Gold`/`All`, mapeo `Spirit Affinity` (BA) ↔ `enchantments`, y API de catálogos.
3. Revisión humana de H-1/H-2/H-3 del importador de componentes (estado actual: IMPLEMENTADA — PENDIENTE DE REVISIÓN).
4. (Opcional, no autorizado) decidir si se activa `log_errors`/`error_log` para fatales.
5. (Opcional, no autorizado) H-4: valorar un segundo código de salida para "completado con incidencias" (actualmente sin cambios).

Validación ya obtenida: `crafting_materials`=186 (99 blueprint, 66 quest_component, 6 sigil, 15 enchantment), `blueprint_components`=2,747 y `memory_limit=512M` efectivo. Tests: `ImportBlueprintComponentsCommandTest` 7 passed; relacionados 22 passed.

Validación D-3 (real): `php -l` OK, Pint PASS y `php artisan test` → 56 passed (421 assertions) sobre SQLite `:memory:`. Ejecución en PostgreSQL: `blueprints`=1549 (IDs preservados), `elements`=8, `enchantments`=73, `spirit_affinities`=38, `spirits`=1, `blueprint_elemental_affinities`=911, 0 huérfanos, orden AZ preservado.

## Regla
El detalle de cada tarea vive en `docs/reports/`. Este archivo resume estado, decisiones y próximos pasos.
