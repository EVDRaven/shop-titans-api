# Reporte Técnico — Implementación de la mejora de OpenAPI (Blueprints MVP)

**Fecha:** 2026-10-26
**Autor:** Editor IA
**Estado:** IMPLEMENTADA (documentación del contrato; sin cambios de código)
**Base:** `docs/reports/2026-10-26_evaluacion_mejora_openapi_blueprints.md` (aprobada por el usuario el 2026-10-26)

---

## 1. Objetivo

Incorporar al contrato `docs/openapi/blueprints-v1.yaml` los ejemplos de respuesta y las correcciones de ejemplos aprobadas en la evaluación previa, para que los consumidores comprendan las respuestas **reales** de la API. **Sin ampliar el alcance funcional ni modificar la implementación.**

## 2. Autorización y decisiones confirmadas

Autorizado por el usuario con estas decisiones:

- Errores: documentar **únicamente** el formato de producción (`APP_DEBUG=false`).
- Relaciones `include`: **omitir** ejemplos de relaciones en esta iteración.
- Error 404: **documentar el comportamiento actual**; sin normalizar el mensaje ni tocar el manejador de excepciones.
- Alcance principal: `docs/openapi/blueprints-v1.yaml`.
- `CHANGELOG.md`: modificar solo si corresponde registrar la mejora.
- Crear informe físico y actualizar `AI_WORK_LOG.md`.
- Sin cambios de código PHP, pruebas, configuración ni base de datos. Sin commits ni push.

## 3. Cambios aplicados

### 3.1 `docs/openapi/blueprints-v1.yaml`

| # | Ubicación | Cambio |
|---|---|---|
| 1 | `paths./blueprints.get.responses.200` | Añadido `content.application/json.example` con `{data, meta, links}` (evidencia **R**). Descripción ampliada: el host de `links` es absoluto y depende de la petición (se ilustra con `https://example.com`). |
| 2 | `paths./blueprints/{id}.get.responses.200` | Añadido `content.application/json.example` con `{data}` (evidencia **R**/**T**). |
| 3 | `components.responses.ValidationError` | Añadido `example` con `message` y `errors` (evidencia **R**). Descripción ampliada sobre `ValidationException::summarize()`. |
| 4 | `components.responses.NotFound` | Añadido `example` con el mensaje real que **expone la clase del modelo** (evidencia **F**, deriva del framework; no capturado en runtime). |
| 5 | `components.responses.ServerError` | Añadido `example` `{"message": "Server Error"}` (evidencia **F**). |
| 6 | `components.schemas.ValidationErrorResponse.properties.message` | **Corregido** `example` (H-1): se reemplaza «The given data was invalid.» por el primer mensaje real; añadida `description`. |
| 7 | `components.schemas.Blueprint.properties.id` | **Corregido** `example` de `1` a `59` (H-2). |
| 8 | `components.schemas.Blueprint.properties.{gold_value,craft_time_formatted,value_per_second,merchant_xp,merchant_xp_per_second}` | **Corregidos** a los valores reales del recurso `id=59`: `50`, `00:00:15`, `3.33`, `135`, `9` (H-3/H-4). |
| 9 | `components.parameters.BlueprintId` | **Corregido** `example` de `1` a `59` (H-2). |

No se añadieron ni eliminaron endpoints, parámetros ni esquemas. No se añadieron ejemplos de relaciones `include`. No se modificaron los esquemas (`additionalProperties` intacto).

> Nota de proceso: la primera aplicación de la corrección de `Blueprint.id` (1 → 59) fue sobrescrita por una segunda edición aplicada en el mismo lote desde la misma base del archivo; se detectó en la validación y se **re-aplicó** correctamente. Estado final verificado (ver §5).

### 3.2 `CHANGELOG.md`

Dos entradas breves (sin reescribir historial, sin afirmar cambios funcionales):

- *Added*: incorporación de los ejemplos de respuesta (200 listado/detalle, 422, 404, 500 de producción).
- *Fixed*: corrección de ejemplos inconsistentes (`id`, `craft_time_formatted`, valores numéricos) y de `ValidationErrorResponse.message.example`.

## 4. Origen verificable de cada ejemplo

| Respuesta | Origen | Evidencia |
|---|---|---|
| 200 listado | Respuesta real capturada (`/tmp/val1.txt`, host `127.0.0.1:8000`) | **R** |
| 200 detalle | Respuesta real capturada (`/tmp/val2b.txt`, `id=59`) + aserciones de `BlueprintApiTest` | **R**/**T** |
| 422 | Respuesta real capturada (`/tmp/val4.txt`, `filter[no_existe]`) | **R** |
| 404 | `Handler::prepareException()` + `ModelNotFoundException::setModel()` | **F** (no capturado en runtime) |
| 500 | `Handler::convertExceptionToArray()` (rama no-debug) | **F** (no capturado en runtime) |

Para el 200 listado y el 200 detalle se usó el registro real `id=59` («Squire Sword») del dataset MVP; los valores numéricos corresponden a la serialización JSON real (`merchant_xp_per_second` se serializa como `9`, sin `.0`, conforme a H-4).

Los `links` de paginación usan un host **ilustrativo** (`https://example.com`) porque el host real depende de la petición; la nota se incluye en la propia `description` de la respuesta.

## 5. Validaciones ejecutadas y resultados

Parser: **PyYAML 6.0.3** (herramienta de sistema; no se instaló ni añadió ninguna dependencia al proyecto).

| Validación | Resultado |
|---|---|
| Parseo del YAML sin errores | **OK** (`yaml.safe_load`) |
| `openapi` == `3.0.3` | **OK** |
| `info.version` == `1.0.0` | **OK** |
| Endpoints (`paths`) == 2 | **OK** (`/blueprints`, `/blueprints/{id}`) |
| Esquemas (`components.schemas`) == 26 | **OK** |
| Respuestas reutilizables == 3 | **OK** (`NotFound`, `ValidationError`, `ServerError`) |
| `$ref` resueltos | **OK** (76/76, 0 sin resolver) |
| Ejemplo 200 listado = `{data, meta, links}` | **OK** |
| Ejemplo 200 detalle = `{data}` | **OK** |
| `craft_time_formatted` == `00:00:15` | **OK** |
| `merchant_xp_per_second` entero `9` (H-4) | **OK** |
| `value_per_second` == `3.33`, `gold_value` == `50` | **OK** |
| 404 expone la clase del modelo | **OK** (`[App\Models\Blueprint]`) |
| 422 coincide con respuesta real | **OK** |
| 500 == `{"message": "Server Error"}` | **OK** |
| `ValidationErrorResponse.message.example` ya no es la frase genérica | **OK** |
| `Blueprint.id.example` == `59` y `BlueprintId.example` == `59` | **OK** |
| Validación estructural OpenAPI 3.0.3 (campos obligatorios, `description` en respuestas, `schema` en `content`, `name`/`in`/`schema` en parámetros, sin tabuladores) | **OK** (0 errores) |

Comando de validación (resumen reproducible, sin dependencias):

```bash
python3 - <<'PY'
import yaml
doc = yaml.safe_load(open('docs/openapi/blueprints-v1.yaml', encoding='utf-8'))
assert doc['openapi'] == '3.0.3'
assert len(doc['paths']) == 2
assert len(doc['components']['schemas']) == 26
print('parse OK; paths=2; schemas=26')
PY
```

### 5.1 Discrepancia detectada (no causada por esta tarea)

`components.parameters` contiene **42** entradas, no 41 como indicaban la evaluación previa y el criterio de aceptación («41 parámetros»). El **endpoint de listado referencia 41 parámetros** y el de detalle referencia 2 (`BlueprintId` + `Include`), sumando 43 referencias sobre 42 componentes únicos (el conteo de 41 coincide con las referencias del listado). **Esta tarea no alteró dicho número**: la corrección de `BlueprintId` solo cambió el valor de `example`, no la colección. Se reporta por transparencia; no se modificó nada al respecto.

### 5.2 Validación OpenAPI 3.0.3 con librería específica

No se ejecutó una validación con librería de esquema OpenAPI (p. ej. `openapi-spec-validator`) porque **no está instalada y añadir dependencias está fuera del alcance autorizado**. En su lugar se ejecutó: (a) parseo YAML estricto, (b) comprobación de campos obligatorios OpenAPI 3.0.3 y (c) validación estructural (respuestas con `description`, `content` con `schema`, parámetros con `name`/`in`/`schema`, sin tabuladores). Resultado: **0 errores**.

## 6. Ejemplos respaldados por runtime vs derivados del framework

- **Capturados en runtime (evidencia directa):** 200 listado, 200 detalle, 422.
- **Derivados del código del framework (no capturados en runtime):** 404 y 500. Se documentan en su **forma de producción** (`APP_DEBUG=false`); el 404 conserva el mensaje que expone la clase del modelo, conforme a la decisión del usuario de **no** normalizarlo.

## 7. Limitaciones y criterios no verificables

- El cuerpo exacto del **404** y del **500** no se capturó en runtime; se deduce del framework Laravel 13.8 (`Handler`, `ModelNotFoundException`). Los ejemplos podrían diferir en detalles si el framework cambiara de versión.
- El **host** de `links` depende de la petición; el ejemplo usa `https://example.com` como valor ilustrativo.
- No se documentaron ejemplos de relaciones `include` (decisión explícita del usuario).
- No se ejecutaron pruebas de aplicación, migraciones, seeders, importadores ni operaciones en PostgreSQL (no necesarias para una mejora documental).

## 8. Verificación de alcance (sin ampliación)

- Se mantiene **OpenAPI 3.0.3**, `info.version 1.0.0`, **2 endpoints**, **26 esquemas** y todas las `$ref` resueltas.
- No se añadieron endpoints, parámetros ni capacidades. No se tocaron controladores, *Form Requests*, recursos, modelos, rutas, migraciones, pruebas, `.env`, Docker Compose ni `phpunit.xml`.
- No se añadió `additionalProperties: false` ni se alteraron esquemas por fuera del alcance.

## 9. Declaración de no cambios de implementación / datos / configuración

**No hubo cambios de código PHP, pruebas, configuración ni base de datos.** Los únicos archivos modificados son **documentación** (`docs/openapi/blueprints-v1.yaml`, `CHANGELOG.md`) y las actualizaciones de bitácora/informe. **No se realizaron commits ni push.**

## 10. Archivos creados / modificados

| Archivo | Acción |
|---|---|
| `docs/openapi/blueprints-v1.yaml` | Modificado (ejemplos y correcciones) |
| `CHANGELOG.md` | Modificado (2 entradas: Added / Fixed) |
| `docs/reports/2026-10-26_implementacion_mejora_openapi_blueprints.md` | Creado (este informe) |
| `AI_WORK_LOG.md` | Modificado (nuevo hito) |

## 11. Estado

**IMPLEMENTADA** la mejora documental autorizada. Todos los criterios de validación ejecutados resultaron **OK**. Queda a la espera de la revisión del usuario.
