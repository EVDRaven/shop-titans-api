# Reporte Técnico — Verificación previa a la primera ejecución de `BlueprintMvpSeeder`

**Fecha:** 2026-10-24
**Autor:** Editor IA
**Estado:** VERIFICACIÓN COMPLETADA — SIN BLOQUEOS (primera ejecución no realizada)

## 1. Objetivo

Verificar todas las condiciones necesarias para preparar la primera ejecución controlada de `Database\Seeders\BlueprintMvpSeeder` contra PostgreSQL: Excel de entrada, respaldo, Docker/configuración y conexión/estado real de la base de datos.

## 2. Alcance

**Incluye (solo lectura/inspección):**
- Inspección del trait de resolución del Excel y del archivo realmente seleccionado.
- Comprobación de existencia, tamaño e integridad estructural del respaldo (sin restaurar).
- Estado de los contenedores y configuración efectiva de Laravel (sin credenciales).
- Consultas de solo lectura a PostgreSQL y comparación con la conexión configurada.

**Excluye:**
- Ejecutar el seeder, seeders individuales o comandos de importación.
- Migraciones, restauraciones o cualquier escritura en la base de datos.
- Crear/sobrescribir/eliminar respaldos.
- Commits o push.

## 3. Estado inicial

- `BlueprintMvpSeeder` implementado, probado en SQLite aislado y formalizado como punto de entrada oficial (documentado en `README.md`).
- PostgreSQL de desarrollo recuperado y validado el 2026-10-23 (dataset completo del MVP presente).
- Respaldo previo disponible en `backups/`.
- No se había realizado ninguna verificación previa a una ejecución real.

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| `docs/reports/2026-10-24_verificacion_previa_ejecucion_blueprint_mvp_seeder.md` | Creado | Constancia de la verificación. |
| `AI_WORK_LOG.md` | Modificado | Registrar el hito de verificación. |
| `backups/shoptitans_pre_blueprintmvpseeder_20261024_153512.dump` | Creado (2026-10-24) | Respaldo fresco previo a la ejecución del seeder (autorizado). |

No se modificaron código, configuración ni estructura/datos de PostgreSQL. El respaldo previo (`shoptitans_pre_recuperacion_20261023_203355.dump`) no se sobrescribió ni eliminó.

## 5. Cambios realizados

Documentales (este informe y el registro en `AI_WORK_LOG.md`) y la creación de un **respaldo fresco** de PostgreSQL (formato CUSTOM, compatible con `pg_restore`) sin sobrescribir ni eliminar el respaldo existente. Ninguna modificación funcional de código ni de la base de datos.

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| El seeder selecciona un Excel existente y no vacío | Archivo por defecto presente, `Microsoft Excel 2007+`, 1.367.141 bytes | Confirmada |
| No hay selección ambigua por múltiples `.xlsx` | `find -iname "*.xlsx"` en `storage/app` → 1 único archivo | Confirmada |
| El respaldo es estructuralmente legible | `pg_restore --list` (stdin) → exit 0, CUSTOM, 229 TOC entries | Confirmada |
| Los servicios `app` y `postgres` están operativos | `docker compose ps` → ambos `Up 36 hours` | Confirmada |
| La conexión configurada coincide con la real | Laravel `pgsql`/`postgres`:5432/`shoptitans` vs `current_database()/current_user = shoptitans` | Confirmada |
| La clase del seeder es cargable | `class_exists("Database\Seeders\BlueprintMvpSeeder")` → true | Confirmada |
| La restauración real del respaldo funciona | No comprobado (requiere restore, no permitido) | **No verificada** |

## 7. Discrepancias

Ninguna bloqueante. Puntos de atención:

- **Base ya poblada:** PostgreSQL contiene el dataset completo del MVP (`blueprints`=1.549, `blueprint_components`=2.747, `crafting_materials`=186). La "primera ejecución" sería una **re-aplicación** sobre base poblada, no una instalación desde cero. La idempotencia del orquestador está validada en SQLite aislado, **no** contra PostgreSQL real.
- **`backups/` solo en el host:** no está montado en los contenedores; se inspeccionó por *stdin*. Para un eventual restore habría que hacerlo accesible al contenedor `postgres`.
- **`inet_server_port()` vacío:** el servidor escucha por socket dentro del contenedor; el puerto efectivo es 5432 (config + publicación Compose).

## 8. Decisiones

### Propuestas
- Ninguna.

### Aprobadas
- Ninguna nueva (verificación, no cambio de alcance).

### Reemplazadas/Rechazadas
- Ninguna.

## 9. Validaciones

```bash
# A. Excel (contenedor app)
docker exec shoptitans-app sh -lc 'cd /var/www/html/storage/app && ls -la && find . -type f -iname "*.xlsx" -print && stat -c "%n | %s bytes | %y" "Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx" && file "Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx"'

# B. Respaldo
ls -la backups/
file backups/shoptitans_pre_recuperacion_20261023_203355.dump
docker exec -i shoptitans-postgres pg_restore --list < backups/shoptitans_pre_recuperacion_20261023_203355.dump

# C. Docker y configuración efectiva (sin credenciales)
docker compose ps
docker exec -w /var/www/html shoptitans-app php -r '...config("database.default")...class_exists("Database\\Seeders\\BlueprintMvpSeeder")...'

# D. PostgreSQL (solo lectura)
docker exec shoptitans-postgres sh -lc 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -tA -c "SELECT current_database(), current_user, inet_server_port(), version();"'
docker exec shoptitans-postgres sh -lc 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -tA -c "SELECT (SELECT count(*) FROM blueprints), ... ;"'
```

Resultado / exit code:

```text
A) storage/app: 1 único .xlsx
   Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx | 1367141 bytes | 2026-10-14 00:48:40 UTC
   Microsoft Excel 2007+                                          → exit 0

B) -rw-rw-r-- 83238 bytes  shoptitans_pre_recuperacion_20261023_203355.dump
   PostgreSQL custom database dump - v1.16-0                     → exit 0
   pg_restore --list: CUSTOM · gzip · TOC 229 · dbname shoptitans · from 17.10  → exit 0

C) shoptitans-app        Up 36 hours
   shoptitans-postgres   Up 36 hours   0.0.0.0:5432->5432/tcp
   app_env=local default=pgsql driver=pgsql host=postgres port=5432
   database=shoptitans username=shoptitans  (password omitida)
   class_exists=true                                              → exit 0

D) shoptitans|shoptitans||PostgreSQL 17.10 (Debian 17.10-1.pgdg13+1)
   1549|2747|186|911|8|73|38|1|12|5|40|19|50|0                  → exit 0
```

Interpretación de los conteos (D): `blueprints`=1549 · `blueprint_components`=2747 · `crafting_materials`=186 · `blueprint_elemental_affinities`=911 · `elements`=8 · `enchantments`=73 · `spirit_affinities`=38 · `spirits`=1 · `resources`=12 · `qualities`=5 · `item_categories`=40 · `workers`=19 · `worker_levels`=50 · `users`=0.

## 10. Resultado final

**Sin bloqueos** para proceder a una primera ejecución controlada:
- Excel presente, no vacío, `.xlsx`, unívoco (sin ambigüedad de selección).
- Respaldo presente, estructuralmente reconocible y legible por `pg_restore` (integridad de restauración **no** verificada).
- Servicios `app` y `postgres` operativos; conexión configurada coherente con la real (`shoptitans` @ `postgres`:5432).
- Clase `Database\Seeders\BlueprintMvpSeeder` cargable.

No se ejecutó el seeder ni se realizaron escrituras; no se modificaron archivos salvo la documentación de esta constancia.

## 11. Riesgos

- **Re-aplicación sobre base poblada:** al ya existir el dataset completo, la ejecución real actúa como re-aplicación; la idempotencia no está verificada contra PostgreSQL real (solo en SQLite aislado). Se recomienda respaldo fresco **inmediatamente antes** de la ejecución.
- **Integridad de restauración no comprobada:** el respaldo actual es legible estructuralmente, pero no se ha probado un restore real.
- **Ruta del Excel:** si el archivo desaparece de `storage/app/`, el seeder se detiene con `RuntimeException` antes de importar.
- **Memoria PHP:** las etapas de importación requieren `memory_limit=512M` (definido en la imagen).

## 12. Pendientes

- Ejecución real controlada de `BlueprintMvpSeeder` **con autorización explícita** y respaldo fresco previo.
- EP-3 (tratamiento de `User::factory()` en `DatabaseSeeder`).
- Discrepancia documental EC-1 (`PROJECT_CONTEXT.md:31` y sección «Estado» de `README.md`).
- Puntos de aprobación humana de H-1..H-4 (§17.5 del informe de revisión de la API).

## 13. Preguntas para ChatGPT

- Dado que la base ya está poblada, ¿se desea ejecutar el orquestador como re-aplicación idempotente o sobre una base vacía tras respaldo?
- ¿Se requiere una prueba de restore del dump en un entorno aparte antes de la ejecución?

## 14. Próximo paso recomendado

Programar la ejecución controlada de `BlueprintMvpSeeder` con: (1) autorización explícita, (2) respaldo fresco previo, (3) captura de conteos antes/después para confirmar idempotencia. No modificar `DatabaseSeeder.php` (EP-3 fuera de alcance).

---

## Addendum — 2026-10-24: respaldo fresco previo a la ejecución (autorizado)

**Acción autorizada y ejecutada:** creación de un respaldo nuevo de la base `shoptitans` en formato compatible con `pg_restore` (CUSTOM), sin sobrescribir ni eliminar el respaldo existente.

**Respaldo nuevo:**
- Ruta absoluta: `/home/draven/Documentos/shop-titans-api/backups/shoptitans_pre_blueprintmvpseeder_20261024_153512.dump`
- Tamaño: **432.554 bytes** · mtime `2026-10-24 09:35` (local) / archivo creado `2026-10-24 15:35:24 UTC`
- `file`: `PostgreSQL custom database dump - v1.16-0`
- `md5sum`: `53170eada1bce8232cbdbddf51192d5e`
- Comando: `pg_dump -U "$POSTGRES_USER" -Fc -d "$POSTGRES_DB"` (dentro del contenedor `shoptitans-postgres`, salida por *stdout* al host) → `exit=0`

**Verificación de inspección (`pg_restore --list`, sin restaurar):** `exit=0`; cabecera: `dbname: shoptitans`, `Format: CUSTOM`, `Compression: gzip`, `TOC Entries: 229`, `Dump Version: 1.16-0`, `from 17.10`.
> **Advertencia:** `pg_restore --list` correcto **no** demuestra que la restauración funcione; solo confirma que el archivo es estructuralmente reconocible. La integridad de restauración **no** está verificada.

**Respaldo previo (intacto):** `backups/shoptitans_pre_recuperacion_20261023_203355.dump` · **83.238 bytes** · mtime `2026-10-23 20:33` · `md5sum 61f012053aba28dcda5c6162d9bda165`.

**Conteos previos (solo lectura, `psql`):** `blueprints`=1549 · `blueprint_components`=2747 · `crafting_materials`=186 · `blueprint_elemental_affinities`=911 · `elements`=8 · `enchantments`=73 · `spirit_affinities`=38 · `spirits`=1 · `resources`=12 · `qualities`=5 · `item_categories`=40 · `workers`=19 · `worker_levels`=50 · `users`=0. Coinciden con la línea base registrada en §9-D.

**Incidencias:** ninguna. El dump se creó con `exit=0` y las verificaciones de inspección y conteos también finalizaron con `exit=0`.

**Restricciones respetadas:** no se ejecutó el seeder ni seeders/importadores/migraciones/restauraciones; no se modificaron datos ni estructura de PostgreSQL; no se sobrescribió ni eliminó ningún respaldo; sin commits ni push.

**Conclusión:** el entorno queda **preparado** para solicitar autorización de ejecución de `BlueprintMvpSeeder`. Cualquier restore real requeriría autorización explícita adicional (el respaldo vive en el host; `backups/` no está montado en el contenedor `postgres`).

---

## Addendum 2 — 2026-10-26: ¿sigue vigente el respaldo del día 24?

**Pregunta:** si el respaldo `shoptitans_pre_blueprintmvpseeder_20261024_153512.dump` sigue vigente respecto a la base actual.

**Respuesta: SÍ, sigue vigente.** Se verificó por **comparación no destructiva** entre el respaldo del 24 y un volcado fresco de la base tomado el 2026-10-26 (a `/tmp`, sin publicarlo como respaldo).

**Método (sin restaurar, sin escribir en PostgreSQL):**
1. Volcado fresco: `pg_dump -Fc` → `/tmp/st_verify_20261026_162306.dump` (exit 0), **mismo tamaño**: 432.554 bytes.
2. Comparación de **datos**: `pg_restore --data-only --file=-` de ambos dumps → **38.739 líneas cada uno**; `diff` → **solo 4 líneas distintas**, y son el par de tokens aleatorios `\restrict`/`\unrestrict` que PostgreSQL 17.10 genera por sesión.

| Comparación | Resultado |
|---|---|
| Datos (`--data-only`) | Idénticos salvo tokens `\restrict` aleatorios (38.739 líneas c/u; 4 líneas distintas) |
| Esquema (`--schema-only`) | Idénticos salvo tokens `\restrict` (1.769 líneas c/u; 4 líneas distintas) |
| TOC (`pg_restore --list`, sin la línea «Archive created at») | **Idéntico** |
| Tamaño del archivo | 432.554 bytes en ambos |

**Interpretación:** la igualdad de datos + esquema (excluyendo tokens efímeros) demuestra que **la base no ha cambiado desde el 2026-10-24 15:35:24 UTC**; el respaldo del 24 representa fielmente el estado actual. Los md5 del *archivo completo* difieren únicamente por la cabecera «Archive created at» y los tokens `\restrict` (esperado, no implica cambio de datos).

**Acción tomada:** conforme a la instrucción condicional («si no puede garantizarlo, cree uno nuevo»), **NO se creó un respaldo nuevo**, dado que la vigencia quedó garantizada con evidencia. El respaldo del 24 y el del 23 permanecen intactos; `backups/` sin cambios. Los archivos temporales de verificación en `/tmp` fueron eliminados.

**Alcance de la garantía:** aplica a **datos y esquema**. No se verificó una **restauración real** (requiere autorización y un destino, y no forma parte de esta comprobación).

---

## Addendum 3 — 2026-10-26: ejecución controlada (ciclo cerrado)

Con la verificación previa y el respaldo vigentes, se **autorizó y ejecutó** `db:seed --class=BlueprintMvpSeeder` sobre `shoptitans`:

- **Comando:** `docker compose exec -T app php artisan db:seed --class="Database\\Seeders\\BlueprintMvpSeeder"`
- **Resultado:** **exit 0**; las 4 etapas ejecutadas (evidencia: `blueprint_components` recreados el 2026-10-26 16:26:45 UTC).
- **Conteos antes = después** (1549/2747/186/911/8/73/38/1/12/5/40/19/50/0) · **0 huérfanos · 0 duplicados** · restricciones FK/UNIQUE/CHECK presentes.
- **Informe de ejecución:** `docs/reports/2026-10-26_ejecucion_controlada_blueprint_mvp_seeder.md`.
