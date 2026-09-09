# EVD-F14-02: Matriz de Roles, Permisos RBAC y Procedimientos IAM
**Sistema:** Plataforma ZINCO - Universidad de Córdoba  
**Versión de Línea Base:** 1.0.0-PROD  

---

## 1. Matriz Formal de Roles del Sistema

| Rol Drupal | Ámbito de Acción | Permisos Clave Asignados |
| :--- | :--- | :--- |
| **anonymous** | Visitante público no autenticado | `access content`, visualización de taxonomías públicas, consulta de dashboards y Asistente Virtual. Prohibida toda acción de creación, edición o administración. |
| **authenticated** | Usuario básico con credenciales verificadas | `access content`, gestión de preferencias de notificación, cambio de clave propia. |
| **actor_registrado** | Investigadores, Empresas y Entidades CTeI avaladas | `access content`, edición de perfil propio (`zinco_actors`), radicación de propuestas a retos (`zinco_retos_soluciones`), solicitud de reconocimientos y registro de necesidades. |
| **organizador_reto** | Entidades facultadas para convocar retos | Creación y parametrización de retos de innovación (`/retos/nuevo`), carga de TDRs y visualización de postulaciones radicadas. |
| **evaluador** | Expertos técnicos evaluadores | `access retos evaluacion`, acceso exclusivo a `/retos/evaluar` y calificación estructurada mediante rúbrica (0-100 pts). |
| **gestor_ctei** | Equipo técnico UNICOR / Dirección TIC | Moderación de actores, validación de necesidades, revisión de convocatorias y supervisión de indicadores. |
| **administrator** | Administrador global institucional | Control total del sistema (`administer site configuration`, `administer users`, `administer permissions`). |

---

## 2. Procedimientos IAM (Altas, Bajas y Modificaciones)
- **Alta de Cuenta Administrativa:** Requiere solicitud formal vía ticket SIGEC con aprobación de la Dirección TIC. Creación en `/admin/people/create` con correo institucional nominal (prohibido el uso de correos genéricos compartidos).
- **Modificación de Privilegios:** Aplicación estricta del principio de mínimo privilegio. La elevación temporal de privilegios debe registrar fecha de expiración y reversión en bitácora.
- **Baja / Desactivación:** Ante retiro o desvinculación, bloqueo inmediato del usuario en `/admin/people` marcando estado 'Bloqueado'. Se preserva la cuenta para mantener la integridad referencial histórica de auditoría.
- **Revisión Periódica:** Auditoría trimestral de la lista de cuentas con roles `administrator` y `gestor_ctei`.
