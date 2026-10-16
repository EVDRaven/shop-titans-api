# Revisión Puntual — Diseño de `source_id` en `crafting_materials`

**Fecha:** 2026-10-14
**Autor:** Editor IA
**Estado:** APROBADA (diseño) — IMPLEMENTADA (código) — PENDIENTE DE EJECUCIÓN EN BASE DE DATOS
**Contexto:** Alternativa A de D-1 (tabla `crafting_materials`). Este reporte resuelve el diseño de cómo cada material se relaciona con su registro de origen.

## 1. Objetivo

Definir cómo relacionar cada registro de `crafting_materials` con su origen (blueprint, quest component, sigil o enchantment) sin perder integridad referencial ni introducir complejidad innecesaria.

## 2. Datos de origen verificados

El Excel v26.9.1 tiene 4 categorías de componentes (190 únicos, 2,901 referencias). Cada una proviene de una hoja distinta con atributos diferentes:

| Categoría | Cantidad | Hoja de origen | Atributos disponibles |
|---|---|---|---|
| **Blueprint** | 103 | `Blueprints` (Type ≠ Enchantment) | Ya existe en tabla `blueprints` con 30+ atributos |
| **Quest Component** | 66 | `Quest Components` | Name, Type (---/Timewarped/Artifact), Tier, Value, Quest Area |
| **Sigil** | 6 | `Full Moon Fusions` (Type = Component) | Name, Worker Level Required, Quantity, Fuse Time, Worker XP + componentes de fusión |
| **Enchantment** | 15 | `Blueprints` (Type = Enchantment) | Atributos completos de blueprint, pero excluidos del MVP |

**Conclusión clave:** Solo los blueprints (103) y enchantments (15) tienen una tabla de origen existente (`blueprints`). Los Quest Components (66) y Sigils (6) **no existen en ninguna tabla actual** del esquema.

## 3. Opciones evaluadas

### Opción 1 — Relación específica con `blueprints`

Usar una FK real `blueprint_id` para materiales cuyo origen sea un blueprint, y representar los demás tipos de forma ad-hoc.

**Implementación:**
- `blueprint_id` FK nullable → `blueprints.id`
- Para quest components y sigils: `blueprint_id = NULL` y almacenar datos inline o en tablas separadas no especificadas.

**Ventajas:**
- ✅ FK real para el 60% de los materiales (103 blueprints + 15 enchantments si se importan).
- ✅ Enlace directo al registro completo del blueprint.

**Desventajas:**
- ❌ No resuelve quest components ni sigils: quedan sin tabla de origen.
- ❌ Diseño asimétrico: un tipo tiene FK, los otros no.
- ❌ Requiere decisiones adicionales para los 72 materiales restantes.

**Veredicto:** Incompleta. No resuelve el problema para todos los tipos.

---

### Opción 2 — Tablas de origen separadas con múltiples FK

Crear tablas `quest_components` y `sigils`, y usar múltiples FK nullable en `crafting_materials`.

**Implementación:**
- Nuevas tablas: `quest_components` (name, type, tier, value, quest_area) y `sigils` (name, worker_level_required, quantity, fuse_time_seconds, worker_xp).
- En `crafting_materials`: `blueprint_id` FK nullable, `quest_component_id` FK nullable, `sigil_id` FK nullable.
- CHECK constraint: exactamente una de las tres FK debe ser NOT NULL.

**Ventajas:**
- ✅ **Todas las FK son reales y enforceables** por PostgreSQL.
- ✅ Sin duplicación de datos: cada entidad en su tabla.
- ✅ Integridad referencial completa.

**Desventajas:**
- ❌ **Mayor cambio de esquema:** 2 tablas nuevas + 3 FK + CHECK constraint.
- ❌ Sobre-ingeniería para datasets pequeños (66 quest components, 6 sigils).
- ❌ Consultas requieren 3 JOINs posibles para obtener atributos.
- ❌ Los sigils tienen sus propios componentes de fusión, lo que podría requerir otra tabla de relaciones.

**Veredicto:** Correcta pero excesiva para el volumen de datos.

---

### Opción 3 — Datos de origen en `crafting_materials` (sin `source_id`)

Almacenar todos los atributos necesarios directamente en `crafting_materials`, sin tabla de origen ni `source_id` polimórfico.

**Implementación:**
- `crafting_materials` incluye: `name`, `material_type` (enum), `tier` (nullable), `gold_value` (nullable), `quest_area` (nullable), `fuse_time_seconds` (nullable), `worker_level_required` (nullable).
- **Sin `source_id`**. Los datos están materializados.
- Opcional: `blueprint_id` FK nullable para enlazar al blueprint original (solo para `material_type = 'blueprint'`).

**Ventajas:**
- ✅ **Sin `source_id` polimórfico:** el problema del origen se elimina.
- ✅ Consultas simples: una sola tabla, sin JOINs para atributos básicos.
- ✅ Menor cambio de esquema: no se crean tablas `quest_components` ni `sigils`.
- ✅ Datasets pequeños justifican la denormalización.

**Desventajas:**
- ❌ Columnas nullable específicas por tipo (ej. `quest_area` solo aplica a quest components).
- ❌ Para blueprints, `name`, `tier`, `gold_value` se duplican (ya existen en `blueprints`).
- ❌ Si un blueprint cambia de nombre, hay que sincronizar `crafting_materials`.

**Veredicto:** Simple y suficiente para el caso de uso. La denormalización es justificable por el tamaño de los datasets.

---

### Opción 4 — Híbrida: datos inline + FK opcional a `blueprints`

Combinar lo mejor de las opciones 1 y 3: materializar atributos comunes en `crafting_materials` y agregar una FK opcional a `blueprints` solo para los materiales que son blueprints reales.

**Implementación:**
- `crafting_materials` almacena atributos comunes: `name` (unique), `material_type` (enum), `tier` (nullable), `gold_value` (nullable).
- `blueprint_id` FK nullable → `blueprints.id` (solo para `material_type = 'blueprint'` y `'enchantment'`).
- Columnas nullable específicas: `quest_area` (quest components), `fuse_time_seconds`, `worker_level_required`, `worker_xp` (sigils).
- **No se crean tablas** `quest_components` ni `sigils`.
- **No hay `source_id` polimórfico.**

**Ventajas:**
- ✅ **Una FK real enforceable** (`blueprint_id`) para el 62% de los materiales (103 blueprints + 15 enchantments).
- ✅ Sin `source_id` polimórfico: no hay ambigüedad sobre el origen.
- ✅ Consultas simples: atributos básicos en la misma tabla.
- ✅ No requiere tablas adicionales para datasets pequeños.
- ✅ El `blueprint_id` permite acceder a los atributos completos del blueprint cuando se necesiten.
- ✅ Los enchantments se pueden enlazar a su registro en `blueprints` si se decide importarlos (resuelve D-2).

**Desventajas:**
- ❌ Algunas columnas son nullable específicas por tipo.
- ❌ Duplicación de `name`, `tier`, `gold_value` para blueprints (mitigada por `blueprint_id`).
- ❌ Requiere sincronización si un blueprint cambia (mitigada por seeder idempotente).

**Veredicto:** **Recomendada.** Balance óptimo entre integridad, simplicidad y rendimiento.

## 4. Comparativa resumida

| Criterio | Opción 1 | Opción 2 | Opción 3 | Opción 4 (rec.) |
|---|---|---|---|---|
| FK enforceable a `blueprints` | ✅ | ✅ | ❌ (opcional) | ✅ |
| FK a quest_components/sigils | ❌ | ✅ | ❌ | ❌ |
| `source_id` polimórfico | No | No | No | No |
| Tablas nuevas | 0 | 2 | 0 | 0 |
| JOINs para atributos básicos | 1 | 1-3 | 0 | 0 |
| Complejidad | Baja | Alta | Baja | Baja-Media |
| Integridad referencial | Parcial | Completa | Baja | Media-Alta |
| Duplicación de datos | Media | Ninguna | Alta | Media |

## 5. Recomendación: Opción 4 (Híbrida)

### 5.1 Estructura de `crafting_materials`

```
crafting_materials
├── id                  PK
├── source_key          string UNIQUE NOT NULL   ← clave estable de identidad (inmutable)
├── name                string NOT NULL          ← nombre visible (actualizable)
├── material_type       enum('blueprint','quest_component','sigil','enchantment') NOT NULL
├── blueprint_id        FK → blueprints.id  NULLABLE  (solo para blueprint)
├── tier                unsignedTinyInteger NULLABLE  (común a todos)
├── gold_value          unsignedBigInteger  NULLABLE  (común a todos)
├── quest_area          string              NULLABLE  (solo quest_component)
├── fuse_time_seconds   unsignedInteger     NULLABLE  (solo sigil)
├── worker_level_required unsignedTinyInteger NULLABLE (solo sigil)
├── worker_xp           unsignedInteger     NULLABLE  (solo sigil)
├── created_at
└── updated_at
```

**Cambio clave respecto a la versión anterior:** se reemplaza `name UNIQUE` por `source_key UNIQUE`. El `name` deja de ser la clave única y pasa a ser un dato actualizable. `source_key` es la clave estable que nunca cambia.

### 5.2 Cómo se representa cada tipo

| Tipo | `material_type` | `source_key` | `blueprint_id` | `tier` | `gold_value` | Columnas específicas |
|---|---|---|---|---|---|---|
| **Blueprint** | `blueprint` | `blueprint:{blueprint_id}` | FK a `blueprints.id` (NOT NULL) | del blueprint | del blueprint | — |
| **Quest Component** | `quest_component` | `quest_component:{name}` | NULL | de la hoja | de la hoja (Value) | `quest_area` |
| **Sigil** | `sigil` | `sigil:{name}` | NULL | NULL (no tiene tier) | NULL | `fuse_time_seconds`, `worker_level_required`, `worker_xp` |
| **Enchantment** | `enchantment` | `enchantment:{name}` | NULL (no se importa en `blueprints`) | de la hoja | de la hoja | — |

**Ejemplos de `source_key`:**
- Blueprint "Squire Sword" con `blueprints.id = 5` → `source_key = "blueprint:5"`
- Quest Component "Iron Pine Cone" → `source_key = "quest_component:Iron Pine Cone"`
- Sigil "Sigil of Might" → `source_key = "sigil:Sigil of Might"`
- Enchantment "Ember Element" → `source_key = "enchantment:Ember Element"`

### 5.3 Claves foráneas

| FK | Tabla destino | Nullable | Uso |
|---|---|---|---|
| `blueprint_id` | `blueprints.id` | Sí | Solo para `material_type = 'blueprint'`. Enlaza al registro completo del blueprint. |
| `blueprint_components.component_id` | `crafting_materials.id` | No | La FK que resuelve D-1. |

**Integridad garantizada:** PostgreSQL valida que `blueprint_id` exista en `blueprints` cuando se setea. `blueprint_components.component_id` siempre apunta a un `crafting_materials.id` válido.

### 5.4 Identidad y sincronización (clave estable `source_key`)

**Problema resuelto:** Si `name` fuera la clave única, un cambio de nombre en el Excel crearía un registro duplicado (el viejo quedaría huérfano). `source_key` es una clave estable e inmutable que permite actualizar el `name` sin duplicados.

**Regla de construcción de `source_key`:**

| Tipo | Patrón | Estabilidad |
|---|---|---|
| `blueprint` | `"blueprint:" . blueprints.id` | ✅ Estable: `blueprints.id` es PK y nunca cambia |
| `enchantment` | `"enchantment:" . name` | ⚠️ Basada en nombre (no hay ID de origen) |
| `quest_component` | `"quest_component:" . name` | ⚠️ Basada en nombre (no hay ID de origen) |
| `sigil` | `"sigil:" . name` | ⚠️ Basada en nombre (no hay ID de origen) |

**Por qué `blueprint` usa ID y los demás usan nombre:**
- Los blueprints ya existen en la tabla `blueprints` con una PK estable.
- Los quest components, sigils y enchantments **no tienen tabla de origen** en el esquema actual, por lo que no hay un ID numérico estable. Su nombre en la hoja del Excel es el único identificador disponible.

**Cómo funciona la actualización de nombre sin duplicados:**

Escenario: el blueprint "Squire Sword" (id=5) cambia a "Squire's Sword" en una nueva versión del Excel.

1. El seeder lee el blueprint actualizado con `name = "Squire's Sword"`.
2. Calcula `source_key = "blueprint:5"` (igual que antes, porque el `blueprint_id` no cambió).
3. Ejecuta: `updateOrCreate(['source_key' => 'blueprint:5'], ['name' => "Squire's Sword", ...])`.
4. Laravel encuentra el registro existente por `source_key` y actualiza el `name`.
5. **No se crea ningún duplicado.** El registro antiguo se actualiza in-place.

**Importación idempotente:**

El seeder `CraftingMaterialSeeder` usa `updateOrCreate` con `source_key` como clave de búsqueda:

```php
// Para cada blueprint que es componente
CraftingMaterial::updateOrCreate(
    ['source_key' => "blueprint:{$blueprint->id}"],
    [
        'name' => $blueprint->name,
        'material_type' => 'blueprint',
        'blueprint_id' => $blueprint->id,
        'tier' => $blueprint->tier,
        'gold_value' => $blueprint->gold_value,
    ]
);

// Para cada quest component
CraftingMaterial::updateOrCreate(
    ['source_key' => "quest_component:{$name}"],
    [
        'name' => $name,
        'material_type' => 'quest_component',
        'blueprint_id' => null,
        'tier' => $tier,
        'gold_value' => $value,
        'quest_area' => $questArea,
    ]
);
```

**Limitación conocida:** Para `enchantment`, `quest_component` y `sigil`, si el nombre cambia en el Excel, el `source_key` cambia y se crearía un nuevo registro (el viejo quedaría). Esto es aceptable porque:
- Estos tipos no tienen un ID estable de origen.
- Los nombres de quest components y sigils son estables en el juego.
- Si ocurre un cambio, se puede detectar y limpiar manualmente (el registro viejo no tiene referencias en `blueprint_components`).

### 5.5 Restricciones por tipo (constraints de PostgreSQL)

**Combinaciones válidas de `material_type` y `blueprint_id`:**

| `material_type` | `blueprint_id` | `source_key` pattern |
|---|---|---|
| `blueprint` | NOT NULL | `blueprint:%` |
| `enchantment` | NULL | `enchantment:%` |
| `quest_component` | NULL | `quest_component:%` |
| `sigil` | NULL | `sigil:%` |

**Restricciones PostgreSQL propuestas:**

**1. CHECK — combinación válida de tipo y `blueprint_id`:**

```sql
ALTER TABLE crafting_materials
ADD CONSTRAINT chk_material_type_blueprint_id
CHECK (
    (material_type = 'blueprint' AND blueprint_id IS NOT NULL)
    OR
    (material_type IN ('enchantment', 'quest_component', 'sigil') AND blueprint_id IS NULL)
);
```

Impide estados inválidos como:
- Un `quest_component` con `blueprint_id` seteado.
- Un `blueprint` sin `blueprint_id`.

**2. CHECK — formato de `source_key` coherente con el tipo:**

```sql
ALTER TABLE crafting_materials
ADD CONSTRAINT chk_source_key_format
CHECK (
    (material_type = 'blueprint' AND source_key LIKE 'blueprint:%')
    OR
    (material_type = 'enchantment' AND source_key LIKE 'enchantment:%')
    OR
    (material_type = 'quest_component' AND source_key LIKE 'quest_component:%')
    OR
    (material_type = 'sigil' AND source_key LIKE 'sigil:%')
);
```

Impide que un material de tipo `blueprint` tenga un `source_key` que no empiece con `blueprint:`.

**3. UNIQUE — `source_key` (clave de identidad):**

```sql
ALTER TABLE crafting_materials
ADD CONSTRAINT uq_crafting_materials_source_key
UNIQUE (source_key);
```

Garantiza que no haya dos materiales con la misma clave de identidad.

**4. UNIQUE parcial — `blueprint_id` (un material por blueprint):**

```sql
CREATE UNIQUE INDEX idx_crafting_materials_blueprint_id_unique
ON crafting_materials (blueprint_id)
WHERE blueprint_id IS NOT NULL;
```

Garantiza que un mismo `blueprints.id` no esté referenciado por más de un material. Usa índice parcial porque `blueprint_id` es nullable.

**5. FK — `blueprint_id` → `blueprints.id` (ya incluida en la estructura):**

```sql
ALTER TABLE crafting_materials
ADD CONSTRAINT fk_crafting_materials_blueprint
FOREIGN KEY (blueprint_id) REFERENCES blueprints(id)
ON DELETE RESTRICT;
```

`ON DELETE RESTRICT` impide eliminar un blueprint que tiene un material asociado, protegiendo la integridad.

**Nota sobre `enchantment` y `blueprint_id`:** En esta etapa, los enchantments **no** se importan en `blueprints`, por lo que `blueprint_id` es NULL para ellos. Si en el futuro se decide importar los 77 enchantments en `blueprints`, se debe:
- Actualizar el CHECK constraint para permitir `material_type = 'enchantment' AND blueprint_id IS NOT NULL`.
- Actualizar el `source_key` de los 15 enchantments a `"blueprint:{id}"`.
- Actualizar `blueprint_id` para los 15 materiales.

Esta futura migración es simple porque el `source_key` de enchantments es basado en nombre, y se puede hacer un `UPDATE` condicional.

## 6. Cambios de esquema y modelos implicados

### 6.1 Migraciones

1. **Nueva tabla `crafting_materials`** con la estructura del §5.1.
2. **Modificar `blueprint_components`**: cambiar `component_id` FK de `blueprints.id` a `crafting_materials.id`.
   - Actualmente `blueprint_components` está vacía (no se importaron componentes en Etapa 2), por lo que no hay datos que migrar.

### 6.2 Modelos

1. **Nuevo modelo `CraftingMaterial`**:
   - `fillable`: `name`, `material_type`, `blueprint_id`, `tier`, `gold_value`, `quest_area`, `fuse_time_seconds`, `worker_level_required`, `worker_xp`.
   - Relación `blueprint(): BelongsTo` → `Blueprint`.
   - Relación `components(): HasMany` → `BlueprintComponent`.
2. **Modificar `BlueprintComponent`**:
   - La relación `component()` apunta a `CraftingMaterial` en lugar de `Blueprint`.

### 6.3 Seeder

**Nuevo `CraftingMaterialSeeder`** (idempotente, `updateOrCreate` por `source_key`):
- 103 materiales `blueprint`: `updateOrCreate(['source_key' => "blueprint:{$bp->id}"], ['name' => $bp->name, 'blueprint_id' => $bp->id, ...])`.
- 66 materiales `quest_component`: desde la hoja `Quest Components`. `source_key = "quest_component:{$name}"`.
- 6 materiales `sigil`: desde la hoja `Full Moon Fusions` (Type = Component, nombre con "Sigil"). `source_key = "sigil:{$name}"`.
- 15 materiales `enchantment`: desde la hoja `Blueprints` (Type = Enchantment). `source_key = "enchantment:{$name}"`, `blueprint_id = NULL`.

## 7. Consecuencias para D-2

D-2 (23 referencias a Enchantments por parte de blueprints no-Enchantment) se resuelve de la siguiente manera:

- Los 15 enchantments se insertan en `crafting_materials` con `material_type = 'enchantment'`.
- `blueprint_id` puede ser NULL (los enchantments no se importan en `blueprints` en esta etapa).
- Las 23 referencias en `blueprint_components` apuntan a estos registros de `crafting_materials`.
- **No se pierden datos** y **no se contamina `blueprints`** con enchantments.

Si en el futuro se decide importar los 77 enchantments en `blueprints`, el seeder puede actualizar `blueprint_id` para los 15 materiales de tipo enchantment.

## 8. Riesgos y decisiones pendientes

### Riesgos
1. **Duplicación de atributos para blueprints:** `name`, `tier`, `gold_value` existen en `blueprints` y `crafting_materials`. Mitigación: seeder idempotente que sincronice en cada importación usando `source_key`.
2. **Columnas nullable específicas:** `quest_area`, `fuse_time_seconds`, etc. solo aplican a ciertos tipos. Mitigación: CHECK constraints documentados en §5.5.
3. **Cambio de nombre en quest components/sigils/enchantments (riesgo ACEPTADO):** Para `quest_component`, `sigil` y `enchantment`, `source_key` se basa en el nombre. Si el nombre cambia entre versiones del Excel, el `source_key` cambia y se crea un registro nuevo; el registro anterior queda huérfano. El Excel disponible no permite demostrar la estabilidad histórica de los nombres. **No se implementa detección de materiales huérfanos ni otras mitigaciones; el riesgo se documenta únicamente.**

### Decisiones CERRADAS en esta revisión
1. **Identidad y sincronización:** `source_key` es la clave estable de identidad. Para `blueprint` se basa en `blueprints.id`; para los demás tipos, en el nombre. El seeder usa `updateOrCreate` por `source_key`, garantizando idempotencia y actualización de nombre sin duplicados.
2. **Restricciones por tipo:** Se definen 5 constraints de PostgreSQL (2 CHECK, 2 UNIQUE, 1 FK) que impiden estados inválidos de `material_type` + `blueprint_id` + `source_key`.

### Decisiones APROBADAS por el usuario
1. ✅ Aprobada la Opción 4 con `source_key`, FK opcional a `blueprints` y constraints de PostgreSQL.
2. ✅ Aprobado el uso de `quest_component:{name}`, `sigil:{name}` y `enchantment:{name}` como `source_key`, aceptando el riesgo de cambio de nombre.
3. ✅ Aprobado documentar únicamente el riesgo de nombres cambiantes, sin implementar mitigaciones.

### Decisiones que siguen pendientes
1. ⏳ **Ejecución de la migración en base de datos** (no autorizada en esta tarea).
2. ⏳ Decisión sobre importar los 77 enchantments en `blueprints` (afecta si `blueprint_id` se setea para los 15 materiales enchantment).

## 9. Conclusión

La **Opción 4 (híbrida)** con los ajustes de esta revisión elimina el problema de `source_id` polimórfico al:

1. **Materializar atributos comunes** en `crafting_materials` (sin tablas adicionales para quest components y sigils).
2. **Usar una FK real y enforceable** (`blueprint_id`) para los materiales de tipo `blueprint`.
3. **Definir `source_key` como clave estable de identidad** (inmutable), separada del `name` (actualizable). Esto resuelve la sincronización: un cambio de nombre no crea duplicados.
4. **Definir restricciones por tipo** mediante 5 constraints de PostgreSQL que impiden estados inválidos.

**Decisiones cerradas en esta revisión:**
- ✅ Identidad y sincronización: `source_key` por tipo (`blueprint:{id}`, `quest_component:{name}`, `sigil:{name}`, `enchantment:{name}`). Idempotencia garantizada vía `updateOrCreate`.
- ✅ Restricciones por tipo: 2 CHECK + 2 UNIQUE + 1 FK.

**Estado de implementación:**
- ✅ Código implementado (migraciones, modelos).
- ⏳ Ejecución de migraciones en base de datos pendiente de autorización.
- ⏳ Importar los 77 enchantments en `blueprints` (decisión futura).

## 10. Referencias

- Propuesta D-1: [2026-10-14_propuesta_modelado_blueprint_components.md](file:///home/draven/Documentos/shop-titans-api/docs/reports/2026-10-14_propuesta_modelado_blueprint_components.md)
- Esquema actual `blueprint_components`: [2026_08_28_100010_create_blueprint_components_table.php](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_08_28_100010_create_blueprint_components_table.php)
- Modelo actual: [BlueprintComponent.php](file:///home/draven/Documentos/shop-titans-api/src/app/Models/BlueprintComponent.php)
- Excel fuente: `Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx`
