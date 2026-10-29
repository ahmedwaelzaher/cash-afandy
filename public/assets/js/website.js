// ==================================================
// Hero heading
// ==================================================
$(() => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    $('[word-rotate]').each((index, element) => {
        const { words, duration, delay } = getOptionsFromSelector(element, 'rotate-');
        if (words.length < 2) return;

        const items = words.map((word, index) => {
            const item = document.createElement('span');
            item.className = 'site-hero-word' + (index === 0 ? ' is-active' : '');
            item.textContent = word;
            return item;
        });
        element.replaceChildren(...items);
        let current = 0;

        window.setInterval(() => {
            const outgoing = items[current];
            const previousWidth = outgoing.getBoundingClientRect().width;
            current = (current + 1) % items.length;
            const incoming = items[current];
            outgoing.classList.remove('is-active');
            incoming.classList.add('is-active');

            const options = { duration, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' };
            element.animate([
                { inlineSize: previousWidth + 'px' },
                { inlineSize: incoming.getBoundingClientRect().width + 'px' },
            ], options);
            outgoing.animate([
                { transform: 'translateY(0)', opacity: 1 },
                { transform: 'translateY(-100%)', opacity: 0 },
            ], options);
            incoming.animate([
                { transform: 'translateY(100%)', opacity: 0 },
                { transform: 'translateY(0)', opacity: 1 },
            ], options);
        }, delay);
    });
});

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
            $('[data-directory]:not([data-directory-mode="search"]) .site-store-filter, [data-directory-pagination] a').each((_, link) => {
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
        $(window).on('directory:updated.afandySearch', (event, href, isSearch) => {
            if (!isSearch) return;
            const url = new URL(href);
            const category = url.searchParams.get('afandy_category') || 'offers';
            const search = url.searchParams.get('afandy_search') || '';
            if ($category.val() === category && $input.val() === search) return;
            debounced.cancel();
            cancel();
            $category.val(category);
            $input.val(search);
            loadedSearch = null;
            loadedCategory = null;
            $results.empty();
            buttonState(Boolean(search));
            show(false);
            syncCategory();
        });
        if ($input.val().trim()) load();
    });
});

// ==================================================
// Directory search, category navigation and pagination
// ==================================================
$(() => {
    $('[data-directory]').each((index, element) => {
        const $section = $(element);
        const isSearch = $section.attr('data-directory-mode') === 'search';
        const searchKey = isSearch ? 'afandy_search' : 'search';
        const categoryKey = isSearch ? 'afandy_category' : 'category';
        const $input = $section.find('[data-directory-search] [name="search"]');
        const $clear = $section.find('[data-clear-directory-search]');
        const $results = $section.find('[data-directory-results]');
        let currentSearch = new URL(window.location.href).searchParams.get(searchKey) || '';
        let request;
        let revision = 0;
        let composing = false;

        const cancelRequest = () => {
            revision++;
            if (request) request.abort();
            request = null;
            $results.attr('aria-busy', 'false');
        };
        const syncLinks = (url, category) => {
            $section.find('.site-store-filter').each((_, link) => {
                const target = new URL(link.href);
                const active = (target.searchParams.get(categoryKey) || '') === category;
                $(link).toggleClass('active', active).attr('aria-current', active ? 'page' : null);
                if (currentSearch) target.searchParams.set(searchKey, currentSearch);
                else target.searchParams.delete(searchKey);
                if (!isSearch) {
                    for (const key of ['afandy_search', 'afandy_category']) {
                        if (url.searchParams.has(key)) target.searchParams.set(key, url.searchParams.get(key));
                        else target.searchParams.delete(key);
                    }
                }
                link.href = target.href;
            });
        };
        const load = (url, historyMode = 'replace') => {
            cancelRequest();
            const currentRevision = revision;
            $results.attr('aria-busy', 'true');
            request = $.ajax({ url: url.href, dataType: 'json', headers: isSearch ? { 'X-Search-Results': '1' } : {} })
                .done((response) => {
                    if (currentRevision !== revision) return;
                    $results.html(response.html);
                    $section.find('[data-directory-label]').text(response.categoryLabel);
                    if (response.pageTitle) $section.find('[data-search-title]').text(response.pageTitle);
                    currentSearch = url.searchParams.get(searchKey) || '';
                    $input.val(currentSearch);
                    $clear.toggleClass('d-none', !currentSearch);
                    syncLinks(url, response.category || '');
                    if (response.counts) {
                        $section.find('[data-afandy-category]').each((_, link) => {
                            $(link).find('.badge').text(Number(response.counts[$(link).attr('data-afandy-category')]).toLocaleString('en-US'));
                        });
                    }
                    if (historyMode === 'push' && url.href !== window.location.href) window.history.pushState(window.history.state, '', url.href);
                    else if (historyMode === 'replace') window.history.replaceState(window.history.state, '', url.href);
                    $(window).trigger('directory:updated', [url.href, isSearch]);
                })
                .fail((xhr, status) => {
                    if (status === 'abort' || currentRevision !== revision) return;
                    toastify().error(__('Error'));
                    if (historyMode === 'none' || status === 'parsererror') window.location.assign(url.href);
                })
                .always(() => {
                    if (currentRevision !== revision) return;
                    request = null;
                    $results.attr('aria-busy', 'false');
                });
        };
        const searchDirectory = () => {
            const url = new URL(window.location.href);
            const search = $input.val().trim();
            url.searchParams.delete('page');
            if (search) url.searchParams.set(searchKey, search);
            else url.searchParams.delete(searchKey);
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
        $section.on('click', '.site-store-filter, [data-directory-pagination] a, [data-search-pagination] a', (event) => {
            const link = event.currentTarget;
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0 || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
            const target = new URL(link.href);
            const url = new URL(window.location.href);
            if (target.origin !== url.origin || target.pathname !== url.pathname) return;
            event.preventDefault();
            searchDebounced.cancel();
            if ($(link).hasClass('site-store-filter')) {
                if (target.searchParams.has(categoryKey)) url.searchParams.set(categoryKey, target.searchParams.get(categoryKey));
                else url.searchParams.delete(categoryKey);
                for (const key of ['page', 'stores_page', 'offers_page']) url.searchParams.delete(key);
            } else {
                for (const key of ['page', 'stores_page', 'offers_page']) {
                    if (target.searchParams.has(key)) url.searchParams.set(key, target.searchParams.get(key));
                    else url.searchParams.delete(key);
                }
            }
            // Keep a newly typed search when navigating before its debounce has fired.
            if ($input.length) {
                const search = $input.val().trim();
                if (search !== currentSearch) for (const key of ['page', 'stores_page', 'offers_page']) url.searchParams.delete(key);
                if (search) url.searchParams.set(searchKey, search);
                else url.searchParams.delete(searchKey);
            }
            load(url, 'push');
        });
        $clear.on('click', () => {
            searchDebounced.cancel();
            $input.val('');
            $clear.addClass('d-none');
            searchDirectory();
        });
        $(window).on('popstate.directory', () => {
            searchDebounced.cancel();
            load(new URL(window.location.href), 'none');
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
// Login modal and protected website actions
// ==================================================
$(() => {
    const $modal = $('#site-login-modal');
    if (!$modal.length) return;

    const modal = tabler.Modal.getOrCreateInstance($modal[0]);
    const $form = $modal.find('[data-login-modal-form]');
    const $error = $modal.find('[data-login-error]');
    let pendingToken;
    let trigger;
    let submitting = false;
    let preparing = false;
    let cancelling = false;

    // Use the current address, including any filters changed through AJAX.
    $modal.on('click', '[data-login-return]', function () {
        const url = new URL(this.href);
        url.searchParams.set('return_to', window.location.href);
        this.href = url.href;
    });

    $(document).on('website:login-required', (event, options = {}) => {
        if (submitting || preparing || cancelling) return;
        preparing = true;
        trigger = options.trigger || document.activeElement;
        $error.prop('hidden', true).empty();
        const $trigger = $(trigger).attr('aria-busy', 'true');
        $.ajax({
            url: $modal.attr('data-pending-action-url'),
            method: 'POST',
            dataType: 'json',
            loginRequiredHandled: true,
            data: {
                name: options.action?.name || 'navigate',
                offer_id: options.action?.offer_id,
                return_to: options.returnTo || window.location.href,
            },
        }).done((response) => {
            if (response.authenticated) {
                window.location.assign(response.redirect);
                return;
            }
            pendingToken = response.token;
            const $drawer = $('.site-navbar-menu.show');
            if ($drawer.length) {
                $drawer.one('hidden.bs.offcanvas', () => modal.show());
                tabler.Offcanvas.getOrCreateInstance($drawer[0]).hide();
            } else {
                modal.show();
            }
        }).fail((xhr) => {
            if (xhr.status !== 419) toastify().error(__('Error'));
        }).always(() => {
            preparing = false;
            $trigger.removeAttr('aria-busy');
        });
    });

    // Expired sessions still pass through the server's authentication middleware.
    $(document).ajaxError((event, xhr, settings) => {
        if (xhr.status !== 401 || settings.loginRequiredHandled) return;
        const url = new URL(settings.url, document.baseURI);
        if (url.origin !== window.location.origin) return;
        $(document).trigger('website:login-required');
    });

    $modal.on('shown.bs.modal', () => {
        $form.find('[name="email"]').trigger('focus');
    }).on('hide.bs.modal', (event) => {
        if (submitting || cancelling) {
            event.preventDefault();
            return;
        }
        if (!pendingToken) return;

        // Confirm cancellation on the server before discarding the local token.
        event.preventDefault();
        cancelling = true;
        $.ajax({
            url: $modal.attr('data-pending-action-url'),
            method: 'DELETE',
            data: { token: pendingToken },
            loginRequiredHandled: true,
        }).done(() => {
            pendingToken = null;
            cancelling = false;
            modal.hide();
        }).fail((xhr) => {
            if (xhr.status !== 419) $error.text(__('Error')).prop('hidden', false);
        }).always(() => { cancelling = false; });
    }).on('hidden.bs.modal', () => {
        $form.find('[name="password"]').val('');
        appendErrorsToForm($form);
        if (trigger?.isConnected && $(trigger).is(':visible')) $(trigger).trigger('focus');
        trigger = null;
    });

    // Stop here before the shared form handler performs a full-page submission.
    $modal.on('submit', '[data-login-modal-form]', function (event) {
        event.preventDefault();
        event.stopPropagation();
        if (submitting || cancelling || !validateForm($form, true)) return;

        submitting = true;
        $error.prop('hidden', true).empty();
        appendErrorsToForm($form);
        const $submit = $form.find('[type="submit"]').prop('disabled', true).attr('aria-busy', 'true');
        const $spinner = $('<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>').prependTo($submit);
        let completed = false;
        $.ajax({
            url: this.action,
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            headers: { 'X-Login-Modal': 'true' },
            loginRequiredHandled: true,
        }).done((response) => {
            completed = true;
            $('meta[name="csrf-token"]').attr('content', response.csrfToken);
            $('input[name="_token"]').val(response.csrfToken);
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': response.csrfToken } });
            pendingToken = null;
            window.location.assign(response.redirect || window.location.href);
        }).fail((xhr) => {
            if (xhr.status === 422 && xhr.responseJSON?.payload) {
                appendErrorsToForm($form, xhr.responseJSON.payload);
                $form.find('.is-invalid').first().trigger('focus');
            } else if (xhr.status !== 419) {
                $error.text(xhr.responseJSON?.message || __('Error')).prop('hidden', false);
            }
        }).always(() => {
            if (completed) return;
            submitting = false;
            $submit.prop('disabled', false).removeAttr('aria-busy');
            $spinner.remove();
        });
    });
});

// ==================================================
// Favorites
// ==================================================
$(() => {
    const pending = new Set();
    const countdowns = new Map();
    const favoriteItems = (id) => $('[data-favorite-item]').filter((_, element) => element.getAttribute('data-favorite-item') === id);
    const pauseCountdown = (id) => {
        const countdown = countdowns.get(id);
        if (!countdown) return;
        clearTimeout(countdown.timeout);
        countdowns.delete(id);
        return Math.max(0, countdown.deadline - performance.now());
    };
    const startCountdown = (id, remaining) => {
        pauseCountdown(id);
        const $items = favoriteItems(id);
        if (!$items.length) return;
        const duration = Number($items.find('[data-favorite-removed]').attr('data-countdown-seconds')) * 1000;
        const countdown = { deadline: performance.now() + (remaining ?? duration), timeout: null };
        countdowns.set(id, countdown);

        const tick = () => {
            const left = Math.max(0, countdown.deadline - performance.now());
            $items.find('[data-favorite-seconds]').text(Math.ceil(left / 1000));
            $items.find('.site-favorite-countdown').css('--favorite-progress', `${left / duration * 360}deg`);
            if (left === 0) {
                const focused = $items.toArray().some((item) => item.contains(document.activeElement));
                countdowns.delete(id);
                $items.remove();
                $('[data-favorites-empty]').prop('hidden', $('[data-favorite-item]').length !== 0);
                if (focused) $('#favorites-title').trigger('focus');
                return;
            }
            countdown.timeout = setTimeout(tick, 100);
        };
        tick();
    };

    $(document).on('click', '[data-offer-favorite]', function () {
        const $button = $(this);
        if ($button.attr('data-favorite-authenticated') !== 'true') {
            $(document).trigger('website:login-required', [{
                trigger: this,
                action: { name: 'favorite_offer', offer_id: $button.attr('data-offer-favorite') },
            }]);
            return;
        }

        const id = $button.attr('data-offer-favorite');
        const url = $button.attr('data-favorite-url');
        if (!url || pending.has(id)) return;
        const favorited = $button.attr('aria-pressed') !== 'true';
        // Keep an undo request from racing the countdown that removes its card.
        const pausedTime = favorited ? pauseCountdown(id) : undefined;
        const buttons = () => $('[data-offer-favorite]').filter((_, element) => element.getAttribute('data-offer-favorite') === id);
        let $focusTarget;

        pending.add(id);
        buttons().prop('disabled', true).attr('aria-busy', 'true');
        $.ajax({ url, method: favorited ? 'PUT' : 'DELETE', dataType: 'json', loginRequiredHandled: true })
            .done((response) => {
                const active = response.favorited;
                buttons().each((_, element) => {
                    const $target = $(element);
                    $target.attr('aria-pressed', String(active))
                        .attr('aria-label', $target.attr(active ? 'data-remove-label' : 'data-add-label'));
                    $target.find('.ti').toggleClass('ti-heart-filled', active).toggleClass('ti-heart', !active);
                });
                favoriteItems(id).each((_, element) => {
                    const $item = $(element);
                    const $card = $item.children('.site-offer-card');
                    $item.toggleClass('is-removed', !active);
                    $card.prop('inert', !active);
                    $item.find('[data-favorite-removed]').prop('hidden', active);
                    $focusTarget = $item.find(active ? '.site-offer-card [data-offer-favorite]' : '[data-favorite-removed] [data-offer-favorite]');
                });
                if (active) pauseCountdown(id);
                else startCountdown(id);
            })
            .fail((xhr) => {
                if (xhr.status === 401) {
                    $(document).trigger('website:login-required', [{
                        trigger: this,
                        action: { name: favorited ? 'favorite_offer' : 'unfavorite_offer', offer_id: id },
                    }]);
                    return;
                }
                if (xhr.status !== 419) toastify().error(__('Unable to update favorites. Please try again.'));
                if (pausedTime !== undefined) startCountdown(id, pausedTime);
            })
            .always(() => {
                pending.delete(id);
                buttons().prop('disabled', false).removeAttr('aria-busy');
                if ($focusTarget) $focusTarget.trigger('focus');
            });
    });

    // Store following remains separate from saved offers until its backend is connected.
    $(document).on('click', '[data-store-favorite]', function () {
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

// ==================================================
// Browser push subscriptions
// ==================================================
$(() => {
    const config = window.AppPushConfig;
    const endpoints = window.WebsitePush;
    if (!config?.enabled || !endpoints) return;

    const $panel = $('[data-browser-notifications]').prop('hidden', false);
    const $enable = $panel.find('[data-push-enable]');
    const $disable = $panel.find('[data-push-disable]');
    const preferenceKey = 'cash-afandy:browser-push-enabled';
    const messageKey = 'cash-afandy:last-push-message';
    let clientPromise;
    let busy = false;
    let lastSync = 0;

    const render = (message, enabled = false) => {
        $panel.find('[data-push-status]').text(message);
        $enable.prop('hidden', enabled).prop('disabled', busy);
        $disable.prop('hidden', !enabled).prop('disabled', busy);
    };
    const wanted = () => localStorage.getItem(preferenceKey) === 'true';
    const remember = (enabled) => localStorage.setItem(preferenceKey, String(enabled));
    const removeSubscription = () =>
        $.ajax({
            url: endpoints.destroyUrl,
            method: 'DELETE',
            dataType: 'json',
            loginRequiredHandled: true,
        });
    const client = () => {
        if (!clientPromise) {
            clientPromise = (async () => {
                const prefix = `https://www.gstatic.com/firebasejs/${config.sdkVersion}`;
                const [{ initializeApp }, sdk] = await Promise.all([
                    import(`${prefix}/firebase-app.js`),
                    import(`${prefix}/firebase-messaging.js`),
                ]);
                if (!(await sdk.isSupported())) throw new Error('unsupported');
                const messaging = sdk.getMessaging(initializeApp(config.firebase));
                sdk.onMessage(messaging, (payload) => {
                    if (!wanted()) return;
                    $(document).trigger('website:notification-received', [payload.data?.notification_id]);
                    if (document.hidden) return;
                    const id = payload.data?.notification_id || payload.messageId;
                    if (id && localStorage.getItem(messageKey) === id) return;
                    if (id) localStorage.setItem(messageKey, id);
                    const text = [payload.notification?.title, payload.notification?.body].filter(Boolean).join(' — ');
                    if (text) toastify().info($('<span>').text(text).html());
                });
                return { sdk, messaging };
            })().catch((error) => {
                clientPromise = null;
                throw error;
            });
        }
        return clientPromise;
    };
    const run = async (action) => {
        if (busy) return;
        busy = true;
        $enable.add($disable).prop('disabled', true);
        try {
            // Coordinate registration and opt-out across tabs in the same browser.
            if (navigator.locks) await navigator.locks.request('cash-afandy:push-subscription', action);
            else await action();
        } catch {
            render(__('Unable to update browser notifications. Please try again.'), wanted());
            $enable.prop('hidden', false);
        } finally {
            busy = false;
            $enable.add($disable).prop('disabled', false);
        }
    };
    const synchronize = async () => {
        if (!wanted()) return;
        if (Notification.permission !== 'granted') {
            await removeSubscription();
            localStorage.removeItem(preferenceKey);
            render(__('Allow notifications in your browser settings to enable them.'));
            return;
        }
        const { sdk, messaging } = await client();
        const registration = await window.AppServiceWorker;
        if (!registration) throw new Error('worker unavailable');
        const worker = registration.installing || registration.waiting || registration.active;
        if (!worker) throw new Error('worker unavailable');
        if (worker.state !== 'activated') {
            await new Promise((resolve, reject) => {
                worker.addEventListener('statechange', () => {
                    if (worker.state === 'activated') resolve();
                    if (worker.state === 'redundant') reject(new Error('worker unavailable'));
                });
            });
        }
        const token = await sdk.getToken(messaging, {
            vapidKey: config.vapidKey,
            serviceWorkerRegistration: registration,
        });
        if (!token || !wanted()) return;
        await $.ajax({
            url: endpoints.storeUrl,
            method: 'POST',
            data: { token },
            dataType: 'json',
            loginRequiredHandled: true,
        });
        if (!wanted()) {
            await removeSubscription();
            return;
        }
        lastSync = Date.now();
        render(__('Notifications are enabled for this browser.'), true);
    };
    const disable = async () => {
        try {
            await removeSubscription();
            localStorage.removeItem(preferenceKey);
        } finally {
            // Revoke the browser token even if the application server is temporarily unreachable.
            try {
                const { sdk, messaging } = await client();
                await sdk.deleteToken(messaging);
            } catch {
                /* Server-side detachment remains sufficient when Firebase is unreachable. */
            }
        }
        render(__('Browser notifications are disabled.'));
    };

    if (!window.isSecureContext || !('Notification' in window) || !('serviceWorker' in navigator)) {
        render(__('Browser notifications are not supported on this device.'));
        $enable.prop('disabled', true);
        return;
    }
    try {
        render(wanted() ? __('Updating browser notifications…') : __('Browser notifications are disabled.'), wanted());
        if (wanted()) run(synchronize);
        else if (localStorage.getItem(preferenceKey) === 'false') run(disable);
    } catch {
        render(__('Browser notifications are not supported on this device.'));
        $enable.prop('disabled', true);
        return;
    }

    $enable.on('click', async () => {
        if (busy) return;
        $enable.prop('disabled', true);
        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                render(__('Allow notifications in your browser settings to enable them.'));
                return;
            }
            remember(true);
            await run(synchronize);
        } catch {
            render(__('Unable to update browser notifications. Please try again.'));
        } finally {
            $enable.prop('disabled', false);
        }
    });
    $disable.on('click', () => {
        remember(false);
        run(disable);
    });
    $(window).on('storage.browserPush', (event) => {
        if (event.originalEvent.key !== preferenceKey) return;
        if (wanted()) run(synchronize);
        else render(__('Browser notifications are disabled.'));
    });
    $(document).on('visibilitychange.browserPush', () => {
        if (!document.hidden && wanted() && Date.now() - lastSync > 300000) run(synchronize);
        else if (!document.hidden && localStorage.getItem(preferenceKey) === 'false') run(disable);
    });
});
