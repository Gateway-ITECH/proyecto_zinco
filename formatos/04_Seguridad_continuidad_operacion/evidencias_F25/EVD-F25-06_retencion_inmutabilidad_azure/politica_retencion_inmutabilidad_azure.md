# Política de Retención, Inmutabilidad y Depuración Segura de Backups
**Plataforma Zinco - Ecosistema CTeI Universidad de Córdoba**  
**Alineación**: Acuerdo 062 de 2021 UNICOR / ISO/IEC 27001:2022  

---

## 1. Esquema de Retención Temporal
- **Retención Local (Servidor de Producción `/backups/zinco/`)**:
  - Dumps diarios de MariaDB y tarballs de archivos conservados durante **30 días calendario**.
  - Purga automática ejecutada diariamente al final del script `zinco-backup-engine.sh`.
- **Retención Externa (Microsoft Azure Blob Storage Archive Tier)**:
  - Respaldos consolidados conservados durante **365 días calendario** (1 año).
  - Configuración mediante Azure Lifecycle Management con transición automática:
    - Día 0: Subida a Storage Tier Cool/Archive.
    - Día 365: Eliminación automática por directiva de ciclo de vida en la nube.

## 2. Inmutabilidad y Protección WORM contra Ransomware
- **Directiva de Inmutabilidad Habilitada**: El contenedor `zinco-backups` cuenta con política de retención inmutable basada en tiempo (WORM - Write Once, Read Many).
- **Bloqueo Legal / Time-based Retention**: Ningún usuario, proceso automatizado ni administrador puede modificar o eliminar los blobs durante los primeros 30 días posteriores a su creación, protegiendo los respaldos contra ataques de malware o secuestro de datos.
