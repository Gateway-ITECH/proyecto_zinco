#!/usr/bin/env bash
# ==============================================================================
# SCRIPT AUTOMATIZADO DE DESPLIEGUE - PLATAFORMA ZINCO
# ==============================================================================
set -euo pipefail

DEPLOY_TAG="${1:-}"
ZINCO_ROOT="/var/www/zinco"
BACKUP_SCRIPT="/usr/local/bin/zinco-backup.sh"

if [[ -z "$DEPLOY_TAG" ]]; then
    echo "ERROR: Debe especificar el tag de despliegue. Ejemplo: $0 v1.0.0-prod" >&2
    exit 1
fi

echo "=== INICIANDO DESPLIEGUE DE ZINCO: $DEPLOY_TAG ==="

# 1. Modo Mantenimiento
echo "[1/7] Activando modo mantenimiento..."
su -s /bin/bash www-data -c "cd $ZINCO_ROOT && vendor/bin/drush state:set system.maintenance_mode 1 --input-format=integer"

# 2. Respaldo previo
echo "[2/7] Ejecutando backup previo..."
if [[ -f "$BACKUP_SCRIPT" ]]; then
    bash "$BACKUP_SCRIPT" --pre-deploy
fi

# 3. Git checkout
echo "[3/7] Descargando tag $DEPLOY_TAG..."
cd "$ZINCO_ROOT"
git fetch --tags
git checkout "tags/$DEPLOY_TAG"

# 4. Composer install
echo "[4/7] Instalando dependencias de produccion..."
composer install --no-dev --optimize-autoloader --no-interaction

# 5. Base de datos y configuracion
echo "[5/7] Ejecutando migraciones de BD y configuracion..."
su -s /bin/bash www-data -c "cd $ZINCO_ROOT && vendor/bin/drush updatedb -y"
su -s /bin/bash www-data -c "cd $ZINCO_ROOT && vendor/bin/drush config:import -y"

# 6. Reconstruir caches y desactivar mantenimiento
echo "[6/7] Reconstruyendo caches y restaurando servicio..."
su -s /bin/bash www-data -c "cd $ZINCO_ROOT && vendor/bin/drush cache:rebuild"
su -s /bin/bash www-data -c "cd $ZINCO_ROOT && vendor/bin/drush state:set system.maintenance_mode 0 --input-format=integer"
su -s /bin/bash www-data -c "cd $ZINCO_ROOT && vendor/bin/drush cache:rebuild"

# 7. Verificacion
echo "[7/7] Validando estado operativo..."
su -s /bin/bash www-data -c "cd $ZINCO_ROOT && vendor/bin/drush status --fields=bootstrap,db-status"

echo "=== DESPLIEGUE $DEPLOY_TAG COMPLETADO SATISFACTORIAMENTE ==="
