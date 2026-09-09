#!/usr/bin/env bash
# ==============================================================================
# SCRIPT DE DIAGNOSTICO Y CHEQUEO DE SALUD RAPIDO - PLATAFORMA ZINCO
# ==============================================================================
set -euo pipefail

echo "================================================================================"
echo "CHEQUEO DE SALUD DE LA PLATAFORMA ZINCO - $(date '+%Y-%m-%d %H:%M:%S')"
echo "================================================================================"

# 1. Servicios del sistema
echo -n "[1] Daemon Apache2: "
if systemctl is-active --quiet apache2; then echo "OK (running)"; else echo "FAIL"; fi

echo -n "[2] Daemon PHP-FPM: "
if systemctl is-active --quiet php8.4-fpm; then echo "OK (running)"; else echo "FAIL"; fi

echo -n "[3] Daemon MariaDB: "
if systemctl is-active --quiet mariadb; then echo "OK (running)"; else echo "FAIL"; fi

# 2. Espacio en disco
echo "[4] Espacio en disco (/var):"
df -h /var | awk 'NR==2 {print "    Usado: "$3" / "$2" ("$5" de ocupacion)"}'

# 3. Drupal Bootstrap Status
echo "[5] Estado de Drupal (Drush):"
su -s /bin/bash www-data -c "cd /var/www/zinco && vendor/bin/drush status --fields=bootstrap,db-status" 2>/dev/null || echo "    [ALERTA] Fallo al ejecutar Drush"

# 4. Ultimos 5 errores en Watchdog
echo "[6] Ultimos errores registrados en Drupal Watchdog:"
su -s /bin/bash www-data -c "cd /var/www/zinco && vendor/bin/drush watchdog:show --severity=Error --count=5" 2>/dev/null || echo "    Sin errores criticos recientes."

# 5. Prueba de respuesta HTTP
echo -n "[7] Verificacion de respuesta HTTP institucional: "
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://zinco.unicordoba.edu.co/ || curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/ || echo "ERR")
echo "Codigo HTTP: $HTTP_CODE"

echo "================================================================================"
echo "DIAGNOSTICO FINALIZADO"
echo "================================================================================"
