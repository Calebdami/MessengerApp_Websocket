import { ref, watch, onMounted } from 'vue';

const theme = ref(localStorage.getItem('messenger-theme') || 'dark');

export function useTheme() {

    function applyTheme(t) {
        const html = document.documentElement;
        // Retirer tous les thèmes
        html.classList.remove('dark', 'light');
        if (t === 'system') {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            html.classList.add(prefersDark ? 'dark' : 'light');
        } else {
            html.classList.add(t);
        }
        theme.value = t;
        localStorage.setItem('messenger-theme', t);
    }

    function setTheme(t) {
        applyTheme(t);
        // Sauvegarder en base via API
        window.axios?.post('/settings/preferences', { theme: t }).catch(() => {});
    }

    onMounted(() => {
        applyTheme(theme.value);
        // Écouter les changements système
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (theme.value === 'system') applyTheme('system');
        });
    });

    return { theme, setTheme };
}