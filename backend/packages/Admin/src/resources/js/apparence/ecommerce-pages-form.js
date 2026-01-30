// E-commerce pages form - Page type and slug management
document.addEventListener('DOMContentLoaded', function() {
    const pageTypeSelect = document.getElementById('page-type');
    const slugContainer = document.getElementById('slug-container');
    const slugInput = document.getElementById('slug-input');
    const slugInfoContainer = document.getElementById('slug-info-container');
    const categorySelectContainer = document.getElementById('category-select-container');
    const categorySelect = document.getElementById('category-select');
    const productSelectContainer = document.getElementById('product-select-container');
    const productSelect = document.getElementById('product-select');

    if (!pageTypeSelect) return;

    // Stocker le slug initial pour la page d'édition
    const initialSlug = slugInput ? slugInput.value : '';

    function updateVisibility(isUserChange = false) {
        const selectedType = pageTypeSelect.value;

        // Reset visibility
        if (slugContainer) slugContainer.style.display = 'none';
        if (slugInfoContainer) slugInfoContainer.style.display = 'none';
        if (categorySelectContainer) categorySelectContainer.style.display = 'none';
        if (productSelectContainer) productSelectContainer.style.display = 'none';

        // Reset required attributes
        if (categorySelect) categorySelect.required = false;
        if (productSelect) productSelect.required = false;

        if (selectedType === 'home') {
            // Page d'accueil : slug = "accueil" automatiquement
            if (slugInput) slugInput.value = 'accueil';
            if (slugInfoContainer) slugInfoContainer.style.display = 'block';
        } else if (selectedType === 'category') {
            // Page catégorie : afficher le select de catégories
            if (categorySelectContainer) categorySelectContainer.style.display = 'block';
            if (categorySelect) {
                categorySelect.required = true;
                // Si c'est un changement utilisateur, reset le select
                if (isUserChange) {
                    categorySelect.value = '';
                    if (slugInput) slugInput.value = '';
                } else {
                    // À l'initialisation, synchroniser le slug avec le select
                    if (slugInput) slugInput.value = categorySelect.value || initialSlug;
                }
            }
        } else if (selectedType === 'product') {
            // Page produit : afficher le select de produits
            if (productSelectContainer) productSelectContainer.style.display = 'block';
            if (productSelect) {
                productSelect.required = true;
                // Si c'est un changement utilisateur, reset le select
                if (isUserChange) {
                    productSelect.value = '';
                    if (slugInput) slugInput.value = '';
                } else {
                    // À l'initialisation, synchroniser le slug avec le select
                    if (slugInput) slugInput.value = productSelect.value || initialSlug;
                }
            }
        } else {
            // Aucun type sélectionné : vider le slug
            if (slugInput) slugInput.value = '';
        }
    }

    // Initialize visibility on page load (sans reset du slug)
    updateVisibility(false);

    pageTypeSelect.addEventListener('change', function() {
        updateVisibility(true);
    });

    // Mettre à jour le champ slug quand une catégorie est sélectionnée
    if (categorySelect && slugInput) {
        categorySelect.addEventListener('change', function() {
            slugInput.value = this.value;
        });
    }

    // Mettre à jour le champ slug quand un produit est sélectionné
    if (productSelect && slugInput) {
        productSelect.addEventListener('change', function() {
            slugInput.value = this.value;
        });
    }
});
