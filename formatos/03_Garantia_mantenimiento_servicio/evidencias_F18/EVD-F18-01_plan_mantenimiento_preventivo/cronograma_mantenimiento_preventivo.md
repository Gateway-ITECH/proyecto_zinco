# Cronograma y Protocolo de Mantenimiento Preventivo de Software
**Plataforma Zinco - Ecosistema CTeI Universidad de Córdoba**  
**Alineación**: ISO/IEC/IEEE 14764:2022 | Contrato BPIN 2023000100045  

---

## 1. Alcance y Objetivos Preventivos
El mantenimiento preventivo comprende las tareas proactivas destinadas a mitigar la degradación del rendimiento, depurar datos transitorios, optimizar los índices relacionales y asegurar la estabilidad de la plataforma sin interrupción no programada del servicio.

## 2. Calendario de Tareas Preventivas

| Frecuencia | Tarea / Procedimiento | Comando / Herramienta | Umbral de Disparo | Responsable |
|---|---|---|---|---|
| **Semanal** | Depuración de cachés temporales de Drupal y sesiones inactivas | `drush cache:rebuild` / `drush cron` | Tabla `cache_*` > 500 MB | DevOps Gateway IT |
| **Semanal** | Rotación y compresión de logs web y de aplicación | `logrotate -f /etc/logrotate.d/zinco` | Logs > 100 MB | SysAdmin UNICOR |
| **Mensual** | Optimización de tablas e índices en MariaDB | `mysqlcheck -o -u root -p zinco_db` | Fragmentación > 15% | DBA UNICOR |
| **Mensual** | Depuración de registros antiguos en tabla `watchdog` | `drush watchdog:delete --severity=Notice` | Registros > 10,000 filas | DevOps Gateway IT |
| **Mensual** | Verificación de espacio libre e inodos en `/var` | `df -h /var && df -i /var` | Ocupación > 75% | SysAdmin UNICOR |
| **Bimensual** | Prueba de renovación de certificados TLS Let's Encrypt | `certbot renew --dry-run` | Vencimiento < 30 días | Seguridad TI UNICOR |
| **Semestral** | Simulacro de restauración de respaldo en Staging | `/usr/local/bin/zinco-restore.sh` | Semestral mandatorio | Equipo Conjunto |

## 3. Ventana de Ejecución Preventiva
Las tareas que requieran reinicio de servicios o bloqueo temporal se ejecutarán en la ventana oficial pactada: **Martes o Jueves entre 21:00 y 23:00 UTC-5**.
