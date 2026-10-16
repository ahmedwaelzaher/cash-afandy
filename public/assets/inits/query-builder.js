/**
 * jQuery QueryBuilder plugin wrapper for TomSelect.
 *
 * @see https://querybuilder.js.org/
 * @see https://tom-select.js.org/
 */
$.fn.tomselect = function (options = {}) {
    return this.each(function () {
        const $select = $(this);

        if ($select.data('tomselect')) {
            return;
        }

        if (!window.__inits?.['tomselect']) {
            console.error('TomSelect init is not loaded.');
            return;
        }

        window.__inits['tomselect'](this, options);
    });
};

/**
 * jQuery QueryBuilder plugin wrapper for Tempus Dominus.
 *
 * @see https://querybuilder.js.org/
 * @see https://getdatepicker.com/
 */
$.fn.datepicker = function (options = {}) {
    return this.each(function () {
        const $input = $(this);

        if ($input.data('picker')) {
            return;
        }

        if (!window.__inits?.['tempus-dominus']) {
            console.error('Tempus Dominus init is not loaded.');
            return;
        }

        window.__inits['tempus-dominus'](this, options);
    });
};

/**
 * Initialize the query builder.
 *
 * @param {string} selector
 * @param {object} options
 * @see https://querybuilder.js.org/
 */
return (selector, options = {}) => {
    const defaultOptions = {
        lang_code: $('html').attr('lang'),
        display_empty_filter: false,
        display_errors: true,
        allow_groups: 2,
        allow_empty: true,
        default_filter: null,
        icons: {
            add_group: 'ti ti-folder-plus',
            add_rule: 'ti ti-circle-plus',
            remove_group: 'ti ti-trash',
            remove_rule: 'ti ti-x',
        },
    };

    const selectorOptions = getOptionsFromSelector(selector, 'query-');
    options = _.merge(defaultOptions, selectorOptions, options);

    // Loop through the filters and apply the value setter and getter.
    for (const filter of options.filters) {
        filter.valueSetter = function (rule, value) {
            let count = rule.operator.nb_inputs;
            let values = count > 1 ? value : [value];
            let $inputs = rule.$el.find('.rule-value-container').find('input, select');

            // Set the values for the input and select elements.
            $inputs.each((index, element) => setFieldValue($(element), values[index]));
        };

        filter.valueGetter = function (rule) {
            let count = rule.operator.nb_inputs;
            let $inputs = rule.$el.find('.rule-value-container').find('input, select');
            let values = [];

            // Get the values for the input and select elements.
            $inputs.each((index, element) => values.push(getFieldValue($(element))));

            return count > 1 ? values : values[0];
        };
    }

    // If the input has a value, parse it and set the rules.
    options.rules = $(selector).val() ? JSON.parse($(selector).val()) : [];

    // Render the query builder.
    let $builder = $(selector).closest('[query-builder-container]').find('[query-builder]');
    let instance = $builder.queryBuilder(options).data('queryBuilder');

    // Serialize the query builder data when the form is submitted.
    let $form = $(selector).closest('form');
    $form.on('submit', () => {
        let rules = instance.getRules();
        let value = _.isEmpty(rules) ? null : JSON.stringify(rules);

        $(selector).val(value);
    });

    // Set the instance on the input element.
    $(selector).data('queryBuilder', instance);
};
