(function (Drupal) {
  Drupal.behaviors.zincoDashboardCharts = {
    attach: function (context, settings) {

      // Chart for Actores por Municipio (using custom progress bars for now)
      // If a Chart.js bar chart is preferred, this section would be replaced.

      // Line chart for Software Intellectual Property Registrations (Last 5 Years)
      const softwareRegistrationsCtx = context.querySelector('#softwareRegistrationsChart');
      if (softwareRegistrationsCtx && !softwareRegistrationsCtx.dataset.chartInitialized) {
        new Chart(softwareRegistrationsCtx, {
          type: 'line',
          data: {
            labels: ['2020', '2021', '2022', '2023', '2024'],
            datasets: [{
              label: 'Registros de Software',
              data: [10, 15, 20, 25, 30], // Placeholder data
              borderColor: 'rgba(75, 192, 192, 1)',
              backgroundColor: 'rgba(75, 192, 192, 0.2)',
              fill: false,
              tension: 0.1
            },
            {
              label: 'Registros de Marcas',
              data: [5, 8, 12, 18, 22], // Placeholder data
              borderColor: 'rgba(255, 159, 64, 1)',
              backgroundColor: 'rgba(255, 159, 64, 0.2)',
              fill: false,
              tension: 0.1
            },
            {
              label: 'Registros de Datos',
              data: [3, 6, 9, 11, 14], // Placeholder data
              borderColor: 'rgba(153, 102, 255, 1)',
              backgroundColor: 'rgba(153, 102, 255, 0.2)',
              fill: false,
              tension: 0.1
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true
              }
            },
            plugins: {
              legend: {
                display: true,
                position: 'top',
              },
              title: {
                display: false,
                text: 'Registros de Protección Intelectual de Software (Últimos 5 Años)'
              }
            }
          }
        });
        softwareRegistrationsCtx.dataset.chartInitialized = 'true';
      }
    }
  };
})(Drupal);