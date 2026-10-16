# Reporte Técnico — Diagnóstico del fallo de `CraftingMaterialSeeder` (exit 255)

**Fecha:** 2026-10-15
**Autor:** Editor IA
**Estado:** DIAGNÓSTICO PENDIENTE DE APROBACIÓN HUMANA

## 1. Objetivo

Determinar la causa raíz del código de salida `255` al ejecutar:

```bash
docker compose exec app php artisan db:seed --class=CraftingMaterialSeeder --force
```

que solo muestra `INFO Seeding database.` y deja `crafting_materials` con 0 registros.

## 2. Alcance

- Inspección de código y configuración (solo lectura).
- Reproducción del fallo (solo lectura) mediante llamadas directas al lector, sin escribir en PostgreSQL.
- Propuesta de solución mínima, **sin implementarla**.

**Excluye:** ejecutar migraciones/seeders, escribir en PostgreSQL, cambiar el diseño o el alcance de D-1.

## 3. Estado inicial

- Migraciones D-1: `Ran` (`2026_10_14_000001`, `2026_10_14_000002`).
- `crafting_materials` existe con 0 filas.
- Entorno: Laravel 13.22.0, PHP 8.4.23, PostgreSQL 17.10 (Docker Compose).
- `docker/php/Dockerfile` = `FROM php:8.4-fpm`, **sin** `php.ini` propio ni ajuste de `memory_limit`.

## 4. Archivos modificados

**Ninguno.** Solo se creó este reporte y se actualizó `AI_WORK_LOG.md`. No se tocó código ni configuración.

## 5. Diagnóstico (evidencia concreta)

### 5.1 Configuración de PHP en el contenedor

```bash
docker compose exec -T app php -r 'echo ini_get("memory_limit"), ...'
```

Resultado:

```text
memory_limit=128M
display_errors=1
log_errors=0
error_log=
```

### 5.2 Reproducción del fallo con el límite real (128M)

```bash
docker compose exec -T app php -d memory_limit=128M -r \
  'require "vendor/autoload.php"; $r = new App\Imports\BlueprintExcelReader;
   $d = $r->readCraftingMaterialData("/var/www/html/storage/app/Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx");
   echo "OK components=", $d["componentNames"]->count(), PHP_EOL;'
```

Resultado:

```text
Fatal error: Allowed memory size of 134217728 bytes exhausted (tried to allocate 4096 bytes)
in /var/www/html/vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Reader/Xlsx.php on line 212
EXIT=255
```

### 5.3 El mismo camino con Laravel bootstrapeado (`tinker`), 128M

```bash
docker compose exec -T app php -d memory_limit=128M artisan tinker --execute='...readCraftingMaterialData(...)...'
```

Resultado (resumido):

```text
Symfony\Component\ErrorHandler\Error\FatalError
Allowed memory size of 134217728 bytes exhausted (tried to allocate 10485760 bytes)
at vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Collection/Cells.php:448
... Whoops\Run::handleShutdown()
EXIT=255
```

### 5.4 El mismo camino con 512M (control positivo)

```bash
docker compose exec -T app php -d memory_limit=512M -r '...readCraftingMaterialData(...)...'
```

Resultado:

```text
components=186 blueprints=1549 quest_components=66 sigils=6 enchantments=77
peak=150.5MB
EXIT=0
```

### 5.5 Conclusión del diagnóstico

El fallo **no** es un problema de datos, de rutas, de Docker ni de métodos inexistentes: es **agotamiento del límite de memoria de PHP (128M)**. PhpSpreadsheet carga el libro completo (`IOFactory::createReader('Xlsx')->load($filePath)`) y el pico de memoria medido es **150.5 MB**, por encima del límite de 128M.

Detalles verificados:

| Comprobación | Resultado |
|---|---|
| `memory_limit` efectivo en el contenedor | 128M |
| Pico real de memoria al leer el Excel | **150.5 MB** (> 128M) |
| Exit code del fallo | 255 (fatal error PHP, no una excepción capturable) |
| Exit code con 512M | 0 |
| Conteos con 512M | 186 / 1549 / 66 / 6 / 77 (coinciden con lo esperado) |
| ¿Existe `php.ini` propio o ajuste de `memory_limit`? | No (`FROM php:8.4-fpm`) |

**Sobre el síntoma "sin salida":** el fatal es un `E_ERROR` por memoria. Con `log_errors=0` y sin `error_log`, no se escribe nada en `storage/logs/laravel.log` (motivo de la ausencia de log). Según el camino de ejecución, el mensaje puede mostrarse (Whoops en `tinker`) o perderse: al estar la memoria agotada al 100 %, el manejador de apagado de Laravel no puede asignar memoria para renderizar el error, y `db:seed` termina en 255 sin texto. La reproducción cruda (`php -r`, sin bootstrap de Laravel) expone el mensaje real.

**Por qué los tests no lo detectaron:** `CraftingMaterialSeederTest` ejecuta `ini_set('memory_limit', '512M')` en su `setUp`, ocultando la diferencia entre el entorno de test y el de ejecución real.

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| Fallo por agotamiento de memoria (128M) | §5.2 y §5.3 (OOM en PhpSpreadsheet) | **Confirmada** |
| El lector funciona correctamente con memoria suficiente | §5.4 (exit 0, conteos correctos) | Confirmada |
| Falta de `php.ini`/`memory_limit` configurado en la imagen | §5.1, §5.5 y `Dockerfile` | Confirmada |
| No es un error de ruta del Excel | El archivo existe y `readCraftingMaterialData` lo lee bien con 512M | Confirmada (descartada) |
| No es una incompatibilidad de métodos | `class_exists` = true y la llamada funciona con 512M | Confirmada (descartada) |

## 7. Discrepancia

```text
DISCREPANCIA
Estado documentado: el seeder D-1 estaba "listo para ejecutar" y validado en tests.
Estado real: el seeder falla en el entorno de ejecución real por memory_limit=128M; su pico real es 150.5MB.
Evidencia: §5.2/§5.3 (exit 255, OOM PhpSpreadsheet) vs §5.4 (exit 0 con 512M, peak=150.5MB).
Impacto: D-1 no puede ejecutarse en el entorno actual. El comando import:blueprint-components (que usa read()) sufrirá el mismo OOM.
Propuesta: elevar memory_limit a 512M (ver §8).
Requiere aprobación: Sí (cambio de configuración de infraestructura).
```

## 8. Solución mínima propuesta

No se implementa ninguna todavía. Opciones ordenadas por mínima invasión:

### Opción A — Workaround inmediato, sin cambios en el repositorio (recomendada para desbloquear)
Ejecutar el proceso real con un límite mayor:

```bash
docker compose exec app php -d memory_limit=512M artisan db:seed --class=CraftingMaterialSeeder --force
```

Ventaja: cero cambios en código/infra. Inconveniente: hay que recordar el flag en cada ejecución (también para `import:blueprint-components`).

### Opción B — Permanente, cambio mínimo de infraestructura (recomendada a medio plazo)
Fijar `memory_limit=512M` en la imagen PHP, añadiendo a `docker/php/Dockerfile`:

```dockerfile
RUN echo 'memory_limit=512M' > /usr/local/etc/php/conf.d/zz-memory.ini
```

y reconstruir la imagen (`docker compose build app`). Ventaja: aplica a CLI y FPM sin repetir flags.

### Opción C — Cambio de código en el seeder
Añadir `ini_set('memory_limit', '512M')` al inicio de `CraftingMaterialSeeder::run()`. Resuelve el caso del seeder, pero mezcla preocupación de infraestructura en código de aplicación y no cubre el comando de importación. Solo si se rechaza A/B.

### Opción D — Reducir memoria del lector (fuera del alcance mínimo)
Usar `setReadFilter`/lectura por streaming para no cargar todo el libro. Es el arreglo de fondo, pero **no es un cambio mínimo** y afecta a D-1; sería una tarea aparte con su propia propuesta.

**Recomendación:** aplicar **A** de inmediato (al desbloquear la ejecución) y **B** como arreglo permanente y mínimo de infraestructura. C y D no se recomiendan en esta tarea.

## 9. Validaciones (solo lectura)

| Comando | Finalidad | Resultado |
|---|---|---|
| `php -r 'ini_get("memory_limit")...'` | Límite de memoria real | 128M |
| `php -d memory_limit=128M -r '...readCraftingMaterialData...'` | Reproducir el fallo | OOM + exit 255 |
| `php -d memory_limit=128M artisan tinker --execute='...'` | Reproducir bajo Laravel | OOM + exit 255 |
| `php -d memory_limit=512M -r '...readCraftingMaterialData...'` | Control positivo | exit 0; 186/1549/66/6/77; peak 150.5MB |
| `grep memory_limit src/tests` | Explicar por qué los tests no lo detectaron | `CraftingMaterialSeederTest` fija 512M |

No se ejecutaron migraciones, seeders ni escrituras en PostgreSQL.

## 10. Resultado

Causa raíz identificada y demostrada: **`memory_limit=128M` insuficiente**; PhpSpreadsheet requiere ~150.5 MB. Solución mínima propuesta (A inmediata + B permanente), pendiente de aprobación.

## 11. Riesgos

1. Si solo se aplica A, cualquier ejecución futura sin el flag volverá a fallar (incluido `import:blueprint-components`).
2. Elevar el límite a 512M solo cubre este caso; si el Excel crece, podría requerir revisión.
3. El "silencio" del fatal dificulta el diagnóstico; conviene mantener `log_errors` o un `error_log` para fatales.

## 12. Pendientes

1. Aprobación humana del diagnóstico y de la solución (A y/o B).
2. Tras aplicar B: reconstruir la imagen y re-ejecutar la siembra real (con autorización).
3. Verificar que el mismo ajuste cubre `import:blueprint-components`.

## 13. Preguntas para ChatGPT

1. ¿Se aprueba B (memory_limit=512M en la imagen) como arreglo permanente, usando A como desbloqueo inmediato?
2. ¿Se desea además activar `log_errors`/`error_log` para fatales en el contenedor?

## 14. Próximo paso

Esperar aprobación. Opción A para desbloquear de inmediato; Opción B como cambio mínimo de infraestructura.

## 15. Confirmación

No se ejecutaron migraciones, seeders, importaciones ni ninguna escritura en PostgreSQL. No se modificó código, esquema ni configuración. Todas las comprobaciones fueron de solo lectura.
