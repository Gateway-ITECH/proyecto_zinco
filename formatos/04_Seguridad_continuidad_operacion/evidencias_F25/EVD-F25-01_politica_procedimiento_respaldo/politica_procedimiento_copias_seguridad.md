# Política y Procedimiento Oficial de Copias de Seguridad (Backups)
**Plataforma Zinco - Ecosistema CTeI Universidad de Córdoba**  
**Código**: SOP-BKP-ZINCO-01 | **Alineación**: Acuerdo 062 de 2021 UNICOR / ISO/IEC 27001:2022  

---

## 1. Alcance y Estrategia 3-2-1
Para garantizar la resiliencia y continuidad operativa de la Plataforma Zinco, se adopta la estrategia 3-2-1 de respaldo de la información:
- **3 Copias**: Datos activos en producción, copia local comprimida en disco del servidor anfitrión, y réplica externa fuera de sitio.
- **2 Medios Distintos**: Disco de estado sólido (NVMe/SSD) local y almacenamiento en la nube en Microsoft Azure Blob Storage (Archive Tier).
- **1 Copia Fuera de Sitio**: Contenedor inmutable en la nube de Azure (región East US) protegido contra desastres físicos locales y ataques de ransomware.

## 2. Parámetros Operativos de Resiliencia
- **RPO (Recovery Point Objective)**: **6 horas** (Pérdida máxima tolerable de datos, cubierta con 4 respaldos diarios).
- **RTO (Recovery Time Objective)**: **2 horas** (Tiempo máximo de restablecimiento total del servicio).
