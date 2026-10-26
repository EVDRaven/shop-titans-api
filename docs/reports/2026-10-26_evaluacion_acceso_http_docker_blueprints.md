# Reporte Técnico — Evaluación de acceso HTTP a la API de Blueprints mediante Docker

**Fecha:** 2026-10-26
**Autor:** Editor IA
**Estado:** PROPUESTA (investigación completada; **sin implementar**)

---

## 1. Objetivo

Investigar la alternativa **más sencilla, segura y coherente** con la arquitectura actual para acceder por HTTP a la API de Blueprints desde el equipo anfitrión. Tarea exclusivamente de investigación y propuesta: **no se implementa nada**.

## 2. Alcance

**Incluye:** inspección de solo lectura del Compose real, Dockerfile, rutas, bootstrap, `.env.example`, `src/public/`, documentación y restricciones; determinación del proceso HTTP existente (si lo hay), puertos/interfaces, red y naturaleza del entorno; comprobación de puerto libre en el anfitrión; comparativa de alternativas; recomendación y plan de implementación propuesto.

**Excluye:** cualquier modificación de `docker-compose.yml`, Dockerfiles, configuración de Laravel, `.env`, `phpunit.xml`; reconstrucción o reinicio de contenedores; ejecución de pruebas, migraciones, seeders, importadores o restauraciones; instalación de dependencias; manipulación de respaldos; commits y push.

## 3. Estado inicial comprobado (con evidencia por archivo)

### 3.1 ¿Qué proceso ejecuta el servicio `app`?
- Imagen construida sobre `php:8.4-fpm` ([docker/php/Dockerfile#L1](file:///home/draven/Documentos/shop-titans-api/docker/php/Dockerfile#L1)).
- **Peros el servicio sobrescribe el arranque a un proceso inerte**: `command: tail -f /dev/null` ([docker-compose.yml#L13](file:///home/draven/Documentos/shop-titans-api/docker-compose.yml#L13)). El contenedor es una **shell CLI inactiva** destinada a `php artisan`, `composer` y pruebas.
- **No hay ningún servidor HTTP en ejecución** dentro de `app`. Aunque el binario `php-fpm` existe en la imagen (`/usr/local/sbin/php-fpm`), **no está arrancado** (el comando es `tail`).
- Evidencia: `docker compose exec app ps` no disponible (sin `ps`), pero el comando del contenedor es `tail`, y `docker compose ps` no muestra ningún listener servido.

### 3.2 ¿Existe Nginx, Apache u otro servidor HTTP?
- **No.** Solo existe `docker/php/Dockerfile`; no hay configuraciones ni servicios de Nginx/Apache ([LS docker/](file:///home/draven/Documentos/shop-titans-api/docker)).
- Existe [src/public/.htaccess](file:///home/draven/Documentos/shop-titans-api/src/public/.htaccess#L1-L25) (reglas de reescritura estilo Apache), pero **no hay Apache** que las use. Es el `.htaccess` por defecto de Laravel, inerte en esta arquitectura.
- `nginx` y `apache2` no están instalados en el contenedor (`which nginx apache2` → no encontrados).

### 3.3 ¿Qué puerto escucha cada proceso y en qué interfaz?
- `app`: **ninguno** (no hay proceso que escuche). El contenedor declara **`9000/tcp` como EXPOSE** (heredado de `php:8.4-fpm`), visible en `docker compose ps` como `9000/tcp`; **no hay publicación** al anfitrión (la columna PORTS no muestra `0.0.0.0:9000->`). **EXPOSE ≠ puerto publicado**: el 9000 es metadatos de la imagen y no es alcanzable desde el host.
- `postgres`: escucha 5432 internamente y **sí está publicado** en todas las interfaces del host: `0.0.0.0:5432->5432/tcp, [::]:5432->5432/tcp` ([docker-compose.yml#L29-L30](file:///home/draven/Documentos/shop-titans-api/docker-compose.yml#L29-L30); confirmado por `docker compose ps` y `ss -ltn` → `*:5432`).

### 3.4 Cómo están conectados `app` y `postgres`
- Por la **red por defecto de Docker Compose**, resolviendo el host por nombre de servicio: `DB_HOST=postgres`, `DB_PORT=5432` ([.env.example#L23-L28](file:///home/draven/Documentos/shop-titans-api/src/.env.example#L23-L28)). No hay red custom en el Compose.

### 3.5 Punto de entrada HTTP y rutas
- Front controller Laravel: [src/public/index.php](file:///home/draven/Documentos/shop-titans-api/src/public/index.php#L1-L20).
- Rutas API con prefijo automático `api` + `v1`: `/api/v1/blueprints` y `/api/v1/blueprints/{id}` ([api.php#L6-L9](file:///home/draven/Documentos/shop-titans-api/src/routes/api.php#L6-L9)).
- Sin middleware de autenticación ([bootstrap/app.php#L15-L17](file:///home/draven/Documentos/shop-titans-api/src/bootstrap/app.php#L15-L17)); render JSON para `api/*` ([bootstrap/app.php#L18-L21](file:///home/draven/Documentos/shop-titans-api/src/bootstrap/app.php#L18-L21)).
- Health check disponible en `/up` ([bootstrap/app.php#L13](file:///home/draven/Documentos/shop-titans-api/src/bootstrap/app.php#L13)).

### 3.6 Puerto libre en el anfitrión (solo lectura)
- `ss -ltn` (host): **libres** los puertos 80, 8000, 8080 y 9000. En uso relevante: `*:5432` (publicación de Postgres) y otros puertos ajenos al proyecto (`27036`, `631`, `53`, etc.).

### 3.7 ¿Desarrollo o despliegue?
- **Solo desarrollo.** `APP_ENV=local`, `APP_DEBUG=true` ([.env.example#L2-L4](file:///home/draven/Documentos/shop-titans-api/src/.env.example#L2-L4)); contraseña de BD en claro `secret`; sin TLS, sin servidor web, sin gestión de secretos ni healthchecks de despliegue. `PROJECT_CONTEXT.md` no contempla despliegue y prohíbe Laravel Sail sin aprobación ([PROJECT_CONTEXT.md#L22](file:///home/draven/Documentos/shop-titans-api/PROJECT_CONTEXT.md#L22)).

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| — | — | **Ninguno.** Tarea de investigación (solo lectura). |

Artefactos escritos por esta tarea (autorizados): este informe y el hito en `AI_WORK_LOG.md`.

## 5. Alternativas comparadas

### Opción A — Servidor de desarrollo de Laravel (`php artisan serve`)

**A1. Persistente (cambio mínimo en Compose).**
Añadir publicación de puerto al servicio `app` y lanzar el servidor por `exec`:
```yaml
  app:
    # ...
    ports:
      - "127.0.0.1:8000:8000"
```
```bash
docker compose exec app php artisan serve --host=0.0.0.0 --port=8000
```
**A2. Sin cambios de archivos (recomendada para uso inmediato).**
```bash
docker compose run --rm -p 127.0.0.1:8000:8000 app \
  php artisan serve --host=0.0.0.0 --port=8000
```
- **Archivos a modificar:** A1 → `docker-compose.yml` (y opcionalmente `README.md`). A2 → **ninguno**.
- **Procesos/Compose:** no altera el `command` del servicio (`tail -f /dev/null` se conserva); añade/expone un puerto.
- **URL esperada:** `http://127.0.0.1:8000/api/v1/blueprints` (y `.../api/v1/blueprints/{id}`).
- **Exposición de red y riesgos:** enlazado **solo a `127.0.0.1`** → no accesible desde la LAN. El servidor embebido de PHP es **monohilo** y **no apto para producción**; solo para desarrollo.
- **Complejidad:** muy baja; sin configuraciones nuevas.
- **Dev/Prod:** ideal para **desarrollo**; no para producción.
- **Conflictos con contenedor/comandos Artisan:** ninguno estructural. Ejecutar `serve` ocupa una sesión (`exec` bloqueante) o un contenedor efímero (A2). El resto de comandos `artisan` siguen funcionando igual. Requiere `--host=0.0.0.0` porque el valor por defecto (`127.0.0.1` interno) no sería alcanzable desde el host aun con el puerto publicado.

### Opción B — Nginx + PHP-FPM
Añadir un servicio `nginx` que sirva `src/public` y hable con PHP-FPM por FastCGI en la red interna; arrancar `php-fpm` en `app`.
- **Archivos a modificar:** `docker-compose.yml` (nuevo servicio `nginx`, publicación de puerto, posible cambio de `command` de `app` a `php-fpm`), nuevo `docker/nginx/default.conf`, y quitar/condicionar el `command: tail -f /dev/null`.
- **Procesos/Compose:** cambio relevante: `app` dejaría de ser un contenedor inerte (`tail`) para correr FPM como PID 1; se añade un tercer contenedor.
- **URL esperada:** `http://127.0.0.1:8000/api/v1/blueprints` (con `:80` interno).
- **Exposición y riesgos:** el puerto HTTP se puede enlazar a `127.0.0.1`; el 9000 de FPM debe permanecer **interno** (sin publicar).
- **Complejidad:** media-alta (nuevo servicio, nueva config, gestión de PID/arranque).
- **Dev/Prod:** apropiado para **producción**; sobredimensionado para el desarrollo actual.
- **Conflictos:** el `command: tail -f /dev/null` **choca** con arrancar FPM; cambiarlo altera el flujo CLI actual (aunque `exec` seguiría disponible). Introduce archivos y mantenimiento nuevos → va contra "evitar sobrearquitectura" ([PROJECT_CONTEXT.md#L24-L25](file:///home/draven/Documentos/shop-titans-api/PROJECT_CONTEXT.md#L24-L25)).

### Opción C — Otras alternativas
- **C1. Cliente FastCGI en el host contra el 9000 de FPM:** arrancar FPM y hablar FastCGI directamente. Exótico, poco seguro y con mala ergonomía; descartado.
- **C2. Publicar el 9000 de FPM y usar un proxy FastCGI del host:** añade dependencias en el host y no aporta simplicidad; descartado.
- **C3. Consumir la API solo vía pruebas/`artisan` (sin HTTP):** ya funciona hoy, pero no satisface el objetivo (acceso HTTP desde el anfitrión).

### Cuadro resumen

| Criterio | A (artisan serve) | B (Nginx + FPM) | C |
|---|---|---|---|
| Archivos a cambiar | A1: 1 (`compose`); A2: 0 | 2–3 (`compose` + conf + `command`) | — |
| Complejidad | Muy baja | Media-alta | Variable |
| URL | `127.0.0.1:8000/api/v1/...` | `127.0.0.1:8000/api/v1/...` | — |
| Aptitud dev / prod | Sí / No | Sí / Sí | — |
| Riesgo de red | Bajo (loopback) | Bajo (loopback; 9000 interno) | — |
| Coherencia con la arquitectura | Alta | Baja (sobrearquitectura) | — |

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| El proyecto ya sirve HTTP | `command: tail -f /dev/null`; sin Nginx/Apache; sin publicación de puerto HTTP | **Rechazada** (no hay HTTP hoy) |
| El `9000/tcp` de `app` es accesible desde el host | `docker compose ps` muestra `9000/tcp` sin mapeo `0.0.0.0:9000->`; es EXPOSE de la imagen | **Rechazada** (EXPOSE ≠ publicado) |
| Postgres está publicado | `ss -ltn` → `*:5432`; PORTS `0.0.0.0:5432->5432/tcp` | Confirmada |
| El puerto 8000 está libre en el host | `ss -ltn` sin listeners en 8000 | Confirmada |
| El entorno es solo de desarrollo | `APP_ENV=local`, `APP_DEBUG=true`, credenciales en claro | Confirmada |

## 7. Discrepancias

Ninguna entre documentación y estado real. Nota: existe `.htaccess` (Apache) sin Apache instalado; es un artefacto inerte del esqueleto de Laravel, no una discrepancia.

## 8. Decisiones

### Propuestas
- **DH-1:** adoptar la **Opción A** para el acceso HTTP de desarrollo, enlazando el puerto **solo a `127.0.0.1`**.
- **DH-2:** modalidad: **A2** (sin cambios de archivos) para uso inmediato; **A1** (publicar `127.0.0.1:8000:8000` en `app`) si se desea persistencia documentada.
- **DH-3:** **no** introducir Nginx/PHP-FPM (Opción B) en esta fase, por sobrearquitectura y por no haber requisito de despliegue.
- **DH-4:** **no** modificar la publicación de PostgreSQL (5432) ni su red.

### Aprobadas
- Ninguna (pendiente de aprobación del usuario).

### Reemplazadas/Rechazadas
- Opciones B, C1 y C2: descartadas para esta fase.

## 9. Recomendación principal

**Opción A**, con **enlace a `127.0.0.1`**. En concreto:
- Para uso inmediato y **sin tocar archivos**: **A2** (`docker compose run --rm -p 127.0.0.1:8000:8000 app php artisan serve --host=0.0.0.0 --port=8000`).
- Si se quiere persistencia: **A1** (añadir `ports: ["127.0.0.1:8000:8000"]` al servicio `app` y lanzar el servidor por `exec`).

**Justificación:** el entorno es **solo de desarrollo**; la API es de **solo lectura** y sin autenticación; `artisan serve` no requiere nuevas dependencias, servicios ni configuraciones, respeta el `command` actual del contenedor y se alinea con la filosofía de mínima complejidad ([PROJECT_CONTEXT.md#L24-L25](file:///home/draven/Documentos/shop-titans-api/PROJECT_CONTEXT.md#L24-L25)). Nginx+FPM sería adecuado para producción, pero hoy es sobrearquitectura sin requisito que lo justifique.

## 10. Archivos que cambiarían en la fase de implementación

- **A1:** únicamente [docker-compose.yml](file:///home/draven/Documentos/shop-titans-api/docker-compose.yml#L1-L16) (bloque `ports` en `app`) y, opcionalmente, [README.md](file:///home/draven/Documentos/shop-titans-api/README.md) (instrucción de uso).
- **A2:** **ningún** archivo (comando ad-hoc).
- **No** se modifican Dockerfile, `.env`/`.env.example`, `bootstrap/app.php`, rutas, `phpunit.xml` ni PostgreSQL. Los `links` de paginación usan el host de la petición (`$paginator->url()`), por lo que **no** es necesario cambiar `APP_URL` ([BlueprintController.php#L40-L45](file:///home/draven/Documentos/shop-titans-api/src/app/Http/Controllers/Api/V1/BlueprintController.php#L40-L45)).

## 11. Propuesta de configuración y mapeo de puertos (sin aplicar)

```yaml
# docker-compose.yml — PROPUESTA (NO APLICADA)
services:
  app:
    # ...
    ports:
      - "127.0.0.1:8000:8000"   # solo loopback del anfitrión
```
```bash
# Arranque del servidor de desarrollo (dentro del contenedor)
docker compose exec app php artisan serve --host=0.0.0.0 --port=8000
```
- Puerto interno del contenedor: 8000 (proceso `artisan serve`).
- Puerto del anfitrión: 8000, enlazado a `127.0.0.1` (no a `0.0.0.0`).
- PostgreSQL permanece **sin cambios** (5432 tal cual está).

## 12. Riesgos, limitaciones y criterios de aceptación

**Riesgos / limitaciones**
- `artisan serve` (servidor embebido de PHP) es **monohilo y no apto para producción**; aceptable solo para desarrollo.
- El proceso `serve` es **bloqueante**: ocupa una sesión (`exec`) o un contenedor efímero (A2).
- Es una **limitación del entorno**, no del código: la API podría servirse por HTTP real solo con el servidor arrancado.
- **Fuera de alcance y observado:** PostgreSQL ya está publicado en `0.0.0.0:5432` (exposición amplia). No se propone cambiarlo en esta tarea; se deja constancia.

**Criterios de aceptación (verificables tras implementar)**
1. `GET http://127.0.0.1:8000/api/v1/blueprints?per_page=1` → `200` con `{data, meta, links}`.
2. `GET http://127.0.0.1:8000/api/v1/blueprints/1` → `200` con `{data}`; un id inexistente → `404` JSON.
3. `GET .../blueprints?filter[component]=Ruby` → `200`; `?filter[foo]=x` → `422`.
4. `ss -ltn` en el host muestra `127.0.0.1:8000` (y **no** `0.0.0.0:8000`).
5. El 9000 de `app` **no** queda publicado; PostgreSQL sin cambios.
6. Los comandos `artisan`, seeders y pruebas siguen funcionando sin alteración.

## 13. Comandos de validación (a ejecutar solo tras aprobación)

```bash
# 1) Rutas servidas por Laravel
docker compose exec -T app php artisan route:list --path=api

# 2) Listado, detalle, filtro y errores
curl -sS -i "http://127.0.0.1:8000/api/v1/blueprints?per_page=1"
curl -sS -i "http://127.0.0.1:8000/api/v1/blueprints/1"
curl -sS -i "http://127.0.0.1:8000/api/v1/blueprints?filter[component]=Ruby"
curl -sS -i "http://127.0.0.1:8000/api/v1/blueprints?filter[no_existe]=x"

# 3) Verificar enlace solo a loopback (host)
ss -ltn | grep -E ':8000'

# 4) Verificar que PostgreSQL no cambió
docker compose ps
```

## 14. Resultado

- Se determina que **hoy no existe ningún proceso HTTP** en el proyecto: `app` es una shell CLI inerte (`tail -f /dev/null`), sin Nginx/Apache, y el `9000/tcp` visible es **solo EXPOSE**, no un puerto publicado.
- Se recomienda la **Opción A** con enlace a `127.0.0.1` (A2 sin cambios; A1 para persistencia).
- **No se implementó nada.** Sin cambios de archivos, Docker, PostgreSQL, respaldos, commits ni push.

## 15. Preguntas para ChatGPT

- ¿Se prefiere A2 (cero cambios de archivos) o A1 (publicar el puerto de forma persistente en `docker-compose.yml`)?
- ¿Debe documentarse el arranque del servidor en `README.md` como parte de la implementación?
- ¿Se desea, en una fase futura y separada, abordar el endurecimiento del entorno (p. ej. no publicar PostgreSQL en `0.0.0.0`)?

## 16. Confirmaciones de seguridad

- **Sin modificaciones** de `docker-compose.yml`, Dockerfiles, configuración de Laravel, `.env`, `phpunit.xml` ni ningún archivo de implementación.
- **Sin reconstruir ni reiniciar contenedores**; sin cambios de estado en Docker ni en el sistema.
- **PostgreSQL intacto**: sin migraciones, seeders, importadores, restauraciones ni cambios de datos/red.
- **Sin dependencias nuevas, sin tocar respaldos, sin commits ni push.**
- Todas las comprobaciones fueron de **solo lectura** (`ss -ltn`, `docker compose ps`, `docker compose exec … ls/which`, lectura de archivos).

## 17. Datos no verificables / bloqueos

- **No verificable sin cambiar el entorno:** la respuesta HTTP real de la API (no se arrancó ningún servidor; prohibido por el alcance). Se marca como pendiente de validación **post-implementación** (§13).
- No se pudo usar `ps` dentro del contenedor (`exec: "ps": executable file not found`); se suplió con el `command` declarado y `docker compose ps`.

## 18. Próximo paso

1. Revisar y aprobar (o corregir) la recomendación y las decisiones DH-1..DH-4.
2. Si se aprueba, implementar la Opción A (A2 o A1) en una tarea separada y ejecutar las validaciones de §13.
3. **No** se inicia la implementación sin aprobación del usuario.
