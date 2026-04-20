((Drupal, once) => {
  Drupal.behaviors.zincoSendCallForPapers = {
    attach: function (context) {
      console.log("cargando js");
      const elements = once('zinco-send-call-for-papers', '#btn-enviar-masivo', context);
      console.log(elements);
      elements.forEach((btn) => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();

          const endpoint = btn.getAttribute('data-endpoint');
          const form = btn.closest('form');
          console.log("form", form);
          if (!form) return;

          // Collect form data using FormData.
          const formData = new FormData(form);

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
