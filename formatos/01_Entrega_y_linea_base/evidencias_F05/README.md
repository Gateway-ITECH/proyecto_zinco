# Carpeta de Evidencias de Modelo y Diccionario de Datos — Formato F05

Esta carpeta contiene el modelo de datos formal, diccionario consolidado de campos, diagramas entidad-relación, catálogos taxonómicos y políticas de tratamiento de datos personales correspondientes al **Modelo de Datos y Diccionario de Datos (Formato F05)** de la **Plataforma Zinco**.

## Estructura de Evidencias

| Identificador | Descripción | Ubicación |
|---|---|---|
| **EVI-01** | Diccionario de datos completo (102 campos en CSV y TXT estructurado) | `EVI-01_diccionario_completo/` |
| **EVI-02** | Diagrama entidad-relación (DER) en Mermaid y matriz de cardinalidades | `EVI-02_diagrama_entidad_relacion/` |
| **EVI-03** | Esquema SQL y definiciones DDL de las 11 entidades personalizadas Zinco | `EVI-03_esquema_tablas_entidades/` |
| **EVI-04** | Catálogo de los 24 vocabularios de taxonomía normativos (DIVIPOLA, CIIU, etc.) | `EVI-04_catalogos_taxonomicos/` |
| **EVI-05** | Procedimiento de respaldo (backup en caliente) y prueba de restauración MySQL | `EVI-05_procedimientos_backup_restore/` |
| **EVI-06** | Matriz de datos personales y Habeas Data (Ley 1581 de 2012) | `EVI-06_matriz_datos_personales/` |

## Integridad
Todos los archivos cuentan con sus firmas criptográficas registradas en `MANIFEST_HASHES_SHA256.txt`.
