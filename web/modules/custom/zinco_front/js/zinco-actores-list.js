(function (Drupal) {
  Drupal.behaviors.zincoActoresList = {
    attach: function (context, settings) {
      once('zincoActoresList', '.zinco-actores-list', context).forEach(function (element) {
        // global variables
        const urlParams = new URLSearchParams(window.location.search);
        const searchInput = element.querySelector('#search-filter');

        // Toggle filter card visibility
        const filterButton = element.querySelector('#toggle_actores_filter');
        const filterCard = element.querySelector('#actors_filters');
        
        
        if (filterButton && filterCard) {
          filterButton.addEventListener('click', function () {
            filterCard.classList.toggle('hidden');
          });
        }

        //si hay filtros en la url marcarlos en el card de filters         
        const filtersParam = urlParams.get('filters');
        if (filtersParam) {
          filterCard.classList.remove('hidden');
          const filtersArray = filtersParam.split(',');
          filtersArray.forEach(filterId => {
            const checkbox = filterCard.querySelector(`#${filterId}`);
            if (checkbox) {
              checkbox.checked = true;
            }
          });
        }

        // si hay search term en la url ponerlo en el input de search
        const searchTerm = urlParams.get('search_term');        
        if (searchTerm && searchInput) {
          filterCard.classList.remove('hidden');
          searchInput.value = searchTerm;
        }

        // Filter functionality
        const applyFiltersButton = element.querySelector('#apply_filters');
        const clearFiltersButton = element.querySelector('#clear_filters');

        if (applyFiltersButton) {
          applyFiltersButton.addEventListener('click', function () {
            const filters = {};
            
            const checkboxes = filterCard.querySelectorAll('input[type="checkbox"]:checked');
            checkboxes.forEach(checkbox => {
              filters[checkbox.id] = checkbox.value;
            });
            const filterKeys = Object.keys(filters);
            const currentUrl = new URL(window.location.href);
            if (filterKeys.length > 0) {
              currentUrl.searchParams.set('filters', filterKeys.join(','));
            } else {
              currentUrl.searchParams.delete('filters');
            }
            window.location.href = currentUrl.toString();
          });
        }

        if (clearFiltersButton) {
          clearFiltersButton.addEventListener('click', function () {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.delete('filters');
            window.location.href = currentUrl.toString();
          });
        }

        // Search functionality
        if (searchInput) {
          searchInput.addEventListener('change', function () {
            const searchTerm = searchInput.value.trim();
            const currentUrl = new URL(window.location.href);
            if (searchTerm) {
              currentUrl.searchParams.set('search_term', searchTerm);
            } else {
              currentUrl.searchParams.delete('search_term');
            }
            window.location.href = currentUrl.toString();
          });
        }
      });
    }
  };
})(Drupal);