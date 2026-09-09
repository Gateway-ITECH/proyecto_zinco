# EVD-F14-06: Protocolo de Respuesta a Incidentes y Contingencia Operativa
**Procedimiento:** Gestión de Incidentes de Seguridad y Continuidad Operativa  
**Entidad:** Universidad de Córdoba / Gateway IT S.A.S.  
**Versión de Línea Base:** 1.0.0-PROD  

---

## 1. Matriz de Severidad de Incidentes

| Severidad | Criterio de Activación | Tiempo Máximo de Respuesta | Acciones Inmediatas |
| :--- | :--- | :--- | :--- |
| **Severidad 1 (Crítica)** | Indisponibilidad total del portal, ataque cibernético confirmado o pérdida de integridad de base de datos. | 30 minutos | Aislamiento de red, activación de modo de mantenimiento (`drush state:set system.maintenance_mode 1`), notificación a Dirección TIC y activación de plan de recuperación DR. |
| **Severidad 2 (Alta)** | Falla en módulo principal (Retos o Actores) que impide transacciones a múltiples usuarios. | 2 horas | Diagnóstico con logs del sistema, contención de componente afectado y despliegue de parche correctivo en Staging. |
| **Severidad 3 (Media)** | Degradación de rendimiento o fallas en tareas secundarias (ETL ScienTI o reportes). | 8 horas | Análisis de colas, optimización de consultas en MariaDB y reprogramación de ejecución. |
| **Severidad 4 (Baja)** | Errores menores de interfaz gráfica o desalineación visual que no impiden la operación. | 48 horas | Registro en backlog de mantenimiento para el siguiente ciclo programado. |

---

## 2. Procedimiento Forense y Preservación de Evidencia
1. Preservación del estado de la memoria y logs del servidor (`/var/log/apache2/`, `/var/log/syslog`, `/admin/reports/dblog`).
2. Cálculo de sumas SHA-256 de archivos sospechosos antes de cualquier modificación.
3. Elaboración de Informe Técnico Post-Mortem dentro de los 3 días hábiles posteriores a la estabilización del incidente, detallando: Causa Raíz, Línea de Tiempo, Impacto Cuantificado y Acciones Preventivas Definitivas.
