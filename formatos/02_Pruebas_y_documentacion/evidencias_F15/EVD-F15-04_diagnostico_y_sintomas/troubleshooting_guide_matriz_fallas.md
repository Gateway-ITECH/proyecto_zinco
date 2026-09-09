# Matriz de Diagnostico y Resolucion Rapida de Incidentes (Troubleshooting)
**Plataforma Zinco - Runbook de Operacion F15**  

---

| Sintoma / Alerta | Causa Raiz Probable | Procedimiento de Diagnostico | Accion Correctiva Inmediata | Nivel de Escalamiento |
|---|---|---|---|---|
| **Error HTTP 500** ("The website encountered an unexpected error") | Excepcion no capturada en modulo custom o cache corrupta. | 1. Revisar `/var/log/apache2/zinco_error.log`<br>2. Ejecutar `drush ws --severity=Error --count=10` | 1. Limpiar caches con `drush cr`<br>2. Si persiste, desactivar modulo afectado con `drush pmu <modulo>` temporalmente. | Nivel 2 (DevOps Gateway IT) |
| **Error "Database connection failed"** / Error 2002 | MariaDB detenido, socket no responde o limite de conexiones alcanzado. | 1. `systemctl status mariadb`<br>2. Verificar logs en `/var/log/mysql/error.log`<br>3. `mysqladmin -u root -p ping` | 1. Reiniciar servicio: `systemctl restart mariadb`<br>2. Si se agotaron conexiones, aumentar `max_connections = 250` en `/etc/mysql/my.cnf`. | Nivel 2 (DBA UNICOR) |
| **Fallo en carga/descarga de adjuntos en Azure Blob** | Token SAS caducado, corte de red saliente 443 o cuota excedida. | 1. Revisar `/var/log/zinco/azure_storage.log`<br>2. Test curl: `curl -I https://zinco.blob.core.windows.net` | 1. Regenerar SAS token o validar Connection String en Azure Key Vault.<br>2. Validar salida firewall UFW/Gateway. | Nivel 2 (SysAdmin UNICOR) |
| **Agotamiento de memoria PHP** ("Allowed memory size exhausted") | Ingesta masiva ScienTI o generacion de reporte PDF muy pesado. | 1. Revisar traza en `/var/log/php8.4-fpm.log`<br>2. Verificar consumo con `top` / `htop` | 1. Aumentar temporalmente `memory_limit = 512M` en `/etc/php/8.4/fpm/php.ini`<br>2. `systemctl reload php8.4-fpm`<br>3. Procesar ingesta en lotes menores (`drush queue:run`). | Nivel 1 -> Nivel 2 |
| **Fallo de sincronizacion ETL GrupLAC / ScienTI** | Portal Minciencias temporalmente fuera de linea o cambio en DOM HTML. | 1. Revisar `/var/log/zinco/etl_scraped.log`<br>2. Probar acceso manual al endpoint ScienTI. | 1. Reprogramar ejecucion con `drush zinco-etl:retry-failed`<br>2. Notificar a Gateway IT para ajuste de parser selector CSS si cambio el DOM. | Nivel 3 (Gateway IT) |
