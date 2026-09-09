#!/usr/bin/env bash
# ==============================================================================
# SCRIPT DE RESPALDO AUTOMATIZADO - PLATAFORMA ZINCO
# ==============================================================================
set -euo pipefail

BACKUP_DIR="/backups/zinco"
DATE=$(date '+%Y%m%d_%H%M%S')
DB_NAME="zinco_db"
FILES_DIR="/var/www/zinco/web/sites/default/files"
LOG_FILE="/var/log/zinco/backup.log"

mkdir -p "$BACKUP_DIR/db" "$BACKUP_DIR/files" "$(dirname "$LOG_FILE")"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log "=== INICIO DE COPIA DE SEGURIDAD ZINCO: $DATE ==="

# 1. Respaldo de Base de Datos MariaDB
DB_DUMP="$BACKUP_DIR/db/${DB_NAME}_${DATE}.sql.gz"
log "Extrayendo base de datos $DB_NAME..."
mysqldump --single-transaction --quick --routines --triggers "$DB_NAME" | gzip -9 > "$DB_DUMP"
log "Base de datos respaldada en: $DB_DUMP"

# 2. Respaldo de Archivos Locales (excluyendo css/js agregados)
FILES_ARCHIVE="$BACKUP_DIR/files/zinco_files_${DATE}.tar.gz"
log "Comprimiendo archivos en $FILES_DIR..."
tar --exclude='css' --exclude='js' --exclude='php' -czf "$FILES_ARCHIVE" -C "$FILES_DIR" .
log "Archivos respaldados en: $FILES_ARCHIVE"

# 3. Generar Checksums SHA-256
sha256sum "$DB_DUMP" > "${DB_DUMP}.sha256"
sha256sum "$FILES_ARCHIVE" > "${FILES_ARCHIVE}.sha256"
log "Checksums SHA-256 generados exitosamente."

# 4. Sincronizacion opcional con Azure Blob Storage (Archive Tier)
if command -v az &>/dev/null && [[ -n "${AZURE_STORAGE_CONNECTION_STRING:-}" ]]; then
    log "Sincronizando backup con Azure Blob Storage (contenedor: zinco-backups)..."
    az storage blob upload --container-name zinco-backups --file "$DB_DUMP" --name "db/$(basename "$DB_DUMP")" --tier Archive --only-show-errors || true
    az storage blob upload --container-name zinco-backups --file "$FILES_ARCHIVE" --name "files/$(basename "$FILES_ARCHIVE")" --tier Archive --only-show-errors || true
    log "Sincronizacion Azure completada."
fi

# 5. Politica de Retencion: Eliminar copias locales mayores a 30 dias
find "$BACKUP_DIR/db" -name "*.sql.gz*" -mtime +30 -delete
find "$BACKUP_DIR/files" -name "*.tar.gz*" -mtime +30 -delete
log "Depuracion de respaldos locales antiguos completada."

log "=== FIN DE RESPALDO EXITOSO: $DATE ==="
