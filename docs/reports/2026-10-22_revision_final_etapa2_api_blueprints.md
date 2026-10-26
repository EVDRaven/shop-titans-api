# Reporte Técnico — Revisión final de la Etapa 2 (API pública de Blueprints)

**Fecha:** 2026-10-22
**Autor:** Editor IA
**Estado:** CORREGIDO Y VALIDADO — PENDIENTE DE REVISIÓN INDEPENDIENTE

## 1. Objetivo

Cerrar la revisión final de la **Etapa 2 (API pública `/api/v1` de Blueprints)** atendiendo cuatro objetivos concretos:

1. Verificar que la configuración de PHPUnit garantice el uso de **SQLite en memoria** y evite conexiones accidentales a **PostgreSQL** durante las pruebas (incluido el modo `env=testing`).
2. Investigar el posible **error 500 en `BlueprintRequest` cuando `include` recibe una estructura anidada**; si se confirma, corregirlo y añadir una prueba de regresión.
3. Actualizar la **descripción obsoleta** en `docs/openapi/blueprints-v1.yaml` (indicaba que los endpoints *no* estaban implementados).
4. Revisar la **correspondencia** entre OpenAPI, rutas, validaciones, controlador, recurso y pruebas.

## 2. Alcance

**Incluye:** verificación de aislamiento de pruebas; diagnóstico y corrección del defecto de `include` con estructura anidada; actualización de la descripción del contrato (sin tocar decisiones D-1..D-8); verificación de correspondencia OpenAPI ↔ código.

**Excluye:** cambios de contrato, de decisiones aprobadas, de esquema de base de datos, de rutas, de lógica funcional de los endpoints, migraciones, seeders, importadores y cualquier escritura en PostgreSQL.

## 3. Estado inicial

- Etapa 2 **implementada** con la revisión correctiva DP-1/DP-2 ya aplicada (`docs/reports/2026-10-22_revision_correctiva_api_blueprints.md`).
- `src/phpunit.xml` con `force="true"` + directivas `<server>` para las variables de base de datos y servicios.
- Descripción del OpenAPI aún con el texto de Etapa 1 ("los endpoints aún NO están implementados").
- `BlueprintRequest::includeList()` original aplicaba `trim()` a **todos** los elementos de `include` sin comprobar su tipo → riesgo de `TypeError` con entradas no escalares.

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| `src/app/Http/Requests/BlueprintRequest.php` | Modificado | `includeList()` ignora elementos no escalares para evitar el `TypeError` (causa del 500) |
| `src/tests/Feature/BlueprintApiTest.php` | Modificado | Prueba de regresión `test_include_rejects_nested_or_array_structures` |
| `docs/openapi/blueprints-v1.yaml` | Modificado | `info.description`: estado actualizado de "no implementado" a "IMPLEMENTADO" |

`src/phpunit.xml` **no** se modificó en este tramo: fue modificado y validado en la revisión correctiva previa (DP-2) y aquí solo se **verificó** su efecto.

## 5. Cambios realizados

### 5.1 Corrección del 500 con `include` anidado (objetivo 2)

`includeList()` ahora descarta cualquier elemento que no sea `string` antes de normalizarlo, en lugar de pasar todo por `trim()`:

```php
$values = is_array($raw) ? $raw : explode(',', (string) $raw);

$normalized = [];

foreach ($values as $value) {
    if (! is_string($value)) {
        continue;
    }

    $value = trim($value);

    if ($value !== '') {
        $normalized[] = $value;
    }
}

return array_values(array_unique($normalized));
```

El rechazo de la estructura anidada queda delegado a la regla `'include' => ['sometimes', 'string']` de cada Form Request, que responde **422** (no 500). `<server>`/`<env>` no intervienen en este defecto.

### 5.2 Prueba de regresión

```php
public function test_include_rejects_nested_or_array_structures(): void
{
    $this->createBlueprint();

    // Estructura anidada (array dentro de array): no debe provocar 500.
    $this->getJson('/api/v1/blueprints?include[workers][0]=workers')->assertStatus(422);
    $this->getJson('/api/v1/blueprints/1?include[workers][0]=workers')->assertStatus(422);

    // Array plano: fuera del contrato (`include` es una cadena CSV).
    $this->getJson('/api/v1/blueprints?include[]=workers')->assertStatus(422);
}
```

### 5.3 Descripción del OpenAPI (objetivo 3)

`info.description` pasa de "MVP — Etapa 1 … los endpoints aún NO están implementados" a declarar el estado **CONTRATO APROBADO (D-1..D-8) e IMPLEMENTADO (Etapa 2)**. **No se modificó** ningún esquema, parámetro, respuesta, `example` ni las `x-approved-decisions` D-1..D-8.

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| `include` con estructura anidada produce 500 | Sin corrección, `trim(): Argument #1 ($string) must be of type string, array given` en `BlueprintApiTest.php:136` | **Confirmada** (y corregida) |
| La corrección elimina el 500 y devuelve 422 | `test_include_rejects_nested_or_array_structures` → PASS (3 assertions) | **Confirmada** |
| `force="true"` + `<server>` en `phpunit.xml` aíslan las pruebas en SQLite `:memory:` incluso con `DB_HOST` inexistente | Suite completa verde en modo estándar y en `env=testing` con `DB_HOST=probe.invalid.aislamiento` | **Confirmada** |
| La descripción del OpenAPI estaba desactualizada | Texto previo: "los endpoints aún NO están implementados"; rutas/controlador sí existen | **Confirmada** (y corregida) |
| OpenAPI, rutas, validaciones, controlador, recurso y pruebas son coherentes | Verificación automática de correspondencia (§9) | **Confirmada** |

## 7. Discrepancias

Ninguna discrepancia bloqueante. Observación (fuera de alcance, no corregida): la base PostgreSQL de desarrollo reporta actualmente `blueprints`=**0** (las migraciones están aplicadas: 24). No es efecto de esta revisión (las pruebas usan SQLite y solo se ejecutaron consultas `SELECT`).

## 8. Decisiones

### Propuestas
- Ninguna.

### Aprobadas
- D-1..D-8 (sin cambios). No se modificó ninguna decisión aprobada.

### Reemplazadas/Rechazadas
- Ninguna.

## 9. Validaciones

### 9.1 Sintaxis PHP
```bash
docker compose exec -T app sh -lc 'php -l app/Http/Requests/BlueprintRequest.php && php -l app/Http/Requests/BlueprintIndexRequest.php && php -l app/Http/Controllers/Api/V1/BlueprintController.php && php -l tests/Feature/BlueprintApiTest.php'
```
```text
No syntax errors detected in app/Http/Requests/BlueprintRequest.php
No syntax errors detected in app/Http/Requests/BlueprintIndexRequest.php
No syntax errors detected in app/Http/Controllers/Api/V1/BlueprintController.php
No syntax errors detected in tests/Feature/BlueprintApiTest.php
```

### 9.2 Estilo (Pint)
```bash
docker compose exec -T app sh -lc 'php vendor/bin/pint --test'
```
```text
  ──────────────────────────────────────────────────────────── Laravel
    PASS   ................................................. 98 files
```

### 9.3 Aislamiento + suite completa (modo estándar, con sonda de BD inexistente)
```bash
docker compose exec -T app sh -lc 'DB_HOST=probe.invalid.aislamiento php vendor/bin/phpunit --colors=never'
```
```text
......... 74 / 74 (100%)
Time: 00:35.409
OK (74 tests, 576 assertions)
```

### 9.4 Aislamiento + suite completa (modo `env=testing`, con sonda de BD inexistente)
```bash
docker compose exec -T app sh -lc 'DB_HOST=probe.invalid.aislamiento php artisan test --env=testing --colors=never'
```
```text
  Tests:    74 passed (576 assertions)
  Duration: 35.59s
```
> La sonda apunta a un host inválido: si el aislamiento fallara, la suite fallaría al conectar. Ambas ejecuciones pasan → la conexión usada es **SQLite `:memory:`**, con independencia de `env=testing`.

### 9.5 Correspondencia OpenAPI ↔ validaciones (`BlueprintIndexRequest` / `BlueprintRequest`) ↔ rutas ↔ controlador ↔ recurso
```bash
python3 <script de correspondencia sobre el YAML y las constantes PHP>
```
```text
INCLUDES == OAS include enum  : True (11)
SORTABLE documented in Sort   : True ['airship_power','craft_time_seconds','gold_value','id','merchant_xp','name','tier']
FILTERS == OAS base fields    : True (16 vs 16)
ANTIQUE == OAS antique enum   : True
  numeric tier: ['gt','gte','lt','lte']
  numeric gold_value: ['gt','gte','lt','lte']
  numeric craft_time_seconds: ['gt','gte','lt','lte']
  numeric merchant_xp: ['gt','gte','lt','lte']
  numeric airship_power: ['gt','gte','lt','lte']
show params: ['id','include']
responses index: ['200','422','500']
responses show : ['200','404','422','500']
```
- Rutas [api.php](file:///home/draven/Documentos/shop-titans-api/src/routes/api.php): `GET /v1/blueprints` y `GET /v1/blueprints/{id}` → coinciden con los 2 `paths` del contrato.
- Controlador [BlueprintController.php](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php): `index(BlueprintIndexRequest)` y `show(BlueprintShowRequest)`, serialización `{data,meta,links}` / `{data}`.
- Recurso [BlueprintResource.php](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Resources/BlueprintResource.php): incluye los campos y relaciones del contrato (`category` siempre, resto por `include`), enteros/decimales como números (D-8).
- `FILTERS` (16) = parámetros `filter[...]` del OAS; cada campo numérico expone `eq`+`gt`/`gte`/`lt`/`lte`.

### 9.6 Integridad del OpenAPI
```text
openapi: 3.0.3 | version: 1.0.0
paths: ['/blueprints', '/blueprints/{id}']
refs totales: 70 | sin resolver: 0
```

### 9.7 PostgreSQL — verificación de solo lectura (sin escrituras)
```bash
docker compose exec -T postgres psql -U shoptitans -d shoptitans -tAc "SELECT (SELECT count(*) FROM information_schema.tables WHERE table_schema='public'), (SELECT count(*) FROM blueprints), (SELECT count(*) FROM migrations);"
```
```text
28|0|24
```
> Solo consultas `SELECT`. No se ejecutaron migraciones, seeders, importadores ni escrituras.

## 10. Resultado final

- **Objetivo 1:** cumplido. La suite pasa en modo estándar y en `env=testing` con `DB_HOST` inválido → aislamiento garantizado en SQLite `:memory:`.
- **Objetivo 2:** cumplido. El 500 con `include` anidado estaba **confirmado**; corregido en `includeList()`; prueba de regresión añadida y en verde.
- **Objetivo 3:** cumplido. Descripción del OpenAPI actualizada a "IMPLEMENTADO"; contrato y D-1..D-8 intactos.
- **Objetivo 4:** cumplido. Correspondencia verificada (include 11/11, filtros 16/16, sort 7/7, enum de antique, operadores, params de detalle y códigos de respuesta).
- Suite total: **74 passed (576 assertions)**; `BlueprintApiTest`: **18 tests** (incluye la regresión).

## 11. Riesgos

- **R-1 (operativo, no bloqueante):** la base PostgreSQL de desarrollo está en `blueprints`=0. No afecta a esta revisión (las pruebas usan SQLite), pero conviene tenerlo presente antes de cualquier verificación manual contra PostgreSQL.
- **R-2 (contrato):** `filter[campo][op]` se documenta como parámetros explícitos porque OpenAPI 3.0.3 (`deepObject`) solo cubre un nivel. Riesgo ya aceptado en la revisión del contrato (Etapa 1).
- **R-3 (aislamiento):** la garantía depende de mantener `phpunit.xml` con `force="true"` + directivas `<server>`; si se edita, debe revalidarse con la sonda de `DB_HOST`.

## 12. Pendientes

- Revisión humana/independiente de la Etapa 2 y de las correcciones DP-1/DP-2 (esta IA **no** declara aprobada la etapa).
- Decisión sobre el estado vacío de `blueprints` en la base de desarrollo (fuera del alcance de esta tarea).

## 13. Preguntas para ChatGPT

1. ¿Se acepta el estado de la Etapa 2 como **CORREGIDO Y VALIDADO**, quedando únicamente la aprobación formal del revisor?
2. ¿Se desea que la descripción del OpenAPI incluya además la referencia explícita a los reportes de Etapa 2 en `docs/reports/`?
3. ¿Debe registrarse el estado `blueprints`=0 de la base de desarrollo como incidencia a resolver antes de una validación manual end-to-end?

## 14. Próximo paso recomendado

Revisión independiente del presente reporte y de `docs/reports/2026-10-22_revision_correctiva_api_blueprints.md`; si procede, aprobar formalmente la Etapa 2 sin más cambios de código.
