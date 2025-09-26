/**
 * @file
 * Javascript for the Zinco Front dashboard.
 */

(function (Drupal) {
  Drupal.behaviors.zincoDashboardFront = {
    attach: function (context, settings) {
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
      
      /*
      * pestaña de actores
      */

      //parametros card grupos de investigacion (minciencias)
      const grupos_investigacion_tabla = 'data_grupos_investigacion';
      const grupos_investigacion_elemento = 'actores_grupos_de_investigacion_value';

      //parametros card investigadores reconocidos
      const card_investigadores_tabla = 'data_investigadores';
      const card_investigadores_elemento = 'actores_investigadores_reconocidos_value';

      //parametros card centros de investigacion
      const card_centros_investigacion_tabla = 'data_view_centros_investigacion';
      const card_centros_investigacion_elemento = 'actores_centros_investigacion_value';

      //parametros card centros de investigacion
      const card_centros_desarrollo_tecnologico_tabla = 'data_view_centros_desarrollo_tecnologico';
      const card_centros_desarrollo_tecnologico_elemento = 'actores_centros_desarrollo_tecnologico_value';

      //parametros card de actores por sector
      const card_actores_sector_tablas = 'data_empresas_tic-data_investigadores-data_centros_investigacion-data_grupos_investigacion';
      const card_actores_sector_campo = 'sector';
      const card_actores_sector_elemento = 'actores_sector_economico_grouped_data';

      //parametros card de actores por municipio
      const card_actores_municipio_tablas = 'data_empresas_tic-data_investigadores-data_centros_investigacion-data_grupos_investigacion';
      const card_actores_municipio_campo = 'municipio';
      const card_actores_municipio_elemento = 'actores_municipio_grouped_data';

      //parametros card de actores por tecnologia 4.0
      const card_actores_tecnologias40_tablas = 'data_empresas_tic-data_investigadores-data_centros_investigacion-data_grupos_investigacion';
      const card_actores_tecnologias40_campo = 'tecnologia40';
      const card_actores_tecnologias40_elemento = 'actores_tecnologias40_grouped_data';

      /*
      * pestaña de formacion
      */

      //parametros card universidades
      const card_universidades_tabla = 'data_instituciones_academicas';
      const card_universidades_elemento = 'formacion_universidades_value';

      //parametros card programas ofertados
      const card_programas_ofertados_tabla = 'data_programas_academicos';
      const card_programas_ofertados_elemento = 'formacion_programas_academicos_value';

      //parametros card de numero de programas academicos por sector
      const card_formacion_programas_academicos_sectores_tablas = 'data_programas_academicos';
      const card_formacion_programas_academicos_sectores_campo = 'sector';
      const card_formacion_programas_academicos_sectores_elemento = 'formacion_programas_academicos_sectores_grouped_data';

      //parametros card de numero de programas academicos por municipio
      const card_formacion_programas_academicos_municipios_tablas = 'data_programas_academicos';
      const card_formacion_programas_academicos_municipios_campo = 'municipio';
      const card_formacion_programas_academicos_municipios_elemento = 'formacion_programas_academicos_municipios_grouped_data';

      // Initial call to load data when the page loads, with default or empty values.
      // pestaña de actores
      loadData(municipio, sector, tecnologias40, grupos_investigacion_tabla, grupos_investigacion_elemento);
      loadData(municipio, sector, tecnologias40, card_investigadores_tabla, card_investigadores_elemento);
      loadData(municipio, sector, tecnologias40, card_centros_investigacion_tabla, card_centros_investigacion_elemento);
      loadData(municipio, sector, tecnologias40, card_centros_desarrollo_tecnologico_tabla, card_centros_desarrollo_tecnologico_elemento);
      //loadGroupedData(municipio, sector, tecnologias40, actores_sector_economico_tabla, actores_sector_economico_campo, actores_sector_economico_elemento);
      loadMultipleGroupedData(municipio, sector, tecnologias40, card_actores_sector_tablas, card_actores_sector_campo, card_actores_sector_elemento);
      loadMultipleGroupedData(municipio, sector, tecnologias40, card_actores_municipio_tablas, card_actores_municipio_campo, card_actores_municipio_elemento);
      loadMultipleGroupedData(municipio, sector, tecnologias40, card_actores_tecnologias40_tablas, card_actores_tecnologias40_campo, card_actores_tecnologias40_elemento);

      //pestaña de formacion
      loadData(municipio, sector, tecnologias40, card_universidades_tabla, card_universidades_elemento);
      loadData(municipio, sector, tecnologias40, card_programas_ofertados_tabla, card_programas_ofertados_elemento);
      loadMultipleGroupedData(municipio, sector, tecnologias40, card_formacion_programas_academicos_sectores_tablas, card_formacion_programas_academicos_sectores_campo, card_formacion_programas_academicos_sectores_elemento);
      loadMultipleGroupedData(municipio, sector, tecnologias40, card_formacion_programas_academicos_municipios_tablas, card_formacion_programas_academicos_municipios_campo, card_formacion_programas_academicos_municipios_elemento);


      if (municipioSelect) {
        municipioSelect.addEventListener('change', function() {
          municipio = municipioSelect ? municipioSelect.value : '';
          sector = sectorSelect ? sectorSelect.value : '';
          tecnologias40 = tecnologias40Select ? tecnologias40Select.value : '';

          console.log('Municipio seleccionado:', this.value);

          // pestaña de actores
          loadData(this.value, sector, tecnologias40, grupos_investigacion_tabla, grupos_investigacion_elemento);
          loadData(this.value, sector, tecnologias40, card_investigadores_tabla, card_investigadores_elemento);
          loadData(this.value, sector, tecnologias40, card_centros_investigacion_tabla, card_centros_investigacion_elemento);
          loadData(this.value, sector, tecnologias40, card_centros_desarrollo_tecnologico_tabla, card_centros_desarrollo_tecnologico_elemento);
          //loadGroupedData(this.value, sector, tecnologias40, actores_sector_economico_tabla, actores_sector_economico_campo, actores_sector_economico_elemento);
          loadMultipleGroupedData(this.value, sector, tecnologias40, card_actores_sector_tablas, card_actores_sector_campo, card_actores_sector_elemento);
          loadMultipleGroupedData(this.value, sector, tecnologias40, card_actores_municipio_tablas, card_actores_municipio_campo, card_actores_municipio_elemento);
          loadMultipleGroupedData(this.value, sector, tecnologias40, card_actores_tecnologias40_tablas, card_actores_tecnologias40_campo, card_actores_tecnologias40_elemento);

          //pestaña de formacion
          loadData(this.value, sector, tecnologias40, card_universidades_tabla, card_universidades_elemento);
          loadData(this.value, sector, tecnologias40, card_programas_ofertados_tabla, card_programas_ofertados_elemento);
          loadMultipleGroupedData(this.value, sector, tecnologias40, card_formacion_programas_academicos_sectores_tablas, card_formacion_programas_academicos_sectores_campo, card_formacion_programas_academicos_sectores_elemento);
        });
      }

      if (sectorSelect) {
        sectorSelect.addEventListener('change', function() {
          municipio = municipioSelect ? municipioSelect.value : '';
          sector = sectorSelect ? sectorSelect.value : '';
          tecnologias40 = tecnologias40Select ? tecnologias40Select.value : '';

          console.log('Sector seleccionado:', this.value);
          // pestaña de actores
          loadData(municipio, this.value, tecnologias40, grupos_investigacion_tabla, grupos_investigacion_elemento);
          loadData(municipio, this.value, tecnologias40, card_investigadores_tabla, card_investigadores_elemento);
          loadData(municipio, this.value, tecnologias40, card_centros_investigacion_tabla, card_centros_investigacion_elemento);
          loadData(municipio, this.value, tecnologias40, card_centros_desarrollo_tecnologico_tabla, card_centros_desarrollo_tecnologico_elemento);
          //loadGroupedData(municipio, this.value, tecnologias40, actores_sector_economico_tabla, actores_sector_economico_campo, actores_sector_economico_elemento);
          loadMultipleGroupedData(municipio, this.value, tecnologias40, card_actores_sector_tablas, card_actores_sector_campo, card_actores_sector_elemento);
          loadMultipleGroupedData(municipio, this.value, tecnologias40, card_actores_municipio_tablas, card_actores_municipio_campo, card_actores_municipio_elemento);
          loadMultipleGroupedData(municipio, this.value, tecnologias40, card_actores_tecnologias40_tablas, card_actores_tecnologias40_campo, card_actores_tecnologias40_elemento);

          //pestaña de formacion
          loadData(municipio, this.value, tecnologias40, card_universidades_tabla, card_universidades_elemento);
          loadData(municipio, this.value, tecnologias40, card_programas_ofertados_tabla, card_programas_ofertados_elemento);
          loadMultipleGroupedData(municipio, this.value, tecnologias40, card_formacion_programas_academicos_sectores_tablas, card_formacion_programas_academicos_sectores_campo, card_formacion_programas_academicos_sectores_elemento);
        });
      }

      if (tecnologias40Select) {
        tecnologias40Select.addEventListener('change', function() {
          municipio = municipioSelect ? municipioSelect.value : '';
          sector = sectorSelect ? sectorSelect.value : '';
          tecnologias40 = tecnologias40Select ? tecnologias40Select.value : '';

          console.log('Tecnologías 4.0 seleccionadas:', this.value);
          // pestaña de actores
          loadData(municipio, sector, this.value, grupos_investigacion_tabla, grupos_investigacion_elemento);
          loadData(municipio, sector, this.value, card_investigadores_tabla, card_investigadores_elemento);
          loadData(municipio, sector, this.value, card_centros_investigacion_tabla, card_centros_investigacion_elemento);
          loadData(municipio, sector, this.value, card_centros_desarrollo_tecnologico_tabla, card_centros_desarrollo_tecnologico_elemento);
          //loadGroupedData(municipio, sector, this.value, actores_sector_economico_tabla, actores_sector_economico_campo, actores_sector_economico_elemento);
          loadMultipleGroupedData(municipio, sector, this.value, card_actores_sector_tablas, card_actores_sector_campo, card_actores_sector_elemento);
          loadMultipleGroupedData(municipio, sector, this.value, card_actores_municipio_tablas, card_actores_municipio_campo, card_actores_municipio_elemento);
          loadMultipleGroupedData(municipio, sector, this.value, card_actores_tecnologias40_tablas, card_actores_tecnologias40_campo, card_actores_tecnologias40_elemento);

          //pestaña de formacion
          loadData(municipio, sector, this.value, card_universidades_tabla, card_universidades_elemento);
          loadData(municipio, sector, this.value, card_programas_ofertados_tabla, card_programas_ofertados_elemento);
          loadMultipleGroupedData(municipio, sector, this.value, card_formacion_programas_academicos_sectores_tablas, card_formacion_programas_academicos_sectores_campo, card_formacion_programas_academicos_sectores_elemento);

        });
      }
    }

  };
  function loadData(municipio, sector, tecnologia40, tabla, elemento) {
    const url = `/dump-data/${tabla}/json?municipio=${municipio}&sector=${sector}&tecnologia40=${tecnologia40}`;
    fetch(url)
      .then(response => response.json())
      .then(data => {
        const countElement = document.getElementById(elemento);
        if (countElement) {
          countElement.textContent = data.length;
        }
      })
      .catch(error => {
        console.error('Error al cargar grupos de investigación:', error);
      });
  }

  function loadMultipleGroupedData(municipio, sector, tecnologia40, tablas, campo, elemento) {
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
            for(key in data.consolidated){
              const item = data.consolidated[key];
                const html = `
                      <div class="d-flex align-items-center mb-2">
                        <div class="progress flex-grow-1 me-2" style="height: 15px;">
                          <div class="progress-bar bg-info" role="progressbar" style="width: ${item.percentage}%;" aria-valuenow="${item.percentage}" aria-valuemin="0" aria-valuemax="${100}"></div>
                        </div>
                        <span class="text-muted" data-group-column="${item.group_column}">${item.group_column.substring(0,5)}... (${item.count})</span>
                      </div>
                    `;
                    groupedDataWrapper.insertAdjacentHTML('beforeend', html);

              
                //console.log(item);
            }
          }
        }
      })
      .catch(error => {
        console.error('Error al cargar grupos de investigación:', error);
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
                  <span class="text-muted" data-group-column="${item.group_column}">${item.group_column.substring(0,5)}... (${item.count})</span>
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

})(Drupal);