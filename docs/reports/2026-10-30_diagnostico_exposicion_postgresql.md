# Reporte Técnico — Diagnóstico de exposición del puerto PostgreSQL `5432`

**Fecha:** 2026-10-30
**Autor:** Editor IA
**Estado:** DIAGNÓSTICO COMPLETADO (estrictamente de solo lectura; sin cambios)
**Objeto:** publicación actual del puerto `5432` de `shoptitans-postgres` y su posible exposición innecesaria.

---

## 0. Objetivo y método

Determinar qué implica la publicación actual del puerto PostgreSQL `5432` en `0.0.0.0` y si representa una **exposición innecesaria**, distinguiendo con claridad entre **escucha en todas las interfaces**, **accesibilidad desde la LAN** y **accesibilidad desde Internet**.

**Método:** evaluación **estrictamente de solo lectura**. No se realizaron escaneos externos ni pruebas de conexión desde terceros.

**No ejecutado (restricciones respetadas):** modificación de `docker-compose.yml`, `.env`, reglas UFW o cualquier otro archivo; detención, reinicio, reconstrucción o eliminación de contenedores; comandos de escritura en PostgreSQL; modificación o eliminación de respaldos; cambios en la publicación del puerto `5432`; commits o push.

## 1. Estado observado

- El contenedor **`shoptitans-postgres` está activo (`Up`)** y publica el puerto `5432` en **`0.0.0.0` (IPv4) y `[::]` (IPv6)**; es decir, en **todas las interfaces** del anfitrión.
- La publicación **no declara `host_ip`**, por lo que Docker la resuelve a todas las interfaces. **Contraste:** el servicio `app` sí fija `host_ip: 127.0.0.1` (solo loopback), mientras que `postgres` queda abierto a todas las interfaces.
- El servicio `app` alcanza a `postgres` **por la red interna de Compose**, por lo que **no necesita** la publicación del puerto para funcionar.
- **El anfitrión está en una LAN privada** (`192.168.1.0/24`, IP `192.168.1.77`, puerta de enlace `192.168.1.254`), con lo que el `5432` publicado quedaría, en principio, alcanzable desde esa red **si no hay filtrado**.

## 2. Evidencia de las interfaces y puertos publicados

| # | Comando | Resultado observado |
|---|---|---|
| 1 | `docker compose ps -a` | `shoptitans-postgres \| Up \| 0.0.0.0:5432->5432/tcp, [::]:5432->5432/tcp` |
| 2 | `docker-compose.yml` (servicio `postgres`) | `ports: - "5432:5432"` (sin `host_ip`) |
| 3 | `docker compose config` | `ports`: `mode: ingress`, `target: 5432`, `published: "5432"`, `protocol: tcp` — **sin `host_ip`** |
| 4 | `ss -ltn` | `LISTEN 0 4096 *:5432 *:*` (todas las interfaces) — frente a `LISTEN 127.0.0.1:8000` de `app` |
| 5 | `ip -4 -o addr show` / `ip route` | `lo 127.0.0.1/8`; `enp4s0 192.168.1.77/24`; `docker0 172.17.0.1/16`; `default via 192.168.1.254 dev enp4s0` |
| 6 | `docker network inspect shop-titans-api_default` | `Subnet 172.18.0.0/16`, `Gateway 172.18.0.1` |
| 7 | `docker info` | `name=docker-desktop`, `server=29.6.2`, `SecurityOptions: [seccomp, cgroupns]` |
| 8 | `command -v ufw`, `systemctl is-active/is-enabled ufw` | `/usr/sbin/ufw`; `active`; `enabled` |
| 9 | `ufw status verbose`, `sudo -n ufw status verbose` | `ERROR: Debe ser root…`; `sudo: interactive authentication is required` → **no legible** |
| 10 | `iptables -t nat -S DOCKER`, `iptables -S DOCKER-USER` (y con `sudo -n`) | `Permission denied (you must be root)`; `sudo: interactive authentication is required` → **no legible** |

**Detalle de la publicación efectiva (evidencia #1) y declarada (evidencia #2/#3):**

```text
docker compose ps →
shoptitans-postgres | Up | 0.0.0.0:5432->5432/tcp, [::]:5432->5432/tcp
```

```yaml
# docker-compose.yml (servicio postgres)
    ports:
      - "5432:5432"
```

```text
docker compose config →
    ports:
      - mode: ingress
        target: 5432
        published: "5432"
        protocol: tcp
```

**Direcciones de escucha en el anfitrión (evidencia #4):**

```text
ss -ltn →
LISTEN 0  4096  127.0.0.1:8000  0.0.0.0:*
LISTEN 0  4096          *:5432         *:*
```

> `ss -ltnp` no mostró el proceso propietario (requiere privilegios de root); no fue posible atribuir el socket a un proceso concreto.

**Red del anfitrión (evidencia #5):** LAN privada `192.168.1.0/24`, puerta de enlace `192.168.1.254`.
**Red Docker (evidencia #6):** red por defecto de Compose `172.18.0.0/16` — `app` alcanza `postgres` **sin** depender del puerto publicado.

## 3. Alcance real que pudo verificarse

| Nivel | Estado | Evidencia / razón |
|---|---|---|
| **Escucha en todas las interfaces** | **CONFIRMADO** | `ss -ltn` → `*:5432`; `docker compose ps` → `0.0.0.0:5432` y `[::]:5432`; config sin `host_ip` |
| **Accesibilidad desde la LAN (`192.168.1.0/24`)** | **NO VERIFICABLE (latente)** | El anfitrión está en una LAN enrutable, pero el filtrado (UFW/iptables) **no pudo leerse** por requerir root. No se hicieron pruebas de conexión desde terceros (prohibido). |
| **Accesibilidad desde Internet** | **NO VERIFICABLE** | Depende del router `192.168.1.254` (NAT / reenvío de puertos), fuera del alcance del proyecto y no inspeccionable. No se realizaron escaneos externos. |

**Interpretación prudente:**
- Está **probado** que el servicio escucha en **todas las interfaces** del anfitrión; la publicación es **más amplia de lo necesario** para un uso exclusivamente local (a diferencia de `app`, acotado a `127.0.0.1`).
- **No está probado** que sea alcanzable desde la LAN ni desde Internet. Si UFW o las reglas de Docker efectivamente bloquean el tráfico entrante hacia `5432`, la exposición real sería nula; si no lo hacen, quedaría alcanzable al menos desde la LAN. **Esta disyuntiva no puede resolverse sin privilegios de root.**

## 4. Riesgos y limitaciones

**Riesgo conocido (no verificado en este entorno):** es un comportamiento **documentado** de Docker que los puertos publicados pueden **eludir las reglas de entrada de UFW**, porque el motor inserta sus propias cadenas NAT/FILTER que se evalúan antes que las reglas `INPUT` de UFW. Esto implicaría que un `ufw status` que parezca restrictivo **no garantizaría** el bloqueo del `5432` publicado. **No se pudo verificar** aquí (iptables requiere root) → se registra como **riesgo potencial**, no como hecho comprobado. Agrava la recomendación de acotar la publicación a `127.0.0.1`.

**Limitaciones:**

- **L1** — Reglas de **UFW no legibles** (requieren root) → el filtrado efectivo es desconocido.
- **L2** — Cadenas de **iptables no legibles** (requieren root) → el comportamiento real de la publicación de Docker es desconocido.
- **L3** — **Prohibido** realizar pruebas de conexión desde terceros → la accesibilidad LAN/Internet **no** se confirmó empíricamente.
- **L4** — **Backend Docker Desktop** (`name=docker-desktop`): el mecanismo de publicación podría diferir del Docker Engine nativo; no confirmado.
- **L5** — La configuración del **router/NAT** (`192.168.1.254`) está fuera del alcance del proyecto y no fue inspeccionada.

## 5. Recomendación técnica

1. **No mantener `0.0.0.0` salvo que exista un requisito explícito.** Para el uso actual (desarrollo local, `app` y herramientas del anfitrión), la publicación podría acotarse a **`127.0.0.1:5432:5432`**, replicando el patrón ya aplicado en `app`. Con ello:
   - Los contenedores siguen comunicándose por la red de Compose (`postgres:5432`) **sin depender** del puerto publicado.
   - Las herramientas del anfitrión siguen accediendo por `127.0.0.1:5432`.
   - Se elimina la escucha en todas las interfaces.
2. **No se ha implementado** dicho cambio: queda **fuera del alcance** de este diagnóstico y la restricción vigente prohíbe modificar la publicación del `5432`. Requiere **aprobación específica**.
3. **Antes de decidir**, conviene verificar con root el estado real de UFW y de las cadenas `DOCKER`/`DOCKER-USER`, y —si se busca descartar exposición a Internet— la configuración de reenvío de puertos del router.

## 6. Alternativas posibles (sin implementar ninguna)

| Alt. | Descripción | Efecto | Requisito |
|---|---|---|---|
| **A** | Acotar a `127.0.0.1:5432:5432` (recomendada) | Elimina la escucha en todas las interfaces; conserva acceso local | Aprobación + editar `docker-compose.yml` + recrear el contenedor |
| **B** | Mantener `0.0.0.0:5432:5432` y asegurar el filtrado con **UFW** | No cambia Compose; depende de reglas de firewall | Verificar con root y asumir el riesgo conocido UFW/Docker |
| **C** | Mantener `0.0.0.0:5432:5432` y reforzar con reglas explícitas en **`DOCKER-USER`** (iptables) | Bloqueo a nivel del motor Docker | Permisos root + reglas persistentes |
| **D** | **No publicar** el `5432` (`ports` eliminado) | Máxima restricción; solo acceso desde contenedores | Aprobación; rompe el acceso directo desde herramientas del anfitrión |

Ninguna alternativa fue implementada.

## 7. Decisiones que requieren aprobación del usuario

- **D-EXP-1:** ¿Se acota la publicación a `127.0.0.1:5432:5432` (Alternativa A)? Es la recomendación.
- **D-EXP-2:** ¿Se opta por una alternativa distinta (B/C/D) o por **no cambiar nada**?
- **D-EXP-3:** ¿Se autoriza una verificación con **root** (lectura de UFW e iptables) para cerrar las limitaciones **L1/L2** antes de decidir?
- **D-EXP-4:** ¿Se autoriza, si procede, inspeccionar la configuración de **NAT/reenvío del router** (`192.168.1.254`) para descartar exposición a Internet (**L5**)?

## 8. Confirmaciones

- **Diagnóstico estrictamente de solo lectura.**
- **No** se modificó `docker-compose.yml`, `.env`, reglas UFW ni ningún otro archivo de configuración.
- **No** se detuvo, reinició, reconstruyó ni eliminó ningún contenedor; **no** se cambió la publicación del `5432`.
- **No** se ejecutaron escrituras en PostgreSQL ni operaciones sobre respaldos.
- **Sin commit ni push.**
