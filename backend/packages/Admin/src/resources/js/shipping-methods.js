// Gestion des modales pour les shipping methods

// Modal Zone
function openZoneModal() {
    document.getElementById('zoneModal').classList.remove('hidden');
    document.getElementById('zoneModal').classList.add('flex');
}

function closeZoneModal() {
    document.getElementById('zoneModal').classList.add('hidden');
    document.getElementById('zoneModal').classList.remove('flex');
}

// Modal Tarif
function openRateModal() {
    document.getElementById('rateModal').classList.remove('hidden');
    document.getElementById('rateModal').classList.add('flex');
}

function closeRateModal() {
    document.getElementById('rateModal').classList.add('hidden');
    document.getElementById('rateModal').classList.remove('flex');
}

// Modal Confirmation
let deleteFormId = '';

function confirmDelete(type, id, name) {
    const messages = {
        zone: `Êtes-vous sûr de vouloir supprimer la zone "${name}" ? Cette action est irréversible.`,
        rate: `Êtes-vous sûr de vouloir supprimer le tarif "${name}" ? Cette action est irréversible.`
    };

    document.getElementById('confirmMessage').textContent = messages[type];
    deleteFormId = `delete-${type}-${id}`;
    document.getElementById('confirmModal').classList.remove('hidden');
    document.getElementById('confirmModal').classList.add('flex');
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.add('hidden');
    document.getElementById('confirmModal').classList.remove('flex');
    deleteFormId = '';
}

function submitDelete() {
    if (deleteFormId) {
        document.getElementById(deleteFormId).submit();
    }
}

// Gestion de l'affichage des sections de configuration
function toggleConfigurationSections() {
    const useWeightPricing = document.getElementById('use_weight_based_pricing');
    const useZonePricing = document.getElementById('use_zone_based_pricing');
    const zonesSection = document.getElementById('zones-section');
    const ratesSection = document.getElementById('rates-section');

    if (!useWeightPricing || !useZonePricing) return;

    const updateVisibility = () => {
        const showZones = useZonePricing.checked;
        const showRates = useWeightPricing.checked || useZonePricing.checked;

        if (zonesSection) {
            if (showZones) {
                zonesSection.style.display = 'block';
                setTimeout(() => zonesSection.style.opacity = '1', 10);
            } else {
                zonesSection.style.opacity = '0';
                setTimeout(() => zonesSection.style.display = 'none', 300);
            }
        }

        if (ratesSection) {
            if (showRates) {
                ratesSection.style.display = 'block';
                setTimeout(() => ratesSection.style.opacity = '1', 10);
            } else {
                ratesSection.style.opacity = '0';
                setTimeout(() => ratesSection.style.display = 'none', 300);
            }
        }
    };

    // Add transition styles
    if (zonesSection) {
        zonesSection.style.transition = 'opacity 0.3s ease-in-out';
        zonesSection.style.opacity = '0';
    }
    if (ratesSection) {
        ratesSection.style.transition = 'opacity 0.3s ease-in-out';
        ratesSection.style.opacity = '0';
    }

    // Initial state
    updateVisibility();

    // Listen to changes
    useWeightPricing.addEventListener('change', updateVisibility);
    useZonePricing.addEventListener('change', updateVisibility);
}

// Gestion des événements
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser l'affichage des sections
    toggleConfigurationSections();

    // Fermer les modales en cliquant à l'extérieur
    document.addEventListener('click', function(event) {
        const modals = ['zoneModal', 'rateModal', 'confirmModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal && event.target === modal) {
                if (modalId === 'zoneModal') closeZoneModal();
                else if (modalId === 'rateModal') closeRateModal();
                else if (modalId === 'confirmModal') closeConfirmModal();
            }
        });
    });

    // Fermer avec Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeZoneModal();
            closeRateModal();
            closeConfirmModal();
        }
    });
});
