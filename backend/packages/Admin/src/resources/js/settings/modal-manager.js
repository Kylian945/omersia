// Gestion générique des modales pour les pages de settings
class ModalManager {
    constructor(config) {
        this.config = config;
        this.init();
    }

    init() {
        // Écouter les clics sur le backdrop pour fermer les modales
        Object.keys(this.config).forEach(modalName => {
            const modalId = this.config[modalName].modalId;
            const modalEl = document.getElementById(modalId);
            if (modalEl) {
                modalEl.addEventListener('click', (e) => {
                    if (e.target === modalEl) {
                        this.close(modalName);
                    }
                });
            }
        });
    }

    open(modalName, ...args) {
        const modal = this.config[modalName];
        if (!modal) return;

        const modalEl = document.getElementById(modal.modalId);
        if (!modalEl) return;

        modalEl.classList.remove('hidden');

        // Appeler le callback d'ouverture si défini
        if (modal.onOpen) {
            modal.onOpen(modalEl, ...args);
        }
    }

    close(modalName) {
        const modal = this.config[modalName];
        if (!modal) return;

        const modalEl = document.getElementById(modal.modalId);
        if (!modalEl) return;

        modalEl.classList.add('hidden');

        // Appeler le callback de fermeture si défini
        if (modal.onClose) {
            modal.onClose(modalEl);
        }
    }
}

// Exporter pour utilisation globale
window.ModalManager = ModalManager;
