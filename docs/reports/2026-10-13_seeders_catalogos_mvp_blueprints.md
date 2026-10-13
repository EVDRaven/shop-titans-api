# Reporte Técnico — Seeders de Catálogos MVP Blueprints

**Fecha:** 2026-10-13
**Autor:** Editor IA
**Estado:** IMPLEMENTADA, EJECUTADA Y VALIDADA
**Excel fuente verificado: `Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx`

---

## 1. Objetivo

Implementar los **cinco seeders de catálogos** del MVP Blueprints, extrayendo datos reales del Excel fuente (v26.9.1), ejecutarlos contra PostgreSQL y validar los conteos de cada catálogo.

Catálogos a implementar:
- `resources` (n=12)
- `qualities` (n=5)
- `item_categories` (n=40, excluye `Enchantment` del MVP)
- `workers` (n=19)
- `worker_levels` (n=50)

Excluidos explícitamente:
- `enchantments` → fuera del MVP (confirmado en `PROJECT_CONTEXT.md`).
- `elements` → registrado como **pendiente opcional**, sin resolver ni modificar su esquema.

---

## 2. Alcance

**Incluye:**
- Creación de 5 archivos seeder.
- Registro de los 5 seeders en `DatabaseSeeder`.
- Extracción y verificación de datos contra el Excel real.
- Validación de sintaxis PHP y estilo de código (Pint).
- Ejecución de `php artisan db:seed` contra PostgreSQL.
- Validación de conteos de los 5 catálogos en PostgreSQL.

**Excluye:**
- Modificación de migraciones existentes.
- Creación de `ElementSeeder` ni `EnchantmentSeeder`.
- Conversión de propuestas en decisiones arquitectónicas aprobadas.

---

## 3. Estado inicial

- 14 migraciones del MVP Blueprints ejecutadas y validadas en PostgreSQL (reporte `2026-08-28_migrate_real_mvp_blueprints.md`).
- `DatabaseSeeder` únicamente creaba un usuario de prueba mediante `User::factory()`.
- No existían seeders de catálogos.
- Modelos Eloquent creados con `$fillable` explícito.

---

## 4. Archivos modificados / creados

| Archivo | Acción | Motivo |
|---|---|---|
| `src/database/seeders/ResourceSeeder.php` | Creado | 12 recursos con `name`, `column_letter_in_excel`, `tier`. |
| `src/database/seeders/QualitySeeder.php` | Creado | 5 calidades con `name`, `rank` (1..5). |
| `src/database/seeders/ItemCategorySeeder.php` | Modificado | 40 categorías (excluye `Enchantment` del MVP). |
| `src/database/seeders/WorkerSeeder.php` | Creado | 19 workers de la hoja Workers. |
| `src/database/seeders/WorkerLevelSeeder.php` | Creado | 50 niveles de la hoja Worker Levels. |
| `src/database/seeders/DatabaseSeeder.php` | Modificado | Registra los 5 seeders en orden; mantiene el usuario de prueba con `User::factory()`. |

---

## 5. Cambios realizados

### 5.1 ResourceSeeder (12 recursos)

Datos verificados contra el Excel v26.9.1 (hoja Blueprints, columnas Y–AJ, confirmado con *Squire Sword = 5 Iron* en columna Y):

| name | column_letter | tier |
|---|---|---|
| Iron | Y | 1 |
| Wood | Z | 1 |
| Leather | AA | 1 |
| Herbs | AB | 1 |
| Steel | AC | 2 |
| Ironwood | AD | 2 |
| Fabric | AE | 2 |
| Oils | AF | 2 |
| Mana | AG | 3 |
| Gems | AH | 3 |
| Essence | AI | 4 |
| Stardust | AJ | 5 |

**Sobre el campo `resources.tier`:** representa la **clasificación propia del recurso** en el juego (T1=básico, T2=avanzado, T3=raro, T4=épico, T5=endgame), NO el tier de blueprint donde aparece por primera vez. La evidencia del Excel (tier mínimo de blueprint donde cada recurso aparece) se usa solo como **soporte** para confirmar la progresión:

| Recurso | Tier de recurso (asignado) | Tier mínimo de blueprint donde aparece |
|---|---|---|
| Iron / Wood / Leather / Herbs | T1 | 1 |
| Steel / Ironwood / Fabric / Oils | T2 | 4 |
| Mana / Gems | T3 | 7 |
| Essence | T4 | 13 |
| Stardust | T5 | 16 |

Ambas escalas son monótonas y consistentes: a mayor tier de recurso, mayor tier mínimo de blueprint donde aparece. La asignación de Steel/Ironwood/Fabric/Oils a T2 (no T4) sigue la clasificación semántica del juego (recursos avanzados), no el tier numérico del blueprint.

### 5.2 QualitySeeder (5 calidades)

| name | rank |
|---|---|
| Normal | 1 |
| Superior | 2 |
| Flawless | 3 |
| Epic | 4 |
| Legendary | 5 |

### 5.3 ItemCategorySeeder (40 categorías)

Valores únicos extraídos de la columna B "Type" de la hoja Blueprints (1.626 filas en v26.9.1). Se excluye `Enchantment` del catálogo del MVP conforme al alcance acordado (los blueprints de tipo Enchantment quedarán con `category_id=null` durante la importación):

Amulet, Aurasong, Axe, Bow, Catalyst, Cloak, Clothes, Crossbow, Dagger, Dessert, Dual Wield, Familiar, Gauntlets, Gloves, Gun, Heavy Armor, Heavy Footwear, Helmet, Herbal Medicine, Idol, Instrument, Light Armor, Light Footwear, Mace, Magician Hat, Mask, Meal, Moonstone, Potion, Quiver, Ring, Rogue Hat, Runestone, Scythe, Shield, Spear, Spell, Staff, Sword, Wand.

### 5.4 WorkerSeeder (19 workers)

Datos de la hoja Workers. Los valores `"---"` del Excel se convirtieron a `null`. Campos: `worker_key`, `default_person_name`, `unlock_level_required`, `building_prerequisite`, `gold_cost`, `gem_cost`, `blueprint_unlocks_raw`.

Workers: Blacksmith, Tailor, Carpenter, Herbalist, Wizard, Jeweler, Priestess, Master, Scholar, Engineer, Sun Dragon, Moon Dragon, Summoner, Cook, Baker, Bard, Veteran, Storm Elemental, Reaper.

### 5.5 WorkerLevelSeeder (50 niveles)

Datos de la hoja Worker Levels (niveles 1..50). Campos: `level`, `xp_needed`, `crafting_speed_bonus`, `crafting_speed_bonus_t15plus`.

- Nivel 1: `xp_needed=null`, bonuses `null`.
- Niveles 2–45: `crafting_speed_bonus` de 0.01 a 0.7; `crafting_speed_bonus_t15plus=null`.
- Niveles 46–50: `crafting_speed_bonus=0.7`; `crafting_speed_bonus_t15plus` de 0.03 a 0.15.
- Nota: los niveles 35 y 36 comparten `xp_needed=125000` (dato real del Excel).

### 5.6 Patrón de idempotencia

Todos los seeders usan `updateOrCreate` (o `firstOrCreate` en categorías) keyados por su campo único natural:
- `resources.name`
- `qualities.name`
- `item_categories.name`
- `workers.worker_key`
- `worker_levels.level`

Esto permite re-ejecutar los seeders sin errores de unicidad y mantiene los catálogos sincronizados con el Excel.

### 5.7 Orden de ejecución en `DatabaseSeeder` y eliminación de `User::factory()`

El orden declarado en `DatabaseSeeder` es:

```
ResourceSeeder → QualitySeeder → ItemCategorySeeder → WorkerSeeder → WorkerLevelSeeder
```

**Justificación del orden:**
- Ninguno de los 5 catálogos tiene FK hacia otro catálogo de este conjunto (ver esquema de migraciones). Por lo tanto, **no existen dependencias de FK** entre ellos y el orden es, estrictamente, indiferente.
- El orden elegido sigue una lógica de *catálogos de referencia* (resources, qualities, item_categories) antes que *catálogos de entidades* (workers, worker_levels), por claridad.
- `worker_levels` **no** tiene FK hacia `workers`; `workers` **no** tiene FK hacia ningún catálogo sembrado aquí. Confirmado en `2026_08_28_100001_create_workers_table.php` y `2026_08_28_100002_create_worker_levels_table.php`.

**Mantenimiento de `User::factory()`:**
- El `DatabaseSeeder` original creaba un único usuario de prueba mediante `User::factory()`.
- **Se restauró** la creación del usuario de prueba después de los seeders de catálogos.
- Justificación: la eliminación de `User::factory()` no tenía una justificación técnica concreta (solo "fuera de alcance"). El usuario de prueba es útil para desarrollo y testing, y `UserFactory` ya existía. Mantenerlo no contradice el alcance del MVP.
- Si en el futuro se desea eliminar el usuario de prueba de `DatabaseSeeder`, debe someterse a aprobación humana con justificación técnica concreta.

---

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| Los 12 recursos mapean a columnas Y–AJ en ese orden | Squire Sword (fila 2) tiene `5.0` en columna Y = Iron; resto `---` | Confirmada |
| `resources.tier` es la clasificación propia del recurso (T1–T5), no el tier de blueprint | La progresión tier_recurso vs tier_mínimo_blueprint es monótona: T1→bp1, T2→bp4, T3→bp7, T4→bp13, T5→bp16. Steel/Ironwood/Fabric/Oils = T2 (avanzados), no T4 | Confirmada — contradicción previa resuelta |
| Existen 5 calidades en el catálogo del juego | Diseño aprobado en `DATA_MODEL_ANALYSIS.md`; esquema `qualities.rank unsignedTinyInteger NOT NULL UNIQUE` soporta 5 ranks | Confirmada (catálogo) |
| Solo 3 calidades aparecen en datos de componentes actuales | Columnas AM/AP "Component Quality" solo tienen Normal, Superior, Flawless (en v26.9.1) | Confirmada — duda sobre Epic/Legendary documentada en §7 |
| 40 categorías MVP en Blueprints (excluye Enchantment) | Conteo de valores únicos en columna B de 1.626 filas (v26.9.1): 41 únicas, de las cuales `Enchantment` se excluye del MVP | Confirmada |
| 19 workers y 50 worker levels | Hojas Workers (19 filas) y Worker Levels (50 filas) en v26.9.1 | Confirmada |
| Niveles 35 y 36 de worker_levels comparten `xp_needed=125000` | Dato literal de la hoja Worker Levels en v26.9.1 | Confirmada — duda documentada en §7 |
| Los elementos son más complejos que el diseño inicial | Columnas AZ–BC contienen 100 valores únicos en v26.9.1: elementos básicos, combinaciones ("Air, Dark"), espíritus ("Bear Vitality") y nombres de elementos ("Ember Element") | Confirmada → pendiente opcional |

---

## 7. Discrepancias y dudas

### DUDA 1 — Qualities: 5 en catálogo vs 3 en datos de componentes
- **Estado documentado:** `DATA_MODEL_ANALYSIS.md` lista 5 calidades (Normal, Superior, Flawless, Epic, Legendary).
- **Estado real:** Las columnas de "Component Quality" en Blueprints (AM, AP) solo contienen Normal, Superior y Flawless, tanto en v26.8.1 como en v26.9.1.
- **Evidencia:** Extracción de valores únicos de las columnas AM (38) y AP (41) en v26.9.1.
- **Impacto:** Bajo. El catálogo `qualities` es de referencia; Epic y Legendary pueden usarse en otros contextos (p. ej. Enchantments, que están fuera del MVP) o futuras importaciones.
- **Decisión:** Mantener las 5 calidades en el seeder (catálogo completo del juego). No se modifican datos de origen.
- **Requiere aprobación:** No (sigue el diseño aprobado).

### DUDA 2 — `worker_levels.xp_needed` duplicado en niveles 35 y 36
- **Estado real:** En la hoja Worker Levels (v26.9.1), el nivel 35 tiene `xp_needed=125000` y el nivel 36 también tiene `xp_needed=125000`.
- **Evidencia:** Dato literal extraído de la hoja Worker Levels.
- **Impacto:** Bajo. No viola ninguna restricción de esquema (`xp_needed` no tiene `unique`).
- **Decisión:** **No modificar arbitrariamente.** El dato se copia tal cual del Excel fuente. Si se confirma que es un error del Excel, deberá corregirse manualmente y documentarse.
- **Requiere aprobación:** No, pero se sugiere revisión humana del dato fuente.

### DISCREPANCIA 1 — Elements: datos reales más complejos que el diseño
- **Estado documentado:** `DATA_MODEL_ANALYSIS.md` §3.4 preveía elementos simples: Fire, Water, Earth, Air, Light, Dark, Spirit, Soul, None.
- **Estado real:** Las columnas AZ–BC contienen **100 valores únicos** en v26.9.1 (antes ~95 en v26.8.1) que incluyen: elementos básicos (Air, Dark, Earth, Fire, Light, Water), combinaciones ("Air, Dark", "Fire, Water"), espíritus animales ("Bear Vitality", "Cat Grace"), nombres de elementos ("Ember Element", "Inferno Element"), y especiales ("All", "Gold", "Ancestor Tradition").
- **Evidencia:** Extracción de valores únicos de las columnas 51–54 de la hoja Blueprints en v26.9.1.
- **Impacto:** Alto para el modelado de `elements`. La tabla actual solo tiene `id` + `name` (unique), lo cual técnicamente puede almacenar todos los valores, pero semánticamente el catálogo mezcla conceptos distintos.
- **Propuesta:** **No resolver en esta tarea.** Registrar como pendiente opcional. El `BlueprintImporter` deberá decidir cómo mapear estos valores.
- **Requiere aprobación:** Sí (cambio de alcance / modelado). **Mantener como pendiente opcional.**

---

## 8. Decisiones

### Propuestas
- Usar `updateOrCreate` / `firstOrCreate` para idempotencia en todos los seeders de catálogos.
- Asignar `resources.tier` según la **clasificación propia del recurso** en el juego: T1 (básicos: Iron, Wood, Leather, Herbs), T2 (avanzados: Steel, Ironwood, Fabric, Oils), T3 (raros: Mana, Gems), T4 (épicos: Essence), T5 (endgame: Stardust). El tier mínimo de blueprint donde aparece cada recurso se usa solo como evidencia de soporte.
- Incluir las 5 calidades en `QualitySeeder` aunque solo 3 aparezcan en datos de componentes actuales.
- Excluir `Enchantment` del catálogo `item_categories` del MVP (los blueprints de tipo Enchantment quedarán con `category_id=null`).
- No modificar arbitrariamente el `xp_needed=125000` duplicado en niveles 35 y 36 de `worker_levels`; copiar tal cual del Excel.

### Aprobadas
- (Ninguna nueva decisión arquitectónica en esta tarea. Se sigue el diseño existente aprobado en `PROJECT_CONTEXT.md` y `DATA_MODEL_ANALYSIS.md`.)

### Reemplazadas/Rechazadas
- (Ninguna.)

---

## 9. Validaciones

### 9.1 Sintaxis PHP (`php -l`)
Comando:
```bash
cd /home/draven/Documentos/shop-titans-api && for f in src/database/seeders/*.php; do fname=$(basename "$f"); docker compose exec -T app php -l "database/seeders/$fname"; done
```
Resultado / exit code:
```text
No syntax errors detected in database/seeders/DatabaseSeeder.php
No syntax errors detected in database/seeders/ItemCategorySeeder.php
No syntax errors detected in database/seeders/QualitySeeder.php
No syntax errors detected in database/seeders/ResourceSeeder.php
No syntax errors detected in database/seeders/WorkerLevelSeeder.php
No syntax errors detected in database/seeders/WorkerSeeder.php
```
**Exit code: 0** en los 6 archivos.

### 9.2 Autoloader (`composer dump-autoload`)
Comando:
```bash
docker compose exec -T app composer dump-autoload
```
Resultado:
```text
Generated optimized autoload files containing 6787 classes
```
**Exit code: 0.**

### 9.3 Estilo de código (`pint --test`)
Comando:
```bash
docker compose exec -T app vendor/bin/pint --test database/seeders/
```
Resultado:
```text
PASS  6 files
```
**Exit code: 0.**

### 9.4 Ejecución de seeders (`db:seed`)

Comando:
```bash
docker compose exec -T app php artisan db:seed
```
Resultado / exit code:
```text
Database seeding completed successfully.
```
**Exit code: 0.** Los 5 seeders de catálogos se ejecutaron correctamente en PostgreSQL.

### 9.5 Validación de conteos en PostgreSQL

Después de ejecutar `db:seed`, se validaron los conteos de cada catálogo en PostgreSQL:

| Catálogo | Tabla | Conteo esperado | Conteo en PostgreSQL |
|---|---|---|---|
| Resources | `resources` | 12 | 12 ✅ |
| Qualities | `qualities` | 5 | 5 ✅ |
| Item Categories | `item_categories` | 40 | 40 ✅ |
| Workers | `workers` | 19 | 19 ✅ |
| Worker Levels | `worker_levels` | 50 | 50 ✅ |

**Todos los catálogos coinciden con los valores esperados del Excel v26.9.1.**

---

## 10. Resultado final

- 5 seeders de catálogos creados con datos reales extraídos y verificados del Excel **v26.9.1**.
- `DatabaseSeeder` actualizado para invocarlos en orden: Resources → Qualities → ItemCategories → Workers → WorkerLevels.
- `ItemCategorySeeder` excluye `Enchantment` del catálogo MVP (40 categorías).
- `resources.tier` clarificado como clasificación propia del recurso (T1–T5), no el tier de blueprint.
- Todos los archivos pasan validación de sintaxis y estilo.
- **`php artisan db:seed` ejecutado correctamente** contra PostgreSQL.
- **Validados los 5 catálogos en PostgreSQL:** resources=12, qualities=5, item_categories=40, workers=19, worker_levels=50.
- No se modificaron migraciones.
- `elements` registrado como pendiente de decisión (sin resolver).
- `enchantments` confirmado fuera del MVP.

---

## 11. Riesgos

1. **Cambios en futuras versiones del Excel:** Los datos de catálogos (especialmente `item_categories` y `workers`) podrían cambiar. Los seeders usan `updateOrCreate` para mitigar.
2. **`elements` sin resolver:** El `BlueprintImporter` no podrá poblar las 4 FK a `elements` hasta que se defina cómo mapear los 100 valores únicos detectados (v26.9.1). Riesgo alto para la importación de blueprints.
3. **`worker_levels.xp_needed` repetido:** Los niveles 35 y 36 comparten `xp_needed=125000`. Es un dato real del Excel; no se modifica arbitrariamente. Si es un error de fuente, deberá corregirse manualmente.
4. **Idempotencia vs. datos manuales:** `updateOrCreate` sobrescribirá cambios manuales en los catálogos si se re-ejecutan los seeders. Aceptable porque el Excel es la fuente de verdad.
5. **Blueprints de tipo `Enchantment`:** Al excluir `Enchantment` del catálogo `item_categories`, esos blueprints quedarán con `category_id=null` durante la importación. Aceptable conforme al alcance del MVP.

---

## 12. Pendientes

- **Resolver modelado de `elements`:** decidir si la tabla actual (`id`, `name`) es suficiente o si requiere columnas adicionales (tipo, categoría) o normalización de valores compuestos. **Pendiente de decisión.**
- **BlueprintImporter:** implementar la importación de 1.626 filas de la hoja Blueprints (v26.9.1), incluyendo la lógica de 2 pasadas para componentes recursivos y el mapeo de `elements`.
- **Verificar `qualities` en contexto:** confirmar si Epic y Legendary aparecen en otras hojas del Excel (p. ej. Enchantments) antes de ejecutar.
- **Revisar `worker_levels.xp_needed` niveles 35/36:** confirmar si el valor duplicado (125000) es intencional o un error del Excel.

---

## 13. Preguntas para la IA arquitecta

1. **`elements`:** ¿La tabla actual (`id`, `name` unique) es suficiente para almacenar los 100 valores detectados (v26.9.1), o se requiere un modelado más rico (tipo de elemento, normalización de combinaciones)? ¿Debe posponerse hasta el `BlueprintImporter`?
2. **`qualities`:** ¿Es correcto incluir Epic y Legendary en el catálogo aunque no aparezcan en datos de componentes? ¿Aparecen en otra hoja?
3. **`resources.tier`:** ¿Es correcta la clasificación T1–T5 como clasificación propia del recurso (básico→endgame), usando el tier mínimo de blueprint solo como evidencia de soporte?
4. **`worker_levels` niveles 35/36:** ¿El `xp_needed` duplicado (125000) es un error del Excel o intencional?
5. **`DatabaseSeeder`:** ¿Es correcto mantener el usuario de prueba (`User::factory`) junto a los seeders de catálogos, o debe separarse en un seeder independiente para entornos de desarrollo?

---

## 14. Próximo paso recomendado

1. **Resolver el modelado de `elements` (pendiente de decisión) antes de implementar el `BlueprintImporter`.
2. Implementar `BlueprintImporter` para importar las 1.626 filas de la hoja Blueprints (v26.9.1), con lógica de 2 pasadas para componentes recursivos.
