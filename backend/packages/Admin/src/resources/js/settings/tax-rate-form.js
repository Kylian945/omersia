// Tax rate form - Update rate unit based on type
document.addEventListener('DOMContentLoaded', () => {
    const typeSelect = document.getElementById('type');
    const rateUnit = document.getElementById('rate-unit');

    if (!typeSelect || !rateUnit) return;

    function updateRateUnit() {
        rateUnit.textContent = typeSelect.value === 'percentage' ? '%' : '€';
    }

    typeSelect.addEventListener('change', updateRateUnit);
    updateRateUnit();
});
