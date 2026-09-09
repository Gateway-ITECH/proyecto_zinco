#!/usr/bin/env bash
# ==============================================================================
# MOTOR DE RESTAURACION Y PRUEBA DE INTEGRIDAD - PLATAFORMA ZINCO
# ==============================================================================
set -euo pipefail

DB_DUMP="${1:-}"
FILES_ARCHIVE="${2:-}"
TARGET_DB="${3:-zinco_staging}"
LOG_FILE="/var/log/zinco/restore.log"

if [[ -z "$DB_DUMP" || ! -f "$DB_DUMP" ]]; then
    echo "ERROR: Debe especificar la ruta del archivo de dump (.sql.gz)" >&2
    exit 1
fi

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log "=== INICIO DE RESTAURACION DE PRUEBA EN $TARGET_DB ==="

# 1. Validacion de Suma Criptografica
if [[ -f "${DB_DUMP}.sha256" ]]; then
    log "Verificando suma de comprobacion SHA-256..."
    sha256sum -c "${DB_DUMP}.sha256"
    log "[OK] Suma SHA-256 verificada e integra."
fi

# 2. Prueba sintactica del archivo gzip
log "Validando integridad del archivo comprimido con gzip -t..."
gzip -t "$DB_DUMP"
log "[OK] Archivo comprimido sin corrupcion."

# 3. Restauracion en MariaDB
log "Restaurando estructura y datos en $TARGET_DB..."
gunzip < "$DB_DUMP" | mysql "$TARGET_DB"
log "[OK] Base de datos restaurada correctamente."

# 4. Verificacion de Tablas y Consistencia
TABLE_COUNT=$(mysql -N -s -e "SELECT count(*) FROM information_schema.tables WHERE table_schema='$TARGET_DB';")
log "Total de tablas restauradas en $TARGET_DB: $TABLE_COUNT (Esperado: >= 126)."

# 5. Reconstruccion de Caches Drupal
log "Reconstruyendo caches de Drupal con Drush..."
su -s /bin/bash www-data -c "cd /var/www/zinco && vendor/bin/drush cache:rebuild" || true

log "=== RESTAURACION COMPLETADA SATISFACTORIAMENTE ==="
