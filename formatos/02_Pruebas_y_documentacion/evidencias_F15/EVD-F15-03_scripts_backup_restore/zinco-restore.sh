#!/usr/bin/env bash
# ==============================================================================
# SCRIPT DE RESTAURACION Y RECUPERACION - PLATAFORMA ZINCO
# ==============================================================================
set -euo pipefail

DB_FILE="${1:-}"
FILES_FILE="${2:-}"
DB_NAME="zinco_db"
FILES_DIR="/var/www/zinco/web/sites/default/files"

if [[ -z "$DB_FILE" || ! -f "$DB_FILE" ]]; then
    echo "ERROR: Debe indicar la ruta valida del dump de BD (.sql.gz). Ejemplo: $0 /backups/zinco/db/dump.sql.gz [/backups/zinco/files/files.tar.gz]" >&2
    exit 1
fi

echo "=== INICIANDO PROCEDIMIENTO DE RESTAURACION ZINCO ==="
read -p "ADVERTENCIA: Esta operacion sobreescribira la base de datos '$DB_NAME'. Desea continuar? (s/N): " confirm
if [[ "$confirm" != "s" && "$confirm" != "S" ]]; then
    echo "Operacion cancelada por el usuario."
    exit 0
fi

# 1. Validar integridad sha256 si existe
if [[ -f "${DB_FILE}.sha256" ]]; then
    echo "Verificando suma SHA-256 del dump de BD..."
    sha256sum -c "${DB_FILE}.sha256"
fi

# 2. Restaurar Base de Datos
echo "Restaurando base de datos $DB_NAME..."
gunzip < "$DB_FILE" | mysql "$DB_NAME"
echo "Base de datos restaurada correctamente."

# 3. Restaurar Archivos si se especificaron
if [[ -n "$FILES_FILE" && -f "$FILES_FILE" ]]; then
    echo "Restaurando directorio de archivos en $FILES_DIR..."
    tar -xzf "$FILES_FILE" -C "$FILES_DIR"
    chown -R www-data:www-data "$FILES_DIR"
    echo "Archivos restaurados y permisos restablecidos."
fi

# 4. Actualizar esquema y limpiar caches
echo "Actualizando esquema y reconstruyendo caches con Drush..."
su -s /bin/bash www-data -c "cd /var/www/zinco && vendor/bin/drush updatedb -y"
su -s /bin/bash www-data -c "cd /var/www/zinco && vendor/bin/drush cache:rebuild"

# 5. Verificacion
su -s /bin/bash www-data -c "cd /var/www/zinco && vendor/bin/drush status"
echo "=== RESTAURACION COMPLETADA SATISFACTORIAMENTE ==="
