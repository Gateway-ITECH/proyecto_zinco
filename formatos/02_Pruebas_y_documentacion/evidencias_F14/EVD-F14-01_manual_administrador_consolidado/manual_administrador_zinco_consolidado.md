# EVD-F14-01: Manual de Administrador Consolidado - Plataforma ZINCO
**Proyecto:** Fortalecimiento del Sistema Territorial de CTeI de Córdoba | BPIN 2023000100045  
**Entidad Beneficiaria:** Universidad de Córdoba  
**Proveedor Tecnológico:** Gateway IT S.A.S.  
**Versión de Línea Base:** 1.0.0-PROD  
**Fecha de Emisión:** 2026-03-09  

---

## 1. Alcance de la Administración del Sistema
El presente manual consolida las directrices técnicas, operativas y de seguridad para la administración de la **Plataforma ZINCO** (Ecosistema de Ciencia, Tecnología e Innovación de Córdoba). La administración comprende:
1. Gestión de Identidades y Acceso (IAM / RBAC).
2. Parametrización de Contenidos y Taxonomías Departamentales.
3. Automatización de Tareas del Sistema (Cron, Colas, ETL Minciencias).
4. Mantenimiento del Entorno Web y Base de Datos (Drupal 11.3.3, PHP 8.4, MariaDB 10.11).
5. Seguridad, Gestión de Parches y Custodia de Secretos.
6. Monitoreo, Respuesta a Incidentes y Planes de Contingencia.

## 2. Segregación de Funciones y Mínimo Privilegio
- **Administrador del Sistema (Drupal Admin):** Responsable de la gestión de usuarios, roles, tipos de contenido y moderación funcional en `/admin/zinco/*`.
- **Administrador de Infraestructura / DevOps (SysAdmin):** Responsable del sistema operativo Ubuntu 24.04, servidor Apache 2.4, motor MariaDB, túneles SSH sobre VPN institucional y pipelines CI/CD en Azure DevOps.
- **Auditor de Seguridad y Cumplimiento:** Rol consultivo con acceso a registros `/admin/reports/dblog`, auditorías de código `composer audit` y reportes forenses.

## 3. Principales Rutas de Gestión Administrativa
- `/admin/zinco/dashboard`: Panel centralizado de administración de ZINCO.
- `/admin/people`: Gestión de usuarios, asignación de roles y estados de cuenta.
- `/admin/people/permissions`: Matriz de permisos RBAC del sistema.
- `/admin/structure/taxonomy`: Gestión de vocabularios (Hélices, Sectores, Municipios, Categorías de Actores).
- `/admin/structure/types`: Definición de campos y visualización de tipos de contenido.
- `/admin/config/system/cron`: Configuración de periodicidad de tareas automáticas.
- `/admin/config/system/smtp`: Parametrización del transporte de correo institucional autenticado.
- `/admin/reports/status`: Informe de estado del entorno y salud de Drupal.
- `/admin/reports/dblog`: Visor de eventos del sistema (Watchdog).
