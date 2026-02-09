const modalManager = new window.ModalManager({
    assignRole: {
        modalId: 'assignRoleModal',
        onClose: (modalEl) => {
            modalEl.querySelector('#role_id').value = '';
        }
    }
});

function openAssignRoleModal(userId, userName) {
    const modalEl = document.getElementById('assignRoleModal');
    modalEl.querySelector('#userName').textContent = userName;
    modalEl.querySelector('#assignRoleForm').action = `/admin/settings/users/${userId}/roles/assign`;
    modalManager.open('assignRole');
}

function closeAssignRoleModal() {
    modalManager.close('assignRole');
}

// Exporter pour utilisation globale
window.openAssignRoleModal = openAssignRoleModal;
window.closeAssignRoleModal = closeAssignRoleModal;
