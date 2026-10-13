# Análisis del Ajuste 3 — `Unlock Prerequisite` en Blueprints

> Generado: 2026-08-28.
>
> Objetivo: Validar que todos los valores distintos de vacío / `"---"` en la hoja **Blueprints, columna C ("Unlock Prerequisite")** correspondan a `worker_key` válidos existentes en la hoja **Workers, columna A**.

---

## 1. Resultado de la validación

### ❌ HIPÓTESIS INICIAL INVALIDADA

> **`Unlock Prerequisite` NO contiene únicamente `worker_keys` de la hoja Workers.**
>
> De hecho, contiene **valores de más de 7 tipos distintos** (workers, packs, offers, chests, content passes, tutorial, collection book).

---

## 2. Datos numéricos concretos

| Indicador | Valor |
|---|---|
| Total blueprints en la hoja | 1.613 |
| Blueprints con prerequisito `---` o vacío | 463 |
| Blueprints CON prerequisito definido | 1.150 |
| Valores DISTINTOS presentes en la columna C | 439 |
| Valores que SÍ coinciden con `workers.worker_key` | **8** |
| Valores que NO coinciden con ningún worker | **431** |

---

## 3. Valores que SÍ son Workers (8)

Los 8 `worker_key` válidos que aparecen realmente como `Unlock Prerequisite`:

- `Blacksmith`
- `Carpenter`
- `Cook`
- `Herbalist`
- `Jeweler`
- `Priestess`
- `Tailor`
- `Wizard`

> Nota: En la hoja Workers existen también `Master` y `Ripley`, pero NUNCA aparecen como valor en la columna `Unlock Prerequisite` de Blueprints (aunque sí aparecen en `Required Worker` — cols R,T,V).

---

## 4. Valores que NO son Workers (431)

Categorías en las que se reparten estos 431 valores:

### 4.1 Mega Packs / Packs / Superior Packs / Offers (≈ 380)

Patrones representativos:
- Aniversario: `"6th Anniversary Mega Pack"`, `"7th Anniversary Superior Pack"`, `"Shop Titans 2024 Mega Pack"`, `"Anniversary 2026 Super Offer"`
- Temporadas / Festivos: `"Halloween Mega Pack"`, `"Christmas 2024 Superior Pack"`, `"Valentine 2023 Mega Pack"`, `"Easter Galore Mega Pack"`, `"Summer Vibes Superior Pack"`, `"Winter Festivities Mega Pack"`
- Eventos temáticos: `"Jurassic Mega Parck"`, `"Ghostbusters Mega Pack"`, `"Jumanji Superior Pack"`, `"Pirate"` (no aparece exacto; hay 25+ content passes de temas), `"Retro Arcade Mega Pack"`, `"Circus Superior Pack"`, `"Neonpunk Mega Pack II"`
- Alianzas / personajes: `"Lilu's Offer Pack"`, `"Sia's Superior Offer"`, `"Malady's Welcome Mega Pack"`, `"Reinhold's Secret Offer 2023"`, `"Wallace Superior Pack"`, `"Bunbun's Mega Pack"`
- Nuevos contenidos: `"The Baker Pack"`, `"The Bard Pack"`, `"The Monk Mega Pack"`, `"The Reaper Pack"`, `"Paladin Superior Pack"`, `"Spellblade Mega Pack"`, `"Chronomancer Superior Pack"`
- Regionales / culturales: `"Year of the Dragon Mega Pack"`, `"Moon Festival Superior Pack"`, `"Oktoberfest Mega Pack"`, `"Diwali"` (no exacto), `"Lunar New Year Pack"`, `"Tsukimi Festival Mega Pack"`
- Bundles genéricos: `"Starter Pack"`, `"Hero Pack"`, `"Deluxe Pack"`, `"Expert Pack"`, `"Explorer Pack"`, `"Worker Duo Superior Pack"`

### 4.2 Chests / Cofres (≈ 25)

Ejemplos: `"Wooden Chest"`, `"Limestone Chest"`, `"Forsaken Chest"`, `"Ancient Chest"`, `"Frozen Chest"`, `"Molten Chest"`, `"Sunken Chest"`, `"Tomb-Bound Chest"`, `"Opulent Chest"`, `"Luxurious Chest"`, `"Pearlescent Chest"`, `"Platinum Chest"`, `"Void Chest"`, `"Divine Chest"`, `"Minor Artifact Chest"`, `"Major Artifact Chest"`, `"Slimy Chest"`, `"Timeworn Chest"`, `"Prehistoric Chest"`, `"Bleak Chest"`, `"Otherworldly Chest"`.

### 4.3 Content Passes (≈ 25)

Patrón `"Content Pass: <nombre>"`:
- Temporadas navideñas / Halloween: `"Content Pass: Christmas 2024"`, `"Content Pass: Halloween 2022"`, `"Content Pass: Halloween Neighborhood"`
- Eventos colaborativos: `"Content Pass: Ghostbusters"`, `"Content Pass: Jumanji"`, `"Content Pass: Vampire"`
- Contenidos del juego: `"Content Pass: Dragonboat Festival"`, `"Content Pass: Ironclad"`, `"Content Pass: Mega Dungeon"`, `"Content Pass: Pirate"`, `"Content Pass: Soular Harmony"`, `"Content Pass: Spacefarer"`
- Año calendario / zodiaco: `"Content Pass: Year of the Dragon"`, `"Content Pass: Year of the Horse"`, `"Content Pass: Year of the Rabbit"`, `"Content Pass: Year of the Snake"`, `"Content Pass: Year of the Tiger"`, `"Content Pass: Cinco de Mayo 2022"`
- Otros: `"Content Pass: Avatar 2021"`, `"Content Pass: Grand Collection"`, `"Content Pass: Malady's Arrival"`, `"Content Pass: Most Fortunate"`, `"Content Pass: Rushing Winds"`, `"Content Pass: Shop Titans 2025"`.

### 4.4 Otros (2)

- `"Intro Tutorial"`
- `"Collection Book"`

---

## 5. Consecuencia en el diseño de la tabla `blueprints`

La propuesta original del campo:

```
blueprints.unlock_prerequisite_worker_id  FK -> workers.id  nullable
```

**ya no es válida**, porque el 98% de los 439 valores distintos NO son workers. Guardar solo el FK al worker significaría perder los otros 431 valores.

---

## 6. Tres propuestas de diseño

### 🅰️ Opción A — Campo texto plano (simple, RECOMENDADA para MVP)

```
blueprints.unlock_prerequisite  -> string nullable
```

- Guardamos el valor LITERAL del Excel:
  - `"Blacksmith"`, `"7th Anniversary Pack"`, `"Ancient Chest"`, `"Content Pass: Vampire"`, `"Intro Tutorial"`, `--- → null`.
- **Pros:**
  - Cero tablas extra, cero FK, cero lógica de clasificación.
  - Compatible con cualquier valor futuro que aparezca en nuevas versiones del Excel.
  - Cero riesgo de clasificar mal.
  - Alineado con "evitar sobrearquitectura" del proyecto.
- **Contras:**
  - No se puede hacer JOIN.
  - Para filtrar "solo workers" hay que hacer parseo o lista blanca manual.

---

### 🅱️ Opción B — 2 campos: tipo + valor (semirnormalizada)

```
blueprints.unlock_prerequisite_type    -> string enum/varchar nullable
blueprints.unlock_prerequisite_value   -> string nullable
```

Clasificación (ejemplos):
```
('worker',        'Blacksmith')
('worker',        'Tailor')
('pack',          '7th Anniversary Pack')
('superior_pack', '7th Anniversary Superior Pack')
('offer',         "Sia's Superior Offer")
('chest',         'Ancient Chest')
('content_pass',  'Vampire')                          // quita el prefijo "Content Pass: "
('tutorial',      'Intro Tutorial')
('collection',    'Collection Book')
(null,            null)                                // ---
```

- **Pros:**
  - Permite consultas tipo "cuántos blueprints se desbloquean por cofre".
  - No requiere tablas extra.
- **Contras:**
  - BlueprintImporter necesita una **función de clasificación** por prefijos/regex ("Mega Pack", "Superior Pack", "Offer Pack", "Chest", "Content Pass:", "Tutorial"...). Riesgo de valores nuevos mal clasificados en futuras versiones del Excel.
  - Mayor complejidad en el importador.

---

### 🅾️ Opción C — Tabla catálogo `unlock_sources` (totalmente normalizada)

```sql
-- Catálogo con ~439 filas en la versión actual
CREATE TABLE unlock_sources (
  id              BIGINT PRIMARY KEY,
  name            VARCHAR(255) UNIQUE NOT NULL,   -- "Blacksmith", "Ancient Chest", "7th Anniversary Pack"...
  type            VARCHAR(50) NOT NULL,           -- "worker" | "pack" | "offer" | "chest" | "content_pass" | "tutorial" | "collection" | "other"
  source_ref_id   BIGINT NULL,                    -- si type='worker' => FK a workers.id; else null
  created_at / updated_at
);

CREATE TABLE blueprints (
  ...
  unlock_prerequisite_source_id  BIGINT NULL  REFERENCES unlock_sources(id),
  ...
);
```

- **Pros:**
  - 100% normalizada, JOINs limpios, índices optimizados.
  - Fácil filtrar "blueprints por tipo de prerequisito".
- **Contras:**
  - Añade una tabla más al MVP (sale del scope "solo 14 tablas" pedido).
  - Requiere **seedear primero el catálogo `unlock_sources`** antes de insertar blueprints (orden de importación).
  - ~439 filas en el catálogo, de las cuales 431 son packs/offers/chests/eventos pasados que rara vez se consultarán como clave.
  - Riesgo similar a Opción B de clasificación errónea de `type`.

---

## 7. Recomendación final

> **Opción A (1 campo texto `unlock_prerequisite string nullable`)** es la elección óptima para el MVP del módulo Blueprints.

Justificación alineada con las prioridades del proyecto:
1. **El MVP de Blueprints no consulta por "tipo de prerequisito"**. El tipo de preguntas que se harán a la API serán por `nombre`, `tier`, `recursos`, `stats`, `crafteo`, no por "dame todos los BPs que se desbloquean por un pack de navidad 2023".
2. **Minimiza complejidad**: 0 tablas, 0 clasificación, 0 errores de categorización.
3. **Fácil migrar en el futuro** si aparece un caso de uso real. Un solo `ALTER TABLE blueprints ADD COLUMN unlock_prerequisite_type VARCHAR;` + actualización de valores desde el literal nos lleva a la Opción B sin tener que reimportar.

---

## 8. Siguiente paso

Confirmar **Opción A / B / C** por el usuario. Una vez confirmada, se actualizará `DATA_MODEL_ANALYSIS.md` y se procederá a crear:
1. 14 migraciones (ordenadas por dependencias de FK).
2. 14 modelos Eloquent con relaciones.
3. Validación con `migrate --pretend` sin ejecutar aún contra PostgreSQL.
