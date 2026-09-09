# Procedimiento Estándar de Mantenimiento Correctivo y Parcheo
**Plataforma Zinco - Ecosistema CTeI Universidad de Córdoba**  
**Código**: SOP-MAINT-CORR-01 | **Alineación**: ISO/IEC/IEEE 14764:2022  

---

## 1. Detección y Registro del Defecto
- Radicación del ticket formal en Jira Service Management (`https://soporte.gatewayit.co/zinco`).
- Asignación de severidad según el catálogo SLA (Sev 1 a Sev 4).
- Asignación del caso a un Ingeniero de Desarrollo de Gateway IT S.A.S.

## 2. Aislamiento y Reproducción en Entorno Local
- Clonación de la base de datos anonimizada o fixtures de prueba en entorno Lando v3 (`zincowsl-app.lndo.site`).
- Reproducción paso a paso del error documentando el stacktrace y log de watchdog.

## 3. Desarrollo de la Solución (Flujo Git GitFlow)
```bash
# Creación de rama de corrección desde tag de producción
git checkout -b hotfix/ZINCO-INC-104 tags/v1.0.0-prod

# Desarrollo del parche y pruebas unitarias
git commit -am "fix(zinco_reportes): solve table overflow in PDF generation Dompdf [ZINCO-INC-104]"

# Pull Request hacia rama staging para revisión por pares
git push origin hotfix/ZINCO-INC-104
```

## 4. Homologación y Validación en Staging
- Despliegue automático o asistido sobre `zinco-staging.unicordoba.edu.co`.
- Ejecución de pruebas de humo y batería de no regresión (115 casos de prueba).
- Verificación técnica por la supervisión de la Universidad de Córdoba.

## 5. Despliegue en Producción y Cierre
- Merge de la rama hotfix a `main`.
- Generación de tag semántico inmutable de parche (ej. `v1.0.1-patch`).
- Despliegue en producción mediante `zinco-deploy.sh v1.0.1-patch`.
- Notificación al usuario y cierre formal del ticket con entrega de RCA si aplicó Sev 1.
