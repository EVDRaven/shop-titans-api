# Reporte Técnico — Evaluación de rendimiento del filtro `filter[component]` (Blueprints MVP)

**Fecha:** 2026-10-26
**Autor:** Editor IA
**Estado:** EVALUACIÓN COMPLETADA (solo lectura; sin cambios de código, datos, índices ni configuración)
**Objeto:** `GET /api/v1/blueprints` → `filter[component]`

---

## 1. Objetivo

Medir el rendimiento real del filtro `filter[component]` de `GET /api/v1/blueprints` sobre PostgreSQL y determinar si existe evidencia suficiente para proponer una optimización mediante índices.

Esta fase es **de solo lectura**. La autorización cubre únicamente la evaluación.

## 2. Alcance y restricciones

**Incluye:** inspección de informes previos, controlador, contrato OpenAPI y migraciones/índices; verificación explícita del destino de conexión; obtención de la línea base (conteos, índices, FKs y estadísticas del planificador); `EXPLAIN (FORMAT JSON)` **sin** `ANALYZE` de las consultas equivalentes al filtro actual; análisis; informe y actualización de `AI_WORK_LOG.md`.

**Excluye (no ejecutado):** creación/modificación/eliminación de índices; cambios en tablas, datos, migraciones, modelos, controladores, consultas o configuración; migraciones, seeders, importadores y restauraciones; instalación de dependencias; cambios en Docker Compose; cualquier escritura en PostgreSQL; `ANALYZE`; `EXPLAIN ANALYZE`; commits y push; modificación o eliminación de respaldos.

## 3. Relación utilizada por el filtro (confirmada por código)

`blueprints.id` → `blueprint_components.blueprint_id` → `blueprint_components.component_id` → `crafting_materials.id`

- Relación padre: `Blueprint::blueprintComponentsAsParent()`.
- Relación anidada: `BlueprintComponent::component()` → `belongsTo(CraftingMaterial::class, 'component_id')`.
- Caso `component` en `BlueprintController::applyFilter()` ([BlueprintController.php#L146-L152](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php#L146-L152)):
  ```php
  $query->whereHas('blueprintComponentsAsParent.component', fn ($relation) => $relation->where('name', $value));
  ```
- El filtro compara por **igualdad exacta** el `name` del componente y **no** acota por `material_type` (DP-D): abarca todos los valores (`blueprint`, `quest_component`, `sigil`, `enchantment`).

## 4. Verificación de seguridad del destino (host y base)

No se infirió el destino por el nombre del contenedor ni por el archivo `.env`. Se verificó en dos planos:

**(a) Configuración efectiva resuelta en runtime por Laravel (contenedor `shoptitans-app`):**

```json
{"default":"pgsql","host":"postgres","port":"5432","database":"shoptitans","username":"shoptitans","active_conn":"shoptitans","driver":"pgsql"}
```

**(b) Identidad del servidor devuelta por la propia sesión SQL:**

```text
 current_database() = shoptitans
 current_user       = shoptitans
 version()          = PostgreSQL 17.10 (Debian 17.10-1.pgdg13+1) on x86_64-pc-linux-gnu
```

**(c) Contraste con la línea base del MVP:** los conteos obtenidos (`blueprints`=1549, `blueprint_components`=2747, `crafting_materials`=186) coinciden **exactamente** con la línea base registrada en `AI_WORK_LOG.md` para el dataset del MVP, lo que confirma que se trata de la **base de desarrollo prevista** y no de un entorno inesperado.

`docker compose ps`: `shoptitans-app` (Up, `9000/tcp` sin publicar) y `shoptitans-postgres` (Up, `0.0.0.0:5432->5432/tcp`). No se modificó el estado de los contenedores.

> Se declara el host (`postgres`, red interna de Compose), el puerto (5432), la base (`shoptitans`) y el usuario (`shoptitans`). **No** se incluyen contraseñas.

## 5. Consultas y comandos de lectura ejecutados

Todas las operaciones fueron `SELECT` de metadatos/lectura o `EXPLAIN` sin `ANALYZE` (que **no** ejecuta la consulta). No se ejecutó ningún `ANALYZE`, `INSERT`, `UPDATE`, `DELETE`, `CREATE`, `ALTER` ni `DROP`.

| # | Tipo | Descripción |
|---|---|---|
| 1 | Metadatos | Identidad de sesión (`current_database`, `current_user`, `version`) |
| 2 | Runtime | Config efectiva de Laravel (`config('database.*')`, `DB::connection()->getDatabaseName()`) |
| 3 | Lectura | Conteos de `blueprints`, `blueprint_components`, `crafting_materials` |
| 4 | Metadatos | Índices de las tres tablas (`pg_index` + `pg_get_indexdef`) |
| 5 | Metadatos | FKs relevantes (`pg_constraint`) |
| 6 | Metadatos | Tamaño/páginas/`reltuples` (`pg_class`); vacuums/análisis (`pg_stat_user_tables`) |
| 7 | Metadatos | Estadísticas del planificador (`pg_stats`) |
| 8 | Lectura | Cardinalidades: nombres distintos; blueprints/componentes distintos |
| 9 | Lectura | Materiales sin `blueprint_components` asociados (construcción del caso C) |
| 10 | Runtime | SQL generada por Laravel para el filtro (`toSql()` + bindings; **sin ejecutar**) |
| 11 | `EXPLAIN` | Plan del filtro con nombre existente (`Iron Pine Cone`), incluido `LIMIT 25` |
| 12 | `EXPLAIN` | Plan del `count(*)` de la paginación con el filtro |
| 13 | `EXPLAIN` | Plan del filtro con nombre inexistente |
| 14 | `EXPLAIN` | Plan de referencia sin filtro (`LIMIT 25`) |
| 15 | `EXPLAIN` | Plan del `count(*)` sin filtro |

## 6. Línea base (A)

### 6.1 Conteos

| Tabla | Filas |
|---|---|
| `blueprints` | 1549 |
| `blueprint_components` | 2747 |
| `crafting_materials` | 186 |

Detalle de cardinalidad: `crafting_materials` tiene **186 nombres distintos** (es decir, `name` es único *de facto* en los datos actuales, aunque sin restricción `UNIQUE`); `blueprint_components` cubre **1499** blueprints distintos y **186** `component_id` distintos (todos los materiales están referenciados al menos una vez).

> **Consecuencia para el caso «nombre que existe pero sin blueprints asociados»:** **no es construible con los datos actuales**, porque **todo** `crafting_materials` está referenciado por al menos una fila de `blueprint_components` (consulta #9 → 0 filas). Se sustituye por un nombre **inexistente** (caso C, §7.3) y se documenta la limitación.

### 6.2 Índices existentes

| Tabla | Índice | Definición |
|---|---|---|
| `blueprints` | `blueprints_pkey` | `btree (id)` UNIQUE |
| `blueprints` | `blueprints_name_unique` | `btree (name)` UNIQUE |
| `blueprint_components` | `blueprint_components_pkey` | `btree (id)` UNIQUE |
| `blueprint_components` | `blueprint_components_blueprint_id_component_id_quality_id_uniqu` | `btree (blueprint_id, component_id, quality_id)` UNIQUE |
| `crafting_materials` | `crafting_materials_pkey` | `btree (id)` UNIQUE |
| `crafting_materials` | `crafting_materials_source_key_unique` | `btree (source_key)` UNIQUE |
| `crafting_materials` | `idx_crafting_materials_blueprint_id_unique` | `btree (blueprint_id) WHERE blueprint_id IS NOT NULL` UNIQUE (parcial) |

**Hallazgo estructural relevante:**
- **No existe** índice cuya **columna líder** sea `blueprint_components.component_id`. El único índice compuesto de esa tabla es `(blueprint_id, component_id, quality_id)`, cuyo prefijo útil es `blueprint_id`; **no** puede servir eficientemente una búsqueda por `component_id`.
- **No existe** índice sobre `crafting_materials.name`.

### 6.3 Claves foráneas relevantes

| Tabla | Constraint | Definición |
|---|---|---|
| `blueprint_components` | `..._blueprint_id_foreign` | `FOREIGN KEY (blueprint_id) REFERENCES blueprints(id) ON DELETE CASCADE` |
| `blueprint_components` | `..._component_id_foreign` | `FOREIGN KEY (component_id) REFERENCES crafting_materials(id) ON DELETE RESTRICT` |
| `blueprint_components` | `..._quality_id_foreign` | `FOREIGN KEY (quality_id) REFERENCES qualities(id) ON DELETE SET NULL` |
| `crafting_materials` | `..._blueprint_id_foreign` | `FOREIGN KEY (blueprint_id) REFERENCES blueprints(id) ON DELETE RESTRICT` |

### 6.4 Tamaño y estadísticas del planificador

| Tabla | Tamaño | Páginas | `reltuples` | `n_live_tup` | `n_dead_tup` | Último `autoanalyze` |
|---|---|---|---|---|---|---|
| `blueprints` | 616 kB | 77 | 1549 | 1549 | 0 | 2026-10-24 02:38:55 |
| `blueprint_components` | 416 kB | 52 | 2747 | 2747 | 0 | 2026-10-26 16:27:32 |
| `crafting_materials` | 32 kB | 4 | 186 | 186 | 0 | 2026-10-24 02:38:55 |

`reltuples` coincide con las filas reales y `n_dead_tup` = 0 → **las estadísticas están presentes y vigentes** (el planificador dispone de información suficiente). **No se ejecutó `ANALYZE`** (queda fuera de autorización; no fue necesario).

Estadísticas de columnas relevantes (`pg_stats`):

| Columna | `n_distinct` | Notas |
|---|---|---|
| `crafting_materials.name` | `-1` (todas distintas) | Sin MCV (no hay valores repetidos) |
| `blueprint_components.component_id` | `186` | Distribución desigual: frecuencias MCV de ~2.6 % a ~0.07 % |
| `blueprint_components.blueprint_id` | `-0.5457` | Correlación física = 1.0 (filas agrupadas por blueprint) |
| `blueprint_components.component_id` | — | Correlación física = 0.0956 (baja) |
| `blueprints.id` | `-1` | Correlación física = 0.9999 (alta) |

## 7. Planes de ejecución (B) — `EXPLAIN (FORMAT JSON)` sin `ANALYZE`

**Aclaración importante:** los costes que se citan son **estimaciones del planificador** (`Startup Cost`/`Total Cost` en unidades arbitrarias), **no** tiempos reales de ejecución. No se ejecutó la consulta (`ANALYZE`), por lo que no hay medida de tiempo.

### 7.1 SQL generada por Laravel para el filtro

```sql
select * from "blueprints"
where exists (
  select * from "blueprint_components"
  where "blueprints"."id" = "blueprint_components"."blueprint_id"
  and exists (
    select * from "crafting_materials"
    where "blueprint_components"."component_id" = "crafting_materials"."id"
    and "name" = ?
  )
)
order by "id" asc
```

`bindings` = `["Iron Pine Cone"]`. La paginación añade `limit 25 offset 0` a esta consulta y ejecuta además un `count(*)` con el mismo `WHERE`.

### 7.2 Caso B1 — nombre existente con resultados (`Iron Pine Cone`; 30 blueprints reales)

```text
Limit (Plan Rows 15; cost 101.46..101.50)
└─ Sort (Sort Key: blueprints.id)
   └─ Nested Loop (Inner Unique: true; cost 93.50..101.16)
      ├─ Aggregate (Hashed)  Group Key: blueprint_components.blueprint_id  (rows 15; cost 93.22..93.37)
      │  └─ Hash Join (Hash Cond: component_id = crafting_materials.id; cost 6.34..93.19)
      │     ├─ Seq Scan blueprint_components (rows 2747; cost 0.00..79.47)   ← sin Filtro: recorre la tabla completa
      │     └─ Hash → Seq Scan crafting_materials (Filter: name = 'Iron Pine Cone'; rows 1; cost 0.00..6.33)
      └─ Index Scan blueprints_pkey (Index Cond: id = blueprint_components.blueprint_id; rows 1)
```

Observaciones:
- El optimizador **no** implementa las subconsultas `EXISTS` anidadas como bucles anidados por fila de blueprint; las **transforma en un semi-join** con agregación por `blueprint_components.blueprint_id`, y luego resuelve el `join` exterior contra `blueprints` por la **clave primaria** (`Index Scan`).
- El acceso a `blueprint_components` es un **`Seq Scan` de 2747 filas / 52 páginas** y el de `crafting_materials` un **`Seq Scan` de 186 filas / 4 páginas** con `Filter` por nombre. Ninguno usa un índice para localizar por `component_id` ni por `name` (no existen, §6.2).
- El nodo final `Sort` ordena el resultado por `blueprints.id` porque el plan exterior ya no entrega las filas ordenadas (el filtro rompe el orden natural de la `pkey`).
- **Estimación frente a realidad:** el plan estima **15** filas; el dato real verificado es **30** blueprints. La subestimación (×2) se explica por la ausencia de MCV en `crafting_materials.name` (186 nombres todos distintos → selectividad uniforme ≈ 1/186 ≈ 1 fila por composición) y por la correlación baja de `component_id`. No afecta a la elección de plan a esta escala.

### 7.3 Caso B2 — nombre inexistente (`No Existe XYZ`)

Plan **idéntico en forma** al caso B1 (misma cadena `Limit → Sort → Nested Loop → Aggregate(Hashed) → Hash Join → Seq Scan ×2`), con la misma estimación de 15 filas. El planificador no distingue el valor porque **no hay estadística MCV ni índice** que permita estimar la selectividad de un nombre concreto de forma precisa. *(El caso solicitado «nombre existente pero sin blueprints» no es construible con los datos actuales — ver §6.1.)*

### 7.4 Caso B3 — sin filtro (referencia), con `LIMIT 25`

```text
Limit (rows 25; cost 0.28..2.40)
└─ Index Scan blueprints_pkey (Forward; rows 1549; cost 0.28..131.55)
```

Sin filtro, el `LIMIT 25` se satisface directamente con la `pkey` (`Index Scan` en orden), sin `Sort` ni joins. Es el caso más barato.

### 7.5 Plan del `count(*)` de la paginación

- **Con filtro:** `Aggregate (Plain) → Nested Loop → Aggregate (Hashed) → Hash Join → Seq Scan ×2`, con `Index Only Scan blueprints_pkey` en el interior. Misma forma que B1.
- **Sin filtro:** `Aggregate (Plain) → Index Only Scan blueprints_pkey` (rows 1549; cost 0.28..51.51).

## 8. Análisis (C)

1. **¿Los índices actuales permiten localizar eficientemente los componentes y sus asociaciones?**
   No existe un índice con `component_id` como columna líder ni un índice sobre `crafting_materials.name`. El planificador compensa esta ausencia resolviendo el `EXISTS` como **semi-join** con `Seq Scan` + `Hash Join` sobre las dos tablas implicadas. A la escala actual esto es **eficiente en términos absolutos** (coste total estimado ≈ 101 en el caso filtrado y ≈ 2.4 en el caso sin filtro), porque ambas tablas son **diminutas** (52 y 4 páginas).

2. **¿El plan estima escanear muchas filas o repetir búsquedas costosas?**
   - Escanea la tabla `blueprint_components` completa (**2747 filas**) una sola vez, y `crafting_materials` completa (**186 filas**) una sola vez. **No** hay N+1: la consulta es un único `SELECT` con el `EXISTS` convertido en join.
   - No hay repetición por fila de blueprint (el `Hash Join` + `Aggregate` evita re-evaluar la subconsulta por cada blueprint).
   - El `Sort` final opera sobre un conjunto pequeño (estimado 15 filas).

3. **¿Los datos y estadísticas permiten una conclusión razonable?**
   Sí. Las estadísticas están **presentes y vigentes** (§6.4) y las cardinalidades son pequeñas y bien conocidas; el plan es **estable** entre distintos valores de `name` (B1 vs B2 idénticos). La estimación de filas subestima ×2, pero es irrelevante al tamaño actual.

4. **¿Qué índice, si alguno, podría evaluarse en una fase posterior?**
   De existir un crecimiento significativo de datos, el candidato con mayor justificación sería:
   - **`blueprint_components (component_id, blueprint_id)`** — permitiría localizar directamente las filas por `component_id` y obtener `blueprint_id` desde el propio índice (evitando el `Seq Scan` de `blueprint_components`, que es el coste dominante del plan, ≈79 de ≈101), sin necesidad de leer el heap para el semi-join.
   - *Justificación basada en el plan:* el nodo más costoso es el `Seq Scan` sobre `blueprint_components`; el índice compuesto existente `(blueprint_id, component_id, quality_id)` **no** puede usarse porque su prefijo es `blueprint_id`.
   - Un índice sobre `crafting_materials (name)` tendría menor justificación: la tabla tiene solo 4 páginas y el `Seq Scan` cuesta ≈6.3.
   - **No obstante**, la tabla `blueprint_components` tiene solo 52 páginas y `component_id` presenta correlación física baja (0.096), por lo que un índice añadiría lecturas aleatorias del heap; **no puede afirmarse que mejore el rendimiento sin medirlo**.
   - El `Seq Scan` **no** se interpreta como prueba de que falte un índice: se explica por el tamaño mínimo de las tablas.

5. **Conclusión del análisis:** a la escala actual del MVP no hay evidencia de un problema de rendimiento del filtro `filter[component]`. El plan es razonable y barato; la ausencia de índice dedicado no se traduce en un coste material con 1549/2747/186 filas.

## 9. Tiempos reales (C — condicional) — NO medidos

**No se midieron tiempos reales.** Para obtenerlos sería necesario ejecutar la consulta (`EXPLAIN ANALYZE`), lo que la autorización vigente excluye expresamente hasta disponer de aprobación específica. Por tanto:

- La evaluación se sustenta en **planes estimados** (`EXPLAIN`, sin ejecución).
- **Limitación declarada:** no hay medida de tiempo de reloj; los `Cost` no son tiempos.
- **Si se desea medir tiempos reales**, se requiere una **autorización específica** para ejecutar `EXPLAIN (ANALYZE, BUFFERS)` sobre los casos B1/B2/B3. Dado el tamaño de las tablas, el impacto sería mínimo y de solo lectura, pero **no se ejecuta sin aprobación**.

## 10. Recomendación final

**No cambiar nada en esta fase.**

- No se crea ningún índice, ni se propone como necesario.
- El filtro `filter[component]` opera con un plan barato y estable sobre el dataset del MVP.
- Se mantiene vigente la decisión **DP-E** (no crear índices sin evidencia de rendimiento): la evaluación **no** aporta evidencia de degradación.

**Recomendación para una fase posterior (si el dataset crece):** solicitar una **medición adicional** autorizada con `EXPLAIN (ANALYZE, BUFFERS)` en el mismo entorno, y —solo si esa medición lo justifica— evaluar específicamente el índice `blueprint_components (component_id, blueprint_id)`. No debe crearse ni afirmarse su mejora sin medir.

## 11. Limitaciones y riesgos

- **L1 — Sin tiempos reales:** la conclusión se basa en costes estimados; el planificador podría elegir otro plan con valores o volúmenes distintos. Mitigación: medición posterior autorizada.
- **L2 — Caso «nombre existente sin blueprints» no construible:** todos los `crafting_materials` están referenciados; se documenta y se sustituye por un nombre inexistente (§6.1, §7.3).
- **L3 — Estimación de filas inexacta:** el plan estima 15 filas frente a 30 reales (×2). Sin impacto a esta escala.
- **L4 — Escala mínima:** las tablas tienen 52 y 4 páginas; las conclusiones **no** son extrapolables a volúmenes mayores.
- **L5 — Correlación baja de `component_id` (0.096):** un futuro índice sobre esa columna implicaría lecturas aleatorias; su beneficio no está demostrado.

## 12. Confirmaciones

- **Sin cambios de índices, tablas, datos, código, config, respaldos ni dependencias.**
- No se ejecutaron migraciones, seeders, importadores ni restauraciones; **ninguna escritura** en PostgreSQL.
- No se ejecutó `ANALYZE` ni `EXPLAIN ANALYZE`.
- PostgreSQL no fue alterado: la línea base permanece idéntica (1549/2747/186).
- **Sin commits ni push.**
- Los artefactos escritos por esta tarea son este informe y el hito correspondiente en `AI_WORK_LOG.md`.

## 13. Bloqueos o desviaciones

- **Ninguno** que requiera ampliar el alcance. La única necesidad identificada (medición de tiempos reales) se documenta como **pendiente de autorización específica** (§9), sin ejecutarla.
