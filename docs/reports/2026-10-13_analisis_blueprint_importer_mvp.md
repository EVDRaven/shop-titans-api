# Reporte Técnico — Análisis de Diseño del BlueprintImporter (MVP Blueprints)

**Fecha:** 2026-10-13
**Autor:** Editor IA
**Estado:** PROPUESTA — PENDIENTE DE REVISIÓN

---

## 1. Objetivo

Definir el diseño de importación de la hoja `Blueprints` del Excel v26.9.1 a las tablas existentes del MVP, **sin implementar código todavía**. El análisis cubre lectura/transformación, mapeo de catálogos, relaciones, componentes recursivos, transacciones, idempotencia y casos especiales.

Fuente de datos: `Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx` (hoja `Blueprints`, 1626 filas × 98 columnas).

---

## 2. Alcance

### Incluye
- Análisis de las 14 tablas del MVP Blueprints y sus relaciones.
- Mapeo columna Excel → campo PostgreSQL.
- Estrategia de procesamiento en dos pasadas para `blueprint_components`.
- Estrategia de transacciones, errores e idempotencia.
- Identificación de discrepancias entre el esquema actual y los datos reales.

### Excluye
- Implementación del importador (código PHP).
- Ejecución de importaciones o modificaciones a la base de datos.
- Modificación de migraciones, modelos o seeders.
- Modelado de `elements` (pendiente de decisión).
- Ampliación del MVP a Skills o Enchantments.

---

## 3. Estado inicial

### 3.1 Esquema existente (14 tablas del MVP)

Las migraciones están ejecutadas y validadas en PostgreSQL (ver `docs/reports/2026-08-28_migrate_real_mvp_blueprints.md`). Las tablas relevantes para el importador son:

| Tabla | Descripción | Clave única |
|---|---|---|
| `blueprints` | Tabla principal | `name` UNIQUE |
| `blueprint_workers` | Workers requeridos + nivel | UNIQUE(`blueprint_id`, `worker_id`) |
| `blueprint_resources` | Recursos + cantidad | UNIQUE(`blueprint_id`, `resource_id`) |
| `blueprint_components` | Componentes (FK recursiva) | UNIQUE(`blueprint_id`, `component_id`, `quality_id`) |
| `blueprint_crafting_upgrades` | 5 niveles | UNIQUE(`blueprint_id`, `upgrade_level`) |
| `blueprint_starforged_milestones` | 5 milestones | UNIQUE(`blueprint_id`, `milestone_number`) |
| `blueprint_ascension_upgrades` | 3 niveles | UNIQUE(`blueprint_id`, `upgrade_level`) |
| `blueprint_transcendence_upgrades` | 3 niveles | UNIQUE(`blueprint_id`, `upgrade_level`) |

### 3.2 Catálogos poblados (seeders ejecutados y validados)

| Catálogo | Registros | Clave natural |
|---|---|---|
| `resources` | 12 | `name`, `column_letter_in_excel` |
| `qualities` | 5 | `name`, `rank` |
| `item_categories` | 40 (excluye `Enchantment`) | `name` |
| `workers` | 19 | `worker_key` |
| `worker_levels` | 50 | `level` |
| `elements` | **0** (pendiente de decisión) | `name` |

### 3.3 Restricción crítica del esquema actual

`blueprint_components.component_id` es una **FK hacia `blueprints.id` con ON DELETE RESTRICT**. Esta decisión arquitectónica se tomó asumiendo que todos los componentes son blueprints. **El análisis de datos demuestra que esta suposición es incorrecta** (ver §7 Discrepancia D-1).

---

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| `docs/reports/2026-10-13_analisis_blueprint_importer_mvp.md` | Creado | Reporte de análisis del diseño del importador. |
| `AI_WORK_LOG.md` | Actualizado | Estado del análisis y próximo paso. |

No se modificó código fuente, migraciones, modelos ni seeders.

---

## 5. Mapeo columnas Excel → tablas/campos PostgreSQL

### 5.1 `blueprints` (tabla principal)

| Col Excel | Letra | Campo PostgreSQL | Tipo | Transformación |
|---|---|---|---|---|
| Name | A | `name` | string | UNIQUE. Sin transformación. |
| Type | B | `category_id` | FK → `item_categories` | Buscar por `name`. **Saltar fila si Type = `Enchantment`** (fuera del MVP). |
| Unlock Prerequisite | C | `unlock_prerequisite` | varchar nullable | `"---"` o vacío → `null`. Valor literal conservado. |
| Research Scrolls | D | `research_scrolls` | unsignedInteger nullable | `"---"` → `null`. |
| Antique Tokens | E | `antique_tokens` | unsignedInteger nullable | `"---"` → `null`. |
| Tier | F | `tier` | unsignedTinyInteger | Valor numérico directo (1..16). |
| Value | G | `gold_value` | unsignedBigInteger | Valor numérico directo. |
| Crafting Time (seconds) | H | `craft_time_seconds` | unsignedInteger | Valor numérico directo. |
| Crafting Time (formatted) | I | `craft_time_formatted` | string nullable | Texto directo (`"00:00:15"`). |
| Value / Crafting Time | J | `value_per_second` | decimal(14,4) nullable | Valor numérico directo. |
| Merchant XP | K | `merchant_xp` | unsignedInteger | Valor numérico directo. |
| Merchant XP / Crafting Time | L | `merchant_xp_per_second` | decimal(14,4) nullable | Valor numérico directo. |
| Worker XP | M | `worker_xp` | unsignedInteger | Valor numérico directo. |
| Fusion XP | N | `fusion_xp` | unsignedInteger | Valor numérico directo. |
| Favor | O | `favor` | unsignedInteger | Valor numérico directo. |
| Airship Power | P | `airship_power` | unsignedInteger | Valor numérico directo. |
| ATK | AS | `atk` | integer nullable | `"---"` → `null`. |
| DEF | AT | `def` | integer nullable | `"---"` → `null`. |
| HP | AU | `hp` | integer nullable | `"---"` → `null`. |
| EVA | AV | `eva` | integer nullable | `"---"` → `null`. |
| CRIT | AW | `crit` | integer nullable | `"---"` → `null`. |
| Mana Shield | AX | `mana_shield` | integer nullable | `"---"` → `null`. |
| Elemental Affinity | AZ | `elemental_affinity_id` | FK → `elements` nullable | **PENDIENTE** (elements sin poblar). |
| Spirit Affinity | BA | `spirit_affinity_id` | FK → `elements` nullable | **PENDIENTE**. |
| Built-In Element | BB | `built_in_element_id` | FK → `elements` nullable | **PENDIENTE**. |
| Built-In Spirit | BC | `built_in_spirit_id` | FK → `elements` nullable | **PENDIENTE**. |
| Discount Energy | CN | `discount_energy` | unsignedInteger | Default 0 si `"---"`. |
| Surcharge Energy | CO | `surcharge_energy` | unsignedInteger | Default 0 si `"---"`. |
| Suggest Energy | CP | `suggest_energy` | unsignedInteger | Default 0 si `"---"`. |
| Speed Up Energy | CQ | `speed_up_energy` | unsignedInteger | Default 0 si `"---"`. |
| Is Antique starting on (UTC) | CS | `available_as_antique_at` | timestamp nullable | `"---"` → `null`. Parsear fecha. |

### 5.2 `blueprint_workers` (1:N, máx. 3 workers por BP)

| Col Excel | Letra | Campo | Notas |
|---|---|---|---|
| Required Worker (1) | R | `worker_id` | FK → `workers.worker_key`. |
| Worker Level (1) | S | `level_required` | `"---"` → skip. |
| Required Worker (2) | T | `worker_id` | Mismo patrón. |
| Worker Level (2) | U | `level_required` | |
| Required Worker (3) | V | `worker_id` | Mismo patrón. |
| Worker Level (3) | W | `level_required` | |

**Validación:** los 19 workers referenciados existen en el catálogo. 0 referencias huérfanas detectadas.

### 5.3 `blueprint_resources` (1:N, 12 recursos fijos)

Las columnas Y-AJ **no tienen header**. El mapeo se hace por `resources.column_letter_in_excel`:

| Letra | Recurso | Campo |
|---|---|---|
| Y | Iron | `resource_id` + `quantity` |
| Z | Wood | |
| AA | Leather | |
| AB | Herbs | |
| AC | Steel | |
| AD | Ironwood | |
| AE | Fabric | |
| AF | Oils | |
| AG | Mana | |
| AH | Gems | |
| AI | Essence | |
| AJ | Stardust | |

**Regla:** solo insertar fila si `quantity > 0` y distinto de `"---"`.

### 5.4 `blueprint_components` (1:N, máx. 2 componentes por BP) — ⚠️ DISCREPANCIA D-1

| Col Excel | Letra | Campo | Notas |
|---|---|---|---|
| Component (1) | AL | `component_id` | **FK a `blueprints.id` — NO resuelve el 91.7% de los casos.** |
| Component Quality (1) | AM | `quality_id` | `"---"` → `null`. |
| Amount Needed (1) | AN | `amount` | `"---"` → skip. |
| Component (2) | AO | `component_id` | |
| Component Quality (2) | AP | `quality_id` | |
| Amount Needed (2) | AQ | `amount` | |

### 5.5 Tablas de upgrades (todas 1:N)

| Tabla | Columnas Excel | Campos |
|---|---|---|
| `blueprint_crafting_upgrades` | BE-BN (5 niveles × 2 cols) | `upgrade_level` (1..5), `name`, `crafts_required` |
| `blueprint_starforged_milestones` | BP-BY (5 milestones × 2 cols) | `milestone_number` (1..5), `name`, `crafts_required` |
| `blueprint_ascension_upgrades` | CA-CF (3 niveles × 2 cols) | `upgrade_level` (1..3), `name`, `shards_required` |
| `blueprint_transcendence_upgrades` | CG-CL (3 niveles × 2 cols) | `upgrade_level` (1..3), `name`, `seals_required` |

**Dato:** los 1626 blueprints tienen los 16 niveles de upgrades completos (5 crafting + 5 starforged + 3 ascension + 3 transcendence).

---

## 6. Flujo propuesto de importación

```
┌─────────────────────────────────────────────────────────────┐
│                    BlueprintImporter                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  0. PRE-VALIDACIÓN                                          │
│     - Cargar catálogos en memoria (resources, qualities,    │
│       item_categories, workers) para lookups O(1).          │
│     - Verificar que elements esté poblado (o omitir).       │
│                                                             │
│  1. LECTURA DEL EXCEL                                       │
│     - Abrir hoja Blueprints con openpyxl (data_only=True).  │
│     - Iterar filas 2..1627.                                 │
│     - Saltar filas con Type = 'Enchantment'.                │
│     - Construir DTO intermedio por fila.                    │
│                                                             │
│  2. TRANSFORMACIÓN Y VALIDACIÓN POR FILA                    │
│     - Convertir '---' → null según campo.                   │
│     - Resolver category_id, worker_ids, resource_ids.       │
│     - Acumular errores por fila (no abortar).               │
│                                                             │
│  3. PASADA 1 — INSERTAR blueprints base                     │
│     - updateOrCreate por 'name' (idempotente).              │
│     - SIN insertar blueprint_components todavía.            │
│     - Orden: por tier ASC (componentes son de tier ≤ padre).│
│                                                             │
│  4. PASADA 2 — INSERTAR relaciones 1:N                      │
│     - blueprint_workers, blueprint_resources.               │
│     - blueprint_crafting_upgrades, starforged, ascension,   │
│       transcendence.                                        │
│     - blueprint_components (requiere que todos los BP       │
│       existan de la pasada 1).                              │
│     - Estrategia: delete-then-insert por blueprint_id o     │
│       updateOrCreate por clave única compuesta.             │
│                                                             │
│  5. REPORTE DE RESULTADOS                                   │
│     - Conteos: blueprints insertados/actualizados,          │
│       workers, resources, components, upgrades.             │
│     - Lista de filas con errores y su motivo.               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### 6.1 Estructura intermedia (DTO)

Propuesta de clase `BlueprintRowData` (o arreglo asociativo) que representa una fila del Excel ya parseada:

```
BlueprintRowData:
  name: string
  category_name: string|null
  unlock_prerequisite: string|null
  research_scrolls: int|null
  antique_tokens: int|null
  tier: int
  gold_value: int
  craft_time_seconds: int
  craft_time_formatted: string|null
  value_per_second: float|null
  merchant_xp: int
  merchant_xp_per_second: float|null
  worker_xp: int
  fusion_xp: int
  favor: int
  airship_power: int
  stats: {atk, def, hp, eva, crit, mana_shield} (int|null)
  elements: {elemental_affinity, spirit_affinity, built_in_element, built_in_spirit} (string|null)
  energies: {discount, surcharge, suggest, speed_up} (int)
  available_as_antique_at: DateTime|null
  workers: [{worker_key, level_required}] (1..3)
  resources: [{resource_name, quantity}] (solo quantity > 0)
  components: [{component_name, quality_name, amount}] (0..2)
  crafting_upgrades: [{level, name, crafts_required}] (5)
  starforged_milestones: [{number, name, crafts_required}] (5)
  ascension_upgrades: [{level, name, shards_required}] (3)
  transcendence_upgrades: [{level, name, seals_required}] (3)
```

---

## 7. Discrepancias

### DISCREPANCIA D-1 — `blueprint_components.component_id` no resuelve el 91.7% de los componentes

**Estado documentado:** `blueprint_components.component_id` es FK a `blueprints.id` (ON DELETE RESTRICT), asumiendo que todos los componentes son blueprints.

**Estado real:** De 190 componentes únicos referenciados en la hoja Blueprints:
- 118 (62%) son blueprints (240 referencias, 8.3% del total).
- 66 (35%) son **Quest Components** (materiales de quest, 2424 referencias, 83.6%).
- 6 (3%) son **ítems especiales** "Sigil of..." de la hoja Full Moon Fusions (237 referencias, 8.2%).

```
Componentes únicos: 190
├── Blueprints:              118  (240 refs,  8.3%)
├── Quest Components:         66  (2424 refs, 83.6%)
└── Sigils (Full Moon Fns):    6  (237 refs,   8.2%)
```

**Evidencia:** Los 66 Quest Components están en la hoja `Quest Components` (columnas Name, Type, Tier, Value, Quest Area). Ejemplos: `Iron Pine Cone`, `Elven Wood`, `Silver Dust`, `Precious Gem`. Los 6 Sigils están en `Full Moon Fusions` (Type = "Special").

**Impacto:** Con el esquema actual, el importador solo podría insertar 240 de 2901 referencias de componentes. El 91.7% de los datos de componentes se perdería o generaría errores de FK.

**Propuesta (requiere aprobación):** Tres opciones:

- **Opción A — Tabla de materiales unificada:** Crear una tabla `crafting_materials` (o similar) que contenga blueprints, quest components y special items con un campo `source_type`. Modificar `blueprint_components.component_id` para que apunte a esta tabla. **Mayor cambio de esquema.**

- **Opción B — FK polimórfica:** Agregar `component_type` (enum: `blueprint`, `quest_component`, `special`) y `component_id` sin FK rígida, o usar FK condicional. **Más flexible pero rompe integridad referencial estricta.**

- **Opción C — Importar Quest Components y Sigils como blueprints "sintéticos":** Insertar los 66 quest components y 6 sigils en `blueprints` con un `category_id` especial (ej. "Quest Component" / "Special Item") y `tier` derivado de su hoja de origen. Así la FK recursiva funciona sin cambiar el esquema. **Menor cambio, pero contamina la tabla `blueprints` con ítems que no son blueprints reales.**

**Requiere aprobación:** Sí. Esta decisión bloquea la implementación de `blueprint_components`.

---

### DISCREPANCIA D-2 — 23 blueprints no-Enchantment dependen de componentes Enchantment

**Estado documentado:** Las filas con Type = `Enchantment` (77 filas) deben excluirse del MVP.

**Estado real:** 23 blueprints no-Enchantment usan componentes que son Enchantment blueprints (ej. `Ember Element`, `Blaze Element`, `Tide Element`). Si se excluyen las filas Enchantment, estas 23 referencias quedan huérfanas.

**Evidencia:** 15 componentes únicos de tipo Enchantment son referenciados 23 veces por blueprints no-Enchantment.

**Impacto:** Si se excluyen Enchantments y se usa la FK recursiva actual, 23 referencias de componentes fallan.

**Propuesta (requiere aprobación):**
- **Opción A:** Incluir los 77 Enchantments en `blueprints` (con category_id = null o una categoría especial) para que la FK funcione, aunque no sean del MVP funcional.
- **Opción B:** Omitir las 23 referencias de componentes Enchantment durante la importación (pérdida de datos parcial).
- **Opción C:** Resolver junto con la Discrepancia D-1 (si se unifican materiales, los Enchantments también forman parte).

**Requiere aprobación:** Sí.

---

### DISCREPANCIA D-3 — `elements` sin poblar (4 FK pendientes)

**Estado documentado:** `elements` es una tabla vacía (0 registros) pendiente de decisión de modelado.

**Estado real:** Las columnas AZ-BC tienen 101 valores únicos, incluyendo elementos básicos (`Fire`, `Water`...), combinaciones (`Air, Dark`), espíritus animales (`Bear Vitality`) y nombres de elementos (`Ember Element`). No se puede insertar `blueprints` con las 4 FK de elements resueltas hasta que se decida cómo normalizar estos valores.

**Impacto:** Los 4 campos de afinidad/elemento (`elemental_affinity_id`, `spirit_affinity_id`, `built_in_element_id`, `built_in_spirit_id`) se insertarían como `null` si no se resuelve `elements`.

**Propuesta:** Mantener `elements` como pendiente. Durante la importación, los 4 campos se dejan en `null` hasta que se decida el modelado. Alternativamente, crear un seeder `ElementSeeder` con los 101 valores únicos como catálogo plano (sin normalización adicional).

**Requiere aprobación:** Sí (ya registrado como pendiente en `AI_WORK_LOG.md`).

---

### DISCREPANCIA D-4 — Columna CS cambió de nombre en v26.9.1

**Estado documentado:** La columna CS se llamaba "Available as an Antique starting on (UTC)".

**Estado real:** En v26.9.1 el header es "Is Antique starting on (UTC)". Mismo significado, sin impacto en el esquema (campo `available_as_antique_at`).

**Propuesta:** Ningún cambio de esquema. El importador debe referenciar la columna por letra (CS), no por nombre de header.

**Requiere aprobación:** No.

---

## 8. Estrategia de componentes recursivos (dos pasadas)

### 8.1 Problema

`blueprint_components.component_id` referencia a otro `blueprints.id`. Si el blueprint "Gladius" (fila 4) tiene como componente "Iron Pine Cone", este debe existir en `blueprints` antes de insertar la fila en `blueprint_components`.

### 8.2 Estrategia de dos pasadas

**Pasada 1:** Insertar todos los `blueprints` base (sin `blueprint_components`), ordenados por `tier ASC`. Los componentes son siempre de tier menor o igual al del padre.

**Pasada 2:** Insertar `blueprint_components` resolviendo `component_id` por nombre contra el mapa `name → id` construido en la pasada 1.

### 8.3 Limitación actual

Esta estrategia solo funciona si **todos los componentes son blueprints**. Como se demostró en D-1, el 91.7% de los componentes NO son blueprints. Por lo tanto, la estrategia de dos pasadas es **necesaria pero no suficiente** hasta que se resuelva D-1.

### 8.4 Orden de inserción general (dependencias)

```
1. blueprints (todos, sin components)         ← Pasada 1
2. blueprint_workers                           ← Pasada 2
3. blueprint_resources                         ← Pasada 2
4. blueprint_crafting_upgrades                 ← Pasada 2
5. blueprint_starforged_milestones             ← Pasada 2
6. blueprint_ascension_upgrades                ← Pasada 2
7. blueprint_transcendence_upgrades            ← Pasada 2
8. blueprint_components                        ← Pasada 2 (último, requiere todos los BP)
```

---

## 9. Transacciones y manejo de errores

### 9.1 Estrategia de transacciones

- **Transacción única por blueprint:** cada blueprint y sus relaciones 1:N se insertan dentro de una transacción (`DB::transaction`). Si falla algo, se hace rollback de ese blueprint y se continúa con el siguiente.
- **No abortar toda la importación por una fila:** los errores se acumulan y se reportan al final.
- **Transacción global opcional:** si se prefiere "todo o nada", envolver toda la importación en una transacción. Riesgo: lock prolongado en PostgreSQL para 1549 blueprints.

### 9.2 Detección de errores

| Tipo de error | Acción |
|---|---|
| `Type` no existe en `item_categories` | Saltar fila, registrar error. |
| `Type = Enchantment` | Saltar fila (decisión MVP). |
| Worker no encontrado en `workers` | Registrar error, omitir ese worker. |
| Recurso con `column_letter_in_excel` no mapeado | Error crítico (no debería ocurrir). |
| Componente no resuelto (D-1) | Depende de la decisión de D-1. |
| Quality no encontrada | `quality_id = null`. |
| Valor numérico inválido | Registrar error, usar `null` si el campo lo permite. |
| Fecha inválida en CS | Registrar error, `available_as_antique_at = null`. |

### 9.3 Reporte de errores

Al finalizar, generar un reporte con:
- Número de filas procesadas.
- Número de blueprints insertados/actualizados.
- Número de filas saltadas (Enchantment + errores).
- Lista de errores agrupados por tipo con ejemplos (nombre del blueprint, fila del Excel, motivo).

---

## 10. Idempotencia

### 10.1 Estrategia `updateOrCreate`

- **`blueprints`:** `updateOrCreate(['name' => $dto->name], $attributes)`. La clave `name` es UNIQUE.
- **`blueprint_workers`:** `updateOrCreate(['blueprint_id' => $bpId, 'worker_id' => $workerId], ['level_required' => $level])`.
- **`blueprint_resources`:** `updateOrCreate(['blueprint_id' => $bpId, 'resource_id' => $resourceId], ['quantity' => $qty])`.
- **`blueprint_components`:** `updateOrCreate(['blueprint_id' => $bpId, 'component_id' => $compId, 'quality_id' => $qualityId], ['amount' => $amount])`.
- **Upgrades:** `updateOrCreate` por `blueprint_id` + `upgrade_level`/`milestone_number`.

### 10.2 Manejo de relaciones eliminadas

Si un blueprint tenía recursos/componentes en una importación anterior y en la nueva ya no los tiene, el `updateOrCreate` no elimina las filas huérfanas. **Estrategia recomendada:** antes de insertar las relaciones 1:N de un blueprint, eliminar las existentes (`BlueprintResource::where('blueprint_id', $bpId)->delete()`) y luego insertar. Esto garantiza que el estado de la BD coincida exactamente con el Excel.

### 10.3 Re-ejecución segura

El importador debe poder ejecutarse múltiples veces sin duplicar datos. La combinación de `updateOrCreate` (para blueprints) + `delete-then-insert` (para relaciones 1:N) garantiza idempotencia.

---

## 11. Casos especiales

### 11.1 Categoría `Enchantment` excluida

- 77 filas con Type = `Enchantment` deben saltarse.
- **Problema derivado (D-2):** 23 blueprints no-Enchantment dependen de componentes Enchantment.

### 11.2 Valor `"---"` como NULL lógico

Convención consistente en todo el Excel:
- Columnas escalares nullable: `"---"` → `null`.
- Columnas de stats: `"---"` → `null`.
- `blueprint_resources`: `"---"` o `0` → no insertar fila.
- `blueprint_components`: `"---"` en Component → no insertar fila.
- `blueprint_workers`: `"---"` en Required Worker → no insertar fila.
- Energías: `"---"` → `0` (default en migración).
- Fecha CS: `"---"` → `null`.

### 11.3 `unlock_prerequisite` con tipos mixtos

445 valores únicos, incluyendo workers, packs, offers, chests, content passes, tutorial. Se conserva el valor literal (varchar nullable, sin FK).

### 11.4 Campos opcionales

- `craft_time_formatted`, `value_per_second`, `merchant_xp_per_second`: derivables pero persistidos.
- 6 stats: todas nullable.
- 4 elementos: todas nullable (hasta resolver D-3).
- `available_as_antique_at`: nullable.

---

## 12. Pruebas propuestas

### 12.1 Unit tests

1. **Parseo de fila:** dado un array de valores del Excel, verificar que el DTO se construye correctamente (conversión `---` → null, tipos numéricos, fechas).
2. **Resolución de catálogos:** verificar que `category_name` → `category_id`, `worker_key` → `worker_id`, `resource_name` → `resource_id` funcionan.
3. **Idempotencia:** ejecutar el importador dos veces con los mismos datos y verificar que no hay duplicados.
4. **Exclusión de Enchantment:** verificar que las 77 filas Enchantment no se insertan.
5. **Manejo de errores:** fila con Type inválido → se registra error y no se inserta.

### 12.2 Feature tests

1. **Importación completa en SQLite :memory::** importar un subconjunto de filas (ej. 10 blueprints) y verificar conteos exactos.
2. **Dos pasadas de componentes:** crear un blueprint A que tiene componente B (ambos en el set de prueba) y verificar que `blueprint_components` se inserta correctamente.
3. **Transacciones:** simular un error en la inserción de relaciones y verificar que el blueprint base no queda huérfano.
4. **Reporte de errores:** verificar que el reporte final contiene la lista de filas con errores.

### 12.3 Verificación en PostgreSQL (post-implementación)

1. Conteo de blueprints importados (debe ser 1626 − 77 Enchantment = 1549, sujeto a decisión D-2).
2. Verificación de FKs: no debe haber `blueprint_components` con `component_id` inexistente.
3. Verificación de UNIQUE constraints: no hay duplicados.
4. Verificación de valores: `gold_value`, `craft_time_seconds`, etc. coinciden con el Excel en una muestra aleatoria.

---

## 13. Recomendación de implementación por etapas

### Etapa 1 — Pre-requisitos (bloqueantes)

1. **Resolver Discrepancia D-1** (componentes no son blueprints). Sin esto, `blueprint_components` no puede implementarse.
2. **Resolver Discrepancia D-3** (modelado de `elements`), o decidir importar con los 4 campos en `null`.
3. **Resolver Discrepancia D-2** (Enchantments como componentes), probablemente junto con D-1.

### Etapa 2 — Importador base (sin componentes)

Implementar la importación de:
- `blueprints` (con `elements` en null si D-3 no está resuelto).
- `blueprint_workers`, `blueprint_resources`.
- Las 4 tablas de upgrades.

Esto permite avanzar con el 80% de los datos sin depender de D-1.

### Etapa 3 — Componentes

Una vez resuelto D-1, implementar la pasada 2 de `blueprint_components`.

### Etapa 4 — Pruebas y validación

- Ejecutar tests en SQLite :memory:.
- Ejecutar importación real en PostgreSQL (con autorización).
- Verificar conteos y coherencia.

---

## 14. Riesgos

1. **Bloqueo por D-1:** La discrepancia más crítica. Si no se resuelve, el 91.7% de los datos de componentes no puede importarse con el esquema actual.
2. **Pérdida de datos de elements:** Si no se decide el modelado de `elements`, los 4 campos de afinidad se importan como `null`.
3. **Consistencia de Enchantments:** Decidir si se incluyen o excluyen afecta la integridad referencial de 23 blueprints.
4. **Volumen de datos:** 1549 blueprints × 16 niveles de upgrades = ~25,000 inserciones solo en upgrades. Requiere transacciones eficientes.
5. **Cambios futuros en el Excel:** El Excel se actualiza periódicamente. El importador debe ser robusto ante reordenamientos de columnas.

---

## 15. Pendientes

- [ ] **Resolver D-1:** Decidir cómo manejar componentes que no son blueprints (Opción A/B/C).
- [ ] **Resolver D-3:** Modelado de `elements` (catálogo plano de 101 valores o normalización).
- [ ] **Resolver D-2:** Decidir si los 77 Enchantments se incluyen en `blueprints`.
- [ ] Implementar `BlueprintImporter` (etapa 2 primero).
- [ ] Implementar `blueprint_components` (etapa 3, tras D-1).
- [ ] Crear tests unitarios y feature.
- [ ] Ejecutar importación real en PostgreSQL (con autorización).

---

## 16. Preguntas para revisión

1. ¿Qué opción prefieren para D-1 (tabla de materiales unificada / FK polimórfica / importar quest components como blueprints sintéticos)?
2. ¿Se deben incluir los 77 Enchantments en `blueprints` para preservar la integridad referencial (D-2), o se excluyen y se omiten sus referencias?
3. ¿Se crea un `ElementSeeder` con los 101 valores únicos como catálogo plano, o se pospone el modelado de `elements`?
4. ¿El importador debe ejecutarse como comando Artisan (`php artisan import:blueprints`) o como clase invocable?
5. ¿Se prefiere transacción por blueprint o transacción global?

---

## 17. Próximo paso recomendado

**Resolver las Discrepancias D-1, D-2 y D-3** antes de implementar el importador. Estas decisiones son bloqueantes y requieren aprobación humana.

Una vez resueltas, implementar la Etapa 2 (importador base sin componentes) para avanzar con los datos que no dependen de las discrepancias.

**Estado de este reporte:** PROPUESTA — PENDIENTE DE REVISIÓN. No se ha implementado código.
