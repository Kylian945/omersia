// Fonction de recherche pour les hooks
document.getElementById('search-hook').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const hookSections = document.querySelectorAll('[data-hook-section]');

    hookSections.forEach(section => {
        const hookName = section.getAttribute('data-hook-name').toLowerCase();
        const hookLabel = section.getAttribute('data-hook-label').toLowerCase();

        if (hookName.includes(searchTerm) || hookLabel.includes(searchTerm)) {
            section.style.display = '';
        } else {
            section.style.display = 'none';
        }
    });
});

// Fonction de recherche pour les modules
document.getElementById('search-module').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const moduleItems = document.querySelectorAll('[data-module-item]');
    const hookSections = document.querySelectorAll('[data-hook-section]');

    moduleItems.forEach(item => {
        const moduleName = item.getAttribute('data-module-name').toLowerCase();

        if (moduleName.includes(searchTerm)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });

    // Masquer les sections de hooks vides
    hookSections.forEach(section => {
        const visibleModules = section.querySelectorAll('[data-module-item]:not([style*="display: none"])');
        if (visibleModules.length === 0 && searchTerm !== '') {
            section.style.display = 'none';
        } else {
            section.style.display = '';
        }
    });
});
