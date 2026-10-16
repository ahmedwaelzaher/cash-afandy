/**
 * Prepare the options for the remote select box.
 */
function prepareRemoteOptions($select, options) {
    let fields = options.searchField || [];

    return _.merge(options, {
        preload: 'focus',
        placeholder: __('Type to search...'),
        valueField: '__value',
        labelField: '__text',
        searchField: ['__text', '__value', ...fields],
        load: function (term, callback) {
            const endpoint = new URL(options.queryRoute);

            // Add the search term to the endpoint.
            endpoint.searchParams.append('term', term);
            endpoint.searchParams.append('data', options.query);

            // If options has limit, add it to the endpoint.
            if (options.limit) {
                endpoint.searchParams.append('limit', options.limit);
            }

            // Append the bindings to the endpoint.
            let bindings = getSelectBindings($select);
            for (const key in bindings) {
                endpoint.searchParams.append(`parameters[${key}]`, bindings[key]);
            }

            fetch(endpoint)
                .then((response) => response.json())
                .then((response) => callback(response.payload.data))
                .catch(() => callback([]));
        },
    });
}

/**
 * Preload values into the select box using AJAX request.
 */
function preloadValues($select) {
    let ids = $select.attr('select-preload-values').trim();

    // Early exit if there are no ids to preload.
    if (ids.length === 0) {
        return;
    }

    let options = getOptionsFromSelector($select, 'select-');
    let endpoint = new URL(options.fetchRoute);
    let instance = $select.data('tomselect');

    endpoint.searchParams.append('data', options.query);
    endpoint.searchParams.append('ids', ids);

    // Append the bindings to the endpoint.
    let bindings = getSelectBindings($select);
    for (const key in bindings) {
        endpoint.searchParams.append(`parameters[${key}]`, bindings[key]);
    }

    fetch(endpoint)
        .then((response) => response.json())
        .then((response) => {
            const data = response.payload.data;

            instance.addOptions(data);
            instance.setValue(data.map((item) => item.__value));
        });
}

/**
 * Get the bindings from the select box.
 */
function getSelectBindings($select) {
    let result = {};
    let bindings = getOptionsFromSelector($select, 'bind-');

    for (const binding in bindings) {
        let bind = bindings[binding];

        if (typeof bind !== 'object') {
            result[binding] = bind;
        } else if (bind.hasOwnProperty('value')) {
            result[binding] = bind.value;
        } else if (bind.hasOwnProperty('selector')) {
            result[binding] = getFieldValue($.query(bind.selector));
        }
    }

    return result;
}

/**
 * Bind the select box to it's bindings inputs.
 */
function bindSelectBindings($select) {
    let instance = $select.data('tomselect');
    let bindings = getOptionsFromSelector($select, 'bind-');

    for (const binding in bindings) {
        let value = bindings[binding];

        // Early exit if the value is not an object or doesn't have a selector.
        if (typeof value !== 'object' || !value.hasOwnProperty('selector')) {
            continue;
        }

        // Get the input element.
        let $input = $.query(value.selector);

        // Early exit if the input element is not found.
        if ($input.length === 0) {
            return console.error(`Input element with selector "${value.selector}" not found.`);
        }

        // Bind the input element to the select box.
        $input.on('change', function () {
            instance.clear();
            instance.clearOptions();

            $select.attr(`bind-${binding}.value`, $(this).val());

            // Reset the loaded searches.
            instance.loadedSearches = {};
            $(instance.control).closest('.ts-wrapper').removeClass('preloaded');
        });
    }
}

/**
 * Initialize the tom select input.
 *
 * @param {string} selector
 * @param {object} options
 * @see https://tom-select.js.org/
 */
return (selector, options = {}) => {
    const $select = $(selector);

    const defaultOptions = {
        create: $select.hasAttr('tags'),
        placeholder: __('Select an option'),

        onInitialize: function () {
            const selected = $select.find('option[selected]');

            if (selected.length === 0 && $select.hasAttr('removable')) {
                this.clear();
            }
        },

        render: {
            option: function (data, escape) {
                return `<div>${data.__html || escape(data.__text || data.text)}</div>`;
            },
        },
    };

    const selectorOptions = getOptionsFromSelector(selector, 'select-');
    options = _.merge(defaultOptions, selectorOptions, options);

    if ($select.hasAttr('same-template')) {
        options.render.item = options.render.option;
    }

    if ($select.hasAttr('multiple') || $select.hasAttr('removable')) {
        options.plugins = options.plugins || [];
        options.plugins.push('remove_button');
    }

    if (options.hasOwnProperty('query')) {
        options = prepareRemoteOptions($select, options);
    }

    const instance = new TomSelect(selector, options);

    // Set the instance on the input element.
    $select.data('tomselect', instance);

    // Bind the select box to it's bindings inputs.
    bindSelectBindings($select);

    // Bind the select box to browser messages that should preload a value.
    window.addEventListener('message', function (event) {
        const message = event.data;
        if (!message || message.key !== $select.attr('id')) return;

        // If a lightbox is still open, close the last opened lightbox.
        Glare.close();

        const value = _.get(message, 'data.value');
        if (value === undefined || value === null || value === '') return;

        $select.attr('select-preload-values', value);
        preloadValues($select);
    });

    // Initial preload values if the attribute is present
    if ($select.hasAttr('select-preload-values')) {
        preloadValues($select);
    }

    // Preload values when `select:preload` event is triggered.
    $select.on('select:preload', function () {
        preloadValues($select);
    });

    // Enable or disable the select box when the visibility changes.
    $select.on('visibility:updated', function (event, visibility) {
        visibility ? instance.enable() : instance.disable();
    });
};
