# Carpeta de Evidencias de Seguridad y Parches - Formato F24
**Plataforma Zinco — Ecosistema de Ciencia, Tecnología e Innovación de Córdoba (CTeI)**  
**Alineación**: NIST SP 800-40 Rev. 4 / ISO/IEC 27001:2022 / OWASP ASVS v4.0  

---

## 1. Propósito
La presente carpeta almacena los reportes de auditoría de seguridad, certificados de reescaneo, matrices de vulnerabilidad CVSS v3.1, actas de excepción controlada y directrices de endurecimiento de plataforma que sustentan el documento oficial **[`F24_Gestión de parches, vulnerabilidades y verificación de seguridad.docx`](../F24_Gesti%C3%B3n%20de%20parches,%20vulnerabilidades%20y%20verificaci%C3%B3n%20de%20seguridad.docx)**.

## 2. Estructura de Evidencias
1. **`EVD-F24-01_politica_gestion_parches/`**:
   - `politica_gestion_parches_vulnerabilidades.md`: Procedimiento estándar (SOP-SEC-01) y SLAs de remediación (críticos <= 72h).
2. **`EVD-F24-02_auditoria_composer_audit/`**:
   - `reporte_composer_audit_linea_base.txt`: Auditoría oficial de Composer Audit sobre los 113 paquetes en `composer.lock` (0 vulnerabilidades).
3. **`EVD-F24-03_endurecimiento_cabeceras_http/`**:
   - `reporte_endurecimiento_cabeceras_tls.txt`: Verificación de cabeceras HTTP de seguridad (ServerTokens, HSTS, X-Frame-Options, CSP).
4. **`EVD-F24-04_matriz_cvss_registro_hallazgos/`**:
   - `matriz_cvss_registro_vulnerabilidades.md`: Matriz detallada de evaluación CVSS v3.1 de los hallazgos remediados (`VULN-ZINCO-01` a `03`).
5. **`EVD-F24-05_acta_excepcion_riesgo_controlado/`**:
   - `acta_excepcion_controlada_scraping_minciencias.txt`: Justificación y controles compensatorios para el web scraping de Minciencias.
6. **`EVD-F24-06_reescaneo_certificacion_seguridad/`**:
   - `certificacion_reescaneo_seguridad_cierre.txt`: Certificación formal de línea base libre de vulnerabilidades y conformidad final.

## 3. Integridad Criptográfica
Todas las evidencias cuentan con sumas de verificación criptográficas registradas en [`MANIFEST_HASHES_SHA256.txt`](./MANIFEST_HASHES_SHA256.txt).
