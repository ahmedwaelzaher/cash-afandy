/**
 * Initialize OverlayScrollbars on the given element.
 *
 * Besides the regular OverlayScrollbars options, three extras are supported
 * (usually set through `os-*` attributes on the element):
 *
 * - `media`: only activate while this media query matches, e.g. "(min-width: 992px)".
 * - `viewport`: selector for the viewport element, or "self" for the element itself.
 * - `slot`: selector for the element that holds the scrollbars, or "self".
 *
 * @param {string|HTMLElement} selector
 * @param {object} options
 * @returns {object|null}
 * @see https://kingsora.github.io/OverlayScrollbars
 */
return (selector, options = {}) => {
    const $element = $(selector);
    const element = $element.get(0);

    // Dark containers get the light scrollbar theme, and vice versa.
    const currentTheme = () => ($element.theme() === 'dark' ? 'os-theme-light' : 'os-theme-dark');

    const defaultOptions = {
        overflow: {
            y: 'scroll',
        },
        scrollbars: {
            theme: currentTheme(),
            autoHide: 'leave',
            autoHideDelay: 300,
            autoHideSuspend: true,
        },
    };

    const { media, viewport, slot, ...scrollbarOptions } = _.merge(getOptionsFromSelector(selector, 'os-'), options);
    options = _.merge(defaultOptions, scrollbarOptions);

    const resolve = (value) => (value === 'self' ? element : element.querySelector(value));

    const target = {
        target: element,
        ...(viewport && { elements: { viewport: resolve(viewport) } }),
        ...(slot && { scrollbars: { slot: resolve(slot) } }),
    };

    let instance = null;
    const mediaQuery = media ? window.matchMedia(media) : null;

    // Create or destroy the instance depending on whether the media query matches.
    const sync = () => {
        if (mediaQuery && !mediaQuery.matches) {
            instance?.destroy();
            instance = null;
            $element.removeAttr('data-overlayscrollbars-initialize');
            return;
        }

        if (instance) return;

        $element.attr('data-overlayscrollbars-initialize', '');
        instance = OverlayScrollbarsGlobal.OverlayScrollbars(target, options);
    };

    mediaQuery?.addEventListener('change', sync);
    document.addEventListener('theme:changed', () => instance?.options({ scrollbars: { theme: currentTheme() } }));

    sync();

    // Bootstrap modals are their own overflow container, so give each open
    // modal its own instance and pause the body scrolling behind it. Overlay
    // scrollbars take no layout space, so neither step shifts the page.
    // Bound through jQuery: dialogs removed on hide (e.g. bootbox) detach
    // before the native event dispatch, so it never bubbles to the document.
    if (element === document.body) {
        const modalInstance = (modal) => OverlayScrollbarsGlobal.OverlayScrollbars(modal);

        $(document).on('show.bs.modal', (event) => {
            if (!modalInstance(event.target)) {
                OverlayScrollbarsGlobal.OverlayScrollbars(
                    { target: event.target, elements: { viewport: event.target } },
                    _.merge({}, options, { overflow: { x: 'hidden' }, scrollbars: { theme: currentTheme() } }),
                );
            }

            instance?.options({ overflow: { y: 'hidden' } });
        });

        $(document).on('shown.bs.modal', (event) => modalInstance(event.target)?.update(true));

        $(document).on('hidden.bs.modal', (event) => {
            modalInstance(event.target)?.destroy();

            if (!document.querySelector('.modal.show')) {
                instance?.options({ overflow: { y: options.overflow.y } });
            }
        });
    }

    return instance;
};
