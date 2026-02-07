import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'packages/Admin/src/resources/js/toast.js',
                'packages/Admin/src/resources/js/api-key.js',
                'packages/Admin/src/resources/js/dashboard-chart.js',
                'packages/Admin/src/resources/js/dashboard.js',
                'packages/Admin/src/resources/js/orders-realtime.js',
                'packages/Admin/src/resources/js/gdpr-realtime.js',
                'packages/Admin/src/resources/js/products-stock-realtime.js',
                'packages/Admin/src/resources/js/order-paid-sound-notification.js',
                'packages/Admin/src/resources/js/page-builder.js',
                'packages/Admin/src/resources/js/page-builder-native.js',
                'packages/Admin/src/resources/js/preview-modal.js',
                'packages/Admin/src/resources/js/products.js',
                'packages/Admin/src/resources/js/quill-editor.js',
                'packages/Admin/src/resources/js/shipping-methods.js',
                'packages/Admin/src/resources/js/theme-activation.js',
                'packages/Admin/src/resources/js/modules/upload-form.js',
                'packages/Admin/src/resources/js/modules-positions.js',
                'packages/Admin/src/resources/js/media/media-library.js',
                'packages/Admin/src/resources/js/media-picker.js',
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
