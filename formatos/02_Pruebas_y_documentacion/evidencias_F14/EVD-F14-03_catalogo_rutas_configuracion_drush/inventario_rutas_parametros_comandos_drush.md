# EVD-F14-03: Inventario de Rutas, Configuración y Comandos Drush
**Módulos Custom:** 16 paquetes `zinco_*`  
**Rutas Documentadas:** 78 rutas routing.yml  
**Entorno de Ejecución:** Drush 13.x / Drupal 11.3.3  

---

## 1. Comandos Drush de Operación Frecuente

| Comando Drush | Propósito Operativo | Frecuencia / Cuándo Usar |
| :--- | :--- | :--- |
| `drush cr` (o `cache:rebuild`) | Reconstrucción completa de la caché del sistema | Tras cambios en código, plantillas Twig, routing o exportación de configuración. |
| `drush core:cron` | Disparo manual de las tareas periódicas programadas | Para forzar el procesamiento de colas de correo o indexación de búsqueda. |
| `drush cim` (o `config:import`) | Importación de configuración versionada desde `config/sync/` | Durante despliegues en pipelines CI/CD tras merge a rama `main`. |
| `drush cex` (o `config:export`) | Exportación de configuración activa a archivos YAML | Antes de realizar commits de cambios funcionales en Drupal. |
| `drush updb` (o `updatedb`) | Aplicación de scripts de actualización de base de datos (`hook_update_N`) | Tras despliegue de nuevas versiones con cambios en esquemas de BD. |
| `drush uli <username>` | Generación de enlace de acceso de un solo uso para administrador | En situaciones de contingencia o recuperación de credenciales administrativas. |
| `drush state:set system.maintenance_mode 1` | Activación del modo de mantenimiento del portal | Previo a ventanas de despliegue o tareas mayores en base de datos. |
| `drush state:set system.maintenance_mode 0` | Desactivación del modo de mantenimiento | Al culminar satisfactoriamente una ventana de mantenimiento. |
| `drush watchdog:show --count=50 --severity=Error` | Consulta de los últimos 50 errores críticos en el log | Diagnóstico rápido de excepciones en tiempo de ejecución. |

---

## 2. Gestión de Vocabularios Taxonómicos (`/admin/structure/taxonomy`)
- `helices_ctei`: Cuádruple Hélice (Academia, Empresa, Estado, Sociedad Civil).
- `sectores_economicos`: Sectores departamentales (Agropecuario, Agroindustrial, Salud, Minero-Energético, Turismo, TIC).
- `municipios_cordoba`: 30 municipios de Córdoba indexados con código DANE.
- `categorias_actores`: Tipologías institucionales de actores CTeI.
- `estados_retos`: Ciclo de vida (Borrador, En Revisión, Publicado, En Evaluación, Adjudicado, Desierto, Cerrado).
