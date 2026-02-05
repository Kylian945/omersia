// Import Quill editor
import './quill-editor.js';

// Unified Page Builder - Works with both CMS Pages and E-commerce Pages
export function pageBuilder({ initial, saveUrl, csrf, categoriesUrl, productsUrl, serverWidgets = [] }) {
    // Normalisation du JSON initial
    let parsed = initial;
    if (typeof parsed === "string") {
        try {
            parsed = JSON.parse(parsed);
        } catch (e) {
            parsed = { sections: [] };
        }
    }
    if (!parsed || !Array.isArray(parsed.sections)) {
        parsed = { sections: [] };
    }

    return {
        data: parsed,
        widgets: serverWidgets,
        dragPayload: null,
        selected: null,
        saving: false,
        widgetsSearch: '',
        availableCategories: [],
        availableProducts: [],

        // View mode state (desktop/mobile)
        viewMode: 'desktop',

        // Drag & Drop state
        dragOverColumn: null,
        dragOverWidget: null,
        dragOverSection: null,
        draggedWidgetIndex: null,

        async init() {
            // Initialiser les propriétés responsive pour les anciennes colonnes
            this.initializeResponsiveColumns();
            // Initialiser la visibilité pour les sections et colonnes existantes
            this.initializeVisibility();
            // Migrer les anciens widgets product_slider
            this.sync();
            // Load categories and products if URLs are provided
            if (categoriesUrl && productsUrl) {
                await Promise.all([
                    this.loadCategories(),
                    this.loadProducts()
                ]);
            }
        },

        // Initialiser les propriétés responsive pour la rétrocompatibilité
        initializeResponsiveColumns() {
            this.data.sections?.forEach(section => {
                section.columns?.forEach(column => {
                    this.ensureResponsiveProps(column);
                    // Gérer les colonnes imbriquées
                    column.columns?.forEach(nestedCol => {
                        this.ensureResponsiveProps(nestedCol);
                    });
                    // Gérer les colonnes dans les widgets Container
                    column.widgets?.forEach(widget => {
                        if (widget.type === 'container' && widget.props?.columns) {
                            widget.props.columns.forEach(containerCol => {
                                this.ensureResponsiveProps(containerCol);
                            });
                        }
                    });
                });
            });
        },

        // S'assurer qu'une colonne a les propriétés responsive
        ensureResponsiveProps(column) {
            if (column.desktopWidth === undefined) {
                column.desktopWidth = column.width || 100;
            }
            if (column.mobileWidth === undefined) {
                column.mobileWidth = 100; // Par défaut, 100% en mobile
            }
            // Garder width pour la rétrocompatibilité
            if (column.width === undefined) {
                column.width = column.desktopWidth;
            }
        },

        // Initialiser la visibilité pour les sections et colonnes existantes
        initializeVisibility() {
            this.data.sections?.forEach(section => {
                // Initialiser la visibilité de la section
                if (!section.visibility) {
                    section.visibility = {
                        desktop: true,
                        tablet: true,
                        mobile: true,
                    };
                }

                // Initialiser la visibilité des colonnes
                section.columns?.forEach(column => {
                    this.ensureVisibilityProps(column);
                    // Gérer les colonnes imbriquées
                    column.columns?.forEach(nestedCol => {
                        this.ensureVisibilityProps(nestedCol);
                    });
                    // Gérer les colonnes dans les widgets Container
                    column.widgets?.forEach(widget => {
                        if (widget.type === 'container' && widget.props?.columns) {
                            widget.props.columns.forEach(containerCol => {
                                this.ensureVisibilityProps(containerCol);
                            });
                        }
                    });
                });
            });
        },

        // S'assurer qu'une colonne a les propriétés de visibilité
        ensureVisibilityProps(column) {
            if (!column.visibility) {
                column.visibility = {
                    desktop: true,
                    tablet: true,
                    mobile: true,
                };
            }
        },

        async loadCategories() {
            try {
                const response = await fetch(categoriesUrl);
                const data = await response.json();
                this.availableCategories = data;
            } catch (error) {
                console.error('Error loading categories:', error);
                this.availableCategories = [];
            }
        },

        async loadProducts() {
            try {
                const response = await fetch(productsUrl);
                const data = await response.json();
                this.availableProducts = data;
            } catch (error) {
                console.error('Error loading products:', error);
                this.availableProducts = [];
            }
        },

        // Toggle view mode between desktop and mobile
        toggleViewMode(mode) {
            this.viewMode = mode;
        },

        // Get column width based on current view mode
        getColumnWidth(column) {
            if (this.viewMode === 'mobile' && column.mobileWidth !== undefined) {
                return column.mobileWidth;
            }
            return column.desktopWidth !== undefined ? column.desktopWidth : column.width;
        },

        // Get column width style (for use in :style attribute)
        getColumnWidthStyle(column) {
            const width = this.getColumnWidth(column);
            // Toujours utiliser calc pour compenser le gap (0.5rem = 8px)
            // Le gap s'applique entre les colonnes, donc on doit soustraire une portion du gap
            // Pour une distribution équitable, on soustrait gap * (n-1) / n où n est le nombre de colonnes
            // Mais comme on ne connaît pas facilement n ici, on utilise une approche simple :
            // On soustrait un petit pourcentage pour compenser le gap
            return `width: calc(${width}% - 0.5rem);`;
        },

        // Sélection
        select(type, id, sectionId = null, columnId = null) {
            this.selected = { type, id, sectionId, columnId };
            if (type === "widget") {
                this.selected.widgetId = id;
            }
        },
        isSelected(type, id) {
            if (!this.selected) return false;
            if (type === "section") return this.selected.type === "section" && this.selected.id === id;
            if (type === "column") return this.selected.type === "column" && this.selected.id === id;
            if (type === "widget") return this.selected.type === "widget" && this.selected.widgetId === id;
            return false;
        },

        currentSection() {
            if (!this.selected || this.selected.type !== "section") return null;
            return (
                this.data.sections.find((s) => s.id === this.selected.id) ||
                null
            );
        },
        currentColumn() {
            if (!this.selected || this.selected.type !== "column") return null;
            const section = this.data.sections.find(
                (s) => s.id === this.selected.sectionId
            );
            // Chercher dans les colonnes de premier niveau
            let column = section?.columns.find((c) => c.id === this.selected.id);
            if (column) return column;

            // Chercher dans les colonnes imbriquées
            for (const col of section?.columns || []) {
                column = this.findNestedColumn(col, this.selected.id);
                if (column) return column;
            }

            // Chercher dans les colonnes des widgets Container
            if (!column) {
                for (const col of section?.columns || []) {
                    column = this.findColumnInContainers(col, this.selected.id);
                    if (column) return column;
                }
            }

            return null;
        },

        // Helper pour trouver une colonne imbriquée récursivement
        findNestedColumn(column, columnId) {
            if (column.id === columnId) return column;
            if (!column.columns) return null;

            for (const nestedCol of column.columns) {
                const found = this.findNestedColumn(nestedCol, columnId);
                if (found) return found;
            }
            return null;
        },

        // Helper pour trouver une colonne dans les widgets Container
        findColumnInContainers(column, columnId) {
            // Vérifier dans les widgets de cette colonne
            for (const widget of column.widgets || []) {
                if (widget.type === 'container' && widget.props?.columns) {
                    for (const containerCol of widget.props.columns) {
                        if (containerCol.id === columnId) return containerCol;
                    }
                }
            }

            // Chercher récursivement dans les colonnes imbriquées
            if (column.columns) {
                for (const nestedCol of column.columns) {
                    const found = this.findColumnInContainers(nestedCol, columnId);
                    if (found) return found;
                }
            }

            return null;
        },
        currentWidget() {
            if (!this.selected || this.selected.type !== "widget") return null;
            const section = this.data.sections.find(
                (s) => s.id === this.selected.sectionId
            );
            if (!section) return null;

            // Chercher la colonne (peut être imbriquée ou dans un Container)
            let col = section.columns.find((c) => c.id === this.selected.columnId);
            if (!col) {
                // Chercher dans les colonnes imbriquées
                for (const parentCol of section.columns) {
                    col = this.findNestedColumn(parentCol, this.selected.columnId);
                    if (col) break;
                }
            }
            // Chercher dans les colonnes des widgets Container
            if (!col) {
                for (const parentCol of section.columns) {
                    col = this.findColumnInContainers(parentCol, this.selected.columnId);
                    if (col) break;
                }
            }

            // Si on n'a toujours pas trouvé la colonne, on ne peut pas trouver le widget
            if (!col) return null;

            // Chercher le widget dans la colonne trouvée
            let widget = col.widgets?.find((w) => w.id === this.selected.widgetId);

            // Initialize visibility settings if not present
            if (widget && !widget.visibility) {
                widget.visibility = {
                    desktop: true,
                    tablet: true,
                    mobile: true
                };
            }

            return widget || null;
        },

        widgetLabel(widget) {
            const map = {
                heading: "Titre",
                text: "Texte",
                image: "Image",
                button: "Bouton",
                accordion: "Accordion",
                spacer: "Espace",
                container: "Container",
                hero_banner: "Hero Banner",
                features_bar: "Barre Features",
                categories_grid: "Grille Catégories",
                promo_banner: "Bannière Promo",
                testimonials: "Témoignages",
                newsletter: "Newsletter",
                product_slider: "Slider Produits",
            };
            return map[widget.type] || widget.type;
        },

        // Hero Banner helpers
        initHeroBannerProps() {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'hero_banner') return;

            // Fix if props is an array instead of object
            if (Array.isArray(widget.props)) {
                console.warn('hero_banner: props is an array, converting to object');
                widget.props = {};
            }
            if (!widget.props) widget.props = {};

            if (!widget.props.badge) widget.props.badge = "";
            if (!widget.props.title) widget.props.title = "Découvrez notre sélection";
            if (!widget.props.subtitle) widget.props.subtitle = "";
            if (!widget.props.description) widget.props.description = "";
            if (!widget.props.primaryCta) widget.props.primaryCta = { text: "Voir les produits", href: "/products" };
            if (!widget.props.secondaryCta) widget.props.secondaryCta = { text: "", href: "" };
            if (!widget.props.image) widget.props.image = "";
        },

        // Categories Grid helpers
        initCategoriesGridProps() {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'categories_grid') return;

            // Fix if props is an array instead of object
            if (Array.isArray(widget.props)) {
                console.warn('categories_grid: props is an array, converting to object');
                widget.props = {};
            }
            if (!widget.props) widget.props = {};

            if (!widget.props.title) widget.props.title = "Découvrez nos catégories";
            if (!widget.props.categorySlugs) widget.props.categorySlugs = [];
            if (widget.props.maxCategories === undefined) widget.props.maxCategories = null;

            // Display configuration
            if (widget.props.displayMode === undefined) widget.props.displayMode = 'grid';
            if (widget.props.slidesPerView === undefined) {
                widget.props.slidesPerView = { desktop: 4, mobile: 2 };
            }
            if (widget.props.slidesToScroll === undefined) {
                widget.props.slidesToScroll = { desktop: 1, mobile: 1 };
            }
            if (widget.props.columns === undefined) {
                widget.props.columns = { desktop: 4, mobile: 2 };
            }
            if (widget.props.showArrows === undefined) widget.props.showArrows = true;
            if (widget.props.showDots === undefined) widget.props.showDots = true;
            if (widget.props.autoplay === undefined) widget.props.autoplay = false;
            if (widget.props.gap === undefined) widget.props.gap = 16;
        },

        isCategorySelected(slug) {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'categories_grid') return false;
            this.initCategoriesGridProps();
            return widget.props.categorySlugs.includes(slug);
        },

        toggleCategory(slug) {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'categories_grid') return;
            this.initCategoriesGridProps();

            const index = widget.props.categorySlugs.indexOf(slug);
            if (index > -1) {
                widget.props.categorySlugs.splice(index, 1);
            } else {
                widget.props.categorySlugs.push(slug);
            }
            this.sync();
        },

        isAllCategoriesSelected() {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'categories_grid') return false;
            this.initCategoriesGridProps();
            return widget.props.categorySlugs.length === this.availableCategories.length;
        },

        toggleAllCategories() {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'categories_grid') return;
            this.initCategoriesGridProps();

            if (this.isAllCategoriesSelected()) {
                widget.props.categorySlugs = [];
            } else {
                widget.props.categorySlugs = this.availableCategories.map(cat => cat.slug);
            }
            this.sync();
        },

        getSelectedCategoriesCount() {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'categories_grid') return 0;
            this.initCategoriesGridProps();
            return widget.props.categorySlugs.length;
        },

        // Product Slider helpers
        initProductSliderProps() {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'product_slider') return;

            // Initialiser les props s'ils n'existent pas (sans écraser les valeurs existantes)
            if (widget.props.mode === undefined) widget.props.mode = 'category';
            if (widget.props.productIds === undefined) widget.props.productIds = [];
            if (widget.props.categorySlug === undefined) widget.props.categorySlug = '';

            // Display configuration
            if (widget.props.displayMode === undefined) widget.props.displayMode = 'slider';
            if (widget.props.slidesPerView === undefined) {
                widget.props.slidesPerView = { desktop: 4, mobile: 2 };
            }
            if (widget.props.slidesToScroll === undefined) {
                widget.props.slidesToScroll = { desktop: 1, mobile: 1 };
            }
            if (widget.props.columns === undefined) {
                widget.props.columns = { desktop: 4, mobile: 2 };
            }
            if (widget.props.showArrows === undefined) widget.props.showArrows = true;
            if (widget.props.showDots === undefined) widget.props.showDots = true;
            if (widget.props.autoplay === undefined) widget.props.autoplay = false;
            if (widget.props.gap === undefined) widget.props.gap = 16;
        },

        isProductSelected(productId) {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'product_slider') return false;
            this.initProductSliderProps();
            return widget.props.productIds.includes(productId);
        },

        toggleProduct(productId) {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'product_slider') return;
            this.initProductSliderProps();

            const index = widget.props.productIds.indexOf(productId);
            if (index > -1) {
                widget.props.productIds.splice(index, 1);
            } else {
                widget.props.productIds.push(productId);
            }
            this.sync();
        },

        isAllProductsSelected() {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'product_slider') return false;
            this.initProductSliderProps();
            return widget.props.productIds.length === this.availableProducts.length;
        },

        toggleAllProducts() {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'product_slider') return;
            this.initProductSliderProps();

            if (this.isAllProductsSelected()) {
                widget.props.productIds = [];
            } else {
                widget.props.productIds = this.availableProducts.map(product => product.id);
            }
            this.sync();
        },

        getSelectedProductsCount() {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'product_slider') return 0;
            this.initProductSliderProps();
            return widget.props.productIds.length;
        },

        setProductSliderMode(mode) {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'product_slider') return;
            this.initProductSliderProps();

            widget.props.mode = mode;
            this.sync();
        },

        getProductSliderMode() {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'product_slider') return 'category';
            this.initProductSliderProps();
            return widget.props.mode;
        },

        // Features Bar helpers
        initFeaturesBarProps() {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'features_bar') return;

            // Fix if props is an array instead of object
            if (Array.isArray(widget.props)) {
                console.warn('features_bar: props is an array, converting to object');
                widget.props = {};
            }
            if (!widget.props) widget.props = {};

            if (!widget.props.features || !Array.isArray(widget.props.features)) {
                widget.props.features = [
                    { icon: 'Truck', title: 'Livraison gratuite', description: 'À partir de 50€ d\'achat' },
                    { icon: 'ShieldCheck', title: 'Paiement sécurisé', description: 'Transactions 100% sécurisées' },
                    { icon: 'Undo2', title: 'Retours faciles', description: '30 jours pour changer d\'avis' },
                    { icon: 'MessageCircle', title: 'Support client', description: 'Disponible 7j/7' }
                ];
            }
        },

        addFeature() {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'features_bar') return;
            this.initFeaturesBarProps();
            widget.props.features.push({ icon: 'Star', title: 'Nouvelle fonctionnalité', description: 'Description' });
            this.sync();
        },

        removeFeature(index) {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'features_bar') return;
            this.initFeaturesBarProps();
            widget.props.features.splice(index, 1);
            this.sync();
        },

        // Tabs helpers
        initTabsProps() {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'tabs') return;

            if (Array.isArray(widget.props)) {
                console.warn('tabs: props is an array, converting to object');
                widget.props = {};
            }
            if (!widget.props) widget.props = {};

            if (!widget.props.items || !Array.isArray(widget.props.items)) {
                widget.props.items = [
                    { title: 'Onglet 1', content: '<p>Contenu du premier onglet</p>' },
                    { title: 'Onglet 2', content: '<p>Contenu du deuxième onglet</p>' },
                    { title: 'Onglet 3', content: '<p>Contenu du troisième onglet</p>' }
                ];
            }
        },

        addTab() {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'tabs') return;
            this.initTabsProps();
            const tabNumber = widget.props.items.length + 1;
            widget.props.items.push({
                title: `Onglet ${tabNumber}`,
                content: `<p>Contenu de l'onglet ${tabNumber}</p>`
            });
            this.sync();
        },

        removeTab(index) {
            const widget = this.currentWidget();
            if (!widget || widget.type !== 'tabs') return;
            this.initTabsProps();
            widget.props.items.splice(index, 1);
            this.sync();
        },

        availableIcons() {
            return [
                // E-commerce
                'Truck', 'Package', 'ShoppingCart', 'ShoppingBag', 'Gift', 'Tag', 'Percent',
                // Payment & Security
                'CreditCard', 'ShieldCheck', 'Lock', 'DollarSign', 'Wallet',
                // Communication
                'Phone', 'Mail', 'MessageCircle', 'MessageSquare', 'Send', 'Headphones',
                // Social & Feedback
                'Star', 'Heart', 'ThumbsUp', 'Award', 'Trophy', 'Smile', 'Users',
                // Time & Location
                'Clock', 'Calendar', 'MapPin', 'Map', 'Navigation',
                // Actions
                'Check', 'CheckCircle', 'Zap', 'Sparkles', 'TrendingUp', 'ArrowRight',
                // Returns & Support
                'Undo2', 'RotateCcw', 'RefreshCw', 'HelpCircle', 'Info',
                // Other
                'Home', 'Bell', 'Settings', 'Eye', 'Download', 'Upload', 'Search', 'Filter'
            ];
        },

        // SECTIONS / COLONNES
        addSection() {
            this.data.sections.push({
                id: "sec_" + Math.random().toString(36).slice(2, 8),
                settings: {
                    background: "#ffffff",
                    paddingTop: 40,
                    paddingBottom: 40,
                    fullWidth: false,
                },
                visibility: {
                    desktop: true,
                    tablet: true,
                    mobile: true,
                },
                columns: [
                    {
                        id: "col_" + Math.random().toString(36).slice(2, 8),
                        width: 100, // Deprecated, kept for backward compatibility
                        desktopWidth: 100,
                        mobileWidth: 100,
                        visibility: {
                            desktop: true,
                            tablet: true,
                            mobile: true,
                        },
                        widgets: [],
                    },
                ],
            });
            this.sync();
        },
        removeSection(id) {
            this.data.sections = this.data.sections.filter((s) => s.id !== id);
            if (this.selected?.type === "section" && this.selected.id === id) {
                this.selected = null;
            }
            this.sync();
        },
        addColumn(sectionId) {
            const section = this.data.sections.find((s) => s.id === sectionId);
            if (!section) return;

            const newWidth = 100 / (section.columns.length + 1);
            section.columns.push({
                id: "col_" + Math.random().toString(36).slice(2, 8),
                width: newWidth, // Deprecated, kept for backward compatibility
                desktopWidth: newWidth,
                mobileWidth: 100, // Par défaut, 100% en mobile
                visibility: {
                    desktop: true,
                    tablet: true,
                    mobile: true,
                },
                widgets: [],
                columns: [], // Support pour colonnes imbriquées
            });

            const equal = 100 / section.columns.length;
            section.columns.forEach((c) => {
                c.width = Math.round(equal);
                c.desktopWidth = Math.round(equal);
                // Ne pas écraser mobileWidth s'il existe déjà
                if (c.mobileWidth === undefined) {
                    c.mobileWidth = 100;
                }
            });
            this.sync();
        },

        // Ajouter une colonne imbriquée dans une colonne
        addNestedColumn(sectionId, parentColumnId) {
            const section = this.data.sections.find((s) => s.id === sectionId);
            const parentColumn = section?.columns.find((c) => c.id === parentColumnId);
            if (!parentColumn) return;

            if (!parentColumn.columns) {
                parentColumn.columns = [];
            }

            parentColumn.columns.push({
                id: "col_" + Math.random().toString(36).slice(2, 8),
                width: 100 / (parentColumn.columns.length + 1),
                widgets: [],
                columns: [], // Support pour colonnes imbriquées récursives
            });

            const equal = 100 / parentColumn.columns.length;
            parentColumn.columns.forEach((c) => {
                c.width = Math.round(equal);
            });
            this.sync();
        },

        // Supprimer une colonne imbriquée
        removeNestedColumn(sectionId, parentColumnId, nestedColumnId) {
            const section = this.data.sections.find((s) => s.id === sectionId);
            const parentColumn = section?.columns.find((c) => c.id === parentColumnId);
            if (!parentColumn || !parentColumn.columns) return;

            parentColumn.columns = parentColumn.columns.filter((c) => c.id !== nestedColumnId);

            // Recalculer les largeurs
            if (parentColumn.columns.length > 0) {
                const equal = 100 / parentColumn.columns.length;
                parentColumn.columns.forEach((c) => {
                    c.width = Math.round(equal);
                });
            }

            if (this.selected?.type === "column" && this.selected.id === nestedColumnId) {
                this.selected = null;
            }

            this.sync();
        },

        removeColumn(sectionId, columnId) {
            const section = this.data.sections.find((s) => s.id === sectionId);
            if (!section) return;

            section.columns = section.columns.filter((c) => c.id !== columnId);

            // Recalculer les largeurs des colonnes restantes
            if (section.columns.length > 0) {
                this.recalculateColumnWidths(sectionId);
            }

            if (
                this.selected?.type === "column" &&
                this.selected.id === columnId
            ) {
                this.selected = null;
            }

            this.sync();
        },

        // WIDGETS
        addWidgetTo(sectionId, columnId, type) {
            const section = this.data.sections.find((s) => s.id === sectionId);
            if (!section) return;

            // Chercher la colonne (peut être imbriquée)
            let col = section.columns.find((c) => c.id === columnId);
            if (!col) {
                // Chercher dans les colonnes imbriquées
                for (const parentCol of section.columns) {
                    col = this.findNestedColumn(parentCol, columnId);
                    if (col) break;
                }
            }

            // Si toujours pas trouvé, chercher dans les colonnes des widgets Container
            if (!col) {
                for (const parentCol of section.columns) {
                    col = this.findColumnInContainers(parentCol, columnId);
                    if (col) break;
                }
            }

            if (!col) return;

            const id = "w_" + Math.random().toString(36).slice(2, 8);
            const widget = { id, type, props: {} };

            switch (type) {
                case "heading":
                    widget.props = { text: "Titre", tag: "h2", align: "left" };
                    break;
                case "text":
                    widget.props = { html: "<p>Texte exemple</p>" };
                    break;
                case "image":
                    widget.props = { url: "", alt: "" };
                    break;
                case "video":
                    widget.props = {
                        type: "youtube",
                        url: "",
                        aspectRatio: "16/9",
                        autoplay: false,
                        loop: false,
                        muted: false
                    };
                    break;
                case "button":
                    widget.props = { label: "En savoir plus", url: "#" };
                    break;
                case "accordion":
                    widget.props = {
                        items: [{ title: "Item 1", content: "Texte..." }],
                    };
                    break;
                case "spacer":
                    widget.props = { size: 32 };
                    break;
                // E-commerce widgets
                case "hero_banner":
                    widget.props = {
                        badge: "Nouvelle Collection",
                        title: "Découvrez notre sélection",
                        subtitle: "de produits d'exception",
                        description: "Explorez notre catalogue de produits soigneusement sélectionnés.",
                        primaryCta: { text: "Voir les produits", href: "/products" },
                        secondaryCta: { text: "En savoir plus", href: "/content/a-propos" },
                        image: ""
                    };
                    break;
                case "features_bar":
                    widget.props = {
                        features: [
                            { icon: "Truck", title: "Livraison gratuite", description: "À partir de 50€ d'achat" },
                            { icon: "ShieldCheck", title: "Paiement sécurisé", description: "Transactions 100% sécurisées" },
                            { icon: "Undo2", title: "Retours faciles", description: "30 jours pour changer d'avis" },
                            { icon: "MessageCircle", title: "Support client", description: "Disponible 7j/7" },
                        ]
                    };
                    break;
                case "categories_grid":
                    widget.props = {
                        title: "Découvrez nos catégories",
                        categorySlugs: [],
                        subtitle: "",
                        maxCategories: null,
                        // Display configuration
                        displayMode: "grid", // 'slider' or 'grid'
                        // Slider configuration
                        slidesPerView: {
                            desktop: 4,
                            mobile: 2
                        },
                        slidesToScroll: {
                            desktop: 1,
                            mobile: 1
                        },
                        // Grid configuration
                        columns: {
                            desktop: 4,
                            mobile: 2
                        },
                        showArrows: true,
                        showDots: true,
                        autoplay: false,
                        gap: 16
                    };
                    break;
                case "promo_banner":
                    widget.props = {
                        badge: "Offre Limitée",
                        title: "Profitez de -20% sur toute la boutique",
                        description: "Utilisez le code BIENVENUE20 lors de votre commande.",
                        ctaText: "Découvrir les offres",
                        ctaHref: "/products",
                        variant: "gradient"
                    };
                    break;
                case "testimonials":
                    widget.props = {
                        title: "Ce que disent nos clients",
                        testimonials: [
                            { name: "Marie Dupont", role: "Cliente fidèle", content: "Excellente qualité et livraison rapide.", rating: 5 },
                            { name: "Thomas Martin", role: "Acheteur vérifié", content: "Service client au top.", rating: 5 },
                        ]
                    };
                    break;
                case "newsletter":
                    widget.props = {
                        title: "Restez informé",
                        description: "Inscrivez-vous à notre newsletter pour recevoir nos offres exclusives.",
                        placeholder: "Votre adresse email",
                        buttonText: "S'inscrire"
                    };
                    break;
                case "product_slider":
                    widget.props = {
                        title: "Produits mis en avant",
                        mode: "category", // 'category' or 'custom'
                        categorySlug: "",
                        productIds: [],
                        // Display configuration
                        displayMode: "slider", // 'slider' or 'grid'
                        // Slider configuration
                        slidesPerView: {
                            desktop: 4,
                            mobile: 2
                        },
                        slidesToScroll: {
                            desktop: 1,
                            mobile: 1
                        },
                        // Grid configuration
                        columns: {
                            desktop: 4,
                            mobile: 2
                        },
                        showArrows: true,
                        showDots: true,
                        autoplay: false,
                        gap: 16
                    };
                    break;
                case "container":
                    widget.props = {
                        background: "#ffffff",
                        paddingTop: 40,
                        paddingBottom: 40,
                        columns: [
                            {
                                id: "col_" + Math.random().toString(36).slice(2, 8),
                                width: 100,
                                widgets: []
                            }
                        ]
                    };
                    break;
            }

            col.widgets.push(widget);
            this.sync();
        },

        removeWidget(sectionId, columnId, widgetId) {
            const section = this.data.sections.find((s) => s.id === sectionId);
            if (!section) return;

            // Chercher la colonne (peut être imbriquée)
            let col = section.columns.find((c) => c.id === columnId);
            if (!col) {
                // Chercher dans les colonnes imbriquées
                for (const parentCol of section.columns) {
                    col = this.findNestedColumn(parentCol, columnId);
                    if (col) break;
                }
            }

            // Si toujours pas trouvé, chercher dans les colonnes des widgets Container
            if (!col) {
                for (const parentCol of section.columns) {
                    col = this.findColumnInContainers(parentCol, columnId);
                    if (col) break;
                }
            }

            if (!col) return;

            col.widgets = col.widgets.filter((w) => w.id !== widgetId);

            if (
                this.selected?.type === "widget" &&
                this.selected.id === widgetId
            ) {
                this.selected = null;
            }
            this.sync();
        },

        // CONTAINER COLUMNS MANAGEMENT
        addColumnToContainer(sectionId, columnId, widgetId) {
            const section = this.data.sections.find((s) => s.id === sectionId);
            if (!section) return;

            // Chercher la colonne (peut être imbriquée)
            let column = section.columns.find((c) => c.id === columnId);
            if (!column) {
                for (const col of section.columns) {
                    column = this.findNestedColumn(col, columnId);
                    if (column) break;
                }
            }
            if (!column) {
                for (const col of section.columns) {
                    column = this.findColumnInContainers(col, columnId);
                    if (column) break;
                }
            }

            const widget = column?.widgets.find((w) => w.id === widgetId);

            if (!widget || widget.type !== 'container' || !widget.props.columns) return;

            widget.props.columns.push({
                id: "col_" + Math.random().toString(36).slice(2, 8),
                width: 100 / (widget.props.columns.length + 1),
                widgets: []
            });

            // Recalcul des largeurs équitables
            const equal = 100 / widget.props.columns.length;
            widget.props.columns.forEach((c) => {
                c.width = Math.round(equal);
            });
            this.sync();
        },

        removeColumnFromContainer(sectionId, columnId, widgetId, containerColumnId) {
            const section = this.data.sections.find((s) => s.id === sectionId);
            if (!section) return;

            // Chercher la colonne (peut être imbriquée)
            let column = section.columns.find((c) => c.id === columnId);
            if (!column) {
                for (const col of section.columns) {
                    column = this.findNestedColumn(col, columnId);
                    if (column) break;
                }
            }
            if (!column) {
                for (const col of section.columns) {
                    column = this.findColumnInContainers(col, columnId);
                    if (column) break;
                }
            }

            const widget = column?.widgets.find((w) => w.id === widgetId);

            if (!widget || widget.type !== 'container' || !widget.props.columns) return;
            if (widget.props.columns.length <= 1) return; // Garde au moins 1 colonne

            widget.props.columns = widget.props.columns.filter((c) => c.id !== containerColumnId);

            // Recalcul des largeurs
            const equal = 100 / widget.props.columns.length;
            widget.props.columns.forEach((c) => {
                c.width = Math.round(equal);
            });

            // Supprimer la sélection si c'est la colonne sélectionnée
            if (this.selected?.type === "column" && this.selected.id === containerColumnId) {
                this.selected = null;
            }

            this.sync();
        },

        // DRAG & DROP - Widget management
        onWidgetDragStart(event, type) {
            this.dragPayload = { from: "palette", type };

            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = "copy";
                event.dataTransfer.setData("text/plain", type);
                event.dataTransfer.setData("dragType", "widget");
            }
        },

        onExistingWidgetDragStart(event, sectionId, columnId, widgetId, widgetIndex) {
            this.dragPayload = {
                from: "existing",
                sectionId,
                columnId,
                widgetId,
                widgetIndex,
            };

            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = "move";
                event.dataTransfer.setData("text/plain", widgetId);
                event.dataTransfer.setData("dragType", "widget");
                event.dataTransfer.setData("widgetIndex", widgetIndex.toString());
            }

            this.draggedWidgetIndex = widgetIndex;
            event.stopPropagation();
        },

        onWidgetDragOver(event, sectionId, columnId, widgetId, widgetIndex) {
            event.preventDefault();
            this.dragOverWidget = widgetId;
            this.dragOverColumn = columnId;
            event.stopPropagation();
        },

        onWidgetDragLeave(event) {
            this.dragOverWidget = null;
        },

        onWidgetDropOnWidget(event, targetSectionId, targetColumnId, targetWidgetId, targetIndex) {
            event.preventDefault();
            event.stopPropagation();

            const dragType = event.dataTransfer?.getData("dragType") || this.dragPayload?.dragType;
            if (dragType !== "widget" && !this.dragPayload) return;

            if (this.dragPayload) {
                if (this.dragPayload.from === "palette") {
                    this.addWidgetAtIndex(targetSectionId, targetColumnId, this.dragPayload.type, targetIndex);
                } else if (this.dragPayload.from === "existing") {
                    this.moveWidgetToIndex(
                        this.dragPayload.sectionId,
                        this.dragPayload.columnId,
                        this.dragPayload.widgetId,
                        this.dragPayload.widgetIndex,
                        targetSectionId,
                        targetColumnId,
                        targetIndex
                    );
                }
            }

            this.dragOverWidget = null;
            this.dragOverColumn = null;
            this.draggedWidgetIndex = null;
            this.dragPayload = null;
            this.sync();
        },

        onEmptyColumnDragOver(event, sectionId, columnId) {
            event.preventDefault();
            this.dragOverColumn = columnId;
            this.dragOverWidget = null;
        },

        onColumnDragLeave(event) {
            this.dragOverColumn = null;
        },

        onWidgetDrop(event, sectionId, columnId) {
            event.preventDefault();
            event.stopPropagation();

            const dragType = event.dataTransfer?.getData("dragType") || this.dragPayload?.dragType;

            if (dragType === "widget" || !dragType) {
                if (this.dragPayload) {
                    if (this.dragPayload.from === "palette") {
                        this.addWidgetTo(sectionId, columnId, this.dragPayload.type);
                    } else if (this.dragPayload.from === "existing") {
                        this.moveWidget(
                            this.dragPayload.sectionId,
                            this.dragPayload.columnId,
                            sectionId,
                            columnId,
                            this.dragPayload.widgetId
                        );
                    }
                } else if (event.dataTransfer) {
                    const type = event.dataTransfer.getData("text/plain");
                    if (type) {
                        this.addWidgetTo(sectionId, columnId, type);
                    }
                }
            }

            this.dragOverColumn = null;
            this.dragOverWidget = null;
            this.draggedWidgetIndex = null;
            this.dragPayload = null;
            this.sync();
        },

        addWidgetAtIndex(sectionId, columnId, widgetType, index) {
            const section = this.data.sections.find((s) => s.id === sectionId);
            if (!section) return;

            // Chercher la colonne (peut être imbriquée ou dans un Container)
            let column = section.columns.find((c) => c.id === columnId);
            if (!column) {
                for (const col of section.columns) {
                    column = this.findNestedColumn(col, columnId);
                    if (column) break;
                }
            }
            if (!column) {
                for (const col of section.columns) {
                    column = this.findColumnInContainers(col, columnId);
                    if (column) break;
                }
            }
            if (!column) return;

            const id = "w_" + Math.random().toString(36).slice(2, 8);
            const widget = { id, type: widgetType, props: {} };

            // Set default props based on widget type
            switch (widgetType) {
                case "heading":
                    widget.props = { text: "Titre", tag: "h2", align: "left" };
                    break;
                case "text":
                    widget.props = { html: "<p>Texte exemple</p>" };
                    break;
                case "image":
                    widget.props = { url: "", alt: "" };
                    break;
                case "button":
                    widget.props = { label: "En savoir plus", url: "#" };
                    break;
                case "accordion":
                    widget.props = { items: [{ title: "Item 1", content: "Texte..." }] };
                    break;
                case "spacer":
                    widget.props = { size: 32 };
                    break;
            }

            column.widgets.splice(index, 0, widget);
            this.sync();
        },

        moveWidget(fromSec, fromCol, toSec, toCol, widgetId) {
            const s1 = this.data.sections.find((s) => s.id === fromSec);
            if (!s1) return;

            // Chercher la colonne source (peut être imbriquée ou dans un Container)
            let c1 = s1.columns.find((c) => c.id === fromCol);
            if (!c1) {
                for (const col of s1.columns) {
                    c1 = this.findNestedColumn(col, fromCol);
                    if (c1) break;
                }
            }
            if (!c1) {
                for (const col of s1.columns) {
                    c1 = this.findColumnInContainers(col, fromCol);
                    if (c1) break;
                }
            }
            if (!c1) return;

            const idx = c1.widgets.findIndex((w) => w.id === widgetId);
            if (idx === -1) return;

            const [widget] = c1.widgets.splice(idx, 1);

            const s2 = this.data.sections.find((s) => s.id === toSec);
            if (!s2) return;

            // Chercher la colonne cible (peut être imbriquée ou dans un Container)
            let c2 = s2.columns.find((c) => c.id === toCol);
            if (!c2) {
                for (const col of s2.columns) {
                    c2 = this.findNestedColumn(col, toCol);
                    if (c2) break;
                }
            }
            if (!c2) {
                for (const col of s2.columns) {
                    c2 = this.findColumnInContainers(col, toCol);
                    if (c2) break;
                }
            }
            if (!c2) return;

            c2.widgets.push(widget);
            this.sync();
        },

        moveWidgetToIndex(fromSectionId, fromColumnId, widgetId, fromIndex, toSectionId, toColumnId, toIndex) {
            const fromSection = this.data.sections.find((s) => s.id === fromSectionId);
            if (!fromSection) return;

            // Chercher la colonne source (peut être imbriquée ou dans un Container)
            let fromColumn = fromSection.columns.find((c) => c.id === fromColumnId);
            if (!fromColumn) {
                for (const col of fromSection.columns) {
                    fromColumn = this.findNestedColumn(col, fromColumnId);
                    if (fromColumn) break;
                }
            }
            if (!fromColumn) {
                for (const col of fromSection.columns) {
                    fromColumn = this.findColumnInContainers(col, fromColumnId);
                    if (fromColumn) break;
                }
            }
            if (!fromColumn) return;

            const widgetIndex = fromColumn.widgets.findIndex((w) => w.id === widgetId);
            if (widgetIndex === -1) return;

            const widget = fromColumn.widgets.splice(widgetIndex, 1)[0];

            const toSection = this.data.sections.find((s) => s.id === toSectionId);
            if (!toSection) return;

            // Chercher la colonne cible (peut être imbriquée ou dans un Container)
            let toColumn = toSection.columns.find((c) => c.id === toColumnId);
            if (!toColumn) {
                for (const col of toSection.columns) {
                    toColumn = this.findNestedColumn(col, toColumnId);
                    if (toColumn) break;
                }
            }
            if (!toColumn) {
                for (const col of toSection.columns) {
                    toColumn = this.findColumnInContainers(col, toColumnId);
                    if (toColumn) break;
                }
            }
            if (!toColumn) return;

            // If moving within the same column and the target index is after the original, adjust the index
            if (fromSectionId === toSectionId && fromColumnId === toColumnId && toIndex > fromIndex) {
                toColumn.widgets.splice(toIndex, 0, widget);
            } else {
                toColumn.widgets.splice(toIndex, 0, widget);
            }
            this.sync();
        },

        // Column Drag & Drop
        onColumnDragStart(event, sectionId, columnId) {
            event.dataTransfer.setData("columnId", columnId);
            event.dataTransfer.setData("sectionId", sectionId);
            event.dataTransfer.setData("dragType", "column");
            event.dataTransfer.effectAllowed = "move";
            event.stopPropagation();
        },

        onColumnDragOver(event, sectionId, columnId) {
            event.preventDefault();
            const dragType = event.dataTransfer.getData("dragType");
            if (dragType === "column") {
                this.dragOverColumn = columnId;
                event.stopPropagation();
            }
        },

        onColumnDrop(event, targetSectionId, targetColumnId) {
            event.preventDefault();
            event.stopPropagation();

            const dragType = event.dataTransfer.getData("dragType");

            if (dragType === "column") {
                const fromColumnId = event.dataTransfer.getData("columnId");
                const fromSectionId = event.dataTransfer.getData("sectionId");
                this.moveColumn(fromSectionId, fromColumnId, targetSectionId, targetColumnId);
            } else if (dragType === "widget") {
                // Handle widget drop on column (delegate to widget drop handler)
                this.onWidgetDrop(event, targetSectionId, targetColumnId);
                return;
            }

            this.dragOverColumn = null;
        },

        moveColumn(fromSectionId, fromColumnId, toSectionId, toColumnId) {
            const fromSection = this.data.sections.find((s) => s.id === fromSectionId);
            if (!fromSection) return;

            const toSection = this.data.sections.find((s) => s.id === toSectionId);
            if (!toSection) return;

            // Chercher les colonnes (peuvent être dans des Containers)
            const fromContext = this.findColumnContext(fromSection, fromColumnId);
            const toContext = this.findColumnContext(toSection, toColumnId);

            if (!fromContext || !toContext) return;

            // Si les deux colonnes sont dans le même container
            if (fromContext.container && toContext.container && fromContext.container === toContext.container) {
                const fromIndex = fromContext.parentColumns.findIndex((c) => c.id === fromColumnId);
                const toIndex = toContext.parentColumns.findIndex((c) => c.id === toColumnId);

                if (fromIndex === -1 || toIndex === -1) return;

                const column = fromContext.parentColumns.splice(fromIndex, 1)[0];
                fromContext.parentColumns.splice(toIndex, 0, column);
            }
            // Si les deux colonnes sont au niveau de la section
            else if (!fromContext.container && !toContext.container && fromSectionId === toSectionId) {
                const fromIndex = fromSection.columns.findIndex((c) => c.id === fromColumnId);
                const toIndex = toSection.columns.findIndex((c) => c.id === toColumnId);

                if (fromIndex === -1 || toIndex === -1) return;

                const column = fromSection.columns.splice(fromIndex, 1)[0];
                fromSection.columns.splice(toIndex, 0, column);
            }
            // Si on déplace entre des sections différentes (colonnes de section uniquement)
            else if (!fromContext.container && !toContext.container && fromSectionId !== toSectionId) {
                const fromIndex = fromSection.columns.findIndex((c) => c.id === fromColumnId);
                const toIndex = toSection.columns.findIndex((c) => c.id === toColumnId);

                if (fromIndex === -1 || toIndex === -1) return;

                const column = fromSection.columns.splice(fromIndex, 1)[0];
                toSection.columns.splice(toIndex, 0, column);

                // Recalculate widths for both sections
                this.recalculateColumnWidths(fromSectionId);
                this.recalculateColumnWidths(toSectionId);
            }

            this.sync();
        },

        // Helper pour trouver le contexte d'une colonne (section ou container)
        findColumnContext(section, columnId) {
            // Vérifier si c'est une colonne de section
            const sectionColumn = section.columns.find((c) => c.id === columnId);
            if (sectionColumn) {
                return { parentColumns: section.columns, container: null };
            }

            // Chercher dans les colonnes imbriquées
            for (const col of section.columns) {
                if (col.columns) {
                    const nestedColumn = col.columns.find((c) => c.id === columnId);
                    if (nestedColumn) {
                        return { parentColumns: col.columns, container: col.id };
                    }
                }
            }

            // Chercher dans les widgets Container
            for (const col of section.columns) {
                for (const widget of col.widgets || []) {
                    if (widget.type === 'container' && widget.props?.columns) {
                        const containerColumn = widget.props.columns.find((c) => c.id === columnId);
                        if (containerColumn) {
                            return { parentColumns: widget.props.columns, container: widget.id };
                        }
                    }
                }
            }

            return null;
        },

        recalculateColumnWidths(sectionId) {
            const section = this.data.sections.find((s) => s.id === sectionId);
            if (!section || section.columns.length === 0) return;

            const newWidth = Math.floor(100 / section.columns.length);
            section.columns.forEach((col) => (col.width = newWidth));
        },

        // Section Drag & Drop
        onSectionDragStart(event, sectionId) {
            event.dataTransfer.setData("sectionId", sectionId);
            event.dataTransfer.setData("dragType", "section");
            event.dataTransfer.effectAllowed = "move";
        },

        onSectionDragOver(event, sectionId) {
            event.preventDefault();
            const dragType = event.dataTransfer.getData("dragType");
            if (dragType === "section") {
                this.dragOverSection = sectionId;
                event.currentTarget.classList.add("border-t-4", "border-t-sky-500", "border-dashed");
            }
        },

        onSectionDragLeave(event) {
            event.currentTarget.classList.remove("border-t-4", "border-t-sky-500", "border-dashed");
        },

        onSectionDrop(event) {
            event.preventDefault();
            const dragType = event.dataTransfer.getData("dragType");

            if (dragType === "section") {
                const fromSectionId = event.dataTransfer.getData("sectionId");
                const targetElement = event.target.closest("[data-section-id]");

                if (targetElement) {
                    const toSectionId = targetElement.getAttribute("data-section-id");
                    this.moveSection(fromSectionId, toSectionId);
                    targetElement.classList.remove("border-t-4", "border-t-sky-500", "border-dashed");
                }
            }

            this.dragOverSection = null;
        },

        onSectionsContainerDragOver(event) {
            event.preventDefault();
        },

        onSectionsContainerDragLeave() {
            // Cleanup any drag over states
        },

        moveSection(fromSectionId, toSectionId) {
            const fromIndex = this.data.sections.findIndex((s) => s.id === fromSectionId);
            if (fromIndex === -1) return;

            const toIndex = this.data.sections.findIndex((s) => s.id === toSectionId);
            if (toIndex === -1) return;

            const section = this.data.sections.splice(fromIndex, 1)[0];
            this.data.sections.splice(toIndex, 0, section);
            this.sync();
        },

        // SYNC + SAVE
        sync() {
            if (this.$refs.contentInput) {
                this.$refs.contentInput.value = JSON.stringify(this.data);
            }
        },

        // Méthode pour afficher les toasts
        showToast(message, type = 'info') {
            if (typeof window.showToast === 'function') {
                window.showToast(message, type);
            }
        },

        async save() {
            this.saving = true;

            const formData = new FormData();
            formData.append('_token', csrf);
            formData.append('content_json', JSON.stringify(this.data));

            try {
                const response = await fetch(saveUrl, {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    this.showToast('Page enregistrée avec succès', 'success');
                } else {
                    const error = await response.json().catch(() => ({}));
                    console.error('Save failed with status:', response.status);
                    this.showToast('Erreur lors de l\'enregistrement: ' + (error.message || 'Erreur inconnue'), 'error');
                }
            } catch (error) {
                console.error('Erreur:', error);
                this.showToast('Erreur lors de l\'enregistrement', 'error');
            } finally {
                this.saving = false;
            }
        },

        async uploadImageDirect(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('images[]', file);

            try {
                const response = await fetch('/admin/apparence/media', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.items && data.items.length > 0) {
                        const item = data.items[0];
                        this.currentWidget().props.url = item.url;
                        this.sync();
                    }
                } else {
                    const errorData = await response.json().catch(() => ({ message: 'Erreur inconnue' }));
                    console.error('Upload failed:', errorData);
                    alert('Erreur lors de l\'upload: ' + (errorData.message || 'Erreur inconnue'));
                }
            } catch (error) {
                console.error('Error uploading image:', error);
                alert('Erreur lors de l\'upload de l\'image');
            } finally {
                event.target.value = '';
            }
        },

        filteredWidgets() {
            if (!this.widgetsSearch || this.widgetsSearch.trim() === '') {
                return this.widgets;
            }

            const search = this.widgetsSearch.toLowerCase().trim();
            return this.widgets.filter(widget =>
                widget.label.toLowerCase().includes(search) ||
                widget.type.toLowerCase().includes(search)
            );
        },

        // Preview Modal
        showPreview: false,
        previewUrl: '',
        iframeLoaded: false,
        previewMode: 'modal', // 'modal' ou 'tab'

        openPreview(mode = 'modal') {
            this.previewMode = mode;

            const frontendUrl = document.querySelector('[data-frontend-url]')?.dataset.frontendUrl || 'http://localhost:3000';
            const pageSlug = document.querySelector('[data-page-slug]')?.dataset.pageSlug || 'default';

            // Construire l'URL en fonction du slug
            let previewUrl;
            if (pageSlug === 'accueil' || pageSlug === 'home') {
                // Page d'accueil via slug
                previewUrl = `${frontendUrl}?preview=1&t=${Date.now()}`;
            } else {
                // Pages CMS classiques
                previewUrl = `${frontendUrl}/content/${pageSlug}?preview=1&t=${Date.now()}`;
            }

            if (mode === 'tab') {
                // Ouvrir dans un nouvel onglet
                window.open(previewUrl, '_blank');
            } else {
                // Ouvrir en modal via événement window avec l'URL
                window.previewUrl = previewUrl;
                window.dispatchEvent(new CustomEvent('preview-open'));
            }
        },

        closePreview() {
            window.dispatchEvent(new CustomEvent('preview-close'));
        },
    };
};

// Make it globally available (keep both names for backward compatibility)
window.pageBuilder = pageBuilder;
window.elementorBuilder = pageBuilder; // Alias for backward compatibility
window.ecommerceBuilder = pageBuilder; // Alias for backward compatibility
