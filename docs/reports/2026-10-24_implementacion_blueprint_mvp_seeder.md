# Reporte Técnico — Implementación de `BlueprintMvpSeeder`

**Fecha:** 2026-10-24
**Autor:** Editor IA
**Estado:** IMPLEMENTADA — PENDIENTE DE REVISIÓN

## 1. Objetivo

Implementar un seeder orquestador (`BlueprintMvpSeeder`) que construya el conjunto de datos del MVP de Blueprints en una instalación nueva, coordinando las etapas existentes en su orden de dependencias real:

1. Seeders de catálogos.
2. Importación de blueprints (`import:blueprints`).
3. `CraftingMaterialSeeder`.
4. Importación de componentes (`import:blueprint-components`).

Deteniéndose ante el fallo de cualquier etapa y propagando los códigos de salida de los comandos de importación.

## 2. Alcance

**Incluye:**
- Nuevo `BlueprintMvpSeeder` como orquestador (sin duplicar lógica de importación/seed existente).
- Extracción del trait `ResolvesBlueprintExcelPath` para eliminar la duplicación de la resolución de la ruta del Excel.
- Ajuste de `CraftingMaterialSeeder` y `BlueprintCatalogSeeder` para resolver el `BlueprintExcelReader` por contenedor (`app(...)`) y usar el trait.
- Pruebas Feature del orquestador (orden, aplicación completa, fallos intermedios, propagación de errores, idempotencia).

**Excluye:**
- No se incorpora `CraftingMaterialSeeder` a `DatabaseSeeder` (EP-1).
- No se modifica el comportamiento de `User::factory()` ni se resuelve EP-3.
- No se alteran esquema, migraciones ni datos de PostgreSQL.
- No se ejecutan `db:seed` global, migraciones ni importadores contra PostgreSQL real.

## 3. Estado inicial

- `DatabaseSeeder` ejecutaba 9 seeders de catálogo y `User::factory()->create()`, sin `CraftingMaterialSeeder` y sin orquestación del MVP completo.
- La resolución de la ruta del Excel estaba duplicada entre `BlueprintCatalogSeeder` y `CraftingMaterialSeeder`.
- `CraftingMaterialSeeder` instanciaba el lector con `new BlueprintExcelReader` (no sustituible por contenedor en pruebas).
- Existían las etapas sueltas (`import:blueprints`, `import:blueprint-components`, `CraftingMaterialSeeder`) ya idempotentes, pero sin un punto de entrada único reproducible.
- Decisión aprobada EP-2: crear el seeder orquestador.

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| `src/database/seeders/BlueprintMvpSeeder.php` | Creado | Orquestador del MVP (entregable principal). |
| `src/database/seeders/Concerns/ResolvesBlueprintExcelPath.php` | Creado | Centraliza la resolución de la ruta del Excel (evita duplicación). |
| `src/tests/Feature/BlueprintMvpSeederTest.php` | Creado | Pruebas de orden, fallos intermedios, propagación e idempotencia. |
| `src/database/seeders/CraftingMaterialSeeder.php` | Modificado | Usa el trait y `app(BlueprintExcelReader::class)`; eliminado `resolveFilePath()` muerto. |
| `src/database/seeders/BlueprintCatalogSeeder.php` | Modificado | Usa el trait y `app(BlueprintExcelReader::class)`. |

`DatabaseSeeder.php` **no** se modificó (EP-1).

## 5. Cambios realizados

### 5.1 `BlueprintMvpSeeder`
- Ejecuta en orden: `$this->call([...9 seeders de catálogo...])` → `import:blueprints` → `CraftingMaterialSeeder` → `import:blueprint-components`.
- Resuelve la ruta del Excel una sola vez con `resolveBlueprintExcelPath()` y la propaga a las 4 etapas.
- Invoca el `CraftingMaterialSeeder` a través de `$this->resolve(CraftingMaterialSeeder::class)` + `$materialSeeder->filePath = $filePath` + `__invoke()`, reutilizando el mecanismo nativo de `Illuminate\Database\Seeder` (preserva el contenedor y el comando en curso).
- `runImportCommand()` usa `Artisan::call($command, ['file' => $filePath])`, compara contra `Command::SUCCESS` y lanza `RuntimeException` incluyendo el nombre del comando y el código de salida, lo que detiene las etapas posteriores.

### 5.2 Trait `ResolvesBlueprintExcelPath`
- Propiedad pública `?string $filePath = null` (permite fijar la ruta tanto en producción como en pruebas).
- Resolución: `$this->filePath` → ruta por defecto del Excel v26.9.1 → primer `*.xlsx` en `storage/app/` → `RuntimeException` si no se encuentra.

### 5.3 `CraftingMaterialSeeder` / `BlueprintCatalogSeeder`
- Adoptan el trait y resuelven el lector por contenedor (`app(BlueprintExcelReader::class)`), requisito para poder mockear en pruebas sin afectar producción.
- Se eliminó el método privado muerto `resolveFilePath()`.

## 6. Hípótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| Los comandos de importación devuelven código de salida (`SUCCESS`/`FAILURE`) | `ImportBlueprints::handle()` e `ImportBlueprintComponents::handle()` devuelven `self::FAILURE`/`self::SUCCESS` | Confirmada |
| Es posible ejecutar un seeder auxiliar fijando su entrada | `$this->resolve(...)` + `filePath` + `__invoke()` funciona en la prueba de orden | Confirmada |
| Las etapas existentes son idempotentes | `updateOrCreate`/`firstOrCreate` + delete-then-create acotado; prueba `test_it_is_idempotent_across_reruns` | Confirmada |
| Mockear `BlueprintExcelReader` vía contenedor es viable | `$this->mock(BlueprintExcelReader::class, ...)` intercepta todas las llamadas | Confirmada |
| `blueprint-components` falla si hay referencias sin resolver | Segunda lectura con `Ghost Sword` inexistente → componentes omitidos → exit 1 | Confirmada |

## 7. Discrepancias

Ninguna funcional.

- **Hallazgo de definición de parámetro:** la ruta del Excel no estaba centralizada y se resolvía de forma duplicada. Se documenta y se resuelve con el trait `ResolvesBlueprintExcelPath`, compatible con la configuración existente (`storage/app/*.xlsx`, incluida la ruta por defecto del v26.9.1). No requiere cambio de configuración.

## 8. Decisiones

### Propuestas
- Nombre `BlueprintMvpSeeder` (aprobado previamente como denominación provisional).

### Aprobadas
- **EP-1:** No incorporar `CraftingMaterialSeeder` aislado a `DatabaseSeeder`.
- **EP-2:** Crear el seeder orquestador reproducible para instalación nueva.

### Reemplazadas/Rechazadas
- **EP-3:** Tratamiento de `User::factory()` en `DatabaseSeeder`. Investigar por separado; **no resuelto en esta tarea**.

## 9. Validaciones

```bash
docker exec shoptitans-app sh -lc 'php -l <5 archivos>'
docker exec shoptitans-app sh -lc 'php artisan test --filter=BlueprintMvpSeederTest'
docker exec shoptitans-app sh -lc 'vendor/bin/pint --test'
docker exec shoptitans-app sh -lc 'php artisan test'
```

Resultado / exit code:

```text
php -l (5 archivos): No syntax errors detected            → exit 0
php artisan test --filter=BlueprintMvpSeederTest:
  Tests:    5 passed (31 assertions)                      → exit 0
vendor/bin/pint --test:
  PASS  103 files                                         → exit 0
php artisan test (suite completa, SQLite :memory:):
  Tests:    86 passed (626 assertions)                    → exit 0
  (CraftingMaterialSeederTest 10/10; BlueprintApiTest 19/19; sin regresiones)
GetDiagnostics: limpio en BlueprintMvpSeeder.php y BlueprintMvpSeederTest.php
```

Aislamiento: toda la suite corrió sobre SQLite `:memory:` (aislamiento forzado por `src/phpunit.xml` con `force="true"` + bloque `<server>`).

## 10. Resultado final

- Seeder orquestador implementado y funcionando en el entorno de pruebas.
- Orden de etapas verificado: `catálogos ×4 (lecturas) → import:blueprints → crafting_materials → import:blueprint-components`.
- Detención ante fallo de blueprints (solo se ejecutan los catálogos) y ante fallo de componentes (las etapas previas sí se aplican; los componentes no), con propagación del código de salida en el mensaje de excepción.
- Idempotencia confirmada en re-ejecución (conteos estables: 1 blueprint / 1 material / 1 componente en el escenario de prueba).
- Sin regresiones en la suite completa.

## 11. Riesgos

- **Ejecución real pendiente:** el orquestador no se ha ejecutado contra PostgreSQL real (prohibido en esta tarea). Su comportamiento en producción se infiere de las etapas subyacentes ya validadas.
- **Ruta del Excel:** si el archivo no está en `storage/app/`, el trait lanza `RuntimeException` antes de cualquier etapa. Se recomienda fijar la ruta explícita al ejecutar en un entorno nuevo.
- **`CraftingMaterialSeeder` no es un comando:** se invoca vía `resolve()/__invoke()`; su propagación de errores depende de excepciones, no de código de salida.
- **Memoria PHP:** las etapas de importación requieren `memory_limit=512M` (ya definido en la imagen); una ejecución fuera de la imagen podría fallar por memoria.
- **`User::factory()` (EP-3):** al no abordarse, `BlueprintMvpSeeder` deliberadamente no crea usuarios.

## 12. Pendientes

- Aprobación humana del propio orquestador (revisión independiente).
- EP-3: investigar por separado el tratamiento de `User::factory()` en `DatabaseSeeder`.
- Discrepancia documental EC-1 (`PROJECT_CONTEXT.md:31` afirma que seeders/importador/API «aún no implementados»).
- Puntos de aprobación humana de H-1..H-4 (§17.5 del informe de revisión independiente de la API).
- Eventual integración del orquestador como punto de entrada documentado para instalación nueva (sin `db:seed` global).

## 13. Preguntas para ChatGPT

- ¿Debe `BlueprintMvpSeeder` registrarse/documentarse como la vía oficial de instalación del MVP, o mantenerse como utilidad manual invocada con `--class`?
- ¿Conviene fijar la ruta del Excel por variable de entorno/configuración en lugar de la detección por `storage/app/*.xlsx`?

## 14. Próximo paso recomendado

Someter `BlueprintMvpSeeder` a revisión independiente (solo lectura) y, una vez aprobado, evaluar EP-3 por separado. No ejecutar el orquestador contra PostgreSQL real sin autorización explícita.
