const modalManager = new window.ModalManager({
    create: {
        modalId: 'createModal'
    },
    edit: {
        modalId: 'editModal',
        onOpen: (modalEl, permissionId) => {
            fetch(`/admin/settings/permissions/${permissionId}/edit`)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const form = doc.querySelector('form');
                    modalEl.querySelector('#editModalContent').innerHTML = form.outerHTML;
                });
        }
    }
});

function openCreateModal() {
    modalManager.open('create');
}

function closeCreateModal() {
    modalManager.close('create');
}

function openEditModal(permissionId) {
    modalManager.open('edit', permissionId);
}

function closeEditModal() {
    modalManager.close('edit');
}

// Exporter pour utilisation globale
window.openCreateModal = openCreateModal;
window.closeCreateModal = closeCreateModal;
window.openEditModal = openEditModal;
window.closeEditModal = closeEditModal;
