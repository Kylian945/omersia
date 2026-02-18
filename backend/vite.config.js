import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'packages/Admin/src/resources/js/core/toast.js',
                'packages/Admin/src/resources/js/settings/api-key.js',
                'packages/Admin/src/resources/js/dashboard/chart.js',
                'packages/Admin/src/resources/js/dashboard/realtime.js',
                'packages/Admin/src/resources/js/orders/realtime.js',
                'packages/Admin/src/resources/js/settings/gdpr/realtime.js',
                'packages/Admin/src/resources/js/products/stock-realtime.js',
                'packages/Admin/src/resources/js/orders/paid-sound-notification.js',
                'packages/Admin/src/resources/js/builder/page-builder.js',
                'packages/Admin/src/resources/js/builder/page-builder-native.js',
                'packages/Admin/src/resources/js/builder/preview-modal.js',
                'packages/Admin/src/resources/js/products/index.js',
                'packages/Admin/src/resources/js/core/quill-editor.js',
                'packages/Admin/src/resources/js/settings/shipping-methods.js',
                'packages/Admin/src/resources/js/apparence/theme-activation.js',
                'packages/Admin/src/resources/js/modules/upload-form.js',
                'packages/Admin/src/resources/js/modules/positions.js',
                'packages/Admin/src/resources/js/media/media-library.js',
                'packages/Admin/src/resources/js/media/picker.js',
                'packages/Admin/src/resources/js/apparence/ecommerce-pages-form.js',
                'packages/Admin/src/resources/js/settings/modal-manager.js',
                'packages/Admin/src/resources/js/settings/roles.js',
                'packages/Admin/src/resources/js/settings/permissions.js',
                'packages/Admin/src/resources/js/settings/users.js',
                'packages/Admin/src/resources/js/settings/stripe-config.js',
                'packages/Admin/src/resources/js/settings/tax-rate-form.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost',
        },
    },
});
