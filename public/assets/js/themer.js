(() => {
    'use strict';

    // The key to use when storing the theme in localStorage
    window.themerKey = window.themerKey || 'theme';
    window.themeConfig = window.themeConfig || {};
    const fonts = (window.themeConfig.fonts ||= {});
    const root = document.documentElement;
    const getThemeFont = () => fonts[root.lang] || fonts[root.dataset.defaultLocale] || 'sans-serif';

    window.applyThemeFont = () => {
        const font = getThemeFont();
        root.setAttribute('data-bs-theme-font', font);
        if (font === 'sans-serif') {
            root.style.removeProperty('--tblr-body-font-family');
        } else {
            root.style.setProperty('--tblr-body-font-family', `${font}, var(--tblr-font-sans-serif)`);
        }
    };

    window.applyThemeFont();

    const config = {
        theme: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light',
        base: 'default',
        primary: 'blue',
        radius: 1,
    };

    for (const key in config) {
        const storedTheme = localStorage.getItem(`${themerKey}-${key}`);
        const selectedValue = storedTheme || (window.themeConfig[key] ?? config[key]);

        // Set the attribute on the document element
        document.documentElement.setAttribute(`data-bs-${key === 'theme' ? 'theme' : 'theme-' + key}`, selectedValue);
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[set-theme]').forEach((element) => {
            element.addEventListener('click', (event) => {
                event.preventDefault();

                let theme = element.getAttribute('set-theme');

                document.documentElement.setAttribute('data-bs-theme', theme);
                localStorage.setItem(`${themerKey}-theme`, theme);

                // Dispatch a custom event to let other scripts know the theme has changed
                document.dispatchEvent(new CustomEvent('theme:changed'));
            });
        });
    });
})();
