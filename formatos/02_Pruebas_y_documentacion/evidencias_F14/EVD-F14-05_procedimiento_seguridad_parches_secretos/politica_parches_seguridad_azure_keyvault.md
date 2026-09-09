# EVD-F14-05: Procedimiento de Seguridad, Gestión de Parches y Secretos
**Estándares de Referencia:** NIST SP 800-40 Rev. 4 / ISO/IEC 27001:2022  
**Entorno:** Ubuntu 24.04 LTS, Drupal 11.3.3, Azure Key Vault  

---

## 1. Política de Gestión y Aplicación de Parches
- **Boletines Oficiales:** Suscripción del equipo de infraestructura a los boletines de seguridad *Drupal Security Advisories* (`security.drupal.org`).
- **Niveles de Servicio para Remediación:**
  - *Crítica (Critical):* Máximo 48 horas tras la liberación del parche.
  - *Alta (High):* Máximo 7 días calendario.
  - *Media / Baja (Moderada/Low):* Incorporación en la siguiente ventana mensual programada.
- **Flujo de Aplicación:**
  1. Descarga y actualización en ambiente Staging con `composer update drupal/core-* --with-dependencies`.
  2. Ejecución de suite de pruebas automáticas y verificación de no regresión.
  3. Ejecución de `composer audit` verificando cero vulnerabilidades abiertas.
  4. Despliegue en producción durante ventana de mantenimiento autorizada.

---

## 2. Custodia de Secretos y Variables de Entorno
- **Azure Key Vault:** Almacenamiento centralizado de cadenas de conexión a base de datos, contraseñas de servicio SMTP, llaves de API externas y secretos de Drupal (`hash_salt`).
- **Cero Secretos en Repositorio:** Prohibición absoluta de incluir contraseñas o tokens en archivos `.yml`, `.php` o commits de Git.
- **Configuración Local:** Archivo `/var/www/html/proyecto_zinco/.env.production` con permisos estrictos `chmod 400` y propietario `www-data:www-data`.
