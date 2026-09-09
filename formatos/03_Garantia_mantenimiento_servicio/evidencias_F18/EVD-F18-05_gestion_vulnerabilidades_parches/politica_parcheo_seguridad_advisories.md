# Política de Gestión de Vulnerabilidades y Parcheo de Seguridad
**Plataforma Zinco - Ecosistema CTeI Universidad de Córdoba**  

---

## 1. Fuentes Oficiales de Avisos de Seguridad
- **Drupal Security Team Advisories**: Alertas publicadas en `https://www.drupal.org/security` (SA-CORE y SA-CONTRIB).
- **Composer Package Security**: Avisos de GitHub Advisory Database y Packagist audit.
- **Ubuntu Security Notices (USN)**: Vulnerabilidades en daemons `apache2`, `php8.4` y `mariadb-server`.

## 2. Clasificación de Severidad y SLAs de Remediación

| Criticidad de la Vulnerabilidad | Criterio CVSS v3 | Tiempo Máximo de Parcheo en Producción |
|---|---|---|
| **Crítica (Zero-Day / SA-CORE)** | CVSS >= 9.0 (Ejecución remota de código, SQLi no autenticada) | **<= 72 horas** |
| **Alta** | CVSS 7.0 - 8.9 (Escalamiento de privilegios, bypass de autenticación) | **<= 7 días hábiles** |
| **Media** | CVSS 4.0 - 6.9 (XSS reflejado, denegación parcial de servicio) | **<= 15 días hábiles** |
| **Baja** | CVSS < 4.0 (Divulgación menor de información en cabeceras) | Siguiente ventana mensual programada |

## 3. Protocolo de Aplicación de Parches
1. Notificación inmediata a la supervisión técnica de UNICOR.
2. Aplicación declarativa en `composer.json` mediante `cweagans/composer-patches` o actualización menor en Staging.
3. Ejecución de auditoría automatizada con `composer audit`.
4. Pruebas de regresión funcional.
5. Despliegue en producción con verificación post-parche.
