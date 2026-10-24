// ==================================================
// Coupon code decoding
// ==================================================
/**
 * Decode a coupon code that was encoded with AES-GCM and base64.
 *
 * @param {string} encoded - The base64-encoded coupon code.
 * @returns {Promise<string>} - The decoded coupon code.
 */
async function decodeCouponCode(encoded) {
    let bytes = Uint8Array.from(atob(encoded), (character) => character.charCodeAt(0));
    let keyBytes = await crypto.subtle.digest('SHA-256', new TextEncoder().encode('cashafandy'));
    let key = await crypto.subtle.importKey('raw', keyBytes, 'AES-GCM', false, ['decrypt']);
    let decoded = await crypto.subtle.decrypt({ name: 'AES-GCM', iv: bytes.slice(0, 12) }, key, bytes.slice(12));

    return new TextDecoder().decode(decoded);
}

// ==================================================
// Website interactions
// ==================================================
$(() => {
    // ==================================================
    // Sharing
    // ==================================================
    $('[data-share-url]').on('click', async function () {
        const $button = $(this);
        const url = $button.attr('data-share-url');

        if (navigator.share) {
            try {
                await navigator.share({ title: $button.attr('data-share-title'), url });
                return;
            } catch (error) {
                if (error.name === 'AbortError') return;
            }
        }

        try {
            await copyToClipboard(url);
            toastify().success($button.attr('data-share-success'));
        } catch {
            toastify().error($button.attr('data-share-error'));
        }
    });

    // ==================================================
    // Favorites
    // ==================================================
    // Preview only; favorites are not persisted until backend integration.
    $('[data-coupon-favorite]').on('click', function () {
        const $button = $(this);
        $button.attr('aria-pressed', $button.attr('aria-pressed') === 'true' ? 'false' : 'true');
    });

    // ==================================================
    // Shared coupon reveal and clipboard feedback
    // ==================================================
    const $modal = $('#site-coupon-reveal');
    const $code = $modal.find('[data-revealed-code]');
    const $copy = $modal.find('[data-copy-revealed]');
    const $status = $modal.find('[data-copy-status]');
    let reveal;

    const copy = async () => {
        const currentReveal = reveal;
        $copy.find('[data-copy-label]').text($status.attr('data-copy-label'));
        $copy.find('.ti').removeClass('ti-check').addClass('ti-copy');
        try {
            await copyToClipboard($code.text());
            if (reveal !== currentReveal || !$modal.hasClass('show')) return;

            $copy.find('[data-copy-label]').text($status.attr('data-copied-label'));
            $copy.find('.ti').removeClass('ti-copy').addClass('ti-check');
            $status.text($status.attr('data-copy-success'));
        } catch {
            if (reveal !== currentReveal || !$modal.hasClass('show')) return;
            $status.text($status.attr('data-copy-error'));
        }
    };

    $modal.on('show.bs.modal', (event) => {
        const $button = $(event.relatedTarget);
        const logo = $button.attr('data-store-logo');
        const url = $button.attr('data-store-url');

        $modal.find('#site-coupon-reveal-title').text($button.attr('data-coupon-title'));
        $modal.find('[data-reveal-store]').text($button.attr('data-store-name'));
        $modal.find('[data-reveal-logo]').prop('hidden', !logo).find('img').attr('src', logo || null);
        $modal.find('[data-reveal-link]').prop('hidden', !url).find('a').attr('href', url || null);

        $code.text('');
        $copy.prop('disabled', true).find('[data-copy-label]').text($status.attr('data-copy-label'));
        $copy.find('.ti').removeClass('ti-check').addClass('ti-copy');
        $status.text($status.attr('data-copy-pending'));
        reveal = decodeCouponCode($button.attr('data-coupon-code'));
        reveal.catch(() => {});
    }).on('shown.bs.modal', async () => {
        const currentReveal = reveal;
        try {
            const code = await currentReveal;
            if (reveal !== currentReveal || !$modal.hasClass('show')) return;

            $code.text(code);
            $copy.prop('disabled', false);
            await copy();
        } catch {
            if (reveal !== currentReveal || !$modal.hasClass('show')) return;
            $status.text($status.attr('data-reveal-error'));
        }
    }).on('hidden.bs.modal', () => {
        reveal = null;
        $code.text('');
        $copy.prop('disabled', true);
    });

    $copy.on('click', copy);

    // ==================================================
    // Offer category filters
    // ==================================================
    $('.site-offers').on('click', '[data-offer-filter]', function () {
        const $section = $(this).closest('.site-offers');
        const filter = $(this).attr('data-offer-filter');

        $section.find('[data-offer-filter]').removeClass('btn-primary').attr('aria-pressed', 'false');
        $(this).addClass('btn-primary').attr('aria-pressed', 'true');
        $section.find('[data-offer-category]').each((_, element) => {
            element.hidden = filter !== 'all' && element.dataset.offerCategory !== filter;
        });
    });
});

// ==================================================
// Navbar sizing, menus and scroll behavior
// ==================================================
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
    const hideOnScroll = !$('body').hasClass('auth-page');
    let framePending = false;
    let previousScroll = window.scrollY;

    // Update the header state based on the current scroll position.
    const updateScroll = () => {
        // Ignore overscroll beyond the page boundaries.
        const scroll = Math.max(0, Math.min(window.scrollY, document.documentElement.scrollHeight - window.innerHeight));

        $header.toggleClass('is-scrolled', scroll > 10);

        if (scroll !== previousScroll) {
            // Keep open menus and focused controls visible.
            $header.toggleClass('is-hidden', hideOnScroll && scroll > headerHeight && scroll > previousScroll
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

// ==================================================
// Store marquee visibility and playback
// ==================================================
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
