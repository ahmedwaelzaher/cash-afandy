/* ---------------------------------
 * jQuery Extensions
 * --------------------------------- */

if (typeof window.$ !== 'undefined') {
    $.fn.extend({
        /**
         * Check if the given element has the given attribute.
         *
         * @param {string} name
         * @returns {boolean}
         */
        hasAttr: function (name) {
            let $element = this;

            // Early return if the element is not found
            if ($element.length === 0) {
                return false;
            }

            return $element.get(0).hasAttribute(name);
        },

        /**
         * Get the theme of the given element.
         *
         * @param {object} $element
         * @returns {string}
         */
        theme: function () {
            let $element = this;

            let attr = 'data-bs-theme';
            let theme = $element.closest(`[${attr}]`).attr(attr);

            // If the theme is inherit, return the theme of the html element
            if (theme === 'inherit') return $('html').attr(attr);

            return theme;
        },
    });

    /**
     * Query the given selector.
     *
     * @param {string} selector
     * @param {string|object} context
     * @returns {object}
     */
    window.$.query = function (selector, context = document) {
        // Get the context jQuery object
        const $context = context instanceof jQuery ? context : $(context);

        // Early return if the selector is already a jQuery object
        if (selector instanceof jQuery) return selector;

        let patterns = [
            selector,
            `#${selector}`,
            `.${selector}`,
            `[name="${selector}"]`,
            `[name^="${selector}"]`,
            `[${selector}]`,
        ];

        try {
            for (const pattern of patterns) {
                if ($context.find(pattern).length) {
                    return $context.find(pattern);
                }
            }
        } catch (error) {
            // ...
        }

        return $(selector);
    };
}

/* ---------------------------------
 * Translations
 * --------------------------------- */

/**
 * Translate the given key with the given parameters.
 *
 * @param {string} key
 * @param {object} params
 * @returns {string}
 */
function __(key, params = {}) {
    if (typeof window.__translations === 'undefined') {
        return key;
    }

    let translation = window.__translations[key] || key;

    for (const [param, value] of Object.entries(params)) {
        translation = translation.replaceAll(`:${param}`, value);
    }

    return translation;
}

/* ---------------------------------
 * Type & Value Helpers
 * --------------------------------- */

/**
 * Check if the given value is JSON.
 *
 * @param {string} value
 * @returns {boolean}
 */
function isJson(value) {
    try {
        JSON.parse(value);
    } catch (error) {
        return false;
    }

    return true;
}

/**
 * Convert the given string to a primitive value.
 *
 * @param {string} string
 * @returns any
 */
function stringToPrimitive(string) {
    const primitives = {
        true: true,
        false: false,
        null: null,
        undefined: undefined,
    };

    // If the value is a primitive, return it.
    if (string in primitives) return primitives[string];

    // If the value is a property of the window object, return it.
    if (string.startsWith('window.')) return _.get(window, string.replace('window.', ''), string);

    // If the value starts with `return`, evaluate it and return the result.
    if (string.startsWith('return ')) return new Function(string)();

    // If the value is a numeric string, return it.
    if (string.match(/^-?\d+$/)) return Number(string);

    // Try to parse the value as JSON and return it.
    return isJson(string) ? JSON.parse(string) : string;
}

/**
 * Slugify the given string.
 *
 * @param {string} string
 * @param {object} options
 * @returns {string}
 */
function slugify(string, options = {}) {
    const defaultOptions = {
        replacement: '-',
        remove: undefined,
        lower: true,
        strict: false,
        trim: true,
    };

    const opts = {
        ...defaultOptions,
        ...options,
    };

    let slug = string.normalize().replace(opts.remove, '').replace(/\s+/g, ' ');

    if (opts.strict) {
        slug = slug.replace(/[^A-Za-z0-9\s]/g, '');
    }

    if (opts.trim) {
        slug = slug.trim();
    }

    slug = slug.replace(/\s+/g, opts.replacement);

    if (opts.lower) {
        slug = slug.toLowerCase();
    }

    return slug;
}

/* ---------------------------------
 * Fuzzy Search
 * --------------------------------- */

/**
 * Score how well `query` fuzzy-matches `text`.
 * Returns a higher score for better matches, or null when there is no match.
 *
 * Matching rules:
 * - Exact substring matches rank highest (earlier + denser is better)
 * - Otherwise characters must appear in order (subsequence)
 * - Consecutive hits and matches at word starts score higher
 *
 * @param {string} query
 * @param {string} text
 * @returns {number|null}
 */
function fuzzyScore(query, text) {
    if (!query || !text) return null;

    query = query.toLowerCase();
    text = text.toLowerCase();

    // Exact substring: prefer earlier and denser matches
    const index = text.indexOf(query);
    if (index !== -1) {
        return 1000 - index + (query.length / text.length) * 100;
    }

    let qi = 0;
    let score = 0;
    let consecutive = 0;
    let lastMatch = -1;

    for (let ti = 0; ti < text.length && qi < query.length; ti++) {
        if (text[ti] !== query[qi]) continue;

        consecutive = lastMatch === ti - 1 ? consecutive + 1 : 1;
        score += 1 + consecutive * 5;

        // Bonus for matching at the start of a word / segment
        if (ti === 0 || /[\s\-_\/]/.test(text[ti - 1])) {
            score += 10;
        }

        lastMatch = ti;
        qi++;
    }

    return qi === query.length ? score : null;
}

/* ---------------------------------
 * Confirmation Dialogs
 * --------------------------------- */

/**
 * Show a confirmation dialog before executing the given action.
 *
 * @param {function} action
 * @param {object} options
 */
function warnBeforeAction(action, options = {}) {
    const defaultOptions = {
        size: 'small',
        title: __('Are you sure?'),
        message: __('This action cannot be undone.'),
        onEscape: true,
        backdrop: true,
        buttons: {
            cancel: {
                label: __('No'),
                className: 'btn',
            },
            confirm: {
                label: __('Yes'),
                className: 'btn-danger',
                callback: function () {
                    return action.call(this);
                },
            },
        },
    };

    bootbox.dialog(_.merge(defaultOptions, options));
}

/**
 * Wait for the user to confirm the given message.
 *
 * @param {object} options
 */
function awaitConfirmation(options = {}) {
    return new Promise((resolve, reject) => {
        const defaultOptions = {
            size: 'small',
            message: __('Are you sure?'),
            onEscape: function () {
                reject();
            },
            backdrop: true,
            buttons: {
                cancel: {
                    label: __('No'),
                    className: 'btn',
                    callback: function () {
                        return reject();
                    },
                },
                confirm: {
                    label: __('Yes'),
                    className: 'btn-primary',
                    callback: function () {
                        return resolve();
                    },
                },
            },
        };

        bootbox.dialog(_.merge(defaultOptions, options));
    });
}

/* ---------------------------------
 * Form Validation
 * --------------------------------- */

/**
 * Validate the given form.
 *
 * @param {object} $form
 * @returns {boolean}
 */
function validateForm($form, verbose = false) {
    const errors = RedotValidator.errors($form);

    // No errors, submit the form
    if (Object.keys(errors).length === 0) {
        return true;
    }

    // Append the errors to the form if verbose mode is enabled
    if (verbose) {
        if ($form.hasAttr('toast-errors')) {
            Object.values(errors).forEach((messages) => toastify().error(Object.values(messages)[0]));
            markInvalidInputs($form, errors);
        } else {
            appendErrorsToForm($form, errors);
            scrollToFirstError($form);
        }
    }

    return false;
}

/**
 * Append the given errors to the given form.
 *
 * @param {object} $form
 * @param {object} errors
 * @returns {void}
 */
function appendErrorsToForm($form, errors = {}) {
    $form.find('.invalid-feedback').remove();
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.has-invalid-feedback').removeClass('has-invalid-feedback');

    for (const [key, value] of Object.entries(errors)) {
        const normalizedKey = key.replace(/\.([^\.]+)/g, '[$1]');
        const possibleKeys = [key, `${key}[]`, normalizedKey, `${normalizedKey}[]`];

        let $input = $form.find(possibleKeys.map((key) => `[name="${key}"]`).join(', '));

        // If the input is not found, try to find it by the validation key.
        if ($input.length === 0) {
            $form.find(`[validation-key]`).each(function () {
                const key = $(this).attr('validation-key');

                if (possibleKeys.includes($(this).attr(key))) {
                    $input = $(this);
                }
            });
        }

        let $container = null;

        if ($input.attr('validation-container')) {
            $container = $($input.attr('validation-container'));
        } else if ($form.attr('validation-container')) {
            $container = $($form.attr('validation-container'));
        } else {
            $container = $input.parent();
        }

        const $feedback = $(`<div class="invalid-feedback"></div>`);

        $input.addClass('is-invalid');
        $container.addClass('has-invalid-feedback').append($feedback);

        let messages = Object.values(value);
        let message = messages.shift();

        if (messages.length) {
            // If there are more than one error, add a counter for the remaining errors
            message += ' ' + __('(and :count more error)', { count: messages.length });
        }

        $feedback.html(`<strong>${message}</strong>`);

        if ($input.closest('.input-group').length) {
            $input.closest('.input-group').addClass('has-invalid-feedback');
        }

        $input.parents('.tab-pane').each(function () {
            const tabpane = $(this).attr('id');
            $(`[href="#${tabpane}"]`).addClass('has-invalid-feedback');
        });
    }
}

/**
 * Mark the inputs matching the given errors as invalid, without appending
 * any inline feedback message.
 *
 * @param {object} $form
 * @param {object} errors
 * @returns {void}
 */
function markInvalidInputs($form, errors = {}) {
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.has-invalid-feedback').removeClass('has-invalid-feedback');

    for (const key of Object.keys(errors)) {
        const normalizedKey = key.replace(/\.([^\.]+)/g, '[$1]');
        const possibleKeys = [key, `${key}[]`, normalizedKey, `${normalizedKey}[]`];

        let $input = $form.find(possibleKeys.map((key) => `[name="${key}"]`).join(', '));

        // If the input is not found, try to find it by the validation key.
        if ($input.length === 0) {
            $form.find(`[validation-key]`).each(function () {
                const key = $(this).attr('validation-key');

                if (possibleKeys.includes($(this).attr(key))) {
                    $input = $(this);
                }
            });
        }

        $input.addClass('is-invalid');

        if ($input.closest('.input-group').length) {
            $input.closest('.input-group').addClass('has-invalid-feedback');
        }

        $input.parents('.tab-pane').each(function () {
            const tabpane = $(this).attr('id');
            $(`[href="#${tabpane}"]`).addClass('has-invalid-feedback');
        });
    }
}

function scrollToFirstError($form) {
    // Get the first error element
    const $firstError = $form.find('.is-invalid').first();

    // Early return if the first error element is not found
    if ($firstError.length === 0) return;

    // Early return if the form has the attribute 'dont-scroll-to-error'
    if ($form.hasAttr('dont-scroll-to-error')) return;

    // Show the tab containing the first error element
    $firstError.parents('.tab-pane').each(function () {
        const tabpane = $(this).attr('id');
        $(`[href="#${tabpane}"]`).tab('show');
    });

    // Scroll to the first error element
    $firstError.get(0).scrollIntoView({
        behavior: 'smooth',
        block: 'center',
    });

    // Early return if the form has the attribute 'dont-toast'
    if ($form.hasAttr('dont-toast')) return;

    toastify().error(__('You have errors in your form. Please correct them and try again.'));
}

/* ---------------------------------
 * Form Fields
 * --------------------------------- */

/**
 * Serialize the given fields.
 *
 * @param {object} $fields
 * @returns {object}
 */
function serializeFields($fields, key = 'name') {
    const data = {};

    $fields.find(`[${key}]`).each(function () {
        const identifier = $(this).attr(key).replace(/\[\]$/, '');

        // Set the field value to the data object.
        _.set(data, identifier, getFieldValue($(this), $fields, key));
    });

    return data;
}

/**
 * Get the value of the given field.
 *
 * @param {object} $field
 * @param {object} form
 * @param {string} key
 * @returns any
 */
function getFieldValue($field, form = 'body', key = 'name') {
    const type = $field.attr('type');
    const identifier = $field.attr(key);

    if (type === 'checkbox' && $field.closest('.form-switch').length) {
        return $field.is(':checked');
    }

    if (type === 'checkbox') {
        const checked = $(form).find(`[${key}="${identifier}"]:checked`);
        return checked.map((i, el) => $(el).val()).get();
    }

    if (type === 'radio') {
        return $(form).find(`[${key}="${identifier}"]:checked`).val() || null;
    }

    if (type === 'number' && $field.hasAttr('as-text') === false) {
        return +$field.val();
    }

    if (window.hugerte && hugerte.get($field.attr('id'))) {
        return hugerte.get($field.attr('id')).getContent();
    }

    if ($field.data('uploader')) {
        return $field.data('uploader').getFiles();
    }

    return $field.val();
}

/**
 * Deserialize the given data to the given fields.
 *
 * @param {object} $fields
 * @param {object} data
 * @returns {void}
 */
function deserializeFields($fields, data = {}, key = 'name') {
    $fields.find(`[${key}]`).each(function () {
        const $field = $(this);
        const identifier = $(this).attr(key).replace(/\[\]$/, '');
        const value = _.get(data, identifier);

        // Early return if the value is not found.
        if (typeof value === 'undefined') {
            return;
        }

        setFieldValue($field, value, $fields, key);

        // Trigger the change event after setting the value.
        $field.trigger('change');
    });
}

/**
 * Set the value of the given field.
 *
 * @param {object} $field
 * @param {any} value
 * @param {object} form
 * @param {string} key
 * @returns {void}
 */
function setFieldValue($field, value, form = 'body', key = 'name') {
    const type = $field.attr('type');
    const identifier = $field.attr(key);

    // Early return if the field is a file input.
    if (type === 'file') {
        return;
    }

    if (type === 'checkbox' && $field.closest('.form-switch').length) {
        return $field.prop('checked', value);
    }

    if (type === 'checkbox') {
        const checked = _.castArray(value);
        const checkboxes = $(form).find(`[${key}="${identifier}"]`);

        // Uncheck all checkboxes before checking the selected ones.
        checkboxes.prop('checked', false);

        return checkboxes.filter((i, el) => checked.includes($(el).val())).prop('checked', true);
    }

    if (type === 'radio') {
        return $(form).find(`[${key}="${identifier}"][value="${value}"]`).prop('checked', true);
    }

    if (type === 'number') {
        return $field.val(+value);
    }

    if ($field.is('select')) {
        if ($field.hasAttr('select-query')) {
            return $field.attr('select-preload-values', Array.isArray(value) ? value.join(',') : value);
        }

        if (value === null || value === '') {
            return $field.val(null);
        }

        // Convert the value to an array if it's not already one.
        value = Array.isArray(value) ? value : value.toString().split(',');

        const tomselect = $field.data('tomselect');

        // Add saved tags that are not among the select's predefined options.
        for (const val of value) {
            const $option = $field.find('option').filter((i, option) => option.value === String(val));

            if ($option.length) {
                $option.attr('selected', true);
            } else if ($field.hasAttr('tags')) {
                $field.append(new Option(val, val, true, true));

                if (tomselect) {
                    tomselect.addOption({ value: val, text: val });
                }
            }
        }

        // Set the value for the tom select element.
        if (tomselect) {
            tomselect.setValue(value);
        }

        return $field.val(value);
    }

    if (window.hugerte && hugerte.get($field.attr('id'))) {
        return hugerte.get($field.attr('id')).setContent(value);
    }

    if ($field.data('repeater')) {
        return $field.data('repeater').set(value);
    }

    if ($field.data('uploader')) {
        return $field.data('uploader').setFiles(value);
    }

    if ($field.data('picker')) {
        return $field.data('picker').dates.setFromInput(value);
    }

    return $field.val(value);
}

/**
 * Check if the other field matches the given value.
 *
 * @param {jQuery} field
 * @param {string} other
 * @param {any} otherValue
 * @returns {boolean}
 */
function checkOtherField(field, other, otherValue) {
    let $form = field.closest('form');

    // Fallback to body if the form is not found
    if ($form.length === 0) {
        $form = $('body');
    }

    // Check if the other field inside a repeater item
    if (field.closest('[repeater-item]').length) {
        const item = field.closest('[repeater-item]');
        const otherField = item.find(`[initial-name^="${other}"]`);

        if (otherField.length) {
            return getFieldValue(otherField.first(), item, 'initial-name') == otherValue;
        }
    }

    if ($form.find(`[name="${other}"]`).length) {
        return getFieldValue($form.find(`[name="${other}"]`).first(), $form) == otherValue;
    }

    return false;
}

/* ---------------------------------
 * DOM & UI Utilities
 * --------------------------------- */

/**
 * Get computed CSS property value(s) from the given element.
 *
 * @param {Element} element
 * @param {string|string[]} properties
 * @returns {string|object}
 */
function getCssProperties(element, properties) {
    const styles = getComputedStyle(element);
    const keys = Array.isArray(properties) ? properties : [properties];
    const values = {};

    for (const property of keys) {
        values[property] = styles.getPropertyValue(property).trim();
    }

    return Array.isArray(properties) ? values : values[properties];
}

/**
 * Make the given element draggable.
 *
 * @param {string} selector
 * @param {number} minDiff
 */
function makeDraggable(selector, minDiff = 5) {
    const $target = $(selector);

    let isMouseDown = false,
        lastX,
        lastY;

    $target.on('mousedown', (e) => {
        isMouseDown = true;
        lastX = e.pageX;
        lastY = e.pageY;
    });

    $target.on('mouseup mouseleave', () => {
        isMouseDown = false;

        $target.removeClass('dragging');
        $target.css('cursor', 'initial');
    });

    $target.on('mousemove', (e) => {
        if (!isMouseDown) {
            return;
        }

        let diffX = e.pageX - lastX;
        let diffY = e.pageY - lastY;

        $target.addClass('dragging');
        $target.css('cursor', 'grabbing');

        if (Math.abs(diffX) > minDiff || Math.abs(diffY) > minDiff) {
            $target.scrollLeft($target.scrollLeft() - diffX);
            $target.scrollTop($target.scrollTop() - diffY);
            lastX = e.pageX;
            lastY = e.pageY;
        }
    });
}

/**
 * Send a form request to the given URL.
 *
 * @param {string} url
 * @param {object} data
 * @param {string} method
 */
function formRequest(url, data = {}, method = 'POST') {
    const $form = $('<form>', {
        method: method.toUpperCase() === 'GET' ? 'GET' : 'POST',
        action: url,
    });

    $form.append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }));
    $form.append($('<input>', { type: 'hidden', name: '_method', value: method }));

    for (const [key, value] of Object.entries(data)) {
        $form.append($('<input>', { type: 'hidden', name: key, value: value }));
    }

    $form.appendTo('body').submit();
}

/**
 * Copy the given text to the clipboard.
 *
 * @param {string} text
 */
function copyToClipboard(text) {
    return navigator.clipboard.writeText(text);
}

/**
 * Toggle fullscreen mode for the given element.
 *
 * @param {string} selector
 */
function toggleFullscreen(selector = 'html', className = 'fullscreen') {
    document.requestFullscreen = document.requestFullscreen || document.webkitRequestFullscreen;
    document.fullscreenElement = document.fullscreenElement || document.webkitFullscreenElement;
    document.exitFullscreen = document.exitFullscreen || document.webkitExitFullscreen;

    if (document.fullscreenElement) {
        document.exitFullscreen();
        $(selector).removeClass(className);
    } else {
        $(selector).addClass(className);
        $(selector).get(0).requestFullscreen();
    }
}

/**
 * Apply selected files to the given avatar.
 *
 * @param {string} source
 * @param {string} target
 * @returns {void}
 */
function applyAvatarPreview(source, target) {
    const $input = $(source);
    const $preview = $(target);
    const reader = new FileReader();

    reader.addEventListener('load', () => {
        $preview.css({
            backgroundImage: `url(${reader.result})`,
        });

        $preview.empty();
    });

    reader.readAsDataURL($input.get(0).files[0]);
}

/**
 * Create a loading overlay for the given element.
 * Returns `show` and `hide` functions. Shows immediately unless `show` is false.
 *
 * @param {object} $element
 * @param {object} options
 * @returns {{ show: function, hide: function }}
 */
function createLoading($element, options = {}) {
    const defaultOptions = {
        delay: 0,
        fade: true,
        lock: true,
        show: true,
        callbacks: {
            show: () => {},
            hide: () => {},
        },
        spinner: {
            size: 'md',
            type: 'border',
            color: null,
        },
    };

    // Merge the default options with the given options.
    options = _.merge(defaultOptions, options);

    const duration = options.fade === true ? 150 : options.fade || 0;
    const $elements = $.query($element);

    // Generate the HTML for the spinner.
    const { type, size, color } = options.spinner;
    const kind = type === 'grow' ? 'spinner-grow' : 'spinner-border';
    const classes = [kind];

    if (size === 'sm' || size === 'lg') {
        classes.push(`${kind}-${size}`);
    }

    if (color) {
        classes.push(`text-${color}`);
    }

    spinnerHtml = `<div class="${classes.join(' ')}" role="status"></div>`;

    // Define the fade function.
    const fade = ($overlay, opacity, complete) => {
        if (duration) {
            $overlay.stop(true).animate({ opacity }, duration, complete);
        } else {
            complete();
        }
    };

    // Define the cleanup function.
    const cleanup = ($el) => {
        $el.removeClass('inline-loading')
            .removeAttr('aria-busy')
            .removeData('inline-loading-timer inline-loading-shown');

        if ($el.hasAttr('inline-loading-static')) {
            $el.css('position', '').removeAttr('inline-loading-static');
        }
    };

    // Define the show element function.
    const showElement = ($el) => {
        // Early return if the element already has a loading overlay or timer.
        if ($el.children('[inline-loading]').length || $el.data('inline-loading-timer')) {
            return;
        }

        const apply = () => {
            $el.removeData('inline-loading-timer');

            if ($el.css('position') === 'static') {
                $el.attr('inline-loading-static', true).css('position', 'relative');
            }

            const $overlay = $(
                `<div inline-loading class="inline-loading-overlay${options.lock ? '' : ' unlocked'}">${spinnerHtml}</div>`,
            );

            $el.addClass('inline-loading').attr('aria-busy', true).append($overlay);

            if (duration) {
                $overlay.css('opacity', 0);
            }

            fade($overlay, 1, () => {
                $el.data('inline-loading-shown', true);
                options.callbacks.show();
            });
        };

        if (options.delay) {
            $el.data('inline-loading-timer', setTimeout(apply, options.delay));
        } else {
            apply();
        }
    };

    // Define the hide element function.
    const hideElement = ($el) => {
        const timer = $el.data('inline-loading-timer');
        const shown = $el.data('inline-loading-shown');
        const $overlay = $el.children('[inline-loading]');

        if (timer) {
            clearTimeout(timer);
        }

        if (!$overlay.length) {
            cleanup($el);
            return;
        }

        fade($overlay, 0, () => {
            $overlay.remove();
            cleanup($el);

            if (shown) {
                options.callbacks.hide();
            }
        });
    };

    // Define the show and hide functions.
    const show = () => $elements.each((_, el) => showElement($(el)));
    const hide = () => $elements.each((_, el) => hideElement($(el)));

    if (options.show) {
        show();
    }

    return { show, hide };
}

/* ---------------------------------
 * Date & Time
 * --------------------------------- */

/**
 * Format the given date as a localized relative time (e.g. "5 minutes ago").
 *
 * @param {string|number|Date} date
 * @param {object} options
 * @returns {string}
 */
function relativeTime(date, options = {}) {
    const locale = options.locale || document.documentElement.lang || 'en';

    const formatter = new Intl.RelativeTimeFormat(locale, {
        numeric: options.numeric || 'always',
        style: options.style || 'long',
    });

    const units = [
        ['year', 31536000],
        ['month', 2592000],
        ['week', 604800],
        ['day', 86400],
        ['hour', 3600],
        ['minute', 60],
        ['second', 1],
    ];

    const seconds = Math.max(1, Math.round((Date.now() - new Date(date).getTime()) / 1000));

    for (const [unit, size] of units) {
        if (seconds >= size || unit === 'second') {
            return formatter.format(-Math.round(seconds / size), unit);
        }
    }
}

/**
 * Keep every [relative-time] element within the root updated in realtime.
 *
 * @param {HTMLElement} root
 * @param {object} options
 * @returns {number} The interval id, so it can be cleared by the caller.
 */
function liveRelativeTimes(root = document.body, options = {}) {
    const update = () => {
        root.querySelectorAll('time[relative-time]').forEach((element) => {
            const datetime = element.getAttribute('datetime');

            if (datetime) {
                element.textContent = relativeTime(datetime, options);
            }
        });
    };

    update();

    return setInterval(update, options.interval || 1000);
}

/* ---------------------------------
 * Initialization
 * --------------------------------- */

/**
 * Initialize the given initiators.
 *
 * @param {string} selector
 */
function init(selector = 'body') {
    const $selector = $(selector).attr('init') ? $(selector).parent() : $(selector);

    // Initialize non-initialized initiators.
    $selector.find('[init]:not([initialized])').each(function () {
        let inits = $(this).attr('init').split(' ');

        // Trigger before all initiators.
        $selector.trigger('init.before');

        for (const init of inits) {
            if (!window.__inits[init]) {
                console.error(`Initiator "${init}" is not defined.`);
                continue;
            }

            // Trigger before the initiator.
            $selector.trigger(`init.before.${init}`);

            const options = this.hasAttribute(init) ? stringToPrimitive($(this).attr(init)) : {};
            window.__inits[init](this, options); // Call the initiator with the given options.

            // Trigger after the initiator.
            $selector.trigger(`init.after.${init}`);
        }

        // Trigger after all initiators.
        $selector.trigger('init.after');

        $(this).attr('initialized', true);
    });

    // Watch for visibility changes.
    if (RedotVisibility.instance) RedotVisibility.instance.watch();
}

/**
 * Get the options from the given selector.
 *
 * @param {string} selector
 * @param {string} prefix
 * @param {boolean} camelCase
 * @returns {object}
 */
function getOptionsFromSelector(selector, prefix, camelCase = true) {
    const attributes = $(selector).get(0).attributes;
    const options = {};

    for (const attribute of attributes) {
        if (!attribute.name.startsWith(prefix)) {
            continue;
        }

        let key = attribute.name.replace(prefix, '');
        let value = stringToPrimitive(attribute.value);

        // Convert the key to camel case if needed.
        if (camelCase) key = key.replace(/-([a-z])/g, (g) => g[1].toUpperCase());

        _.set(options, key, value);
    }

    return options;
}
