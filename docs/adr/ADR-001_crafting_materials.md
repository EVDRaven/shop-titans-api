# ADR-001 — Tabla `crafting_materials` como catálogo unificado de componentes de fabricación

## Estado
Aceptada (diseño) — Pendiente de ejecución en base de datos.

## Fecha
2026-10-13

## Contexto

La tabla `blueprint_components` tiene `component_id` como FK hacia `blueprints.id`. Sin embargo, el análisis del Excel v26.9.1 demostró que solo el 7.5% de los componentes son blueprints reales. El resto son Quest Components (83.6%), Sigils (8.2%) y Enchantments (0.8%), entidades que no existen en la tabla `blueprints`.

Esto genera una discrepancia arquitectónica (D-1): la FK `component_id → blueprints.id` no puede representar la totalidad de los componentes del juego.

## Evidencia

- 190 componentes únicos y 2,901 referencias en el Excel.
- Distribución: 103 blueprints, 66 quest components, 6 sigils, 15 enchantments.
- La hoja `Quest Components` y `Full Moon Fusions` (sigils) no tienen columnas de identificador numérico; el nombre es el único atributo de identidad.
- No existe evidencia de cambios de nombre históricos en el Changelog, pero solo se dispone de una versión del Excel, por lo que no se puede demostrar estabilidad interversiones.

## Decisión

Se adopta la **Opción 4 (híbrida)** del reporte D-1:

1. Crear la tabla `crafting_materials` que materializa los atributos comunes de los cuatro tipos de componentes (`blueprint`, `quest_component`, `sigil`, `enchantment`).
2. Cada material se identifica de forma estable mediante `source_key` (UNIQUE, NOT NULL):
   - `blueprint:{blueprints.id}` para materiales de tipo `blueprint`.
   - `quest_component:{name}`, `sigil:{name}`, `enchantment:{name}` para los tipos sin tabla de origen.
3. `blueprint_id` es una FK nullable hacia `blueprints.id`, obligatoria solo para `material_type = 'blueprint'`.
4. Cambiar `blueprint_components.component_id` para que apunte a `crafting_materials.id`.
5. Aplicar restricciones de integridad en PostgreSQL:
   - CHECK de coherencia entre `material_type` y `blueprint_id`.
   - CHECK de formato de `source_key` según el tipo.
   - UNIQUE en `source_key`.
   - UNIQUE parcial en `blueprint_id` (WHERE NOT NULL).
   - FK `blueprint_id → blueprints.id` con `ON DELETE RESTRICT`.

## Alternativas consideradas

- **Opción 1 — FK específica a `blueprints`:** incompleta, no resuelve quest components ni sigils.
- **Opción 2 — Tablas de origen separadas con múltiples FK:** correcta pero excesiva para datasets pequeños (66 quest components, 6 sigils).
- **Opción 3 — Datos inline sin `source_id`:** simple pero pierde el enlace al blueprint original y no tiene clave estable de identidad.
- **Opción 4 — Híbrida (adoptada):** balance entre integridad, simplicidad y rendimiento.

## Consecuencias

### Positivas
- Una FK real enforceable (`blueprint_id`) para los materiales de tipo blueprint.
- Sin `source_id` polimórfico: no hay ambigüedad sobre el origen.
- Consultas simples: atributos básicos en una sola tabla, sin JOINs.
- No requiere tablas adicionales para quest components y sigils.
- `source_key` permite actualizar el nombre de un material sin crear duplicados (para blueprints).
- Restricciones PostgreSQL impiden estados inválidos de tipo y `blueprint_id`.

### Negativas
- Algunas columnas son nullable específicas por tipo (`quest_area`, `fuse_time_seconds`, etc.).
- Duplicación de `name`, `tier`, `gold_value` para blueprints (mitigada por `blueprint_id` y seeder idempotente).
- **Riesgo aceptado:** para `quest_component`, `sigil` y `enchantment`, `source_key` se basa en el nombre. Si el nombre cambia entre versiones del Excel, se crea un registro nuevo y el anterior queda huérfano. No se implementa detección automática de materiales huérfanos; el riesgo se documenta únicamente.

## Aprobación
- Usuario: Aprobada (diseño). Ejecución de base de datos no autorizada.
- ChatGPT: Pendiente.

## Referencias
- Reporte D-1: `docs/reports/2026-10-14_propuesta_modelado_blueprint_components.md`
- Reporte de `source_id`: `docs/reports/2026-10-14_revision_source_id_crafting_materials.md`
- Reporte de estabilidad de identidad: `docs/reports/2026-10-14_revision_estabilidad_identidad_materiales.md`
- Contexto: `PROJECT_CONTEXT.md`
