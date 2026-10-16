(() => {
    'use strict';

    // The key to use when storing the theme in localStorage
    window.themerKey = window.themerKey || 'theme';
    window.themeConfig = window.themeConfig || {};

    const config = Object.assign(
        {
            theme: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light',
            base: 'default',
            font: 'sans-serif',
            primary: 'blue',
            radius: 1,
        },
        window.themeConfig,
    );

    for (const key in config) {
        const storedTheme = localStorage.getItem(`${themerKey}-${key}`);
        const selectedValue = storedTheme ? storedTheme : config[key];

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
