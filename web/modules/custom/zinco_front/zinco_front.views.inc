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
    'help' => t('External URL source of information.'),
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

  // ----------------------------------------------------------------------
  // Table: data_view_investigadores
  // ----------------------------------------------------------------------
  $data['data_view_investigadores'] = [
    'table' => [
      'group' => t('Investigadores'),
      'provider' => 'zinco_front',
      'base' => [
        'field' => 'id_persona_pr', // Assuming ID_PERSONA_PR is the primary key or unique identifier
        'title' => t('Investigadores Data'),
        'help' => t('Data from data_view_investigadores table.'),
      ],
    ],
  ];

  // Define fields for data_view_investigadores
  $fields = [
    'ID_CONVOCATORIA' => 'ID Convocatoria',
    'NME_CONVOCATORIA' => 'Nombre Convocatoria',
    'ANO_CONVO' => 'Año Convocatoria',
    'ID_PERSONA_PR' => 'ID Persona',
    'ID_AREA_CON_PR' => 'ID Area Conocimiento',
    'NME_ESP_AREA_PR' => 'Nombre Esp Area',
    'NME_AREA_PR' => 'Nombre Area',
    'NME_GRAN_AREA_PR' => 'Nombre Gran Area',
    'NME_GENERO_PR' => 'Genero',
    'NME_PAIS_NAC_PR' => 'Pais Nacimiento',
    'NME_REGION_NAC_PR' => 'Region Nacimiento',
    'NME_DEPARTAMENTO_NAC_PR' => 'Departamento Nacimiento',
    'NME_MUNICIPIO_NAC_PR' => 'Municipio Nacimiento',
    'COD_DANE_NAC_PR' => 'Cod Dane Nacimiento',
    'ID_NIV_FORMACION_PR' => 'ID Nivel Formacion',
    'NME_NIV_FORM_PR' => 'Nombre Nivel Formacion',
    'NRO_ORDEN_FORM_PR' => 'Nro Orden Formacion',
    'ID_CLAS_PR' => 'ID Clasificacion',
    'NME_CLASIFICACION_PR' => 'Nombre Clasificacion',
    'ORDEN_CLAS_PR' => 'Orden Clasificacion',
    'EDAD_ANOS_PR' => 'Edad Años',
    'NME_PAIS_RES_PR' => 'Pais Residencia',
    'NME_REGION_RES_PR' => 'Region Residencia',
    'NME_DEPARTAMENTO_RES_PR' => 'Departamento Residencia',
    'municipio' => 'Municipio Residencia',
    'COD_DANE_RES_PR' => 'Cod Dane Residencia',
    'ID_VICTIMA_CONFLICTO' => 'ID Victima Conflicto',
    'TXT_GRUPO_ETNICO' => 'Grupo Etnico',
    'TXT_POBLACION_DISCA' => 'Poblacion Discapacidad',
    'INST_FILIA' => 'Institucion Filia',
    'sector' => 'Sector',
    'tecnologia40' => 'Tecnologia 4.0',
  ];

  foreach ($fields as $field_name => $label) {
    // Determine field name in lowercase for the array key if needed, 
    // but usually keys match column names. 
    // However, Drupal coding standards prefer lowercase keys. 
    // Since the DB columns are uppercase (mostly), we should map them.
    // But for simplicity and matching the DB, we'll use the column name as the key 
    // or lowercase it if the DB driver handles case insensitivity.
    // MySQL on Linux is case sensitive for tables, but columns are usually case insensitive.
    // Let's use the exact column name from the SQL for the key to be safe, 
    // or lowercase it if that's the convention in this file (the previous table used lowercase).
    // The previous table 'data_grupos_investigacion' had lowercase fields.
    // The SQL for this new table has UPPERCASE fields.
    // I will use lowercase keys for the Views data array for consistency, 
    // and specify 'real field' if necessary, but usually Views handles it.
    // Actually, if I use lowercase keys, I must set 'real field' if the DB column is uppercase.
    // Let's assume standard behavior: use lowercase keys and set 'real field' to the uppercase name.
    
    $lower_field_name = strtolower($field_name);
    
    $data['data_view_investigadores'][$lower_field_name] = [
      'title' => t($label),
      'help' => t($label . '.'),
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
    
    // If the field name has uppercase letters, map it to the real field
    if ($field_name !== $lower_field_name) {
      $data['data_view_investigadores'][$lower_field_name]['real field'] = $field_name;
    }
  }

  // ----------------------------------------------------------------------

  
  // ----------------------------------------------------------------------
  // Table: data_organizaciones_intermedias
  // ----------------------------------------------------------------------
  $data['data_organizaciones_intermedias'] = [
    'table' => [
      'group' => t('Organizaciones Intermedias'),
      'provider' => 'zinco_front',
      'base' => [
        'field' => 'id',
        'title' => t('Organizaciones Intermedias Data'),
        'help' => t('Data from data_organizaciones_intermedias table.'),
      ],
    ],
  ];

  // Define fields for data_organizaciones_intermedias
  $fields_org = [
    'id' => 'ID',
    'nombre_organizacion' => 'Nombre Organizacion',
    'tipo_actor' => 'Tipo Actor',
    'fuente_informacion' => 'Fuente Informacion',
    'municipio' => 'Municipio',
    'sector' => 'Sector',
    'departamento' => 'Departamento',
  ];

  foreach ($fields_org as $field_name => $label) {
    $field_data = [
      'title' => t($label),
      'help' => t($label . '.'),
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

    // Adjust specific fields
    if ($field_name == 'id') {
      $field_data['field']['id'] = 'numeric';
      $field_data['filter']['id'] = 'numeric';
      $field_data['argument']['id'] = 'numeric';
    }
    
    // Check if it is a URL field based on name
    if ($field_name == 'fuente_informacion') {
        $field_data['field']['id'] = 'standard';
    }

    $data['data_organizaciones_intermedias'][$field_name] = $field_data;

    //
  }

  // ----------------------------------------------------------------------
  // Table: data_entidades_gobierno
  // ----------------------------------------------------------------------
  $data['data_entidades_gobierno'] = [
    'table' => [
      'group' => t('Entidades Gobierno'),
      'provider' => 'zinco_front',
      'base' => [
        'field' => 'id',
        'title' => t('Entidades Gobierno Data'),
        'help' => t('Data from data_entidades_gobierno table.'),
      ],
    ],
  ];

  // Define fields for data_entidades_gobierno
  $fields_entidades = [
    'id' => 'ID',
    'nombre_entidad' => 'Nombre Entidad',
    'dependencias' => 'Dependencias',
    'tipo_contribucion' => 'Tipo Contribucion',
    'contacto' => 'Contacto',
    'municipio' => 'Municipio',
    'sector' => 'Sector',
    'tecnologia40' => 'Tecnologia 4.0',
  ];

  foreach ($fields_entidades as $field_name => $label) {
    $field_data = [
      'title' => t($label),
      'help' => t($label . '.'),
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

    // Adjust specific fields
    if ($field_name == 'id') {
      $field_data['field']['id'] = 'numeric';
      $field_data['filter']['id'] = 'numeric';
      $field_data['argument']['id'] = 'numeric';
    }

    $data['data_entidades_gobierno'][$field_name] = $field_data;
  }

  

  // ----------------------------------------------------------------------
  // Table: data_instancias_gobierno
  // ----------------------------------------------------------------------
  $data['data_instancias_gobierno'] = [
    'table' => [
      'group' => t('Instancias Gobierno'),
      'provider' => 'zinco_front',
      'base' => [
        'field' => 'id',
        'title' => t('Instancias Gobierno Data'),
        'help' => t('Data from zinco_data_instancias_gobierno table.'),
      ],
    ],
  ];

  // Define fields for data_instancias_gobierno
  $fields_instancias = [
    'id' => 'ID',
    'nombre_instancia' => 'Nombre Instancia',
    'entidades_miembros' => 'Entidades Miembros',
    'entidad_lider' => 'Entidad Lider',
    'contacto' => 'Contacto',
    'municipio' => 'Municipio',
    'sector' => 'Sector',
    'tecnologia40' => 'Tecnologia 4.0',
  ];

  foreach ($fields_instancias as $field_name => $label) {
    $field_data = [
      'title' => t($label),
      'help' => t($label . '.'),
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

    // Adjust specific fields
    if ($field_name == 'id') {
      $field_data['field']['id'] = 'numeric';
      $field_data['filter']['id'] = 'numeric';
      $field_data['argument']['id'] = 'numeric';
    }

    $data['data_instancias_gobierno'][$field_name] = $field_data;
  }

  // ----------------------------------------------------------------------
  // Table: data_view_articulos_investigacion
  // ----------------------------------------------------------------------
  $data['data_view_articulos_investigacion'] = [
    'table' => [
      'group' => t('Articulos Investigacion'),
      'provider' => 'zinco_front',
      'base' => [
        'field' => 'id',
        'title' => t('Articulos Investigacion Data'),
        'help' => t('Data from data_view_articulos_investigacion view.'),
      ],
    ],
  ];

  // Define fields for data_view_articulos_investigacion
  $fields_articulos = [
    'ID' => 'ID',
    'ID_CONVOCATORIA' => 'ID Convocatoria',
    'NME_CONVOCATORIA' => 'Nombre Convocatoria',
    'ANO_CONVO' => 'Año Convocatoria',
    'ID_PRODUCTO_PD' => 'ID Producto',
    'NME_CLASE_PD' => 'Nombre Clase',
    'NME_TIPO_MEDICION_PD' => 'Nombre Tipo Medicion',
    'NME_TIPOLOGIA_PD' => 'Nombre Tipologia',
    'ID_TIPO_PD_MED' => 'ID Tipo PD Med',
    'NME_CATEGORIA_PD' => 'Nombre Categoria',
    'FCREACION_PD' => 'Fecha Creacion',
    'NME_PRODUCTO_PD' => 'Nombre Producto',
    'COD_GRUPO_GR' => 'Cod Grupo',
    'NME_GRUPO_GR' => 'Nombre Grupo',
    'ID_PERSONA_PD' => 'ID Persona',
    'municipio' => 'Municipio',
    'sector' => 'Sector',
    'tecnologia40' => 'Tecnologia 4.0',
  ];

  foreach ($fields_articulos as $field_name => $label) {
    $lower_field_name = strtolower($field_name);
    
    $data['data_view_articulos_investigacion'][$lower_field_name] = [
      'title' => t($label),
      'help' => t($label . '.'),
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
    
    // ID handling
    if ($lower_field_name == 'id') {
       $data['data_view_articulos_investigacion'][$lower_field_name]['field']['id'] = 'numeric';
       $data['data_view_articulos_investigacion'][$lower_field_name]['filter']['id'] = 'numeric';
       $data['data_view_articulos_investigacion'][$lower_field_name]['argument']['id'] = 'numeric';
    }

    // Map to real field if uppercase
    if ($field_name !== $lower_field_name) {
      $data['data_view_articulos_investigacion'][$lower_field_name]['real field'] = $field_name;
    }
  }

  // ----------------------------------------------------------------------
  // Table: data_instituciones_academicas
  // ----------------------------------------------------------------------
  $data['data_instituciones_academicas'] = [
    'table' => [
      'group' => t('Instituciones Academicas'),
      'provider' => 'zinco_front',
      'base' => [
        'field' => 'id',
        'title' => t('Instituciones Academicas Data'),
        'help' => t('Data from data_instituciones_academicas table.'),
      ],
    ],
  ];

  // Define fields for data_instituciones_academicas
  $fields_instituciones = [
    'id' => 'ID',
    'nombre_institucion' => 'Nombre Institucion',
    'caracter_institucion' => 'Caracter Institucion',
    'caracter_academico' => 'Caracter Academico',
    'departamento_domicilio' => 'Departamento Domicilio',
    'num_programas_ofertados' => 'Num Programas Ofertados',
    'num_programas_ctei' => 'Num Programas CTEI',
    'sector' => 'Sector',
    'tecnologia40' => 'Tecnologia 4.0',
    'municipio' => 'Municipio',
  ];

  foreach ($fields_instituciones as $field_name => $label) {
    $field_data = [
      'title' => t($label),
      'help' => t($label . '.'),
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

    // Numeric fields
    if (in_array($field_name, ['id', 'num_programas_ofertados', 'num_programas_ctei'])) {
      $field_data['field']['id'] = 'numeric';
      $field_data['filter']['id'] = 'numeric';
      $field_data['argument']['id'] = 'numeric';
    }

    $data['data_instituciones_academicas'][$field_name] = $field_data;
  }

  return $data;
}
