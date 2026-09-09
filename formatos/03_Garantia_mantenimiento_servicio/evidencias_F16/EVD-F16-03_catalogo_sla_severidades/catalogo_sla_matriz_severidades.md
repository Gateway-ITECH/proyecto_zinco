# Catálogo de Niveles de Servicio (SLA) y Matriz de Severidades
**Plataforma Zinco - Ecosistema CTeI Universidad de Córdoba**  

---

## 1. Matriz de Clasificación de Severidad

| Nivel de Severidad | Criterio de Impacto Operacional | Ejemplo Típico en Zinco |
|---|---|---|
| **Severidad 1 (Crítica / Bloqueante)** | Caída total de la plataforma, inoperatividad de MariaDB, fallo general de login, corrupción masiva de datos o vulnerabilidad crítica de seguridad activa. | Error 500 en todas las páginas, timeout general de BD, servidor caído. |
| **Severidad 2 (Alta / Mayor)** | Falla grave en un módulo misional sin procedimiento alterno (workaround) viable. Afecta a un grupo significativo de usuarios. | Fallo total en la postulación a retos de innovación, error en generación de todos los reportes PDF. |
| **Severidad 3 (Media / Menor)** | Falla funcional o error en un módulo pero con solución temporal viable. Afectación parcial. | Retraso en sincronización de un grupo ScienTI, filtro secundario en dashboard que no actualiza vía AJAX. |
| **Severidad 4 (Baja / Consulta)** | Consultas técnicas de operación, solicitudes de información o desalineaciones cosméticas menores de interfaz. | Consulta sobre formato de importación CSV, ajuste de tipografía o padding en pie de página. |

## 2. Compromisos de Tiempos de Atención y Resolución (SLA)

| Severidad | Tiempo Máximo de Primera Respuesta (MTTA) | Tiempo Máximo de Mitigación (Workaround) | Tiempo Máximo de Solución Definitiva (MTTR) | Cobertura Horaria |
|---|---|---|---|---|
| **Severidad 1** | **<= 30 minutos** | **<= 2 horas** | **<= 4 horas** | **24/7/365 (Guardia Activa)** |
| **Severidad 2** | **<= 2 horas** | **<= 6 horas** | **<= 24 horas** | L-V 07:00 a 18:00 UTC-5 |
| **Severidad 3** | **<= 4 horas** | **<= 24 horas** | **<= 72 horas** | L-V 07:00 a 18:00 UTC-5 |
| **Severidad 4** | **<= 8 horas** | **<= 48 horas** | **<= 5 días hábiles** | L-V 07:00 a 18:00 UTC-5 |

## 3. Metas Mensuales Consolidadas de Calidad de Servicio
- **Disponibilidad Mensual (Uptime)**: >= **99.5%** (tiempo fuera no programado mensual admisible <= 3.6 horas).
- **Cumplimiento Global de Tiempos SLA**: >= **95.0%** de los tickets resueltos dentro del tiempo objetivo.
