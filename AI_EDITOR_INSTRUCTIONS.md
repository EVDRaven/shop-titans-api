# AI Editor Instructions — Protocolo v2.1

## Rol
Implementador técnico del proyecto. ChatGPT actúa principalmente como arquitecto/revisor y el Usuario mantiene la decisión final.

## Antes de cualquier tarea
Lee:
1. `PROJECT_CONTEXT.md`
2. `README.md`
3. `AI_WORK_LOG.md`
4. reportes relacionados
5. código real
6. estado de Git

No asumas que la documentación coincide con el código.

## Verificación de hipótesis
Antes de convertir una suposición sobre datos externos en una FK, enum, restricción, clasificación, relación o lógica del importer, verifica los datos reales cuando sea posible.

Si falla, documenta hipótesis, evidencia, conclusión, impacto y propuesta.

## Alcance
Trabaja únicamente en la tarea solicitada. No adelantes módulos futuros ni agregues complejidad sin necesidad.

## Estados
Usa:
- PROPUESTA
- EN IMPLEMENTACIÓN
- IMPLEMENTADA
- PENDIENTE DE REVISIÓN
- APROBADA
- EJECUTADA
- VALIDADA
- BLOQUEADA

No confundas aprobación de implementación con autorización de ejecución.

## Discrepancias
Si documentación, código, BD o fuente de datos difieren, registra:

```text
DISCREPANCIA
Estado documentado:
Estado real:
Evidencia:
Impacto:
Propuesta:
Requiere aprobación: Sí/No
```

No ocultes ni resuelvas silenciosamente discrepancias arquitectónicas.

## Reporte obligatorio
Toda tarea técnica significativa genera `docs/reports/YYYY-MM-DD-nombre-corto.md`.

Debe incluir:
1. Objetivo
2. Alcance
3. Estado inicial
4. Archivos modificados
5. Cambios realizados
6. Hipótesis verificadas
7. Discrepancias
8. Decisiones propuestas
9. Decisiones aprobadas
10. Validaciones/comandos/exit codes
11. Resultado
12. Riesgos
13. Pendientes
14. Preguntas para ChatGPT
15. Próximo paso

Usa `docs/reports/TEMPLATE_REPORT.md`.

## Bitácora
Al terminar, actualiza `AI_WORK_LOG.md`. Debe ser breve; el detalle pertenece a los reportes.

## ADR
Crear ADR cuando una decisión tenga impacto arquitectónico duradero. No marcarla como aceptada sin aprobación.

## Changelog
Actualizar `CHANGELOG.md` solo para cambios aprobados/integrados.

## Operaciones sensibles
Para migraciones reales, eliminación de datos, deploys u operaciones irreversibles:
- confirmar autorización explícita;
- documentar comando y resultado;
- detenerse si la siguiente acción requiere decisión.

## Entrega
Informar estado, cambios, archivos, validaciones, resultado, riesgos, pendientes, preguntas y próximo paso. No avanzar automáticamente a una tarea que requiera aprobación.
