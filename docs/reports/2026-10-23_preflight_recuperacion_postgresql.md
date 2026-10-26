# Reporte Técnico — Preflight de recuperación de PostgreSQL (solo lectura)

**Fecha:** 2026-10-23
**Autor:** Editor IA
**Estado:** PREPARACIÓN COMPLETADA — RECUPERACIÓN **NO AUTORIZADA NI EJECUTADA**

---

## 1. Objetivo

Preparar un plan **preciso, verificable y seguro** para recuperar los datos originales de la base de desarrollo `shoptitans` (PostgreSQL 17) desde el Excel de Shop Titans, los seeders y los importadores del proyecto, **sin ejecutar la recuperación**.

Este informe entrega, para su revisión humana:

1. La verificación de las fuentes (Excel, seeders, importadores, idempotencia y dependencias).
2. El procedimiento reconstruido, en orden, con los comandos sugeridos (no ejecutados) y las tablas afectadas.
3. Las validaciones de aceptación (conteos, integridad referencial, duplicados, orden AZ, incidencias de importación).
4. Los riesgos detectados y las condiciones de seguridad (incluida una guarda previa que aborta si la conexión no es la esperada, y una estrategia de respaldo recomendada — sin generarla).

## 2. Alcance

**Incluye:** inspección de código/configuración/archivos y del Excel, consultas de **solo lectura** a PostgreSQL y derivación de los conteos esperados a partir del Excel.

**Excluye:** toda escritura en PostgreSQL; ejecución de migraciones, seeders, importadores, `db:seed`, `migrate:fresh|refresh`, `db:wipe` o equivalentes; modificación de código/configuración/Excel/archivos existentes; recreación o reinicio de contenedores o volúmenes; commits o pushes.

> **Restricción aplicada:** ninguna comprobación de este preflight implicó riesgo de escritura. No fue necesario detenerse por esa causa. Las únicas operaciones contra PostgreSQL fueron `SELECT` de solo lectura.

## 3. Estado inicial

- Base: `shoptitans`, usuario `shoptitans`, PostgreSQL 17 (contenedor `shoptitans-postgres`, `Up`). Contenedor `shoptitans-app` `Up`.
- **Conteos reales actuales (comprobados por consultas de solo lectura):** tablas de datos = **0 filas**; solo `migrations` con **24** filas. El esquema existe y está completo.

```text
blueprint_ascension_upgrades|0      blueprints|0                        migrations|24
blueprint_components|0              blueprint_starforged_milestones|0   qualities|0
blueprint_crafting_upgrades|0       blueprint_transcendence_upgrades|0  resources|0
blueprint_elemental_affinities|0    blueprint_workers|0                 spirit_affinities|0
blueprint_resources|0               crafting_materials|0                spirits|0
elements|0                          enchantments|0                     users|0
item_categories|0                   worker_levels|0                     workers|0
```

- Contexto: el incidente del 2026-10-23 ~03:28 UTC reconstruyó el esquema y revirtió las inserciones (ver `docs/reports/2026-10-23_diagnostico_postgresql_vacio.md`). La verificación de aislamiento de PHPUnit ya está completada (`docs/reports/2026-10-23_verificacion_aislamiento_phpunit.md`): SQLite `:memory:`, sin conexión a PostgreSQL.

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| `docs/reports/2026-10-23_preflight_recuperacion_postgresql.md` | Creado | Informe de este preflight |
| `AI_WORK_LOG.md` | Modificado | Registrar el hito y el estado del preflight |

**No** se modificó PostgreSQL, ni código, ni configuración, ni el Excel, ni contenedores/volúmenes. **No** se alteraron informes históricos.

## 5. Verificación de las fuentes

### 5.1 Excel

- **Ubicación canónica (contenedor `app`):** `/var/www/html/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx`.
  Ruta en host: `src/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx`.
- **Tamaño:** 1.367.141 bytes. Existe además una copia en la raíz del proyecto (mismo tamaño; artefacto untracked, no usado por el código).
- **Resolución de ruta en código:** `BlueprintCatalogSeeder::resolveFilePath()` y `CraftingMaterialSeeder::resolveFilePath()` buscan la ruta por defecto `storage_path('app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx')`; si no existe, usan `glob(storage_path('app/*.xlsx'))`; si no hay ninguna, lanzan `RuntimeException`.
- **Hojas usadas:** `Blueprints`, `Quest Components`, `Full Moon Fusions`, `Enchantments` (todas presentes en el libro; el libro tiene 31 hojas en total).
- **Columnas usadas** (hoja `Blueprints`, desde la fila 2 hasta `getHighestRow()` = 1627):

  | Grupo | Columnas |
  |---|---|
  | Identidad / clasificación | `A` Name, `B` Type |
  | Desbloqueo / coste | `C` Unlock Prerequisite, `D` Research Scrolls, `E` Antique Tokens, `F` Tier, `G` Gold Value |
  | Tiempo / valor | `H` Craft Time (s), `I` Craft Time (formatted), `J` Value/s |
  | XP / favor / airship | `K` Merchant XP, `L` Merchant XP/s, `M` Worker XP, `N` Fusion XP, `O` Favor, `P` Airship Power |
  | Workers (3) | `R`/`S`, `T`/`U`, `V`/`W` (nombre/nivel) |
  | Recursos (12) | `Y` Iron, `Z` Wood, `AA` Leather, `AB` Herbs, `AC` Steel, `AD` Ironwood, `AE` Fabric, `AF` Oils, `AG` Mana, `AH` Gems, `AI` Essence, `AJ` Stardust |
  | Componentes (2) | `AL`/`AM`/`AN` y `AO`/`AP`/`AQ` (nombre/calidad/cantidad) |
  | Estadísticas | `AS` ATK, `AT` DEF, `AU` HP, `AV` EVA, `AW` CRIT, `AX` Mana Shield |
  | Afinidades | `AZ` Elemental Affinity, `BA` Spirit Affinity, `BB` Built-In Element, `BC` Built-In Spirit |
  | Upgrades | Crafting `BE/BF`..`BM/BN` (5); Starforged `BP/BQ`..`BX/BY` (5); Ascension `CA/CB`..`CE/CF` (3); Transcendence `CG/CH`..`CK/CL` (3) |
  | Energías / antigüedad | `CN` Discount, `CO` Surcharge, `CP` Suggest, `CQ` Speed-Up, `CS` Available As Antique At |

  Otras hojas: `Quest Components` (`A` Name, `B` Type, `C` Tier, `D` Gold Value, `E` Quest Area), `Full Moon Fusions` (`A` Name, `B` Type, `C` Worker Level, `E` Fuse Time (s), `G` Worker XP, `J` Tier), `Enchantments` (`A` Name, `B` Type).

- **Regla de exclusión:** las filas con `B` = `Enchantment` se excluyen de `blueprints`, `blueprint_components` y del catálogo de componentes; se recogen aparte en `readEnchantmentNames()` / `readCraftingMaterialData()['enchantmentNames']`.

### 5.2 Conteos esperados derivados del Excel (comprobación independiente)

Se derivaron los conteos **a partir del propio Excel** (lectura de solo lectura, misma lógica que `BlueprintExcelReader`) y **coinciden exactamente** con las referencias históricas del usuario y con la ejecución validada del 2026-10-16:

| Magnitud derivada del Excel | Valor | Referencia histórica |
|---|---|---|
| Filas `Blueprints` no-`Enchantment` | **1.549** | `blueprints`: 1.549 ✓ |
| Filas `Blueprints` con tipo `Enchantment` (excluidas) | 77 | (excluidas por diseño) |
| Filas útiles de la hoja `Enchantments` | **73** | `enchantments`: 73 ✓ |
| Tokens distintos de `AZ` (elementos base) | **8** | `elements`: 8 ✓ |
| Tokens distintos de `BA` | **38** | `spirit_affinities`: 38 ✓ |
| Tokens distintos de `BC` | **1** | `spirits`: 1 ✓ |
| Nombres de componente únicos (`AL`/`AO` en filas no-`Enchantment`) | **186** | `crafting_materials`: 186 ✓ |
| Filas de `Quest Components` | 66 | (insumo de `crafting_materials`) |
| Filas `Sigil of...` en `Full Moon Fusions` | 6 | (insumo de `crafting_materials`) |

> `blueprint_components` (2.747) y `blueprint_elemental_affinities` (911) no se derivan por simple conteo de filas: dependen de las referencias por fila y de los tokens de `AZ`. Sus valores de referencia (2.747 y 911) provienen de la ejecución validada del 2026-10-16 y se comprobarán post-recuperación (§9).

### 5.3 Seeders e importadores necesarios

Doce (`12`) archivos en `src/database/seeders/` y dos (`2`) comandos Artisan.

| # | Fuente | Tipo | Excel | Idempotente | Tabla(s) afectada(s) | Conteo esperado |
|---|---|---|---|---|---|---|
| 1 | `ResourceSeeder` | Seeder (datos en código) | No | Sí (`updateOrCreate`/`firstOrCreate`) | `resources` | 12 |
| 2 | `QualitySeeder` | Seeder (datos en código) | No | Sí | `qualities` | 5 |
| 3 | `ItemCategorySeeder` | Seeder (datos en código) | No | Sí (`firstOrCreate`) | `item_categories` | 40 |
| 4 | `WorkerSeeder` | Seeder (datos en código) | No | Sí | `workers` | 19 |
| 5 | `WorkerLevelSeeder` | Seeder (datos en código) | No | Sí | `worker_levels` | 50 |
| 6 | `ElementSeeder` | Seeder (Excel, vía `BlueprintCatalogSeeder`) | Sí (`AZ`) | Sí (`updateOrCreate`) | `elements` | 8 |
| 7 | `EnchantmentSeeder` | Seeder (Excel) | Sí (hoja `Enchantments`) | Sí | `enchantments` | 73 |
| 8 | `SpiritAffinitySeeder` | Seeder (Excel) | Sí (`BA`) | Sí | `spirit_affinities` | 38 |
| 9 | `SpiritSeeder` | Seeder (Excel) | Sí (`BC`) | Sí | `spirits` | 1 |
| 10 | `CraftingMaterialSeeder` | Seeder (Excel) | Sí (varias hojas) | Sí (`updateOrCreate` por `source_key`) | `crafting_materials` | 186 |
| 11 | `import:blueprints` | Comando → `BlueprintImportService::import()` | Sí | Sí (blueprints por `name`; relaciones *delete-then-create* scoped) | `blueprints` + 7 tablas de relación | 1.549 |
| 12 | `import:blueprint-components` | Comando → `BlueprintImportService::importComponents()` | Sí | Sí (`delete-then-create` por blueprint) | `blueprint_components` | 2.747 |

**Dependencias:**
- `import:blueprints` carga catálogos: `item_categories`, `workers`, `resources`, `elements`, `spirit_affinities`, `enchantments`, `spirits`. **No** necesita `crafting_materials`.
- `CraftingMaterialSeeder` usa `Blueprint::all()` para los materiales de tipo `blueprint:` → **requiere `blueprints` ya cargados**; los tipos `quest_component:`, `sigil:` y `enchantment:` no dependen de `blueprints`.
- `import:blueprint-components` requiere `blueprints`, `crafting_materials` y `qualities`.
- Los cinco seeders de catálogo en código (1–5) no tienen dependencias entre sí ni del Excel.

**Idempotencia (por inspección de código):**
- Catálogos 1–9: `updateOrCreate`/`firstOrCreate` por clave natural (`name`, `worker_key`, `level`) → reejecutables sin duplicar.
- `CraftingMaterialSeeder`: `updateOrCreate` por `source_key` (`blueprint:{id}`, `quest_component:{name}`, `sigil:{name}`, `enchantment:{name}`) → reejecutable.
- `import:blueprints`: `Blueprint::updateOrCreate(['name' => ...])`; las relaciones se reconstruyen con `->delete()` previo **acotado al blueprint padre** (`blueprintWorkers`, `blueprintResources`, `elementalAffinities`, `craftingUpgrades`, `starforgedMilestones`, `ascensionUpgrades`, `transcendenceUpgrades`). Validación previa: si hay errores, **aborta sin escribir**.
- `import:blueprint-components`: por cada fila, `$blueprint->blueprintComponentsAsParent()->delete()` y recreación (acotado al blueprint), dentro de una transacción global.

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| El Excel está disponible y es el que espera el código | Archivo presente en `storage/app` (1.367.141 bytes); ruta por defecto coincide; hojas y columnas mapeadas | Confirmada |
| Todas las tablas de datos están vacías | `SELECT count(*)` de solo lectura → 0 en todas; solo `migrations`=24 | Confirmada |
| Las referencias históricas de conteo son reproducibles desde el Excel | Conteos derivados del Excel == referencias (1.549/73/8/38/1/186) | Confirmada |
| Ningún seeder/comando ejecuta `TRUNCATE`/`DROP`/`db:wipe`/`migrate:fresh` | Inspección de los 12 seeders y 2 comandos + `BlueprintImportService`: solo `delete()` scoped y `updateOrCreate` | Confirmada |
| El seeder global (`DatabaseSeeder`) crea un usuario de prueba | `DatabaseSeeder::run()` llama a `User::factory()->create(['name'=>'Test User','email'=>'test@example.com'])` | Confirmada |

## 7. Discrepancias

| # | Estado documentado | Estado real | Evidencia | Impacto | Propuesta |
|---|---|---|---|---|---|
| PD-1 | La hoja `Blueprints` contiene 77 filas de tipo `Enchantment` | El catálogo `enchantments` se llena desde la hoja **`Enchantments`** (73 filas con nombre y tipo), no desde `Blueprints` | Derivación del Excel: `Blueprints` 77 vs `Enchantments` 73; referencia histórica `enchantments`=73 | Ninguno funcional: 73 es el valor correcto y validado | Documentar (no requiere acción) |
| PD-2 | `src/database/database.sqlite` podría parecer una fuente | Es un residuo obsoleto sin las tablas del MVP | Diagnóstico previo | Riesgo de confusión en la recuperación | **No** usarlo como fuente |
| PD-3 | El `db:seed` global sería una vía natural de “sembrar todo” | `db:seed` ejecuta `DatabaseSeeder`, que **crea un usuario** y **no** siembra `crafting_materials` | Código de `DatabaseSeeder` | Ejecutar `db:seed` ensuciaría `users` y dejaría `crafting_materials` vacío | Sembrar **por clase** (§8) y **no** usar `db:seed` global |

## 8. Procedimiento propuesto (orden de operaciones)

> **Todos los comandos se listan como sugerencia y NO se han ejecutado.** Todos se ejecutan desde el contenedor `app` (conexión `pgsql` → `shoptitans`).
> Supuesto de ejecución: los contenedores no se recrean; el Excel permanece en `storage/app`.

**Referencia del Excel (dentro del contenedor):**
```text
/var/www/html/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx
```

### Fase 0 — Respaldo (recomendado; NO generado en este preflight)

Ver §11. Se debe generar un respaldo lógico antes de cualquier escritura.

### Fase 1 — Catálogos deterministas (sin Excel)

Orden no dependiente; seguro e idempotente.

```bash
docker compose exec -T app php artisan db:seed --class=ResourceSeeder
docker compose exec -T app php artisan db:seed --class=QualitySeeder
docker compose exec -T app php artisan db:seed --class=ItemCategorySeeder
docker compose exec -T app php artisan db:seed --class=WorkerSeeder
docker compose exec -T app php artisan db:seed --class=WorkerLevelSeeder
```

Tablas afectadas: `resources` (12), `qualities` (5), `item_categories` (40), `workers` (19), `worker_levels` (50).

### Fase 2 — Catálogos derivados del Excel (requieren el Excel)

```bash
docker compose exec -T app php artisan db:seed --class=ElementSeeder
docker compose exec -T app php artisan db:seed --class=EnchantmentSeeder
docker compose exec -T app php artisan db:seed --class=SpiritAffinitySeeder
docker compose exec -T app php artisan db:seed --class=SpiritSeeder
```

Tablas afectadas: `elements` (8), `enchantments` (73), `spirit_affinities` (38), `spirits` (1).
Si el Excel no se encuentra, cada seeder lanza `RuntimeException` (fallo explícito, sin escritura parcial de catálogo).

### Fase 3 — Blueprints y sus relaciones

```bash
docker compose exec -T app php artisan import:blueprints "/var/www/html/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx"
```

Tablas afectadas: `blueprints` (1.549) y, reconstruidas por blueprint, `blueprint_workers`, `blueprint_resources`, `blueprint_elemental_affinities` (911), `blueprint_crafting_upgrades`, `blueprint_starforged_milestones`, `blueprint_ascension_upgrades`, `blueprint_transcendence_upgrades`.
Requiere Fases 1 y 2 completas. Si la validación previa detecta cualquier referencia sin resolver, **aborta sin escribir** y devuelve `FAILURE`.

### Fase 4 — Materiales de fabricación

```bash
docker compose exec -T app php artisan db:seed --class=CraftingMaterialSeeder
```

Tabla afectada: `crafting_materials` (186 = 99 `blueprint` + 66 `quest_component` + 6 `sigil` + 15 `enchantment`).
Requiere Fase 3 (para los materiales de tipo `blueprint:`). Emite advertencias si un componente no se clasifica; las advertencias se deben revisar.

### Fase 5 — Componentes de blueprints

```bash
docker compose exec -T app php artisan import:blueprint-components "/var/www/html/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx"
```

Tabla afectada: `blueprint_components` (2.747).
Requiere Fases 1 (`qualities`), 3 (`blueprints`) y 4 (`crafting_materials`).
Devuelve `FAILURE` (exit ≠ 0) si hay referencias sin resolver, blueprints omitidos o errores → **condición de parada** (§9.4).

### Notas de ejecución

- **Memoria:** el importador carga el libro completo con PhpSpreadsheet; el pico real medido fue ~150.5 MB. La imagen ya fija `memory_limit=512M` de forma permanente (2026-10-15). Verificar antes con `docker compose exec -T app php -r 'echo ini_get("memory_limit");'` → `512M`.
- **No usar** `db:seed` sin `--class` (ver PD-3) ni `migrate:fresh|refresh|db:wipe` (destructivos, fuera de alcance).
- **No** usar la copia del Excel en la raíz del proyecto; usar la de `storage/app` (la que resuelve el código).

## 9. Validaciones de aceptación (a ejecutar tras la recuperación; solo lectura)

### 9.1 Conteos (referencias históricas — no son los conteos actuales, que son 0)

```sql
SELECT 'blueprints' AS t, count(*) FROM blueprints
UNION ALL SELECT 'blueprint_components', count(*) FROM blueprint_components
UNION ALL SELECT 'crafting_materials', count(*) FROM crafting_materials
UNION ALL SELECT 'elements', count(*) FROM elements
UNION ALL SELECT 'enchantments', count(*) FROM enchantments
UNION ALL SELECT 'spirit_affinities', count(*) FROM spirit_affinities
UNION ALL SELECT 'spirits', count(*) FROM spirits
UNION ALL SELECT 'blueprint_elemental_affinities', count(*) FROM blueprint_elemental_affinities
ORDER BY 1;
```

| Tabla | Esperado |
|---|---|
| `blueprints` | 1.549 |
| `blueprint_components` | 2.747 |
| `crafting_materials` | 186 |
| `elements` | 8 |
| `enchantments` | 73 |
| `spirit_affinities` | 38 |
| `spirits` | 1 |
| `blueprint_elemental_affinities` | 911 |

Referencias adicionales (de la ejecución validada del 2026-10-16, útiles como control): `blueprint_workers`=3.744, `blueprint_resources`=4.135, `blueprint_crafting_upgrades`=7.745, `blueprint_starforged_milestones`=7.745, `blueprint_ascension_upgrades`=4.647, `blueprint_transcendence_upgrades`=4.647, `resources`=12, `qualities`=5, `item_categories`=40, `workers`=19, `worker_levels`=50.

### 9.2 Claves foráneas y registros huérfanos (todos deben devolver 0)

```sql
SELECT count(*) FROM blueprint_components bc LEFT JOIN crafting_materials cm ON cm.id = bc.component_id WHERE cm.id IS NULL;
SELECT count(*) FROM blueprint_components bc LEFT JOIN qualities q ON q.id = bc.quality_id WHERE bc.quality_id IS NOT NULL AND q.id IS NULL;
SELECT count(*) FROM blueprint_elemental_affinities a LEFT JOIN blueprints b ON b.id = a.blueprint_id WHERE b.id IS NULL;
SELECT count(*) FROM blueprint_elemental_affinities a LEFT JOIN elements e ON e.id = a.element_id WHERE e.id IS NULL;
SELECT count(*) FROM blueprints b LEFT JOIN item_categories c ON c.id = b.category_id WHERE c.id IS NULL;
SELECT count(*) FROM blueprints b LEFT JOIN spirit_affinities s ON s.id = b.spirit_affinity_id WHERE b.spirit_affinity_id IS NOT NULL AND s.id IS NULL;
SELECT count(*) FROM blueprints b LEFT JOIN enchantments e ON e.id = b.built_in_element_id WHERE b.built_in_element_id IS NOT NULL AND e.id IS NULL;
SELECT count(*) FROM blueprints b LEFT JOIN spirits s ON s.id = b.built_in_spirit_id WHERE b.built_in_spirit_id IS NOT NULL AND s.id IS NULL;
SELECT count(*) FROM blueprint_workers w LEFT JOIN workers k ON k.id = w.worker_id WHERE k.id IS NULL;
SELECT count(*) FROM blueprint_resources r LEFT JOIN resources rs ON rs.id = r.resource_id WHERE rs.id IS NULL;
SELECT count(*) FROM crafting_materials m LEFT JOIN blueprints b ON b.id = m.blueprint_id WHERE m.blueprint_id IS NOT NULL AND b.id IS NULL;
```

### 9.3 Duplicados (todos deben devolver 0 filas)

```sql
SELECT name, count(*) FROM blueprints GROUP BY name HAVING count(*) > 1;
SELECT source_key, count(*) FROM crafting_materials GROUP BY source_key HAVING count(*) > 1;
SELECT blueprint_id, element_id, count(*) FROM blueprint_elemental_affinities GROUP BY 1,2 HAVING count(*) > 1;
SELECT blueprint_id, position, count(*) FROM blueprint_elemental_affinities GROUP BY 1,2 HAVING count(*) > 1;
SELECT blueprint_id, component_id, quality_id, count(*) FROM blueprint_components GROUP BY 1,2,3 HAVING count(*) > 1;
```

### 9.4 Incidencias de importación

- `import:blueprints`: la tabla de métricas debe mostrar `Blueprints omitidos = 0` y **0 errores** (exit 0).
- `import:blueprint-components`: `Referencias sin resolver = 0`, `Blueprints omitidos = 0`, `Errores = 0` → exit 0. Un exit ≠ 0 indica datos incompletos y **debe tratarse como fallo** del proceso de recuperación.
- `CraftingMaterialSeeder`: sin advertencias de “componente no clasificado”.

### 9.5 Afinidades AZ y su orden

```sql
-- 1) Restricción de posición (debe ser 0; hay CHECK position IN (1,2))
SELECT count(*) FROM blueprint_elemental_affinities WHERE position NOT IN (1,2);

-- 2) Blueprints con 2 elementos (esperado: 109 → 802 con 1 + 109 con 2 = 911)
SELECT count(*) FROM (SELECT blueprint_id FROM blueprint_elemental_affinities GROUP BY blueprint_id HAVING count(*) = 2) t;

-- 3) Distribución por elemento (referencia 2026-10-16): Dark 177 · Earth 160 · Light 139 ·
--    Water 118 · Gold 103 · Air 101 · Fire 87 · All 26  (Σ = 911)
SELECT e.name, count(*) FROM blueprint_elemental_affinities a JOIN elements e ON e.id = a.element_id GROUP BY e.name ORDER BY 2 DESC;

-- 4) Orden AZ preservado: position 1 debe coincidir con el primer token del raw_value
SELECT count(*) FROM blueprint_elemental_affinities a
JOIN elements e ON e.id = a.element_id
WHERE a.position = 1 AND trim(split_part(a.raw_value, ',', 1)) <> e.name;
-- Debe ser 0. Además deben coexistir 'Dark, Earth' y 'Earth, Dark'.
```

### 9.6 Exclusiones y coherencia de catálogos

```sql
-- No debe haber blueprints de categoría Enchantment (esperado 0)
SELECT count(*) FROM blueprints b JOIN item_categories c ON c.id = b.category_id WHERE c.name = 'Enchantment';

-- built_in_element_id debe apuntar siempre a enchantments de tipo 'Element' (esperado 0)
SELECT count(*) FROM blueprints b JOIN enchantments e ON e.id = b.built_in_element_id WHERE e.type <> 'Element';

-- Distribución de material_type en crafting_materials (esperado 99/66/6/15)
SELECT material_type, count(*) FROM crafting_materials GROUP BY material_type ORDER BY 1;
```

## 10. Resultado final

- **Fuentes verificadas:** Excel ubicado y legible; hojas y columnas de los importadores identificadas; 12 seeders + 2 comandos inventariados; dependencias e idempotencia confirmadas por inspección de código.
- **Procedimiento reconstruido:** 5 fases ordenadas (catálogos en código → catálogos del Excel → blueprints → `crafting_materials` → componentes), con comandos sugeridos y tablas afectadas.
- **Validaciones definidas:** conteos, integridad referencial, duplicados, orden AZ, exclusiones y control de incidencias de importación.
- **Estado actual de la base:** todas las tablas de datos en 0 (solo `migrations`=24).

> **PREPARACIÓN COMPLETADA — RECUPERACIÓN NO AUTORIZADA NI EJECUTADA.**
> Ningún comando de la §8 se ejecutó. No se escribió en PostgreSQL, no se modificaron archivos del proyecto (salvo este informe y `AI_WORK_LOG.md`), no se recrearon contenedores/volúmenes y no hubo commit ni push.

## 11. Riesgos

| # | Riesgo | Detalle | Mitigación propuesta |
|---|---|---|---|
| R-1 | **`db:seed` global ejecuta `User::factory()->create()`** | `DatabaseSeeder` crea `Test User`/`test@example.com` y **no** siembra `crafting_materials`. Además `users.email` es `UNIQUE`: una segunda ejecución fallaría | **No** usar `db:seed` sin `--class`. Sembrar por clase (Fases 1, 2, 4). Evitar crear usuarios de prueba en la base de desarrollo |
| R-2 | **Delete-then-create en relaciones** | `import:blueprints` y `import:blueprint-components` borran y recrean filas de relación, acotadas al blueprint padre | Sobre tablas ya vacías es seguro. Reejecuciones son deterministas mientras el Excel no cambie. No ejecutar con un Excel distinto al v26.9.1 |
| R-3 | **Reasignación de IDs** | Los `delete-then-create` de relaciones generan IDs nuevos; no afecta a FKs externas (no hay tablas que dependan de esos IDs) | Verificar §9.2/§9.3 post-recuperación |
| R-4 | **Dependencia de entorno** | Los comandos escriben según `DB_CONNECTION`/`DB_DATABASE` del contenedor. Un contexto con otra conexión escribiría en la base equivocada | **Guarda previa obligatoria** (§12) + ejecutar solo desde el contenedor `app` con `.env` sin cambios |
| R-5 | **Memoria PHP** | PhpSpreadsheet requiere ~150.5 MB de pico al cargar el libro | La imagen fija `memory_limit=512M`; verificar antes de importar |
| R-6 | **Excel incorrecto/ausente** | El código usa una ruta por defecto y un `glob` de respaldo (podría tomar otro `.xlsx`) | Confirmar ruta y versión (v26.9.1, 1.367.141 bytes) antes de importar; el `glob` de respaldo podría seleccionar un archivo inesperado si hubiera más de un `.xlsx` en `storage/app` (hoy solo hay uno) |
| R-7 | **Falta de respaldo** | No existe dump ni PITR del estado anterior (ver diagnóstico previo) | Generar respaldo antes de escribir (§ ver “Estrategia de respaldo”) |
| R-8 | **Orden incorrecto** | Ejecutar `import:blueprint-components` antes de `crafting_materials` daría “referencias sin resolver” (datos incompletos) | Respetar el orden de §8; comprobar exit codes y §9.4 |

### Estrategia de respaldo recomendada (NO generada)

1. **Respaldo lógico (obligatorio antes de escribir):**
   ```bash
   docker compose exec -T postgres pg_dump -U shoptitans -d shoptitans -Fc > backups/shoptitans_pre_recuperacion_$(date +%Y%m%d_%H%M%S).dump
   ```
   (o `pg_dumpall` para incluir roles/globals). Verificar el tamaño y que `pg_restore --list` lea el archivo.
2. **Segunda capa (opcional):** instantánea/copia del volumen `shop-titans-api_postgres_data` antes de la recarga (sin recrear el volumen ni el contenedor).
3. Conservar el respaldo hasta validar §9 completo.

### Guarda previa (abortar si la conexión no es la esperada)

Antes de cualquier escritura, comprobar de forma explícita la conexión y abortar si no coincide:

```sql
-- Debe devolver: shoptitans | shoptitans | <IP del contenedor postgres>
SELECT current_database(), current_user, inet_server_addr();
```

y, desde el contenedor `app`:

```bash
docker compose exec -T app php artisan tinker --execute='echo config("database.default"), " / ", config("database.connections.".config("database.default").".database");'
# Esperado: pgsql / shoptitans
```

Condición de parada: si `current_database() <> 'shoptitans'` o el driver/database no es `pgsql`/`shoptitans`, **detenerse** y no ejecutar ninguna Fase. No usar contextos `--env=testing` para la recuperación.

## 12. Pendientes

1. **Revisión humana de este preflight.** No se autoriza ninguna escritura con este informe.
2. Decidir si se genera el respaldo (Fase 0) y su ubicación exacta.
3. Decidir si `CraftingMaterialSeeder` se añade a `DatabaseSeeder` (hoy no está) — decisión de código, fuera del alcance de solo lectura.
4. Autorizar (por separado y explícitamente) la ejecución de las Fases 1–5.
5. Tras la recuperación, ejecutar las validaciones de §9 y comparar contra las referencias.

## 13. Preguntas para ChatGPT

1. ¿Se confirma el orden propuesto (catálogos en código → catálogos del Excel → `import:blueprints` → `CraftingMaterialSeeder` → `import:blueprint-components`), dada la dependencia de `CraftingMaterialSeeder` con `blueprints` y de los componentes con `crafting_materials`?
2. ¿Se acepta la estrategia de respaldo con `pg_dump -Fc` como obligatoria y la copia del volumen como opcional?
3. ¿Se acepta la guarda previa (`current_database()`/`current_user` + verificación de `config('database.default')`) como condición de parada, y la prohibición expresa de usar `db:seed` global?
4. ¿Se acepta que `enchantments`=73 (hoja `Enchantments`) sea el valor correcto, pese a que la hoja `Blueprints` tenga 77 filas de tipo `Enchantment`?

## 14. Próximo paso recomendado

**Detenerse y esperar autorización humana explícita.** La autorización de este preflight no permite escribir en PostgreSQL. Ante una autorización de recuperación, la secuencia recomendada sería: (1) generar el respaldo; (2) ejecutar la guarda previa y abortar si no se cumple; (3) ejecutar las Fases 1→5 en orden, verificando exit codes; (4) ejecutar las validaciones de §9 y comparar con las referencias históricas.
