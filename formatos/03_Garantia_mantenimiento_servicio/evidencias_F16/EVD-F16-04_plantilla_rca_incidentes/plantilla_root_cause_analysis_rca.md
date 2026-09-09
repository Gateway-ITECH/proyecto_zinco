# Plantilla de Análisis de Causa Raíz (Root Cause Analysis - RCA)
**Plataforma Zinco - Gestión de Incidentes Críticos**  
**Aplicabilidad**: Obligatorio para incidentes Severidad 1 y recurrentes Severidad 2.  

---

## 1. Resumen Ejecutivo del Incidente
- **Código de Ticket**: `ZINCO-INC-YYYY-XXXX`
- **Fecha y Hora de Inicio**: `AAAA-MM-DD HH:MM UTC-5`
- **Fecha y Hora de Detección**: `AAAA-MM-DD HH:MM UTC-5`
- **Fecha y Hora de Restauración**: `AAAA-MM-DD HH:MM UTC-5`
- **Tiempo Total de Afectación**: `X horas Y minutos`
- **Servicios Afectados**: [ ] Portal Web  [ ] MariaDB  [ ] Azure Blob  [ ] ScienTI ETL  [ ] SMTP
- **Impacto a Usuarios**: Descripción del número de usuarios o transacciones afectadas.

## 2. Cronología Detallada de Eventos
| Timestamp | Evento / Acción Tomada | Responsable |
|---|---|---|
| `HH:MM` | Detección inicial por alerta automática / ticket de usuario | Mesa de Ayuda |
| `HH:MM` | Confirmación de severidad y escalamiento a Nivel 3 | DevOps Gateway IT |
| `HH:MM` | Identificación del factor desencadenante | Ing. Core |
| `HH:MM` | Aplicación de mitigación inmediata (workaround) | DevOps |
| `HH:MM` | Verificación de servicio restablecido en producción | UNICOR Supervisión |
| `HH:MM` | Despliegue de parche definitivo | DevOps |

## 3. Metodología de los 5 Porqués (5 Whys)
1. *¿Por qué ocurrió la indisponibilidad?* -> [Respuesta 1]
2. *¿Por qué ocurrió lo anterior?* -> [Respuesta 2]
3. *¿Por qué falló el mecanismo preventivo?* -> [Respuesta 3]
4. *¿Por qué no se detectó en pruebas?* -> [Respuesta 4]
5. *¿Por qué (Causa Raíz Fundamental)?* -> [Causa Raíz Identificada]

## 4. Plan de Acciones Correctivas y Preventivas (CAPA)
| ID Acción | Descripción de la Acción Preventiva | Responsable | Fecha Límite | Estado |
|---|---|---|---|---|
| CAPA-01 | Ajuste de configuración o refactorización de código | Gateway IT | AAAA-MM-DD | Pendiente |
| CAPA-02 | Adición de prueba automatizada o regla de alerta | DevOps | AAAA-MM-DD | Pendiente |
