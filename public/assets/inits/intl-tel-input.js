/**
 * Initialize intl-tel-input.
 *
 * @param {string} selector
 * @param {object} options
 * @see https://intl-tel-input.com/
 */
return async (selector, options = {}) => {
    const $input = $(selector);
    const input = $input.get(0);

    if (!input) {
        return;
    }

    if (!window.intlTelInput) {
        console.error('intl-tel-input is not loaded.');
        return;
    }

    if (input.iti) {
        return;
    }

    const defaultOptions = {
        loadUtils: () => import('https://cdn.jsdelivr.net/npm/intl-tel-input@29.1.0/dist/js/utils.js'),
    };

    const selectorOptions = getOptionsFromSelector(selector, 'iti-');
    options = _.merge(defaultOptions, selectorOptions, options);

    const instance = window.intlTelInput(input, options);

    $input.data('intlTelInput', instance);

    $input.closest('form').on('submit', (e) => {
        $input.val(instance.getNumber());
    });
};
