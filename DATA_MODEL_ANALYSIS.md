# Análisis del Modelo de Datos — Shop Titans API

> Documento de diseño generado el 2026-08-28.
>
> Fuente de datos: Excel real ubicado en raíz del proyecto.
>
> Hojas analizadas: Blueprints (1.613 filas × 98 cols), Workers, Worker Levels, Skills, Enchantments, Slots.

---

## 1. Estructura confirmada de la hoja Blueprints (98 columnas)

Agrupación real de columnas, detectada al inspeccionar el Excel:

| Grupos de columnas (letras) | Nombre en Excel | Núm. cols | Patrón detectado |
|---|---|---|---|
| A-P (0-15) | Info principal | 16 | Name, Type, Unlock Prereq, Research Scrolls, Antique Tokens, Tier, Value, Crafting Time s/format, Value/Time, Merchant XP abs/time, Worker XP, Fusion XP, Favor, Airship Power |
| Q (16) | Separador vacío | 1 | — |
| **R-W (17-22)** | Trabajadores necesarios | 6 | `Required Worker` (R) + `Worker Level` (S) → [Worker 1], (T/U) → [Worker 2], (V/W) → [Worker 3]. **Máx. 3 workers por blueprint.** |
| X (23) | Separador vacío | 1 | — |
| **Y-AJ (24-35)** | 12 Recursos | **12** | **SIN HEADERS**, pero corresponden exactamente, por orden, a: Iron (Y), Wood (Z), Leather (AA), Herbs (AB), Steel (AC), Ironwood (AD), Fabric (AE), Oils (AF), Mana (AG), Gems (AH), Essence (AI), Stardust (AJ). *Confirmado por Squire Sword = 5 Iron en columna Y.* |
| AK (36) | Separador vacío | 1 | — |
| **AL-AQ (37-42)** | Componentes | 6 | `Component` (AL) + `Component Quality` (AM) + `Amount Needed` (AN) → [Comp 1], (AO/AP/AQ) → [Comp 2]. **Máx. 2 componentes por blueprint.** |
| AR (43) | Separador vacío | 1 | — |
| **AS-AX (44-49)** | 6 Estadísticas | 6 | ATK (AS), DEF (AT), HP (AU), EVA (AV), CRIT (AW), Mana Shield (AX) |
| AY (50) | Separador vacío | 1 | — |
| **AZ-BC (51-54)** | 4 Afinidades / Elementos | 4 | Elemental Affinity (AZ), Spirit Affinity (BA), Built-In Element (BB), Built-In Spirit (BC) |
| BD (55) | Separador vacío | 1 | — |
| **BE-BN (56-65)** | Crafting Upgrades | 10 | 5 niveles × 2 cols (nombre upgrade + Crafts Needed) |
| BO (66) | Separador vacío | 1 | — |
| **BP-BY (67-76)** | Starforged Milestones | 10 | 5 milestones × 2 cols (nombre milestone + Crafts Needed) |
| BZ (77) | Separador vacío | 1 | — |
| **CA-CF (78-83)** | Ascension Upgrades | 6 | 3 upgrades × 2 cols (nombre upgrade + Shards Needed) |
| **CG-CL (84-89)** | Transcendence Upgrades | 6 | 3 upgrades × 2 cols (nombre upgrade + Seals Needed) |
| CM (90) | Separador vacío | 1 | — |
| **CN-CQ (91-94)** | Energías | 4 | Discount Energy, Surcharge Energy, Suggest Energy, Speed Up Energy |
| CR (95) | Separador vacío | 1 | — |
| **CS (96)** | Antique Date | 1 | Available as an Antique starting on (UTC) |
| CT (97) | Separador final | 1 | — |

**Total:** 16 + 6 + 12 + 6 + 6 + 4 + 10 + 10 + 6 + 6 + 4 + 1 = **98 columnas**.

---

## 2. Entidades definitivas

### 2.1 Catálogos maestros (tablas con datos fijos/referenciales)

| Entidad | Hoja Excel origen | Motivo |
|---|---|---|
| `workers` | `Workers` + `Worker Levels` (datos propios del worker) | Cada worker tiene nombre, persona, coste, nivel requerido, desbloqueos. Existe además progresión por nivel. |
| `worker_levels` | `Worker Levels` (hoja INDEPENDIENTE) | Tabla de XP y speed bonus por cada nivel del worker. **NO** es una relación con blueprints, es progreso intrínseco. |
| `resources` | Hoja `Blueprints` cols Y-AJ (12 fijos) | Catálogo cerrado n=12. Orden: Iron, Wood, Leather, Herbs, Steel, Ironwood, Fabric, Oils, Mana, Gems, Essence, Stardust. |
| `item_categories` | Hoja `Blueprints` col B "Type" | Valores: Sword, Axe, Dagger, Spear, Heavy Armor, Light Armor, Mask, Scythe, Boots, Gloves, Ring, Amulet, etc. Cada blueprint tiene exactamente 1 "Type". |
| `elements` | Hoja `Blueprints` cols AZ-BC | Elementos / espíritus que aparecen en afinidades y built-in: Fire, Water, Earth, Air, Light, Dark, Spirit, Soul + None. |
| `qualities` | Hoja `Blueprints` cols AM / AP "Component Quality" | Calidades: Normal, Superior, Flawless, Epic, Legendary. `"---"` = sin calidad mínima. |
| `enchantments` | Hoja `Enchantments` (INDEPENDIENTE) | Lista de encantamientos del juego. Actualmente es un catálogo aislado (sin relación a blueprints en el Excel). |
| `skills` | Hoja `Skills` (INDEPENDIENTE) | Habilidades de Héroes / Campeones (personajes jugables). **NO pertenecen al MVP del módulo Blueprints.** |

#### Importante — Skills: NO son de Blueprints
La hoja `Skills` tiene columna G "Classes" con valores como `"Argon (Champion)"`, `"Lilu (Champion)"`, y columna B "Type" = `"Leader"`. **Las skills son de heroes/champions (personajes), no de ítems.** No creamos relación N:M `blueprint_skills`.

#### Importante — Enchantments: Catálogo aislado
Hoja `Enchantments` solo tiene 3 columnas útiles: `Name`, `Type` (siempre "Element"), y un valor numérico sin header. **No existe mapeo Enchantments ↔ Blueprints en el Excel actual.** Guardamos el catálogo pero sin relación por ahora.

---

### 2.3 Decisión aprobada — `Unlock Prerequisite` → Opción A

Validación del Ajuste 3 demostró que la columna C `"Unlock Prerequisite"` **no contiene únicamente worker_keys** (8 de 439 valores distintos sí son workers; los 431 restantes son Packs, Offers, Chests, Content Passes, Intro Tutorial, Collection Book).

**Decisión final (Aprobada):**
```
blueprints.unlock_prerequisite  ->  varchar / string  nullable
```

Se conserva el **valor LITERAL** proveniente del Excel:
- `"Blacksmith"`, `"Tailor"`, `"7th Anniversary Superior Pack"`, `"Ancient Chest"`, `"Content Pass: Vampire"`, `"Intro Tutorial"`...
- `"---"` / vacío → `null`.

**Eliminado del diseño:**
- Campo `unlock_prerequisite_worker_id` (FK a workers) → NO EXISTE.
- **No hay FK entre `blueprints` y `workers` para este campo.**

**Fuente:** `UNLOCK_PREREQUISITE_ANALYSIS.md` (documento de análisis completo).

---

### 2.2 Entidad núcleo + relaciones 1:N (asociadas a cada blueprint individual)

| Entidad | Origen columnas Excel | Relación |
|---|---|---|
| `blueprints` | A-P + AS-AX + AZ-BC + CN-CS | 1 fila por blueprint (toda la información atómica NO repetitiva). |
| `blueprint_workers` | R-W (3 máx) | 1:N / trabajadores que pueden craftear el BP + nivel mínimo requerido. |
| `blueprint_resources` | Y-AJ (12 recursos) | 1:N / `blueprint_id, resource_id, quantity`. Solo se inserta si `quantity > 0` / distinto de `"---"`. |
| `blueprint_components` | AL-AQ (2 máx) | 1:N / `blueprint_id` (padre), `component_id` (hijo, FK al mismo blueprints — **relación recursiva**), `quality_id` nullable, `amount`. |
| `blueprint_crafting_upgrades` | BE-BN (5 niveles) | 1:N / `upgrade_level, name, crafts_required`. |
| `blueprint_starforged_milestones` | BP-BY (5 milestones) | 1:N / `milestone_number, name, crafts_required`. |
| `blueprint_ascension_upgrades` | CA-CF (3 upgrades) | 1:N / `upgrade_level, name, shards_required`. |
| `blueprint_transcendence_upgrades` | CG-CL (3 upgrades) | 1:N / `upgrade_level, name, seals_required`. |

---

## 3. Campos por entidad (definidos con nombres reales del Excel)

### 3.1 `workers` (catálogo maestro desde Hoja `Workers`)

| Campo | Columna Excel | Tipo | Notas |
|---|---|---|---|
| id | PK | bigint | |
| **worker_key** | A "Worker" | string UNIQUE | "Blacksmith", "Tailor", "Carpenter", "Wizard", "Master", "Ripley"... Clave natural. |
| **default_person_name** | B "Name" | string | "Wallace", "Julia", "Allan"... Nombre del personaje por defecto. |
| unlock_level_required | C "Level Required" | integer nullable | `"---"` → null |
| building_prerequisite | D "Building Prerequisite" | string nullable | `"---"` → null |
| gold_cost | E "Gold Cost" | integer nullable | `"---"` → null |
| gem_cost | F "Gem Cost" | integer nullable | `"---"` → null |
| blueprint_unlocks_raw | G "Blueprint Unlocks" | text nullable | CSV textual: `"Leather Gloves, Shirt, Leather Boots"`. Solo para referencia al importar; las relaciones reales se deducen desde blueprints. |
| created_at / updated_at | | timestamps | |

### 3.2 `worker_levels` (desde Hoja `Worker Levels`)

| Campo | Columna Excel | Tipo | Notas |
|---|---|---|---|
| id | PK | bigint | |
| **level** | A "Worker Level" | integer UNIQUE | 1..N |
| xp_needed | B "XP Needed" | integer nullable | Nivel 1 = `"---"` → null |
| crafting_speed_bonus | C "Crafting Speed Bonus" | decimal nullable | Nivel 1 = null |
| crafting_speed_bonus_t15plus | D "Crafting Speed Bonus (Tier 15+)" | decimal nullable | Niveles bajos = null |

### 3.3 `resources` (n=12, orden cols Y-AJ)

| Campo | Valor ejemplo | Notas |
|---|---|---|
| id | 1..12 | |
| **name** (UNIQUE) | "Iron", "Wood", "Leather", "Herbs", "Steel", "Ironwood", "Fabric", "Oils", "Mana", "Gems", "Essence", "Stardust" | Orden exacto columnas Y→AJ |
| column_letter_in_excel | "Y", "Z", ..., "AJ" | **IMPORTANTE para BlueprintImporter**, ya que las columnas no tienen header. |
| tier | T1, T2, T3... | Iron/Wood/Leather/Herbs = básicos (T1), Steel/Ironwood/Fabric/Oils = avanzados (T2), Mana/Gems/Essence = raros (T3), Stardust = endgame. |

### 3.4 `elements` (afinidades + built-in)

| Campo | Valor ejemplo |
|---|---|
| id | PK |
| name (UNIQUE) | "Fire", "Water", "Earth", "Air", "Light", "Dark", "Spirit", "Soul", "None" |

### 3.5 `enchantments` (Hoja Enchantments)

| Campo | Columna Excel | Ejemplo |
|---|---|---|
| id | PK | |
| **name** (UNIQUE) | A "Name" | "Ember Element", "Flame Element", "Blaze Element", "Inferno Element" |
| **type** | B "Type" | "Element" |
| **value** | C *(sin header)* | 5, 10, 15, 25... |

### 3.6 `blueprints` (tabla principal — información NO repetitiva)

| Campo | Columna Excel | Tipo | Notas |
|---|---|---|---|
| id | PK | bigint | |
| **name** (UNIQUE) | A "Name" | string | "Squire Sword" |
| **category_id** (FK → item_categories) | B "Type" | bigint | "Sword", "Heavy Armor" → catálogo |
| **unlock_prerequisite** | C "Unlock Prerequisite" | varchar nullable | **Decisión Opción A aprobada.** Valor literal: workers / packs / offers / chests / content passes / tutorial / collection. `"---"` o vacío → `null`. NO hay FK hacia `workers`. |
| research_scrolls | D "Research Scrolls" | integer nullable | `"---"` → null |
| antique_tokens | E "Antique Tokens" | integer nullable | `"---"` → null |
| tier | F "Tier" | integer | 1..16+ |
| gold_value | G "Value" | integer | |
| craft_time_seconds | H "Crafting Time (seconds)" | integer | |
| craft_time_formatted | I "Crafting Time (formatted)" | string | Derivable, guardar solo para debug |
| value_per_second | J "Value / Crafting Time" | decimal | Calculable pero útil persistir |
| merchant_xp | K "Merchant XP" | integer | |
| merchant_xp_per_second | L "Merchant XP / Crafting Time" | decimal | |
| worker_xp | M "Worker XP" | integer | |
| fusion_xp | N "Fusion XP" | integer | |
| favor | O "Favor" | integer | |
| airship_power | P "Airship Power" | integer | |
| **atk** | AS "ATK" | integer nullable | `"---"` → null |
| **def** | AT "DEF" | integer nullable | |
| **hp** | AU "HP" | integer nullable | |
| **eva** | AV "EVA" | integer nullable | |
| **crit** | AW "CRIT" | integer nullable | |
| **mana_shield** | AX "Mana Shield" | integer nullable | |
| elemental_affinity_id (FK elements) | AZ | bigint nullable | `"---"` → None / null |
| spirit_affinity_id (FK elements) | BA | bigint nullable | |
| built_in_element_id (FK elements) | BB | bigint nullable | |
| built_in_spirit_id (FK elements) | BC | bigint nullable | |
| discount_energy | CN "Discount Energy" | integer | |
| surcharge_energy | CO "Surcharge Energy" | integer | |
| suggest_energy | CP "Suggest Energy" | integer | |
| speed_up_energy | CQ "Speed Up Energy" | integer | |
| available_as_antique_at | CS "Available as an Antique starting on (UTC)" | timestamp nullable | `"---"` → null |
| created_at / updated_at | | timestamps | |

> **Decisión arquitectónica:** estadísticas ATK/DEF/HP/EVA/CRIT/Mana Shield como **6 columnas directas** (Opción A). Es la opción más simple, evita 6 self-joins, y está alineada con la filosofía anti-sobrearquitectura del proyecto. Las 4 afinidades/elementos también son FK directas.

---

## 4. Relaciones definitivas, FKs y constraints de unicidad

### 4.1 Relaciones 1:N (blueprints → tablas pivote/detalle)

| Tabla pivote | FK a blueprints | Otros FKs | Constraints recomendados |
|---|---|---|---|
| `blueprint_workers` | `blueprint_id` | `worker_id` (FK workers) | `UNIQUE(blueprint_id, worker_id)` — un worker no puede tener 2 niveles distintos para el mismo BP. |
| `blueprint_resources` | `blueprint_id` | `resource_id` (FK resources) | `UNIQUE(blueprint_id, resource_id)`. `quantity > 0`. **NO insertar fila si Excel = `"---"` o 0.** |
| `blueprint_components` | `blueprint_id` (PADRE) | `component_id` (FK `blueprints.id` — ⚠️ **RECURSIVA**), `quality_id` (FK qualities nullable) | `UNIQUE(blueprint_id, component_id, quality_id)`. `amount > 0`. |
| `blueprint_crafting_upgrades` | `blueprint_id` | — | `UNIQUE(blueprint_id, upgrade_level)`. levels = 1..5 |
| `blueprint_starforged_milestones` | `blueprint_id` | — | `UNIQUE(blueprint_id, milestone_number)`. nums = 1..5 |
| `blueprint_ascension_upgrades` | `blueprint_id` | — | `UNIQUE(blueprint_id, upgrade_level)`. levels = 1..3 |
| `blueprint_transcendence_upgrades` | `blueprint_id` | — | `UNIQUE(blueprint_id, upgrade_level)`. levels = 1..3 |

### 4.2 Relaciones N:M — NINGUNA necesaria en el MVP de Blueprints

Análisis del Excel elimina tablas N:M propuestas provisionalmente:

- ❌ `blueprint_possible_enchantments`: no existe mapeo Enchantments ↔ Blueprints. Enchantments = catálogo aislado.
- ❌ `blueprint_builtin_skills`: skills son de Heroes/Champions (columna "Classes"), NO de blueprints.

---

## 5. Clasificación: Catálogo vs Relación 1:N vs Columna Directa

### ✅ Son CATÁLOGOS (tablas separadas)
- `workers` (datos propios del worker)
- `worker_levels` (hoja independiente de progresión)
- `resources` (n=12, cols Y-AJ)
- `item_categories` (tipos de item)
- `elements` (afinidades y built-ins)
- `qualities` (calidades de componentes)
- `enchantments` (catálogo, sin relación actual a BPs)
- `skills` (catálogo heroes/champs, sin uso aún en blueprints)

### ✅ Son RELACIÓN 1:N (nunca columnas planas)
- Workers requeridos + niveles (3 grupos repetitivos → filas)
- 12 Recursos + cantidades (cols Y-AJ → filas)
- 2 Componentes + calidad + cantidad (→ filas)
- 5 × Crafting Upgrades + Crafts → filas
- 5 × Starforged Milestones + Crafts → filas
- 3 × Ascension Upgrades + Shards → filas
- 3 × Transcendence Upgrades + Seals → filas

### ✅ Son COLUMNAS DIRECTAS en `blueprints`
- Datos generales: nombre, tipo, tier, tiempos, XP, valor, monedas, tokens, scrolls
- 6 estadísticas: ATK, DEF, HP, EVA, CRIT, Mana Shield
- 4 afinidades/elementos: 4 FKs a `elements`
- 4 energías: discount, surcharge, suggest, speedup
- Antique date

---

## 6. Hallazgos y problemas de normalización (con decisiones de diseño)

### 🟡 Problema 1 — Columnas de recursos Y..AJ no tienen nombres de header
**Solución:** tabla `resources` incluye campo `column_letter_in_excel` para que el BlueprintImporter mapee alfabéticamente:
```
Col Y → resource_id where name='Iron'
Col Z → resource_id where name='Wood'
...
Col AJ → resource_id where name='Stardust'
```

### 🟡 Problema 2 — `blueprint_components` es relación RECURSIVA + solo 2 slots en Excel
- El Excel limita a 2 componentes por blueprint.
- La tabla 1:N NO limita a 2 (es más flexible ante futuras expansiones).
- **Decisión:** la limitación de 2 slots es responsabilidad exclusiva del `BlueprintImporter` (warning si hay >2).

### 🟡 Problema 3 — Orden de inserción por dependencia de componentes
Un blueprint "Gladius" puede tener componente "Iron Pine Cone". El componente debe existir antes.
**Decisión para BlueprintImporter:**
1. Primer pase: insertar `blueprints` base SIN `blueprint_components`. Ordenar por `tier ASC` (componentes son siempre de tier ≤ padre).
2. Segundo pase: insertar las filas en `blueprint_components` con los IDs resueltos.
Si el orden por tier no es suficiente → 2 pasadas es la estrategia segura.

### 🟡 Problema 4 — Valor `"---"` = NULL lógico en el Excel
Convención detectada: `"---"` = vacío / no aplica / 0.
**Decisión:** la lógica de parseo vive **exclusivamente en BlueprintImporter**, no en triggers SQL:
- Para `blueprint_resources`: `"---"` o "0" → NO insertar fila (no hay costo).
- Para stats (ATK/DEF/etc): `"---"` → valor `null`.
- Para columnas escalares en `blueprints` (research_scrolls, antique_tokens, etc.): `"---"` → `null`.
- Para fechas (available_as_antique_at): `"---"` → `null`.

### 🟡 Problema 5 — Skills y Enchantments sin relación en el Excel actual
- `skills`: tablas creadas pero "dormidas" hasta el módulo Heroes/Champions.
- `enchantments`: catálogo guardado pero sin relaciones. Se conectará cuando aparezca un uso real (ej: filtrar qué encantamientos aplican por categoría de ítem) o si el Excel crece con esa información.

### 🟡 Problema 6 — Workers: 1 clase (worker_key) vs. N personas
El worker se llama `Blacksmith` (clase/trabajo) y la columna B es el nombre de persona "Wallace". En el juego, ¿puedes contratar múltiples Blacksmiths con nombres distintos? Si la respuesta es sí, el diseño escalaría:
- `worker_classes` (worker_key UNIQUE: Blacksmith, Tailor...) → actual `workers`.
- `workers` (personas individuales con level/xp/owner del jugador).

Pero esto pertenece al módulo de Heroes/Workers del jugador, NO al módulo Blueprints.
**Decisión para el MVP Blueprints:** los campos `Required Worker` y `Unlock Prerequisite` usan FK al `id` del worker (clave natural `worker_key`), y la tabla se sigue llamando `workers`.

---

## 7. Resumen de ambigüedades resueltas (vs. análisis provisional anterior)

| # | Pregunta provisional | Respuesta del Excel real |
|---|---|---|
| 1 | Workers: ¿datos propios o solo mapeo? | Sí tienen datos propios en Hoja Workers (coste, nivel, prereqs). → 2 tablas `workers` + `worker_levels`. |
| 2 | Cols Required Worker son nombre? | Sí, directamente `worker_key` ("Blacksmith", "Tailor"). → FK a `workers`. |
| 3 | Enchantments: catálogo o mapeo a BPs? | SOLO catálogo aislado. Sin relación actual a blueprints. |
| 4 | Skills: ¿de ítems o personajes? | Skills = Classes / Heroes / Champions (col G). Fuera de ámbito de blueprints. |
| 5 | Enchantments permitidos por categoría? | Sin datos en el Excel. Guardamos catálogo sin relaciones. |
| 6 | Enchantments por calidad? | Sin datos en el Excel. |
| 7 | Shards y Seals son resources? | NO. Son integers genéricos en sus tablas de upgrade (no están en las 12 cols Y-AJ). |
| 8 | Stats en 6 cols individuales? | Sí (AS-AX). Squire Sword = solo ATK=16. → 6 columnas directas. |
| 9 | Built-In Element / Spirit único? | Sí (cols BB, BC). 1 blueprint = 0 o 1 built-in de cada tipo. → 2 FK directos. |
| 10 | Components: quality required o any? | Ejemplos = `"---"`. → `quality_id` nullable en `blueprint_components`. |
| 11 | Skills vs Workers / Items | Skills = Classes (heroes). |
| 12 | Skills por calidad? | No aplica en blueprints. |

---

## 8. Diagrama final de entidades (MVP Blueprints + Workers + Enchantments + Worker Levels)

```
┌─────────────────────────────────────────────────────────────────────┐
│                         CATÁLOGOS                                   │
├─────────────────────┐ ┌───────────────────┐ ┌────────────────────┐ │
│ workers             │ │ worker_levels     │ │ resources (n=12)    │ │
│ (Blacksmith, etc.)  │ │ (XP/speed x nivel)│ │ Iron→Stardust       │ │
│ worker_key UNIQUE   │ │ level UNIQUE      │ │ column_letter Y-AJ  │ │
└──────────┬──────────┘ └───────────────────┘ └─────────┬──────────┘ │
           │                                            │            │
           │ FK (unlock_prereq y blueprint_workers)     │ FK         │
           ▼                                            ▼            │
┌──────────────────────────────────────────────────────────────────── │
│                    blueprints (TABLA PRINCIPAL)                     │
│ name UNIQUE, category_id, stats(6), 4 elements FK, energies, etc.  │
└──┬──┬──┬──┬──────────────┬──────────────┬────────────┬─────────────┘
   │  │  │  │              │              │            │
   │  │  │  │  blueprint_  │  blueprint_  │ blueprint_ │ 4 tablas:
   │  │  │  │  workers     │  resources   │ components │ Crafting,
   │  │  │  │              │              │ recursiva: │ Starforged,
   │  │  │  │ worker_id,   │ resource_id, │ component  │ Ascension,
   │  │  │  │ level_req    │  quantity    │ quality_id │ Transcendence
   │  │  │  │              │              │ amount     │
   ▼  ▼  ▼  ▼              ▼              ▼            ▼
   (tablas 1:N con blueprint_id + campos específicos + UNIQUE)

┌───────────────────────────────────┐
│  CATÁLOGOS SIN RELACIÓN (AÚN)      │
├───────────────────┬───────────────┤
│ enchantments      │ skills        │
│ (Name/Type/Value) │ (heroes only) │
└───────────────────┴───────────────┘
```

---

## 9. Próximos pasos de implementación (pendientes)

Cuando el diseño se apruebe, la implementación sigue este orden (alineado con `PROJECT_CONTEXT.md §9`):

1. **Paso 5.1 — Migraciones de catálogos**:
   `item_categories`, `resources`, `elements`, `qualities`, `workers`, `worker_levels`, `enchantments`, `skills`.

2. **Paso 5.2 — Migración de `blueprints`** (tabla principal) con todas sus FKs a los catálogos anteriores.

3. **Paso 5.3 — Migraciones tablas 1-N**:
   `blueprint_workers`, `blueprint_resources`, `blueprint_components` (recursiva),
   `blueprint_crafting_upgrades`, `blueprint_starforged_milestones`,
   `blueprint_ascension_upgrades`, `blueprint_transcendence_upgrades`.

4. **Paso 6 — Modelos Eloquent** correspondientes + relaciones `belongsTo` / `hasMany` + constraints.

5. **Paso 7 — Seeders / BlueprintImporter**:
   Cargar primero los 12 `resources` (con su `column_letter_in_excel`), `elements`,
   `qualities`, `workers`, `worker_levels`, `enchantments`, `skills`.
   Luego: importar la hoja Blueprints (1.613 filas) con la lógica de 2 pasadas
   para componentes recursivos.
