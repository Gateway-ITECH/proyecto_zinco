# EVD-F13-05: Guía Operativa - Asistente Inteligente y Centro de Notificaciones
**Módulos:** Agente Inteligente para Consultas CTeI (Módulo 9) y Centro de Notificaciones  
**Ambiente Verificado:** Staging / Producción ZINCO  
**Versión de Línea Base:** 1.0.0-PROD  

---

## 1. Guía Operativa del Agente Inteligente para Consultas (Módulo 9)
### 1.1 Acceso al Asistente
El Asistente Virtual CTeI está disponible de manera permanente para todos los usuarios (anónimos y autenticados) mediante:
- Widget flotante interactivo ubicado en la esquina inferior derecha de la plataforma.
- Sección dedicada en barra de navegación: `/asistente-ctei`.

### 1.2 Capacidades de Comprensión y Respuesta (NLU)
- **Consultas sobre Actores:** El usuario puede consultar: *"¿Cuáles son los grupos de investigación en biotecnología agrícola?"* -> El asistente procesa la intención, identifica la entidad y despliega tarjetas con los grupos categorizados en Córdoba con enlace a su ficha.
- **Consultas sobre Oportunidades y Retos:** *"¿Qué retos de innovación están abiertos para empresas?"* -> El agente lista los retos vigentes filtrados por estado 'Abierto'.
- **Búsquedas de Capacidades y Equipos:** *"¿Dónde puedo realizar pruebas de análisis de suelos?"* -> Mapea laboratorios e infraestructura de la Universidad de Córdoba y aliados registrados.
- **Chips de Sugerencia Rápida:** Al iniciar una conversación o cuando una consulta es genérica, el agente ofrece botones de navegación guiada: `[Explorar Grupos de Investigación]`, `[Ver Convocatorias Abiertas]`, `[Conocer Guías de Propiedad Intelectual]`.

### 1.3 Controles de Seguridad y Fallback
- El asistente opera bajo una política de confidencialidad estricta: No solicita, no procesa y descarta inmediatamente credenciales, contraseñas o datos personales sensibles.
- En caso de degradación temporal del servicio de procesamiento de lenguaje natural, el sistema conmuta automáticamente a la búsqueda multifiltro avanzada (`/actores`, `/retos`).

---

## 2. Guía Operativa del Centro de Notificaciones y Preferencias
### 2.1 Notificaciones en Plataforma (Campana Web)
- Accesible en la barra superior (`block--user-menu`). Despliega alertas visuales no leídas con insignia numérica.
- Eventos notificados: Notificación de recepción de retos, confirmación de postulación radicada, notificación de evaluación registrada, solicitudes de reconocimiento institucional.

### 2.2 Notificaciones por Correo Electrónico
- Los 10 eventos transaccionales oficiales son despachados desde la dirección institucional `notificaciones-zinco@unicordoba.edu.co` con firma digital SPF/DKIM para evitar clasificaciones como correo no deseado (Spam).

### 2.3 Parametrización de Preferencias del Usuario
- Ubicación: *Mi Perfil -> Configuración de Notificaciones* (`/user/{uid}/edit#edit-notifications`).
- El usuario autenticado puede habilitar o inhabilitar la recepción de boletines informativos y resúmenes semanales de nuevas convocatorias, garantizándose siempre el envío de avisos transaccionales críticos (restablecimiento de clave y asignación formal de evaluación).
