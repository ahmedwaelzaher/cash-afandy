// ==================================================
// Offer code decoding
// ==================================================
/**
 * Decode an offer code that was encoded with AES-GCM and base64.
 *
 * @param {string} encoded - The base64-encoded offer code.
 * @returns {Promise<string>} - The decoded offer code.
 */
async function decodeOfferCode(encoded) {
    let bytes = Uint8Array.from(atob(encoded), (character) => character.charCodeAt(0));
    let keyBytes = await crypto.subtle.digest('SHA-256', new TextEncoder().encode('cashafandy'));
    let key = await crypto.subtle.importKey('raw', keyBytes, 'AES-GCM', false, ['decrypt']);
    let decoded = await crypto.subtle.decrypt({ name: 'AES-GCM', iv: bytes.slice(0, 12) }, key, bytes.slice(12));

    return new TextDecoder().decode(decoded);
}

// ==================================================
// Header search suggestions
// ==================================================
$(() => {
    $('[data-afandy-search]').each((index, element) => {
        const $form = $(element);
        const $panel = $form.parent();
        const $input = $form.find('[name="afandy_search"]');
        const $category = $form.find('[name="afandy_category"]');
        const $button = $form.find('[data-afandy-search-button]');
        const $spinner = $form.find('[data-submit-spinner]');
        const $results = $panel.find('#afandy-search-results');
        let request;
        let revision = 0;
        let composing = false;
        let completed = false;
        let loadedSearch = null;
        let loadedCategory = null;
        let dismissed = false;

        const syncCategory = () => {
            const selected = $category.val();
            $form.find('[data-search-category]').each((_, option) => {
                const $option = $(option);
                const active = $option.attr('data-search-category') === selected;
                $option.toggleClass('active', active).attr('aria-pressed', String(active));
                $option.find('.ti-check').toggleClass('d-none', !active);
                if (active) {
                    $form.find('[data-search-type-label]').text($option.find('[data-search-category-label]').text());
                }
            });
        };
        const show = (visible) => {
            $results.prop('hidden', !visible);
            $input.attr('aria-expanded', String(visible));
        };
        const buttonState = (clear) => {
            completed = clear;
            $button.attr('type', clear ? 'button' : 'submit')
                .attr('aria-label', $button.attr(clear ? 'data-reset-label' : 'data-search-label'));
            $button.find('.ti').toggleClass('ti-search', !clear).toggleClass('ti-x', clear);
            $button.find('.ti').prop('hidden', false);
        };
        const cancel = () => {
            revision++;
            if (request) request.abort();
            request = null;
            $spinner.empty();
            $button.find('.ti').prop('hidden', false);
            $results.attr('aria-busy', 'false');
        };
        const updateUrl = (search) => {
            const url = new URL(window.location.href);
            if (search) url.searchParams.set('afandy_search', search);
            else url.searchParams.delete('afandy_search');
            url.searchParams.set('afandy_category', $category.val());
            window.history.replaceState(window.history.state, '', url.href);
            // Directory links may already be rendered when the header search changes.
            $('[data-directory] .site-store-filter, [data-directory-pagination] a').each((_, link) => {
                const target = new URL(link.href);
                if (search) target.searchParams.set('afandy_search', search);
                else target.searchParams.delete('afandy_search');
                target.searchParams.set('afandy_category', $category.val());
                link.href = target.href;
            });
            $('[data-afandy-category], [data-search-pagination] a').each((_, link) => {
                const target = new URL(link.href);
                if (target.searchParams.get('afandy_search') !== search) {
                    target.searchParams.delete('stores_page');
                    target.searchParams.delete('offers_page');
                }
                if (search) target.searchParams.set('afandy_search', search);
                else target.searchParams.delete('afandy_search');
                link.href = target.href;
            });
        };
        const load = () => {
            cancel();
            const search = $input.val().trim();
            updateUrl(search);
            if (Array.from(search).length < 2) {
                loadedSearch = null;
                $results.empty();
                buttonState(Boolean(search));
                show(false);
                return;
            }
            const currentRevision = revision;
            dismissed = false;
            const url = new URL(element.action);
            url.searchParams.set('afandy_search', search);
            const category = $category.val();
            url.searchParams.set('afandy_category', category);
            const country = new URL(window.location.href).searchParams.get('country');
            if (country) url.searchParams.set('country', country);
            buttonState(false);
            $results.empty().attr('aria-busy', 'true');
            show(false);
            $spinner.append($('<span class="spinner-border spinner-border-sm" role="status"></span>').attr('aria-label', $button.attr('data-search-label')));
            $button.find('.ti').prop('hidden', true);
            request = $.ajax({ url: url.href, dataType: 'json' })
                .done((response) => {
                    if (currentRevision !== revision) return;
                    loadedSearch = search;
                    loadedCategory = category;
                    $results.html(response.html);
                    // Do not reopen a dropdown that the user has left or dismissed.
                    show(!dismissed && $panel[0].contains(document.activeElement) && Boolean(response.html));
                })
                .fail((xhr, status) => {
                    if (status === 'abort' || currentRevision !== revision) return;
                    loadedSearch = null;
                    $results.empty().append($('<p class="p-3 mb-0 text-center" role="status"></p>').text(__('Error')));
                    show(!dismissed && $panel[0].contains(document.activeElement));
                })
                .always(() => {
                    if (currentRevision !== revision) return;
                    request = null;
                    $spinner.empty();
                    $results.attr('aria-busy', 'false');
                    buttonState(true);
                });
        };
        const debounced = _.debounce(load, 300);
        const clear = () => {
            debounced.cancel();
            cancel();
            $input.val('');
            loadedSearch = null;
            loadedCategory = null;
            $results.empty();
            buttonState(false);
            show(false);
            updateUrl('');
            $input.trigger('focus');
        };

        $form.on('submit', (event) => {
            event.preventDefault();
            debounced.cancel();
            load();
        });
        $form.on('click', '[data-search-category]', (event) => {
            const selected = $(event.currentTarget).attr('data-search-category');
            if (!['offers', 'stores'].includes(selected) || selected === $category.val()) return;
            debounced.cancel();
            $category.val(selected);
            loadedSearch = null;
            loadedCategory = null;
            syncCategory();
            load();
            $input.trigger('focus');
        });
        $button.on('click', (event) => {
            if (!completed) return;
            event.preventDefault();
            clear();
        });
        $input.on('input', () => {
            debounced.cancel();
            cancel();
            buttonState(false);
            show(false);
            if (!$input.val().trim()) clear();
            else if (!composing) debounced();
        }).on('compositionstart', () => {
            composing = true;
            debounced.cancel();
            cancel();
        }).on('compositionend', () => {
            composing = false;
            debounced();
        }).on('focus', () => {
            dismissed = false;
            if (loadedSearch === $input.val().trim() && loadedCategory === $category.val()) show(Boolean($results.children().length));
            else if (Array.from($input.val().trim()).length >= 2 && !request) load();
        });

        $panel.on('keydown', (event) => {
            if (composing || event.isComposing) return;
            if ($(event.target).closest('.site-search-type').length) return;
            if (event.key === 'Escape') {
                debounced.cancel();
                cancel();
                buttonState(Boolean($input.val().trim()));
                show(false);
                event.stopPropagation();
                event.preventDefault();
                if (document.activeElement !== $input[0]) $input.trigger('focus');
                dismissed = true;
                show(false);
                return;
            }
            if (!['ArrowDown', 'ArrowUp'].includes(event.key) || $results.prop('hidden')) return;
            const $links = $results.find('a');
            if (!$links.length) return;
            event.preventDefault();
            const index = $links.index(document.activeElement);
            const next = index + (event.key === 'ArrowDown' ? 1 : -1);
            if (index === -1) $links.eq(event.key === 'ArrowDown' ? 0 : -1).trigger('focus');
            else if (next < 0 || next >= $links.length) $input.trigger('focus');
            else $links.eq(next).trigger('focus');
        }).on('focusout', () => {
            setTimeout(() => {
                if (!$panel[0].contains(document.activeElement)) {
                    dismissed = true;
                    show(false);
                }
            }, 0);
        });
        $(document).on('click.afandySearch', (event) => {
            if (!$panel[0].contains(event.target)) {
                dismissed = true;
                show(false);
            }
        });
        $(window).on('popstate.afandySearch', () => {
            debounced.cancel();
            $input.val(new URL(window.location.href).searchParams.get('afandy_search') || '');
            const category = new URL(window.location.href).searchParams.get('afandy_category');
            $category.val(['offers', 'stores'].includes(category) ? category : 'offers');
            syncCategory();
            load();
        });
        if ($input.val().trim()) load();
    });
});

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
                        const headerSearch = new URL(window.location.href).searchParams.get('afandy_search');
                        if (headerSearch) filterUrl.searchParams.set('afandy_search', headerSearch);
                        else filterUrl.searchParams.delete('afandy_search');
                        const headerCategory = new URL(window.location.href).searchParams.get('afandy_category');
                        if (headerCategory) filterUrl.searchParams.set('afandy_category', headerCategory);
                        else filterUrl.searchParams.delete('afandy_category');
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
            const headerSearch = new URL(window.location.href).searchParams.get('afandy_search');
            if (headerSearch) url.searchParams.set('afandy_search', headerSearch);
            else url.searchParams.delete('afandy_search');
            const headerCategory = new URL(window.location.href).searchParams.get('afandy_category');
            if (headerCategory) url.searchParams.set('afandy_category', headerCategory);
            else url.searchParams.delete('afandy_category');
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
    $(document).on('click', '[data-offer-favorite]', function () {
        const $button = $(this);
        $button.attr('aria-pressed', $button.attr('aria-pressed') === 'true' ? 'false' : 'true');
    });
});

// ==================================================
// Shared offer reveal and clipboard feedback
// ==================================================
$(() => {
    const $modal = $('#site-offer-reveal');
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

        $modal.find('#site-offer-reveal-title').text($button.attr('data-offer-title'));
        $modal.find('[data-reveal-store]').text($button.attr('data-store-name'));
        $modal.find('[data-reveal-logo]').prop('hidden', !logo).find('img').attr('src', logo || null);
        $modal.find('[data-reveal-link]').prop('hidden', !url).find('a').attr('href', url || null);

        $code.text('');
        $copy.prop('disabled', true).find('[data-copy-label]').text($status.attr('data-copy-label'));
        $copy.find('.ti').removeClass('ti-check').addClass('ti-copy');
        $status.text($status.attr('data-copy-pending'));
        reveal = decodeOfferCode($button.attr('data-offer-code'));
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
    $header.find('.site-header-search').on('shown.bs.dropdown', (event) => {
        if ($(event.target).attr('aria-controls') === 'site-header-search') $('#navbar-search').trigger('focus');
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
