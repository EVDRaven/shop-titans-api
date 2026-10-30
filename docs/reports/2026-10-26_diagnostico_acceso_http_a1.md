# Reporte Técnico — Diagnóstico previo del acceso HTTP persistente (Opción A1)

**Fecha:** 2026-10-26
**Autor:** Editor IA
**Estado:** DIAGNÓSTICO COMPLETADO (solo lectura; sin cambios de archivos, Docker ni PostgreSQL). **Implementación de A1 NO autorizada todavía.**
**Objeto:** acceso HTTP persistente a `GET /api/v1/blueprints` mediante Opción A1.

---

## 1. Objetivo

Confirmar el estado **actual** del acceso HTTP y preparar una propuesta de implementación **A1**, sin efectuar cambios. No se asume vigente el diagnóstico anterior: se vuelve a comprobar el estado real.

## 2. Alcance y restricciones

**Incluye (solo lectura):** revisión de `docker-compose.yml`, `README.md`, `PROJECT_CONTEXT.md` y `AI_WORK_LOG.md`; consulta del estado de servicios y puertos publicados; determinación de la disponibilidad del puerto `8000`; comprobación de cambios locales previos relacionados con A1; propuesta del cambio mínimo, con secciones exactas.

**No ejecutado:** edición/creación de archivos de implementación; `docker compose up/down/restart/build` ni ningún comando que altere el estado de Docker; arranque o detención de contenedores; migraciones, seeders, importadores ni escrituras en PostgreSQL; tocar respaldos, dependencias, commits o push.

> Los únicos artefactos escritos por esta tarea son **este informe** y el **hito correspondiente en `AI_WORK_LOG.md`**, exigidos por los entregables.

## 3. Comandos de solo lectura ejecutados

| # | Comando | Propósito |
|---|---|---|
| 1 | `docker compose ps -a` | Estado de servicios y puertos publicados (incluye contenedores detenidos) |
| 2 | `ss -ltn \| grep -E ':(80\|8000\|8080\|9000\|5432)\b'` | Disponibilidad real de puertos en el anfitrión |
| 3 | `git status --short` | Detectar cambios locales previos relacionados con A1 |
| 4 | `git log --oneline -3` | Estado del historial |
| 5 | `git grep -n -i -E "artisan serve\|127\.0\.0\.1:8000\|ports:" -- docker-compose.yml README.md` | Buscar rastros de A1 en los archivos clave |
| 6 | `curl -s -g -o /dev/null -w "%{http_code}" "http://127.0.0.1:8000/api/v1/blueprints?per_page=1"` | Verificación de solo lectura de quién responde en 8000 |
| 7 | `docker inspect --format '…' shop-titans-api-app-run-ecad09fb4e91` | Identificar el contenedor que ocupa el 8000 (imagen, comando, estado) |

No se ejecutó ningún comando que modifique el estado de Docker, del sistema o de PostgreSQL.

## 4. Estado comprobado

### 4.1 `docker-compose.yml` (servicio `app`)

Servicio `app` ([docker-compose.yml#L2-L16](file:///home/draven/Documentos/shop-titans-api/docker-compose.yml#L2-L16)):

| Propiedad | Valor actual |
|---|---|
| `build.context` / `dockerfile` | `.` / `docker/php/Dockerfile` |
| `container_name` | `shoptitans-app` |
| `working_dir` | `/var/www/html` |
| `volumes` | `./src:/var/www/html` |
| `command` | `tail -f /dev/null` (shell CLI inerte) |
| `depends_on` | `postgres` |
| `ports` | **No declarado** (el servicio `app` no publica ningún puerto) |

El único bloque `ports` del archivo pertenece a `postgres` ([docker-compose.yml#L29-L30](file:///home/draven/Documentos/shop-titans-api/docker-compose.yml#L29-L30)): `"5432:5432"`.

**Conclusión: A1 NO está implementado.** No hay `ports` en `app`, ni cambio de `command` a `php artisan serve`.

### 4.2 Servicios y puertos publicados (estado real)

```text
NAME                                   IMAGE                 COMMAND                          SERVICE    STATUS         PORTS
shop-titans-api-app-run-ecad09fb4e91   shop-titans-api-app   "docker-php-entrypoi…"           app         Up 7 minutes   127.0.0.1:8000->8000/tcp
shoptitans-app                         shop-titans-api-app   "docker-php-entrypoi…"           app         Up 3 days      9000/tcp
shoptitans-postgres                    postgres:17           "docker-entrypoint.s…"           postgres   Up 3 days      0.0.0.0:5432->5432/tcp, [::]:5432->5432/tcp
```

```text
ss -ltn →
LISTEN 0  4096  127.0.0.1:8000  0.0.0.0:*
LISTEN 0  4096          *:5432         *:*
```

Interpretación:
- `shoptitans-app` (servicio persistente): solo `EXPOSE 9000/tcp`, **sin publicar** — sin cambios respecto al diagnóstico previo.
- `shoptitans-postgres`: `0.0.0.0:5432->5432/tcp` — **sin cambios**.
- **`shop-titans-api-app-run-ecad09fb4e91`**: contenedor **efímero** (`docker compose run`) con `127.0.0.1:8000->8000/tcp`, **en ejecución**.

### 4.3 Identidad del contenedor que ocupa el puerto 8000

```text
Name:     /shop-titans-api-app-run-ecad09fb4e91
Image:    shop-titans-api-app
Cmd:      php artisan serve --host=0.0.0.0 --port=8000
Status:   running
Started:  2026-10-26T20:47:43Z
```

Verificación de solo lectura del endpoint: `GET http://127.0.0.1:8000/api/v1/blueprints?per_page=1` → **HTTP 200**.

Es, por tanto, un **servidor de desarrollo A2 (contenedor efímero) que sigue en ejecución**, análogo al contenedor `shop-titans-api-app-run-2e1435aa3236` que la validación de A2 había detenido y eliminado.

### 4.4 Disponibilidad del puerto 8000

**El puerto 8000 NO está disponible:** está ocupado por el contenedor efímero anterior en **solo loopback** (`LISTEN 127.0.0.1:8000`). No hay ningún otro proceso escuchando en 8000.

> Este punto **contradice** el supuesto del diagnóstico previo ("puertos 80/8000/8080/9000 libres"): el estado cambió desde entonces.

### 4.5 `README.md`

- No contiene ninguna sección de **acceso HTTP** ni mención de `artisan serve`, `127.0.0.1:8000` ni `ports`. La búsqueda `git grep` solo halló `ports:` en `docker-compose.yml` (bloque de `postgres`).
- Secciones actuales: `Stack`, `Flujo`, `Estado` (checklist), `Preparación de datos (MVP de Blueprints)`, `Documentación`, `Regla`.
- **Punto natural para documentar A1:** una **nueva sección** entre `## Preparación de datos (MVP de Blueprints)` y `## Documentación`; opcionalmente, una línea en el checklist `## Estado`.

### 4.6 `PROJECT_CONTEXT.md` y `AI_WORK_LOG.md` (decisiones previas)

- `PROJECT_CONTEXT.md` **no** menciona acceso HTTP en "Decisiones activas"; no hay decisión oficial registrada sobre A1.
- `AI_WORK_LOG.md` registra la evaluación de acceso HTTP (DH-1..DH-4 **propuestas, no aprobadas**) y la validación de A2:
  - **DH-1** adoptar Opción A ligada a `127.0.0.1`.
  - **DH-2** modalidad **A2** (inmediata) o **A1** (persistente).
  - **DH-3** no introducir Nginx/PHP-FPM en esta fase.
  - **DH-4** no modificar la publicación ni la red de PostgreSQL.
  - Archivos que cambiarían en A1: **`docker-compose.yml`** (publicación en `app`) y, opcionalmente, **`README.md`**.
- La evaluación fija la URL esperada `http://127.0.0.1:8000/api/v1/blueprints` y descarta Nginx/Apache por sobrearquitectura.

### 4.7 Cambios locales previos relacionados con A1

- `git status --short`: **solo** `AI_WORK_LOG.md` (modificado por tareas documentales previas, no por A1) y el informe de la evaluación de rendimiento (sin seguimiento). **Ningún cambio local relacionado con A1.**
- `git log --oneline -3`: `ba17e9c (HEAD -> master, origin/master) docs: agregar ejemplos de respuesta a OpenAPI de blueprints` y dos commits previos. **No hay rama ni trabajo en curso de A1.**
- No existe `docker-compose.override.yml` ni otro archivo de composición: el único archivo es `docker-compose.yml`.

## 5. Bloqueo detectado

**B-1 — El puerto 8000 está en uso por un contenedor efímero A2 en ejecución** (`shop-titans-api-app-run-ecad09fb4e91`, `Up`, `127.0.0.1:8000->8000/tcp`).

Consecuencias:
- Si se implementara A1 y se levantara `app` con `ports: 127.0.0.1:8000:8000`, el arranque **fallaría por conflicto de puerto** mientras ese contenedor exista.
- **No se puede proceder a implementar/validar A1 sin antes retirar ese contenedor** (`docker stop` seguido de `docker rm`, o `docker compose rm`), lo que **altera el estado de Docker** y **queda fuera de esta autorización**.
- Nota operativa: al ser un contenedor creado con `docker compose run`, **`docker compose down` no lo elimina**; requiere `docker stop`/`docker rm` explícitos (coherente con la lección registrada en la validación de A2).

**No se ha detenido ni eliminado el contenedor** (prohibido en esta fase). Se reporta para su tratamiento en la fase de implementación.

## 6. Cambio mínimo propuesto para A1

Todo el cambio de infraestructura se concentra en **`docker-compose.yml`, servicio `app`**; la documentación, en **`README.md`**. No se tocan Dockerfiles, `.env`/`.env.example`, rutas ni configuración de Laravel, PostgreSQL ni respaldos.

### 6.1 `docker-compose.yml` — servicio `app`

**Punto exacto:** [docker-compose.yml#L13](file:///home/draven/Documentos/shop-titans-api/docker-compose.yml#L13) (línea `command: tail -f /dev/null`) y, a continuación, la adición de un bloque `ports`.

**Antes:**

```yaml
  app:
    build: 
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: shoptitans-app

    working_dir: /var/www/html

    volumes:
      - ./src:/var/www/html
    
    command: tail -f /dev/null

    depends_on:
      - postgres
```

**Después (cambio mínimo propuesto):**

```yaml
  app:
    build: 
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: shoptitans-app

    working_dir: /var/www/html

    volumes:
      - ./src:/var/www/html

    command: php artisan serve --host=0.0.0.0 --port=8000

    ports:
      - "127.0.0.1:8000:8000"

    depends_on:
      - postgres
```

**Justificación de las dos modificaciones (son inseparables):**
1. **`command`**: `app` hoy es una shell CLI inerte (`tail -f /dev/null`); **sin** cambiar el `command`, nada escucharía en el puerto 8000 dentro del contenedor y la publicación quedaría **inerte**. El valor propuesto reproduce exactamente el comando ya validado en A2 (`php artisan serve --host=0.0.0.0 --port=8000`).
2. **`ports: "127.0.0.1:8000:8000"`**: publica el puerto **solo en loopback** (criterio de diseño), no en `0.0.0.0`. Es el formato equivalente al mapeo ya observado en A2 (`127.0.0.1:8000->8000/tcp`).

**Notas de compatibilidad:**
- El servicio sigue siendo accesible con `docker compose exec app …` (seeders, importadores, `php artisan test`), ya que el proceso `artisan serve` corre en primer plano y el contenedor permanece vivo.
- No se altera `working_dir`, `volumes`, `container_name` ni `depends_on`.
- No se toca el bloque `ports` de `postgres` (DH-4).

### 6.2 `README.md` — nueva sección de acceso HTTP

**Punto exacto:** insertar una **nueva sección** entre `## Preparación de datos (MVP de Blueprints)` ([README.md#L23-L46](file:///home/draven/Documentos/shop-titans-api/README.md#L23-L46)) y `## Documentación` ([README.md#L48-L49](file:///home/draven/Documentos/shop-titans-api/README.md#L48-L49)). Texto propuesto (sujeto a aprobación):

```markdown
## Acceso HTTP (desarrollo)
La API se sirve mediante el servidor de desarrollo de Laravel dentro del contenedor `app`:

```bash
docker compose up -d app
curl http://127.0.0.1:8000/api/v1/blueprints
```

El puerto se publica **solo en `127.0.0.1`** (no accesible desde otras interfaces).
`php artisan serve` es el servidor embebido de PHP (monohilo) y **no sustituye** a un
servidor de producción (p. ej. Nginx + PHP-FPM), que queda fuera del alcance de esta fase.
```

**Opcional:** añadir una línea al checklist `## Estado` ([README.md#L11-L21](file:///home/draven/Documentos/shop-titans-api/README.md#L11-L21)), p. ej. `- Acceso HTTP (desarrollo): ✅`.

## 7. Consideraciones y decisiones pendientes de aprobación

| ID | Punto | Observación |
|---|---|---|
| **DH-2** | Elegir **A1** (persistente) frente a **A2** (efímera) | La propuesta asume A1. **Decisión del usuario pendiente.** |
| **N-1** | **Cambio de `command`** | Es imprescindible para que A1 funcione; implica que `app` deja de ser una shell CLI inerte y pasa a ejecutar el servidor de desarrollo. Debe aprobarse explícitamente. |
| **N-2** | **Retiro del contenedor B-1** | Bloqueo previo a la implementación; requiere autorización para alterar el estado de Docker. |
| **N-3** | `--no-reload` | La lección registrada indica que `--no-reload` fue crítico en un contenedor con variables de entorno sobreescritas. En el servicio `app` normal (con `src/.env` y `DB_HOST=postgres`) el contenedor A2 actual funciona **sin** `--no-reload` (HTTP 200). Se propone mantenerlo **sin** el flag, salvo que la validación de A1 revele inestabilidad. |
| **N-4** | `artisan serve` **monohilo** | Limitación de rendimiento asumida; apto solo para desarrollo. |
| **N-5** | Alcance documental | `CHANGELOG.md` y `PROJECT_CONTEXT.md` **no** se modificarían en esta propuesta (salvo instrucción expresa en sentido contrario). |
| **N-6** | Persistencia de `A2` | Si se aprueba A1, `A2` (contenedor `run`) deja de ser necesario; conviene retirar el contenedor B-1. |

## 8. Validaciones

No se ejecutaron pruebas de aplicación, migraciones, seeders ni operaciones en PostgreSQL (innecesarias para un diagnóstico de solo lectura). La única petición HTTP realizada fue un `GET` de comprobación (`/api/v1/blueprints?per_page=1` → 200), de solo lectura.

## 9. Confirmaciones

- **No se creó ni modificó ningún archivo** salvo **este informe** y el **hito en `AI_WORK_LOG.md`** (entregables exigidos).
- **No se alteró el estado de Docker**: no se ejecutó `up`/`down`/`restart`/`build`, ni se arrancaron, detuvieron o eliminaron contenedores. El contenedor efímero B-1 **sigue en ejecución** tal como estaba.
- **No se modificó PostgreSQL**: sin migraciones, seeders, importadores ni escrituras.
- **No se tocaron** respaldos, dependencias, `.env`, Dockerfiles, rutas ni configuración de la aplicación.
- **Sin commit ni push.** El historial permanece en `ba17e9c` (`master` / `origin/master`).

## 10. Recomendación y próximo paso

1. **Aprobar o corregir** el cambio mínimo propuesto (§6) y las decisiones N-1/N-2/N-3.
2. **Autorizar por separado** el retiro del contenedor efímero B-1 (bloqueo previo a la implementación).
3. Con la aprobación, **implementar A1** (editar `docker-compose.yml` y `README.md`) y **validar** por HTTP (`200`) en `127.0.0.1:8000`, con verificación de que el puerto queda ligado **solo a loopback**.

**Este diagnóstico se detiene aquí, a la espera de aprobación. No se implementa A1.**
