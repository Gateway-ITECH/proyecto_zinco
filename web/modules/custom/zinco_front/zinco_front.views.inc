<?php

/**
 * @file
 * Contains zinco_front.views.inc.
 */

/**
 * Implements hook_views_data().
 */
function zinco_front_views_data() {
  $data = [];

  $data['data_grupos_investigacion'] = [
    'table' => [
      'group' => t('Grupos de Investigación'),
      'provider' => 'zinco_front',
      'base' => [
        'field' => 'id',
        'title' => t('Grupos de Investigación Data'),
        'help' => t('Data from data_grupos_investigacion table.'),
      ],
    ],
  ];

  // ID field.
  $data['data_grupos_investigacion']['id'] = [
    'title' => t('ID'),
    'help' => t('The unique ID of the group.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // Nombre Grupo.
  $data['data_grupos_investigacion']['nombre_grupo'] = [
    'title' => t('Nombre Grupo'),
    'help' => t('Name of the research group.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // Categoria Minciencias.
  $data['data_grupos_investigacion']['categoria_minciencias'] = [
    'title' => t('Categoria Minciencias'),
    'help' => t('Minciencias category.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // Numero Investigadores.
  $data['data_grupos_investigacion']['numero_investigadores'] = [
    'title' => t('Numero Investigadores'),
    'help' => t('Number of researchers.'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // Entidad Pertenece.
  $data['data_grupos_investigacion']['entidad_pertenece'] = [
    'title' => t('Entidad Pertenece'),
    'help' => t('Entity the group belongs to.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // Programa Nacional CTEI 1.
  $data['data_grupos_investigacion']['programa_nacional_ctei1'] = [
    'title' => t('Programa Nacional CTEI 1'),
    'help' => t('National Program CTEI 1.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // Programa Nacional CTEI 2.
  $data['data_grupos_investigacion']['programa_nacional_ctei2'] = [
    'title' => t('Programa Nacional CTEI 2'),
    'help' => t('National Program CTEI 2.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // Fuente Informacion.
  $data['data_grupos_investigacion']['fuente_informacion'] = [
    'title' => t('Fuente Informacion'),
    'help' => t('Source of information.'),
    'field' => [
      'id' => 'string', // Using markup for text fields that might contain HTML or long text.
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // Municipio.
  $data['data_grupos_investigacion']['municipio'] = [
    'title' => t('Municipio'),
    'help' => t('Municipality.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // Sector.
  $data['data_grupos_investigacion']['sector'] = [
    'title' => t('Sector'),
    'help' => t('Sector.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // Tecnologia 4.0.
  $data['data_grupos_investigacion']['tecnologia40'] = [
    'title' => t('Tecnologia 4.0'),
    'help' => t('Technology 4.0.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // Gruplac Total Softwares.
  $data['data_grupos_investigacion']['gruplac_total_softwares'] = [
    'title' => t('Gruplac Total Softwares'),
    'help' => t('Total softwares in Gruplac.'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // Cod Grupo.
  $data['data_grupos_investigacion']['cod_grupo'] = [
    'title' => t('Cod Grupo'),
    'help' => t('Group Code.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  return $data;
}
