# Reporte Técnico — Implementación BlueprintImporter (Etapa 2)

**Fecha:** 2026-10-13
**Autor:** Editor IA
**Estado:** IMPLEMENTADA — PENDIENTE DE REVISIÓN

## 1. Objetivo

Implementar el comando Artisan `php artisan import:blueprints {file}` que lee la hoja `Blueprints` del Excel v26.9.1 e importa los blueprints del MVP (datos principales, workers, resources y las 4 tablas de mejoras) a PostgreSQL. Excluye filas `Enchantment` y deja las 4 FK de elementos en `null`. No importa `blueprint_components` en esta etapa.

## 2. Alcance

**Incluye:**
- Lectura del Excel v26.9.1 con `phpoffice/phpspreadsheet`.
- Exclusión de filas con `Type = Enchantment`.
- Conversión de `"---"` y vacíos a `null` (o `0` para energías).
- Resolución de `category_id`, `worker_id`, `resource_id` contra catálogos existentes.
- Persistencia en transacción global: `blueprints`, `blueprint_workers`, `blueprint_resources`, `blueprint_crafting_upgrades`, `blueprint_starforged_milestones`, `blueprint_ascension_upgrades`, `blueprint_transcendence_upgrades`.
- Idempotencia: `updateOrCreate` por `name` para blueprints; `delete-then-insert` para relaciones 1:N.
- Las 4 FK de elementos quedan en `null`.
- Registro de errores de validación con fila, blueprint y motivo.
- Rollback de la transacción ante errores de escritura.
- Pruebas unitarias y de integración con fixture reducido.

**Excluye:**
- Importación de `blueprint_components` (requiere resolver D-1 y D-2).
- Modificación de migraciones ni modelos existentes.
- Poblado de `elements` (D-3).
- Ampliación a Skills o Enchantments.
- Ejecución real contra PostgreSQL (requiere aprobación humana).

## 3. Estado inicial

- Propósito del BlueprintImporter aprobado para implementación (Etapa 2).
- 5 catálogos ejecutados y validados en PostgreSQL: resources=12, qualities=5, item_categories=40, workers=19, worker_levels=50.
- `phpoffice/phpspreadsheet ^5.10` disponible en `composer.json`.
- Discrepancias D-1, D-2, D-3 documentadas y fuera de alcance.

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| `src/app/Imports/BlueprintRowData.php` | Creado | DTO readonly con `toBlueprintAttributes()` |
| `src/app/Imports/ImportResult.php` | Creado | Value object de resultado de importación |
| `src/app/Imports/BlueprintExcelReader.php` | Creado | Lector del Excel con mapeo de 98 columnas |
| `src/app/Services/BlueprintImportService.php` | Creado | Persistencia con transacción, idempotencia y rollback |
| `src/app/Console/Commands/ImportBlueprints.php` | Creado | Comando Artisan `import:blueprints {file}` |
| `src/tests/Unit/BlueprintExcelReaderTest.php` | Creado | 9 tests unitarios del lector |
| `src/tests/Feature/BlueprintImportServiceTest.php` | Creado | 6 tests de integración del servicio |
| `src/tests/fixtures/blueprints_test.xlsx` | Creado | Fixture Excel reducido (3 filas: 2 válidas + 1 Enchantment) |
| `docker/php/Dockerfile` | Modificado | Agregadas `libzip-dev libpng-dev libjpeg-dev libfreetype6-dev` y extensiones `zip` `gd` |
| `src/composer.json` | Modificado | Agregado `phpoffice/phpspreadsheet: ^5.10` |

## 5. Cambios realizados

### 5.1 Arquitectura en 4 capas

1. **`BlueprintExcelReader`** — Lee el Excel, mapea columnas por letra (A..CS), convierte `"---"`/vacío a `null`, excluye `Enchantment` y retorna `Collection<BlueprintRowData>`.
2. **`BlueprintRowData`** — DTO readonly inmutable. `toBlueprintAttributes()` retorna el array para `Blueprint::updateOrCreate`, excluyendo `category_id` (resuelto aparte) y los 4 campos de elements (quedan `null` por omisión).
3. **`BlueprintImportService`** — `import(Collection $rows): ImportResult`. Carga catálogos en memoria con `keyBy()` para lookups O(1). Valida todas las filas antes de escribir; si hay errores, retorna fallo sin tocar la BD. Si la validación pasa, ejecuta `DB::transaction` con `updateOrCreate` por `name` y `delete-then-insert` para cada relación 1:N.
4. **`ImportBlueprints`** — Comando Artisan que orquesta reader → service y muestra métricas/errores.

### 5.2 Manejo de datos ambiguos

- `"---"` y celdas vacías → `null`.
- Energías (`discount_energy`, `surcharge_energy`, `suggest_energy`, `speed_up_energy`) → `null` coalesce a `0` (campos `unsignedInteger default(0)`).
- Campos numéricos `NOT NULL` (tier, gold_value, craft_time_seconds, merchant_xp, worker_xp, fusion_xp, favor, airship_power) → `null` coalesce a `0`.
- Recursos con cantidad `<= 0` → no se incluyen en `blueprint_resources`.
- Slots de worker vacíos → no se incluyen en `blueprint_workers`.
- Mejoras/milestones sin nombre ni cantidad → no se incluyen.

### 5.3 Idempotencia

- `Blueprint::updateOrCreate(['name' => $row->name], $attributes)`.
- Para cada relación 1:N: `$blueprint->relacion()->delete()` seguido de `create()` por cada elemento.
- Repetir la importación actualiza el blueprint y reemplaza todas sus relaciones sin dejar huérfanos.

### 5.4 Transacciones y rollback

- Toda la escritura va dentro de `DB::transaction`.
- Si una fila tiene errores de validación (categoría/worker/resource inexistente), **no se escribe nada** y se retornan los errores con fila, blueprint y motivo.
- Si ocurre un error de escritura (excepción dentro de la transacción), Laravel revierte automáticamente y el servicio retorna `ImportResult::failure` con el mensaje de la excepción.

### 5.5 Fix de tipado estricto (PHP 8.4)

Se detectó y corrigió un error de tipo: la variable `$wasCreated` se pasaba por referencia a `persistBlueprint()` sin inicializar. PHP 8.4 rechaza `null` para un parámetro tipado `bool`. Fix: `$wasCreated = false;` antes de la llamada.

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| `phpoffice/phpspreadsheet` puede leer el Excel completo sin agotar memoria | Reader probado con `php -d memory_limit=512M` | Confirmada |
| Worker keys en catálogo están capitalizados (`Blacksmith`, no `blacksmith`) | Seeder `WorkerSeeder` | Confirmada (requirió corrección de fixture/tests) |
| `updateOrCreate` + `delete-then-insert` garantiza idempotencia | Test `import is idempotent` y `removes obsolete relations` | Confirmada |
| Validación previa evita escritura parcial ante errores | Tests `fails when category/worker not found` | Confirmada |
| Rollback funciona ante error de constraint UNIQUE | Test `rolls back on write error` (worker duplicado) | Confirmada |
| Tipado estricto PHP 8.4 requiere inicializar variables por referencia | Error `Argument #2 ($wasCreated) must be of type bool, null given` | Confirmada y corregida |

## 7. Discrepancias

### D-1 — `blueprint_components.component_id` no resuelve el 91.7% de los componentes
- **Estado:** Pendiente. Fuera de alcance de esta etapa.
- **Impacto:** No se importa `blueprint_components` hasta resolver el modelado (Quest Components y Sigils no son blueprints).

### D-2 — 23 blueprints no-Enchantment dependen de componentes Enchantment
- **Estado:** Pendiente. Fuera de alcance.
- **Impacto:** Sin resolución de D-1/D-2, estos 23 blueprints quedarán sin componentes importados.

### D-3 — `elements` sin poblar (101 valores únicos)
- **Estado:** Pendiente. Las 4 FK (`elemental_affinity_id`, `spirit_affinity_id`, `built_in_element_id`, `built_in_spirit_id`) se guardan en `null`.
- **Impacto:** Blueprints importados sin afinidades elementales. Requiere decisión de modelado de `elements`.

## 8. Decisiones

### Aprobadas (aplicadas en esta etapa)
- Excluir filas `Type = Enchantment`.
- Mantener 4 FK de elementos en `null`.
- No importar `blueprint_components`.
- No modificar migraciones ni modelos.
- Conservar los 5 catálogos existentes sin cambios.
- No ejecutar importación real contra PostgreSQL.

### Propuestas (pendientes)
- Resolver D-1: definir modelado para componentes que no son blueprints (Quest Components, Sigils).
- Resolver D-2: estrategia para los 23 blueprints con dependencias Enchantment.
- Resolver D-3: modelado y poblado de `elements`.

## 9. Validaciones

```bash
# Sintaxis PHP
for f in app/Imports/BlueprintRowData.php app/Imports/ImportResult.php app/Imports/BlueprintExcelReader.php app/Services/BlueprintImportService.php app/Console/Commands/ImportBlueprints.php; do php -l "$f"; done
```
Resultado:
```text
No syntax errors detected in app/Imports/BlueprintRowData.php
No syntax errors detected in app/Imports/ImportResult.php
No syntax errors detected in app/Imports/BlueprintExcelReader.php
No syntax errors detected in app/Services/BlueprintImportService.php
No syntax errors detected in app/Console/Commands/ImportBlueprints.php
```

```bash
# Estilo de código
vendor/bin/pint --test app/Imports app/Services app/Console/Commands/ImportBlueprints.php tests/Unit/BlueprintExcelReaderTest.php tests/Feature/BlueprintImportServiceTest.php
```
Resultado:
```text
PASS  7 files
```

```bash
# Suite de tests completa
php artisan test
```
Resultado:
```text
PASS  Tests\Unit\BlueprintExcelReaderTest (9 tests)
PASS  Tests\Unit\ExampleTest
PASS  Tests\Feature\BlueprintImportServiceTest (6 tests)
PASS  Tests\Feature\ExampleTest

Tests: 17 passed (99 assertions)
Duration: 1.12s
```

```bash
# Comando Artisan registrado
php artisan list | grep import
```
Resultado:
```text
import:blueprints  Importa blueprints del Excel v26.9.1 al MVP (sin componentes).
```

## 10. Resultado final

Implementación completada y validada con tests (17/17 pasan, 99 assertions). El comando `import:blueprints` está registrado y funcional. **No se ha ejecutado contra PostgreSQL real** por restricción del protocolo.

## 11. Riesgos

- **Memory limit:** El Excel completo (1626 filas × 98 cols) requiere `memory_limit=512M`. El comando no lo fija internamente; se recomienda `php -d memory_limit=512M artisan import:blueprints ...`.
- **`elements` en null:** Todos los blueprints importados tendrán afinidades elementales nulas hasta resolver D-3.
- **Componentes sin importar:** Los blueprints quedan sin `blueprint_components` hasta resolver D-1/D-2.
- **Worker keys sensibles a mayúsculas:** La resolución usa `worker_key` exacto. Si el Excel usa minúsculas, fallará la validación.

## 12. Pendientes

- Aprobación humana para ejecutar `php artisan import:blueprints` contra PostgreSQL.
- Resolver D-1, D-2 y D-3 para etapas posteriores (componentes + elements).
- Agregar `memory_limit` interno en el comando o documentar el requisito.

## 13. Preguntas para ChatGPT

- ¿Es correcto el approach de `delete-then-insert` para idempotencia en relaciones 1:N, o se prefiere `sync`/`upsert`?
- ¿Debe el comando fijar `memory_limit=512M` internamente, o se documenta como requisito del operador?
- ¿Qué estrategia se recomienda para D-1 (componentes que no son blueprints): tabla polimórfica, tabla separada de Quest Components, o FK nullable?

## 14. Próximo paso recomendado

1. Revisión humana del código y del presente reporte.
2. Ejecución de importación de prueba contra una base de staging (con volcado previo):
   ```bash
   docker compose exec -T app php -d memory_limit=512M artisan import:blueprints "/var/www/html/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx"
   ```
3. Validación de conteos: `blueprints` ≈ 1549 filas (1626 - 77 Enchantment).
4. Si la importación de prueba es exitosa, proceder con la resolución de D-1, D-2 y D-3 en etapas sucesivas.
