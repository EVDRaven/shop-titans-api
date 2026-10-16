# Reporte Técnico — Revisión Puntual y Final del BlueprintImporter (Etapa 2)

**Fecha:** 2026-10-13
**Autor:** Editor IA
**Estado:** IMPLEMENTADA — PENDIENTE DE REVISIÓN

## 1. Objetivo

Revisar el código implementado del `BlueprintImporter` (Etapa 2) contra el Excel fuente v26.9.1, sin modificar código ni ejecutar la importación contra PostgreSQL. Tres puntos de revisión:

1. Campos numéricos obligatorios: detectar vacíos/`---` y evaluar si el fallback a `0` es correcto.
2. Mapeo de columnas: verificar que las columnas del `BlueprintExcelReader` coinciden con los encabezados y datos reales.
3. Validación y rollback: confirmar que los errores de validación impiden escribir y que los errores de persistencia revierten toda la transacción.

## 2. Alcance

**Incluye:**
- Análisis directo del Excel `Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx` (hoja `Blueprints`, 1626 filas).
- Revisión estática de `BlueprintExcelReader`, `BlueprintRowData`, `BlueprintImportService` y las pruebas de integración.
- Verificación de headers de columna y conteo de nulos.

**Excluye:**
- Modificación de código, migraciones, modelos ni seeders.
- Ejecución de `import:blueprints` contra PostgreSQL.
- Resolución de D-1, D-2 o D-3.
- Ampliación del alcance a componentes o elements.

## 3. Estado inicial

- Implementación del BlueprintImporter Etapa 2 completada y validada con 17 tests (99 assertions).
- Excel fuente v26.9.1 disponible en la raíz del proyecto (1626 filas × 98 columnas).
- 5 catálogos ejecutados y validados en PostgreSQL.

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| `docs/reports/2026-10-13_revision_puntual_blueprint_importer.md` | Creado | Reporte de revisión puntual y final. |

No se modificó código fuente.

## 5. Hallazgos

### 5.1 Punto 1 — Campos numéricos obligatorios

**Resultado: ✅ Sin problemas. El fallback a `0` es correcto y no oculta errores.**

Se analizaron los 8 campos `NOT NULL` de la tabla `blueprints` en las 1626 filas:

| Campo | Columna | Vacíos | `---` | Total nulos | Valores `0` reales |
|---|---|---|---|---|---|
| `tier` | F | 0 | 0 | 0 | 0 |
| `gold_value` | G | 0 | 0 | 0 | 1 |
| `craft_time_seconds` | H | 0 | 0 | 0 | 0 |
| `merchant_xp` | K | 0 | 0 | 0 | 0 |
| `worker_xp` | M | 0 | 0 | 0 | 0 |
| `fusion_xp` | N | 0 | 0 | 0 | 0 |
| `favor` | O | 0 | 0 | 0 | 0 |
| `airship_power` | P | 0 | 0 | 0 | 11 |

**Evidencia:** Ninguna de las 1626 filas tiene valores vacíos o `"---"` en estos 8 campos. El operador `?? 0` en [BlueprintExcelReader.php](file:///home/draven/Documentos/shop-titans-api/src/app/Imports/BlueprintExcelReader.php#L160-L170) (método `parseRow`) nunca se activa con datos reales. Es una medida defensiva. Los valores `0` detectados en `gold_value` (1 fila) y `airship_power` (11 filas) son valores reales del Excel, no nulos convertidos.

**Nota adicional (energías):** Las 4 energías (`discount_energy`, `surcharge_energy`, `suggest_energy`, `speed_up_energy`) también son `NOT NULL`. Solo una fila tiene `"---"` en 3 de ellas: **"Fresh Spirit"** (fila 1584), que es un **Enchantment** y se excluye de la importación. Para los 1549 blueprints no-Enchantment, todas las energías tienen valores válidos.

**Corrección necesaria:** Ninguna.

---

### 5.2 Punto 2 — Mapeo de columnas

**Resultado: ✅ Correcto. Todas las columnas verificadas coinciden con los encabezados reales.**

| Grupo | Columnas | Verificación |
|---|---|---|
| Workers | R, S, T, U, V, W | R=`Required Worker ` (espacio final), S=`Worker Level`, T/V=`Required Worker`, U/W=`Worker Level`. ✅ |
| Recursos | Y–AJ | Headers son fórmulas `=IMAGE(...)`; el reader usa letras fijas que coinciden con el catálogo `resources` (Iron, Wood, Leather, Herbs, Steel, Ironwood, Fabric, Oils, Mana, Gems, Essence, Stardust). ✅ |
| Crafting Upgrades | BE–BN | BE/BF=`Crafting Upgrade 1`/`Crafts Needed` … BM/BN=`Crafting Upgrade 5`/`Crafts Needed`. ✅ |
| Starforged | BP–BY | BP/BQ=`Starforged Milestone 1`/`Crafts Needed` … BX/BY=`Starforged Milestone 5`/`Crafts Needed`. ✅ |
| Ascension | CA–CF | CA/CB=`Ascension Upgrade 1`/`Shards Needed` … CE/CF=`Ascension Upgrade 3`/`Shards Needed`. ✅ |
| Transcendence | CG–CL | CG/CH=`Transcendence Upgrade 1`/`Seals Needed` … CK/CL=`Transcendence Upgrade 3`/`Seals Needed`. ✅ |
| Estadísticas | AS–AX | AS=`ATK`, AT=`DEF`, AU=`HP`, AV=`EVA`, AW=`CRIT`, AX=`Mana Shield`. ✅ |
| Fecha CS | CS | Header: `Is Antique starting on (UTC)`. El reader usa la letra CS (no el nombre), por lo que el cambio de nombre (D-4) no tiene impacto. Muestra fila 2: `---`. ✅ |

**Nota menor:** El header de la columna R tiene un espacio final (`Required Worker `) distinto a T y V (`Required Worker`). No afecta porque el reader referencia por letra de columna, no por nombre de header.

**Corrección necesaria:** Ninguna.

---

### 5.3 Punto 3 — Validación y rollback

**Resultado: ✅ Correcto. La validación previa impide escribir y los errores de persistencia revierten toda la transacción.**

**Validación previa impide escribir:**

En [BlueprintImportService.php](file:///home/draven/Documentos/shop-titans-api/src/app/Services/BlueprintImportService.php#L30-L42), el servicio valida todas las filas antes de abrir cualquier transacción. Si hay errores, retorna `ImportResult::failure($errors)` sin tocar la base de datos:

```php
foreach ($rows as $index => $row) {
    $rowErrors = $this->validateRow($row, $index + 2);
    if ($rowErrors->isNotEmpty()) {
        $errors = $errors->merge($rowErrors);
        continue;
    }
    $validRows->push($row);
}
if ($errors->isNotEmpty()) {
    return ImportResult::failure($errors);
}
```

**Errores de persistencia revierten la transacción:**

Toda la escritura está dentro de `DB::transaction` en [BlueprintImportService.php](file:///home/draven/Documentos/shop-titans-api/src/app/Services/BlueprintImportService.php#L47-L71). Cualquier excepción es capturada por `catch (Throwable $e)` y Laravel revierte automáticamente toda la transacción:

```php
try {
    DB::transaction(function () use ($validRows, &$inserted, &$updated) {
        foreach ($validRows as $row) {
            $blueprint = $this->persistBlueprint($row, $wasCreated);
            $this->persistWorkers($blueprint, $row);
            // ... todas las relaciones 1:N
        }
    });
} catch (Throwable $e) {
    return ImportResult::failure(collect([...]));
}
```

**Evidencia en tests:**
- [test_import_fails_when_category_not_found](file:///home/draven/Documentos/shop-titans-api/src/tests/Feature/BlueprintImportServiceTest.php#L112-L121): categoría inexistente → `Blueprint::count() === 0`.
- [test_import_fails_when_worker_not_found](file:///home/draven/Documentos/shop-titans-api/src/tests/Feature/BlueprintImportServiceTest.php#L123-L134): worker inexistente → `Blueprint::count() === 0`.
- [test_import_rolls_back_on_write_error](file:///home/draven/Documentos/shop-titans-api/src/tests/Feature/BlueprintImportServiceTest.php#L136-L147): worker duplicado viola UNIQUE → `Blueprint::count() === 0` (todo revertido).

**Corrección necesaria:** Ninguna.

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| Los 8 campos obligatorios no tienen nulos en el Excel | Conteo de 1626 filas: 0 vacíos y 0 `---` | Confirmada |
| El fallback `?? 0` no altera datos reales | Nunca se activa (0 nulos en campos obligatorios) | Confirmada |
| El mapeo de columnas coincide con el Excel | Headers verificados para workers, recursos, 4 tablas de upgrades, estadísticas y CS | Confirmada |
| La validación previa impide escritura parcial | Tests de categoría/worker inexistente verifican `Blueprint::count() === 0` | Confirmada |
| El rollback revierte toda la transacción | Test de worker duplicado verifica `Blueprint::count() === 0` | Confirmada |

## 7. Discrepancias

D-1, D-2 y D-3 permanecen pendientes y fuera del alcance de esta revisión. No se detectaron nuevas discrepancias.

## 8. Decisiones

### Aprobadas (aplicadas)
- Excluir filas `Type = Enchantment`.
- Mantener 4 FK de elements en `null`.
- No importar `blueprint_components`.
- No modificar migraciones ni modelos.

### Rechazadas
- Ninguna en esta revisión.

## 9. Validaciones

```bash
# Análisis del Excel con phpspreadsheet (script temporal eliminado tras la revisión)
docker compose exec -T app php -d memory_limit=512M /var/www/html/storage/app/final_review.php
```
Resultado:
```text
=== PUNTO 1 ===
RESULTADO: TODOS LOS 8 CAMPOS OBLIGATORIOS TIENEN VALORES VALIDOS EN LAS 1626 FILAS

=== PUNTO 2 ===
Workers, Recursos, Crafting Upgrades, Starforged, Ascension, Transcendence, Estadísticas, CS: todos los headers coinciden.

Enchantments: 77
Blueprints no-Enchantment: 1549
```

```bash
# Suite de tests (sin cambios desde la implementación)
php artisan test
```
Resultado:
```text
Tests: 17 passed (99 assertions)
```

## 10. Resultado final

**No se detectaron problemas en ninguno de los tres puntos de revisión.**

1. **Campos numéricos obligatorios:** 0 nulos en 1626 filas. El fallback `?? 0` es defensivo y no altera datos.
2. **Mapeo de columnas:** Todas las columnas verificadas coinciden con los encabezados reales del Excel.
3. **Validación y rollback:** La validación previa impide escribir; la transacción global con `catch (Throwable)` revierte todo ante errores de persistencia.

El código del `BlueprintImporter` Etapa 2 es correcto y está listo para aprobación humana.

## 11. Riesgos

- Sin riesgos nuevos detectados en esta revisión.
- Continúan los riesgos ya documentados: `elements` en `null`, `blueprint_components` sin importar, y la necesidad de `memory_limit=512M` para el Excel completo.

## 12. Pendientes

- Aprobación humana para ejecutar `php artisan import:blueprints` contra PostgreSQL.
- Resolver D-1, D-2 y D-3 para etapas posteriores.

## 13. Preguntas para ChatGPT

Ninguna nueva. Las preguntas abiertas sobre D-1 (modelado de componentes), D-2 (Enchantments) y D-3 (elements) permanecen del reporte de implementación.

## 14. Próximo paso recomendado

1. Revisión humana del presente reporte.
2. Si se aprueba, ejecutar la importación de prueba contra staging:
   ```bash
   docker compose exec -T app php -d memory_limit=512M artisan import:blueprints "/var/www/html/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx"
   ```
3. Validar conteo: ~1549 blueprints importados.
