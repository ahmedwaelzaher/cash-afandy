/**
 * Initialize the tempus dominus date picker.
 *
 * @param {string} selector
 * @param {object} options
 * @returns {object}
 * @see https://getdatepicker.com/
 */
return (selector, options = {}) => {
    const $picker = $(selector);

    // Determine if the picker inside a rtl direction
    const isRtl = getComputedStyle($picker.get(0)).direction === 'rtl';

    const defaultOptions = {
        display: {
            theme: $('html').attr('data-bs-theme'),

            components: {
                calendar: true,
                clock: false,
            },

            icons: {
                previous: isRtl ? 'ti ti-chevron-right' : 'ti ti-chevron-left',
                next: isRtl ? 'ti ti-chevron-left' : 'ti ti-chevron-right',
            },
        },

        localization: {
            locale: document.documentElement.lang || 'en',
            format: 'yyyy-MM-dd',
            hourCycle: 'h12',
        },
    };

    // Get the options from the selector
    const selectorOptions = getOptionsFromSelector(selector, 'date-');
    options = _.merge(defaultOptions, selectorOptions, options);

    // If the picker has the datetime attribute, add the time components
    if ($picker.hasAttr('datetime') || options.type === 'datetime') {
        options.display.components.clock = true;
        options.display.components.calendar = true;
        options.localization.format = 'yyyy-MM-dd hh:mm T';
    }

    // If the picker has only-time attribute, remove the date components
    if ($picker.hasAttr('only-time') || options.type === 'time') {
        options.display.components.calendar = false;
        options.display.components.clock = true;
        options.localization.format = 'hh:mm T';
    }

    // Remove the type option because it's not needed anymore
    delete options.type;

    // Locale drives month/weekday names via Intl, but the `T` token uses
    // dayPeriod (ص/م in Arabic) which Laravel's `date` rule cannot parse.
    tempusDominus.DateTime.prototype.meridiem = function () {
        return this.getHours() < 12 ? 'AM' : 'PM';
    };

    tempusDominus.DateTime.prototype.getLocaleAfternoon = function () {
        return 'PM';
    };

    // Initialize the picker
    const picker = new tempusDominus.TempusDominus($picker.get(0), options);
    $picker.data('picker', picker);

    // Update the theme when the theme changes
    document.addEventListener('theme:changed', () => {
        const theme = $('html').attr('data-bs-theme');

        // Hide the picker
        picker.hide();
        picker.updateOptions({ display: { theme: theme } });
    });
};
