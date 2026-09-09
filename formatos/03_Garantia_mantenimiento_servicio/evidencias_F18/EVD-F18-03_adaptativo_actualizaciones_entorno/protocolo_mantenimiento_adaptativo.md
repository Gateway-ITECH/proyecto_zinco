# Protocolo de Mantenimiento Adaptativo ante Cambios de Entorno
**Plataforma Zinco - Ecosistema CTeI Universidad de Córdoba**  

---

## 1. Alcance y Disparadores Adaptativos
El mantenimiento adaptativo comprende las modificaciones necesarias para que la Plataforma Zinco continúe funcionando de forma óptima ante cambios exógenos en el entorno de ejecución:
1. **Actualizaciones de Plataforma Base**: Cambios menores en PHP (8.4.x), MariaDB (10.11.x) o librerías de sistema en Ubuntu 24.04 LTS.
2. **Cambios en APIs y Scrapers Externos**: Modificaciones en la estructura HTML o cabeceras de respuesta del portal Minciencias ScienTI / GrupLAC.
3. **Plataformas en la Nube**: Actualizaciones del SDK de Microsoft Azure Storage Blob o protocolos de cifrado TLS.

## 2. Procedimiento de Gestión de Cambios Adaptativos
1. **Detección Temprana**: Suscripción a los canales oficiales de release de PHP, MariaDB, Drupal y Minciencias.
2. **Evaluación de Compatibilidad**: Simulación en entorno de desarrollo contenerizado Lando con la nueva versión del componente.
3. **Ajuste de Código / Scrapers**: Modificación de selectores CSS en `gruplac_scraper`, redefinición de llamadas a funciones obsoletas o actualización de parámetros de conexión.
4. **Validación en Staging**: Ejecución continua de scraping y pruebas de navegación durante mínimo 48 horas en Staging.
5. **Paso a Producción Controlado**: Despliegue en ventana nocturna con plan de contingencia y rollback inmediato.
