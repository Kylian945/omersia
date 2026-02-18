/**
 * Theme Activation Handler
 * Handles theme activation with widget compatibility checking
 */

function initThemeActivation() {
    // Handle theme activation
    document.querySelectorAll('.activate-theme-btn').forEach(button => {
        if (button.dataset.themeActivationBound === 'true') {
            return;
        }
        button.dataset.themeActivationBound = 'true';

        button.addEventListener('click', async function() {
            const themeId = this.dataset.themeId;

            // Open modal
            window.dispatchEvent(new CustomEvent('open-modal', {
                detail: { name: 'confirm-theme-activation' }
            }));

            // Reset content
            document.getElementById('theme-comparison-content').innerHTML = getLoadingHTML();
            document.getElementById('theme-activation-actions').classList.add('hidden');

            try {
                // Fetch comparison data
                const response = await fetch(`/admin/apparence/theme/${themeId}/compare-widgets`);
                const data = await response.json();

                if (!data.has_incompatibilities) {
                    // No issues, activate directly
                    submitActivation(themeId);
                    return;
                }

                // Show comparison details
                document.getElementById('theme-comparison-content').innerHTML =
                    buildComparisonHTML(data);
                document.getElementById('theme-activation-actions').classList.remove('hidden');

                // Set form action
                document.getElementById('theme-activation-form').action =
                    `/admin/apparence/theme/${themeId}/activate`;

            } catch (error) {
                console.error('Error fetching theme comparison:', error);
                document.getElementById('theme-comparison-content').innerHTML = getErrorHTML();
                document.getElementById('theme-activation-actions').classList.remove('hidden');
                document.getElementById('theme-activation-form').action =
                    `/admin/apparence/theme/${themeId}/activate`;
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initThemeActivation);
} else {
    initThemeActivation();
}

/**
 * Submit theme activation form
 */
function submitActivation(themeId) {
    const action = `/admin/apparence/theme/${themeId}/activate`;

    // Prefer the existing form rendered by Blade (@csrf already present)
    const existingForm = document.getElementById('theme-activation-form');
    if (existingForm) {
        existingForm.action = action;
        existingForm.submit();
        return;
    }

    // Fallback: build a form dynamically if modal form is not available
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.content ||
        document.querySelector('input[name="_token"]')?.value;

    if (!csrfToken) {
        console.error('Theme activation failed: CSRF token not found.');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action;

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;

    form.appendChild(csrfInput);
    document.body.appendChild(form);
    form.submit();
}

/**
 * Build comparison HTML from data
 */
function buildComparisonHTML(data) {
    let html = `<div class="space-y-4">`;

    // Warning header
    html += `
        <div class="rounded-lg bg-amber-50 border border-amber-200 p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h4 class="text-sm font-semibold text-amber-900">Attention : Incompatibilités détectées</h4>
                    <p class="text-xs text-amber-800 mt-1">
                        Le nouveau thème ne supporte pas certains widgets utilisés dans vos pages actuelles.
                        <strong>${data.total_widgets_to_remove} widget(s)</strong> seront supprimés.
                    </p>
                </div>
            </div>
        </div>
    `;

    // Removed widgets section
    if (data.removed_widgets && data.removed_widgets.length > 0) {
        html += buildRemovedWidgetsHTML(data.removed_widgets);
    }

    // Affected pages section
    if (data.affected_pages && data.affected_pages.length > 0) {
        html += buildAffectedPagesHTML(data.affected_pages);
    }

    // Added widgets section
    if (data.added_widgets && data.added_widgets.length > 0) {
        html += buildAddedWidgetsHTML(data.added_widgets);
    }

    html += `</div>`;
    return html;
}

/**
 * Build removed widgets HTML
 */
function buildRemovedWidgetsHTML(widgets) {
    let html = `
        <div>
            <h5 class="text-xs font-semibold text-gray-900 mb-2">Widgets qui seront supprimés :</h5>
            <div class="space-y-1">
    `;

    widgets.forEach(widget => {
        html += `
            <div class="flex items-center gap-2 text-xs text-red-700 bg-red-50 px-3 py-1.5 rounded-lg">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span class="font-medium">${widget.label || widget.type}</span>
            </div>
        `;
    });

    html += `
            </div>
        </div>
    `;

    return html;
}

/**
 * Build affected pages HTML
 */
function buildAffectedPagesHTML(pages) {
    let html = `
        <div>
            <h5 class="text-xs font-semibold text-gray-900 mb-2">Pages affectées :</h5>
            <div class="max-h-48 overflow-y-auto space-y-2">
    `;

    pages.forEach(page => {
        html += `
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="text-xs font-medium text-gray-900">${page.title}</div>
                        <div class="text-xxxs text-gray-500 mt-0.5">
                            Type: ${page.page_type} • Langue: ${page.locale}
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xxxs font-medium bg-red-100 text-red-800">
                        ${page.incompatible_widgets.length} widget(s)
                    </span>
                </div>
                <div class="mt-2 flex flex-wrap gap-1">
        `;

        page.incompatible_widgets.forEach(widgetType => {
            html += `<span class="text-xxxs px-2 py-0.5 bg-red-100 text-red-700 rounded">${widgetType}</span>`;
        });

        html += `
                </div>
            </div>
        `;
    });

    html += `
            </div>
        </div>
    `;

    return html;
}

/**
 * Build added widgets HTML
 */
function buildAddedWidgetsHTML(widgets) {
    let html = `
        <div>
            <h5 class="text-xs font-semibold text-gray-900 mb-2">Nouveaux widgets disponibles :</h5>
            <div class="space-y-1">
    `;

    widgets.forEach(widget => {
        html += `
            <div class="flex items-center gap-2 text-xs text-green-700 bg-green-50 px-3 py-1.5 rounded-lg">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="font-medium">${widget.label || widget.type}</span>
            </div>
        `;
    });

    html += `
            </div>
        </div>
    `;

    return html;
}

/**
 * Get loading HTML
 */
function getLoadingHTML() {
    return `
        <div class="flex items-center justify-center py-8">
            <div class="animate-spin h-8 w-8 border-4 border-gray-300 border-t-gray-900 rounded-full"></div>
        </div>
    `;
}

/**
 * Get error HTML
 */
function getErrorHTML() {
    return `
        <div class="rounded-lg bg-red-50 border border-red-200 p-4">
            <p class="text-xs text-red-800">
                Une erreur est survenue lors de l'analyse du thème. Voulez-vous continuer ?
            </p>
        </div>
    `;
}
