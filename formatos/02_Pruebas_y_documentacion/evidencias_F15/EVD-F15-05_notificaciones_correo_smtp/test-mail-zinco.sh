#!/usr/bin/env bash
# ==============================================================================
# SCRIPT DE PRUEBA TRANSACCIONAL SMTP - PLATAFORMA ZINCO
# ==============================================================================
set -euo pipefail

DEST_EMAIL="${1:-operaciones.ti@unicordoba.edu.co}"
ZINCO_ROOT="/var/www/zinco"

echo "Enviando correo de prueba a: $DEST_EMAIL..."

su -s /bin/bash www-data -c "cd $ZINCO_ROOT && vendor/bin/drush php:eval "
\$mail_manager = \Drupal::service('plugin.manager.mail');
\$params = [
    'subject' => 'Prueba Sintetica de Despacho SMTP - Zinco F15',
    'body' => ['Este es un mensaje de prueba automatizado para verificar el canal SMTP de la Plataforma Zinco.']
];
\$result = \$mail_manager->mail('zinco_core', 'manual_test', '$DEST_EMAIL', 'es', \$params);
if (\$result['result']) {
    print('EXITO: Correo despachado correctamente.
');
} else {
    print('ERROR: Fallo el despacho del correo.
');
    exit(1);
}
""
