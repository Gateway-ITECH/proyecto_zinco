# Runbook de Despliegue Paso a Paso - Plataforma Zinco
**Ecosistema de Ciencia, Tecnologia e Innovacion de Cordoba (CTeI)**  
**Version**: 1.0.0-PROD | **Ambiente**: Staging / Produccion UNICOR  

---

## 1. Alcance y Ventana de Despliegue
- Las actualizaciones a produccion deben realizarse dentro de la ventana de mantenimiento autorizada (Martes o Jueves entre 21:00 y 23:00 UTC-5).
- Requiere aprobacion formal previa del Lider Tecnico de Gateway IT y Supervision TI de UNICOR.
- Se debe validar previamente que el release haya permanecido estable minimo 48 horas en Staging.

## 2. Lista de Verificacion Pre-Despliegue
1. [ ] Verificar que no existan respaldos en curso en MariaDB ni Azure Blob Storage.
2. [ ] Validar espacio en disco (`df -h /var > 20% disponible`).
3. [ ] Notificar a los usuarios mediante banner de mantenimiento programado.
4. [ ] Generar snapshot / backup de seguridad inmediato de la base de datos `zinco_db`.

## 3. Procedimiento Operativo Detallado

### Paso 1: Activar Modo de Mantenimiento
```bash
su -s /bin/bash www-data -c "cd /var/www/zinco && vendor/bin/drush state:set system.maintenance_mode 1 --input-format=integer"
su -s /bin/bash www-data -c "cd /var/www/zinco && vendor/bin/drush cache:rebuild"
```

### Paso 2: Generar Respaldo Pre-Despliegue
```bash
/usr/local/bin/zinco-backup.sh --pre-deploy
```

### Paso 3: Sincronizar Codigo Fuente
```bash
cd /var/www/zinco
git fetch --all --tags
git checkout tags/v1.0.0-prod
```

### Paso 4: Instalar Dependencias PHP con Composer
```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

### Paso 5: Ejecutar Actualizaciones de Esquema y Base de Datos
```bash
su -s /bin/bash www-data -c "cd /var/www/zinco && vendor/bin/drush updatedb -y"
```

### Paso 6: Sincronizar Configuraciones de Drupal
```bash
su -s /bin/bash www-data -c "cd /var/www/zinco && vendor/bin/drush config:import -y"
```

### Paso 7: Reconstruir Caches y Desactivar Mantenimiento
```bash
su -s /bin/bash www-data -c "cd /var/www/zinco && vendor/bin/drush cache:rebuild"
su -s /bin/bash www-data -c "cd /var/www/zinco && vendor/bin/drush state:set system.maintenance_mode 0 --input-format=integer"
su -s /bin/bash www-data -c "cd /var/www/zinco && vendor/bin/drush cache:rebuild"
```

### Paso 8: Verificacion y Pruebas de Humo (Smoke Tests)
```bash
curl -I https://zinco.unicordoba.edu.co
su -s /bin/bash www-data -c "cd /var/www/zinco && vendor/bin/drush status"
```
