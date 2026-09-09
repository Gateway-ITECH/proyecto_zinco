# Carpeta de Evidencias de Copias de Seguridad - Formato F25
**Plataforma Zinco — Ecosistema de Ciencia, Tecnología e Innovación de Córdoba (CTeI)**  
**Alineación**: Acuerdo 062 de 2021 UNICOR / ISO/IEC 27001:2022 (Control A.8.13)  

---

## 1. Propósito
La presente carpeta almacena los procedimientos operativos de respaldo, scripts de automatización, bitácoras de ejecución de la línea base, motores de restauración y reportes de simulacro que sustentan el documento oficial **[`F25_Plan y registro de copias de seguridad.docx`](../F25_Plan%20y%20registro%20de%20copias%20de%20seguridad.docx)**.

## 2. Estructura de Evidencias
1. **`EVD-F25-01_politica_procedimiento_respaldo/`**:
   - `politica_procedimiento_copias_seguridad.md`: Procedimiento estándar (SOP-BKP-01) y estrategia 3-2-1.
2. **`EVD-F25-02_script_motor_respaldo_zinco/`**:
   - `zinco-backup-engine.sh`: Script operativo completo de respaldo automatizado de MariaDB, archivos y sync Azure.
3. **`EVD-F25-03_bitacora_ejecucion_respaldos/`**:
   - `backup_execution_log.txt`: Bitácora detallada de la ejecución de respaldo de línea base formal.
4. **`EVD-F25-04_script_restauracion_verificacion/`**:
   - `zinco-restore-engine.sh`: Script operativo de restauración, verificación de integridad y recarga de cachés.
5. **`EVD-F25-05_simulacro_restauracion_staging/`**:
   - `reporte_simulacro_restauracion_staging.txt`: Reporte técnico del simulacro en Staging (38 minutos, 100% exitoso).
6. **`EVD-F25-06_retencion_inmutabilidad_azure/`**:
   - `politica_retencion_inmutabilidad_azure.md`: Configuración de retención a 30/365 días, políticas WORM y depuración segura.

## 3. Integridad Criptográfica
Todas las evidencias cuentan con sumas de verificación criptográficas registradas en [`MANIFEST_HASHES_SHA256.txt`](./MANIFEST_HASHES_SHA256.txt).
