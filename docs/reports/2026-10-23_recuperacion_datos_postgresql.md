# Reporte Técnico — Recuperación de datos de PostgreSQL (ejecutada)

**Fecha:** 2026-10-23
**Autor:** Editor IA
**Estado:** RECUPERACIÓN EJECUTADA Y VALIDADA — EXITOSA

---

## 1. Objetivo

Recuperar los datos originales de la base de desarrollo `shoptitans` (PostgreSQL 17) desde el Excel de Shop Titans, los seeders y los importadores del proyecto, siguiendo el plan del preflight `docs/reports/2026-10-23_preflight_recuperacion_postgresql.md` y **con autorización humana explícita** (`EJECUTAR RECUPERACIÓN DE DATOS — AUTORIZADO`).

## 2. Alcance

**Incluye:** respaldo lógico previo, verificación de conexión, ejecución de las Fases 1–5 del preflight (seeders por clase + importadores) y las validaciones definidas.

**Excluye (no ejecutado):** `db:seed` global; `migrate:fresh`, `migrate:refresh`, `db:wipe`; eliminación/recreación de volúmenes o contenedores; modificación de esquema o de código; commits o pushes.

## 3. Estado inicial

- Base `shoptitans` con esquema completo; **todas las tablas de datos = 0**; solo `migrations` = 24.
- Contenedores `app` y `postgres` `Up`. `memory_limit=512M` confirmado.
- Excel presente en `storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx` (1.367.141 bytes).

## 4. Archivos generados / modificados

| Ruta | Acción |
|---|---|
| `backups/shoptitans_pre_recuperacion_20261023_203355.dump` | **Generado** (respaldo lógico, CUSTOM, 83.238 bytes, 229 TOC entries) |
| `docs/reports/2026-10-23_recuperacion_datos_postgresql.md` | **Creado** (este informe) |
| `AI_WORK_LOG.md` | **Modificado** (registro del hito) |

**No** se alteraron informes históricos ni código/configuración ni el Excel. **No** hubo commit ni push.

## 5. Respaldo (Fase 0)

```bash
docker compose exec -T postgres pg_dump -U shoptitans -d shoptitans -Fc > backups/shoptitans_pre_recuperacion_20261023_203355.dump
# exit=0 → 83.238 bytes
docker compose exec -T postgres pg_restore --list < <dump>
# exit=0 → "Format: CUSTOM", "dbname: shoptitans", "TOC Entries: 229", pg_dump 17.10
```

El archivo es legible por `pg_restore` (cabecera válida y TOC listable). Ruta absoluta:
`/home/draven/Documentos/shop-titans-api/backups/shoptitans_pre_recuperacion_20261023_203355.dump`.

## 6. Verificación de conexión (Fase 0b)

```sql
SELECT current_database(), current_user, inet_server_addr();
-- shoptitans|shoptitans|   (conexión por socket local dentro del contenedor postgres)
```

```bash
docker compose exec -T app php artisan tinker --execute='echo config("database.default"), " / ", config("database.connections.".config("database.default").".database"), " / ", config("database.connections.".config("database.default").".username"), PHP_EOL;'
-- pgsql / shoptitans / shoptitans
```

**Coincide exactamente** con lo esperado (`pgsql` / `shoptitans` / usuario `shoptitans`) → se procedió.

## 7. Procedimiento ejecutado y resultados por fase

> Todos los comandos se ejecutaron desde el contenedor `app`; **exit code comprobado en cada fase**.

### Fase 1 — Catálogos en código

```bash
for S in ResourceSeeder QualitySeeder ItemCategorySeeder WorkerSeeder WorkerLevelSeeder; do
  docker compose exec -T app php artisan db:seed --class=$S
done
```

| Seeder | exit | Filas esperadas | Filas verificadas |
|---|---|---|---|
| `ResourceSeeder` | 0 | 12 | 12 ✓ |
| `QualitySeeder` | 0 | 5 | 5 ✓ |
| `ItemCategorySeeder` | 0 | 40 | 40 ✓ |
| `WorkerSeeder` | 0 | 19 | 19 ✓ |
| `WorkerLevelSeeder` | 0 | 50 | 50 ✓ |

### Fase 2 — Catálogos del Excel

```bash
for S in ElementSeeder EnchantmentSeeder SpiritAffinitySeeder SpiritSeeder; do
  docker compose exec -T app php artisan db:seed --class=$S
done
```

| Seeder | exit | Filas esperadas | Filas verificadas |
|---|---|---|---|
| `ElementSeeder` | 0 | 8 | 8 ✓ |
| `EnchantmentSeeder` | 0 | 73 | 73 ✓ |
| `SpiritAffinitySeeder` | 0 | 38 | 38 ✓ |
| `SpiritSeeder` | 0 | 1 | 1 ✓ |

### Fase 3 — Blueprints

```bash
docker compose exec -T app php artisan import:blueprints "/var/www/html/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx"
```

```text
Filas leídas (excluyendo Enchantment): 1549
Filas procesadas: 1549 | Insertados: 1549 | Actualizados: 0 | Omitidos: 0
Importación completada con éxito.  (exit=0)
```

### Fase 4 — Materiales de fabricación

```bash
docker compose exec -T app php artisan db:seed --class=CraftingMaterialSeeder   # exit=0, sin advertencias
```

Distribución `material_type`: `blueprint` 99 · `quest_component` 66 · `sigil` 6 · `enchantment` 15 → **186** ✓

### Fase 5 — Componentes de blueprints

```bash
docker compose exec -T app php artisan import:blueprint-components "/var/www/html/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx"
```

```text
Referencias de componentes encontradas: 2747
Blueprints procesados: 1549 | Componentes insertados: 2747
Filas omitidas: 0 | Referencias sin resolver: 0 | Errores: 0
Importación de componentes completada sin incidencias.  (exit=0)
```

## 8. Validaciones

### 8.1 Conteos (todas las referencias coinciden)

| Tabla | Esperado | Real | Resultado |
|---|---|---|---|
| `blueprints` | 1.549 | 1.549 | ✓ |
| `blueprint_components` | 2.747 | 2.747 | ✓ |
| `crafting_materials` | 186 | 186 | ✓ |
| `elements` | 8 | 8 | ✓ |
| `enchantments` | 73 | 73 | ✓ |
| `spirit_affinities` | 38 | 38 | ✓ |
| `spirits` | 1 | 1 | ✓ |
| `blueprint_elemental_affinities` | 911 | 911 | ✓ |
| `blueprint_workers` | 3.744 | 3.744 | ✓ |
| `blueprint_resources` | 4.135 | 4.135 | ✓ |
| `blueprint_crafting_upgrades` | 7.745 | 7.745 | ✓ |
| `blueprint_starforged_milestones` | 7.745 | 7.745 | ✓ |
| `blueprint_ascension_upgrades` | 4.647 | 4.647 | ✓ |
| `blueprint_transcendence_upgrades` | 4.647 | 4.647 | ✓ |
| `resources` | 12 | 12 | ✓ |
| `qualities` | 5 | 5 | ✓ |
| `item_categories` | 40 | 40 | ✓ |
| `workers` | 19 | 19 | ✓ |
| `worker_levels` | 50 | 50 | ✓ |
| `users` | 0 (no usar `db:seed` global) | 0 | ✓ |

### 8.2 Claves foráneas y huérfanos — todos 0

`orphan_components_material`, `orphan_components_quality`, `orphan_affinity_blueprint`, `orphan_affinity_element`, `orphan_blueprint_category`, `orphan_blueprint_spirit_affinity`, `orphan_blueprint_builtin_element`, `orphan_blueprint_builtin_spirit`, `orphan_blueprint_workers`, `orphan_blueprint_resources`, `orphan_material_blueprint` → **todos = 0** ✓

### 8.3 Duplicados — todos 0

`dup_blueprints_name`, `dup_materials_source_key`, `dup_affinity_pair`, `dup_affinity_position`, `dup_component_triple` → **todos = 0** ✓

### 8.4 Afinidades AZ y orden

| Comprobación | Esperado | Real | Resultado |
|---|---|---|---|
| `invalid_position` (`position NOT IN (1,2)`) | 0 | 0 | ✓ |
| Blueprints con AZ | 802 | 802 | ✓ |
| Blueprints con 2 elementos | 109 | 109 | ✓ |
| Blueprints con ≥3 elementos | 0 | 0 | ✓ |
| `position` 1 ≠ primer token de `raw_value` | 0 | 0 | ✓ |

Distribución por elemento (coincide con la referencia): Dark 177 · Earth 160 · Light 139 · Water 118 · Gold 103 · Air 101 · Fire 87 · All 26 (Σ = 911) ✓
El orden AZ se preserva: coexisten `Earth, Dark` (14) y `Dark, Earth` (3), entre otras 23 ordenaciones distintas.

### 8.5 Exclusiones y coherencia

| Comprobación | Esperado | Real | Resultado |
|---|---|---|---|
| Blueprints con categoría `Enchantment` | 0 | 0 | ✓ |
| `built_in_element_id` con tipo ≠ `Element` | 0 | 0 | ✓ |

### 8.6 Incidencias de importación

`import:blueprints`: 0 omitidos, 0 errores. `import:blueprint-components`: 0 filas omitidas, 0 referencias sin resolver, 0 errores. `CraftingMaterialSeeder`: sin advertencias.

## 9. Resultado final

**RECUPERACIÓN EXITOSA Y VALIDADA.** Las 5 fases se ejecutaron con `exit=0`; todos los conteos coinciden con las referencias históricas; no hay huérfanos, duplicados, posiciones inválidas ni pérdida de orden AZ; las exclusiones se respetan; `users` permanece en 0 (no se usó `db:seed` global).

## 10. Riesgos residuales

| # | Riesgo | Estado |
|---|---|---|
| RR-1 | Reejecución con Excel distinto al v26.9.1 → relaciones reconstruidas con datos distintos | Mitigado: solo se usó el Excel canónico de `storage/app` |
| RR-2 | Reasignación de IDs de relación por delete-then-create | Sin impacto: no hay dependencias externas a esos IDs; FKs validadas |
| RR-3 | Respaldo en texto plano en `backups/` (untracked) | Aceptable; no subir a Git. Conservar hasta cierre del incidente |
| RR-4 | `CraftingMaterialSeeder` sigue sin estar en `DatabaseSeeder` | Decisión de código pendiente (fuera del alcance de la recuperación) |

## 11. Pendientes

1. Conservar el respaldo `backups/shoptitans_pre_recuperacion_20261023_203355.dump` hasta el cierre del incidente.
2. Decidir si se añade `CraftingMaterialSeeder` a `DatabaseSeeder` (decisión de código separada).
3. Decidir si se elimina/archiva el `.gitignore`-untracked de `backups/` o se añade a `.gitignore`.

## 12. Preguntas para ChatGPT

1. ¿Se acepta la recuperación como completa y correcta con las validaciones de §8?
2. ¿Se autoriza añadir `CraftingMaterialSeeder` a `DatabaseSeeder` (con `WithoutModelEvents` y sin `User::factory`) para futuras recargas reproducibles?

## 13. Próximo paso recomendado

Cerrar el incidente de datos y retomar la **revisión independiente de la Etapa 2** de la API de Blueprints. Mantener el respaldo hasta confirmación.

---

### Anexo — Comandos ejecutados (resumen)

```bash
# Respaldo + verificación
docker compose exec -T postgres pg_dump -U shoptitans -d shoptitans -Fc > backups/shoptitans_pre_recuperacion_20261023_203355.dump
docker compose exec -T postgres pg_restore --list < backups/shoptitans_pre_recuperacion_20261023_203355.dump

# Conexión
docker compose exec -T postgres psql -U shoptitans -d shoptitans -At -c "SELECT current_database(), current_user, inet_server_addr();"

# Fases 1 y 2 (db:seed por clase)
docker compose exec -T app php artisan db:seed --class=<Seeder>

# Fases 3 y 5 (importadores)
docker compose exec -T app php artisan import:blueprints "/var/www/html/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx"
docker compose exec -T app php artisan import:blueprint-components "/var/www/html/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx"
```
