# Procedimiento Operativo Estándar (SOP): Gestión y Atención de Incidentes
**Plataforma Zinco - Ecosistema CTeI Universidad de Córdoba**  
**Código**: SOP-IT-ZINCO-01 | **Versión**: 1.0.0-PROD | **Fecha**: 2026-03-09  

---

## 1. Objetivo y Alcance
Estandarizar el ciclo de vida de los incidentes y solicitudes de soporte técnico sobre la Plataforma Zinco durante la etapa de garantía y operación pos-entrega, asegurando el cumplimiento de los tiempos de respuesta y solución acordados en el SLA.

## 2. Flujo de Atención de Incidentes

```
[Usuario / Alerta Monitoreo]
             │
             ▼
[Canal Oficial: Jira / Correo soporte.zinco@gatewayit.co]
             │
             ▼
[Nivel 1: Triaje y Clasificación de Severidad (<= 30 min)]
   ├── Sev 1 (Crítica) ────────► [Notificación Inmediata N3 + Teléfono de Guardia]
   └── Sev 2, 3, 4     ────────► [Asignación a Ingeniero de Soporte N2]
             │
             ▼
[Diagnóstico y Mitigación Temporal (Workaround)]
             │
             ▼
[Desarrollo de Parche en Rama Git / Prueba en Staging]
             │
             ▼
[Validación Funcional y Aprobación de Supervisión UNICOR]
             │
             ▼
[Despliegue Asistido en Producción (Ventana Nocturna / Inmediata si Sev 1)]
             │
             ▼
[Cierre Formal de Ticket + Entrega de RCA si Sev 1]
```

## 3. Registro y Atributos Obligatorios del Ticket
Todo ticket debe contener:
- **ID Único**: Formato `ZINCO-INC-YYYY-XXXX` o `ZINCO-REQ-YYYY-XXXX`.
- **Módulo Afectado**: Uno de los 12 módulos funcionales o componente de infraestructura.
- **Severidad Inicial**: Asignada con base en el impacto operacional.
- **Evidencia Adjunta**: Captura de pantalla, URL exacta, log de error y pasos de reproducción.
- **Trazabilidad**: Timestamps de creación, primera respuesta, mitigación y cierre formal.
