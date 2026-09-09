# Plan de Continuidad de Negocio y Recuperacion ante Desastres (DRP)
**Plataforma Zinco - Ecosistema CTeI Universidad de Cordoba**  

---

## 1. Metricas Objetivas de Recuperacion
- **RTO (Recovery Time Objective)**: **2 horas**  
  Tiempo maximo admisible desde la declaracion de la contingencia mayor hasta el restablecimiento completo del servicio web y de consulta.
- **RPO (Recovery Point Objective)**: **6 horas**  
  Perdida maxima de datos tolerada. Se mitiga mediante respaldos automaticos de transacciones y base de datos ejecutados 4 veces al dia (02:00, 08:00, 14:00, 20:00 UTC-5).

## 2. Niveles de Contingencia
1. **Nivel 1 - Degradacion de Servicio**: Fallo de proceso PHP-FPM o Apache. Accion: reinicio automatico mediante systemd watchdog (< 2 minutos).
2. **Nivel 2 - Corrupcion de Base de Datos**: Fallo de consistencia MariaDB. Accion: restauracion del dump mas reciente mediante `zinco-restore.sh` (< 45 minutos).
3. **Nivel 3 - Desastre Total de Servidor Fisico/VM**: Destruccion o caida prolongada del host principal. Accion: Aprovisionamiento de VM secundaria de contingencia en infraestructura UNICOR o nube secundaria, recuperacion de codigo desde GitHub, restauracion de BD y archivos desde Azure Blob Archive Tier (< 2 horas).

## 3. Directorio de Contactos de Emergencia
- **Lider de Infraestructura y Redes UNICOR**: soporte.infraestructura@unicordoba.edu.co | Tel: +57 300 000 0001
- **Lider DevOps Gateway IT S.A.S.**: devops@gatewayit.co | Tel: +57 310 000 0002
- **DBA Institucional UNICOR**: dba@unicordoba.edu.co | Tel: +57 300 000 0003
- **Direccion TIC / Supervisora Contractual**: direccion.tic@unicordoba.edu.co
