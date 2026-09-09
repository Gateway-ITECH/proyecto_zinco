# Carpeta de Evidencias Técnicas y Operativas - Formato F15 (Runbook de Operación)
**Plataforma Zinco — Ecosistema de Ciencia, Tecnología e Innovación de Córdoba (CTeI)**  
**Contrato / Proyecto**: BPIN 2023000100045 — Universidad de Córdoba & Gateway IT S.A.S.  

---

## 1. Propósito
La presente carpeta almacena el compendio completo de evidencias técnicas, scripts de soporte, reportes de estado, procedimientos de recuperación y configuraciones operacionales que respaldan el documento oficial **[`F15_Manual técnico - runbook de operación.docx`](../F15_Manual%20t%C3%A9cnico%20-%20runbook%20de%20operaci%C3%B3n.docx)**, garantizando la continuidad del servicio, el mantenimiento preventivo/correctivo y la soberanía operativa por parte del equipo de TI de la Universidad de Córdoba.

## 2. Estructura de Evidencias y Scripts
1. **`EVD-F15-01_verificacion_servicios/`**:
   - `servicios_status_report.txt`: Estado activo de los servicios `apache2`, `php8.4-fpm`, `mariadb` y Drupal 11.3.3.
   - `zinco-service-manager.sh`: Script ejecutable para start, stop, restart, status y healthcheck del stack Zinco.
2. **`EVD-F15-02_procedimiento_despliegue/`**:
   - `deploy_runbook_step_by_step.md`: Procedimiento detallado de despliegue paso a paso con pre-checks y validaciones.
   - `zinco-deploy.sh`: Script automatizado de despliegue en producción a partir de tags versionados en Git.
3. **`EVD-F15-03_scripts_backup_restore/`**:
   - `zinco-backup.sh`: Script operativo de respaldo de base de datos MariaDB, compresión y sincronización Azure.
   - `zinco-restore.sh`: Script de restauración y verificación de integridad de base de datos y archivos.
   - `drp_rto_rpo_contingencia.md`: Plan de recuperación de desastres (DRP) con métricas RTO 2 horas y RPO 6 horas.
4. **`EVD-F15-04_diagnostico_y_sintomas/`**:
   - `troubleshooting_guide_matriz_fallas.md`: Matriz de causas raíces, comandos y acciones de mitigación inmediata.
   - `zinco-healthcheck.sh`: Script de diagnóstico y sondeo sintético de salud de la plataforma.
5. **`EVD-F15-05_notificaciones_correo_smtp/`**:
   - `smtp_configuration_test_report.txt`: Configuración de transporte SMTP institucional y reporte de prueba transaccional.
   - `test-mail-zinco.sh`: Script para prueba sintética de despacho Drush mail.
6. **`EVD-F15-06_politicas_seguridad_certificados/`**:
   - `security_hardening_runbook.md`: Directrices de bastionado SSH Ed25519, Firewall UFW y variables de entorno.
   - `ssl_tls_renewal_verification.txt`: Verificación de validez de certificados TLS X.509 y automatización Certbot.

## 3. Integridad Criptográfica
Todas las evidencias cuentan con sumas de verificación criptográficas registradas en [`MANIFEST_HASHES_SHA256.txt`](./MANIFEST_HASHES_SHA256.txt).
