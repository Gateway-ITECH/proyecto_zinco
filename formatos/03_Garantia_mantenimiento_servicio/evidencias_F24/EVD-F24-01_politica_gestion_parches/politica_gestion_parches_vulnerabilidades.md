# Política y Procedimiento de Gestión de Parches y Vulnerabilidades
**Plataforma Zinco - Ecosistema CTeI Universidad de Córdoba**  
**Código**: SOP-SEC-PATCH-01 | **Alineación**: NIST SP 800-40 Rev. 4 / ISO/IEC 27001:2022  

---

## 1. Objetivo
Establecer un proceso estructurado, sistemático y auditable para identificar, evaluar, priorizar, remediar y verificar vulnerabilidades de seguridad en el software, dependencias e infraestructura que soportan la Plataforma Zinco.

## 2. Flujo del Ciclo de Gestión de Vulnerabilidades
```
[Detección: Scanner / Advisories / Pentest]
                    │
                    ▼
[Triaje y Clasificación de Severidad (CVSS v3.1)]
                    │
                    ▼
[Evaluación de Impacto y Explotabilidad en Zinco]
                    │
                    ▼
[Desarrollo de Parche / Hardening en Staging]
                    │
                    ▼
[Pruebas de Seguridad + Pruebas de No Regresión]
                    │
                    ▼
[Despliegue Asistido en Producción (Ventana SLA)]
                    │
                    ▼
[Reescaneo de Verificación + Cierre Formal]
```

## 3. Matriz de Severidad y Plazos de Remediación (SLA de Seguridad)
- **Crítica (CVSS 9.0 - 10.0 / SA-CORE Crítico)**: Remediación en producción en un tiempo **<= 72 horas**.
- **Alta (CVSS 7.0 - 8.9)**: Remediación en producción en un tiempo **<= 7 días hábiles**.
- **Media (CVSS 4.0 - 6.9)**: Remediación en producción en un tiempo **<= 15 días hábiles**.
- **Baja (CVSS < 4.0)**: Remediación en el siguiente ciclo mensual programado.
