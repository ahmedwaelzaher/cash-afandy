/* ---------------------------------
 * Observe initiators
 * --------------------------------- */

$(document).ready(() => {
    // Initialize the visibility plugin.
    RedotVisibility.init();

    // Watch for DOM changes and call the init function.
    const observer = new MutationObserver(() => {
        window.init();
    });

    // Listen for DOM changes and call the init function.
    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });

    // Call the init function on page load.
    window.init();
});

/* ---------------------------------
 * Anchor links
 * --------------------------------- */

// The <base> tag makes bare "#hash" hrefs resolve against the site root,
// which triggers a full navigation. Handle same-page anchors manually.
// Add the `disable-anchor` attribute to a link to opt out of this behavior.
$(document).on('click', 'a[href^="#"]:not([data-bs-toggle]):not([disable-anchor])', (event) => {
    if (event.isDefaultPrevented()) return;
    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    const hash = $(event.currentTarget).attr('href');

    // A bare "#" has no target, but left alone it would resolve against the
    // <base> tag and navigate to the site root — swallow it instead.
    if (hash === '#') return event.preventDefault();

    const target = document.getElementById(hash.slice(1));
    if (!target) return;

    event.preventDefault();
    target.scrollIntoView({ behavior: 'smooth' });
    history.pushState(null, '', location.pathname + location.search + hash);
});

/* ---------------------------------
 * Bootbox
 * --------------------------------- */

bootbox.setDefaults({
    animate: true,
    backdrop: 'static',
    centerVertical: true,
    container: 'body',
});

/* ---------------------------------
 * Glare
 * --------------------------------- */

$(document).on('click', '[data-lightbox]', (event) => {
    if (event.isDefaultPrevented()) return;
    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    event.preventDefault();
    Glare.open([event.currentTarget], { defaultType: 'iframe' });
});

/* ---------------------------------
 * Ajax
 * --------------------------------- */

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
    },
    statusCode: {
        419: () => {
            bootbox.dialog({
                title: __('Session Expired'),
                message: __('Your session has expired. Please refresh the page to continue.'),
                onEscape: false,
                buttons: {
                    confirm: {
                        label: __('Refresh'),
                        className: 'btn-primary',
                        callback: () => window.location.reload(),
                    },
                },
            });
        },
    },
});

/* ---------------------------------
 * Redot Visibility
 * --------------------------------- */

// Disable validation on hidden elements
$(document).on('visibility:updated', '[visible-when]', (event, visible) => {
    const $container = $(event.target);
    const $targets = $container.is('[validation]') ? $container : $container.find('[validation]');

    if (visible) {
        $targets.removeAttr('disable-validation');
    } else {
        $targets.attr('disable-validation', true);
    }
});

/* ---------------------------------
 * Redot Validator
 * --------------------------------- */

// Disable HTML5 validation
$('form').attr('novalidate', true);

$(document).on('submit', 'form:not([disable-validation])', (event) => {
    event.preventDefault();

    const $form = $(event.target);
    const isValid = validateForm($form, true);

    // No errors, submit the form
    if (isValid) {
        const $submit = $form.find('[type="submit"]');
        const $spinner = $('<span class="spinner-border spinner-border-sm me-2" role="status"></span>');

        $submit.prepend($spinner);
        $submit.prop('disabled', true);

        return event.target.submit();
    }
});
