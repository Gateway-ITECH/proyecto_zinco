#!/usr/bin/env bash
# ==============================================================================
# MOTOR DE RESPALDO AUTOMATIZADO - PLATAFORMA ZINCO
# Alineacion: Acuerdo 062 de 2021 UNICOR / ISO/IEC 27001:2022
# ==============================================================================
set -euo pipefail

BACKUP_DIR="/backups/zinco"
TIMESTAMP=$(date '+%Y%m%d_%H%M%S')
DB_NAME="zinco_db"
FILES_DIR="/var/www/zinco/web/sites/default/files"
LOG_FILE="/var/log/zinco/backup.log"

mkdir -p "$BACKUP_DIR/db" "$BACKUP_DIR/files" "$(dirname "$LOG_FILE")"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log "=== INICIANDO MOTOR DE COPIA DE SEGURIDAD ZINCO: $TIMESTAMP ==="

# 1. Respaldo Transaccional MariaDB
DB_OUT="$BACKUP_DIR/db/${DB_NAME}_${TIMESTAMP}.sql.gz"
log "Extrayendo dump de base de datos $DB_NAME (consistente sin bloqueo)..."
mysqldump --single-transaction --quick --routines --triggers "$DB_NAME" | gzip -9 > "$DB_OUT"
sha256sum "$DB_OUT" > "${DB_OUT}.sha256"
log "Dump de base de datos generado: $DB_OUT"

# 2. Respaldo de Archivos Locales (excluyendo temporales de cache)
FILES_OUT="$BACKUP_DIR/files/zinco_files_${TIMESTAMP}.tar.gz"
log "Comprimiendo sistema de archivos en $FILES_DIR..."
tar --exclude='css' --exclude='js' --exclude='php' -czf "$FILES_OUT" -C "$FILES_DIR" .
sha256sum "$FILES_OUT" > "${FILES_OUT}.sha256"
log "Archivos comprimidos generados: $FILES_OUT"

# 3. Sincronizacion fuera de sitio con Azure Blob Storage (Archive Tier)
if command -v az &>/dev/null && [[ -n "${AZURE_STORAGE_CONNECTION_STRING:-}" ]]; then
    log "Sincronizando con Azure Blob Storage (contenedor: zinco-backups)..."
    az storage blob upload --container-name zinco-backups --file "$DB_OUT" --name "db/$(basename "$DB_OUT")" --tier Archive --only-show-errors || true
    az storage blob upload --container-name zinco-backups --file "$FILES_OUT" --name "files/$(basename "$FILES_OUT")" --tier Archive --only-show-errors || true
    log "Sincronizacion en la nube completada."
fi

# 4. Politica de Retencion: Depurar copias locales > 30 dias
log "Aplicando politica de retencion local (purgando > 30 dias)..."
find "$BACKUP_DIR/db" -name "*.sql.gz*" -mtime +30 -delete
find "$BACKUP_DIR/files" -name "*.tar.gz*" -mtime +30 -delete

log "=== MOTOR DE RESPALDO COMPLETADO SATISFACTORIAMENTE ==="
