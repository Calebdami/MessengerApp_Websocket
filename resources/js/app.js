import './bootstrap.js';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import Aura from '@primevue/themes/aura';
import '../css/app.css';
import 'primeicons/primeicons.css';

createInertiaApp({
    title: (title) => title ? `${title} — Messenger` : 'Messenger',
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createPinia())
            .use(ZiggyVue)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: '.dark',
                        cssLayer: false,
                    }
                }
            })
            .use(ToastService)
            .use(ConfirmationService)
            .mount(el);
    },
    progress: { color: '#c4b5fd', includeCSS: true, showSpinner: true },
});