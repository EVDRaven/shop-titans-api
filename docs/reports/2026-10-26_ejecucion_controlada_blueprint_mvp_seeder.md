# Reporte Técnico — Ejecución controlada de `BlueprintMvpSeeder` (ejecutada)

**Fecha:** 2026-10-26 (ventana ~16:26–16:27 UTC)
**Autor:** Editor IA
**Estado:** EJECUTADA — VALIDADA

## 1. Objetivo

Ejecutar de forma controlada `Database\Seeders\BlueprintMvpSeeder` sobre la base PostgreSQL existente `shoptitans`, siguiendo el protocolo del proyecto, y verificar el resultado (conteos, integridad referencial y ausencia de huérfanos/duplicados).

## 2. Alcance

**Incluye:**
- Verificaciones previas (servicios, conexión, conteos base, respaldo legible).
- Una única ejecución de `db:seed --class=BlueprintMvpSeeder`.
- Verificación posterior (conteos, integridad referencial, huérfanos/duplicados).
- Documentación del resultado.

**Excluye:**
- Migraciones, `db:seed` global, restauraciones u otros comandos de escritura.
- Prueba real de restauración del respaldo.
- Commits o push.

## 3. Estado inicial

- Servicios `app` y `postgres` operativos (`Up 3 days`); PostgreSQL 17.10 en `shoptitans-postgres`, `5432` publicado.
- Base `shoptitans` poblada con el dataset del MVP; respaldo fresco del 2026-10-24 disponible y verificado.
- Verificación previa del 2026-10-24 y comprobación de vigencia del respaldo del 2026-10-26: **sin bloqueos**.

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| `docs/reports/2026-10-26_ejecucion_controlada_blueprint_mvp_seeder.md` | Creado | Informe de la ejecución. |
| `docs/reports/2026-10-24_verificacion_previa_ejecucion_blueprint_mvp_seeder.md` | Modificado | Addendum 3 (cierre del ciclo de verificación). |
| `AI_WORK_LOG.md` | Modificado | Registrar el hito de ejecución. |

No se modificaron código, configuración, migraciones ni los respaldos.

## 5. Cambios realizados

Cambios en la base de datos `shoptitans` producidos por el propio seeder (re-aplicación idempotente). No hubo cambios de esquema ni de código. Conteos totales sin variación (ver §9).

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| Los servicios y la conexión corresponden a `shoptitans` | `current_database()=shoptitans`, `current_user=shoptitans`, PG 17.10 | Confirmada |
| Los conteos iniciales coinciden con la línea base | 1549/2747/186/911/8/73/38/1/12/5/40/19/50/0 | Confirmada |
| El respaldo sigue disponible y legible | 432.554 B, md5 `53170e…d5e`, `pg_restore --list` exit 0 | Confirmada |
| El seeder ejecuta las 4 etapas en orden | Salida con los 9 catálogos `DONE`; `blueprint_components` recreados el 2026-10-26 16:26:45 UTC | Confirmada |
| La re-aplicación es idempotente a nivel de conteos | Conteos antes = después | Confirmada |
| No hay huérfanos ni duplicados | 10 comprobaciones = 0 | Confirmada |
| La restauración del respaldo funciona | No comprobado (solo `pg_restore --list`) | **No verificada** |

## 7. Discrepancias

Ninguna frente a la línea base. Limitación de observabilidad:

- La salida de los comandos de importación (`import:blueprints`, `import:blueprint-components`) **no aparece en consola** porque `Artisan::call()` captura su salida y no la reimprime. Por ello la evidencia de ejecución se apoya en los conteos, la ausencia de huérfanos/duplicados y las marcas de tiempo (`blueprint_components.max(created_at)=2026-10-26 16:26:45 UTC`).

## 8. Decisiones

### Propuestas
- Ninguna.

### Aprobadas
- Ejecución controlada de `BlueprintMvpSeeder` (autorizada por el usuario).

### Reemplazadas/Rechazadas
- Ninguna.

## 9. Validaciones

> **Base de datos verificada:** `shoptitans` (usuario `shoptitans`) en `shoptitans-postgres`, PostgreSQL `17.10 (Debian 17.10-1.pgdg13+1)`.

### 9.1 Verificaciones previas

```bash
docker compose ps
# shoptitans-app        Up 3 days        9000/tcp
# shoptitans-postgres   Up 3 days        0.0.0.0:5432->5432/tcp

# conexión + conteos iniciales
shoptitans|shoptitans||PostgreSQL 17.10 (Debian 17.10-1.pgdg13+1)
1549|2747|186|911|8|73|38|1|12|5|40|19|50|0

# respaldo
-rw-rw-r-- 432554 bytes  backups/shoptitans_pre_blueprintmvpseeder_20261024_153512.dump
53170eada1bce8232cbdbddf51192d5e
pg_restore --list → exit 0 ; dbname shoptitans ; TOC Entries 229 ; CUSTOM ; gzip
```

### 9.2 Comando de ejecución

```bash
docker compose exec -T app php artisan db:seed --class="Database\\Seeders\\BlueprintMvpSeeder"
```

> Se añadió el flag `-T` a `docker compose exec` porque la terminal no es interactiva (equivalente funcional al comando autorizado).

### 9.3 Salida y código de finalización

```text
   INFO  Seeding database.

  Database\Seeders\ResourceSeeder .................................... RUNNING
  Database\Seeders\ResourceSeeder ................................ 178 ms DONE
  Database\Seeders\QualitySeeder ..................................... RUNNING
  Database\Seeders\QualitySeeder ................................... 4 ms DONE
  Database\Seeders\ItemCategorySeeder ................................ RUNNING
  Database\Seeders\ItemCategorySeeder ............................. 16 ms DONE
  Database\Seeders\WorkerSeeder ...................................... RUNNING
  Database\Seeders\WorkerSeeder ................................... 10 ms DONE
  Database\Seeders\WorkerLevelSeeder ................................. RUNNING
  Database\Seeders\WorkerLevelSeeder ............................. 133 ms DONE
  Database\Seeders\ElementSeeder ..................................... RUNNING
  Database\Seeders\ElementSeeder ............................... 8,893 ms DONE
  Database\Seeders\EnchantmentSeeder ................................. RUNNING
  Database\Seeders\EnchantmentSeeder ........................... 3,194 ms DONE
  Database\Seeders\SpiritAffinitySeeder .............................. RUNNING
  Database\Seeders\SpiritAffinitySeeder ........................ 3,129 ms DONE
  Database\Seeders\SpiritSeeder ...................................... RUNNING
  Database\Seeders\SpiritSeeder ................................ 3,182 ms DONE

EXIT=0
```

**Código de finalización: `0` (éxito).** La salida de las etapas de importación no se imprime (capturada por `Artisan::call()`).

### 9.4 Conteos antes / después

| Tabla | Antes | Después | Δ |
|---|---:|---:|---:|
| blueprints | 1.549 | 1.549 | 0 |
| blueprint_components | 2.747 | 2.747 | 0 |
| crafting_materials | 186 | 186 | 0 |
| blueprint_elemental_affinities | 911 | 911 | 0 |
| elements | 8 | 8 | 0 |
| enchantments | 73 | 73 | 0 |
| spirit_affinities | 38 | 38 | 0 |
| spirits | 1 | 1 | 0 |
| resources | 12 | 12 | 0 |
| qualities | 5 | 5 | 0 |
| item_categories | 40 | 40 | 0 |
| workers | 19 | 19 | 0 |
| worker_levels | 50 | 50 | 0 |
| users | 0 | 0 | 0 |

### 9.5 Integridad referencial / huérfanos / duplicados (todos en 0)

```text
comp_orphan_blueprint|comp_orphan_component|comp_orphan_quality|comp_dups|cm_dup_source_key|cm_orphan_blueprint|cm_dup_blueprint_id|aff_orphan_blueprint|aff_orphan_element|bp_dup_name
0|0|0|0|0|0|0|0|0|0
```

Distribución de `crafting_materials`: `blueprint`=99, `quest_component`=66, `sigil`=6, `enchantment`=15 (total 186).

Restricciones presentes (verificadas en `pg_constraint`): FKs de `blueprint_components` (`blueprint_id`, `component_id`→`crafting_materials`, `quality_id`), UNIQUE `(blueprint_id, component_id, quality_id)`; CHECKs de `crafting_materials` (`chk_material_type_blueprint_id`, `chk_source_key_format`, `material_type`); UNIQUE/FK/CHECK de `blueprint_elemental_affinities`.

### 9.6 Evidencia de ejecución de las etapas (marcas de tiempo)

```text
now()                                  = 2026-10-26 16:27:25 UTC
blueprint_components.max(updated_at)   = 2026-10-26 16:26:45 UTC   ← recreados en esta ejecución
blueprints.max(updated_at)             = 2026-10-24 02:38:27 UTC   ← sin cambios (updateOrCreate no-op)
crafting_materials.max(updated_at)     = 2026-10-24 02:38:34 UTC   ← sin cambios (no-op)
elements.max(updated_at)               = 2026-10-24 02:34:20 UTC   ← catálogos sin cambios (no-op)
```

Interpretación: los catálogos y la importación de materiales corrieron sin modificar filas (idempotencia); `blueprint_components` fue recreado hoy, confirmando la ejecución de `import:blueprint-components`.

## 10. Resultado final

- **Comando finalizado correctamente:** `db:seed --class=BlueprintMvpSeeder` → **exit 0**; las 4 etapas (catálogos → `import:blueprints` → `CraftingMaterialSeeder` → `import:blueprint-components`) se ejecutaron.
- **Integridad funcional confirmada:** conteos idénticos a la línea base, 0 huérfanos, 0 duplicados, restricciones (FK/UNIQUE/CHECK) presentes, componentes recreados de forma consistente.
- **Idempotencia confirmada** a nivel de conteos en una base ya poblada.

## 11. Riesgos

- **Observabilidad limitada:** la salida de los comandos de importación no se imprime; la confirmación depende de conteos, integridad y marcas de tiempo.
- **Restauración no probada:** el respaldo solo se verificó como legible (`pg_restore --list`); no se probó un restore real.
- **`users`=0:** sin cambios; el tratamiento de `User::factory()` (EP-3) sigue fuera de alcance.

## 12. Pendientes

- EP-3 (tratamiento de `User::factory()` en `DatabaseSeeder`).
- Discrepancia documental EC-1 (`PROJECT_CONTEXT.md:31`).
- Puntos de aprobación humana de H-1..H-4 (§17.5 del informe de revisión de la API).

## 13. Preguntas para ChatGPT

- ¿Conviene exponer en consola la salida de los comandos de importación invocados con `Artisan::call()` (p. ej. `Artisan::call(..., Artisan::output())`) para mejorar la observabilidad del seeder?

## 14. Próximo paso recomendado

Mantener el respaldo del 2026-10-24 hasta cerrar el incidente y abordar los pendientes por separado (EP-3, EC-1, H-1..H-4). No se requieren acciones correctivas derivadas de esta ejecución.
