#!/usr/bin/env bash
# ==============================================================================
# SCRIPT DE GESTION OPERATIVA DE SERVICIOS - PLATAFORMA ZINCO
# Plataforma Zinco (Ecosistema CTeI - Universidad de Cordoba / Gateway IT S.A.S.)
# ==============================================================================
set -euo pipefail

LOG_FILE="/var/log/zinco/service_manager.log"
mkdir -p "$(dirname "$LOG_FILE")"

log() {
    local ts
    ts=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$ts] $1" | tee -a "$LOG_FILE"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
       echo "[ERROR] Este script debe ejecutarse como root o con sudo." >&2
       exit 1
    fi
}

start_services() {
    log "Iniciando servicios del stack Zinco..."
    systemctl start mariadb
    systemctl start php8.4-fpm
    systemctl start apache2
    log "Servicios iniciados correctamente."
}

stop_services() {
    log "Deteniendo servicios del stack Zinco..."
    systemctl stop apache2
    systemctl stop php8.4-fpm
    systemctl stop mariadb
    log "Servicios detenidos."
}

restart_services() {
    log "Reiniciando servicios del stack Zinco..."
    systemctl restart mariadb
    systemctl restart php8.4-fpm
    systemctl restart apache2
    log "Reconstruyendo cache de Drupal..."
    su -s /bin/bash www-data -c "cd /var/www/zinco && vendor/bin/drush cache:rebuild"
    log "Servicios y caches reiniciados satisfactoriamente."
}

status_services() {
    echo "=== ESTADO DE SERVICIOS ZINCO ==="
    for s in mariadb php8.4-fpm apache2; do
        if systemctl is-active --quiet "$s"; then
            echo "[OK] $s esta ACTIVO (running)"
        else
            echo "[FAIL] $s esta INACTIVO"
        fi
    done
}

healthcheck() {
    status_services
    echo "=== PRUEBA DE CONEXION BASE DE DATOS ==="
    su -s /bin/bash www-data -c "cd /var/www/zinco && vendor/bin/drush status --fields=bootstrap,db-status"
    echo "=== PRUEBA HTTP LOCAL ==="
    local code
    code=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/ || true)
    echo "Respuesta HTTP Local: $code"
}

check_root

case "${1:-}" in
    start)
        start_services
        ;;
    stop)
        stop_services
        ;;
    restart)
        restart_services
        ;;
    status)
        status_services
        ;;
    healthcheck)
        healthcheck
        ;;
    *)
        echo "Uso: $0 {start|stop|restart|status|healthcheck}"
        exit 1
        ;;
esac
