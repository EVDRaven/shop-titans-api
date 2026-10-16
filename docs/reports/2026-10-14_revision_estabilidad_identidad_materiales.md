# Revisión de estabilidad de identidad de materiales — D-1

**Fecha:** 2026-10-13
**Estado:** PROPUESTA — PENDIENTE DE APROBACIÓN HUMANA

## 1. Objetivo

Determinar si los nombres de `Quest Components`, `Sigils` y `Enchantments` del Excel fuente pueden utilizarse como parte de `source_key`, o si la fuente proporciona identificadores más estables.

Pregunta central: **¿qué evidencia tenemos para justificar que los nombres son claves de identidad aceptables para los materiales que no son blueprints?**

## 2. Alcance

Exclusivamente de investigación y documentación. No se modifica código, migraciones, base de datos, seeders ni datos importados.

## 3. Archivos y hojas examinados

**Excel fuente:** `Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx`

**Hojas inspeccionadas:**
| Hoja | Filas de datos | Columnas | Propósito del análisis |
|---|---|---|---|
| `Quest Components` | 66 | A–F | Identificadores, nombres, unicidad, referencias |
| `Full Moon Fusions` | 101 | A–T | Identificadores de los 6 Sigils |
| `Blueprints` | 1626 | A–CT | Enchantments (Type=Enchantment) y referencias cruzadas |
| `Enchantments` | 73 | A–J | Verificación de IDs y consistencia con Blueprints |
| `Changelog` | 1027 | A–E | Evidencia de cambios de nombre históricos |
| `Legend  Key` | 20 | A–G | Convenciones de formato |

## 4. Hallazgos por tipo

### 4.1 Quest Components (66 materiales)

**Columnas de la hoja `Quest Components`:**
- A: `Name`
- B: `Type` (vacío para la mayoría; `Timewarped` o `Artifact` para algunos)
- C: `Tier`
- D: `Value`
- E: `Quest Area`
- F: vacía

**Identificador de origen:** **No existe ninguna columna de ID, Key, Code, GUID o UUID.** El único atributo que identifica a cada quest component es `Name`.

**Unicidad:** 66 nombres únicos, 0 duplicados.

**Referencias cruzadas en la hoja `Blueprints`:** Los 66 quest components son referenciados en las columnas de componentes (`AL`, `AO`) por su nombre exacto. Ningún quest component queda sin referencia.

**Inconsistencias de caso/espacios:** Ninguna. Los nombres coinciden exactamente entre la hoja `Quest Components` y las referencias en `Blueprints`.

### 4.2 Sigils (6 materiales)

**Ubicación:** Hoja `Full Moon Fusions`, filas con `Type = Component` y `Name` conteniendo "Sigil".

**Columnas relevantes:**
- A: `Name`
- B: `Type`
- C: `Worker Level Required`
- D: `Quantity`
- E: `Fuse Time (seconds)`
- F: `Fuse Time (formatted)`
- G: `Worker XP`

**Identificador de origen:** **No existe ninguna columna de ID.** El único identificador es `Name`.

**Unicidad:** 6 nombres únicos (Sigil of Might, Sigil of Grace, Sigil of Spark, Sigil of True Might, Sigil of True Grace, Sigil of True Spark), 0 duplicados.

**Referencias cruzadas en `Blueprints`:** Los 6 sigils son referenciados por nombre exacto (40, 37, 37, 52, 37, 34 referencias respectivamente).

**Inconsistencias de caso/espacios:** Ninguna.

### 4.3 Enchantments (77 materiales)

**Ubicación:** Hoja `Blueprints`, filas con `Type = Enchantment`. También existen en la hoja `Enchantments` (73 filas).

**Diferencia entre hojas:** La hoja `Enchantments` contiene 73 registros; la hoja `Blueprints` contiene 77. Los 4 que están solo en `Blueprints` son: `Fresh Spirit`, `Mundra's Spirit`, `Spirit Spirit`, `Tomb-Bound Spirit`.

**Identificador de origen:** **No existe ninguna columna de ID** en ninguna de las dos hojas. El único identificador es `Name`.

**Unicidad:** 77 nombres únicos en `Blueprints`, 0 duplicados.

**Referencias cruzadas en `Blueprints`:** 15 de los 77 enchantments son referenciados como componentes por blueprints de tipo distinto a Enchantment (por ejemplo, `Ember Element`, `Tide Element`, `Viper Spirit`). Las referencias usan el nombre exacto.

**Inconsistencias de caso/espacios:** Ninguna entre las dos hojas.

### 4.4 Changelog (evidencia de cambios de nombre)

La hoja `Changelog` tiene 1027 filas con entradas fechadas desde 2019. Se buscó por los términos `rename`, `renamed`, `changed name`, `name changed`, `new name`, `now called`.

**Resultado: 0 coincidencias.** No hay evidencia en el Changelog de cambios de nombre explícitos para ningún tipo de material.

**Limitación:** Las entradas del Changelog son mayormente genéricas ("Updated data for all blueprints", "Updated data for all skills"), por lo que un cambio de nombre podría haberse realizado sin documentarlo explícitamente.

## 5. Evidencia comprobada vs inferencias vs no determinable

### Evidencia comprobada en los archivos
1. No existe ninguna columna de identificador numérico o alfanumérico en las hojas `Quest Components`, `Full Moon Fusions`, `Blueprints` ni `Enchantments`. El `Name` es el único atributo de identidad disponible.
2. Los nombres son únicos dentro de cada tipo (66 QC, 6 Sigils, 77 Enchantments, 0 duplicados cada uno).
3. Todos los quest components y sigils son referenciados en `Blueprints` por nombre exacto.
4. No hay inconsistencias de mayúsculas/minúsculas ni espacios entre las hojas.
5. El Changelog no documenta ningún cambio de nombre explícito.

### Inferencias razonables
1. Dado que el Excel se mantiene manualmente por la comunidad y no expone IDs internos del juego, el `Name` es probablemente el identificador más estable disponible en esta fuente.
2. Los nombres de quest components siguen un patrón temático por área (Howling Woods, Aurora Caves, etc.) y los enchantments siguen patrones de progresión (Ember → Flame → Blaze → Inferno → Blistering), lo que sugiere que son nombres canónicos del juego.
3. Si un nombre cambiara en una versión futura del juego, el mantenedor del Excel actualizaría el nombre en la hoja correspondiente y en todas las referencias, ya que no hay IDs para mantener.

### Aspectos que no se pueden determinar con esta versión del Excel
1. **No se puede afirmar que los nombres permanecerán estables entre versiones del juego.** Solo tenemos una versión del Excel (v26.9.1). Para confirmar estabilidad se necesitaría comparar múltiples versiones históricas.
2. **No se puede descartar que el juego tenga IDs internos** que simplemente no están expuestos en este Excel comunitario. La ausencia de un identificador visible en el Excel no es prueba de que no exista en el código del juego.
3. **No se puede confirmar si el Changelog documenta todos los cambios de nombre.** Sus entradas son genéricas; un rename podría haberse realizado sin mención explícita.

## 6. Conclusión

**Respuesta a la pregunta central:** La evidencia disponible **justifica parcialmente** el uso del nombre como clave de identidad para `quest_component`, `sigil` y `enchantment`:

- ✅ **No existe ningún identificador alternativo** en el Excel. El `Name` es el único atributo que identifica inequívocamente a cada material.
- ✅ Los nombres son **únicos dentro de cada tipo** y **coinciden exactamente** entre la hoja de origen y las referencias en `Blueprints`.
- ⚠️ **No hay evidencia de cambios de nombre históricos** en el Changelog, pero esto no garantiza estabilidad futura.
- ❌ **No se puede afirmar que los nombres permanecerán estables** entre versiones del juego, porque solo se dispone de una versión del Excel y el Changelog no documenta renames explícitamente.

**Recomendación:** Usar `quest_component:{name}`, `sigil:{name}` y `enchantment:{name}` como `source_key` es **la mejor opción disponible** con la fuente actual, pero debe documentarse como una decisión con riesgo de cambio de nombre. Si en el futuro se detecta un cambio de nombre en una nueva versión del Excel, el seeder creará un nuevo registro (porque el `source_key` cambiará) y el registro antiguo quedará huérfano. Este escenario se puede mitigar con un chequeo post-importación que detecte materiales sin referencias.

## 7. Decisiones pendientes de aprobación humana

1. ¿Aprobar el uso de `quest_component:{name}`, `sigil:{name}` y `enchantment:{name}` como `source_key`, aceptando el riesgo de cambio de nombre?
2. ¿Se desea implementar una mitigación (detección de materiales huérfanos post-importación)?

## 8. Referencias

- Propuesta `crafting_materials`: [2026-10-14_revision_source_id_crafting_materials.md](file:///home/draven/Documentos/shop-titans-api/docs/reports/2026-10-14_revision_source_id_crafting_materials.md)
- Propuesta D-1: [2026-10-14_propuesta_modelado_blueprint_components.md](file:///home/draven/Documentos/shop-titans-api/docs/reports/2026-10-14_propuesta_modelado_blueprint_components.md)
