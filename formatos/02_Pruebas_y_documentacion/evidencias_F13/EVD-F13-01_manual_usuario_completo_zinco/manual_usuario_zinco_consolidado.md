# EVD-F13-01: Manual de Usuario Consolidado - Plataforma ZINCO
**Proyecto:** Fortalecimiento del Sistema Territorial de CTeI de Córdoba | BPIN 2023000100045  
**Entidad Beneficiaria:** Universidad de Córdoba  
**Proveedor Tecnológico:** Gateway IT S.A.S.  
**Versión de Línea Base:** 1.0.0-PROD  
**Fecha de Emisión:** 2026-03-09  

---

## 1. Propósito y Alcance del Manual
El presente documento consolida la guía operativa y procedimental completa para los usuarios de la **Plataforma ZINCO** (Ecosistema de Ciencia, Tecnología e Innovación del Departamento de Córdoba). Su objetivo es proporcionar a los diferentes actores del ecosistema (investigadores, empresarios, directivos universitarios, funcionarios de gobierno, evaluadores y ciudadanía) las directrices claras para navegar, consultar, publicar y gestionar información técnica y colaborativa dentro del portal.

## 2. Requisitos de Acceso y Entorno
- **URL Institucional Oficial:** `https://zinco.apps1.unicordoba.edu.co/`
- **Requisitos de Software:** Navegador web moderno con soporte para ECMAScript 6 y CSS Grid (Google Chrome 120+, Mozilla Firefox 120+, Microsoft Edge 120+, Apple Safari 17+).
- **Conectividad:** Conexión a Internet de banda ancha (mínimo 5 Mbps recomendados).
- **Herramientas Complementarias:** Visor de documentos PDF (Adobe Acrobat Reader o navegador integrado) y suite ofimática con soporte de hojas de cálculo (CSV/Excel).

## 3. Perfiles de Usuario y Modelo de Seguridad
1. **Visitante / Ciudadano (Anónimo):**
   - Acceso irrestricto a información pública: Dashboard de indicadores CTeI, Directorio de Actores, Catálogo de Retos abiertos, Vitrina de Oportunidades (convocatorias, eventos, cursos, noticias), Caja de Herramientas y Asistente Virtual para consultas.
2. **Actor CTeI Registrado / Autenticado:**
   - Capacidades de autogestión de perfil institucional o individual (datos de contacto, líneas de investigación, infraestructura técnica, productos y servicios).
   - Postulación de soluciones técnicas a Retos de Innovación Abierta vigentes.
   - Publicación de nuevos Retos de Innovación (sujeto a aprobación de organizador/administrador).
   - Registro y radicación de Necesidades y demandas tecnológicas departamentales.
   - Solicitud de reconocimientos y avales institucionales entre actores.
3. **Evaluador de Retos de Innovación:**
   - Acceso al panel especializado de evaluación (`/retos/evaluar`).
   - Calificación cualitativa y cuantitativa de postulaciones asignadas con rúbrica estructurada.
   - Emisión de conceptos técnicos y veredictos de viabilidad.
4. **Administrador / Gestor Institucional (UNICOR / Dirección TIC):**
   - Moderación y validación de actores registrados.
   - Publicación y cambio de estado de retos de innovación.
   - Asignación formal de evaluadores a soluciones radicadas.
   - Parametrización de taxonomías, auditoría de seguridad y gestión de usuarios.

## 4. Gestión de Cuenta: Autenticación, Recuperación y Cierre
- **Inicio de Sesión:** Acceso mediante enlace superior "Iniciar Sesión" (`/user/login`). Autenticación con nombre de usuario o correo electrónico institucional y contraseña segura (mínimo 10 caracteres, mayúsculas, minúsculas, dígitos y caracteres especiales).
- **Recuperación de Acceso:** Procedimiento de autoservicio a través de `/user/password`. Requiere comprobación de correo registrado y token temporal de un solo uso con vigencia de 24 horas.
- **Cierre Seguro de Sesión:** Selección de "Cerrar Sesión" en el menú de usuario (`/user/logout`). Destrucción inmediata de cookies de sesión `SSESS*` en navegador y servidor.

## 5. Módulos Operativos Principales
- **Dashboard e Indicadores CTeI:** Visualización interactiva de KPIs de investigación, grupos categorizados, proyectos I+D+i y capacidades instaladas en Córdoba. Filtros dinámicos por municipio, sector económico y taxonomía CTeI. Exportación autorizada de datos en formato CSV y reportes ejecutivos.
- **Directorio de Actores CTeI:** Catálogo clasificado bajo el modelo de la Cuádruple Hélice (Academia, Empresa, Estado y Sociedad Civil). Fichas técnicas detalladas con métricas de interoperabilidad Minciencias ScienTI / GrupLAC.
- **Retos de Innovación Abierta:** Ciclo de vida completo que comprende:
  1. *Fase de Convocatoria:* Publicación del desafío con términos de referencia, recompensas y plazos.
  2. *Fase de Postulación:* Radicación de propuestas por solucionadores mediante formularios estructurados y carga de anexos técnicos (PDF hasta 25 MB).
  3. *Fase de Evaluación:* Revisión ciega por evaluadores designados con calificación de 0 a 100 puntos y justificación técnica.
  4. *Fase de Cierre y Resultados:* Publicación de veredictos y soluciones ganadoras.
- **Caja de Herramientas CTeI:** Repositorio digital de guías metodológicas, manuales de propiedad intelectual, formatos tipo de formulación de proyectos y normatividad CTeI.
- **Módulo de Necesidades:** Ventanilla de captación de demandas tecnológicas territoriales, permitiendo conectar problemáticas locales con capacidades científicas de los grupos de investigación de Córdoba.
- **Agente Inteligente para Consultas:** Módulo conversacional con NLU para atención al usuario en lenguaje natural, búsqueda contextual y direccionamiento ágil.
- **Centro de Notificaciones:** Sistema bidireccional de alertas visuales en plataforma y notificaciones por correo electrónico transaccional.
