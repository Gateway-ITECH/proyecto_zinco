# Guia de Endurecimiento de Seguridad (Security Hardening)
**Plataforma Zinco - Ecosistema CTeI Universidad de Cordoba**  
**Alineacion**: ISO/IEC 27001:2022 | Controles A.8.2, A.8.9, A.8.20, A.8.24  

---

## 1. Politica de Acceso a Servidores y Bastionado SSH
- Autenticacion SSH restringida exclusivamente mediante llaves criptograficas de curva eliptica (`Ed25519`).
- Deshabilitada autenticacion por contrasenas (`PasswordAuthentication no`).
- Deshabilitado acceso remoto directo del superusuario (`PermitRootLogin no`).
- Acceso permitido unicamente a traves de la red VPN Institucional UNICOR y Bastion Host autorizado.
- Timeout de inactividad de sesion SSH: 15 minutos (`ClientAliveInterval 300`, `ClientAliveCountMax 3`).

## 2. Firewall de Red a Nivel de Sistema Operativo (UFW)
- Politica por defecto: Rechazar trafico entrante (`ufw default deny incoming`).
- Puertos autorizados:
  - Puerto 80/TCP (HTTP - Redireccion forzada a HTTPS).
  - Puerto 443/TCP (HTTPS - Cifrado TLS 1.3/1.2).
  - Puerto 22/TCP (SSH - Restringido por IP origen de subred VPN UNICOR).
  - Puerto 3306/TCP (MariaDB - Escuchando estrictamente en `127.0.0.1`, sin exposicion publica).

## 3. Gestion de Secretos y Variables de Entorno
- Principio de cero secretos en repositorios Git: `.env`, `settings.local.php`, llaves privadas excluidas en `.gitignore`.
- Credenciales inyectadas via `/etc/environment` y gestionadas por Azure Key Vault.
- Permisos del archivo `settings.php`: Lectura estricta `chmod 440` y propietario `www-data:www-data`.

## 4. Auditoria Continua de Vulnerabilidades
- Ejecucion automatizada de `composer audit` previa a cada despliegue para detectar CVEs en dependencias PHP.
- Monitoreo continuo del boletin de seguridad de Drupal (Drupal Security Team).
