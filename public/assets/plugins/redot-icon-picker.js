class RedotIconPicker {
    /**
     * The input element that binds the icon picker.
     */
    $input;

    /**
     * The wrapper element that contains the icon picker components.
     */
    $wrapper;

    /**
     * The preview element that shows the selected icon.
     */
    $preview;

    /**
     * The picker button element that opens the modal.
     */
    $picker;

    /**
     * Default options for the icon picker.
     */
    options = {
        /**
         * The local Tabler Icons manifest used as the icon catalog.
         */
        source: '/vendor/tabler-icons/tabler-icons.json',

        /**
         * The maximum number of icons to fetch.
         */
        maxResults: 100,

        /**
         * Debounce delay for search input.
         */
        searchDebounce: 100,

        /**
         * The repeater attributes that will be used to identify the elements.
         */
        attributes: {
            template: 'iconpicker-template',
            modal: 'iconpicker-modal',
            style: 'iconpicker-style',
            search: 'iconpicker-search',
            list: 'iconpicker-list',
            icon: 'iconpicker-icon',
            empty: 'iconpicker-empty',
            animation: 'iconpicker-animation',
            rotation: 'iconpicker-rotation',
        },
    };

    /**
     * Available Tabler icons from the local manifest.
     */
    icons = null;

    /**
     * Icons returned by the last search.
     */
    visibleIcons = [];

    /**
     * Create a new instance.
     */
    constructor(selector, options = {}) {
        this.options = _.merge(this.options, options);

        this.$input = $(selector);
        this.$wrapper = this.$input.closest('[iconpicker-wrapper]');
        this.$preview = this.$wrapper.find('[iconpicker-preview]');
        this.$picker = this.$wrapper.find('[iconpicker-picker]');

        this.init();
    }

    /**
     * Initialize the icon picker.
     */
    init() {
        this.bindEvents();
        this.$input.trigger('change');
    }

    /**
     * Bind events to the icon picker elements.
     */
    bindEvents() {
        this.$input.on('change input', () => {
            this.updatePreview();
        });

        this.$picker.on('click', () => {
            this.openModal();
        });
    }

    /**
     * Update the preview icon based on input value.
     */
    updatePreview() {
        this.$preview.attr('class', `icon icon-sm ${this.$input.val()}`);
    }

    /**
     * Open the icon picker modal.
     */
    openModal() {
        const self = this;
        const attrs = self.options.attributes;

        bootbox.dialog({
            container: this.$wrapper,
            title: __('Select an icon'),
            message: `<div ${attrs.modal}>${$(`[${attrs.template}]`).html()}</div>`,
            onShow: function () {
                const $content = $(this).find('.bootbox-body');

                self.bindModalEvents($content);
                self.searchIcons($content);
            },
            buttons: {
                cancel: {
                    label: __('Cancel'),
                    className: 'btn-secondary',
                },
                confirm: {
                    label: __('Save'),
                    className: 'btn-primary',
                    callback: function () {
                        self.saveSelection(this.find('.bootbox-body'));
                    },
                },
            },
        });
    }

    /**
     * Bind events to the icon picker modal.
     */
    bindModalEvents($content) {
        const attrs = this.options.attributes;

        $content.find(`[${attrs.search}]`).on(
            'keyup',
            _.debounce((event) => {
                const term = event.target.value.trim();
                this.searchIcons($content, term);
            }, this.options.searchDebounce),
        );

        $content.on('click', `[${attrs.icon}]`, function () {
            $content.find(`[${attrs.icon}]`).removeClass('selected');
            $(this).addClass('selected');
        });

        $content.on('click', `[${attrs.style}]`, (event) => {
            $content.find(`[${attrs.style}]`).removeClass('active');
            $(event.currentTarget).addClass('active');
            this.searchIcons($content, $content.find(`[${attrs.search}]`).val().trim());
        });

        $content.on('change', `[${attrs.animation}], [${attrs.rotation}]`, () => {
            this.updateIconEffects($content);
        });
    }

    /**
     * Get the selected animation and rotation classes.
     */
    getEffects($content) {
        const attrs = this.options.attributes;

        const animation = $content.find(`[${attrs.animation}]`).val();
        const rotation = $content.find(`[${attrs.rotation}]`).val();

        return [animation, rotation].filter(Boolean).join(' ');
    }

    /**
     * Apply the selected animation and rotation to the icons in the grid.
     */
    updateIconEffects($content) {
        const attrs = this.options.attributes;
        const effects = this.getEffects($content);

        $content.find(`[${attrs.icon}]`).each(function () {
            let $icons = $(this).find('i');
            $icons.attr('class', `${$(this).attr(attrs.icon)} ${effects}`.trim());
        });
    }

    /**
     * Search the locally hosted Tabler icon catalog.
     */
    async searchIcons($content, term = '') {
        const attrs = this.options.attributes;
        const loading = createLoading($content.find(`[${attrs.list}]`));

        $content.find(`[${attrs.empty}]`).hide();

        try {
            if (this.icons === null) {
                this.icons = await this.loadIcons();
            }

            const query = term.toLowerCase().replace(/\s+/g, '-');
            const style = $content.find(`[${attrs.style}].active`).attr(attrs.style);
            const variants = this.styleVariants(this.icons, style);

            this.visibleIcons = this.rankIcons(variants, query);
            this.populateIcons($content, this.visibleIcons);
        } catch (error) {
            console.error('Error fetching icons:', error);

            this.visibleIcons = [];
            this.showError($content);
        } finally {
            loading.hide();
        }
    }

    /**
     * Load the Tabler icon manifest.
     */
    async loadIcons() {
        const response = await fetch(this.options.source);

        if (!response.ok) {
            throw new Error(`Unable to load Tabler Icons: ${response.status}`);
        }

        return response.json();
    }

    /**
     * Rank icon variants by fuzzy score, capped at maxResults.
     */
    rankIcons(icons, query) {
        if (!query) {
            return icons.slice(0, this.options.maxResults);
        }

        const matches = [];

        for (const icon of icons) {
            const score = fuzzyScore(query, icon.name);

            if (score !== null) {
                matches.push({ icon, score });
            }
        }

        matches.sort((a, b) => b.score - a.score || a.icon.name.localeCompare(b.icon.name));

        return matches.slice(0, this.options.maxResults).map(({ icon }) => icon);
    }

    /**
     * Expand the icons into the renderable variants of the selected style tab.
     *
     * The "all" tab lists every available variant of each icon, while the
     * "outline" and "filled" tabs only list the icons available in that style.
     */
    styleVariants(icons, style) {
        const variants = [];

        for (const icon of icons) {
            const styles = icon.styles ?? ['outline'];

            if ((style === 'all' || style === 'outline') && styles.includes('outline')) {
                variants.push({ name: icon.name, class: `ti ti-${icon.name}` });
            }

            if ((style === 'all' || style === 'filled') && styles.includes('filled')) {
                variants.push({ name: icon.name, class: `ti ti-${icon.name}-filled` });
            }
        }

        return variants;
    }

    /**
     * Populate the icon picker modal with icons.
     */
    populateIcons($content, icons) {
        const attrs = this.options.attributes;
        const effects = this.getEffects($content);

        $content.find(`[${attrs.icon}]`).remove();

        for (const icon of icons) {
            const cls = icon.class;
            const iconCls = `${cls} ${effects}`.trim();

            $content.find(`[${attrs.list}]`).append(`<div ${attrs.icon}="${cls}"><i class="${iconCls}"></i></div>`);
        }

        const $empty = $content.find(`[${attrs.empty}]`);
        const isEmpty = $content.find(`[${attrs.icon}]`).length === 0;

        $empty.toggle(isEmpty);
    }

    /**
     * Show error message in the modal.
     */
    showError($content) {
        const attrs = this.options.attributes;

        $content.find(`[${attrs.icon}]`).remove();
        $content.find(`[${attrs.empty}]`).show();
    }

    /**
     * Save the selected icon to the input.
     */
    saveSelection($content) {
        const attrs = this.options.attributes;
        const $selected = $content.find(`[${attrs.icon}].selected`);

        if ($selected.length === 0) {
            return;
        }

        const animation = $content.find(`[${attrs.animation}]`).val();
        const rotation = $content.find(`[${attrs.rotation}]`).val();
        const value = [$selected.attr(attrs.icon), animation, rotation].filter(Boolean).join(' ');

        this.$input.val(value).trigger('change');
    }
}
