// Stripe configuration - Toggle password visibility for API keys
// Note: Ce fichier est chargé dynamiquement, donc on n'attend pas DOMContentLoaded
document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('data-toggle-password');
        const input = document.getElementById(targetId);

        if (!input) return;

        const isPassword = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPassword ? 'text' : 'password');

        // Toggle de l'icône pour feedback visuel
        const icon = btn.querySelector('svg');
        if (icon) {
            icon.classList.toggle('text-neutral-700', !isPassword);
            icon.classList.toggle('text-neutral-400', isPassword);
        }
    });
});
