/**
 * @file
 * Vanilla JS for managing actors count in the landing page config form.
 */
(function (Drupal, once) {
  Drupal.behaviors.zincoGestionarActores = {
    attach: function (context, settings) {
      const elements = once('zinco-gestionar-actores', '.gestionar-actores-link', context);
      
      elements.forEach(function (element) {
        element.addEventListener('click', function (event) {
          event.preventDefault();
          
          const endpoint = element.getAttribute('data-endpoint');
          if (!endpoint) return;

          // Añadir indicador de carga opcional si se desea
          element.style.opacity = '0.5';
          element.style.pointerEvents = 'none';

          fetch(endpoint)
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok');
              }
              return response.json();
            })
            .then(data => {
              if (data.count !== undefined) {
                // Buscamos el contenedor del campo
                const container = element.closest('.field-actores-registrados-container');
                if (container) {
                  // Buscamos el input de valor dentro del contenedor
                  const input = container.querySelector('input');
                  if (input) {
                    input.value = data.count;
                  }
                }
              }
            })
            .catch(error => {
              console.error('Error fetching actors count:', error);
            })
            .finally(() => {
              element.style.opacity = '1';
              element.style.pointerEvents = 'auto';
            });
        });
      });
    }
  };
})(Drupal, once);
