$(() => {
    const $header = $('.site-navbar');
    if (!$header.length || $header.data('navbar-initialized')) return;
    $header.data('navbar-initialized', true);

    /* Tabler manages offcanvas focus, Escape, backdrop and scroll locking. */
    const updateHeader = () => {
        document.documentElement.style.setProperty('--site-navbar-height', `${$header.outerHeight() || 0}px`);
    };
    updateHeader();
    $(window).on('resize.website', updateHeader);
    if (window.ResizeObserver) {
        new ResizeObserver(updateHeader).observe($header[0]);
    }
    const $menu = $('#site-navbar-menu');
    $menu.on('show.bs.offcanvas hidden.bs.offcanvas', (event) => {
        $header.find('[data-bs-toggle="offcanvas"]').attr('aria-expanded', event.type === 'show' ? 'true' : 'false');
    });

    let framePending = false;
    const updateScroll = () => {
        $header.toggleClass('is-scrolled', window.scrollY > 10);
        framePending = false;
    };
    $(window).on('scroll.website', () => {
        if (framePending) return;
        framePending = true;
        requestAnimationFrame(updateScroll);
    });
    updateScroll();
});
