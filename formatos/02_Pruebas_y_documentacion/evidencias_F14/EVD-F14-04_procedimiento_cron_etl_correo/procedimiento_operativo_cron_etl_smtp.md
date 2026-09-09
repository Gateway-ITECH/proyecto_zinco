# EVD-F14-04: Procedimiento Operativo - Cron, ETL y Transporte SMTP
**Servicios:** Tareas Automáticas, Conector ScienTI / GrupLAC y Correo Transaccional  
**Versión de Línea Base:** 1.0.0-PROD  

---

## 1. Arquitectura y Ejecución de Tareas Periódicas (Cron)
- **Timer de Sistema:** `/etc/systemd/system/zinco-cron.timer` y servicio asociado `zinco-cron.service`.
- **Frecuencia:** Ejecución automática cada 60 minutos (horario exacto al minuto :00).
- **Comando Ejecutado:** `/usr/local/bin/drush -r /var/www/html/proyecto_zinco/web core:cron`
- **Monitoreo:** Verificación mediante `systemctl status zinco-cron.service` y en `/admin/reports/status`.

---

## 2. Proceso ETL de Interoperabilidad Minciencias ScienTI / GrupLAC
- **Módulo Responsable:** `zinco_etl` y módulo scraper `gruplac_scraper`.
- **Frecuencia de Sincronización:** Semanal (domingos a las 01:00 UTC-5).
- **Controles de Concurrencia:** Ejecución asíncrona mediante Drupal Queue API con límite de 5 peticiones por minuto para no saturar servidores gubernamentales externos.
- **Mecanismo de Resiliencia:** Si el servicio de Minciencias no responde tras 3 intentos con backoff exponencial, se conserva la información histórica en base de datos local y se genera alerta de severidad Media en el Watchdog.

---

## 3. Configuración del Transporte de Correo Institucional (SMTP)
- **Módulo Operativo:** `drupal/smtp` (versión 8.x-1.3+).
- **Servidor Saliente:** `smtp.office365.com` (o relay institucional `smtp.unicordoba.edu.co`).
- **Puerto y Cifrado:** Puerto 587 con protocolo STARTTLS.
- **Dirección Remitente Oficial:** `notificaciones-zinco@unicordoba.edu.co`
- **Nombre Mostrado:** *Plataforma ZINCO - Ecosistema CTeI Córdoba*
- **Autenticación:** Credenciales institucionales protegidas en variables de entorno `.env.production`.
- **Comprobación de Entrega:** Registro de traza en `/admin/reports/dblog` tipo `smtp` y pruebas con el botón "Enviar correo de prueba" en `/admin/config/system/smtp`.
