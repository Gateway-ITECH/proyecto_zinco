# Carpeta de Evidencias de Repositorio y Build Reproducible — Formato F06

Esta carpeta contiene la documentación técnica, scripts de compilación, manifiestos criptográficos y reportes de prueba que demuestran la reproducibilidad del código fuente de la **Plataforma Zinco (Formato F06)**.

## Estructura de Evidencias

| Identificador | Descripción | Ubicación |
|---|---|---|
| **EVI-01** | Estado del repositorio Git, ramas, remotos y detalle de commit HEAD | `EVI-01_estado_repositorio_git/` |
| **EVI-02** | Manifiesto de sumas criptográficas SHA-256 de archivos maestros de build | `EVI-02_hashes_y_checksums/` |
| **EVI-03** | Script ejecutable de construcción reproducible en frío (`BUILD_REPRODUCIBLE.sh`) | `EVI-03_script_build_reproducible/` |
| **EVI-04** | Procedimiento de despliegue paso a paso y protocolo de rollback | `EVI-04_procedimiento_despliegue_rollback/` |
| **EVI-05** | Historial completo y trazable de los 98 commits del proyecto | `EVI-05_historial_completo_git/` |
| **EVI-06** | Reporte y log formal de la prueba de reproducibilidad determinista | `EVI-06_prueba_reproducibilidad/` |

## Integridad
Todos los archivos cuentan con sus firmas criptográficas registradas en `MANIFEST_HASHES_SHA256.txt`.
