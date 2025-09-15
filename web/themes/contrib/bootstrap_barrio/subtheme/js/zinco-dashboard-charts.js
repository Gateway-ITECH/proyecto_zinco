(function (Drupal) {
  Drupal.behaviors.zincoDashboardCharts = {
    attach: function (context, settings) {
      // Chart for Egresados por Nivel Académico
      var academicLevelCtx = context.querySelector('#academicLevelChart');
      if (academicLevelCtx && !academicLevelCtx.dataset.chartInitialized) {
        academicLevelCtx.dataset.chartInitialized = 'true';
        new Chart(academicLevelCtx.getContext('2d'), {
          type: 'doughnut',
          data: {
            labels: ['Técnico', 'Tecnólogo', 'Universitario', 'Posgrado', 'Maestría', 'Doctorado'],
            datasets: [{
              data: [1200, 1850, 3200, 850, 450, 150],
              backgroundColor: ['#007bff', '#ffc107', '#28a745', '#dc3545', '#6f42c1', '#6c757d'],
              hoverOffset: 4
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: {
                display: false // Hide default legend as we have custom one
              }
            }
          }
        });
      }

      // Chart for Actores por Municipio (using custom progress bars for now)
      // If a Chart.js bar chart is preferred, this section would be replaced.
    }
  };
})(Drupal);