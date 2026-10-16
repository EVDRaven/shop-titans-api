# Reporte Técnico — Límite de memoria PHP permanente (memory_limit=512M)

**Fecha:** 2026-10-15
**Autor:** Editor IA
**Estado:** IMPLEMENTADO Y VALIDADO

## 1. Objetivo

Configurar `memory_limit=512M` de forma permanente para PHP en el contenedor `app`, reconstruir la imagen y comprobar la configuración efectiva. Implementa la **propuesta B** previamente aprobada por el usuario.

## 2. Alcance

- Añadir la configuración PHP permanente al `Dockerfile`.
- Reconstruir exclusivamente el servicio `app` y recrear el contenedor.
- Validar el valor efectivo de `memory_limit`.

**Excluye:** migraciones, seeders, importaciones, cambios en el lector Excel u otro código de aplicación, activación de logging, commits/push.

## 3. Estado inicial

- `docker/php/Dockerfile`: `FROM php:8.4-fpm`, sin `php.ini` propio ni ajuste de `memory_limit` → valor efectivo **128M**.
- Causa raíz documentada en `2026-10-15_diagnostico_fallo_crafting_material_seeder.md` (pico real de PhpSpreadsheet 150.5 MB > 128M).

## 4. Archivos modificados

| Archivo | Acción |
|---|---|
| `docker/php/Dockerfile` | Modificado (añadida 1 línea + comentario) |
| `docs/reports/2026-10-15_limite_memoria_php_permanente.md` | Creado |
| `AI_WORK_LOG.md` | Actualizado |

No se modificaron migraciones, seeders, importadores, lector Excel ni otro código de aplicación.

## 5. Cambio aplicado

En `docker/php/Dockerfile`, tras la instalación de la extensión GD y antes de copiar Composer:

```dockerfile
#Limite de memoria PHP (PhpSpreadsheet requiere mas de 128M)
RUN echo 'memory_limit=512M' > /usr/local/etc/php/conf.d/zz-memory.ini
```

Se usa el fichero `zz-memory.ini` en `conf.d` (prefijo `zz` para que se cargue al final y prevalezca).

## 6. Comandos ejecutados y resultados

| Paso | Comando | Resultado |
|---|---|---|
| Reconstrucción | `docker compose build app` | `Image shop-titans-api-app Built` (exit 0). Paso `#12 RUN echo 'memory_limit=512M' ...` → DONE. |
| Recreación | `docker compose up -d --no-deps app` | `Container shoptitans-app Recreated` y `Started` |
| Validación | `docker compose exec app php -r 'echo ini_get("memory_limit"), PHP_EOL;'` | **`512M`** |
| Comprobación del fichero | `docker compose exec app cat /usr/local/etc/php/conf.d/zz-memory.ini` | `memory_limit=512M` |
| Ficheros ini cargados | `docker compose exec app php --ini` | `Additional .ini files parsed: ... docker-fpm.ini, zz-memory.ini` |

Se usó `-T` en `exec` (equivalente no interactivo) para evitar la asignación de TTY en el shell del agente.

## 7. Estado de servicios (tras la recreación)

| Servicio | Imagen | Estado |
|---|---|---|
| app | shop-titans-api-app | Up (recreado, nueva imagen) |
| postgres | postgres:17 | Up 2 days (no recreado) |

Solo se recreó `app` (`--no-deps`); `postgres` permaneció intacto.

## 8. Resultado

`memory_limit=512M` es ahora el valor efectivo por defecto de PHP en el contenedor `app`, aplicable a CLI y FPM, sin necesidad de pasar `-d memory_limit=512M`. Cubre también el comando `import:blueprint-components`.

## 9. Git

- Estado previo: 6 ficheros modificados (`AI_WORK_LOG.md`, `docker/php/Dockerfile`, `Blueprint.php`, `BlueprintComponent.php`, `composer.json`, `composer.lock`) + numerosos nuevos sin trackear (código D-1, reportes, Excel). Todos preservados.
- Tras el cambio: el único delta respecto al estado previo es la línea nueva en `docker/php/Dockerfile` y la documentación de este reporte. No se eliminó ni revirtió nada.
- No se creó commit ni push.

## 10. Incidencias

Ninguna. La reconstrucción y la validación finalizaron correctamente.

## 11. Riesgos

1. Si el Excel creciera mucho más allá de lo actual, 512M podría volver a ser insuficiente; se revisaría entonces.
2. El fichero `zz-memory.ini` se hornea en la imagen: requiere reconstruir la imagen para futuros ajustes.

## 12. Pendientes

1. Revisión humana final del comando `import:blueprint-components` (corrección del código de salida).
2. Decidir si se activa `log_errors`/`error_log` para fatales (no incluido; fuera del alcance autorizado).

## 13. Próximo paso

Revisión humana del código del comando. No se avanzará sin autorización.

## 14. Confirmación

No se ejecutaron migraciones, seeders, importaciones ni ninguna escritura en PostgreSQL. No se modificó el esquema ni datos. No se activó logging. No se creó commit ni push.
