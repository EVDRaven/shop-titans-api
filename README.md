# Shop Titans API

API para consultar datos estructurados de Shop Titans.

## Stack
Laravel · PHP 8.4 · PostgreSQL 17 · Docker Compose · Git

## Flujo
`Excel → BlueprintImporter → BlueprintDTO → Validación/transformación → PostgreSQL → Laravel API`

## Estado
- Infraestructura: ✅
- Laravel → PostgreSQL: ✅
- Análisis Excel: ✅
- Modelo Blueprints: ✅
- Migraciones: ✅
- PostgreSQL: ✅
- Seeders: ✅
- BlueprintImporter: ✅
- API: ✅
- Tests: ✅

## Preparación de datos (MVP de Blueprints)
Comando oficial:

```bash
php artisan db:seed --class="Database\\Seeders\\BlueprintMvpSeeder"
```

En este repositorio (Docker) se ejecuta dentro del contenedor de la aplicación, por ejemplo:

```bash
docker compose exec app php artisan db:seed --class="Database\\Seeders\\BlueprintMvpSeeder"
```

Este seeder prepara el conjunto de datos del MVP de Blueprints ejecutando, en este orden:

1. Seeders de catálogos (recursos, calidades, categorías, workers, niveles, elementos, encantamientos, afinidades y espíritus).
2. Importación de Blueprints (`import:blueprints`).
3. Materiales de crafteo (`CraftingMaterialSeeder`).
4. Importación de componentes (`import:blueprint-components`).

Requisito: el archivo Excel de Shop Titans debe estar en `storage/app/` (es decir, `src/storage/app/`).
Por defecto se busca el archivo `Shop Titans Data Spreadsheet _ c_ v26.9.1 _ v1.0.9.1332.xlsx`; si no existe, se toma el primer `*.xlsx` de esa carpeta. Si no se encuentra ninguno, el proceso se detiene con error antes de importar.

Alcance: este procedimiento prepara el **MVP de Blueprints**; no constituye la instalación completa de todos los datos de la aplicación. Los seeders individuales siguen disponibles para tareas específicas y mantenimiento.

## Acceso HTTP (desarrollo)
La API queda disponible en `http://127.0.0.1:8000` (accesible únicamente desde el equipo local).

Iniciar el servicio:

```bash
docker compose up -d app
```

Comprobar el acceso:

```bash
curl http://127.0.0.1:8000/api/v1/blueprints
```

Consultar los registros del contenedor:

```bash
docker compose logs -f app
```

`php artisan serve` es un servidor destinado al desarrollo, no a producción.

## Documentación
`PROJECT_CONTEXT.md` · `AI_EDITOR_INSTRUCTIONS.md` · `AI_WORK_LOG.md` · `CHANGELOG.md` · `CONTRIBUTING.md` · `docs/reports/` · `docs/adr/`

## Regla
Una propuesta no se convierte en decisión oficial sin aprobación.
