import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { clearClientApiCredentials } from '@/utils/apiAuthStorage';
import { ensureSanctumCsrfCookie } from '@/utils/apiCsrf';
import { syncApiTokenFromSession } from '@/utils/syncApiTokenFromSession';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

function syncApiAuthFromInertiaPage(page) {
    const user = page?.props?.auth?.user ?? null;
    if (user?.id) {
        void ensureSanctumCsrfCookie().then(() => {
            const path = typeof window !== 'undefined' ? window.location.pathname : '';
            // Admin Inertia: không ghi JWT vào localStorage (token hết hạn → 401 khi upload).
            if (path.startsWith('/admin')) {
                clearClientApiCredentials();
                return;
            }
            return syncApiTokenFromSession(user);
        });
    } else {
        clearClientApiCredentials();
    }
}

router.on('success', (event) => {
    syncApiAuthFromInertiaPage(event.detail.page);
});

createInertiaApp({
    title: title => `${title} - ${appName}`,
    resolve: name =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        syncApiAuthFromInertiaPage(props.initialPage);
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
});
