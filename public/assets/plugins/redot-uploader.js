class RedotUploader {
    /**
     * The hidden input element that binds the uploader data.
     */
    $input;

    /**
     * The uploader identifier, used to fetch other uploader elements.
     */
    identifier;

    /**
     * The container element that contains the uploader instance.
     */
    $container;

    /**
     * The wrapper element that contains the uploader items.
     */
    $wrapper;

    /**
     * Default options for the uploader.
     */
    options = {
        /**
         * The config for the uploader, accepts a string.
         */
        config: null,

        /**
         * The upload endpoint.
         */
        endpoint: '/uploader/upload',

        /**
         * Enable the uploader to be sortable, accepts boolean or Sortable options.
         */
        sortable: true,

        /**
         * Determine if the uploader should be multiple, accepts boolean.
         */
        multiple: true,

        /**
         * The accept attribute for the uploader, accepts a string.
         */
        accept: '*',

        /**
         * Verbose error messages, accepts boolean.
         */
        verboseErrors: true,

        /**
         * Auto upload files on selection.
         */
        autoUpload: true,

        /**
         * Maximum file size in bytes (10MB default).
         */
        maxSize: 10 * 1024 * 1024,

        /**
         * Show a confirmation dialog before removing files.
         */
        confirmable: true,

        /**
         * The shape of the stored value, accepts 'object' or 'url'.
         */
        returnType: 'object',

        /**
         * The uploader attributes that will be used to identify the elements.
         */
        attributes: {
            list: 'uploader-list',
            item: 'uploader-item',
            empty: 'uploader-empty',
            input: 'uploader-input',
            trigger: 'uploader-trigger',
        },
    };

    /**
     * Flag to track if sortable is currently active.
     */
    sortableActive = false;

    /**
     * Buffer to collect error messages before showing a toast.
     */
    errorMessages = [];

    /**
     * Timeout handler for debounced error toasts.
     */
    errorToastTimeout = null;

    /**
     * Create a new instance.
     */
    constructor(selector, options = {}) {
        this.options = _.merge(this.options, options);

        this.$input = $(selector);
        this.$container = this.$input.closest('[uploader-container]');
        this.$wrapper = this.$container.find('[uploader-wrapper]');
        this.$list = this.$wrapper.find(`[${this.options.attributes.list}]`);

        this.identifier = this.$input.attr('id') || _.uniqueId('uploader-');

        this.init();
    }

    /**
     * Initialize the uploader.
     */
    init() {
        // Create the file input element
        this.createFileInput();

        // Bind events
        this.bindEvents();

        // Load initial files
        this.loadInitialFiles();

        // Initialize sortable if enabled
        this.initSortable();

        // Update empty state
        this.updateEmptyState();

        this.trigger('initialized');
    }

    /**
     * Create the file input element.
     */
    createFileInput() {
        const { attributes, multiple, accept } = this.options;

        this.$fileInput = $('<input>')
            .attr({
                type: 'file',
                [attributes.input]: true,
                accept: accept,
                multiple: multiple,
                style: 'display: none;',
            })
            .insertAfter(this.$input);
    }

    /**
     * Bind events.
     */
    bindEvents() {
        const { attributes } = this.options;

        // Open the file picker from the trigger only, so scrollbar and list
        // interactions never open a file dialog by accident.
        this.$wrapper.on('click', `[${attributes.trigger}]`, () => {
            this.$fileInput.trigger('click');
        });

        // Handle file selection
        this.$fileInput.on('change', (e) => {
            const files = Array.from(e.target.files);

            if (files.length === 0) return;

            // Reset input value to allow selecting the same file again
            this.$fileInput.val('');

            // Add files
            let acceptedFiles = this.filterFiles(files);
            acceptedFiles.forEach((file) => this.addFile(file));
        });

        // Handle drag and drop
        this.initDragAndDrop();
    }

    /**
     * Initialize drag and drop functionality.
     */
    initDragAndDrop() {
        const $dropZone = this.$wrapper;

        $dropZone
            .on('dragenter dragover', (e) => {
                // Don't apply drag effects if sortable is working
                if (this.sortableActive) return;

                e.preventDefault();
                e.stopPropagation();
                $dropZone.addClass('drag-over');
            })
            .on('dragleave drop', (e) => {
                // Don't apply drag effects if sortable is working
                if (this.sortableActive) return;

                e.preventDefault();
                e.stopPropagation();
                $dropZone.removeClass('drag-over');
            })
            .on('drop', (e) => {
                // Don't handle file drops if sortable is working
                if (this.sortableActive) return;

                const files = Array.from(e.originalEvent.dataTransfer.files);

                // Filter files based on accept attribute
                const acceptedFiles = this.filterFiles(files);

                acceptedFiles.forEach((file) => this.addFile(file));
            });
    }

    /**
     * Filter files based on accept attribute.
     */
    filterFiles(files) {
        const { accept } = this.options;

        if (!accept || accept === '*' || accept === '*/*') {
            return files;
        }

        const acceptedTypes = accept.split(',').map((type) => type.trim());

        return files.filter((file) => {
            return acceptedTypes.some((type) => {
                if (type.startsWith('.')) {
                    // Extension check
                    return file.name.toLowerCase().endsWith(type.toLowerCase());
                } else if (type.endsWith('/*')) {
                    // MIME type category check
                    const category = type.split('/')[0];
                    return file.type.startsWith(category + '/');
                } else {
                    // Exact MIME type check
                    return file.type === type;
                }
            });
        });
    }

    /**
     * Add a file to the uploader.
     */
    addFile(file) {
        // Validate file size
        if (this.options.maxSize && file.size > this.options.maxSize) {
            if (this.options.verboseErrors) {
                const maxSizeKb = this.options.maxSize / 1024;
                this.queueError(__('validation.lte.file', { attribute: file.name, value: maxSizeKb }));
            }

            return;
        }

        // Check if multiple is false and we already have a file
        if (!this.options.multiple && this.getFiles().length > 0) {
            this.clearFiles();
        }

        const fileData = {
            name: file.name,
            size: file.size,
            type: file.type,
            status: 'pending',
            progress: 0,
            url: null,
            file: file,
        };

        // Create file item
        const $item = this.createFileItem(fileData);

        // Add to list
        this.$list.append($item);

        // Update empty state
        this.updateEmptyState();

        this.trigger('file:added', { file: fileData, item: $item });

        // Auto upload if enabled
        if (this.options.autoUpload) {
            this.uploadFile(fileData, $item);
        }
    }

    /**
     * Create a file item element.
     */
    createFileItem(fileData) {
        const { attributes } = this.options;
        const isImage = fileData.type.startsWith('image/');

        const $item = $(`
            <div ${attributes.item} class="uploader-item position-relative d-flex align-items-center gap-2 overflow-hidden">
                <div class="uploader-item-preview position-relative d-flex align-items-center justify-content-center flex-shrink-0 overflow-hidden rounded-2" sortable-handle>
                    ${isImage ? '<img class="uploader-item-image w-100 h-100">' : this.getFileIcon(fileData.type)}
                    ${isImage ? '<a class="lightbox-preview position-absolute top-0 start-0 d-flex align-items-center justify-content-center w-100 h-100 text-decoration-none"></a>' : ''}
                </div>
                <div class="uploader-item-info d-flex align-items-baseline gap-2 flex-fill">
                    <div class="uploader-item-name flex-fill text-truncate fw-medium">${_.escape(fileData.name)}</div>
                    <div class="uploader-item-size flex-shrink-0 text-secondary">${this.formatFileSize(fileData.size)}</div>
                    <div class="uploader-item-progress">
                        <div class="progress rounded-0 bg-transparent">
                            <div class="progress-bar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
                <div class="uploader-item-actions d-flex align-items-center flex-shrink-0">
                    <button type="button" class="btn btn-icon" action="upload">
                        <i class="ti ti-upload"></i>
                    </button>
                    <button type="button" class="btn btn-icon ${this.options.returnType === 'url' ? 'd-none' : ''}" action="rename">
                        <i class="ti ti-edit"></i>
                    </button>
                    <button type="button" class="btn btn-icon" action="remove">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            </div>
        `);

        // Store file data
        $item.data('file', fileData);

        // Load preview for images
        if (isImage && fileData.file) {
            this.loadImagePreview(fileData.file, $item.find('.uploader-item-image'));
        } else if (isImage && fileData.url) {
            $item.find('.uploader-item-image').attr('src', fileData.thumbnail || fileData.url);
        }

        // Handle lightbox preview
        $item.find('.lightbox-preview').on('click', (e) => {
            if (!fileData.url) return;

            Glare.open([
                {
                    src: fileData.url,
                    type: 'image',
                    caption: fileData.name,
                },
            ]);
        });

        // Handle remove action
        $item.find('[action="remove"]').on('click', (e) => {
            e.stopPropagation();
            this.removeFile($item);
        });

        // Handle rename action
        $item.find('[action="rename"]').on('click', (e) => {
            e.stopPropagation();
            this.renameFile($item);
        });

        // Handle upload action
        $item.find('[action="upload"]').on('click', (e) => {
            e.stopPropagation();

            const fileData = $item.data('file');
            this.uploadFile(fileData, $item);
        });

        // Update status
        this.updateItemStatus($item, fileData.status);

        return $item;
    }

    /**
     * Load image preview from file.
     */
    loadImagePreview(file, $img) {
        const reader = new FileReader();

        reader.onload = (e) => {
            $img.attr('src', e.target.result);
        };

        reader.readAsDataURL(file);
    }

    /**
     * Get file icon based on MIME type.
     */
    getFileIcon(mimeType) {
        const iconMap = {
            'application/pdf': 'ti-file-type-pdf',
            'application/msword': 'ti-file-type-doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'ti-file-type-doc',
            'application/vnd.ms-excel': 'ti-file-type-xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'ti-file-type-xls',
            'application/vnd.ms-powerpoint': 'ti-file-type-ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation': 'ti-file-type-ppt',
            'application/zip': 'ti-file-zip',
            'application/x-rar-compressed': 'ti-file-zip',
            'application/x-7z-compressed': 'ti-file-zip',
            'text/plain': 'ti-file-text',
            'text/html': 'ti-file-code',
            'text/css': 'ti-file-code',
            'text/javascript': 'ti-file-code',
            'application/json': 'ti-file-code',
            'video/': 'ti-video',
            'audio/': 'ti-file-music',
        };

        let iconClass = 'ti-file';

        for (const [type, icon] of Object.entries(iconMap)) {
            if (mimeType.startsWith(type)) {
                iconClass = icon;
                break;
            }
        }

        return `<i class="ti ${iconClass} text-muted"></i>`;
    }

    /**
     * Format file size.
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Upload a file.
     */
    uploadFile(fileData, $item) {
        const formData = new FormData();
        formData.append('file', fileData.file);
        formData.append('config', this.options.config);

        // Update status
        this.updateItemStatus($item, 'uploading');

        $.ajax({
            url: this.options.endpoint,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            xhr: () => {
                const xhr = new window.XMLHttpRequest();

                // Upload progress
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const progress = (e.loaded / e.total) * 100;
                        this.updateProgress($item, progress);
                    }
                });

                return xhr;
            },
            success: (response) => {
                // Update file data
                fileData.status = 'uploaded';
                Object.assign(fileData, response.payload);

                // Update item
                this.syncFileData($item, fileData);
                this.updateItemStatus($item, 'uploaded');

                this.trigger('file:uploaded', { file: fileData, item: $item, response });
            },
            error: (xhr) => {
                fileData.status = 'error';
                fileData.error = xhr.responseJSON?.message || __('validation.uploaded', { attribute: fileData.name });

                this.syncFileData($item, fileData);
                this.updateItemStatus($item, 'error');

                if (this.options.verboseErrors) {
                    this.queueError(fileData.error);
                }

                this.trigger('file:error', { file: fileData, item: $item, error: xhr });
            },
        });
    }

    /**
     * Sync file data with the item.
     */
    syncFileData($item, fileData) {
        // Update the item representation
        $item.find('.uploader-item-name').text(fileData.name);
        $item.find('.uploader-item-size').text(this.formatFileSize(fileData.size));

        // Update the item data
        $item.data('file', fileData);
    }

    /**
     * Update item status.
     */
    updateItemStatus($item, status) {
        $item.removeClass('status-pending status-uploading status-uploaded status-error').addClass(`status-${status}`);

        // Hide/show progress
        $item.find('.uploader-item-progress').toggle(status === 'uploading');

        // Hide/show upload button
        $item.find('[action="upload"]').toggle(['pending', 'error'].includes(status));
    }

    /**
     * Update upload progress.
     */
    updateProgress($item, progress) {
        $item.find('.progress-bar').css('width', `${progress}%`);
    }

    /**
     * Rename a file.
     */
    renameFile($item) {
        const uploader = this;
        const fileData = $item.data('file');

        // Get name and extension
        const dotIndex = fileData.name.lastIndexOf('.');
        const name = dotIndex > 0 ? fileData.name.slice(0, dotIndex) : fileData.name;
        const extension = dotIndex > 0 ? fileData.name.slice(dotIndex + 1) : '';

        // Create input group
        const $inputGroup = $(`
            <div class="input-group input-group-flat">
                <input type="text" class="form-control" value="${name}" autocomplete="off">
                <span class="input-group-text">.${extension}</span>
            </div>
        `);

        bootbox.dialog({
            title: __('Rename'),
            message: $inputGroup,
            buttons: {
                cancel: {
                    label: __('Cancel'),
                    className: 'btn-secondary',
                },
                confirm: {
                    label: __('Rename'),
                    className: 'btn-primary',
                    callback: function () {
                        const name = this.find('.bootbox-body input').val();

                        if (name) {
                            fileData.name = `${name}.${extension}`;
                            $item.find('.uploader-item-name').text(fileData.name);
                            uploader.trigger('file:renamed', { file: fileData, item: $item });
                        }
                    },
                },
            },
        });
    }

    /**
     * Remove a file.
     */
    removeFile($item, force = false) {
        if (force || this.options.confirmable === false) {
            const fileData = $item.data('file');

            $item.remove();
            this.updateEmptyState();
            this.trigger('file:removed', { file: fileData });

            return;
        }

        // Show confirmation dialog
        if (typeof warnBeforeAction !== 'undefined') {
            warnBeforeAction(
                () => this.removeFile($item, true),
                _.isPlainObject(this.options.confirmable) ? this.options.confirmable : {},
            );
        } else {
            if (confirm('Are you sure you want to remove this file?')) {
                this.removeFile($item, true);
            }
        }
    }

    /**
     * Clear all files.
     */
    clearFiles() {
        const { attributes } = this.options;
        this.$list.find(`[${attributes.item}]`).remove();
        this.updateEmptyState();
        this.trigger('cleared');
    }

    /**
     * Get all files.
     */
    getFiles() {
        const { attributes } = this.options;
        const files = [];

        this.$list.find(`[${attributes.item}]`).each((_, item) => {
            const fileData = $(item).data('file');
            if (fileData && fileData.status === 'uploaded') {
                const data = {
                    name: fileData.name,
                    size: fileData.size,
                    type: fileData.type,
                    url: fileData.url,
                    path: fileData.path,
                };

                if (fileData.thumbnail) {
                    data.thumbnail = fileData.thumbnail;
                }

                files.push(data);
            }
        });

        return files;
    }

    /**
     * Set files.
     */
    setFiles(files) {
        const { multiple, returnType } = this.options;

        if (returnType === 'url') {
            if (isJson(files)) files = JSON.parse(files);
            if (!Array.isArray(files)) files = [files];
            files = files.map((url) => this.parseUrl(url));
        } else {
            if (typeof files === 'string') files = JSON.parse(files);
            if (multiple && !Array.isArray(files)) return;
            if (!multiple) files = [files];
        }

        files.forEach((file) => {
            const fileData = {
                name: file.name,
                size: file.size || 0,
                type: file.type || 'application/octet-stream',
                status: 'uploaded',
                url: file.url,
                path: file.path,
                thumbnail: file.thumbnail || '',
            };

            const $item = this.createFileItem(fileData);
            this.$list.append($item);
        });
    }

    /**
     * Parse a URL into a file descriptor.
     */
    parseUrl(url) {
        const types = {
            jpg: 'image/jpeg',
            jpeg: 'image/jpeg',
            png: 'image/png',
            gif: 'image/gif',
            webp: 'image/webp',
            svg: 'image/svg+xml',
            bmp: 'image/bmp',
            avif: 'image/avif',
        };

        const [path] = url.split(/[?#]/);
        const [name] = path.split('/').slice(-1);
        const [, extension = ''] = name.match(/\.([^.]+)$/) || [];

        return {
            url,
            name: decodeURIComponent(name),
            type: types[extension.toLowerCase()] || 'application/octet-stream',
        };
    }

    /**
     * Load initial files from input value.
     */
    loadInitialFiles() {
        const value = this.$input.val();

        if (!value) return;

        try {
            this.setFiles(value);
        } catch (e) {
            console.error('Failed to parse initial files:', e);
        }
    }

    /**
     * Save files to input.
     */
    saveToInput() {
        const { multiple, returnType } = this.options;

        const files = this.getFiles();
        let value = '';

        if (files.length > 0) {
            if (returnType === 'url') {
                const urls = files.map((file) => file.url);
                value = multiple ? JSON.stringify(urls) : urls[0];
            } else {
                value = JSON.stringify(multiple ? files : files[0]);
            }
        }

        if (this.$input.val() === value) {
            return;
        }

        this.$input.val(value).trigger('change');
    }

    /**
     * Initialize sortable functionality.
     */
    initSortable() {
        if (!this.options.sortable) return;

        const touchQuery = window.matchMedia('(hover: none)');
        const sortableHandle = () => (touchQuery.matches ? '[sortable-handle]' : `[${this.options.attributes.item}]`);

        const defaultSortableOptions = {
            animation: 150,
            handle: sortableHandle(),
        };

        const requiredSortableOptions = {
            onStart: () => {
                this.sortableActive = true;
            },
            onEnd: () => {
                this.sortableActive = false;
                this.trigger('reordered');
            },
        };

        const sortable = new Sortable(
            this.$list.get(0),
            _.merge(
                _.isPlainObject(this.options.sortable) ? this.options.sortable : {},
                defaultSortableOptions,
                requiredSortableOptions,
            ),
        );

        // On touch devices, only the file icon/preview starts a drag so
        // the rest of the row can scroll the attached-files list.
        touchQuery.addEventListener('change', () => {
            sortable.option('handle', sortableHandle());
        });
    }

    /**
     * Update empty state visibility.
     */
    updateEmptyState() {
        const { attributes } = this.options;
        const hasFiles = this.$list.find(`[${attributes.item}]`).length > 0;

        this.$container.toggleClass('has-files', hasFiles);
        this.$container.find(`[${attributes.empty}]`).toggle(!hasFiles);
    }

    /**
     * Queue an error message and display grouped toasts.
     */
    queueError(message) {
        if (!message) return;

        this.errorMessages.push(message);

        clearTimeout(this.errorToastTimeout);

        this.errorToastTimeout = setTimeout(() => {
            const messages = _.uniq(this.errorMessages).filter(Boolean);

            if (messages.length === 0) return;

            const content = messages.map((msg) => `• ${_.escape(msg)}`).join('<br>');

            $.error(content);

            this.errorMessages = [];
            this.errorToastTimeout = null;
        }, 100);
    }

    /**
     * Trigger an event on the uploader.
     */
    trigger(name, data = {}) {
        this.$input.trigger(`uploader:${name}`, _.merge({ uploader: this }, data));

        // Keep the hidden input in sync after the file list changes.
        if (name !== 'initialized') {
            this.saveToInput();
        }
    }
}
