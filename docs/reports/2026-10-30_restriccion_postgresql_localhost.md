# Reporte Técnico — Restricción de la publicación de PostgreSQL a localhost (ejecutada)

**Fecha:** 2026-10-30
**Autor:** Editor IA
**Estado:** IMPLEMENTADO Y VALIDADO
**Objeto:** restringir la publicación del puerto PostgreSQL `5432` de todas las interfaces a solo `127.0.0.1` (Alternativa A del diagnóstico del 2026-10-30), aprobada expresamente por el usuario.

---

## 1. Autorización y alcance

El usuario **autorizó expresamente implementar la Alternativa A**. Alcance autorizado: modificar **únicamente** la publicación del puerto PostgreSQL en `docker-compose.yml`. Sin cambios en `.env`/`.env.example`, configuración de Laravel, servicio `app` ni su comando A1, Dockerfiles, redes, volúmenes, esquema/datos de PostgreSQL ni respaldos. Sin añadir `--no-reload` ni workers adicionales.

## 2. Estado previo a la aplicación

```text
docker compose ps -a →
  shoptitans-app       Up 23 minutes   127.0.0.1:8000->8000/tcp
  shoptitans-postgres  Up 7 days       0.0.0.0:5432->5432/tcp, [::]:5432->5432/tcp

docker inspect shoptitans-postgres (Mounts) →
  Type=volume  Name=shop-titans-api_postgres_data
  Source=/var/lib/docker/volumes/shop-titans-api_postgres_data/_data
  Destination=/var/lib/postgresql/data  Mode=rw

docker volume ls → shop-titans-api_postgres_data (local)
```

- **Volumen en uso (antes):** `shop-titans-api_postgres_data`, montado en `/var/lib/postgresql/data`.
- Confirma el uso del volumen persistente existente (`postgres_data` declarado en `docker-compose.yml`).
- `psql` disponible en el host (`/usr/bin/psql`).

**Diff revisado (previo):** el único cambio pendiente de configuración era el binding del `5432` (el resto del diff correspondía a la implementación A1 previa: comando y `ports` de `app`).

## 3. Cambio aplicado

[docker-compose.yml#L32-L33](file:///home/draven/Documentos/shop-titans-api/docker-compose.yml#L32-L33):

```diff
     ports:
-      - "5432:5432"
+      - "127.0.0.1:5432:5432"
```

## 4. Validación de configuración

```text
docker compose config  → exit 0
  postgres:
    ports:
      - mode: ingress
        host_ip: 127.0.0.1
        target: 5432
        published: "5432"
        protocol: tcp
```

`host_ip: 127.0.0.1` presente para `postgres` (y para `app` en `8000`). El diff confirmó que el **único** cambio de configuración fue el binding del `5432`.

## 5. Aplicación

```text
docker compose up -d postgres →
  ✔ Container shoptitans-postgres Started  0.8s
```

Se recreó **únicamente** el servicio `postgres` (`docker inspect` → `Created: 2026-10-30T22:57:13Z`). No se ejecutó `docker compose down`, `down -v`, `docker volume rm` ni ninguna eliminación de volúmenes. No se ejecutaron migraciones, seeders, importaciones ni escrituras SQL.

## 6. Validaciones posteriores

### 6.1 Publicación y contenedores

```text
docker compose ps →
  shoptitans-app       Up          127.0.0.1:8000->8000/tcp
  shoptitans-postgres  Up (recreate) 127.0.0.1:5432->5432/tcp

docker port shoptitans-postgres → 5432/tcp -> 127.0.0.1:5432
```

**Ya no aparece** `0.0.0.0:5432` ni `[::]:5432`. Confirmación adicional en el host:

```text
ss -ltn | (5432) → LISTEN 0 4096 127.0.0.1:5432 0.0.0.0:*
```

Solo escucha en **loopback**. **Ambos servicios quedan activos.**

### 6.2 Volumen (antes vs después)

| | Antes | Después |
|---|---|---|
| Nombre | `shop-titans-api_postgres_data` | `shop-titans-api_postgres_data` |
| Destino | `/var/lib/postgresql/data` | `/var/lib/postgresql/data` |
| Driver / Mode | `local` / `rw` | `local` / `rw` |

**Idéntico.** El volumen persistente permaneció intacto (la recreación no lo afectó).

### 6.3 Acceso desde el host por `127.0.0.1:5432` (solo lectura)

```text
psql -h 127.0.0.1 -p 5432 -U shoptitans -d shoptitans -tAc "SELECT current_database(), current_user, version();"
→ shoptitans | shoptitans | PostgreSQL 17.10 (Debian 17.10-1.pgdg13+1) …
psql_exit=0
```

PostgreSQL responde por `127.0.0.1:5432`. **Contraseña no mostrada** (se leyó de `.env` por sustitución de shell y no se imprimió).

### 6.4 Acceso desde el contenedor `app` (nombre interno + Laravel)

```text
docker compose exec app getent hosts postgres → 172.18.0.2  postgres
docker compose exec app php artisan tinker --execute="…DB::selectOne('select current_database() as db, current_user as usr, version() as ver')…"
→ {"db":"shoptitans","usr":"shoptitans","ver":"PostgreSQL 17.10 …"}
```

`postgres` sigue resolviendo al contenedor por la red interna y **Laravel consulta `shoptitans`** por su conexión configurada. La restricción del binding **no** afectó la comunicación interna.

### 6.5 Prueba HTTP

```text
GET http://127.0.0.1:8000/api/v1/blueprints?per_page=1 → http_code=200
"meta":{"current_page":1,"per_page":1,"total":1549,"last_page":1549,"from":1,"to":1}
```

HTTP 200 y `total=1549` confirmados; paginación correcta.

### 6.6 Resultado global

**Todas las validaciones fueron satisfactorias.** No se detectó ningún fallo.

## 7. Riesgos y limitaciones

- **Interrupción breve:** la recreación de `postgres` cerró las conexiones existentes durante unos segundos (comportamiento esperado e informado). Los datos persistieron (volumen intacto).
- **Sin verificación LAN/Internet:** conforme a las restricciones, **no** se hicieron pruebas desde otros dispositivos. No obstante, `ss -ltn` demuestra que el socket ya **no escucha** en ninguna interfaz de red (solo loopback), por lo que no es alcanzable desde la LAN ni (por este puerto) desde Internet.
- **Alcance limitado:** no se modificó firewall ni router; el análisis de reglas UFW/iptables sigue pendiente (requiere root).

## 8. Archivos modificados

- [docker-compose.yml](file:///home/draven/Documentos/shop-titans-api/docker-compose.yml#L32-L33) — única línea de binding del `5432` (`"5432:5432"` → `"127.0.0.1:5432:5432"`).
- [AI_WORK_LOG.md](file:///home/draven/Documentos/shop-titans-api/AI_WORK_LOG.md) — hito de esta implementación.
- **Informe (creado):** `docs/reports/2026-10-30_restriccion_postgresql_localhost.md`.

## 9. Confirmaciones

- Publicación restringida a `127.0.0.1:5432`; **sin** `0.0.0.0` ni `[::]`.
- Volumen `shop-titans-api_postgres_data` **idéntico** antes/después (sin pérdida de datos).
- **No** se ejecutó `docker compose down`, `down -v`, `docker volume rm` ni eliminación de volúmenes.
- **No** se ejecutaron migraciones, seeders, importaciones ni escrituras SQL.
- **No** se modificaron `.env`, configuración de Laravel, servicio `app`, Dockerfiles, redes, respaldos, firewall ni router.
- **Sin** commit ni push; **sin** pruebas desde otros dispositivos.
