((Drupal, once) => {
  Drupal.behaviors.zincoSendCallForPapers = {
    attach: function (context) {
      const elements = once('zinco-send-call-for-papers', '#btn-enviar-masivo', context);
      elements.forEach((btn) => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();

          const endpoint = btn.getAttribute('data-endpoint');
          const form = document.getElementById('zinco-front-call-for-papers-form');
          if (!form) {
            console.error('Form #zinco-front-call-for-papers-form not found.');
            return;
          }

          // Collect form data using FormData.
          const formData = new FormData(form);
          console.log('Datos enviados en FormData:');
          for (let [key, value] of formData.entries()) {
            console.log(key, value);
          }

          // Disable button and show processing state.
          btn.disabled = true;
          const originalValue = btn.value;
          btn.value = 'Procesando...';

          fetch(endpoint, {
            method: 'POST',
            body: formData,
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
            .then(response => {
              if (!response.ok) {
                return response.json().then(err => { throw err; });
              }
              return response.json();
            })
            .then(data => {
              if (data.success && data.redirect) {
                // Redirect to the batch processing page.
                window.location.href = data.redirect;
              } else {
                alert('Error: ' + (data.message || 'Ocurrió un error inesperado.'));
                btn.disabled = false;
                btn.value = originalValue;
              }
            })
            .catch(error => {
              console.error('Error during mass message submission:', error);
              const errorMsg = error && error.message ? error.message : 'Error en la petición.';
              alert('Error: ' + errorMsg);
              btn.disabled = false;
              btn.value = originalValue;
            });
        });
      });
    }
  };
})(Drupal, once);
