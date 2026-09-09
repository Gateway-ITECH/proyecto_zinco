# EVD-F13-06: Matriz de Resolución de Errores Frecuentes y Soporte Técnico
**Procedimiento:** Atención de Incidencias, Autoservicio Seguro y Mesa de Ayuda SIGEC  
**Entidad:** Universidad de Córdoba / Gateway IT S.A.S.  
**Versión de Línea Base:** 1.0.0-PROD  

---

## 1. Matriz de Errores Frecuentes y Acciones Seguras de Autoservicio

| Código / Mensaje | Causa Técnica Probable | Acción Segura Recomendada para el Usuario | Escalamiento |
| :--- | :--- | :--- | :--- |
| **Error 403 / Acceso Denegado** | El rol actual no posee permisos suficientes o la sesión expiró por inactividad. | Verificar que la sesión esté activa; si la función requiere rol especial (ej. evaluador o gestor), solicitar asignación al administrador institucional. | Mesa de Ayuda Nivel 1 |
| **Error 404 / Página no Encontrada** | La URL digitada es incorrecta o el recurso (reto/noticia) fue despublicado o archivado. | Regresar a la página de inicio o utilizar el menú superior y el buscador integrado para localizar el contenido vigente. | N/A (Autoservicio) |
| **Error 500 / Error Interno del Servidor** | Falla transitoria en el procesamiento de base de datos o timeout en servicio externo. | No recargar la página repetidamente; esperar 2 minutos e intentar nuevamente. Si persiste, radicar ticket de soporte. | Soporte Nivel 2 (DevOps) |
| **Error de Contraseña o Cuenta Bloqueada** | Se superó el límite de 5 intentos fallidos consecutivos de autenticación. | Utilizar la opción "Recuperar contraseña" (`/user/password`) o esperar 15 minutos para el desbloqueo automático de IP. | Mesa de Ayuda Nivel 1 |
| **Archivo Excede Tamaño Permitido** | El archivo adjunto en la postulación o reto supera el límite configurado de 25 MB. | Optimizar o comprimir el archivo PDF utilizando herramientas estándar de compresión antes de cargarlo. | N/A (Autoservicio) |
| **Formato de Archivo no Admitido** | Se intentó subir una extensión no permitida por seguridad (ej. `.exe`, `.bat`, `.sh`). | Convertir la documentación a formato admitido (`.pdf`, `.docx`, `.xlsx`, `.png`, `.jpg`). | N/A (Autoservicio) |
| **Correo de Notificación no Recibido** | Retardo en servidor de correo del usuario o desvío a carpeta de Spam / Correo no deseado. | Revisar buzón de Spam; agregar `notificaciones-zinco@unicordoba.edu.co` a contactos de confianza y comprobar correo en perfil. | Mesa de Ayuda Nivel 1 |

---

## 2. Procedimiento de Radicación y Atención en Mesa de Ayuda
- **Canal Oficial:** Sistema Integrado de Gestión de Solicitudes (SIGEC) de la Universidad de Córdoba: `https://sigec.unicordoba.edu.co/soporte-zinco`
- **Correo Alterno de Soporte:** `soporte-zinco@unicordoba.edu.co`
- **Horario de Atención:** Lunes a Viernes de 08:00 a 12:00 y de 14:00 a 17:00 (UTC-5).
- **Datos Mínimos Mandatorios para Radicar un Ticket:**
  1. Nombre completo y documento de identificación del usuario.
  2. Perfil de acceso asignado (Visitante, Actor CTeI, Evaluador).
  3. URL exacta donde se presentó la anomalía.
  4. Fecha, hora exacta y descripción detallada de la acción que se intentaba ejecutar.
  5. Captura de pantalla del mensaje de error visualizado.
