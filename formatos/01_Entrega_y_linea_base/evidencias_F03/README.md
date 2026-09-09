# Carpeta de Evidencias CMDB — Formato F03

Esta carpeta reúne los elementos de configuración, inventarios, mapas de dependencias y actas de reconciliación correspondientes al **Inventario de Activos y Elementos de Configuración (CMDB - Formato F03)** de la **Plataforma Zinco**.

## Estructura de Evidencias

| Identificador | Descripción | Ubicación |
|---|---|---|
| **EVI-01** | Inventario CMDB consolidado en formatos CSV y TXT estructurado | `EVI-01_inventario_cmdb/` |
| **EVI-02** | Mapa de relaciones, jerarquías y matriz de impacto de fallas | `EVI-02_relaciones_dependencias/` |
| **EVI-03** | Ficha técnica de arquitectura de contenedores Lando / Docker | `EVI-03_arquitectura_servicios_docker/` |
| **EVI-04** | Catálogo de activos de software (16 módulos custom Zinco y dependencias) | `EVI-04_activos_software_modulos/` |
| **EVI-05** | Ficha técnica de almacenamiento cloud y conector Azure Blob Storage | `EVI-05_activos_cloud_azure/` |
| **EVI-06** | Acta formal de auditoría y reconciliación física y lógica de la CMDB | `EVI-06_reconciliacion_auditoria/` |

## Integridad
Todos los artefactos cuentan con sus hashes SHA-256 certificados en `MANIFEST_HASHES_SHA256.txt`.
