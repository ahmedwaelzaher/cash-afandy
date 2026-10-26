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
// Directory search and pagination
// ==================================================
$(() => {
    $('[data-directory-search]').each((index, element) => {
        const $input = $(element).find('[name="search"]');
        const $clear = $(element).find('[data-clear-directory-search]');
        const $section = $(element).closest('[data-directory]');
        const $results = $section.find('[data-directory-results]');
        const $count = $section.find('[data-directory-count]');
        let currentSearch = $input.val().trim();
        let request;
        let revision = 0;
        let composing = false;

        const cancelRequest = () => {
            revision++;
            if (request) request.abort();
            $results.attr('aria-busy', 'false');
        };

        const load = (url) => {
            cancelRequest();
            const currentRevision = revision;
            window.history.replaceState(window.history.state, '', url.href);
            $results.attr('aria-busy', 'true');
            request = $.ajax({ url: url.href, dataType: 'json' })
                .done((response) => {
                    if (currentRevision !== revision) return;
                    $results.html(response.html);
                    $count.text(response.countLabel);
                    currentSearch = url.searchParams.get('search') || '';
                    $section.find('.site-store-filter').each((index, link) => {
                        const filterUrl = new URL(link.href);
                        if (currentSearch) filterUrl.searchParams.set('search', currentSearch);
                        else filterUrl.searchParams.delete('search');
                        link.href = filterUrl.href;
                    });
                })
                .fail((xhr, status) => {
                    if (status !== 'abort' && currentRevision === revision) toastify().error(__('Error'));
                })
                .always(() => {
                    if (currentRevision === revision) $results.attr('aria-busy', 'false');
                });
        };

        const searchDirectory = () => {
            const url = new URL(window.location.href);
            const search = $input.val().trim();
            url.searchParams.delete('page');
            if (search) url.searchParams.set('search', search);
            else url.searchParams.delete('search');
            load(url);
        };

        const searchDebounced = _.debounce(() => {
            if (!composing && $input.val().trim() !== currentSearch) searchDirectory();
        }, 500);

        $input.on('input', () => {
            cancelRequest();
            $clear.toggleClass('d-none', !$input.val().length);
            searchDebounced();
        }).on('compositionstart', () => {
            composing = true;
            cancelRequest();
            searchDebounced.cancel();
        }).on('compositionend', () => {
            composing = false;
            searchDebounced();
        });

        $results.on('click', '[data-directory-pagination] a', (event) => {
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
            event.preventDefault();
            searchDebounced.cancel();
            const url = new URL(event.currentTarget.href);
            // Pagination must not discard a search that is still being debounced.
            if ($input.val().trim() !== currentSearch) searchDirectory();
            else load(url);
        });
        $clear.on('click', () => {
            searchDebounced.cancel();
            $input.val('');
            $clear.addClass('d-none');
            searchDirectory();
        });
    });

});

// ==================================================
// Sharing
// ==================================================
$(() => {
    $(document).on('click', '[data-share-url]', async function () {
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

});

// ==================================================
// Favorites
// ==================================================
$(() => {
    // Visual state only until favorites are connected to the backend.
    $(document).on('click', '[data-coupon-favorite]', function () {
        const $button = $(this);
        $button.attr('aria-pressed', $button.attr('aria-pressed') === 'true' ? 'false' : 'true');
    });
});

// ==================================================
// Shared coupon reveal and clipboard feedback
// ==================================================
$(() => {
    const $modal = $('#site-coupon-reveal');
    if (!$modal.length) return;

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

});

// ==================================================
// Offer filters
// ==================================================
$(() => {
    $('.site-offers').on('click', '[data-offer-filter]', function () {
        const $section = $(this).closest('.site-offers');
        const filter = $(this).attr('data-offer-filter');
        const attribute = $section.attr('data-offer-filter-attribute') || 'data-offer-category';

        $section.find('[data-offer-filter]').removeClass('btn-primary').attr('aria-pressed', 'false');
        $(this).addClass('btn-primary').attr('aria-pressed', 'true');
        $section.find(`[${attribute}]`).each((_, element) => {
            element.hidden = filter !== 'all' && !element.getAttribute(attribute).split(' ').includes(filter);
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
