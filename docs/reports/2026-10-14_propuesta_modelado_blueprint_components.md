# Propuesta de Arquitectura — Modelado de Blueprint Components (D-1)

**Fecha:** 2026-10-14
**Autor:** Editor IA
**Estado:** PROPUESTA — PENDIENTE DE APROBACIÓN HUMANA

## 1. Objetivo

Analizar cómo representar correctamente los componentes de fabricación de los blueprints, que pueden ser **blueprints**, **Quest Components** o **Sigils**, manteniendo la integridad referencial de PostgreSQL y la compatibilidad con el modelo actual.

## 2. Estado actual del esquema

### 2.1 Tabla `blueprint_components`

Migración: [2026_08_28_100010_create_blueprint_components_table.php](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_08_28_100010_create_blueprint_components_table.php)

| Columna | Tipo | Restricción |
|---|---|---|
| `id` | bigint PK | auto-incremental |
| `blueprint_id` | FK → `blueprints.id` | `ON DELETE CASCADE` |
| `component_id` | FK → `blueprints.id` | `ON DELETE RESTRICT` |
| `quality_id` | FK → `qualities.id` | nullable, `ON DELETE SET NULL` |
| `amount` | unsignedInteger | NOT NULL |
| `UNIQUE(blueprint_id, component_id, quality_id)` | | |

Modelo: [BlueprintComponent.php](file:///home/draven/Documentos/shop-titans-api/src/app/Models/BlueprintComponent.php)

### 2.2 Problema (Discrepancia D-1)

`component_id` es una FK rígida hacia `blueprints.id`, pero **solo el 7.5% de los componentes referenciados son blueprints reales**. El resto proviene de otras hojas del Excel.

### 2.3 Datos verificados del Excel v26.9.1

Análisis directo de las columnas AL (Component 1) y AO (Component 2) en la hoja `Blueprints`:

| Categoría | Únicos | Referencias | % del total | Hoja de origen |
|---|---|---|---|---|
| **Blueprint** | 103 | 217 | 7.5% | `Blueprints` (Type ≠ Enchantment) |
| **Quest Component** | 66 | 2,424 | 83.6% | `Quest Components` |
| **Sigil** | 6 | 237 | 8.2% | `Full Moon Fusions` (Type = Special) |
| **Enchantment** (D-2) | 15 | 23 | 0.8% | `Blueprints` (Type = Enchantment) |
| **Total** | **190** | **2,901** | 100% | |

**0 componentes desconocidos.** Todas las 2,901 referencias se pueden clasificar en una de las 4 categorías.

### 2.4 Restricciones del modelo `blueprints`

Migración: [2026_08_28_100007_create_blueprints_table.php](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_08_28_100007_create_blueprints_table.php)

Campos `NOT NULL` sin valor por defecto (relevantes para la alternativa C):
- `tier` (unsignedTinyInteger)
- `gold_value` (unsignedBigInteger)
- `craft_time_seconds` (unsignedInteger)
- `merchant_xp`, `worker_xp`, `fusion_xp`, `favor`, `airship_power` (unsignedInteger)

Categorías existentes en `item_categories`: 40 categorías (Sword, Bow, Armor, etc.). **No existe** "Quest Component", "Sigil", "Special" ni "Enchantment".

## 3. Alternativas evaluadas

### 3.1 Alternativa A — Tabla de materiales unificada (`crafting_materials`)

Crear una nueva tabla que centralice todos los tipos de componentes, con un campo discriminador `material_type`.

**Cambios requeridos:**
1. Nueva tabla `crafting_materials`:
   - `id` (PK)
   - `name` (string, unique)
   - `material_type` (enum: `blueprint`, `quest_component`, `sigil`, `enchantment`)
   - `source_id` (bigint nullable — referencia al registro original, sin FK rígida para tablas cruzadas)
   - `tier` (unsignedTinyInteger nullable)
   - `gold_value` (unsignedBigInteger nullable)
   - timestamps
2. Modificar `blueprint_components.component_id` para que apunte a `crafting_materials.id` en lugar de `blueprints.id`.
3. Poblar `crafting_materials` con los 190 componentes únicos (103 blueprints + 66 quest components + 6 sigils + 15 enchantments).
4. Para los blueprints, `source_id` apunta a `blueprints.id` (FK opcional).

**Ventajas:**
- ✅ Integridad referencial estricta: `blueprint_components.component_id` → `crafting_materials.id` (FK válida).
- ✅ Una sola tabla para todos los tipos de componentes. Consultas simples.
- ✅ Los 103 blueprints que son componentes se enlazan a su registro original vía `source_id`.
- ✅ Fácil de extender si aparecen nuevos tipos de materiales.
- ✅ Resuelve D-2 automáticamente: los 15 enchantments se registran como `material_type = enchantment`.

**Desventajas:**
- ❌ Mayor cambio de esquema: tabla nueva + modificación de FK existente.
- ❌ Duplicación de datos: los nombres de blueprints existen en `blueprints` y `crafting_materials`.
- ❌ Requiere mantener sincronizados `blueprints` y `crafting_materials` cuando se actualicen blueprints.

### 3.2 Alternativa B — Relación polimórfica

Agregar un campo `component_type` a `blueprint_components` y quitar la FK rígida de `component_id`.

**Cambios requeridos:**
1. En `blueprint_components`:
   - Agregar `component_type` (enum: `blueprint`, `quest_component`, `sigil`, `enchantment`).
   - Quitar la FK constraint de `component_id` (mantener el índice).
   - `component_id` pasa a ser un bigint sin FK rígida.
2. Crear tablas `quest_components` y `sigils` (o `full_moon_fusions`) para almacenar los 66 Quest Components y 6 Sigils.
3. El modelo `BlueprintComponent` usa relación polimórfica (`morphTo`).

**Ventajas:**
- ✅ Flexible: soporta múltiples tipos sin tabla intermedia.
- ✅ Sin duplicación de datos: cada tipo tiene su tabla propia.
- ✅ Consultas por tipo son directas (`where component_type = 'quest_component'`).

**Desventajas:**
- ❌ **Pierde integridad referencial estricta**: `component_id` no puede tener FK a 3 tablas distintas. PostgreSQL no soporta FK polimórfica nativa.
- ❌ Requiere triggers o lógica de aplicación para garantizar que `component_id` exista en la tabla correcta.
- ❌ Consultas JOIN más complejas (requieren `CASE` o múltiples consultas).
- ❌ Riesgo de referencias huérfanas si se elimina un registro sin limpiar `blueprint_components`.

### 3.3 Alternativa C — Registros sintéticos en `blueprints`

Insertar los 66 Quest Components y 6 Sigils (y opcionalmente los 15 Enchantments) como filas "sintéticas" en la tabla `blueprints`, usando categorías especiales.

**Cambios requeridos:**
1. Agregar 2-3 categorías a `item_categories`: `Quest Component`, `Sigil` (y opcionalmente `Enchantment`).
2. Insertar los 66 Quest Components en `blueprints` con:
   - `name` = nombre del Quest Component
   - `category_id` = "Quest Component"
   - `tier` = derivado de la hoja `Quest Components`
   - `gold_value`, `craft_time_seconds`, `merchant_xp`, etc. = 0 (o valores de la hoja si existen)
3. Insertar los 6 Sigils similarmente con categoría "Sigil".
4. **No modificar** `blueprint_components`: la FK recursiva ya funciona porque ahora todos los componentes existen en `blueprints`.

**Ventajas:**
- ✅ **Cero cambios en el esquema** de `blueprint_components`. La FK recursiva funciona tal como está.
- ✅ La estrategia de dos pasadas (ya documentada) funciona sin modificaciones.
- ✅ El importador puede resolverte componentes por `name` contra `blueprints`.
- ✅ Todos los 2,901 componentes se resuelven, incluyendo los 23 enchantments (si se insertan como sintéticos).

**Desventajas:**
- ❌ **Contamina la tabla `blueprints`**: 72-87 filas que no son blueprints reales aparecen en consultas generales.
- ❌ Campos sin sentido: Quest Components no tienen `craft_time_seconds`, `merchant_xp`, `worker_xp`, etc. Se llenarían con `0`.
- ❌ La columna `name` tiene UNIQUE: si un Quest Component comparte nombre con un blueprint, hay conflicto. (Verificado: 0 conflictos en v26.9.1.)
- ❌ Consultas de blueprints reales requieren filtrar `WHERE category_id NOT IN (quest_component, sigil)`.
- ❌ Datos inconsistentes: los Quest Components tienen su propia hoja con `type`, `value`, `quest_area` que no caben en `blueprints`.

## 4. Comparativa de las alternativas

| Criterio | A. Tabla unificada | B. Polimórfica | C. Sintéticos |
|---|---|---|---|
| **Integridad referencial** | ✅ Estricta (FK a `crafting_materials`) | ❌ Sin FK estricta | ✅ Estricta (FK a `blueprints`) |
| **Cambio de esquema** | Alto (tabla nueva + modificar FK) | Medio (agregar columna + 2 tablas) | Bajo (solo seeders de categorías) |
| **Duplicación de datos** | Media (nombres en 2 tablas) | Ninguna | Ninguna (pero datos sin sentido) |
| **Consultas simples** | ✅ Una JOIN a `crafting_materials` | ❌ JOINs condicionales | ✅ JOIN a `blueprints` |
| **Mantenimiento** | Medio (sincronizar tablas) | Bajo | Alto (filtrar sintéticos en cada query) |
| **Compatibilidad con modelo actual** | Media (cambia la FK) | Baja (quita FK, agrega polimorfismo) | ✅ Alta (sin cambios en `blueprint_components`) |
| **Resuelve D-2** | ✅ Sí | ✅ Sí | ✅ Sí (si se insertan enchantments) |
| **Consistencia semántica** | ✅ Clara (`material_type`) | ✅ Clara (`component_type`) | ❌ Confusa (items que no son blueprints) |
| **Escalabilidad** | ✅ Fácil agregar tipos | ✅ Fácil agregar tipos | ❌ Requiere nuevas categorías |

## 5. Recomendación: Alternativa A — Tabla de materiales unificada

### Razones

1. **Integridad referencial es irrenunciable.** La alternativa B pierde la FK estricta, lo que contradice el principio del proyecto de mantener la integridad de PostgreSQL. La alternativa C la mantiene pero a costa de contaminar `blueprints`.

2. **La tabla `crafting_materials` es semánticamente correcta.** Los componentes de fabricación son una entidad distinta de los blueprints. Un Quest Component no es un blueprint; unirlos en `blueprints` (Alternativa C) genera confusión y campos sin significado.

3. **Una sola tabla simplifica consultas.** Para obtener los componentes de un blueprint, basta un JOIN a `crafting_materials`. No se necesitan CASE ni múltiples consultas como en B.

4. **Resuelve D-1 y D-2 simultáneamente.** Los 190 componentes únicos (incluyendo 15 enchantments) se registran en `crafting_materials` con su `material_type`. Las 2,901 referencias quedan todas resueltas.

5. **Escalable.** Si en el futuro aparecen nuevos tipos de materiales (ej. recursos de eventos), solo se agrega un valor al enum `material_type`.

### Cómo se conservan las 2,901 referencias

1. **Paso 1 — Poblar `crafting_materials`:** Insertar los 190 componentes únicos:
   - 103 blueprints (`material_type = 'blueprint'`, `source_id` = `blueprints.id`)
   - 66 quest components (`material_type = 'quest_component'`, `source_id` = null o FK a futura tabla `quest_components`)
   - 6 sigils (`material_type = 'sigil'`, `source_id` = null o FK a futura tabla `sigils`)
   - 15 enchantments (`material_type = 'enchantment'`, `source_id` = null o FK a futura tabla de enchantments)

2. **Paso 2 — Migrar `blueprint_components`:** Cambiar la FK de `component_id` para apuntar a `crafting_materials.id`. Re-mapear los `component_id` existentes (actualmente 0 filas, ya que no se importaron componentes) a los nuevos IDs de `crafting_materials`.

3. **Paso 3 — Importar componentes:** El importador resuelve cada nombre de componente contra `crafting_materials.name` y usa su `id`.

### Cambios requeridos

| Cambio | Tipo | Descripción |
|---|---|---|
| Nueva tabla `crafting_materials` | Migración | `id`, `name` (unique), `material_type` (enum), `source_id` (bigint nullable), `tier` (nullable), `gold_value` (nullable), timestamps |
| Modificar FK de `blueprint_components.component_id` | Migración | Apuntar a `crafting_materials.id` en lugar de `blueprints.id` |
| Modelo `CraftingMaterial` | Nuevo | Relaciones: `hasMany(BlueprintComponent::class)`, `morphTo()` para `source` |
| Modelo `BlueprintComponent` | Modificación | `component()` apunta a `CraftingMaterial` en lugar de `Blueprint` |
| Seeder `CraftingMaterialSeeder` | Nuevo | Insertar los 190 componentes desde las hojas `Blueprints`, `Quest Components` y `Full Moon Fusions` |
| Importador | Modificación | Resolver componentes contra `crafting_materials` en lugar de `blueprints` |

## 6. Consecuencias para D-2 (23 referencias a Enchantments)

D-2 se refiere a que 23 blueprints no-Enchantment usan componentes que son Enchantments (15 únicos). La recomendación (Alternativa A) **resuelve D-2 automáticamente**:

- Los 15 Enchantments se insertan en `crafting_materials` con `material_type = 'enchantment'`.
- Las 23 referencias en `blueprint_components` apuntan a estos registros.
- No se requiere importar los 77 Enchantments completos en `blueprints`.
- No se pierden datos: las 23 referencias quedan resueltas.

**Ventaja adicional:** Los Enchantments quedan aislados en `crafting_materials` sin contaminar `blueprints`, coherente con la decisión de excluirlos del MVP.

## 7. Riesgos conocidos

1. **Duplicación de nombres:** Los 103 blueprints que son componentes tendrán su nombre tanto en `blueprints` como en `crafting_materials`. Se mitiga con `source_id` que enlaza al registro original.

2. **Sincronización:** Si un blueprint cambia de nombre, hay que actualizar también `crafting_materials`. Se mitiga con un seeder idempotente que re-sincronice en cada importación.

3. **Migración de datos:** Actualmente `blueprint_components` está vacía (no se importaron componentes en Etapa 2), por lo que no hay datos que migrar. La migración de esquema es segura.

4. **`source_id` sin FK rígida:** Para que `source_id` pueda apuntar a `blueprints`, `quest_components` o `sigils`, no puede tener una FK única. Se recomienda usar `morphTo` de Eloquent y mantener la integridad vía lógica de aplicación o triggers.

## 8. Próximos pasos

1. **Aprobación humana** de la Alternativa A.
2. Crear migración para `crafting_materials` y modificación de `blueprint_components`.
3. Crear modelo `CraftingMaterial` y actualizar `BlueprintComponent`.
4. Crear seeder `CraftingMaterialSeeder` (idempotente) con los 190 componentes.
5. Implementar la pasada de componentes en el importador.
6. Resolver D-3 (elements) si se requiere para completar el modelo.

## 9. Archivos afectados (si se aprueba)

- `src/database/migrations/[fecha]_create_crafting_materials_table.php` (nuevo)
- `src/database/migrations/[fecha]_modify_blueprint_components_component_id.php` (nuevo)
- `src/app/Models/CraftingMaterial.php` (nuevo)
- `src/app/Models/BlueprintComponent.php` (modificar relación `component()`)
- `src/database/seeders/CraftingMaterialSeeder.php` (nuevo)
- `src/app/Imports/BlueprintExcelReader.php` (agregar parseo de componentes AL-AQ)
- `src/app/Services/BlueprintImportService.php` (agregar persistencia de componentes)

## 10. Referencias

- Análisis previo: [2026-10-13_analisis_blueprint_importer_mvp.md](file:///home/draven/Documentos/shop-titans-api/docs/reports/2026-10-13_analisis_blueprint_importer_mvp.md)
- Implementación Etapa 2: [2026-10-13_implementacion_blueprint_importer_etapa2.md](file:///home/draven/Documentos/shop-titans-api/docs/reports/2026-10-13_implementacion_blueprint_importer_etapa2.md)
- Migración actual: [2026_08_28_100010_create_blueprint_components_table.php](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_08_28_100010_create_blueprint_components_table.php)
- Modelo actual: [BlueprintComponent.php](file:///home/draven/Documentos/shop-titans-api/src/app/Models/BlueprintComponent.php)
- Excel fuente: `Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx`
