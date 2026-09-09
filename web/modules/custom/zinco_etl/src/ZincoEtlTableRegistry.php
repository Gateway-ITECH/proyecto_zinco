<?php

namespace Drupal\zinco_etl;

/**
 * Central registry of ETL-managed tables and their primary keys.
 *
 * Both BatchMergeForm (CSV import) and TableViewController (browse/CRUD)
 * need the same table => primary key mapping; keeping a single copy avoids
 * the two lists drifting apart.
 */
class ZincoEtlTableRegistry
{

    /**
     * Table definitions keyed by short id.
     *
     * @var array
     */
    protected static $definitions = [
        'investigadores' => [
            'table' => 'data_investigadores',
            'title' => 'Investigadores',
            'pk' => 'ID_PERSONA_PR',
        ],
        'grupos_investigacion' => [
            'table' => 'data_grupos_investigacion',
            'title' => 'Grupos de Investigación',
            'pk' => 'cod_grupo',
        ],
        'instituciones_academicas' => [
            'table' => 'data_instituciones_academicas',
            'title' => 'Instituciones Académicas',
            'pk' => 'id',
        ],
        'programas_academicos' => [
            'table' => 'data_programas_academicos',
            'title' => 'Programas Académicos',
            'pk' => 'id',
        ],
        'organizaciones_intermedias' => [
            'table' => 'data_organizaciones_intermedias',
            'title' => 'Organizaciones Intermedias',
            'pk' => 'id',
        ],
        'entidades_gobierno' => [
            'table' => 'data_entidades_gobierno',
            'title' => 'Entidades de Gobierno',
            'pk' => 'id',
        ],
        'instancias_gobierno' => [
            'table' => 'data_instancias_gobierno',
            'title' => 'Instancias de Gobierno',
            'pk' => 'id',
        ],
    ];

    /**
     * Gets all table definitions keyed by short id.
     */
    public static function all(): array
    {
        return static::$definitions;
    }

    /**
     * Gets the definition for a given physical table name, if known.
     */
    public static function getDefinition(string $table): ?array
    {
        foreach (static::$definitions as $definition) {
            if ($definition['table'] === $table) {
                return $definition;
            }
        }
        return NULL;
    }

    /**
     * Gets the primary key column for a given physical table name.
     */
    public static function getPrimaryKey(string $table): ?string
    {
        return static::getDefinition($table)['pk'] ?? NULL;
    }

    /**
     * Checks whether a physical table name is a known/allowed ETL table.
     *
     * Used to keep table names coming from the URL restricted to this
     * allowlist before they are interpolated into SQL.
     */
    public static function isKnownTable(string $table): bool
    {
        return static::getDefinition($table) !== NULL;
    }

}
