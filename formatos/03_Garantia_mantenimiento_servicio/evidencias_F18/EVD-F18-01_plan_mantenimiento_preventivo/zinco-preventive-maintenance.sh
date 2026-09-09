#!/usr/bin/env bash
# ==============================================================================
# SCRIPT DE MANTENIMIENTO PREVENTIVO AUTOMATIZADO - PLATAFORMA ZINCO
# ==============================================================================
set -euo pipefail

LOG_FILE="/var/log/zinco/preventive_maintenance.log"
ZINCO_ROOT="/var/www/zinco"
DB_NAME="zinco_db"

mkdir -p "$(dirname "$LOG_FILE")"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log "=== INICIO DE SESION DE MANTENIMIENTO PREVENTIVO ZINCO ==="

# 1. Verificacion de espacio en disco e inodos
log "[1/5] Verificando espacio en disco..."
DISK_USAGE=$(df /var | awk 'NR==2 {print $5}' | tr -d '%')
if [[ "$DISK_USAGE" -gt 80 ]]; then
    log "[ALERTA] Espacio en disco crítico: ${DISK_USAGE}% utilizado en /var."
else
    log "[OK] Espacio en disco adecuado: ${DISK_USAGE}% utilizado."
fi

# 2. Depuracion de registros de watchdog y sesiones expiradas en Drupal
log "[2/5] Depurando registros antiguos de watchdog y sesiones temporales..."
su -s /bin/bash www-data -c "cd $ZINCO_ROOT && vendor/bin/drush watchdog:delete --severity=Notice --count=5000 -y" || true
su -s /bin/bash www-data -c "cd $ZINCO_ROOT && vendor/bin/drush core:cron" || true

# 3. Optimizacion de tablas MariaDB
log "[3/5] Analizando y optimizando tablas en MariaDB ($DB_NAME)..."
mysqlcheck --optimize --databases "$DB_NAME" || log "[WARN] Error parcial al optimizar MariaDB."

# 4. Reconstruccion de caches Drupal
log "[4/5] Reconstruyendo memoria cache de Drupal..."
su -s /bin/bash www-data -c "cd $ZINCO_ROOT && vendor/bin/drush cache:rebuild"

# 5. Rotacion preventiva de logs
log "[5/5] Ejecutando rotacion preventiva de logs del sistema..."
logrotate -f /etc/logrotate.d/zinco || true

log "=== MANTENIMIENTO PREVENTIVO FINALIZADO EXITOSAMENTE ==="
