# Reporte Técnico — Formalización de `BlueprintMvpSeeder` como punto de entrada oficial

**Fecha:** 2026-10-24
**Autor:** Editor IA
**Estado:** IMPLEMENTADA — PENDIENTE DE REVISIÓN

## 1. Objetivo

Formalizar `Database\Seeders\BlueprintMvpSeeder` como el procedimiento oficial para preparar el conjunto de datos del MVP de Blueprints, documentándolo en `README.md` (comando oficial, orden de etapas, requisito del Excel y alcance).

## 2. Alcance

**Incluye:**
- Nuevo apartado documental en `README.md` con el comando oficial y su equivalente en Docker.
- Descripción del orden de etapas, del requisito del archivo Excel (ubicación esperada en `storage/app/`) y de la limitación de alcance (MVP de Blueprints, no instalación completa).
- Registro en `AI_WORK_LOG.md` y este informe.

**Excluye:**
- Cambios de código, firmas o comportamiento de comandos.
- Cambios de esquema, migraciones o nuevas dependencias.
- Modificación de `DatabaseSeeder.php` (EP-1/EP-3 fuera de alcance).
- Ejecución del seeder, `db:seed` global o importaciones contra PostgreSQL real.
- Commit o push.

## 3. Estado inicial

- `BlueprintMvpSeeder` ya implementado y validado (2026-10-24), con suite de pruebas aprobada.
- El comando oficial no estaba documentado en `README.md`.
- `README.md` (raíz del repositorio) describe stack, flujo, estado y documentación; no incluía instrucciones de preparación de datos.

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| `README.md` | Modificado | Documentar el comando oficial y el procedimiento del MVP de Blueprints. |
| `AI_WORK_LOG.md` | Modificado | Registrar la decisión aprobada, los cambios y las validaciones. |
| `docs/reports/2026-10-24_formalizacion_blueprint_mvp_seeder_punto_entrada.md` | Creado | Informe físico de la tarea. |

Sin cambios en `DatabaseSeeder.php`, comandos, migraciones ni dependencias.

## 5. Cambios realizados

Nuevo apartado **«Preparación de datos (MVP de Blueprints)»** en `README.md`, ubicado entre «Estado» y «Documentación», con:

- **Comando oficial:**
  ```bash
  php artisan db:seed --class="Database\\Seeders\\BlueprintMvpSeeder"
  ```
- **Equivalente en Docker** (contexto del repositorio):
  ```bash
  docker compose exec app php artisan db:seed --class="Database\\Seeders\\BlueprintMvpSeeder"
  ```
- **Orden de etapas:** catálogos → `import:blueprints` → `CraftingMaterialSeeder` → `import:blueprint-components`.
- **Requisito del Excel:** archivo en `storage/app/` (`src/storage/app/`); por defecto el `Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx`, si no existe se toma el primer `*.xlsx`; si no hay ninguno, el proceso se detiene con error antes de importar.
- **Alcance:** prepara el MVP de Blueprints; no constituye la instalación completa de la aplicación. Los seeders individuales siguen disponibles para tareas específicas y mantenimiento.

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| El namespace documentado coincide con la clase existente | `src/database/seeders/BlueprintMvpSeeder.php` declara `namespace Database\Seeders;` | Confirmada |
| El namespace resuelve por autoload PSR-4 | `src/composer.json`: `"Database\\Seeders\\": "database/seeders/"` | Confirmada |
| La clase es resoluble por el autoloader | `class_exists('Database\Seeders\BlueprintMvpSeeder')` → `clase OK` | Confirmada |
| El comando documentado sigue el formato estándar de `db:seed --class` | Comando provisto por el usuario; clase resoluble | Confirmada |

## 7. Discrepancias

Ninguna.

Observación menor (no bloqueante): `README.md` mantiene la sección «Estado» con `Seeders`/`BlueprintImporter`/`API`/`Tests` marcados como ⏳, aunque ya están implementados (coherente con la discrepancia documental EC-1 registrada sobre `PROJECT_CONTEXT.md:31`). No se modificó por estar fuera del alcance de esta tarea (solo documentación del punto de entrada).

## 8. Decisiones

### Propuestas
- Ninguna nueva.

### Aprobadas
- **Formalización de `BlueprintMvpSeeder` como procedimiento oficial** para preparar el dataset del MVP de Blueprints (esta tarea).

### Reemplazadas/Rechazadas
- Ninguna.

## 9. Validaciones

Comprobaciones realizadas (solo lectura; sin tocar la base de datos):

```bash
# PSR-4 en composer.json
#   "Database\\Seeders\\": "database/seeders/"

docker exec shoptitans-app sh -lc 'cd /var/www/html && php -r "require \"vendor/autoload.php\"; echo class_exists(\"Database\\\\Seeders\\\\BlueprintMvpSeeder\") ? \"clase OK\" : \"clase NO\"; echo PHP_EOL;"'
```

Resultado / exit code:

```text
clase OK                                        → exit 0
```

No se ejecutó `BlueprintMvpSeeder`, `db:seed` (ni global ni por clase), importadores contra PostgreSQL real, migraciones ni comandos destructivos. No se ejecutó la suite completa por no existir una razón técnica concreta (cambio exclusivamente documental).

## 10. Resultado final

- Comando oficial documentado y verificado como resoluble (`Database\Seeders\BlueprintMvpSeeder`).
- Documentación del procedimiento, requisito del Excel y alcance añadida a `README.md`.
- Seeders individuales preservados; sin cambios de código ni de base de datos.

## 11. Riesgos

- **Ejecución real pendiente:** el comando documentado no se ha ejecutado contra PostgreSQL real (prohibido en esta tarea); su funcionamiento se infiere del seeder ya implementado y probado en SQLite.
- **Discrepancia de «Estado» en `README.md`:** las marcas ⏳ pueden inducir a error sobre lo ya implementado (EC-1); su corrección queda pendiente de aprobación.
- **Ruta del Excel:** si no está en `storage/app/`, el seeder se detiene con `RuntimeException` antes de importar.

## 12. Pendientes

- Revisión independiente de `BlueprintMvpSeeder` (solo lectura) y primera ejecución real **con autorización explícita**.
- EP-3 (tratamiento de `User::factory()` en `DatabaseSeeder`).
- Corrección de la discrepancia documental EC-1 (`PROJECT_CONTEXT.md:31` y sección «Estado» de `README.md`).
- Puntos de aprobación humana de H-1..H-4 (§17.5 del informe de revisión de la API).

## 13. Preguntas para ChatGPT

- ¿Se desea también actualizar la sección «Estado» de `README.md` para reflejar el avance real (seeders/importador/API/tests)?
- ¿Conviene exponer la ruta del Excel por variable de entorno además de la detección automática en `storage/app/`?

## 14. Próximo paso recomendado

Someter `BlueprintMvpSeeder` a revisión independiente y, una vez aprobado, programar su primera ejecución contra PostgreSQL real con autorización explícita y respaldo previo. No modificar `DatabaseSeeder.php` (EP-3 fuera de alcance).
