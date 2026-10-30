# Reporte Técnico — Validación del acceso HTTP persistente A1 (Blueprints MVP)

**Fecha:** 2026-10-30
**Autor:** Editor IA
**Estado:** IMPLEMENTADO Y VALIDADO
**Objeto:** acceso HTTP persistente a la API de Blueprints mediante la Opción A1.

---

## 1. Objetivo

Cerrar la implementación de **A1**: sustituir la shell CLI inerte del servicio `app` por el servidor de desarrollo de Laravel, publicar el puerto **solo en `127.0.0.1:8000`** y validar el acceso HTTP real, sin tocar PostgreSQL, respaldos, Dockerfiles ni configuración de la aplicación.

## 2. Archivos modificados

| Archivo | Acción | Cambio |
|---|---|---|
| `docker-compose.yml` | Modificado | Servicio `app`: `command` y nuevo bloque `ports` |
| `README.md` | Modificado | Nueva sección `## Acceso HTTP (desarrollo)` |

Ningún otro archivo fue modificado por esta implementación.

### 2.1 `docker-compose.yml` (servicio `app`)

```diff
-    command: tail -f /dev/null
+    command: php artisan serve --host=0.0.0.0 --port=8000
+
+    ports:
+      - "127.0.0.1:8000:8000"
```

Sin cambios en `build`, `container_name`, `working_dir`, `volumes` ni `depends_on`. El servicio `postgres` quedó **intacto**.

### 2.2 `README.md`

Nueva sección `## Acceso HTTP (desarrollo)`, situada entre `## Preparación de datos (MVP de Blueprints)` y `## Documentación`, que documenta: la URL `http://127.0.0.1:8000`, el arranque (`docker compose up -d app`), la comprobación (`curl http://127.0.0.1:8000/api/v1/blueprints`), la consulta de registros (`docker compose logs -f app`) y la advertencia de que `php artisan serve` es un servidor de desarrollo, no de producción.

## 3. Validación

### 3.1 `docker compose config`

Terminó **correctamente** (exit code 0) y resolvió:

- `app.command` → `[php, artisan, serve, --host=0.0.0.0, --port=8000]`
- `app.ports` → `[{mode: ingress, host_ip: 127.0.0.1, target: 8000, published: "8000", protocol: tcp}]`
- `postgres.ports` → `5432:5432` (sin `host_ip`, como antes)

### 3.2 `docker compose up -d app`

```text
[+] up 2/2
 ✔ Container shoptitans-postgres Running
 ✔ Container shoptitans-app      Started
```

Ambos contenedores quedan **activos**:

```text
shoptitans-app      | Up | 127.0.0.1:8000->8000/tcp
shoptitans-postgres | Up | 0.0.0.0:5432->5432/tcp, [::]:5432->5432/tcp
```

### 3.3 Publicación y enlace de puerto

- `docker compose ps` → **`127.0.0.1:8000->8000/tcp`** (solo loopback).
- `ss -ltn` → `LISTEN 127.0.0.1:8000` (**no** aparece `0.0.0.0:8000`).
- Log del contenedor: `INFO Server running on [http://0.0.0.0:8000].`

> Nota técnica: el `--host=0.0.0.0` es **interno al contenedor** (necesario para ser alcanzable a través del mapeo publicado); el enlace **en el anfitrión** queda limitado a `127.0.0.1` por el `host_ip` del mapeo.

### 3.4 Prueba HTTP real

| Petición | Resultado |
|---|---|
| `GET http://127.0.0.1:8000/api/v1/blueprints?per_page=1` | **HTTP 200** |
| `GET http://127.0.0.1:8000/api/v1/blueprints/59` | **HTTP 200** |
| `GET http://127.0.0.1:8000/up` | **HTTP 200** |

Respuesta del listado (JSON válido, claves `data`, `meta`, `links`):

```json
{
  "meta": { "current_page": 1, "per_page": 1, "total": 1549, "last_page": 1549, "from": 1, "to": 1 },
  "links": {
    "first": "http://127.0.0.1:8000/api/v1/blueprints?page=1",
    "last":  "http://127.0.0.1:8000/api/v1/blueprints?page=1549",
    "prev":  null,
    "next":  "http://127.0.0.1:8000/api/v1/blueprints?page=2"
  }
}
```

- `total = 1549` y `per_page = 1` (paginación coherente: `last_page = 1549`, `from = 1`, `to = 1`).
- `data` contiene **1** elemento (`id = 59`), conforme a `per_page=1`.

### 3.5 PostgreSQL

`shoptitans-postgres` permaneció **activo** (`Up`) y **sin modificaciones**: mismos puertos (`0.0.0.0:5432->5432/tcp, [::]:5432->5432/tcp`), sin migraciones, seeders, importadores ni escrituras. `total = 1549` confirma que los datos no cambiaron.

## 4. Limitaciones

- `php artisan serve` es el **servidor embebido de PHP (monohilo)**: apto solo para desarrollo, **no** para producción. Queda fuera del alcance de A1 introducir Nginx/Apache.
- La publicación persiste mientras el contenedor esté en marcha; `restart: unless-stopped` **no** está configurado en `app` (solo en `postgres`), por lo que el servicio `app` no se reinicia automáticamente tras un reinicio del host.
- PostgreSQL **sigue publicado en `0.0.0.0:5432`**; su análisis corresponde al diagnóstico de exposición (informe `docs/reports/2026-10-30_diagnostico_exposicion_postgresql.md`). A1 no lo modifica.

## 5. Confirmaciones

- Solo se modificaron **`docker-compose.yml`** y **`README.md`**.
- **No** se modificó PostgreSQL ni se ejecutaron migraciones, seeders, importadores o escrituras.
- **No** se tocaron respaldos, Dockerfiles, `.env`, `.env.example`, rutas ni configuración de Laravel; **no** se añadió Nginx ni Apache.
- Las únicas operaciones sobre contenedores fueron `docker compose up -d app` y el retiro del contenedor efímero previo que ocupaba el puerto 8000 (autorizado por el usuario).
- **Sin commit ni push.**
