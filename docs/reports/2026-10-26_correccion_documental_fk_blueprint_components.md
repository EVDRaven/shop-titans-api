# Reporte Técnico — Corrección documental de la FK `blueprint_components.component_id`

**Fecha:** 2026-10-26
**Autor:** Editor IA
**Estado:** IMPLEMENTADA (pendiente de revisión)

## 1. Objetivo

Verificar (solo lectura) la relación real de la clave foránea `blueprint_components.component_id` y, si se confirma la discrepancia documental, corregir **únicamente** el fragmento obsoleto de `PROJECT_CONTEXT.md` para describir la relación verificada. No modificar código ni base de datos.

## 2. Alcance

**Incluye:** inspección de la migración `2026_10_14_000002`, de la migración original de `blueprint_components`, del modelo `BlueprintComponent` (relaciones) y del modelo `CraftingMaterial`; contraste con `PROJECT_CONTEXT.md`; corrección del fragmento y documentación.

**Excluye:** cambios de código PHP (modelos, relaciones), esquema, datos, configuración o credenciales de PostgreSQL; cualquier otro apartado de documentación.

**Prohibiciones respetadas:** no se ejecutaron seeders, `db:seed`, importadores ni migraciones; no hubo escrituras en PostgreSQL; no se modificó código PHP; no se borraron ni modificaron respaldos; **no** hubo commit ni push.

## 3. Estado inicial

`PROJECT_CONTEXT.md` (§ «MVP Blueprints», línea 54) afirmaba:

> `blueprint_components.component_id` → `blueprints.id` como FK recursiva.

Esta afirmación quedó señalada como observación pendiente en el hito anterior (`docs/reports/2026-10-26_ep3_ec1_database_seeder_context.md`, §7), fuera del alcance de EC-1.

## 4. Archivos modificados

| Archivo | Acción | Motivo |
|---|---|---|
| `PROJECT_CONTEXT.md` | Modificado | Corregir la línea 54 (relación de la FK) |
| `docs/reports/2026-10-26_correccion_documental_fk_blueprint_components.md` | Creado | Informe de la tarea |
| `AI_WORK_LOG.md` | Modificado | Registrar el hito, evidencia y ruta del informe |

## 5. Verificación (solo lectura)

Tres fuentes independientes confirman la relación real:

### 5.1 Migración original `2026_08_28_100010_create_blueprint_components_table.php`

`component_id` se creó inicialmente apuntando a `blueprints` ([líneas 18–20](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_08_28_100010_create_blueprint_components_table.php#L18-L20)):

```php
$table->foreignId('component_id')
    ->constrained('blueprints')
    ->restrictOnDelete();
```

### 5.2 Migración `2026_10_14_000002_change_blueprint_components_component_id_foreign.php`

Reapunta la FK a `crafting_materials` ([líneas 10–20](file:///home/draven/Documentos/shop-titans-api/src/database/migrations/2026_10_14_000002_change_blueprint_components_component_id_foreign.php#L10-L20)):

```php
Schema::table('blueprint_components', function (Blueprint $table) {
    $table->dropForeign(['component_id']);

    $table->foreign('component_id')
        ->references('id')
        ->on('crafting_materials')
        ->restrictOnDelete();
});
```

El `down()` revierte a `blueprints.id` y solo es reversible si no existen componentes que referencien materiales no-`blueprint` (quest_component/sigil/enchantment).

### 5.3 Modelos Eloquent

- [BlueprintComponent.php](file:///home/draven/Documentos/shop-titans-api/src/app/Models/BlueprintComponent.php#L29-L32): `component()` → `belongsTo(CraftingMaterial::class, 'component_id')`.
- [CraftingMaterial.php](file:///home/draven/Documentos/shop-titans-api/src/app/Models/CraftingMaterial.php#L40-L43): `blueprintComponents()` → `hasMany(BlueprintComponent::class, 'component_id')`.

**Conclusión:** la tabla/columna referenciada por la FK real es **`crafting_materials.id`** (con `restrictOnDelete`). La afirmación de `PROJECT_CONTEXT.md` era **obsoleta**.

## 6. Hipótesis verificadas

| Hipótesis | Evidencia | Resultado |
|---|---|---|
| `component_id` referencia `crafting_materials.id` (estado actual) | Migración `2026_10_14_000002` (`up()`) + modelos (§5.2/§5.3) | Confirmada |
| `component_id` referenciaba `blueprints.id` **antes** de esa migración | Migración original `2026_08_28_100010` (§5.1) | Confirmada |
| `PROJECT_CONTEXT.md` describía un estado obsoleto | Contraste con §5.1–§5.3 | Confirmada |

## 7. Discrepancias

- **Objeto de esta tarea (resuelta):** `PROJECT_CONTEXT.md:54` afirmaba `component_id → blueprints.id`; el estado real es `crafting_materials.id`. Corregido (§8).
- No se detectaron otras discrepancias sobre `blueprint_components.component_id`. No quedaron discrepancias irresolubles por inspección.

## 8. Cambio realizado

Única línea modificada en `PROJECT_CONTEXT.md` (§ «MVP Blueprints», línea 54):

```diff
-`blueprint_components.component_id` → `blueprints.id` como FK recursiva.
+`blueprint_components.component_id` → `crafting_materials.id` (FK, `restrictOnDelete`); antes apuntaba a `blueprints.id` (migración `2026_10_14_000002`).
```

No se modificó ningún otro apartado del documento. La mención a la migración se añade como trazabilidad verificable.

## 9. Validaciones

- **Inspección de solo lectura** de las migraciones y modelos citados en §5 (evidencia directa).
- **Inspección del diff** (`git diff -- PROJECT_CONTEXT.md`) para confirmar el cambio exacto.
- No se ejecutaron seeders, `db:seed`, importadores ni migraciones. No se ejecutaron pruebas que alteren la base de datos (no procede: el cambio es exclusivamente documental).

Resultado del diff (extracto):

```diff
-`blueprint_components.component_id` → `blueprints.id` como FK recursiva.
+`blueprint_components.component_id` → `crafting_materials.id` (FK, `restrictOnDelete`); antes apuntaba a `blueprints.id` (migración `2026_10_14_000002`).
```

## 10. Resultado final

- **FK confirmada:** `blueprint_components.component_id` → **`crafting_materials.id`** (`restrictOnDelete`), según la migración `2026_10_14_000002` y los modelos `BlueprintComponent::component()` y `CraftingMaterial::blueprintComponents()`.
- **Documentación corregida:** `PROJECT_CONTEXT.md:54` describe ahora la relación verificada y deja constancia del cambio de destino (antes `blueprints.id`).
- **Sin cambios de código ni de base de datos.**

## 11. Riesgos

| # | Riesgo | Mitigación |
|---|---|---|
| R-1 | Que existan otras referencias documentales a la FK antigua | Búsqueda acotada: no se hallaron otras referencias al `component_id` → `blueprints.id` en la documentación revisada |
| R-2 | Confusión con `crafting_materials.blueprint_id` (que sí apunta a `blueprints.id`, nullable) | La corrección distingue ambas relaciones |

## 12. Pendientes

- Aprobación humana de H-1..H-4 (§17.5 del informe de revisión de la API).
- No quedan discrepancias documentales conocidas sobre `blueprint_components.component_id`.

## 13. Preguntas para ChatGPT / aprobación humana

1. ¿Se aprueba la corrección de `PROJECT_CONTEXT.md:54`?

## 14. Próximo paso recomendado

Revisar y aprobar la corrección. No se requieren acciones adicionales derivadas de esta tarea.
