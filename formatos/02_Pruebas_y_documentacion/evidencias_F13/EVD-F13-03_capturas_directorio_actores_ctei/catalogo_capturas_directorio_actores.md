# EVD-F13-03: Catálogo Descriptivo de Capturas - Directorio de Actores CTeI
**Módulo:** Exploración de Actores, Fichas Institucionales y Edición de Perfil  
**Ambiente Verificado:** Staging / Producción ZINCO  
**Versión de Línea Base:** 1.0.0-PROD  

---

### Ficha de Captura CAP-05: Directorio General de Actores CTeI
- **Ruta:** `/actores`
- **Descripción Visual:** Vista pública con paginador de cuadrícula (*card grid*). Cada tarjeta muestra logotipo institucional o fotografía del actor, nombre oficial, tipo de actor (Grupo de Investigación, Empresa, Entidad de Gobierno, etc.), municipio de sede y etiquetas de áreas de conocimiento.
- **Panel Lateral de Filtros:** Filtros dinámicos facetados por Hélice (Academia, Empresa, Estado, Sociedad), Categoría Minciencias (A1, A, B, C, Reconocido), Municipio y Enfoque Temático (Agroindustria, Salud, Biodiversidad, TIC).

### Ficha de Captura CAP-06: Perfil Detallado de Actor (Ficha Institucional)
- **Ruta:** `/actores/{id}` o `/perfil/{tipo_actor}/{id}`
- **Descripción Visual:** Página estructurada en pestañas o bloques modulares:
  1. *Cabecera:* Portada institucional, logotipo, resumen ejecutivo y enlaces a redes/sitio web.
  2. *Datos de Contacto:* Dirección física, teléfono verificado, correo de enlace oficial.
  3. *Líneas de Investigación / Capacidades:* Desglose de fortalezas tecnológicas e infraestructura física disponible.
  4. *Producción Científica y Proyectos:* Listado interoperable con ScienTI / GrupLAC (artículos, patentes, software y proyectos I+D+i vinculados).
  5. *Reconocimientos:* Insignias y avales otorgados por otros actores del ecosistema departamental.

### Ficha de Captura CAP-07: Formulario de Edición y Actualización de Perfil
- **Ruta:** `/actores/{id}/edit` (disponible únicamente para el actor propietario o administrador)
- **Descripción Visual:** Formulario multicapa con pestañas verticales ("Información General", "Capacidades Técnicas", "Oferta de Productos/Servicios", "Galería Multimedia y Documentos"). Dispone de campos de texto enriquecido, selectores taxonómicos y componentes de carga de archivos con validación en tiempo real.
