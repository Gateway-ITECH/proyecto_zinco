#!/usr/bin/env bash
# ==============================================================================
# SCRIPT AUTOMATIZADO DE CONSTRUCCIÓN REPRODUCIBLE — PLATAFORMA ZINCO
# ==============================================================================
set -euo pipefail

echo "[1/5] Verificando requisitos de entorno (PHP 8.4, Composer 2.x, Docker)..."
php -v | head -n 1
composer --version
docker --version

echo "[2/5] Descargando y bloqueando dependencias exactas desde composer.lock..."
composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

echo "[3/5] Instalando dependencias de procesamiento Python..."
pip install --no-cache-dir -r requirements.txt

echo "[4/5] Levantando contenedores orquestados con Lando..."
lando start

echo "[5/5] Sincronizando configuración Drupal y limpiando caché..."
lando drush cim -y
lando drush cr

echo ">>> BUILD REPRODUCIBLE COMPLETADO EXITOSAMENTE AL 100% <<<"
