/**
 * @file
 * Javascript for the Zinco Front dashboard.
 */

(function (Drupal) {
  Drupal.behaviors.zincoDashboardFront = {
    attach: function (context, settings) {
      once('zincoDashboardFront', 'body', context).forEach(function (element) {
        console.log('Zinco Dashboard Front script loaded!');

        const municipioSelect = context.querySelector('#edit-municipio');
        const sectorSelect = context.querySelector('#edit-sector');
        const tecnologias40Select = context.querySelector('#edit-tecnologias-40');

        if (municipioSelect) {
          municipioSelect.selectedIndex = 0;
        }
        if (sectorSelect) {
          sectorSelect.selectedIndex = 0;
        }
        if (tecnologias40Select) {
          tecnologias40Select.selectedIndex = 0;
        }

        let municipio = municipioSelect ? municipioSelect.value : '';
        let sector = sectorSelect ? sectorSelect.value : '';
        let tecnologias40 = tecnologias40Select ? tecnologias40Select.value : '';

        callData(municipio, sector, tecnologias40);

        if (municipioSelect) {
          municipioSelect.addEventListener('change', function () {
            municipio = municipioSelect ? municipioSelect.value : '';
            sector = sectorSelect ? sectorSelect.value : '';
            tecnologias40 = tecnologias40Select ? tecnologias40Select.value : '';

            const detalleDumpDataLinks = document.querySelectorAll('.detalle-dump-data-link');
            detalleDumpDataLinks.forEach(link => {
              link.href += `?municipio=${municipio}&sector=${sector}&tecnologia40=${tecnologias40}`;
            });

            console.log('Municipio seleccionado:', this.value);

            callData(municipio, sector, tecnologias40);
          });
        }

        if (sectorSelect) {
          sectorSelect.addEventListener('change', function () {
            municipio = municipioSelect ? municipioSelect.value : '';
            sector = sectorSelect ? sectorSelect.value : '';
            tecnologias40 = tecnologias40Select ? tecnologias40Select.value : '';

            const detalleDumpDataLinks = document.querySelectorAll('.detalle-dump-data-link');
            detalleDumpDataLinks.forEach(link => {
              link.href += `?municipio=${municipio}&sector=${sector}&tecnologia40=${tecnologias40}`;
            });

            console.log('Sector seleccionado:', this.value);
            callData(municipio, sector, tecnologias40);

          });
        }

        if (tecnologias40Select) {
          tecnologias40Select.addEventListener('change', function () {
            municipio = municipioSelect ? municipioSelect.value : '';
            sector = sectorSelect ? sectorSelect.value : '';
            tecnologias40 = tecnologias40Select ? tecnologias40Select.value : '';

            //para todos los elementos que tengan la clase detalle-dump-data-link, agregar el valor de tecnologias40 al href
            const detalleDumpDataLinks = document.querySelectorAll('.detalle-dump-data-link');
            detalleDumpDataLinks.forEach(link => {
              link.href += `?municipio=${municipio}&sector=${sector}&tecnologia40=${tecnologias40}`;
            });

            console.log('Tecnologías 4.0 seleccionadas:', this.value);
            callData(municipio, sector, tecnologias40);

          });
        }

        // Event listener for clearing filters
        const clearFiltersButton = document.getElementById('clear-filters');
        if (clearFiltersButton) {
          clearFiltersButton.addEventListener('click', function () {
            document.getElementById('edit-municipio').value = '';
            document.getElementById('edit-sector').value = '';
            document.getElementById('edit-tecnologias-40').value = '';
            // Optionally trigger a change event if needed for other listeners
            document.getElementById('edit-municipio').dispatchEvent(new Event('change'));
            document.getElementById('edit-sector').dispatchEvent(new Event('change'));
            document.getElementById('edit-tecnologias-40').dispatchEvent(new Event('change'));
            // Call callData to refresh the dashboard with cleared filters
            callData('', '', '');
          });
        }
      });

      //
      // Event listener for the export button
      const exportButton = document.getElementById('export-pdf-button');
      if (exportButton) {
        exportButton.addEventListener('click', function () {
          const jsonData = localStorage.getItem('jsonData');
          if (jsonData) {
            fetch('/generate-pdf-from-json', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: jsonData,
            })
              .then(response => {
                if (!response.ok) {
                  throw new Error('Network response was not ok');
                }
                return response.json();
              })
              .then(data => {
                if (data.url) {
                  const url = data.url;
                  window.open(url, '_blank');
                  //alert('PDF generado y abierto en una nueva pestaña.');
                } else {
                  throw new Error('URL del PDF no encontrada en la respuesta.');
                }
              })
              .catch(error => {
                console.error('Error al generar el PDF:', error);
                alert('Hubo un error al generar el PDF.');
              });
          } else {
            alert('No hay datos de actores para exportar. Por favor, cargue los datos primero.');
          }
        });
      } // Closing brace for if (exportButton)





    }

  };
  function callData(municipio, sector, tecnologias40) {
    /*
     * pestaña de actores
     */

    //parametros card grupos de investigacion (minciencias)
    const grupos_investigacion_tabla = 'data_grupos_investigacion';
    const grupos_investigacion_elemento = ['actores_grupos_de_investigacion_value'];

    //parametros card investigadores reconocidos
    const card_investigadores_tabla = 'data_view_investigadores';
    const card_investigadores_elemento = ['actores_investigadores_reconocidos_value'];

    //parametros card centros de investigacion
    //se reutiliza en card generacion de conocimiento > centros de investigacion
    const card_centros_investigacion_tabla = 'data_view_centros_investigacion';
    const card_centros_investigacion_elemento = ['actores_centros_investigacion_value', 'generacion_conocimiento_centros_investigacion_value'];

    //parametros card centros de desarrollo tecnologico
    const card_centros_desarrollo_tecnologico_tabla = 'data_view_centros_desarrollo_tecnologico';
    const card_centros_desarrollo_tecnologico_elemento = ['actores_centros_desarrollo_tecnologico_value'];

    //parametros card centros de innovacion
    //se reutiliza en card generacion de conocimiento > centros de innovacion
    const card_centros_innovacion_tabla = 'data_view_centros_innovacion';
    const card_centros_innovacion_elemento = ['actores_centros_innovacion_value', 'generacion_conocimiento_centros_innovacion_value'];

    //parametros card centros de ciencia
    const card_centros_ciencia_tabla = 'data_view_centros_ciencia';
    const card_centros_ciencia_elemento = ['actores_centros_ciencia_value'];

    //parametros card centros institutos
    const card_centros_institutos_tabla = 'data_view_centros_institutos';
    const card_centros_institutos_elemento = ['actores_centros_institutos_value'];

    //parametros card empresas altamente innovadoras
    const card_empresas_altamente_innovadoras_tabla = 'data_view_empresas_altamente_innovadoras';
    const card_empresas_altamente_innovadoras_elemento = ['actores_empresas_altamente_innovadoras_value'];

    //parametros card empresas altamente innovadoras
    const card_empresas_incubadoras_tabla = 'data_view_empresas_incubadoras';
    const card_empresas_incubadoras_elemento = ['actores_empresas_incubadoras_value'];

    //parametros card unidades empresariales de I+D+i
    const card_empresas_unidades_idi_tabla = 'data_view_empresas_unidades_idi';
    const card_empresas_unidades_idi_elemento = ['empresas_unidades_idi_value'];

    //parametros card organizaciones intermedias otris
    const card_organizaciones_intermedias_otri_tabla = 'data_view_organizaciones_intermedias_otri';
    const card_organizaciones_intermedias_otri_elemento = ['actores_organizaciones_intermedias_otri_value'];

    //parametros card agentes gobierno entidades gobierno
    const card_entidades_gobierno_tabla = 'data_entidades_gobierno';
    const card_entidades_gobierno_elemento = ['actores_entidades_gobierno_value'];

    //parametros card agentes gobierno instancias orientacion politica
    const card_instancias_gobierno_tabla = 'data_instancias_gobierno';
    const card_instancias_gobierno_elemento = ['actores_instancias_gobierno_value'];

    //parametros card de actores por sector
    const card_actores_sector_tablas = 'data_empresas_tic-data_investigadores-data_view_centros_investigacion-data_view_centros_desarrollo_tecnologico-data_grupos_investigacion-data_view_centros_innovacion';
    const card_actores_sector_campo = 'sector';
    const card_actores_sector_elemento = 'actores_sector_economico_grouped_data';

    //parametros card de actores por municipio
    const card_actores_municipio_tablas = 'data_empresas_tic-data_investigadores-data_view_centros_investigacion-data_view_centros_desarrollo_tecnologico-data_grupos_investigacion-data_view_centros_innovacion';
    const card_actores_municipio_campo = 'municipio';
    const card_actores_municipio_elemento = 'actores_municipio_grouped_data';

    //parametros card de actores por tecnologia 4.0
    const card_actores_tecnologias40_tablas = 'data_empresas_tic-data_investigadores-data_view_centros_investigacion-data_view_centros_desarrollo_tecnologico-data_grupos_investigacion-data_view_centros_innovacion';
    const card_actores_tecnologias40_campo = 'tecnologia40';
    const card_actores_tecnologias40_elemento = 'actores_tecnologias40_grouped_data';

    /*
    * pestaña de actores zinco
    */
    const card_actores_zinco_empresas_altamente_innovadoras_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_empresas_altamente_innovadoras_bundle = 'empresa_explotadora_de_conocimie';
    const card_actores_zinco_empresas_altamente_innovadoras_elemento = 'zinco_actores_empresas_altamente_innovadoras_value';
    const card_actores_zinco_empresas_altamente_innovadoras_filters = {};

    const card_actores_zinco_startups_base_tecnologica_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_startups_base_tecnologica_bundle = 'startups_de_base_tecnologica';
    const card_actores_zinco_startups_base_tecnologica_elemento = 'zinco_actores_startups_base_tecnologica_value';
    const card_actores_zinco_startups_base_tecnologica_filters = {};

    const card_actores_zinco_unidades_empresariales_idi_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_unidades_empresariales_idi_bundle = 'unidades_empresariales_de_i_d_i';
    const card_actores_zinco_unidades_empresariales_idi_elemento = 'zinco_actores_unidades_empresariales_idi_value';
    const card_actores_zinco_unidades_empresariales_idi_filters = {};

    const card_actores_zinco_empresas_spin_off_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_empresas_spin_off_bundle = 'empresas_spin_off';
    const card_actores_zinco_empresas_spin_off_elemento = 'zinco_actores_empresas_spin_off_value';
    const card_actores_zinco_empresas_spin_off_filters = {};

    const card_actores_zinco_emprendimientos_dinamicos_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_emprendimientos_dinamicos_bundle = 'emprendimientos_dinamicos';
    const card_actores_zinco_emprendimientos_dinamicos_elemento = 'zinco_actores_emprendimientos_dinamicos_value';
    const card_actores_zinco_emprendimientos_dinamicos_filters = {};

    const card_actores_zinco_fondos_corporate_venture_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_fondos_corporate_venture_bundle = 'fondos_de_corporate_venture';
    const card_actores_zinco_fondos_corporate_venture_elemento = 'zinco_actores_fondos_corporate_venture_value';
    const card_actores_zinco_fondos_corporate_venture_filters = {};

    const card_actores_zinco_fondos_venture_capital_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_fondos_venture_capital_bundle = 'fondos_de_venture_capital';
    const card_actores_zinco_fondos_venture_capital_elemento = 'zinco_actores_fondos_venture_capital_value';
    const card_actores_zinco_fondos_venture_capital_filters = {};

    const card_actores_zinco_instituciones_microfinancieras_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_instituciones_microfinancieras_bundle = 'instituciones_microfinancieras';
    const card_actores_zinco_instituciones_microfinancieras_elemento = 'zinco_actores_instituciones_microfinancieras_value';
    const card_actores_zinco_instituciones_microfinancieras_filters = {};

    const card_actores_zinco_inversionistas_y_redes_de_inversionistas_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_inversionistas_y_redes_de_inversionistas_bundle = 'inversionistas_y_redes_de_invers';
    const card_actores_zinco_inversionistas_y_redes_de_inversionistas_elemento = 'zinco_actores_inversionistas_y_redes_de_inversionistas_value';
    const card_actores_zinco_inversionistas_y_redes_de_inversionistas_filters = {};

    const card_actores_zinco_plataformas_crowdfunding_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_plataformas_crowdfunding_bundle = 'plataformas_de_crowdfunding';
    const card_actores_zinco_plataformas_crowdfunding_elemento = 'zinco_actores_plataformas_crowdfunding_value';
    const card_actores_zinco_plataformas_crowdfunding_filters = {};

    const card_actores_zinco_empresas_generadoras_de_conocimiento_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_empresas_generadoras_de_conocimiento_bundle = 'empresa_generadora_de_conocimien';
    const card_actores_zinco_empresas_generadoras_de_conocimiento_elemento = 'zinco_actores_empresas_generadoras_de_conocimiento_value';
    const card_actores_zinco_empresas_generadoras_de_conocimiento_filters = {};

    const card_actores_zinco_instituciones_educacion_superior_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_instituciones_educacion_superior_bundle = 'institucion_de_educacion_superio';
    const card_actores_zinco_instituciones_educacion_superior_elemento = 'zinco_actores_instituciones_educacion_superior_value';
    const card_actores_zinco_instituciones_educacion_superior_filters = {};

    const card_actores_zinco_investigadores_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_investigadores_bundle = 'investigador';
    const card_actores_zinco_investigadores_elemento = 'zinco_actores_investigadores_value';
    const card_actores_zinco_investigadores_filters = {};

    const card_actores_zinco_grupos_investigacion_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_grupos_investigacion_bundle = 'grupos_de_investigacion';
    const card_actores_zinco_grupos_investigacion_elemento = 'zinco_actores_grupos_investigacion_value';
    const card_actores_zinco_grupos_investigacion_filters = {};

    const card_actores_zinco_centros_investigacion_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_centros_investigacion_bundle = 'centros_e_institutos_de_investig';
    const card_actores_zinco_centros_investigacion_elemento = 'zinco_actores_centros_investigacion_value';
    const card_actores_zinco_centros_investigacion_filters = {};

    const card_actores_zinco_centros_desarrollo_tecnologico_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_centros_desarrollo_tecnologico_bundle = 'centros_de_desarrollo_tecnologic';
    const card_actores_zinco_centros_desarrollo_tecnologico_elemento = 'zinco_actores_centros_desarrollo_tecnologico_value';
    const card_actores_zinco_centros_desarrollo_tecnologico_filters = {};

    const card_actores_zinco_entidad_publica_fomento_innovacion_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_entidad_publica_fomento_innovacion_bundle = 'entidad_gobierno';
    const card_actores_zinco_entidad_publica_fomento_innovacion_elemento = 'zinco_actores_entidad_publica_fomento_innovacion_value';
    const card_actores_zinco_entidad_publica_fomento_innovacion_filters = {};

    const card_actores_zinco_instancias_orientacion_politica_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_instancias_orientacion_politica_bundle = 'instancias_de_orientacion_politi';
    const card_actores_zinco_instancias_orientacion_politica_elemento = 'zinco_actores_instancias_orientacion_politica_value';
    const card_actores_zinco_instancias_orientacion_politica_filters = {};

    const card_actores_zinco_oficinas_transferencia_tecnologica_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_oficinas_transferencia_tecnologica_bundle = 'otri';
    const card_actores_zinco_oficinas_transferencia_tecnologica_elemento = 'zinco_actores_oficinas_transferencia_tecnologica_value';
    const card_actores_zinco_oficinas_transferencia_tecnologica_filters = {};

    const card_actores_zinco_parque_tecnologico_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_parque_tecnologico_bundle = 'parque_tecnologico';
    const card_actores_zinco_parque_tecnologico_elemento = 'zinco_actores_parque_tecnologico_value';
    const card_actores_zinco_parque_tecnologico_filters = {};

    const card_actores_zinco_catis_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_catis_bundle = 'cati';
    const card_actores_zinco_catis_elemento = 'zinco_actores_catis_value';
    const card_actores_zinco_catis_filters = {};

    const card_actores_zinco_centros_innovacion_productividad_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_centros_innovacion_productividad_bundle = 'centros_de_innovacion_y_producti';
    const card_actores_zinco_centros_innovacion_productividad_elemento = 'zinco_actores_centros_innovacion_productividad_value';
    const card_actores_zinco_centros_innovacion_productividad_filters = {};

    const card_actores_zinco_incubadoras_empresas_base_tecnologica_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_incubadoras_empresas_base_tecnologica_bundle = 'incubadora';
    const card_actores_zinco_incubadoras_empresas_base_tecnologica_elemento = 'zinco_actores_incubadoras_empresas_base_tecnologica_value';
    const card_actores_zinco_incubadoras_empresas_base_tecnologica_filters = {};

    const card_actores_zinco_centros_emprendimiento_ies_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_centros_emprendimiento_ies_bundle = 'centros_de_emprendimiento_ies';
    const card_actores_zinco_centros_emprendimiento_ies_elemento = 'zinco_actores_centros_emprendimiento_ies_value';
    const card_actores_zinco_centros_emprendimiento_ies_filters = {};

    const card_actores_zinco_redes_mentores_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_redes_mentores_bundle = 'redes_de_mentores';
    const card_actores_zinco_redes_mentores_elemento = 'zinco_actores_redes_mentores_value';
    const card_actores_zinco_redes_mentores_filters = {};

    const card_actores_zinco_aceleradoras_entidad = 'zinco_actors_zincoactors';
    const card_actores_zinco_aceleradoras_bundle = 'aceleradora';
    const card_actores_zinco_aceleradoras_elemento = 'zinco_actores_aceleradoras_value';
    const card_actores_zinco_aceleradoras_filters = {};

    /*
    * pestaña de formacion
    */

    //parametros card universidades
    const card_universidades_tabla = 'data_instituciones_academicas';
    const card_universidades_elemento = ['formacion_universidades_value'];

    //parametros card programas ofertados
    const card_programas_ofertados_tabla = 'data_programas_academicos';
    const card_programas_ofertados_elemento = ['formacion_programas_academicos_value'];

    //parametros card de numero de programas academicos por sector
    const card_formacion_programas_academicos_sectores_tablas = 'data_programas_academicos';
    const card_formacion_programas_academicos_sectores_campo = 'nivel_formacion';
    const card_formacion_programas_academicos_sectores_elemento = 'formacion_programas_academicos_sectores_grouped_data';

    //parametros card de numero de programas academicos por municipio
    const card_formacion_programas_academicos_municipios_tablas = 'data_programas_academicos';
    const card_formacion_programas_academicos_municipios_campo = 'municipio';
    const card_formacion_programas_academicos_municipios_elemento = 'formacion_programas_academicos_municipios_grouped_data';

    //parametros card de numero de programas academicos por eacin
    const card_formacion_programas_academicos_eacin_tablas = 'data_programas_academicos';
    const card_formacion_programas_academicos_eacin_campo = 'relacion_sector_40';
    const card_formacion_programas_academicos_eacin_elemento = 'formacion_programas_academicos_eacin_grouped_data';

    /*
   * pestaña de retos
   */
    //parametros card retos abiertos
    const card_retos_entidad = 'zinco_retos_innovacion';
    const card_retos_elemento = 'retos_abiertos_value';
    const card_retos_filters = { 'estado_reto_innovacion': 'Abierto' };

    //parametros card retos en evaluacion
    const card_retos_en_evaluacion_entidad = 'zinco_retos_innovacion';
    const card_retos_en_evaluacion_elemento = 'retos_en_evaluacion_value';
    const card_retos_en_evaluacion_filters = { 'estado_reto_innovacion': 'En evaluación' };

    //parametros card retos finalizados
    const card_retos_finalizados_entidad = 'zinco_retos_innovacion';
    const card_retos_finalizados_elemento = 'retos_finalizados_value';
    const card_retos_finalizados_filters = { 'estado_reto_innovacion': 'Cumplido' };

    //parametros card retos por sectores
    const card_retos_sectores_entidad = 'zinco_retos_innovacion';
    const card_retos_sectores_campo = 'sector';
    const card_retos_sectores_elemento = 'retos_sectores_grouped_data';

    //parametros card retos por tecnologia
    const card_retos_tecnologias_entidad = 'zinco_retos_innovacion';
    const card_retos_tecnologias_campo = 'tecnologia40';
    const card_retos_tecnologias_elemento = 'retos_tecnologias_grouped_data';

    /*
   * pestaña de proyectos
   */
    //parametros card proyectos propuestos
    const card_proyectos_propuestos_entidad = 'zinco_proyectos_idi';
    const card_proyectos_propuestos_elemento = 'proyectos_propuestos_value';
    const card_proyectos_propuestos_filters = { 'estado_proyecto': 'Propuesto' };


    //parametros card proyectos en ejecucion
    const card_proyectos_ejecucion_entidad = 'zinco_proyectos_idi';
    const card_proyectos_ejecucion_elemento = 'proyectos_ejecucion_value';
    const card_proyectos_ejecucion_filters = { 'estado_proyecto': 'En ejecución' };

    //parametros card proyectos finalizados
    const card_proyectos_finalizados_entidad = 'zinco_proyectos_idi';
    const card_proyectos_finalizados_elemento = 'proyectos_finalizados_value';
    const card_proyectos_finalizados_filters = { 'estado_proyecto': 'Finalizado' };

    //parametros card proyectos por sector
    const card_proyectos_sectores_entidad = 'zinco_proyectos_idi';
    const card_proyectos_sectores_campo = 'sector';
    const card_proyectos_sectores_elemento = 'proyectos_sectores_grouped_data';

    /*
   * pestaña de protección intelectual
   */
    //parametros card registros software
    const card_registros_software_tabla = 'data_software_registrados';
    const card_registros_software_elemento = ['registros_software_value'];

    /*
   * pestaña de generacion de conocimiento
   */
    //parametros card produccion total
    const card_produccion_total_tabla = 'data_produccion_cientifica';
    const card_produccion_total_elemento = ['produccion_total_value'];

    //parametros card centros cdt
    const card_centros_cdt_tabla = 'data_view_centros_desarrollo_tecnologico';
    const card_centros_cdt_elemento = ['generacion_conocimiento_centros_cdt_value'];

    //parametros card grupos investigacion por categoria
    const card_grupos_investigacion_categorias_tablas = 'data_grupos_investigacion';
    const card_grupos_investigacion_categorias_campo = 'categoria_minciencias';
    const card_grupos_investgacion_categorias_elemento = 'generacion_conocimiento_grupos_investigacion_categorias_grouped_data';

    /*
   * pestaña de software
   */
    //parametros card software activos
    const card_software_activos_tabla = 'data_grupos_investigacion';
    const card_software_activos_elemento = ['software_activos_value'];
    const card_software_activos_campo_acumulativo = 'gruplac_total_softwares';

    //parametros card software en desarrollo
    const card_software_desarrollo_entidad = 'zinco_proyectos_software';
    const card_software_desarrollo_elemento = 'software_desarrollo_value';
    const card_software_desarrollo_filters = { 'estado_proyecto': 'En desarrollo' };

    //parametros card software finalizados
    const card_software_finalizado_entidad = 'zinco_proyectos_software';
    const card_software_finalizado_elemento = 'software_finalizado_value';
    const card_software_finalizado_filters = { 'estado_proyecto': 'Finalizado' };

    //parametros card recursos captados
    const card_software_recursos_captados_entidad = 'zinco_software_recursos';
    const card_software_recursos_captados_elemento = 'software_recursos_captados_value';
    const card_software_recursos_captados_filters = {};

    //parametros card eventos
    const card_software_eventos_entidad = 'node';
    //const card_software_eventos_bundle = 'evento';
    const card_software_eventos_elemento = 'software_eventos_value';
    const card_software_eventos_filters = { 'tipo_evento': 'Aceleradora-Incubadora' };

    //parametros card software por fuente de financiacion
    const card_software_fuentes_financiacion_entidad = 'zinco_software_recursos';
    const card_software_fuentes_financiacion_campo = 'fuente_financiacion';
    const card_software_fuentes_financiacion_elemento = 'software_fuentes_financiacion_grouped_data';

    /*
    * pestaña de empresas
    */

    // //parametros card empresas altamente innovadoras
    // const card_empresas_altamente_innovadoras_tabla = 'data_view_empresas_altamente_innovadoras';
    // const card_empresas_altamente_innovadoras_elemento = ['empresas_altamente_innovadoras_value'];



    //parametros card startups
    const card_empresas_startups_entidad = 'zinco_reconocimientos';
    const card_empresas_startups_elemento = 'empresas_startups_value';
    const card_empresas_startups_filters = { 'bundle': 'startup' };

    //parametros card spinoffs
    const card_empresas_spinoffs_entidad = 'zinco_reconocimientos';
    const card_empresas_spinoffs_elemento = 'empresas_spinoffs_value';
    const card_empresas_spinoffs_filters = { 'bundle': 'spin_off' };

    //parametros card empresas por sectores
    const card_empresas_sectores_tablas = 'data_empresas_tic';
    const card_empresas_sectores_campo = 'sector';
    const card_empresas_sectores_elemento = 'empresas_sectores_grouped_data';

    //parametros card empresas por tecnologias
    const card_empresas_tecnologias_tablas = 'data_empresas_tic';
    const card_empresas_tenologias_campo = 'tecnologia40';
    const card_empresas_tecnologias_elemento = 'empresas_tecnologias_grouped_data';

    /*
    * pestaña de produccion cientifica
    */

    //parametros card articulos de investigacion
    const card_articulos_investigacion_tabla = 'data_view_articulos_investigacion';
    const card_articulos_investigacion_elemento = ['articulos_investigacion_value'];

    //parametros card articulos de investigacion
    const card_patentes_tabla = 'data_view_patentes';
    const card_patentes_elemento = ['patentes_value'];

    //parametros card productos tecnologicos
    const card_productos_tecnologicos_tabla = 'data_view_productos_tecnologicos';
    const card_productos_tecnologicos_elemento = ['productos_tecnologicos_value'];

    //parametros card consultorias
    const card_consultorias_tabla = 'data_view_consultorias';
    const card_consultorias_elemento = ['consultorias_value'];

    //parametros card tipos de consultorias
    const card_tipos_consultorias_tablas = 'data_view_consultorias';
    const card_tipos_consultorias_campo = 'NME_TIPOLOGIA_PD';
    const card_tipos_consultorias_elemento = 'tipos_consultorias_grouped_data';

    //parametros card tipos de consultorias
    const card_productos_tecnologicos_grouped_tablas = 'data_view_productos_tecnologicos';
    const card_productos_tecnologicos_grouped_campo = 'NME_TIPOLOGIA_PD';
    const card_productos_tecnologicos_grouped_elemento = 'productos_tecnologicos_grouped_data';




    // Initial call to load data when the page loads, with default or empty values.
    const dataLoadPromises = [
      // pestaña de actores
      loadData(municipio, sector, tecnologias40, grupos_investigacion_tabla, grupos_investigacion_elemento),
      loadData(municipio, sector, tecnologias40, card_investigadores_tabla, card_investigadores_elemento),
      loadData(municipio, sector, tecnologias40, card_centros_investigacion_tabla, card_centros_investigacion_elemento),
      loadData(municipio, sector, tecnologias40, card_centros_desarrollo_tecnologico_tabla, card_centros_desarrollo_tecnologico_elemento),
      loadData(municipio, sector, tecnologias40, card_centros_innovacion_tabla, card_centros_innovacion_elemento),
      loadData(municipio, sector, tecnologias40, card_centros_ciencia_tabla, card_centros_ciencia_elemento),
      loadData(municipio, sector, tecnologias40, card_centros_institutos_tabla, card_centros_institutos_elemento),
      loadData(municipio, sector, tecnologias40, card_empresas_altamente_innovadoras_tabla, card_empresas_altamente_innovadoras_elemento),
      loadData(municipio, sector, tecnologias40, card_empresas_incubadoras_tabla, card_empresas_incubadoras_elemento),
      loadData(municipio, sector, tecnologias40, card_empresas_unidades_idi_tabla, card_empresas_unidades_idi_elemento),
      loadData(municipio, sector, tecnologias40, card_organizaciones_intermedias_otri_tabla, card_organizaciones_intermedias_otri_elemento),
      loadData(municipio, sector, tecnologias40, card_entidades_gobierno_tabla, card_entidades_gobierno_elemento),
      loadData(municipio, sector, tecnologias40, card_instancias_gobierno_tabla, card_instancias_gobierno_elemento),
      loadMultipleGroupedData(municipio, sector, tecnologias40, card_actores_sector_tablas, card_actores_sector_campo, card_actores_sector_elemento),
      loadMultipleGroupedData(municipio, sector, tecnologias40, card_actores_municipio_tablas, card_actores_municipio_campo, card_actores_municipio_elemento),
      loadMultipleGroupedData(municipio, sector, tecnologias40, card_actores_tecnologias40_tablas, card_actores_tecnologias40_campo, card_actores_tecnologias40_elemento),

      //pestaña de actores de zinco
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_empresas_altamente_innovadoras_entidad, card_actores_zinco_empresas_altamente_innovadoras_bundle, card_actores_zinco_empresas_altamente_innovadoras_elemento, card_actores_zinco_empresas_altamente_innovadoras_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_startups_base_tecnologica_entidad, card_actores_zinco_startups_base_tecnologica_bundle, card_actores_zinco_startups_base_tecnologica_elemento, card_actores_zinco_startups_base_tecnologica_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_unidades_empresariales_idi_entidad, card_actores_zinco_unidades_empresariales_idi_bundle, card_actores_zinco_unidades_empresariales_idi_elemento, card_actores_zinco_unidades_empresariales_idi_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_empresas_spin_off_entidad, card_actores_zinco_empresas_spin_off_bundle, card_actores_zinco_empresas_spin_off_elemento, card_actores_zinco_empresas_spin_off_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_emprendimientos_dinamicos_entidad, card_actores_zinco_emprendimientos_dinamicos_bundle, card_actores_zinco_emprendimientos_dinamicos_elemento, card_actores_zinco_emprendimientos_dinamicos_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_fondos_corporate_venture_entidad, card_actores_zinco_fondos_corporate_venture_bundle, card_actores_zinco_fondos_corporate_venture_elemento, card_actores_zinco_fondos_corporate_venture_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_fondos_venture_capital_entidad, card_actores_zinco_fondos_venture_capital_bundle, card_actores_zinco_fondos_venture_capital_elemento, card_actores_zinco_fondos_venture_capital_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_instituciones_microfinancieras_entidad, card_actores_zinco_instituciones_microfinancieras_bundle, card_actores_zinco_instituciones_microfinancieras_elemento, card_actores_zinco_instituciones_microfinancieras_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_inversionistas_y_redes_de_inversionistas_entidad, card_actores_zinco_inversionistas_y_redes_de_inversionistas_bundle, card_actores_zinco_inversionistas_y_redes_de_inversionistas_elemento, card_actores_zinco_inversionistas_y_redes_de_inversionistas_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_plataformas_crowdfunding_entidad, card_actores_zinco_plataformas_crowdfunding_bundle, card_actores_zinco_plataformas_crowdfunding_elemento, card_actores_zinco_plataformas_crowdfunding_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_empresas_generadoras_de_conocimiento_entidad, card_actores_zinco_empresas_generadoras_de_conocimiento_bundle, card_actores_zinco_empresas_generadoras_de_conocimiento_elemento, card_actores_zinco_empresas_generadoras_de_conocimiento_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_instituciones_educacion_superior_entidad, card_actores_zinco_instituciones_educacion_superior_bundle, card_actores_zinco_instituciones_educacion_superior_elemento, card_actores_zinco_instituciones_educacion_superior_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_investigadores_entidad, card_actores_zinco_investigadores_bundle, card_actores_zinco_investigadores_elemento, card_actores_zinco_investigadores_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_grupos_investigacion_entidad, card_actores_zinco_grupos_investigacion_bundle, card_actores_zinco_grupos_investigacion_elemento, card_actores_zinco_grupos_investigacion_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_centros_investigacion_entidad, card_actores_zinco_centros_investigacion_bundle, card_actores_zinco_centros_investigacion_elemento, card_actores_zinco_centros_investigacion_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_centros_desarrollo_tecnologico_entidad, card_actores_zinco_centros_desarrollo_tecnologico_bundle, card_actores_zinco_centros_desarrollo_tecnologico_elemento, card_actores_zinco_centros_desarrollo_tecnologico_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_entidad_publica_fomento_innovacion_entidad, card_actores_zinco_entidad_publica_fomento_innovacion_bundle, card_actores_zinco_entidad_publica_fomento_innovacion_elemento, card_actores_zinco_entidad_publica_fomento_innovacion_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_instancias_orientacion_politica_entidad, card_actores_zinco_instancias_orientacion_politica_bundle, card_actores_zinco_instancias_orientacion_politica_elemento, card_actores_zinco_instancias_orientacion_politica_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_oficinas_transferencia_tecnologica_entidad, card_actores_zinco_oficinas_transferencia_tecnologica_bundle, card_actores_zinco_oficinas_transferencia_tecnologica_elemento, card_actores_zinco_oficinas_transferencia_tecnologica_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_parque_tecnologico_entidad, card_actores_zinco_parque_tecnologico_bundle, card_actores_zinco_parque_tecnologico_elemento, card_actores_zinco_parque_tecnologico_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_catis_entidad, card_actores_zinco_catis_bundle, card_actores_zinco_catis_elemento, card_actores_zinco_catis_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_centros_innovacion_productividad_entidad, card_actores_zinco_centros_innovacion_productividad_bundle, card_actores_zinco_centros_innovacion_productividad_elemento, card_actores_zinco_centros_innovacion_productividad_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_incubadoras_empresas_base_tecnologica_entidad, card_actores_zinco_incubadoras_empresas_base_tecnologica_bundle, card_actores_zinco_incubadoras_empresas_base_tecnologica_elemento, card_actores_zinco_incubadoras_empresas_base_tecnologica_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_centros_emprendimiento_ies_entidad, card_actores_zinco_centros_emprendimiento_ies_bundle, card_actores_zinco_centros_emprendimiento_ies_elemento, card_actores_zinco_centros_emprendimiento_ies_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_redes_mentores_entidad, card_actores_zinco_redes_mentores_bundle, card_actores_zinco_redes_mentores_elemento, card_actores_zinco_redes_mentores_filters),
      loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_aceleradoras_entidad, card_actores_zinco_aceleradoras_bundle, card_actores_zinco_aceleradoras_elemento, card_actores_zinco_aceleradoras_filters),

      // pestaña de formacion
      loadData(municipio, sector, tecnologias40, card_universidades_tabla, card_universidades_elemento),
      loadData(municipio, sector, tecnologias40, card_programas_ofertados_tabla, card_programas_ofertados_elemento),
      loadMultipleGroupedData(municipio, sector, tecnologias40, card_formacion_programas_academicos_sectores_tablas, card_formacion_programas_academicos_sectores_campo, card_formacion_programas_academicos_sectores_elemento),
      loadMultipleGroupedData(municipio, sector, tecnologias40, card_formacion_programas_academicos_municipios_tablas, card_formacion_programas_academicos_municipios_campo, card_formacion_programas_academicos_municipios_elemento),
      loadMultipleGroupedData(municipio, sector, tecnologias40, card_formacion_programas_academicos_eacin_tablas, card_formacion_programas_academicos_eacin_campo, card_formacion_programas_academicos_eacin_elemento),

      // pestaña de retos
      loadEntityData(municipio, sector, tecnologias40, card_retos_entidad, card_retos_elemento, card_retos_filters),
      loadEntityData(municipio, sector, tecnologias40, card_retos_en_evaluacion_entidad, card_retos_en_evaluacion_elemento, card_retos_en_evaluacion_filters),
      loadEntityData(municipio, sector, tecnologias40, card_retos_finalizados_entidad, card_retos_finalizados_elemento, card_retos_finalizados_filters),
      loadEntityGroupedData(municipio, sector, tecnologias40, card_retos_sectores_entidad, card_retos_sectores_elemento, card_retos_sectores_campo),
      loadEntityGroupedData(municipio, sector, tecnologias40, card_retos_tecnologias_entidad, card_retos_tecnologias_elemento, card_retos_tecnologias_campo),

      // pestaña de proyectos
      loadEntityData(municipio, sector, tecnologias40, card_proyectos_propuestos_entidad, card_proyectos_propuestos_elemento, card_proyectos_propuestos_filters),
      loadEntityData(municipio, sector, tecnologias40, card_proyectos_ejecucion_entidad, card_proyectos_ejecucion_elemento, card_proyectos_ejecucion_filters),
      loadEntityData(municipio, sector, tecnologias40, card_proyectos_finalizados_entidad, card_proyectos_finalizados_elemento, card_proyectos_finalizados_filters),
      loadEntityGroupedData(municipio, sector, tecnologias40, card_proyectos_sectores_entidad, card_proyectos_sectores_elemento, card_proyectos_sectores_campo),

      // pestaña de proteccion intelectual
      loadData(municipio, sector, tecnologias40, card_registros_software_tabla, card_registros_software_elemento),

      // pestaña de generacion de conocimiento
      loadData(municipio, sector, tecnologias40, card_produccion_total_tabla, card_produccion_total_elemento),
      loadData(municipio, sector, tecnologias40, card_centros_cdt_tabla, card_centros_cdt_elemento),
      loadMultipleGroupedData(municipio, sector, tecnologias40, card_grupos_investigacion_categorias_tablas, card_grupos_investigacion_categorias_campo, card_grupos_investgacion_categorias_elemento),

      // pestaña de software
      loadAccumulativeData(municipio, sector, tecnologias40, card_software_activos_tabla, card_software_activos_campo_acumulativo, card_software_activos_elemento),
      loadEntityData(municipio, sector, tecnologias40, card_software_desarrollo_entidad, card_software_desarrollo_elemento, card_software_desarrollo_filters),
      loadEntityData(municipio, sector, tecnologias40, card_software_finalizado_entidad, card_software_finalizado_elemento, card_software_finalizado_filters),
      loadEntityData(municipio, sector, tecnologias40, card_software_recursos_captados_entidad, card_software_recursos_captados_elemento, card_software_recursos_captados_filters, 'monto_obtenido'),
      loadEntityData(municipio, sector, tecnologias40, card_software_eventos_entidad, card_software_eventos_elemento, card_software_eventos_filters),
      loadEntityGroupedData(municipio, sector, tecnologias40, card_software_fuentes_financiacion_entidad, card_software_fuentes_financiacion_elemento, card_software_fuentes_financiacion_campo),

      // pestaña de empresas


      loadEntityData(municipio, sector, tecnologias40, card_empresas_startups_entidad, card_empresas_startups_elemento, card_empresas_startups_filters),
      loadEntityData(municipio, sector, tecnologias40, card_empresas_spinoffs_entidad, card_empresas_spinoffs_elemento, card_empresas_spinoffs_filters),
      loadMultipleGroupedData(municipio, sector, tecnologias40, card_empresas_sectores_tablas, card_empresas_sectores_campo, card_empresas_sectores_elemento),
      loadMultipleGroupedData(municipio, sector, tecnologias40, card_empresas_tecnologias_tablas, card_empresas_tenologias_campo, card_empresas_tecnologias_elemento),

      // pestaña de produccion cientifica
      loadData(municipio, sector, tecnologias40, card_articulos_investigacion_tabla, card_articulos_investigacion_elemento),
      loadData(municipio, sector, tecnologias40, card_patentes_tabla, card_patentes_elemento),
      loadData(municipio, sector, tecnologias40, card_productos_tecnologicos_tabla, card_productos_tecnologicos_elemento),
      loadData(municipio, sector, tecnologias40, card_consultorias_tabla, card_consultorias_elemento),
      loadMultipleGroupedData(municipio, sector, tecnologias40, card_tipos_consultorias_tablas, card_tipos_consultorias_campo, card_tipos_consultorias_elemento),
      loadMultipleGroupedData(municipio, sector, tecnologias40, card_productos_tecnologicos_grouped_tablas, card_productos_tecnologicos_grouped_campo, card_productos_tecnologicos_grouped_elemento),
    ];


    // const dataLoadPromises2 = [
    //   loadBundleData(municipio, sector, tecnologias40, card_actores_zinco_unidades_empresariales_idi_entidad, card_actores_zinco_unidades_empresariales_idi_bundle, card_actores_zinco_unidades_empresariales_idi_elemento, card_actores_zinco_unidades_empresariales_idi_filters),

    // ];

    Promise.all(dataLoadPromises)
      .then(() => {
        console.log('All dashboard data loaded successfully!');
        const jsonData = getDataAsJson();
        console.log(jsonData);
        localStorage.setItem('jsonData', JSON.stringify(jsonData));
      })
      .catch(error => {
        console.error('Error loading some dashboard data:', error);
      });


  };


  function loadData(municipio, sector, tecnologia40, tabla, elementos) {
    return new Promise((resolve, reject) => {
      const url = `/dump-data/${tabla}/json?municipio=${municipio}&sector=${sector}&tecnologia40=${tecnologia40}`;
      fetch(url)
        .then(response => response.json())
        .then(data => {
          for (const elementoId of elementos) {
            const countElement = document.getElementById(elementoId);
            console.log(countElement);
            if (countElement) {
              console.log(data.length);
              countElement.textContent = data.length;
            }
            console.log(countElement.textContent);
          }

          resolve(data.length); // Resolve the promise with the data length
        })
        .catch(error => {
          console.error('Error al cargar ' + tabla, error);
          reject(error); // Reject the promise on error
        });
    });
  }

  function loadAccumulativeData(municipio, sector, tecnologia40, tabla, campo_acumulativo, elementos) {
    return new Promise((resolve, reject) => {
      const url = `/dump-tabla-campo-acumulativo/${tabla}/${campo_acumulativo}/json?municipio=${municipio}&sector=${sector}&tecnologia40=${tecnologia40}`;
      fetch(url)
        .then(response => response.json())
        .then(data => {
          for (const elementoId of elementos) {
            const countElement = document.getElementById(elementoId);
            if (countElement) {
              countElement.textContent = data.sum;
            }
          }
          resolve(data.sum); // Resolve the promise with the sum
        })
        .catch(error => {
          console.error('Error al cargar datos:', error);
          reject(error); // Reject the promise on error
        });
    });
  }


  function loadMultipleGroupedData(municipio, sector, tecnologia40, tablas, campo, elemento) {
    return new Promise((resolve, reject) => {
      const url = `/dump-multiple-grouped-data/${tablas}/${campo}/json?municipio=${municipio}&sector=${sector}&tecnologia40=${tecnologia40}`;
      fetch(url)
        .then(response => response.json())
        .then(data => {
          const parentElement = document.getElementById(elemento);
          if (parentElement) {
            const groupedDataWrapper = parentElement.querySelector('.grouped_data_wrapper');
            if (groupedDataWrapper) {
              groupedDataWrapper.innerHTML = ''; // Clear previous content
              //console.log(data);
              for (key in data.consolidated) {
                const item = data.consolidated[key];
                const html = `
                      <div class="d-flex align-items-center mb-2">
                        <div class="progress flex-grow-1 me-2" style="height: 15px;">
                          <div class="progress-bar bg-secondary" role="progressbar" style="width: ${item.percentage}%;" aria-valuenow="${item.percentage}" aria-valuemin="0" aria-valuemax="${100}"></div>
                        </div>
                        <span class="" data-group-column="${item.group_column}">${item.group_column.substring(0, 5)}... (${item.count})</span>
                      </div>
                    `;
                groupedDataWrapper.insertAdjacentHTML('beforeend', html);


                //console.log(item);
              }
            }
          }
          resolve(data); // Resolve the promise with the fetched data
        })
        .catch(error => {
          console.error('Error al cargar grupos de investigación:', error);
          reject(error); // Reject the promise on error
        });
    });
  }

  function loadGroupedData(municipio, sector, tecnologia40, tabla, campo, elemento) {
    const url = `/dump-grouped-data/${tabla}/${campo}/json?municipio=${municipio}&sector=${sector}&tecnologia40=${tecnologia40}`;
    fetch(url)
      .then(response => response.json())
      .then(data => {
        const parentElement = document.getElementById(elemento);
        if (parentElement) {
          const groupedDataWrapper = parentElement.querySelector('.grouped_data_wrapper');
          if (groupedDataWrapper) {
            groupedDataWrapper.innerHTML = ''; // Clear previous content

            data.forEach(item => {
              const html = `
                <div class="d-flex align-items-center mb-2">
                  <div class="progress flex-grow-1 me-2" style="height: 15px;">
                    <div class="progress-bar bg-info" role="progressbar" style="width: ${item.percentage}%;" aria-valuenow="${item.percentage}" aria-valuemin="0" aria-valuemax="${100}"></div>
                  </div>
                  <span class="" data-group-column="${item.group_column}">${item.group_column.substring(0, 5)}... (${item.count})</span>
                </div>
              `;
              groupedDataWrapper.insertAdjacentHTML('beforeend', html);
            });
          }
        }
      })
      .catch(error => {
        console.error('Error al cargar grupos de investigación:', error);
      });
  }

  function loadEntityData(municipio, sector, tecnologia40, entity, elemento, filters, campo_acumulativo = null) {
    return new Promise((resolve, reject) => {
      let url = `/dump-entity/${entity}/json`;
      const params = new URLSearchParams();
      if (municipio) params.append('municipio', municipio);
      if (sector) params.append('sector', sector);
      if (tecnologia40) params.append('tecnologia40', tecnologia40);

      for (const key in filters) {
        if (filters.hasOwnProperty(key) && filters[key]) {
          params.append(key, filters[key]);
        }
      }
      if (params.toString()) {
        url += `?${params.toString()}`;
      }
      fetch(url)
        .then(response => response.json())
        .then(data => {
          const countElement = document.getElementById(elemento);
          if (countElement) {
            if (campo_acumulativo && data.length > 0) {
              const sum = data.reduce((acc, item) => parseInt(acc) + (parseInt(item[campo_acumulativo]) || 0), 0);
              countElement.textContent = sum;
              resolve(sum); // Resolve with the sum
            } else {
              countElement.textContent = data.length;
              resolve(data.length); // Resolve with the data length
            }
          } else {
            resolve(data); // Resolve with the data if no countElement
          }
        })
        .catch(error => {
          console.error('Error al cargar datos:', error);
          reject(error); // Reject the promise on error
        });
    });
  }

  function loadEntityGroupedData(municipio, sector, tecnologia40, entity, elemento, campo, filters) {
    return new Promise((resolve, reject) => {
      let url = `/dump-entity/${entity}/json`;
      const params = new URLSearchParams();
      if (municipio) params.append('municipio', municipio);
      if (sector) params.append('sector', sector);
      if (tecnologia40) params.append('tecnologia40', tecnologia40);

      for (const key in filters) {
        if (filters.hasOwnProperty(key) && filters[key]) {
          params.append(key, filters[key]);
        }
      }
      if (params.toString()) {
        url += `?${params.toString()}`;
      }
      fetch(url)
        .then(response => response.json())
        .then(data => {
          if (campo) { // Group data by 'campo' if specified
            const groupedData = data.reduce((acc, item) => {
              const key = item[campo];
              acc[key] = (acc[key] || 0) + 1;
              return acc;
            }, {});

            const totalCount = data.length;
            const consolidated = [];

            for (const key in groupedData) {
              if (groupedData.hasOwnProperty(key)) {
                const count = groupedData[key];
                const percentage = (count / totalCount) * 100;
                consolidated.push({
                  group_column: key,
                  count: count,
                  percentage: percentage
                });
              }
            }

            const parentElement = document.getElementById(elemento);
            if (parentElement) {
              const groupedDataWrapper = parentElement.querySelector('.grouped_data_wrapper');
              if (groupedDataWrapper) {
                groupedDataWrapper.innerHTML = ''; // Clear previous content

                consolidated.forEach(item => {
                  const html = `
                    <div class="d-flex align-items-center mb-2">
                      <div class="progress flex-grow-1 me-2" style="height: 15px;">
                        <div class="progress-bar bg-secondary" role="progressbar" style="width: ${item.percentage}%;" aria-valuenow="${item.percentage}" aria-valuemin="0" aria-valuemax="${100}"></div>
                      </div>
                      <span class="" data-group-column="${item.group_column}">${item.group_column.substring(0, 5)}... (${item.count})</span>
                    </div>
                  `;
                  groupedDataWrapper.insertAdjacentHTML('beforeend', html);
                });
              }
            }
            resolve(consolidated); // Resolve with the consolidated data
          } else {
            resolve(data); // Resolve with the raw data if no grouping
          }
        })
        .catch(error => {
          console.error('Error al cargar datos:', error);
          reject(error); // Reject the promise on error
        });
    });
  }

  function loadBundleData(municipio, sector, tecnologia40, entity, bundle, elemento, filters, campo_acumulativo = null) {
    let url = `/dump-entity-bundle/${entity}/${bundle}/json`;
    const params = new URLSearchParams();
    if (municipio) params.append('municipio', municipio);
    if (sector) params.append('sector', sector);
    if (tecnologia40) params.append('tecnologia40', tecnologia40);

    for (const key in filters) {
      if (filters.hasOwnProperty(key) && filters[key]) {
        params.append(key, filters[key]);
      }
    }
    if (params.toString()) {
      url += `?${params.toString()}`;
    }
    fetch(url)
      .then(response => response.json())
      .then(data => {
        const countElement = document.getElementById(elemento);
        console.log(data.length);
        if (countElement) {
          if (campo_acumulativo && data.length > 0) {
            const sum = data.reduce((acc, item) => parseInt(acc) + (parseInt(item[campo_acumulativo]) || 0), 0);
            countElement.textContent = sum;
          } else {
            countElement.textContent = data.length;
          }
        }
      })
      .catch(error => {
        console.error('Error al cargar datos:', error);
      });
  }

  /**
   * Extracts data from the "Actores" tab in the HTML and returns it as a JSON object.
   * Assumes a structure where data is within elements with specific IDs or classes.
   *
   * @returns {object} The extracted data as a JSON object.
   */
  function getDataAsJson() {
    // Helper to get text content of an element by ID and convert to number.
    const getNumericValue = (id) => {
      const element = document.getElementById(id);
      return element ? parseInt(element.textContent) > 0 ? parseInt(element.textContent) : 0 : 0;
    };

    // Helper to extract data from tables with 'nombre' and 'valor' columns.
    const extractTableData = (tableId) => {
      const parentElement = document.getElementById(tableId);
      const data = [];
      if (parentElement) {
        const groupedDataWrapper = parentElement.querySelector('.grouped_data_wrapper');
        if (groupedDataWrapper) {
          const dataItems = groupedDataWrapper.querySelectorAll('.d-flex.align-items-center.mb-2');
          dataItems.forEach(item => {
            const spanElement = item.querySelector('span');
            if (spanElement) {
              const nombre = spanElement.getAttribute('data-group-column');
              const textContent = spanElement.textContent.trim();
              const match = textContent.match(/\((\d+)\)/);
              const valor = match ? parseInt(match[1], 10) : 0;
              data.push({
                nombre: nombre,
                valor: valor
              });
            }
          });
        }
      }
      return data;
    };

    // Get filter values directly from the DOM
    const municipioSelect = document.getElementById('edit-municipio');
    const sectorSelect = document.getElementById('edit-sector');
    const tecnologias40Select = document.getElementById('edit-tecnologias-40');

    const municipio = municipioSelect ? municipioSelect.value : '';
    const sector = sectorSelect ? sectorSelect.value : '';
    const tecnologias40 = tecnologias40Select ? tecnologias40Select.value : '';

    const actoresData = {};

    // Extract simple key-value pairs.
    actoresData.grupos_de_investigacion_minciencias = getNumericValue('actores_grupos_de_investigacion_value');
    actoresData.investigadores_reconocidos = getNumericValue('actores_investigadores_reconocidos_value');
    actoresData.centros_investigacion_activos = getNumericValue('actores_centros_investigacion_value');
    actoresData.centros_desarrollo_tecnologico = getNumericValue('actores_centros_desarrollo_tecnologico_value');
    actoresData.centros_innovacion = getNumericValue('actores_centros_innovacion_value');

    // Extract data from arrays of objects (tables).
    actoresData.actores_por_sector_economico = extractTableData('actores_sector_economico_grouped_data');
    actoresData.actores_por_municipio = extractTableData('actores_municipio_grouped_data');
    actoresData.actores_por_tecnologia = extractTableData('actores_tecnologias40_grouped_data');

    const formacionData = {};
    formacionData.universidades = getNumericValue('formacion_universidades_value');
    formacionData.programas_ofertados = getNumericValue('formacion_programas_academicos_value');
    formacionData.programas_academicos_por_sectores = extractTableData('formacion_programas_academicos_sectores_grouped_data');
    formacionData.programas_academicos_por_municipio = extractTableData('formacion_programas_academicos_municipios_grouped_data');

    const retosData = {};
    retosData.retos_abiertos = getNumericValue('retos_abiertos_value');
    retosData.retos_en_evaluacion = getNumericValue('retos_en_evaluacion_value');
    retosData.retos_finalizados = getNumericValue('retos_finalizados_value');
    retosData.retos_por_sectores = extractTableData('retos_sectores_grouped_data');
    retosData.retos_por_tecnologia = extractTableData('retos_tecnologias_grouped_data');

    const proyectosData = {};
    proyectosData.proyectos_propuestos = getNumericValue('proyectos_propuestos_value');
    proyectosData.proyectos_en_ejecucion = getNumericValue('proyectos_ejecucion_value');
    proyectosData.proyectos_finalizados = getNumericValue('proyectos_finalizados_value');
    proyectosData.proyectos_por_sectores = extractTableData('proyectos_sectores_grouped_data');

    const proteccionIntelectualData = {};
    proteccionIntelectualData.registros_software = getNumericValue('registros_software_value');

    const generacionConocimientoData = {};
    generacionConocimientoData.produccion_total = getNumericValue('produccion_total_value');
    generacionConocimientoData.centros_investigacion = getNumericValue('generacion_conocimiento_centros_investigacion_value');
    generacionConocimientoData.centros_cdt = getNumericValue('generacion_conocimiento_centros_cdt_value');
    generacionConocimientoData.centros_innovacion = getNumericValue('generacion_conocimiento_centros_innovacion_value');
    generacionConocimientoData.grupos_investigacion_categorias = extractTableData('generacion_conocimiento_grupos_investigacion_categorias_grouped_data');

    const softwareData = {};
    softwareData.software_activos = getNumericValue('software_activos_value');
    softwareData.software_en_desarrollo = getNumericValue('software_desarrollo_value');
    softwareData.software_finalizado = getNumericValue('software_finalizado_value');
    softwareData.software_recursos_captados = getNumericValue('software_recursos_captados_value');
    softwareData.software_eventos = getNumericValue('software_eventos_value');
    softwareData.software_fuentes_financiacion = extractTableData('software_fuentes_financiacion_grouped_data');

    const empresasData = {};
    empresasData.empresas_altamente_innovadoras = getNumericValue('empresas_altamente_innovadoras_value');
    empresasData.empresas_unidades_idi = getNumericValue('empresas_unidades_idi_value');
    empresasData.empresas_startups = getNumericValue('empresas_startups_value');
    empresasData.empresas_spinoffs = getNumericValue('empresas_spinoffs_value');
    empresasData.empresas_por_sectores = extractTableData('empresas_sectores_grouped_data');
    empresasData.empresas_por_tecnologias = extractTableData('empresas_tecnologias_grouped_data');

    const produccionCientificaData = {};
    produccionCientificaData.articulos_investigacion = getNumericValue('articulos_investigacion_value');
    produccionCientificaData.patentes = getNumericValue('patentes_value');
    produccionCientificaData.productos_tecnologicos = getNumericValue('productos_tecnologicos_value');
    produccionCientificaData.consultorias = getNumericValue('consultorias_value');
    produccionCientificaData.tipos_consultorias = extractTableData('tipos_consultorias_grouped_data');
    produccionCientificaData.productos_tecnologicos_por_categorias = extractTableData('productos_tecnologicos_grouped_data');

    return {
      actoresData,
      formacionData,
      retosData,
      proyectosData,
      proteccionIntelectualData,
      generacionConocimientoData,
      softwareData,
      empresasData,
      produccionCientificaData,
      filtrosSeleccionados: {
        municipio: municipio,
        sector: sector,
        tecnologias40: tecnologias40,
      },
    };
  }

})(Drupal);
