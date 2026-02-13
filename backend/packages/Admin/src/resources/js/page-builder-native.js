// Import Quill editor et le builder de base
import "./quill-editor.js";
import { pageBuilder } from "./page-builder.js";

// Page Builder avec support du contenu natif (beforeNative / afterNative)
// Cette version utilise le builder de base sans modification
export function pageBuilderNative(config) {
    const { initial, pageType, ...restConfig } = config;

    // Normalisation du JSON initial pour beforeNative/afterNative
    let parsed = initial;
    if (typeof parsed === "string") {
        try {
            parsed = JSON.parse(parsed);
        } catch (e) {
            parsed = {
                beforeNative: { sections: [] },
                afterNative: { sections: [] },
            };
        }
    }

    // S'assurer que la structure beforeNative/afterNative existe
    if (!parsed || !parsed.beforeNative || !parsed.afterNative) {
        parsed = {
            beforeNative: { sections: parsed?.beforeNative?.sections || [] },
            afterNative: { sections: parsed?.afterNative?.sections || [] },
        };
    }

    // Créer une instance du builder de base
    // IMPORTANT: On passe une structure vide car on va override data
    const baseBuilder = pageBuilder({
        initial: { sections: [] },
        ...restConfig,
    });

    // Retourner le builder de base avec nos overrides
    return {
        ...baseBuilder,

        // Override data avec notre structure beforeNative/afterNative
        data: parsed,

        // Stocker le type de page pour référence
        pageType: pageType,

        // View mode state (desktop/mobile) - hérité du builder de base mais on peut l'override si besoin
        viewMode: "desktop",

        // Override addSection pour accepter le paramètre zone
        addSection(zone = "beforeNative") {
            const section = {
                id: "section-" + Date.now(),
                settings: {
                    background: "",
                    paddingTop: 40,
                    paddingBottom: 40,
                    fullWidth: false,
                },
                columns: [
                    {
                        id: "col-" + Date.now(),
                        width: 100,
                        widgets: [],
                    },
                ],
            };

            // Ajouter la section dans la bonne zone
            if (zone === "beforeNative") {
                this.data.beforeNative.sections.push(section);
            } else if (zone === "afterNative") {
                this.data.afterNative.sections.push(section);
            }

            this.sync();
        },

        // Override removeSection pour accepter le paramètre zone
        removeSection(sectionId, zone) {
            if (zone === "beforeNative") {
                this.data.beforeNative.sections =
                    this.data.beforeNative.sections.filter(
                        (s) => s.id !== sectionId
                    );
            } else if (zone === "afterNative") {
                this.data.afterNative.sections =
                    this.data.afterNative.sections.filter(
                        (s) => s.id !== sectionId
                    );
            }
            this.sync();
        },

        // Override addColumn pour accepter le paramètre zone
        addColumn(sectionId, zone) {
            const sections =
                zone === "beforeNative"
                    ? this.data.beforeNative.sections
                    : this.data.afterNative.sections;

            const section = sections.find((s) => s.id === sectionId);
            if (!section) return;

            const newColumn = {
                id: "col-" + Date.now(),
                width: 50,
                widgets: [],
            };

            // Ajuster la largeur des colonnes existantes
            const totalColumns = section.columns.length + 1;
            const newWidth = Math.floor(100 / totalColumns);

            section.columns.forEach((col) => {
                col.width = newWidth;
            });

            newColumn.width = 100 - newWidth * section.columns.length;
            section.columns.push(newColumn);
            this.sync();
        },

        // Override removeColumn pour accepter le paramètre zone
        removeColumn(sectionId, columnId, zone) {
            const sections =
                zone === "beforeNative"
                    ? this.data.beforeNative.sections
                    : this.data.afterNative.sections;

            const section = sections.find((s) => s.id === sectionId);
            if (!section) return;

            section.columns = section.columns.filter((c) => c.id !== columnId);

            // Réajuster les largeurs
            if (section.columns.length > 0) {
                const newWidth = Math.floor(100 / section.columns.length);
                section.columns.forEach((col, idx) => {
                    col.width =
                        idx === section.columns.length - 1
                            ? 100 - newWidth * (section.columns.length - 1)
                            : newWidth;
                });
            }

            this.sync();
        },

        // Override removeWidget pour accepter le paramètre zone
        removeWidget(sectionId, columnId, widgetId, zone) {
            const sections =
                zone === "beforeNative"
                    ? this.data.beforeNative.sections
                    : this.data.afterNative.sections;

            const section = sections.find((s) => s.id === sectionId);
            if (!section) return;

            // Chercher la colonne (peut être imbriquée ou dans un Container)
            let column = section.columns.find((c) => c.id === columnId);
            if (!column) {
                for (const parentCol of section.columns) {
                    column = this.findNestedColumn(parentCol, columnId);
                    if (column) break;
                }
            }
            if (!column) {
                for (const parentCol of section.columns) {
                    column = this.findColumnInContainers(parentCol, columnId);
                    if (column) break;
                }
            }
            if (!column) return;

            column.widgets = column.widgets.filter((w) => w.id !== widgetId);

            // Si le widget supprimé était sélectionné → on reset
            if (
                this.selected?.type === "widget" &&
                (this.selected.widgetId === widgetId ||
                    this.selected.id === widgetId)
            ) {
                this.selected = null;
            }

            this.sync();
        },

        // Override select pour accepter le paramètre zone
        select(type, id, zone = null, sectionId = null, columnId = null) {
            this.selected = { type, id, zone, sectionId, columnId };
            if (type === "widget") {
                this.selected.widgetId = id;
            }
        },

        // Override currentSection pour chercher dans les deux zones
        currentSection() {
            if (!this.selected || this.selected.type !== "section") return null;

            const zone = this.selected.zone;
            if (zone === "beforeNative") {
                return (
                    this.data.beforeNative.sections.find(
                        (s) => s.id === this.selected.id
                    ) || null
                );
            } else if (zone === "afterNative") {
                return (
                    this.data.afterNative.sections.find(
                        (s) => s.id === this.selected.id
                    ) || null
                );
            }
            return null;
        },

        // Override currentColumn pour chercher dans les deux zones
        currentColumn() {
            if (!this.selected || this.selected.type !== "column") return null;

            const zone = this.selected.zone;
            const sections =
                zone === "beforeNative"
                    ? this.data.beforeNative.sections
                    : this.data.afterNative.sections;

            const section = sections.find(
                (s) => s.id === this.selected.sectionId
            );

            // Chercher dans les colonnes de premier niveau
            let column = section?.columns.find(
                (c) => c.id === this.selected.id
            );
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

        // Override currentWidget pour chercher dans les deux zones
        currentWidget() {
            // Si rien n'est sélectionné ou que ce n'est pas un widget → on renvoie un objet safe
            if (!this.selected || this.selected.type !== "widget") {
                return { id: null, type: null, props: {} };
            }

            const zone = this.selected.zone;
            const sections =
                zone === "beforeNative"
                    ? this.data.beforeNative.sections
                    : this.data.afterNative.sections;

            const section = sections.find(
                (s) => s.id === this.selected.sectionId
            );
            if (!section) {
                return { id: null, type: null, props: {} };
            }

            // Chercher la colonne (peut être imbriquée ou dans un Container)
            let col = section.columns.find(
                (c) => c.id === this.selected.columnId
            );
            if (!col) {
                // Colonnes imbriquées
                for (const parentCol of section.columns) {
                    col = this.findNestedColumn(
                        parentCol,
                        this.selected.columnId
                    );
                    if (col) break;
                }
            }
            if (!col) {
                // Colonnes dans les widgets Container
                for (const parentCol of section.columns) {
                    col = this.findColumnInContainers(
                        parentCol,
                        this.selected.columnId
                    );
                    if (col) break;
                }
            }

            if (!col || !col.widgets) {
                return { id: null, type: null, props: {} };
            }

            const widget = col.widgets.find(
                (w) => w.id === this.selected.widgetId
            );
            if (!widget) {
                // sélection invalide → on la reset pour éviter les futurs bugs
                this.selected = null;
                return { id: null, type: null, props: {} };
            }

            return widget;
        },

        // Helper pour trouver une section dans les deux zones
        findSection(sectionId) {
            let section = this.data.beforeNative.sections.find(
                (s) => s.id === sectionId
            );
            if (section) return { section, zone: "beforeNative" };

            section = this.data.afterNative.sections.find(
                (s) => s.id === sectionId
            );
            if (section) return { section, zone: "afterNative" };

            return null;
        },

        // Override addWidgetTo pour supporter les zones
        addWidgetTo(sectionId, columnId, type, zone = null) {
            // Si zone n'est pas fournie, essayer de la trouver
            if (!zone) {
                const result = this.findSection(sectionId);
                if (!result) return;
                zone = result.zone;
            }

            const sections =
                zone === "beforeNative"
                    ? this.data.beforeNative.sections
                    : this.data.afterNative.sections;

            const section = sections.find((s) => s.id === sectionId);
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
            const widget = {
                id,
                type,
                props: this.getWidgetDefaultProps(type),
            };

            col.widgets.push(widget);
            this.sync();
        },

        // Override onColumnDragStart pour supporter les zones
        onColumnDragStart(event, sectionId, columnId, zone) {
            event.dataTransfer.setData("columnId", columnId);
            event.dataTransfer.setData("sectionId", sectionId);
            event.dataTransfer.setData("zone", zone);
            event.dataTransfer.setData("dragType", "column");
            event.dataTransfer.effectAllowed = "move";
            event.stopPropagation();
        },

        // Override onColumnDrop pour supporter les zones
        onColumnDrop(event, targetSectionId, targetColumnId, zone) {
            event.preventDefault();
            event.stopPropagation();

            const dragType = event.dataTransfer.getData("dragType");

            if (dragType === "column") {
                const fromColumnId = event.dataTransfer.getData("columnId");
                const fromSectionId = event.dataTransfer.getData("sectionId");
                const fromZone = event.dataTransfer.getData("zone");
                this.moveColumn(
                    fromSectionId,
                    fromColumnId,
                    targetSectionId,
                    targetColumnId,
                    fromZone,
                    zone
                );
            } else if (dragType === "widget") {
                // Handle widget drop on column (delegate to widget drop handler)
                this.onWidgetDrop(event, targetSectionId, targetColumnId, zone);
                return;
            }

            this.dragOverColumn = null;
        },

        // Override onWidgetDrop pour supporter les zones
        onWidgetDrop(event, sectionId, columnId, zone) {
            event.preventDefault();
            event.stopPropagation();

            const dragType =
                event.dataTransfer?.getData("dragType") ||
                this.dragPayload?.dragType;

            if (dragType === "widget" || !dragType) {
                if (this.dragPayload) {
                    if (this.dragPayload.from === "palette") {
                        this.addWidgetTo(
                            sectionId,
                            columnId,
                            this.dragPayload.type,
                            zone
                        );
                    } else if (this.dragPayload.from === "existing") {
                        this.moveWidget(
                            this.dragPayload.sectionId,
                            this.dragPayload.columnId,
                            sectionId,
                            columnId,
                            this.dragPayload.widgetId,
                            this.dragPayload.zone,
                            zone
                        );
                    }
                } else if (event.dataTransfer) {
                    const type = event.dataTransfer.getData("text/plain");
                    if (type) {
                        this.addWidgetTo(sectionId, columnId, type, zone);
                    }
                }
            }

            this.dragOverColumn = null;
            this.dragOverWidget = null;
            this.draggedWidgetIndex = null;
            this.dragPayload = null;
            this.sync();
        },

        // Override onExistingWidgetDragStart pour supporter les zones
        onExistingWidgetDragStart(
            event,
            sectionId,
            columnId,
            widgetId,
            widgetIndex,
            zone
        ) {
            this.dragPayload = {
                from: "existing",
                sectionId,
                columnId,
                widgetId,
                widgetIndex,
                zone,
                dragType: "widget",
            };

            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = "move";
                event.dataTransfer.setData("text/plain", widgetId);
                event.dataTransfer.setData("dragType", "widget");
                event.dataTransfer.setData(
                    "widgetIndex",
                    widgetIndex.toString()
                );
            }

            this.draggedWidgetIndex = widgetIndex;
            event.stopPropagation();
        },

        // DnD entre widgets : drop sur un widget (avec gestion de zone)
        onWidgetDropOnWidget(
            event,
            targetSectionId,
            targetColumnId,
            targetWidgetId,
            targetIndex,
            zone
        ) {
            event.preventDefault();
            event.stopPropagation();

            const dragType =
                event.dataTransfer?.getData("dragType") ||
                this.dragPayload?.dragType;
            if (dragType !== "widget" && !this.dragPayload) return;

            if (this.dragPayload) {
                if (this.dragPayload.from === "palette") {
                    // On ajoute un nouveau widget à un index précis
                    this.addWidgetAtIndex(
                        targetSectionId,
                        targetColumnId,
                        this.dragPayload.type,
                        targetIndex,
                        zone
                    );
                } else if (this.dragPayload.from === "existing") {
                    // On déplace un widget existant à un index précis
                    this.moveWidgetToIndex(
                        this.dragPayload.sectionId,
                        this.dragPayload.columnId,
                        this.dragPayload.widgetId,
                        this.dragPayload.widgetIndex,
                        targetSectionId,
                        targetColumnId,
                        targetIndex,
                        this.dragPayload.zone,
                        zone
                    );
                }
            }

            this.dragOverWidget = null;
            this.dragOverColumn = null;
            this.draggedWidgetIndex = null;
            this.dragPayload = null;
            this.sync();
        },

        // Ajout d'un widget à un index précis dans une colonne (avec zone)
        addWidgetAtIndex(sectionId, columnId, widgetType, index, zone = null) {
            // Si zone n'est pas fournie, on la déduit
            if (!zone) {
                const result = this.findSection(sectionId);
                if (!result) return;
                zone = result.zone;
            }

            const sections =
                zone === "beforeNative"
                    ? this.data.beforeNative.sections
                    : this.data.afterNative.sections;

            const section = sections.find((s) => s.id === sectionId);
            if (!section) return;

            // Chercher la colonne (imbriquée ou dans un Container)
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
            const widget = {
                id,
                type: widgetType,
                props: this.getWidgetDefaultProps(widgetType),
            };

            column.widgets.splice(index, 0, widget);
            this.sync();
        },

        // Déplacement d'un widget à un index précis (avec zones)
        moveWidgetToIndex(
            fromSectionId,
            fromColumnId,
            widgetId,
            fromIndex,
            toSectionId,
            toColumnId,
            toIndex,
            fromZone,
            toZone
        ) {
            const fromSections =
                fromZone === "beforeNative"
                    ? this.data.beforeNative.sections
                    : this.data.afterNative.sections;

            const fromSection = fromSections.find(
                (s) => s.id === fromSectionId
            );
            if (!fromSection) return;

            // Chercher la colonne source (imbriquée / container)
            let fromColumn = fromSection.columns.find(
                (c) => c.id === fromColumnId
            );
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

            const widgetIndex = fromColumn.widgets.findIndex(
                (w) => w.id === widgetId
            );
            if (widgetIndex === -1) return;

            const widget = fromColumn.widgets.splice(widgetIndex, 1)[0];

            const toSections =
                toZone === "beforeNative"
                    ? this.data.beforeNative.sections
                    : this.data.afterNative.sections;

            const toSection = toSections.find((s) => s.id === toSectionId);
            if (!toSection) return;

            // Chercher la colonne cible (imbriquée / container)
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

            // Même colonne & même section & même zone → on garde le comportement standard
            if (
                fromSectionId === toSectionId &&
                fromColumnId === toColumnId &&
                fromZone === toZone &&
                toIndex > fromIndex
            ) {
                toColumn.widgets.splice(toIndex, 0, widget);
            } else {
                toColumn.widgets.splice(toIndex, 0, widget);
            }

            this.sync();
        },

        // Override moveColumn pour supporter les zones
        moveColumn(
            fromSectionId,
            fromColumnId,
            toSectionId,
            toColumnId,
            fromZone,
            toZone
        ) {
            const fromSections =
                fromZone === "beforeNative"
                    ? this.data.beforeNative.sections
                    : this.data.afterNative.sections;

            const toSections =
                toZone === "beforeNative"
                    ? this.data.beforeNative.sections
                    : this.data.afterNative.sections;

            const fromSection = fromSections.find(
                (s) => s.id === fromSectionId
            );
            if (!fromSection) return;

            const fromIndex = fromSection.columns.findIndex(
                (c) => c.id === fromColumnId
            );
            if (fromIndex === -1) return;

            const toSection = toSections.find((s) => s.id === toSectionId);
            if (!toSection) return;

            const toIndex = toSection.columns.findIndex(
                (c) => c.id === toColumnId
            );
            if (toIndex === -1) return;

            // If moving within the same section
            if (fromSectionId === toSectionId && fromZone === toZone) {
                const column = fromSection.columns.splice(fromIndex, 1)[0];
                fromSection.columns.splice(toIndex, 0, column);
            } else {
                // Moving to a different section or zone
                const column = fromSection.columns.splice(fromIndex, 1)[0];
                toSection.columns.splice(toIndex, 0, column);

                // Recalculate widths for both sections
                this.recalculateColumnWidths(fromSectionId, fromZone);
                this.recalculateColumnWidths(toSectionId, toZone);
            }

            this.sync();
        },

        // Override recalculateColumnWidths pour supporter les zones
        recalculateColumnWidths(sectionId, zone) {
            const sections =
                zone === "beforeNative"
                    ? this.data.beforeNative.sections
                    : this.data.afterNative.sections;

            const section = sections.find((s) => s.id === sectionId);
            if (!section || section.columns.length === 0) return;

            const newWidth = Math.floor(100 / section.columns.length);
            section.columns.forEach((col) => (col.width = newWidth));
        },

        // CONTAINER COLUMNS MANAGEMENT
        addColumnToContainer(sectionId, columnId, widgetId, zone = null) {
            // Si zone n'est pas fournie, essayer de la trouver
            if (!zone) {
                const result = this.findSection(sectionId);
                if (!result) return;
                zone = result.zone;
            }

            const sections =
                zone === "beforeNative"
                    ? this.data.beforeNative.sections
                    : this.data.afterNative.sections;

            const section = sections.find((s) => s.id === sectionId);
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

            if (!widget || widget.type !== "container" || !widget.props.columns)
                return;

            widget.props.columns.push({
                id: "col_" + Math.random().toString(36).slice(2, 8),
                width: 100 / (widget.props.columns.length + 1),
                widgets: [],
            });

            // Recalcul des largeurs équitables
            const equal = 100 / widget.props.columns.length;
            widget.props.columns.forEach((c) => {
                c.width = Math.round(equal);
            });
            this.sync();
        },

        removeColumnFromContainer(
            sectionId,
            columnId,
            widgetId,
            containerColumnId,
            zone = null
        ) {
            // Si zone n'est pas fournie, essayer de la trouver
            if (!zone) {
                const result = this.findSection(sectionId);
                if (!result) return;
                zone = result.zone;
            }

            const sections =
                zone === "beforeNative"
                    ? this.data.beforeNative.sections
                    : this.data.afterNative.sections;

            const section = sections.find((s) => s.id === sectionId);
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

            if (!widget || widget.type !== "container" || !widget.props.columns)
                return;
            if (widget.props.columns.length <= 1) return; // Garde au moins 1 colonne

            widget.props.columns = widget.props.columns.filter(
                (c) => c.id !== containerColumnId
            );

            // Recalcul des largeurs
            const equal = 100 / widget.props.columns.length;
            widget.props.columns.forEach((c) => {
                c.width = Math.round(equal);
            });

            // Supprimer la sélection si c'est la colonne sélectionnée
            if (
                this.selected?.type === "column" &&
                this.selected.id === containerColumnId
            ) {
                this.selected = null;
            }

            this.sync();
        },

        // Override moveWidget pour supporter les zones
        moveWidget(fromSec, fromCol, toSec, toCol, widgetId, fromZone, toZone) {
            const fromSections =
                fromZone === "beforeNative"
                    ? this.data.beforeNative.sections
                    : this.data.afterNative.sections;

            const s1 = fromSections.find((s) => s.id === fromSec);
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

            const toSections =
                toZone === "beforeNative"
                    ? this.data.beforeNative.sections
                    : this.data.afterNative.sections;

            const s2 = toSections.find((s) => s.id === toSec);
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

        // Méthode pour afficher les toasts
        showToast(message, type = "info") {
            if (typeof window.showToast === "function") {
                window.showToast(message, type);
            }
        },

        // Preview Modal
        showPreview: false,
        previewMode: 'modal', // 'modal' ou 'tab'

        openPreview(mode = 'modal') {
            this.previewMode = mode;

            const frontendUrl = document.querySelector('[data-frontend-url]')?.dataset.frontendUrl || 'http://localhost:3000';
            const pageSlug = document.querySelector('[data-page-slug]')?.dataset.pageSlug || 'default';
            const pageType = document.querySelector('[data-page-type]')?.dataset.pageType || 'page';

            // Construire l'URL en fonction du type de page
            let previewUrl;
            if (pageType === 'home') {
                // Page d'accueil principale
                previewUrl = `${frontendUrl}/?preview=1&t=${Date.now()}`;
            } else if (pageSlug === 'accueil' || pageSlug === 'home') {
                // Page d'accueil via slug
                previewUrl = `${frontendUrl}?preview=1&t=${Date.now()}`;
            } else if (pageType === 'category') {
                previewUrl = `${frontendUrl}/categories/${pageSlug}?preview=1&t=${Date.now()}`;
            } else if (pageType === 'product') {
                previewUrl = `${frontendUrl}/products/${pageSlug}?preview=1&t=${Date.now()}`;
            } else {
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

        // Override de la méthode save pour gérer la structure beforeNative/afterNative
        async save() {
            this.saving = true;

            try {
                const response = await fetch(config.saveUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": config.csrf,
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        content_json: JSON.stringify(this.data),
                    }),
                });

                if (response.ok) {
                    this.showToast("Contenu sauvegardé avec succès", "success");
                } else {
                    const error = await response.json();
                    this.showToast(
                        "Erreur lors de la sauvegarde: " +
                            (error.message || "Erreur inconnue"),
                        "error"
                    );
                }
            } catch (error) {
                console.error("Error saving:", error);
                this.showToast("Erreur lors de la sauvegarde", "error");
            } finally {
                this.saving = false;
            }
        },
    };
}

// Export global pour Alpine
window.pageBuilderNative = pageBuilderNative;

// S'assurer qu'Alpine est disponible avant d'enregistrer
if (typeof window.Alpine !== "undefined") {
    window.Alpine.data("pageBuilderNative", pageBuilderNative);
} else {
    // Si Alpine n'est pas encore chargé, attendre l'événement alpine:init
    document.addEventListener("alpine:init", () => {
        window.Alpine.data("pageBuilderNative", pageBuilderNative);
    });
}
