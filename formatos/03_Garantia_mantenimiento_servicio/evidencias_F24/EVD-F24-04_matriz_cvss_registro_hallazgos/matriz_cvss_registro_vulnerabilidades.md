# Matriz de Evaluación CVSS v3.1 y Registro de Vulnerabilidades Remediadas
**Plataforma Zinco - Ecosistema CTeI Universidad de Córdoba**  

---

## 1. Registro de Vulnerabilidades Analizadas y Remediadas

| ID Hallazgo | Activo Afectado | Descripción de la Vulnerabilidad | Vector CVSS v3.1 | Score Base | Severidad | Acción de Remediación Aplicada | Estado Final |
|---|---|---|---|:---:|:---:|---|:---:|
| **VULN-ZINCO-01** | Servidor Web Apache / PHP-FPM | Divulgación de versión de software en cabeceras HTTP (`Server` y `X-Powered-By`). | `CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:L/I:N/A:N` | **5.3** | Media | Se configuró `ServerTokens Prod` y `expose_php = Off` en Apache y PHP-FPM. | **Remediado** |
| **VULN-ZINCO-02** | Configuración TLS / VirtualHost | Ausencia de cabecera estricta HSTS en respuestas HTTP de redirección inicial. | `CVSS:3.1/AV:N/AC:L/PR:N/UI:R/S:U/C:L/I:N/A:N` | **4.3** | Media | Se inyectó cabecera `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload`. | **Remediado** |
| **VULN-ZINCO-03** | Sistema de Archivos (`settings.php`) | Permisos de escritura residuales para usuario del servidor web en archivo de configuración. | `CVSS:3.1/AV:L/AC:L/PR:L/UI:N/S:U/C:L/I:L/A:N` | **3.8** | Baja | Se ajustaron permisos estrictos a `chmod 440` con propietario `www-data:www-data`. | **Remediado** |

## 2. Trazabilidad de Cierre
Todos los hallazgos identificados durante la fase de aseguramiento y pruebas de seguridad fueron remediados en el ambiente de Staging, verificados mediante escaneo dinámico DAST y consolidados en la línea base inmutable `v1.0.0-PROD`.
