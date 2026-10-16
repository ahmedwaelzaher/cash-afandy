/**
 * Initialize the HugeRTE editor.
 *
 * @param {string} selector
 * @param {object} options
 * @returns {object}
 * @see https://github.com/hugerte/hugerte-docs
 */
return async (selector, options = {}) => {
    if (typeof hugerte === 'undefined') {
        console.error('HugeRTE is not loaded.');
        return;
    }

    // Get the theme from the data-bs-theme attribute
    let theme = $('html').attr('data-bs-theme');

    if (theme === 'auto') {
        theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    // Resolve Tabler tokens for the iframe body (parent CSS cannot style it).
    const css = getCssProperties(document.documentElement, [
        '--tblr-bg-forms',
        '--tblr-body-bg',
        '--tblr-body-color',
        '--tblr-primary',
    ]);

    options = Object.assign({}, options, {
        selector: '#' + $(selector).attr('id'),
        language: document.documentElement.lang,
        directionality: document.documentElement.dir,
        height: 300,
        menubar: false,
        branding: false,
        skin: theme === 'dark' ? 'oxide-dark' : 'oxide',
        content_css: theme === 'dark' ? 'dark' : 'default',
        content_style: `body { background-color: ${css['--tblr-bg-forms'] || css['--tblr-body-bg']}; color: ${css['--tblr-body-color']}; } a { color: ${css['--tblr-primary']}; }`,
        plugins: 'advlist autolink code directionality link lists table image',
        toolbar:
            'undo redo | styles | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify outdent indent ltr rtl | bullist numlist | table image',
        toolbar_mode: 'sliding',
        image_title: true,
        automatic_uploads: true,
        images_upload_handler: (blobInfo, progress) =>
            new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.withCredentials = false;
                xhr.open('POST', '/hugerte/upload');
                xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));

                xhr.upload.onprogress = (e) => {
                    progress((e.loaded / e.total) * 100);
                };

                xhr.onload = () => {
                    if (xhr.status === 403) {
                        reject({ message: 'HTTP Error: ' + xhr.status, remove: true });
                        return;
                    }

                    if (xhr.status < 200 || xhr.status >= 300) {
                        reject('HTTP Error: ' + xhr.status);
                        return;
                    }

                    const json = JSON.parse(xhr.responseText);

                    if (!json || typeof json.location != 'string') {
                        reject('Invalid JSON: ' + xhr.responseText);
                        return;
                    }

                    resolve(json.location);
                };

                xhr.onerror = () => {
                    reject('Image upload failed due to a XHR Transport error. Code: ' + xhr.status);
                };

                const formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());

                xhr.send(formData);
            }),
        file_picker_types: 'image',
        file_picker_callback: (cb, value, meta) => {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');

            input.onchange = () => {
                const file = input.files[0];
                const reader = new FileReader();

                reader.onload = () => {
                    const id = 'blobid' + new Date().getTime();
                    const blobCache = hugerte.activeEditor.editorUpload.blobCache;
                    const base64 = reader.result.split(',')[1];
                    const blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);

                    cb(blobInfo.blobUri(), { title: file.name });
                };

                reader.readAsDataURL(file);
            };

            input.click();
        },
    });

    const [instance] = await hugerte.init(options);
    $(selector).data('hugerte', instance);

    document.addEventListener('theme:changed', () => instance?.destroy(), { once: true });
};
