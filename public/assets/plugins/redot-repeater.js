class RedotRepeater {
    /**
     * The hidden input element that binds the repeater data.
     */
    $input;

    /**
     * The repeater identifier, used to fetch other repeater elements.
     */
    identifier;

    /**
     * The repeater name, used as a prefix for the repeater items.
     */
    name;

    /**
     * The template element that will be cloned to create new repeater items.
     */
    $template;

    /**
     * The container element that contains the repeater items.
     */
    $container;

    /**
     * The wrapper element that contains the repeater items.
     */
    $wrapper;

    /**
     * The list of repeater items.
     */
    $list;

    /**
     * Default options for the repeater.
     */
    options = {
        /**
         * Enable the repeater to be sortable, accepts boolean or Sortable options.
         */
        sortable: true,

        /**
         * Scroll to the newly inserted item, accepts boolean.
         */
        scrollable: true,

        /**
         * Show a confirmation dialog before removing or clearing items, accepts boolean or Bootbox options.
         */
        confirmable: true,

        /**
         * The number of initial items to insert.
         */
        initialItems: 0,

        /**
         * The repeater item tag name.
         */
        itemTag: 'div',

        /**
         * Animations that will be used when inserting or removing items.
         * Property `effect` can be `show`, `hide`, or any jQuery animation method.
         * Property `duration` is the time in milliseconds for the animation to complete.
         */
        animations: {
            insert: {
                effect: 'show',
                duration: 0,
            },
            remove: {
                effect: 'hide',
                duration: 0,
            },
        },

        /**
         * The repeater actions selectors that will be used to bind the events.
         */
        actions: {
            insert: '[action="insert"]',
            remove: '[action="remove"]',
            clear: '[action="clear"]',
        },

        /**
         * The repeater attributes that will be used to identify the elements.
         */
        attributes: {
            template: 'repeater-template',
            list: 'repeater-list',
            item: 'repeater-item',
            empty: 'repeater-empty',
        },
    };

    /**
     * Create a new instance.
     */
    constructor(selector, options = {}) {
        this.options = _.merge(this.options, options);
        const { attributes } = this.options;

        this.$input = $(selector);
        this.$container = this.$input.closest('[repeater-container]');
        this.$wrapper = this.$container.find('[repeater-wrapper]');
        this.$list = this.$wrapper.find(`[${attributes.list}]`);

        this.identifier = this.$input.attr('id');
        this.name = this.$input.attr('name');

        // Find the template element for the repeater.
        this.$template = $(`[${attributes.template}="${this.identifier}"]`);

        // Restore the repeater data.
        this.set(this.$input.val());

        // Insert initial items.
        if (this.$list.find(`[${attributes.item}]`).length === 0) {
            for (let i = 0; i < this.options.initialItems; i++) {
                this.insert(null, null, true);
            }
        }

        // Initialize the repeater.
        this.init();
    }

    /**
     * Initialize the repeater.
     */
    init() {
        // Bind the actions.
        const { actions, attributes } = this.options;

        this.$wrapper.find(actions.insert).each((i, el) => {
            // Early return if the element inside a repeater item.
            if ($(el).closest(`[${attributes.item}]`).length > 0) return;

            // Bind the insert event to the element. (for static insert buttons)
            $(el).on('click', (e) => {
                e.preventDefault();
                this.insert();
            });
        });

        this.$wrapper.find(actions.clear).each((i, el) => {
            // Early return if the element is inside a repeater item.
            if ($(el).closest(`[${attributes.item}]`).length > 0) return;

            // Bind the clear event to the element. (for static clear buttons)
            $(el).on('click', (e) => {
                e.preventDefault();
                this.clear();
            });
        });

        // Make the repeater sortable.
        if (this.options.sortable) {
            const defaultSortableOptions = {
                animation: 150,
                filter: `[${attributes.empty}]`,
            };

            const requiredSortableOptions = {
                onEnd: () => this.reorder(),
            };

            // Check if the repeater items have a sortable handle.
            if ($(this.$template.html()).find('[sortable-handle]').length) {
                defaultSortableOptions.handle = '[sortable-handle]';
            }

            new Sortable(
                this.$list.get(0),
                _.merge(
                    _.isPlainObject(this.options.sortable) ? this.options.sortable : {},
                    defaultSortableOptions,
                    requiredSortableOptions,
                ),
            );
        }

        // Bind the form submit event to serialize the repeater data.
        this.$container.closest('form').on('submit', () => {
            // Clear the input value before serializing the repeater data.
            this.$input.val(null);

            // Get the repeater items and serialize them.
            const items = this.get();

            // Early return if the items are empty.
            if (items.length === 0) return;

            this.$input.val(JSON.stringify(items));
        });

        // Append error messages to the repeater.
        if (typeof window.ErrorsBag !== 'undefined') {
            const errors = _.merge({}, window.ErrorsBag);

            for (const key in errors) {
                if (key.startsWith(this.name) === false) {
                    delete errors[key];
                }
            }

            if (_.isEmpty(errors) === false) {
                appendErrorsToForm(this.$list, errors);
            }
        }

        this.trigger('initialized');
    }

    /**
     * Trigger an event on the repeater.
     */
    trigger(name, data = {}) {
        this.$input.trigger(`repeater:${name}`, _.merge({ repeater: this }, data));
    }

    /**
     * Insert a new repeater item.
     */
    insert(data = {}, after = null, silent = false) {
        const $item = $(`<${this.options.itemTag}>`).html(this.$template.html()).hide();
        const uniqueId = _.uniqueId(this.options.attributes.item + '-');

        // Assign the `options.attributes.item` attribute to the new item.
        $item.attr(this.options.attributes.item, uniqueId);

        // Handle the visibility plugin to read the correct selector.
        this.handleVisibility($item);

        // Replace the `id` attribute and `for` attribute of the labels.
        this.handleDuplicateIds($item, uniqueId);

        // Rewrite the field names of the repeater item.
        this.rewriteFieldNames($item);

        // Bind the events to the repeater item.
        this.bindItemEvents($item);

        // Append the new item to the wrapper.
        if (after === null) {
            this.$list.append($item);
        } else {
            $(after).closest(`[${this.options.attributes.item}]`).after($item);
        }

        // Show the new item with animation.
        const { effect, duration } = this.options.animations.insert;
        $item[effect](duration);

        // Reorder the repeater items after inserting a new item.
        this.reorder();

        // Set the repeater item fields.
        deserializeFields($item, data, 'initial-name');

        // Scroll to the newly inserted item.
        if (this.options.scrollable && silent === false) {
            $item.get(0).scrollIntoView({ behavior: 'smooth' });
        }

        this.trigger('inserted', { item: $item, data });
    }

    /**
     * Handle the visibility of the repeater items.
     */
    handleVisibility($item) {
        if (!RedotVisibility.instance) return;

        const instance = RedotVisibility.instance;
        const regex = new RegExp(instance.selectorPattern.source, 'g');

        // Store the visibility keys of the elements.
        const map = new Map();

        $item.find(`[${instance.selector}]`).each((i, el) => {
            const $el = $(el);
            let statement = $el.attr(instance.selector);
            let selectors = [...statement.matchAll(regex)];

            for (const [, selector] of selectors) {
                $.query(selector, $item).each((i, el) => {
                    // Early return if the element already has a visibility key.
                    if ($(el).attr('visibility-key')) return;

                    // Generate a unique visibility key.
                    const key = map.has(selector) ? map.get(selector) : _.uniqueId('visibility-');

                    // Store the visibility key in the map.
                    if (map.has(selector) === false) map.set(selector, key);

                    // Append the visibility key to the element.
                    $(el).attr('visibility-key', key);
                });

                if (map.has(selector)) {
                    // Replace the selector with the visibility key.
                    statement = statement.replace(selector, `[visibility-key="${map.get(selector)}"]`);
                }
            }

            $el.attr(instance.selector, statement);
        });
    }

    /**
     * Handle the duplicate IDs of the repeater items.
     */
    handleDuplicateIds($item, uniqueId) {
        $item.find('[id]').each((i, el) => {
            const $el = $(el);
            const id = $el.attr('id');

            // Replace the `id` attribute of the element.
            $el.attr('id', `${uniqueId}-${id}`);

            // Attributes that may contain the `id` of the element.
            const attributes = [
                'for',
                'href',
                'aria-controls',
                'aria-labelledby',
                'aria-describedby',
                'data-target',
                'data-bs-target',

                // Repeater specific attributes. (for nested repeaters)
                ...Object.values(this.options.attributes),
            ];

            attributes.forEach((attr) => {
                const selector = `[${attr}="${id}"], [${attr}="#${id}"]`;
                const $items = $item.find(selector).add(selector);

                $items.each((i, el) => {
                    const $el = $(el);
                    const value = $el.attr(attr);

                    $el.attr(attr, value.replace(id, `${uniqueId}-${id}`));
                });
            });
        });
    }

    /**
     * Rewrite the field names of the repeater items.
     */
    rewriteFieldNames($item) {
        $item.find('[name]').each((i, el) => {
            const $el = $(el);
            const initial = $el.attr('name');

            // Store the initial name of the field.
            $el.attr('initial-name', initial);

            const pos = initial.indexOf('[');
            const [first, rest = ''] = pos === -1 ? [initial] : [initial.substring(0, pos), initial.substring(pos)];

            const rewritten = `${this.name}[__index__][${first}]${rest}`;

            // Rewrite the field name with the rewritten one.
            $el.attr('name', rewritten);
        });
    }

    /**
     * Bind the events to the repeater item.
     */
    bindItemEvents($item) {
        const { actions } = this.options;

        $item.find(actions.insert).on('click', (e) => {
            e.preventDefault();
            this.insert({}, $item);
        });

        $item.find(actions.remove).on('click', (e) => {
            e.preventDefault();
            this.remove($item);
        });

        $item.find(actions.clear).on('click', (e) => {
            e.preventDefault();
            this.clear();
        });
    }

    /**
     * Remove a repeater item.
     */
    remove(item, force = false) {
        if (force || this.options.confirmable === false) {
            this.trigger('remove', { item });

            // Remove the item with animation.
            const { effect, duration } = this.options.animations.remove;

            item[effect](duration, () => {
                item.remove();
                this.trigger('removed', { item });
            });

            // Reorder the repeater items after removing an item.
            return this.reorder();
        }

        // Show a confirmation dialog before removing the item.
        warnBeforeAction(
            () => this.remove(item, true),
            _.isPlainObject(this.options.confirmable) ? this.options.confirmable : {},
        );
    }

    /**
     * Clear all repeater items.
     */
    clear(force = false) {
        if (this.$list.find(`[${this.options.attributes.item}]`).length === 0) {
            return;
        }

        if (force || this.options.confirmable === false) {
            this.trigger('clear');
            this.$list.find(`[${this.options.attributes.item}]`).remove();
            this.trigger('cleared');

            // Reorder the repeater items after clearing all items.
            return this.reorder();
        }

        // Show a confirmation dialog before clearing all items.
        warnBeforeAction(
            () => this.clear(true),
            _.isPlainObject(this.options.confirmable) ? this.options.confirmable : {},
        );
    }

    /**
     * Reorder the repeater items.
     */
    reorder() {
        const { attributes } = this.options;
        const $items = this.$list.find(`[${attributes.item}]`);

        $items.each((index, el) => {
            $(el)
                .find('[name]')
                .each((i, field) => {
                    const $field = $(field);
                    const name = $field.attr('name');
                    const regex = new RegExp(`^${_.escapeRegExp(this.name)}\\[(\\d+|__index__)\\]`, 'g');

                    $field.attr('name', name.replace(regex, `${this.name}[${index}]`));
                });
        });

        // Update the repeater empty state.
        this.$container.find(`[${attributes.empty}]`).toggle($items.length === 0);
    }

    /**
     * Get the repeater data.
     */
    get() {
        const items = this.$list.find(`[${this.options.attributes.item}]`).get();

        return items.map((item) => serializeFields($(item), 'initial-name'));
    }

    /**
     * Set the repeater data.
     */
    set(items) {
        // Early return if the items are empty.
        if (!items || items.length === 0) return;

        if (typeof items === 'string') {
            items = JSON.parse(items);
        }

        try {
            items.forEach((item) => this.insert(item, null, true));
        } catch (error) {
            console.error(error);
        }
    }
}
