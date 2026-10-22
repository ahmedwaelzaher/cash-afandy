$(() => {
    // Initialize the navbar once per page.
    const $header = $('.site-navbar');
    if (!$header.length || $header.data('navbar-initialized')) return;
    $header.data('navbar-initialized', true);

    // Keep the header height available to CSS and scroll calculations.
    let headerHeight = 0;

    // Update the header height on page load and window resize.
    const updateHeader = () => {
        headerHeight = $header.outerHeight() || 0;
        document.documentElement.style.setProperty('--site-navbar-height', `${headerHeight}px`);
    };

    updateHeader();
    $(window).on('resize.website', updateHeader);

    if (window.ResizeObserver) {
        new ResizeObserver(updateHeader).observe($header[0]);
    }

    // Sync the menu button state; Tabler handles the offcanvas behavior.
    const $menu = $('#site-navbar-menu');

    $menu.on('show.bs.offcanvas hidden.bs.offcanvas', (event) => {
        $header.find('[data-bs-toggle="offcanvas"]').attr('aria-expanded', event.type === 'show' ? 'true' : 'false');
    });

    // Keep the header visible while its controls are in use.
    $header.on('focusin show.bs.offcanvas show.bs.dropdown', () => $header.removeClass('is-hidden'));

    // Focus the search input after its dropdown opens.
    $header.find('.site-header-search').on('shown.bs.dropdown', () => {
        $('#navbar-search').trigger('focus');
    });

    // Track scroll direction to hide on the way down and reveal on the way up.
    let framePending = false;
    let previousScroll = window.scrollY;

    // Update the header state based on the current scroll position.
    const updateScroll = () => {
        // Ignore overscroll beyond the page boundaries.
        const scroll = Math.max(0, Math.min(window.scrollY, document.documentElement.scrollHeight - window.innerHeight));

        $header.toggleClass('is-scrolled', scroll > 10);

        if (scroll !== previousScroll) {
            // Keep open menus and focused controls visible.
            $header.toggleClass('is-hidden', scroll > headerHeight && scroll > previousScroll
                && !$header.find('.show, .showing, :focus-visible, input:focus').length);
        }

        previousScroll = scroll;
        framePending = false;
    };

    // Schedule at most one scroll update per animation frame.
    $(window).on('scroll.website', () => {
        if (framePending) return;
        framePending = true;
        requestAnimationFrame(updateScroll);
    });

    // Set the initial state, including restored scroll positions.
    updateScroll();
});

$(() => {
    const $marquees = $('.site-marquee');
    if (!$marquees.length) return;

    // Repeated logos remain clickable without duplicating keyboard or screen-reader stops.
    $marquees.find('[aria-hidden="true"]').removeAttr('inert').find('a').attr('tabindex', '-1');

    const updateMarquees = () => {
        $marquees.each((_, element) => {
            const visible = !window.IntersectionObserver || element.dataset.marqueeVisible === 'true';
            const paused = document.hidden || !visible;
            element.style.setProperty('--marquee-play-state', paused ? 'paused' : 'running');
        });
    };

    // Pause off-screen marquees and background tabs without restarting the animation.
    if (window.IntersectionObserver) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(({ target, isIntersecting }) => {
                target.dataset.marqueeVisible = String(isIntersecting);
            });
            updateMarquees();
        });

        $marquees.each((_, element) => observer.observe(element));
    }

    $(document).on('visibilitychange.marquee', updateMarquees);
    updateMarquees();
});
