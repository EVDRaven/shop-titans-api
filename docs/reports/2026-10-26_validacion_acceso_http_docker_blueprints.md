# Reporte Técnico — Validación del acceso HTTP temporal a la API de Blueprints (Opción A2)

**Fecha:** 2026-10-26
**Autor:** Editor IA
**Estado:** VALIDADA (implementación temporal ejecutada y verificada; servidor detenido y entorno restaurado)

---

## 1. Objetivo

Implementar y **validar** la **Opción A2** definida en `docs/reports/2026-10-26_evaluacion_acceso_http_docker_blueprints.md`: servir la API de Blueprints mediante el servidor de desarrollo de Laravel en un **contenedor temporal**, publicando el puerto **únicamente en `127.0.0.1:8000`**, y comprobar las respuestas HTTP reales, el enlace del puerto y el estado de los contenedores.

## 2. Alcance

**Incluye:** arranque del servidor con el comando autorizado; validaciones HTTP (`curl`) del listado, detalle, filtro por componente, filtro inválido y health check; verificación del enlace de puerto (`ss -ltn`) y de contenedores (`docker ps` / `docker compose ps`); creación de este informe y actualización de `AI_WORK_LOG.md`.

**Excluye:** cualquier modificación de `docker-compose.yml`, Dockerfiles, archivos de Laravel, `.env`, `phpunit.xml` u otros archivos de implementación; migraciones, seeders, importadores, restauraciones o escrituras en PostgreSQL; cambios en la publicación de PostgreSQL o en los respaldos; instalación de dependencias; reconstrucción de imágenes; commits y push.

## 3. Estado inicial

- En la evaluación previa se determinó que **no existía ningún proceso HTTP** en el proyecto: `app` es una shell CLI inerte (`command: tail -f /dev/null`), sin Nginx/Apache, y el `9000/tcp` visible es solo `EXPOSE`.
- Puertos 80/8000/8080/9000 **libres** en el anfitrión; `5432` publicado por PostgreSQL.
- El usuario **aprobó** la Opción A2 y autorizó el comando de arranque y las validaciones de este informe.

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| — | — | **Ninguno.** No se modificó ningún archivo de implementación. |

Artefactos escritos por esta tarea (autorizados): este informe y el hito en `AI_WORK_LOG.md`.

## 5. Cambios realizados

- **Arranque temporal (sin cambios de archivos):**
  ```bash
  docker compose run --rm -p 127.0.0.1:8000:8000 app \
    php artisan serve --host=0.0.0.0 --port=8000
  ```
  Se creó un contenedor efímero `shop-titans-api-app-run-2e1435aa3236` (imagen `shop-titans-api-app`) con mapeo **`127.0.0.1:8000->8000/tcp`**. El `command` del servicio `app` (`tail -f /dev/null`) **no se alteró**; el servidor se ejecutó como proceso adicional dentro de un contenedor `run` efímero.
- **Salida del servidor:** `INFO Server running on [http://0.0.0.0:8000]`. Se requirió `--host=0.0.0.0` para ser alcanzable desde el host a través del mapeo publicado.
- **Cierre:** tras las validaciones se detuvo el proceso y se eliminó el contenedor efímero (ver §9 y §10).
- **Sin cambios** en `docker-compose.yml`, Dockerfiles, `.env`, `bootstrap/app.php`, rutas, `phpunit.xml` ni PostgreSQL.

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| El servidor de desarrollo sirve la API en `127.0.0.1:8000` | `curl` → `200` en listado y detalle | Confirmada |
| El puerto queda ligado **solo a loopback** | `ss -ltn` → `LISTEN 127.0.0.1:8000` (sin `0.0.0.0:8000`) | Confirmada |
| El filtro `filter[component]` funciona por HTTP | `total=30` con `Iron Pine Cone` en ambas formas | Confirmada |
| Un filtro no permitido devuelve `422` | `filter[no_existe]=x` → `422` con mensaje JSON | Confirmada |
| El `9000/tcp` de `app` **no** se publica | `docker ps`/`docker compose ps` muestran `9000/tcp` sin mapeo | Confirmada |
| PostgreSQL no se modifica | `docker compose ps` idéntico antes/después (`0.0.0.0:5432->5432/tcp`) | Confirmada |

## 7. Discrepancias

Ninguna entre documentación y estado real.

Nota (no discrepancia): el comando `curl` interpreta `[` y `]` como rangos de glob; se requirió `curl -g` (globoff) para las URLs con `filter[...]`. Es un comportamiento de la herramienta, no de la API.

## 8. Decisiones

### Propuestas
- Ninguna nueva.

### Aprobadas / aplicadas
- **DH-1 / DH-2 (modalidad A2):** acceso HTTP temporal mediante `php artisan serve` en contenedor efímero, ligado a `127.0.0.1:8000`. Ejecutado y validado.
- **DH-3:** no se introdujo Nginx/PHP-FPM.
- **DH-4:** no se modificó la publicación ni la red de PostgreSQL.

### Reemplazadas/Rechazadas
- Ninguna.

## 9. Validaciones

**Entorno de ejecución:** servidor efímero activo durante las comprobaciones; terminales de solo lectura para `curl`, `ss`, `docker ps`.

### 9.1 Validaciones HTTP (códigos y contenido)

```bash
curl -sS -g -o /tmp/val1.txt -w "%{http_code}" "http://127.0.0.1:8000/api/v1/blueprints?per_page=1"
curl -sS -g -o /tmp/val2b.txt -w "%{http_code}" "http://127.0.0.1:8000/api/v1/blueprints/59"
curl -sS -g -o /tmp/val3b.txt -w "%{http_code}" "http://127.0.0.1:8000/api/v1/blueprints?filter[component]=Iron%20Pine%20Cone"
curl -sS -g -o /tmp/val3c.txt -w "%{http_code}" "http://127.0.0.1:8000/api/v1/blueprints?filter[component][eq]=Iron%20Pine%20Cone"
curl -sS -g -o /tmp/val4.txt  -w "%{http_code}" "http://127.0.0.1:8000/api/v1/blueprints?filter[no_existe]=x"
curl -sS -g -o /tmp/val5.txt  -w "%{http_code}" "http://127.0.0.1:8000/up"
```

Resultados observados:

| # | Petición | HTTP | Contenido |
|---|---|---|---|
| 1 | `GET /api/v1/blueprints?per_page=1` | **200** | `{data, meta, links}`; `meta.total=1549`, `per_page=1`, `links.first/last/next` con host `127.0.0.1:8000` |
| 2 | `GET /api/v1/blueprints/1` | **404** | **El ID 1 no existe** (documentado). Se reintentó con ID válido del listado |
| 2b | `GET /api/v1/blueprints/59` | **200** | `{data}` con `id=59`, `name="Squire Sword"` |
| 3 | `GET …?filter[component]=Iron Pine Cone` | **200** | `meta.total=30`; primeros: `Gladius`, `Espada`, `Serrated Cinquedea`, … |
| 3c | `GET …?filter[component][eq]=Iron Pine Cone` | **200** | `meta.total=30` (misma lista ⇒ ambas formas equivalentes) |
| 3-nota | `GET …?filter[component]=Ruby` | **200** | `meta.total=0` (no hay componente con ese nombre; filtro correcto, sin resultados) |
| 4 | `GET …?filter[no_existe]=x` | **422** | `{"message":"El filtro 'no_existe' no está permitido.","errors":{"filter[no_existe]":[ … ]}}` |
| 5 | `GET /up` | **200** | HTML "Application up" (health check de Laravel) |

Detalle de la respuesta del listado (extracto real):
```json
{"data":[{"id":59,"name":"Squire Sword","category":{"id":536,"name":"Sword"},"unlock_prerequisite":"Blacksmith","tier":1,"gold_value":50,"craft_time_seconds":15,"value_per_second":3.33,"merchant_xp":135,"airship_power":13,"stats":{"atk":16,"def":null,...},"energies":{"discount":5,"surcharge":15,...},"available_as_antique_at":null}],
 "meta":{"current_page":1,"per_page":1,"total":1549,"last_page":1549,"from":1,"to":1},
 "links":{"first":"http://127.0.0.1:8000/api/v1/blueprints?page=1","last":"…?page=1549","prev":null,"next":"…?page=2"}}
```
Observación: `gold_value`, `value_per_second`, `airship_power` y `energies` se serializan como **números JSON** (no strings), conforme a la decisión de formato vigente.

### 9.2 Enlace de puerto (host)

```bash
ss -ltn | grep ':8000'
```
Resultado:
```text
LISTEN 0      4096       127.0.0.1:8000       0.0.0.0:*
```
→ Ligado **exclusivamente a loopback**; no aparece `0.0.0.0:8000` (no accesible desde la LAN).

### 9.3 Estado de contenedores (durante el servicio)

```bash
docker ps --format 'table {{.Names}}\t{{.Image}}\t{{.Ports}}\t{{.Status}}'
```
Resultado:
```text
NAMES                                  IMAGE                 PORTS                                         STATUS
shop-titans-api-app-run-2e1435aa3236   shop-titans-api-app   127.0.0.1:8000->8000/tcp                      Up 47 seconds
shoptitans-app                         shop-titans-api-app   9000/tcp                                      Up 3 days
shoptitans-postgres                    postgres:17           0.0.0.0:5432->5432/tcp, [::]:5432->5432/tcp   Up 3 days
```
`docker compose ps` durante el servicio listó solo `shoptitans-app` (`9000/tcp`, sin mapeo) y `shoptitans-postgres` (`0.0.0.0:5432->5432/tcp`), **sin cambios** respecto al estado inicial.

### 9.4 Registro del servidor (evidencia de servicio efectivo)

El log de `artisan serve` registró las peticiones atendidas, p. ej.:
```text
2026-10-26 18:27:25 /api/v1/blueprints ......................... ~ 500.44ms
2026-10-26 18:27:26 /api/v1/blueprints/1 ....................... ~ 0.05ms
2026-10-26 18:27:36 /api/v1/blueprints ......................... ~ 500.34ms
2026-10-26 18:27:42 /api/v1/blueprints/59 ...................... ~ 0.13ms
```

### 9.5 Cierre y estado final

Tras detener el servidor:
```bash
ss -ltn | grep ':8000'          # -> (sin listeners en 8000)
docker ps --format 'table {{.Names}}\t{{.Ports}}\t{{.Status}}'
docker compose ps
```
Resultado final:
```text
=== ss -ltn | grep ':8000' ===
(sin listeners en 8000)
=== docker ps ===
NAMES                 PORTS                                         STATUS
shoptitans-app        9000/tcp                                      Up 3 days
shoptitans-postgres   0.0.0.0:5432->5432/tcp, [::]:5432->5432/tcp   Up 3 days
```
→ **Puerto 8000 liberado**, contenedor efímero **eliminado**, y contenedores persistentes (`shoptitans-app`, `shoptitans-postgres`) **intactos**. Entorno restaurado a su estado previo.

## 10. Resultado final

- **La Opción A2 funciona y queda validada.** La API responde por HTTP real en `http://127.0.0.1:8000/api/v1/blueprints` (listado `200`, detalle `200`, filtro por componente `200` con `total=30`, filtro inválido `422`, health `/up` `200`).
- **El puerto quedó ligado solo a `127.0.0.1`** (no accesible desde la LAN); PostgreSQL y el contenedor persistente `app` **sin cambios**.
- **El ID `1` no existe** en el dataset (el primer blueprint es `id=59`); se documenta y se validó el detalle con `59`.
- **Servidor detenido y entorno restaurado** (puerto liberado, contenedor efímero eliminado, contenedores persistentes intactos).
- **Sin cambios** en archivos de implementación, PostgreSQL ni respaldos. **Sin commit ni push.**

## 11. Riesgos

- `artisan serve` es el **servidor embebido de PHP: monohilo y no apto para producción**; su uso queda acotado a desarrollo/validación temporal.
- El proceso ocupa una sesión en primer plano; si se interrumpe abruptamente, puede requerir `docker stop` del contenedor efímero (fue necesario en esta ejecución: el cierre del cliente no eliminó el contenedor por sí solo; se detuvo explícitamente).
- **Fuera de alcance, observado:** PostgreSQL sigue publicado en `0.0.0.0:5432` (exposición amplia). No se modificó en esta tarea.

## 12. Pendientes

- Ninguno obligatorio. Opcionales sujetos a decisión del usuario: persistir el acceso HTTP (A1, `ports` en `docker-compose.yml` + documentación en `README.md`); ejemplos de respuesta en OpenAPI; endurecer la publicación de PostgreSQL.

## 13. Preguntas para ChatGPT

- ¿Se desea persistir el acceso mediante A1 o mantenerlo como procedimiento ad-hoc (A2)?
- ¿Debe documentarse el comando de arranque en `README.md` como paso de desarrollo?

## 14. Próximo paso recomendado

1. Revisar y aprobar este informe de validación.
2. Decidir si se persiste la configuración (A1) o se conserva A2 como procedimiento manual documentado.
3. Detenerse a la espera de aprobación antes de cualquier cambio de archivos de implementación.
