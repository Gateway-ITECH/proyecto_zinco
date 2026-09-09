# Carpeta de Evidencias Técnicas - Formato F18 (Plan de Mantenimiento de Software)
**Plataforma Zinco — Ecosistema de Ciencia, Tecnología e Innovación de Córdoba (CTeI)**  
**Alineación**: ISO/IEC/IEEE 14764:2022 | Contrato BPIN 2023000100045  

---

## 1. Propósito
La presente carpeta almacena los procedimientos técnicos, scripts automatizados, calendarios de mantenimiento preventivo, políticas de gestión de cambios adaptativos/evolutivos y reportes de auditoría de parches que sustentan el documento oficial **[`F18_Plan de mantenimiento de software.docx`](../F18_Plan%20de%20mantenimiento%20de%20software.docx)**.

## 2. Estructura de Evidencias
1. **`EVD-F18-01_plan_mantenimiento_preventivo/`**:
   - `cronograma_mantenimiento_preventivo.md`: Cronograma de tareas preventivas semanales, mensuales y semestrales.
   - `zinco-preventive-maintenance.sh`: Script operativo de mantenimiento preventivo y limpieza automatizada.
2. **`EVD-F18-02_procedimiento_mantenimiento_correctivo/`**:
   - `procedimiento_parcheo_correctivo.md`: Protocolo de corrección de defectos, flujo Git, pruebas en Staging y despliegue.
   - `flujo_atencion_defectos_diagrama.txt`: Diagrama de flujo de atención y cierre de incidentes correctivos.
3. **`EVD-F18-03_adaptativo_actualizaciones_entorno/`**:
   - `protocolo_mantenimiento_adaptativo.md`: Procedimiento de adaptación ante cambios en PHP 8.4, MariaDB 10.11 y Minciencias.
   - `matriz_compatibilidad_entorno.txt`: Matriz de compatibilidad y soporte de versiones del stack Zinco.
4. **`EVD-F18-04_politica_mantenimiento_evolutivo/`**:
   - `politica_gestion_cambios_evolutivos.md`: Marco de gestión de requerimientos evolutivos y delimitación con la garantía.
   - `formato_solicitud_cambio_rfc.md`: Formato estándar de Solicitud de Cambio (Request for Change - RFC).
5. **`EVD-F18-05_gestion_vulnerabilidades_parches/`**:
   - `politica_parcheo_seguridad_advisories.md`: Procedimiento de monitoreo de Drupal Advisories y SLAs de remediación.
   - `reporte_auditoria_composer_audit.txt`: Reporte de ejecución de `composer audit` (0 vulnerabilidades).
6. **`EVD-F18-06_metricas_calendario_mantenimiento/`**:
   - `tablero_control_metricas_mantenimiento.md`: Tablero de indicadores clave (Uptime, MTTR, parches y backups).
   - `calendario_anual_mantenimiento_2026_2027.txt`: Calendario operativo anualizado de mantenimiento preventivo.

## 3. Integridad Criptográfica
Todas las evidencias cuentan con sumas de verificación criptográficas registradas en [`MANIFEST_HASHES_SHA256.txt`](./MANIFEST_HASHES_SHA256.txt).
