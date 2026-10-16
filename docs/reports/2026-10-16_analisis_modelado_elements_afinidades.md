# Análisis de modelado D-3 — Elementos y afinidades de Blueprints (cols AZ–BC)

**Fecha:** 2026-10-16
**Autor:** Editor IA
**Estado:** PROPUESTA — PENDIENTE DE APROBACIÓN
**Alcance:** análisis y recomendación. **No se modificó código, esquema ni datos.**

---

## 1. Objetivo

Determinar el modelo de datos adecuado para las columnas AZ–BC del Excel fuente (`Elemental Affinity`, `Spirit Affinity`, `Built-In Element`, `Built-In Spirit`), evaluando si un único catálogo `elements` es suficiente o si se requieren catálogos separados.

## 2. Alcance

**Incluye:** análisis de solo lectura del Excel v26.9.1, comparación con la hoja `Enchantments` y otras fuentes, y propuesta de modelado.

**Excluye:** modificar código/modelos/migraciones/esquema, ejecutar seeders/importadores, escribir en PostgreSQL, commits/push. La decisión **no** se marca como aprobada.

## 3. Estado inicial

- Migración `2026_08_28_100005_create_elements_table.php`: `elements(id, name UNIQUE, timestamps)` — un único espacio de nombres.
- `blueprints` (migración `2026_08_28_100007`) define 4 FKs nulas a `elements`: `elemental_affinity_id` (AZ), `spirit_affinity_id` (BA), `built_in_element_id` (BB), `built_in_spirit_id` (BC).
- `DATA_MODEL_ANALYSIS.md` §3.4 documenta `elements` con nombres `Fire, Water, Earth, Air, Light, Dark, Spirit, Soul, None` — **hipótesis previa no verificada**.
- `enchantments` es un catálogo existente (hoja `Enchantments`), sin relación con `blueprints`.

## 4. Archivos

En esta tarea **solo documentación**:

| Archivo | Acción |
|---|---|
| `docs/reports/2026-10-16_analisis_modelado_elements_afinidades.md` | Creado |
| `AI_WORK_LOG.md` | Modificado (estado PROPUESTA) |

Ningún archivo de código, migración o esquema fue modificado.

## 5. Metodología y comandos de solo lectura

Herramientas usadas (lectura pura; sin PostgreSQL, sin escrituras):

```bash
# Lectura del XLSX como ZIP/XML con la librería estándar de Python (sin Docker, que no estaba disponible)
python3 /tmp/analyze_azbc.py     # volcado de AZ-BC + frecuencias + hoja Enchantments
python3 /tmp/analyze2.py         # clasificación cruzada con Enchantments
python3 /tmp/analyze3.py         # hoja Legend Key + búsqueda de "Mundra" + Full Moon Fusions
```

> Nota: el daemon de Docker no estaba accesible durante esta tarea, por lo que el análisis se hizo con Python estándar sobre el `.xlsx` (que es un contenedor ZIP de XML). No se ejecutaron comandos contra PostgreSQL.

---

## 6. DATOS OBSERVADOS (Excel v26.9.1 — hoja `Blueprints`, 1.626 filas con nombre)

### 6.1 Encabezados (fila 1)

| Columna | Índice | Header |
|---|---|---|
| AZ | 52 | `Elemental Affinity` |
| BA | 53 | `Spirit Affinity` |
| BB | 54 | `Built-In Element` |
| BC | 55 | `Built-In Spirit` |

Filas con **algún** valor no vacío en AZ–BC: **1.005** de 1.626.

### 6.2 AZ — `Elemental Affinity` (32 valores distintos, `---` = 824)

Valores **base** (8):

| Valor | Frecuencia |
|---|---|
| Dark | 126 |
| Earth | 114 |
| Light | 103 |
| **Gold** | 101 |
| Water | 89 |
| Fire | 68 |
| Air | 66 |
| **All** | 26 |

Valores **compuestos** (23 cadenas, 109 filas en total; dos elementos separados por `", "`):

| Valor | Fr. | | Valor | Fr. |
|---|---|---|---|---|
| `Earth, Dark` | 14 | | `Dark, Earth` | 3 |
| `Light, Dark` | 14 | | `Earth, Light` | 3 |
| `Earth, Water` | 11 | | `Fire, Dark` | 3 |
| `Air, Dark` | 8 | | `Water, Air` | 3 |
| `Air, Earth` | 8 | | `Water, Dark` | 3 |
| `Dark, Fire` | 6 | | `Water, Light` | 3 |
| `Air, Light` | 5 | | `Fire, Water` | 3 |
| `Light, Water` | 5 | | `Light, Gold` | 2 |
| `Earth, Air` | 5 | | `Light, Earth` | 2 |
| `Fire, Air` | 4 | | `Light, Air` | 1 |
| | | | `Water, Fire` | 1 |
| | | | `Fire, Light` | 1 |
| | | | `Air, Fire` | 1 |

**Observado:** el **orden de los dos elementos NO es canónico** (`Earth, Dark` coexiste con `Dark, Earth`). Hay 23 cadenas distintas que corresponden a **15 pares canónicos** (conjuntos) distintos.

### 6.3 BA — `Spirit Affinity` (39 valores distintos, `---` = 1296)

38 valores no vacíos (top por frecuencia): `Ancestor Tradition`(41), `Quetzalcoatl Spirit`(33), `Titan Solemnity`(23), `Christmas Spirit`(20), `Dinosaur Senses`(17), `Xolotl Spirit`(13), `Bunny Hops`(12), `Wolf Ferocity`(12), `Ouroboros Eternity`(11), `Phoenix Immortality`(11), `Tiger Power`(9), `Bahamut Sovereignty`(9), `Eagle Accuracy`(9), `Tomb-Born Malevolence`(8), `Griffin Freedom`(8), `Spirit Spirit`(7), `Bear Vitality`(7), `Behemoth Violence`(7), `Owl Wisdom`(7), `Viper Lethality`(6), `Chimera Malignity`(6), `Leviathan Assurance`(6), `Carbuncle Whimsy`(6), `Cat Grace`(6), `Kraken Cruelty`(4), `Kirin Serenity`(4), `Horse Endurance`(4), `Lizard Regeneration`(4), `Krampus Wile`(3), `Shark Bloodlust`(3), `Scholar Spirit`(3), `Walrus Nonchalance`(3), `Ram Robustness`(2), `Lion Pride`(2), `Hydra Onslaught`(1), `Ox Endurance`(1), `Tarrasque Terror`(1), `Mammoth Presence`(1).

**Observado:** patrón dominante **`<Ser> <Cualidad>`** (Wolf Ferocity, Bear Vitality, Eagle Accuracy, Cat Grace…). Solo **5** terminan en `Spirit` (`Quetzalcoatl Spirit`, `Xolotl Spirit`, `Christmas Spirit`, `Scholar Spirit`, `Spirit Spirit`).

### 6.4 BB — `Built-In Element` (31 valores distintos, `---` = 1541)

30 valores, todos con sufijo `Element`: `Unholy`(4), `Wild`(4), `Blistering`(4), `Flame`(4), `Tide`(4), `Gale`(4), `Holy`(4), `Flood`(3), `Oblivion`(3), `Sacred`(3), `Primal`(3), `Nightmare`(3), `Tempest`(3), `Tornado`(3), `Maelstrom`(3), `Blaze`(3), `Gaia`(3), `Apotheosis`(3), `Nature`(2), `Ember`(2), `Bubble`(2), `Corrupted`(2), `Torrent`(2), `Breeze`(2), `Primeval`(2), `Abyssal`(2), `Light`(2), `Hurricane`(2), `Inferno`(2), `Divine`(2).

### 6.5 BC — `Built-In Spirit` (2 valores distintos, `---` = 1616)

- `Mundra's Spirit` → **10** filas.
- `---` → 1.616.

### 6.6 Combinaciones (AZ | BA | BB | BC)

155 combinaciones distintas. Top: `--- | --- | --- | ---` (621), `Dark | --- | --- | ---` (114), `Earth | …` (93), `Water | …` (77), `Light | …` (71), `Fire | …` (60), `Air | …` (53), `Gold | Ancestor Tradition | --- | ---` (41), `Gold | Quetzalcoatl Spirit | --- | ---` (32), `All | --- | --- | ---` (24), `--- | Dinosaur Senses | --- | ---` (15), `Light | Titan Solemnity | --- | ---` (14).

**Observado:** las columnas son **independientes entre sí** (pueden combinarse libremente) y no forman una única dimensión.

### 6.7 Hoja `Enchantments` (73 filas)

Estructura: `A=Name`, `B=Type`, `C=valor`. **73 entradas** = 33 de tipo `Element` + 40 de tipo `Spirit`.

- 33 `Element`: `Ember Element`, `Flame Element`, `Blaze Element`, `Inferno Element`, `Blistering Element`, `Bubble Element`, `Tide Element`, `Flood Element`, `Torrent Element`, `Maelstrom Element`, `Breeze Element`, `Gale Element`, `Tempest Element`, `Hurricane Element`, `Tornado Element`, `Nature Element`, `Wild Element`, `Primal Element`, `Primeval Element`, `Gaia Element`, `Light Element`, `Holy Element`, `Sacred Element`, `Divine Element`, `Apotheosis Element`, `Corrupted Element`, `Unholy Element`, `Nightmare Element`, `Abyssal Element`, `Oblivion Element`, `Luxurious Element`, `Opulent Element`, `Platinum Element`.
- 40 `Spirit`: `Ram Spirit`, `Wolf Spirit`, `Ox Spirit`, `Eagle Spirit`, `Viper Spirit`, `Cat Spirit`, `Bunny Spirit`, `Goose Spirit`, `Xolotl Spirit`, `Rhino Spirit`, `Owl Spirit`, `Armadillo Spirit`, `Lizard Spirit`, `Horse Spirit`, `Hippo Spirit`, `Squirrel Spirit`, `Shark Spirit`, `Walrus Spirit`, `Lion Spirit`, `Bear Spirit`, `Mammoth Spirit`, `Dinosaur Spirit`, `Tiger Spirit`, `Quetzalcoatl Spirit`, `Phoenix Spirit`, `Hydra Spirit`, `Tarrasque Spirit`, `Carbuncle Spirit`, `Chimera Spirit`, `Kraken Spirit`, `Christmas Spirit`, `Krampus Spirit`, `Kirin Spirit`, `Behemoth Spirit`, `Ouroboros Spirit`, `Bahamut Spirit`, `Leviathan Spirit`, `Griffin Spirit`, `Titan Spirit`, `Ancestor Spirit`.

### 6.8 Cruces de clasificación

| Columna | Distintos no vacíos | Coinciden con `Enchantments` | NO coinciden |
|---|---|---|---|
| AZ `Elemental Affinity` | 31 | **0** | 31 |
| BA `Spirit Affinity` | 38 | **3** (`Christmas Spirit`, `Quetzalcoatl Spirit`, `Xolotl Spirit`) | 35 |
| BB `Built-In Element` | 30 | **30 (100 %)** | 0 |
| BC `Built-In Spirit` | 1 | 0 | 1 (`Mundra's Spirit`) |

- `Mundra's Spirit` aparece en `Workers` (fila 13, worker `Mundra`) y en 4 blueprints con nombre `Mundra's …` → es un **espíritu especial ligado a un worker**, no una entrada de `Enchantments`.
- La hoja `Legend Key` **no** define estos términos (solo explica colores de la hoja Home/Blueprints).
- Los 40 `X Spirit` de `Enchantments` **no** coinciden textualmente con los valores de BA (`Wolf Spirit` vs `Wolf Ferocity`).

---

## 7. INFERENCIAS (derivadas, no datos literales)

> Separado explícitamente de §6 (datos observados).

1. **Las 4 columnas pertenecen a 3–4 dominios distintos**, no a un único espacio de nombres:
   - **AZ** → *tokens de elemento base* (`Fire/Water/Earth/Air/Light/Dark` + especiales `Gold`, `All`), con **multiplicidad** (1 o 2 valores).
   - **BA** → *nombres de afinidad espiritual* (`<Ser> <Cualidad>`), dominio propio.
   - **BB** → *nombres de encantamiento de tipo `Element`* (ya existentes en `enchantments`).
   - **BC** → *espíritu especial/nominado* (`Mundra's Spirit`), sin catálogo hoy.
2. Un **único catálogo `elements(name UNIQUE)`** mezclaría los 4 dominios en un mismo saco de nombres, y **no puede** representar el caso compuesto de AZ (2 valores) con una sola FK.
3. Existe una **correspondencia estructural plausible** BA ↔ `Enchantments.Spirit` (p. ej. `Wolf Spirit` ↔ `Wolf Ferocity`), pero **el Excel no aporta tabla de mapeo**; tratarlo como FK sería una suposición no verificada (violaría la regla del proyecto).
4. Los valores `Gold` (101) y `All` (26) de AZ tienen **semántica no documentada** en el Excel.
5. `---` / vacío = "no aplica" → NULL (coherente con la convención del proyecto).

---

## 8. Comparación de alternativas de modelado

### Alternativa A — Un único catálogo `elements` + 4 FKs, añadiendo los valores compuestos como filas
- **Integridad referencial:** formal (todas las FKs resuelven), pero **semánticamente falsa**: 4 columnas de dominios distintos apuntan a un mismo espacio.
- **Complejidad:** nula (no hay cambios de esquema).
- **Consultas:** simples (`JOIN elements`), pero ambiguas (`elements.name` = `Fire` | `Wolf Ferocity` | `Ember Element`).
- **Pérdida de información:** alta. No distingue dominio; los compuestos quedan como cadenas `"Earth, Dark"` (el orden no canónico genera filas duplicadas `Dark, Earth`); duplica nombres ya presentes en `enchantments` (doble fuente de verdad); `Mundra's Spirit` no tiene grupo natural.
- **Veredicto:** no recomendada.

### Alternativa B — Catálogos separados por dominio + FK simple en AZ (solo valor único)
- **Integridad referencial:** correcta por dominio.
- **Complejidad:** media (nuevos catálogos: `spirit_affinities`, `spirits`; BB reusa `enchantments`).
- **Consultas:** claras por dominio.
- **Pérdida de información:** **sí** en AZ: descartaría o truncaría los 109 registros compuestos (23 cadenas / 15 pares).
- **Veredicto:** incompleta (pierde datos reales).

### Alternativa C (RECOMENDADA) — Catálogos por dominio + tabla 1:N para la afinidad elemental
1. **`elements`** (existente): tokens base `Fire, Water, Earth, Air, Light, Dark` (+ `Gold`, `All` pendientes de semántica).
2. **`blueprint_elemental_affinities`** (nueva 1:N): `blueprint_id`, `element_id`, `position` (1–2). Representa AZ, incluidos los compuestos, sin perder el orden ni la relación individual. Sustituye `blueprints.elemental_affinity_id`.
3. **`spirit_affinities`** (nueva): catálogo de los 38 nombres de BA. `blueprints.spirit_affinity_id` FK nullable re-apuntada a este catálogo.
4. **`enchantments`** (existente, REUTILIZADO): `blueprints.built_in_element_id` FK nullable re-apuntada a `enchantments.id` (cruce 100 % verificado en BB).
5. **`spirits`** (nueva, catálogo pequeño): `Mundra's Spirit`; `blueprints.built_in_spirit_id` FK nullable re-apuntada.
- **Integridad referencial:** correcta y por dominio; BB apoyada en evidencia (100 % de coincidencia).
- **Complejidad:** media-alta (3 catálogos nuevos + 1 tabla 1:N + cambio de 4 FKs en `blueprints`).
- **Consultas:** precisas (`blueprint → afinidades`, `→ espíritu`, `→ encantamiento built-in`).
- **Pérdida de información:** nula (los compuestos de AZ se preservan como 2 filas; el orden se guarda si se desea).
- **Coherencia con el proyecto:** alineada con el patrón ya usado para relaciones multi-valor (recursos, componentes, upgrades) y con la regla "verificar datos antes de convertirlos en FK".

### Alternativa D — Guardar AZ/BA/BC como texto literal; solo BB como FK
- **Integridad referencial:** ninguna en AZ/BA/BC; BB correcta.
- **Complejidad:** mínima.
- **Consultas:** por igualdad exacta; sin deduplicación normalizada.
- **Pérdida de información:** nula (se conserva el literal, incluidos `---`).
- **Veredicto:** aceptable solo si se prioriza no comprometerse a un catálogo aún no verificado (BA/BC). Contradice la filosofía de catálogos del proyecto, pero es defendible como paso intermedio.

### Tabla resumen

| Criterio | A (un catálogo) | B (separados, AZ simple) | **C (recomendada)** | D (literal) |
|---|---|---|---|---|
| Integridad referencial | Formal pero falsa | Correcta | **Correcta** | Nula (AZ/BA/BC) |
| Complejidad de esquema | Nula | Media | **Media-alta** | Baja |
| Consultas | Simples/ambiguas | Claras | **Claras** | Simples/rígidas |
| Pérdida de información (AZ) | Media (orden) | **Alta** (compuestos) | **Nula** | Nula |
| Fidelidad semántica | Baja | Alta | **Alta** | Media |
| Riesgo de decisión no verificada | Alto | Medio | **Bajo en BB, medio en BA/BC** | Bajo |

---

## 9. Recomendación

**Alternativa C**, con dos cautelas explícitas antes de implementar:

1. **Semántica de `Gold` y `All` en AZ** (101 y 26 filas): verificar si son elementos reales o marcadores especiales; podría requerir un enumerado/atributo aparte en lugar de filas de `elements`.
2. **Naturaleza de BA**: si se confirma (con fuente externa) que cada afinidad espiritual corresponde 1:1 a un `X Spirit` de `enchantments`, entonces `spirit_affinities` podría resolverse como FK a `enchantments` en lugar de un catálogo nuevo. **Hoy no está verificado**, por lo que se propone catálogo propio y se deja la unificación como decisión separada.

Además: **normalizar el orden** de los pares de AZ (canónico, p. ej. alfabético) para evitar duplicados (`Earth, Dark` vs `Dark, Earth`).

**No implementar sin aprobación.**

---

## 10. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| AZ puede contener 2 elementos | 23 cadenas compuestas (109 filas) | Confirmada |
| BB son nombres de encantamiento | 30/30 coinciden con `Enchantments` (`Element`) | Confirmada |
| El catálogo `elements` actual (name UNIQUE) modela `Spirit, Soul, None` | Ningún valor `Spirit`/`Soul`/`None` en AZ–BC | **Refutada** (hipótesis previa de `DATA_MODEL_ANALYSIS.md` §3.4) |
| BA se corresponde textualmente con `X Spirit` de `Enchantments` | Solo 3/38 coinciden | **No confirmada** |

## 11. Discrepancias

```text
DISCREPANCIA
Estado documentado (DATA_MODEL_ANALYSIS.md §3.4): elements = {Fire, Water, Earth, Air, Light, Dark, Spirit, Soul, None}
Estado real: AZ usa {Fire, Water, Earth, Air, Light, Dark, Gold, All} + 15 pares; BA usa 38 nombres "<Ser> <Cualidad>"; BB usa 30 nombres "X Element" (de Enchantments); BC usa "Mundra's Spirit".
Evidencia: §6 de este reporte (Excel v26.9.1).
Impacto: un único catálogo `elements` no representa correctamente las 4 columnas; BA/BB/BC quedan mal modelados.
Propuesta: Alternativa C (§8/§9).
Requiere aprobación: Sí
```

## 12. Riesgos

1. **Suposición no verificada:** tratar BA como dominio independiente cuando podría mapear a `enchantments`; mitigado dejando la unificación como decisión pendiente.
2. **Semántica de `Gold`/`All`:** modelarlos como `elements` sin verificar podría ser incorrecto.
3. **Cambio de esquema:** C modifica 4 FKs de `blueprints` y añade 3 catálogos + 1 tabla 1:N (requiere migración y ajustes de importador).
4. **Orden de pares en AZ:** sin normalización canónica se generarán duplicados semánticos.

## 13. Pendientes

1. Aprobación humana de la alternativa recomendada (C) o de una variante.
2. Verificar semántica de `Gold`/`All`.
3. Verificar (fuente externa) el posible mapeo BA ↔ `enchantments.Spirit`.

## 14. Preguntas para ChatGPT

1. ¿Se aprueba la Alternativa C, o se prefiere la D (literal) como paso intermedio de bajo compromiso?
2. ¿Cómo modelar `Gold` y `All` de AZ: filas de `elements`, columnas booleanas o enumerado?
3. ¿Se admite `spirit_affinities` como catálogo propio a la espera de confirmar el mapeo con `enchantments`?
4. ¿Debe conservarse el orden original de los pares de AZ (`position`) o basta el conjunto canónico?

## 15. Próximo paso

Revisión humana y aprobación. Estado: **PROPUESTA — PENDIENTE DE APROBACIÓN**. No se implementará nada hasta recibir autorización expresa.

## Confirmación

No se modificó código, modelos, migraciones ni esquema. No se ejecutaron seeders, importadores ni escrituras (ni lecturas) contra PostgreSQL. No se creó commit ni push. Todo el análisis se realizó con lecturas del Excel mediante Python estándar.
