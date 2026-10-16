/**
 * Datatables frontend helpers, exposed as `window.Datatables` so the package
 * views consume them instead of inlining Alpine expressions and click logic.
 */
window.Datatables = (() => {
    // Resolve the wire id for an element, honoring dropdown menus detached to <body>
    const wireId = ($el) => $el.closest('[parent-wire-id]').attr('parent-wire-id') || $el.closest('[wire\\:id]').attr('wire:id');

    // Livewire component the element belongs to
    const wire = ($el) => window.Livewire.find(wireId($el));

    // Read the row selection, it lives in Alpine so ticking a checkbox never hits the server
    const selection = ($el) => window.Alpine.$data(wire($el).el).selected;

    // Run a callback behind the action's confirm message, if it carries one
    const confirmed = ($action, callback) => {
        if ($action.is('[confirm]') === false) {
            return callback();
        }

        warnBeforeAction(callback, { message: $action.attr('confirm') });
    };

    // Build the action URL, bulk actions carry the selection in the query string
    const url = ($action) => {
        const target = new URL($action.attr('href'), window.location.origin);

        if ($action.is('[bulk-keys]')) {
            selection($action).forEach((key) => target.searchParams.append(`${$action.attr('bulk-keys')}[]`, key));
        }

        return target;
    };

    // Submit the action as a spoofed form, bulk actions carry the selection as it stands at click time
    const submit = ($action) => {
        let body = $action.is('[request-body]') ? JSON.parse(atob($action.attr('request-body'))) : {};

        if ($action.is('[bulk-keys]')) {
            body = { ...body, [$action.attr('bulk-keys')]: selection($action) };
        }

        // formRequest renders one input per key, so arrays flatten into key[index] entries
        const data = {};
        Object.entries(body).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach((item, index) => (data[`${key}[${index}]`] = item));
            } else {
                data[key] = value;
            }
        });

        formRequest($action.attr('href'), data, $action.attr('method'));
    };

    // Invoke an inline action through Livewire
    const invoke = ($action) =>
        $action.attr('action-scope') === 'bulk'
            ? wire($action).call('runBulkAction', $action.attr('action-name'), selection($action))
            : wire($action).call('runAction', $action.attr('action-name'), $action.attr('action-key'));

    // Alpine component for the datatable root
    const table = ({ filtersOpen = false } = {}) => ({
        filtersOpen,
        selected: [],
        toggleFilters() {
            this.filtersOpen = !this.filtersOpen;
        },
        selectedLabel(template) {
            return template.replace(':count', this.selected.length);
        },
        clearSelection() {
            this.selected = [];
        },
    });

    // Alpine component for the header checkbox toggling the current page
    const pageSelection = (page) => ({
        page,
        get allSelected() {
            return this.page.every((key) => this.selected.includes(key));
        },
        get indeterminate() {
            return !this.allSelected && this.page.some((key) => this.selected.includes(key));
        },
        toggle() {
            this.selected = this.allSelected
                ? this.selected.filter((key) => !this.page.includes(key))
                : [...new Set([...this.selected, ...this.page])];
        },
    });

    return { wire, selection, confirmed, url, submit, invoke, table, pageSelection };
})();

// Detach row-action dropdown menus to <body> so they escape the scrollable
// table, tagging the menu with its wire id to keep the Livewire context.
$(document).on('show.bs.dropdown', '.datatable-actions .dropdown', (event) => {
    const $dropdown = $(event.target).closest('.dropdown');
    const $menu = $dropdown.find('.dropdown-menu');

    const $root = $dropdown.closest('[wire\\:id]');
    $menu.attr('parent-wire-id', $root.attr('wire:id'));

    $menu.appendTo('body');
});

// Handle datatable action clicks, everything funnels through the same confirm gate
$(document).on('click', '.datatable-action', (event) => {
    const $action = $(event.target).closest('.datatable-action');

    // Inline actions run through Livewire
    if ($action.is('[action-name]')) {
        event.preventDefault();

        return Datatables.confirmed($action, () => Datatables.invoke($action));
    }

    // Non-GET actions submit a spoofed form
    if ($action.is('[method]') && $action.attr('method') !== 'get') {
        event.preventDefault();

        return Datatables.confirmed($action, () => Datatables.submit($action));
    }

    // GET actions only need intercepting when they carry a selection or a
    // confirm message, otherwise the browser (or fancybox) owns the click.
    if ($action.is('[bulk-keys]') || $action.is('[confirm]')) {
        event.preventDefault();

        return Datatables.confirmed($action, () => window.open(Datatables.url($action), $action.attr('target') || '_self'));
    }
});
