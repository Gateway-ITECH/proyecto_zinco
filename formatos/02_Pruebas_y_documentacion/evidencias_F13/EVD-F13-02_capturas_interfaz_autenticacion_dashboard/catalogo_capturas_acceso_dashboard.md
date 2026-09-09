# EVD-F13-02: Catálogo Descriptivo de Capturas - Acceso y Dashboard
**Módulo:** Autenticación de Usuarios y Dashboard Central de Indicadores CTeI  
**Ambiente Verificado:** Staging / Producción ZINCO (`https://zinco.apps1.unicordoba.edu.co/`)  
**Versión de Línea Base:** 1.0.0-PROD  

---

### Ficha de Captura CAP-01: Formulario de Inicio de Sesión
- **Ruta:** `/user/login`
- **Descripción Visual:** Interfaz institucional centrada con logotipo de ZINCO y Universidad de Córdoba. Dispone de campos de entrada para "Nombre de usuario o dirección de correo electrónico" y "Contraseña", botón de acción "Iniciar sesión" en azul primario (#0d6efd) y enlace inferior a recuperación de contraseña.
- **Elementos de Validación:** Protección CSRF habilitada, bloqueo progresivo tras 5 intentos fallidos consecutivos con captcha de seguridad.

### Ficha de Captura CAP-02: Recuperación Segura de Contraseña
- **Ruta:** `/user/password`
- **Descripción Visual:** Pantalla minimalista con instrucciones operativas. Campo único para ingreso de dirección de correo electrónico registrada y botón "Enviar instrucciones por correo electrónico". Notificación emergente verde de confirmación tras el envío del enlace de un solo uso.

### Ficha de Captura CAP-03: Barra de Navegación Superior y Menú de Usuario
- **Ruta:** Menú global en cabecera (`block--user-menu.html.twig`)
- **Descripción Visual:** Barra de navegación superior responsive. En estado anónimo muestra enlaces a "Inicio", "Actores", "Retos", "Oportunidades", "Caja de Herramientas", "Necesidades" y botón "Iniciar Sesión". En estado autenticado incorpora avatar del usuario, indicador con campana de notificaciones activas y menú desplegable con accesos a "Mi Perfil", "Mis Postulaciones", "Configuración de Notificaciones" y "Cerrar Sesión".

### Ficha de Captura CAP-04: Dashboard Centralizado de Indicadores CTeI
- **Ruta:** `/dashboard` o `/indicadores`
- **Descripción Visual:** Cuadro de mando integral organizado en tarjetas de resumen analítico (KPI cards) en la parte superior (Total Actores Registrados, Retos Activos, Grupos de Investigación Clasificados, Proyectos I+D+i en Ejecución).
- **Componentes Gráficos:** Gráficos dinámicos interactivos (barras, dona y líneas de tendencia) con desglose por municipio cordobés, sector productivo y tipología de actor. Filtros combinables en cabecera con botón de exportación a CSV.
