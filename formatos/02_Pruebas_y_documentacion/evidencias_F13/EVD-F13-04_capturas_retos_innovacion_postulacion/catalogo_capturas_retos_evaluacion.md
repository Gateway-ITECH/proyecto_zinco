# EVD-F13-04: Catálogo Descriptivo de Capturas - Ciclo de Retos de Innovación
**Módulo:** Publicación, Postulación y Evaluación de Retos de Innovación Abierta  
**Ambiente Verificado:** Staging / Producción ZINCO  
**Versión de Línea Base:** 1.0.0-PROD  

---

### Ficha de Captura CAP-08: Catálogo y Ficha de Detalle de Reto de Innovación
- **Ruta:** `/retos` y `/retos/{id}`
- **Descripción Visual:** Catálogo con etiquetas de estado visuales ("Abierto a Postulaciones" en verde, "En Evaluación" en amarillo, "Cerrado" en gris). La ficha de detalle desglosa el planteamiento del problema, entidad organizadora, cronograma de hitos, criterios de evaluación porcentuales, premios o incentivos, y botón destacado "Postular mi Solución".

### Ficha de Captura CAP-09: Formulario de Radicación de Solución Técnica
- **Ruta:** `/retos/{id}/postular`
- **Descripción Visual:** Formulario estructurado para el postulante que solicita: Título de la propuesta, Resumen ejecutivo, Justificación técnica, Metodología de implementación, Cronograma tentativo, Presupuesto estimado, Equipo ejecutor y componente de carga de anexos técnicos en formato PDF (máx. 25 MB). Incluye casilla obligatoria de aceptación de términos de propiedad intelectual y no divulgación de información no autorizada.

### Ficha de Captura CAP-10: Panel de Evaluación Técnica de Postulaciones
- **Ruta:** `/retos/evaluar` y `/retos/calificar/{id}` (restringido al rol `evaluador`)
- **Descripción Visual:** Bandeja de entrada del evaluador que lista las soluciones asignadas pendientes de calificación. La interfaz de evaluación presenta un visor de la propuesta y soportes en la mitad izquierda, y una rúbrica estructurada de calificación en la mitad derecha con deslizadores numéricos (0 a 100) en criterios como: Pertinencia, Nivel de Madurez Tecnológica (TRL), Viabilidad Económica e Impacto Regional, junto a un campo de concepto técnico obligatorio.
