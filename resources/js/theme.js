const KEY = 'audit-master-theme';

/** Thème clair / sombre / système, mémorisé côté navigateur. */
export function initTheme() {
    const stored = localStorage.getItem(KEY);

    if (stored === 'dark' || stored === 'light') {
        document.documentElement.dataset.theme = stored;
    }

    paint();

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const current = document.documentElement.dataset.theme
                || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = next;
            localStorage.setItem(KEY, next);
            paint();
        });
    });
}

function paint() {
    const isDark = document.documentElement.dataset.theme === 'dark'
        || (!document.documentElement.dataset.theme && matchMedia('(prefers-color-scheme: dark)').matches);

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.textContent = isDark ? '☀' : '☾';
        button.setAttribute('aria-label', isDark ? 'Passer au thème clair' : 'Passer au thème sombre');
        button.setAttribute('title', isDark ? 'Thème clair' : 'Thème sombre');
    });
}
