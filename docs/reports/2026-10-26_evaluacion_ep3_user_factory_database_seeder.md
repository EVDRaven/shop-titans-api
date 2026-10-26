# Reporte Técnico — Evaluación de EP-3: `User::factory()->create()` en `DatabaseSeeder`

**Fecha:** 2026-10-26
**Autor:** Editor IA
**Estado:** PROPUESTA (investigación de solo lectura completada; decisión de implementación pendiente de aprobación humana)

## 1. Objetivo

Formular una **recomendación fundamentada** sobre cómo debe comportarse `src/database/seeders/DatabaseSeeder.php` respecto a la creación de usuarios mediante `User::factory()->create()`, evaluando alternativas a la luz de cuatro criterios explícitos:

- **C1 — Idempotencia de `db:seed`:** la re-ejecución no debe fallar por la restricción UNIQUE de `users.email`.
- **C2 — No crear usuarios en BD real/desarrollo:** evitar sembrar `Test User`/`test@example.com` en PostgreSQL de desarrollo o producción.
- **C3 — Seguridad de credenciales:** evitar el password fijo `'password'` de `UserFactory` en entornos no de prueba.
- **C4 — Reutilizar `UserFactory`:** mantener el uso del factory, pero condicionado al entorno.

## 2. Alcance

**Incluye:** inspección estática (solo lectura) de `DatabaseSeeder`, `UserFactory`, modelo `User`, migración de `users`, rutas/middleware y suite de pruebas; análisis de idempotencia, credenciales y entornos; matriz de alternativas y recomendación.

**Excluye:** cualquier modificación de código, configuración, esquema o datos; ejecución de `db:seed`, seeders, importadores, migraciones o comandos destructivos; escrituras en PostgreSQL o cualquier base; commits o push. **No se modifica `DatabaseSeeder.php` en esta tarea.** La decisión final y la implementación corresponden al usuario.

## 3. Estado inicial

- El incidente de datos de PostgreSQL quedó **cerrado** el 2026-10-26 (ejecución controlada de `BlueprintMvpSeeder`, `exit 0`, Δ=0, 0 huérfanos/duplicados).
- `BlueprintMvpSeeder` es el punto de entrada oficial del dataset del MVP y **no** crea usuarios (EP-3 se dejó explícitamente fuera de su alcance).
- La base `shoptitans` tiene `users` = **0 filas**: el seeder global no se ha ejecutado contra PostgreSQL (su efecto colateral de usuario de prueba se evitó sembrando por clase).
- `DatabaseSeeder` mantiene, sin cambios desde el 2026-10-13, la línea final `User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com'])`.

## 4. Archivos inspeccionados (ninguno modificado)

| Archivo | Motivo |
|---|---|
| `src/database/seeders/DatabaseSeeder.php` | Objeto de la evaluación |
| `src/database/factories/UserFactory.php` | Origen de los atributos del usuario y de las credenciales |
| `src/app/Models/User.php` | Casts (`password => hashed`), `#[Fillable]`/`#[Hidden]`, traits |
| `src/database/migrations/0001_01_01_000000_create_users_table.php` | `users.email` UNIQUE (causa del fallo en re-ejecución) |
| `src/routes/api.php` | Ausencia de autenticación en la API |
| `src/bootstrap/app.php` | Ausencia de middleware de autenticación |
| `src/app/Http/Requests/BlueprintRequest.php` | `authorize(): true` (sin control de acceso por usuario) |
| `src/tests/Feature/*` (`BlueprintImportServiceTest`, `BlueprintMvpSeederTest`, `CraftingMaterialSeederTest`, `BlueprintApiTest`, …) | Verificar si algún test depende de `DatabaseSeeder`/del usuario de prueba |
| `src/tests/TestCase.php` | Base de pruebas (sin seed global) |
| `src/.env`, `src/.env.example` | Entorno efectivo (`APP_ENV=local`) |

## 5. Hallazgos de la investigación

### 5.1 Comportamiento actual de `DatabaseSeeder`

`DatabaseSeeder::run()` ([DatabaseSeeder.php](file:///home/draven/Documentos/shop-titans-api/src/database/seeders/DatabaseSeeder.php#L16-L34)) invoca 9 seeders de catálogo dentro de `call()` y, **fuera** del bloque, ejecuta:

```php
User::factory()->create([
    'name' => 'Test User',
    'email' => 'test@example.com',
]);
```

Usa `WithoutModelEvents`, que **no** suprime la creación del usuario. **Hecho verificado.**

### 5.2 Idempotencia (C1): el UNIQUE(`email`) rompe la re-ejecución

- `users.email` es **UNIQUE** ([migración](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/0001_01_01_000000_create_users_table.php#L17)).
- `User::factory()->create(...)` **no** es idempotente: en la segunda ejecución de `db:seed` se produce una violación de unicidad (`QueryException`) y el comando termina con **código de error**. Como la creación del usuario es **lo último**, los catálogos ya se aplicaron (éxito parcial, sin rollback global del seeder).

Este comportamiento ya se había documentado en `docs/reports/2026-10-23_evaluacion_crafting_material_seeder.md` (§5.4, R-2) y `docs/reports/2026-10-23_preflight_recuperacion_postgresql.md` (R-1). **Hecho verificado** (por lectura de código/esquema); el fallo exacto es inferencia estándar del comportamiento UNIQUE de PostgreSQL (no se ejecutó, por prohibición de la tarea).

### 5.3 Credenciales (C3): password fijo en `UserFactory`

`UserFactory::definition()` ([UserFactory.php](file:///home/draven/Documentos/shop-titans-api/src/database/factories/UserFactory.php#L25-L34)) genera `name`/`email` con `fake()`, fija `email_verified_at = now()` y usa:

```php
'password' => static::$password ??= Hash::make('password'),
```

Es decir, **password conocido y fijo** (`password`) para todo usuario creado por el factory. Aceptable en una base efímera de pruebas; **inaceptable** como credencial en una base persistente de desarrollo o producción. **Hecho verificado.**

### 5.4 Entornos (C2/C4): `APP_ENV=local` es el valor efectivo en desarrollo

- `src/.env` y `src/.env.example` definen `APP_ENV=local`; la BD de desarrollo `shoptitans` se ejecuta bajo ese entorno.
- La suite de pruebas usa SQLite `:memory:` con `force="true"` en `src/phpunit.xml` (entorno `testing`), aislada de PostgreSQL.
- **Conclusión:** atar la creación del usuario a `local` **reintroduciría** la violación de C2 (crearía el usuario en la BD de desarrollo). El único entorno donde el usuario de prueba es inocuo es `testing` (SQLite efímera). **Hecho verificado.**

### 5.5 Ausencia de autenticación: el usuario de prueba no cumple función operativa

- `src/routes/api.php` expone únicamente `GET /api/v1/blueprints` y `GET /api/v1/blueprints/{id}`, **sin** middleware de autenticación.
- `src/bootstrap/app.php` no registra middleware de auth; `User` **no** usa `HasApiTokens` (Sanctum); `BlueprintRequest::authorize()` retorna `true`.
- Ningún test depende de `DatabaseSeeder` ni del usuario `test@example.com`: las pruebas usan `RefreshDatabase` y `$this->seed([...])` con seeders de catálogo específicos; `BlueprintImportServiceTest` siembra solo `Resource/ItemCategory/Worker`.

**Conclusión:** en el estado actual del proyecto, el usuario sembrado por `DatabaseSeeder` **no** habilita ninguna funcionalidad (no hay login/API autenticada) y **no** es requerido por ninguna prueba. **Hecho verificado.**

### 5.6 Superficie de impacto

- `grep` de `Test User` / `test@example.com` en `src/`: **única** coincidencia en `DatabaseSeeder.php` (líneas 31–32).
- `users` en PostgreSQL de desarrollo: **0 filas** (el seeder global nunca se ejecutó sobre pgsql).

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| `User::factory()->create()` rompe la idempotencia de `db:seed` | `users.email` UNIQUE + factory sin `firstOrCreate` (§5.2) | Confirmada |
| `UserFactory` usa credenciales fijas (`'password'`) | `UserFactory::definition()` (§5.3) | Confirmada |
| `APP_ENV=local` es el entorno efectivo de desarrollo | `src/.env`, `src/.env.example` (§5.4) | Confirmada |
| Ningún test depende de `DatabaseSeeder` ni del usuario de prueba | Suite con `RefreshDatabase` + `$this->seed([...])` (§5.5) | Confirmada |
| La API no requiere autenticación | `routes/api.php`, `bootstrap/app.php`, `User` sin `HasApiTokens` (§5.5) | Confirmada |
| El usuario de prueba cumple una función operativa | Sin login/API autenticada (§5.5) | **Rechazada** |

## 7. Discrepancias

- **EC-1 (documental, menor).** `PROJECT_CONTEXT.md` (línea 31) afirma «Seeders, BlueprintImporter y API aún no implementados», mientras el repositorio ya contiene 12 seeders, 2 importadores y la API implementada. **Estado documentado:** pendiente. **Estado real:** implementado. **Impacto:** informativo. **Propuesta:** actualizar esa línea (fuera del alcance de esta tarea; requiere aprobación).
- No se detectaron discrepancias entre código y documentación respecto a EP-3.

## 8. Decisiones

### Propuestas

- **EP-3 (recomendación):** **condicionar por entorno** la creación del usuario de prueba, **reutilizando `UserFactory`** (C4) pero **restringida a `app()->environment('testing')`** (satisface C2), mediante un patrón **idempotente** (C1) y con **credenciales confinadas a la base efímera de pruebas** (C3). Alternativas evaluadas en §10.
- Recordatorio: EP-1 (no incorporar `CraftingMaterialSeeder` aislado) y EP-2 (seeder orquestador `BlueprintMvpSeeder`) ya están resueltas.

### Aprobadas

- Ninguna (esta tarea es de evaluación; la decisión corresponde al usuario).

### Reemplazadas/Rechazadas

- Ninguna.

## 9. Validaciones

**No se ejecutó ningún comando** (ni seeders, ni importadores, ni migraciones, ni escrituras). La validación fue **inspección estática de solo lectura** (lectura de archivos y búsquedas en el repositorio). No se tocó PostgreSQL.

## 10. Resultado final

### 10.1 Alternativas

| Alternativa | Descripción | C1 Idempotencia | C2 Sin usuarios en dev/prod | C3 Credenciales | C4 Reutiliza `UserFactory` |
|---|---|---|---|---|---|
| **A. Retirar `User::factory()`** | Eliminar la línea del seeder global | ✔ (nada que falle) | ✔ | ✔ | ✘ (no se usa el factory en el seeder) |
| **B. Condicionar a `testing` + idempotencia + credenciales** | Crear el usuario solo en `testing`, con `firstOrCreate`/guarda por `email`, reutilizando `UserFactory` | ✔ | ✔ | ✔ (confinado a SQLite efímera) | ✔ |
| **C. Statu quo** | Dejar la línea como está | ✘ | ✘ | ✘ | ✔ |

### 10.2 Recomendación

**Adoptar la alternativa B.** Resuelve simultáneamente los cuatro criterios y respeta la arquitectura vigente:

1. **Entorno (C2/C4):** ejecutar `User::factory()` **solo** si `app()->environment('testing')`. En `local`, `staging`, `production` u otros entornos, `DatabaseSeeder` **no** crea usuarios ⇒ no se siembra `Test User` en la BD de desarrollo ni en producción.
2. **Idempotencia (C1):** sustituir `create()` por un patrón idempotente (p. ej. `User::query()->firstOrCreate(['email' => $email], <atributos de UserFactory>)` o una guarda `exists()` previa), de modo que `db:seed` pueda repetirse sin violar UNIQUE(`email`).
3. **Credenciales (C3):** el password fijo `'password'` de `UserFactory` queda **confinado** a SQLite `:memory:` (efímera). Si en el futuro se necesitara un login de desarrollo en `local`, debería obtenerse un **opt-in explícito** (variable de entorno) con password derivado aleatoriamente, nunca el valor por defecto del factory.

> Nota: la alternativa **A** (retirar por completo) también satisface C1–C3 y es más simple, pero **no** cumple C4 (el criterio pide reutilizar `UserFactory` atándolo a entorno). Por eso B es la recomendación principal; A queda como opción si el usuario prioriza la simplicidad sobre C4.

## 11. Riesgos

| # | Riesgo | Mitigación |
|---|---|---|
| R-1 | Mantener el statu quo (C) rompe la idempotencia de `db:seed` y crea un usuario en la BD de desarrollo | Adoptar B (o A) |
| R-2 | Un gate de entorno mal definido (p. ej. incluir `local`) reintroduce la violación de C2 | Restringir estrictamente a `testing`; validar con prueba de entorno |
| R-3 | Un patrón `exists()`-luego-`create()` tiene una carrera teórica | Usar `firstOrCreate` (atómico a nivel de consulta) o confinar a `testing` |
| R-4 | Retirar por completo (A) podría sorprender a quien espera el stub de Laravel | Documentar la decisión; sin impacto operativo (no hay auth ni test dependiente) |

## 12. Pendientes

- Aprobación de la recomendación EP-3 (§10.2) y, si se aprueba, su **implementación en una tarea separada** (cambio en `DatabaseSeeder.php` + prueba de idempotencia/entorno).
- Actualización de EC-1 (§7) en `PROJECT_CONTEXT.md`, si se aprueba.
- Aprobación humana de H-1..H-4 (§17.5 del informe de revisión de la API).

## 13. Preguntas para ChatGPT / aprobación humana

1. ¿Se aprueba la **alternativa B** (usuario de prueba solo en `testing`, idempotente, reutilizando `UserFactory`)?
2. ¿O se prefiere la **alternativa A** (retirar `User::factory()` por completo), aun incumpliendo C4?
3. Si se aprueba B, ¿se desea **algún** usuario en `local` mediante un opt-in explícito (variable de entorno con password aleatorio), o ninguno fuera de `testing`?

## 14. Próximo paso recomendado

Recibir la decisión sobre EP-3 (§13). **No modificar `DatabaseSeeder` en esta tarea.** Si se aprueba B, planificar la implementación como tarea independiente con su propio informe, validación (`php -l`, `vendor/bin/pint --test`, prueba de idempotencia en SQLite `:memory:`) y sin tocar PostgreSQL.
