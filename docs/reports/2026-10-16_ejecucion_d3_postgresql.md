# Reporte Técnico — Ejecución D-3 en PostgreSQL (elementos y afinidades AZ–BC)

**Fecha:** 2026-10-16
**Autor:** Editor IA
**Estado:** EJECUTADA Y VALIDADA

## 1. Objetivo

Ejecutar de forma autorizada las cinco migraciones D-3, los seeders de catálogos y la importación de blueprints para poblar las afinidades AZ–BC, y validar los resultados por consultas de solo lectura en PostgreSQL.

## 2. Alcance

**Incluye:** `migrate`, seeders por clase (`ElementSeeder`, `EnchantmentSeeder`, `SpiritAffinitySeeder`, `SpiritSeeder`), `import:blueprints` y verificación posterior (estado de migraciones, conteos, distribuciones, integridad y orden de AZ).

**Excluye:** `db:seed` global (evitado por el efecto colateral del `User` de prueba) y cualquier reparación destructiva.

## 3. Estado inicial

Preflight previo: 5 migraciones D-3 en `Pending`; tablas nuevas inexistentes; 4 columnas de afinidad de `blueprints` en `NULL` (0/0/0/0); `blueprints`=1549, `elements`=0, `crafting_materials`=186, `blueprint_components`=2.747; Excel `v26.9.1` en `storage/app/` del contenedor.

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| — | Ninguno | La ejecución no modifica código; solo la base de datos y la documentación. |
| `docs/reports/2026-10-16_ejecucion_d3_postgresql.md` | Creado | Reporte de la ejecución. |
| `AI_WORK_LOG.md` | Modificado | Estado de la tarea. |

## 5. Cambios realizados

### 5.1 Migraciones (batch [4])
Se crearon las tablas `enchantments`, `spirit_affinities`, `spirits`, `blueprint_elemental_affinities`; se eliminó `blueprints.elemental_affinity_id`; se re-apuntaron `spirit_affinity_id` → `spirit_affinities`, `built_in_element_id` → `enchantments`, `built_in_spirit_id` → `spirits` (todas `ON DELETE SET NULL`).

### 5.2 Seeders
Catálogos poblados (idempotentes): `elements`=8, `enchantments`=73, `spirit_affinities`=38, `spirits`=1.

### 5.3 Importación
`import:blueprints` → 1.549 filas procesadas, **0 insertados**, **1.549 actualizados**, **0 omitidos**. IDs de `blueprints` preservados (sin inserciones).

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| Las 5 migraciones corren sin error | `migrate` → 5 `DONE`, EXIT 0 | Confirmada |
| Las columnas estaban vacías (guarda pasa) | 0/0/0/0 en preflight | Confirmada |
| Importación idempotente preserva IDs | 0 insertados / 1549 actualizados; 1549 IDs distintos, min 1, max 1549 | Confirmada |
| Conteos de origen AZ–BC reproducidos | ba=330, bb=85, bc=10, pares AZ=911; `---`=824 → 802 blueprints con AZ | Confirmada |
| BB resuelto a `enchantments` de tipo `Element` | 30 elementos, todos `type=Element`; 0 filas no-Element | Confirmada |
| BC = `Mundra's Spirit` | 10 blueprints | Confirmada |
| Orden de AZ preservado | `ordered_az` == `raw_value` en pares; existen `Dark, Earth` y `Earth, Dark` sin normalizar | Confirmada |
| No hay duplicados ni huérfanos | 0 en todas las comprobaciones | Confirmada |
| `blueprint_components`/`crafting_materials` intactos | 2.747 y 186 | Confirmada |

## 7. Discrepancias

- **Filas de lectura: 1.549 vs 1.626 del análisis.** El análisis D-3 contó 1.626 filas (incluye las de tipo `Enchantment`); el importador excluye 77 filas `Enchantment` por diseño. Los conteos de AZ–BC no cambian (las filas `Enchantment` tenían `---` en AZ–BC), por lo que **ba/bb/bc coinciden exactamente** con el análisis.
- **Distribución AZ por elemento:** el análisis listó frecuencias de valores simples (Dark126…All26); en BD la frecuencia por elemento incluye su aparición en compuestos (Dark177…All26). La suma en BD (911) equivale a 693 simples + 218 de compuestos (109 filas × 2).
- **`elements`=8** (no 32): los 32 valores distintos de AZ incluyen las 23 cadenas compuestas; los tokens base son 8, coherente con el modelo aprobado.
- Sin discrepancias bloqueantes.

## 8. Decisiones

### Aprobadas
- Ejecución de D-3 (autorización explícita del usuario).
- Sembrar por clase en lugar de `db:seed` global.
- Mantener `Gold` (103) y `All` (26) como elementos provisionales.

### Pendientes de decisión
- Semántica de `Gold`/`All`; posible mapeo BA ↔ `enchantments.Spirit`; eventual constraint de BD para `built_in_element_id`.

## 9. Validaciones

```bash
docker compose exec -T app php artisan migrate
docker compose exec -T app php artisan db:seed --class=ElementSeeder
docker compose exec -T app php artisan db:seed --class=EnchantmentSeeder
docker compose exec -T app php artisan db:seed --class=SpiritAffinitySeeder
docker compose exec -T app php artisan db:seed --class=SpiritSeeder
docker compose exec -T app php -d memory_limit=512M artisan import:blueprints "storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx"
docker compose exec -T app php artisan migrate:status
# Consultas psql de solo lectura (conteos, distribuciones, integridad, orden)
```

Resultado / exit code:
```text
migrate: 000001..000005 → 5 DONE → EXIT 0
db:seed --class=ElementSeeder/EnchantmentSeeder/SpiritAffinitySeeder/SpiritSeeder → EXIT 0 (4/4)
import:blueprints → "Importación completada con éxito" · procesadas 1549 · insertados 0 · actualizados 1549 · omitidos 0 → EXIT 0

migrate:status: las 5 migraciones D-3 → [4] Ran
Conteos:
  blueprints=1549 · elements=8 · enchantments=73 · spirit_affinities=38 · spirits=1
  blueprint_elemental_affinities=911 · blueprint_components=2747 · crafting_materials=186
No nulos en blueprints: ba=330 · bb=85 · bc=10
IDs de blueprints: total=1549 · distinct=1549 · min=1 · max=1549
AZ: blueprints con AZ=802 · position 1=802 · position 2=109 (109 filas con 2 elementos)
AZ por elemento: Dark177 · Earth160 · Light139 · Water118 · Gold103 · Air101 · Fire87 · All26 (Σ=911)
BB: 30 elementos distintos, 30/30 type='Element'
BC: Mundra's Spirit = 10
enchantments: Element=33 · Spirit=40
Integridad: az_orphans=0 · ba_orphans=0 · bb_orphans=0 · bc_orphans=0 · bb_not_element_type=0
            dup_pairs=0 · dup_positions=0 · blueprints con >2 elementos=0
Orden AZ (muestra): 'Bogbound Terror'=Dark, Earth y 'Blackthorn Scepter'=Earth, Dark (sin normalizar)
```

Todas las consultas exit code 0. Ninguna operación de escritura fuera de las autorizadas.

## 10. Resultado final

**EJECUTADA Y VALIDADA.** Migraciones (`Ran`), seeders (4/4) e importación concluyeron correctamente; la validación confirma integridad referencial (0 huérfanos), ausencia de duplicados, orden de AZ preservado y preservación de `blueprints` (1.549) y de sus dependencias (`blueprint_components`=2.747, `crafting_materials`=186). Los conteos de AZ–BC coinciden con los datos fuente documentados.

## 11. Riesgos

- **`Gold`/`All`** sin semántica confirmada (103 y 26 respectivamente).
- **Identidad por nombre** en los catálogos: riesgo de cambios entre versiones del Excel (mitigado por idempotencia).
- **Integridad de BB** garantizada a nivel de aplicación; sin constraint a nivel de BD.
- **Reimportaciones futuras:** `import:blueprints` reconstruye AZ (delete-then-insert); verificado idempotente, pero debe seguir ejecutándose sobre catálogos previamente sembrados.

## 12. Pendientes

- Investigar la semántica de `Gold`/`All`.
- Verificar (fuente externa) el posible mapeo BA ↔ `enchantments.Spirit`.
- Decidir si se expone la API de catálogos/afinidades.
- Depurar artefactos untracked en la raíz del repo.

## 13. Preguntas para ChatGPT

1. ¿Se acepta la integridad de BB solo a nivel de aplicación o se implementa el FK compuesto con columna generada?
2. ¿`Gold`/`All` como elementos de pleno derecho o marcadores especiales?

## 14. Próximo paso recomendado

Revisión humana de este reporte. Si procede, continuar con la exposición de los catálogos de afinidad vía API o iniciar la investigación semántica de `Gold`/`All`.
