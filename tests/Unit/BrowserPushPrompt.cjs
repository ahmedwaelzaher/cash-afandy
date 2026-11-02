const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync(process.argv[2], 'utf8').split('// Browser push subscriptions')[1];
const deferred = () => {
    let resolve, reject;
    const promise = new Promise((yes, no) => {
        resolve = yes;
        reject = no;
    });
    return { promise, resolve, reject };
};
const until = async (condition) => {
    for (let attempt = 0; attempt < 100; attempt++) {
        if (condition()) return;
        await new Promise(setImmediate);
    }
    assert.fail('The browser registration did not reach the expected state.');
};

function browser(reducedMotion = false, direction = 'rtl', withPanel = false, savedState = {}) {
    const element = () => ({ props: { hidden: true }, children: {}, events: {} });
    const prompt = element();
    const panel = element();
    const toggle = element();
    panel.children['[data-push-toggle]'] = toggle;
    const document = element();
    const notificationContainer = element();
    const window = Object.assign(element(), {
        AppPushConfig: { enabled: true, sdkVersion: 'test', firebase: {}, vapidKey: 'public-key' },
        WebsitePush: {
            storeUrl: '/subscribe', destroyUrl: '/unsubscribe',
            types: {
                general: { label: 'General notifications', icon: 'ti ti-bell' },
                new_offer: { label: 'New offer', icon: 'ti ti-star' },
            },
        },
        AppServiceWorker: Promise.resolve({ active: { state: 'activated' } }),
        isSecureContext: true,
        location: { href: 'https://cashafandy.test/ar/offers' },
        matchMedia: () => ({ matches: state.reducedMotion }),
        getComputedStyle: () => ({ direction: state.direction, insetInlineEnd: '16px', transform: 'none' }),
    });
    const state = {
        prompt,
        panel,
        toggle,
        permission: deferred(),
        token: deferred(),
        saved: deferred(),
        permissionRequests: 0,
        tokenRequests: 0,
        tokenDeletes: 0,
        posts: [],
        animations: [],
        events: [],
        cards: [],
        toasts: [],
        reducedMotion,
        direction,
        viewportHeight: savedState.viewportHeight || 10000,
    };
    Object.defineProperties(notificationContainer, {
        children: { get: () => notificationContainer.childrenList || [] },
        childElementCount: { get: () => (notificationContainer.childrenList || []).length },
        scrollHeight: { get: () => Math.max(0, (notificationContainer.childrenList || []).length * 110 - 10) },
        clientHeight: { get: () => Math.min(notificationContainer.scrollHeight, state.viewportHeight) },
    });
    document.getElementById = () => ({
        content: {
            firstElementChild: {
                cloneNode: () => {
                    const card = element();
                    card.isNotification = true;
                    card.getBoundingClientRect = () => ({ top: state.measureLayout && card.parent
                        ? state.viewportHeight - Math.min(notificationContainer.scrollHeight, state.viewportHeight)
                            + notificationContainer.children.indexOf(card) * 110
                        : 0 });
                    Object.defineProperties(card, {
                        offsetTop: { get: () => notificationContainer.children.indexOf(card) * 110 },
                        offsetHeight: { get: () => 100 },
                    });
                    card.animate = prompt.animate;
                    card.props['data-delay'] = '5000';
                    card.children['[data-notification-action]'] = element();
                    card.children['[data-notification-fallback]'] = element();
                    card.children['[data-notification-fallback]'].props.hidden = false;
                    return card;
                },
            },
        },
    });
    document.querySelector = () => notificationContainer;
    prompt.animate = (frames, options) => {
        state.animations.push({ from: frames[0].transform ?? frames[0].translate, to: frames[1].transform ?? frames[1].translate, duration: options.duration });
        let cancelled = false;
        const animation = {
            cancel: () => {
                cancelled = true;
            },
        };
        queueMicrotask(() => {
            if (!cancelled) animation.onfinish?.();
        });
        return animation;
    };
    class Collection {
        constructor(items) {
            this.items = items;
            items.forEach((item, index) => {
                this[index] = item;
            });
        }
        prop(key, value) {
            this.items.forEach((item) => {
                item.props[key] = value;
                item[key] = value;
            });
            return this;
        }
        text(value) {
            this.items.forEach((item) => {
                item.text = value;
            });
            return this;
        }
        find(selector) {
            if (selector === '[data-notification-icon]') {
                return new Collection(this.items.flatMap((item) =>
                    ['[data-notification-type-icon]', '[data-notification-fallback]'].map((key) => (item.children[key] ||= element()))));
            }
            return new Collection(this.items.map((item) => (item.children[selector] ||= element())));
        }
        add(other) {
            return new Collection([...this.items, ...other.items]);
        }
        on(event, callback) {
            this.items.forEach((item) => {
                item.events[event] = callback;
            });
            return this;
        }
        trigger(event) {
            state.events.push(event);
            return this;
        }
        attr(key, value) {
            if (value === undefined) return this.items[0]?.props[key];
            return this.prop(key, value);
        }
        one(event, callback) {
            return this.on(event, callback);
        }
        addClass() { return this; }
        removeClass() { return this; }
        appendTo(target) {
            const parent = target instanceof Collection ? target[0] : target;
            this.items.forEach((item) => {
                (parent.childrenList ||= []).push(item);
                item.parent = parent;
                if (item.isNotification && !state.cards.includes(item)) state.cards.push(item);
            });
            return this;
        }
        remove() {
            return this.detach().prop('removed', true);
        }
        detach() {
            this.items.forEach((item) => {
                if (item.parent) item.parent.childrenList = item.parent.childrenList.filter((child) => child !== item);
                item.parent = null;
            });
            return this;
        }
    }
    function $(target, attributes) {
        if (typeof target === 'function') return target();
        if (target === '<img>') return new Collection([Object.assign(element(), { props: attributes })]);
        if (target === '[data-browser-notifications]') return new Collection(withPanel ? [panel] : []);
        return new Collection([target === '[data-push-prompt]' ? prompt : target]);
    }
    $.ajax = (options) => {
        state.posts.push(options);
        return state.saved.promise;
    };
    const storage = savedState.local || new Map();
    const database = savedState.database || new Map();
    const owner = savedState.recipientKey || 'user:1';
    window.setInterval = (callback) => { state.poll = callback; };
    window.WebsiteNotificationClient = {
        list: async () => [...database.values()].filter((row) => row.owner === owner && !row.shown && !row.token).map((row) => row.payload),
        claim: async (id) => {
            const row = database.get(id);
            if (!row || row.owner !== owner || row.shown || row.token) return null;
            row.token = 'claim-' + id;
            return row.token;
        },
        confirm: async (id, token) => {
            const row = database.get(id);
            if (row?.owner === owner && row.token === token) { row.shown = true; row.token = null; }
        },
        release: async (id, token) => {
            const row = database.get(id);
            if (row?.owner === owner && row.token === token) row.token = null;
        },
    };
    const Notification = {
        permission: savedState.permission || 'default',
        requestPermission: () => {
            state.permissionRequests++;
            return state.permission.promise;
        },
    };
    window.Notification = Notification;
    const context = vm.createContext({
        window,
        document,
        $,
        Notification,
        navigator: { serviceWorker: {} },
        URL,
        tabler: {
            Toast: class {
                constructor(card, options) {
                    this.card = card;
                    this.options = options;
                    state.toasts.push(this);
                }
                show() {
                    this.card.shown = true;
                    this.card.events['shown.bs.toast']?.();
                }
                hide() {
                    const event = { defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
                    this.card.events['hide.bs.toast']?.(event);
                    if (!event.defaultPrevented) this.card.events['hidden.bs.toast']();
                }
                dispose() {
                    this.disposed = true;
                }
            },
        },
        __: (text) => text,
        localStorage: {
            getItem: (key) => storage.get(key) ?? null,
            setItem: (key, value) => storage.set(key, value),
            removeItem: (key) => storage.delete(key),
        },
    });
    const sdk = {
        initializeApp: () => ({}),
        isSupported: async () => true,
        getMessaging: () => ({}),
        onMessage: (_, callback) => {
            state.receive = (payload) => {
                const id = payload.data?.notification_id || payload.messageId;
                if (!database.has(id)) database.set(id, { payload, owner, shown: false, token: null });
                callback(payload);
            };
        },
        getToken: () => {
            state.tokenRequests++;
            return state.token.promise;
        },
        deleteToken: async () => {
            state.tokenDeletes++;
        },
    };
    new vm.Script(source, {
        importModuleDynamically: async () => {
            const module = new vm.SyntheticModule(
                Object.keys(sdk),
                function () {
                    for (const [key, value] of Object.entries(sdk)) this.setExport(key, value);
                },
                { context },
            );
            await module.link(() => {});
            await module.evaluate();
            return module;
        },
    }).runInContext(context);
    state.click = () => prompt.children['[data-push-enable]'].events.click();
    state.enableBrowser = () => panel.children['[data-push-enable]'].events.click();
    state.switch = (checked) => {
        toggle.checked = checked;
        toggle.props.checked = checked;
        return toggle.events.change.call(toggle);
    };
    state.grant = (permission) => {
        Notification.permission = permission;
        state.permission.resolve(permission);
    };
    state.setHidden = (hidden) => {
        document.hidden = hidden;
    };
    state.setDirection = (value) => { state.direction = value; };
    state.setReducedMotion = (value) => { state.reducedMotion = value; };
    state.resize = (height) => {
        state.viewportHeight = height;
        window.events['resize.browserNotifications']();
    };
    state.measureLayout = false;
    state.savedState = { database, permission: 'granted' };
    return state;
}

(async () => {
    const state = browser();
    assert.deepEqual(state.animations, [
        { from: 'translateX(calc(-100% - 16px))', to: 'translateX(0)', duration: 200 },
    ]);
    const first = state.click();
    assert.equal(state.prompt.props.hidden, false, 'Keep the invitation until the user decides.');
    state.grant('granted');
    await until(() => state.tokenRequests === 1);
    assert.equal(state.prompt.props.hidden, true, 'Hide while Firebase is still obtaining its token.');
    assert.deepEqual(state.animations.at(-1), {
        from: 'translateX(0)',
        to: 'translateX(calc(-100% - 16px))',
        duration: 200,
    });
    assert.equal(state.posts.length, 0);
    state.token.resolve('first-token');
    await until(() => state.posts.length === 1);
    assert.equal(state.prompt.props.hidden, true, 'Keep it hidden while the server saves the subscription.');
    state.saved.reject(new Error('Server unavailable'));
    await first;
    assert.equal(state.prompt.props.hidden, false, 'Restore the invitation with a retry on failure.');
    assert.deepEqual(state.animations.at(-1), {
        from: 'translateX(calc(-100% - 16px))',
        to: 'translateX(0)',
        duration: 200,
    });
    assert.equal(state.prompt.children['[data-push-prompt-status]'].props.hidden, false);
    assert.equal(state.prompt.children['[data-push-enable]'].props.disabled, false);

    state.token = deferred();
    state.saved = deferred();
    const retry = state.click();
    await until(() => state.tokenRequests === 2);
    assert.equal(state.permissionRequests, 1, 'Retry with existing permission without requesting it again.');
    state.token.resolve('retry-token');
    await until(() => state.posts.length === 2);
    state.saved.resolve();
    await retry;
    assert.equal(state.prompt.props.hidden, true);
    assert.equal(state.posts[1].data.token, 'retry-token');
    assert.ok(state.events.includes('website:push-subscription-updated'));

    const empty = browser();
    const noToken = empty.click();
    empty.grant('granted');
    await until(() => empty.tokenRequests === 1);
    empty.token.resolve('');
    await noToken;
    assert.equal(empty.prompt.props.hidden, false, 'An empty Firebase token must show a recoverable error.');
    assert.equal(empty.posts.length, 0);

    const denied = browser();
    const refusal = denied.click();
    denied.grant('denied');
    await refusal;
    assert.equal(denied.prompt.props.hidden, true);
    assert.equal(denied.tokenRequests, 0, 'Do not register a browser without permission.');

    const ltr = browser(false, 'ltr');
    await new Promise(setImmediate);
    const ltrClick = ltr.click();
    ltr.grant('denied');
    await ltrClick;
    assert.equal(ltr.animations[0].from, 'translateX(calc(100% + 16px))', 'Enter from the right in LTR.');
    assert.equal(ltr.animations.at(-1).to, 'translateX(calc(100% + 16px))', 'Exit to the same edge in LTR.');

    const reduced = browser(true);
    const reducedClick = reduced.click();
    reduced.grant('denied');
    await reducedClick;
    assert.ok(reduced.animations.length === 0, 'Respect reduced motion for both appearance and dismissal.');

    const panel = browser(false, 'rtl', true);
    const enable = panel.switch(true);
    assert.equal(panel.toggle.props.disabled, true, 'Lock the switch while permission is pending.');
    panel.grant('granted');
    await until(() => panel.tokenRequests === 1);
    panel.token.resolve('panel-token');
    await until(() => panel.posts.length === 1);
    panel.saved.reject(new Error('Registration failed'));
    await enable;
    assert.equal(panel.toggle.props.checked, false, 'Failed activation restores the off state.');
    assert.equal(panel.toggle.props.disabled, false, 'A failed activation remains retryable.');

    panel.token = deferred();
    panel.saved = deferred();
    const enableAgain = panel.switch(true);
    await until(() => panel.tokenRequests === 2);
    assert.equal(panel.permissionRequests, 1, 'The switch reuses permission on retry.');
    panel.token.resolve('panel-retry-token');
    await until(() => panel.posts.length === 2);
    panel.saved.resolve();
    await enableAgain;
    assert.equal(panel.toggle.props.checked, true);
    assert.equal(panel.panel.children['[data-push-status]'].text, 'Notifications are enabled for this browser.');

    panel.saved = deferred();
    const failedDisable = panel.switch(false);
    await until(() => panel.posts.length === 3);
    assert.equal(panel.posts.at(-1).method, 'DELETE');
    assert.equal(panel.toggle.props.disabled, true);
    panel.saved.reject(new Error('Server unavailable'));
    await failedDisable;
    assert.equal(panel.toggle.props.checked, true, 'An unsuccessful opt-out restores the previous state.');
    assert.equal(panel.toggle.props.disabled, false);

    panel.saved = deferred();
    const disable = panel.switch(false);
    await until(() => panel.posts.length === 4);
    panel.saved.resolve();
    await disable;
    assert.equal(panel.toggle.props.checked, false);
    assert.equal(panel.toggle.props.disabled, false);
    assert.ok(panel.tokenDeletes > 0, 'Opt-out still revokes the Firebase token.');
    assert.equal(panel.panel.children['[data-push-status]'].text, 'Browser notifications are disabled.');

    const panelDenied = browser(false, 'ltr', true);
    const deniedSwitch = panelDenied.switch(true);
    panelDenied.grant('denied');
    await deniedSwitch;
    assert.equal(panelDenied.toggle.props.checked, false);
    assert.equal(panelDenied.toggle.props.disabled, false);
    assert.equal(panelDenied.tokenRequests, 0);

    const account = browser(false, 'rtl', true);
    const accountEnable = account.enableBrowser();
    account.grant('granted');
    await until(() => account.tokenRequests === 1);
    account.token.resolve('account-browser-token');
    await until(() => account.posts.length === 1);
    account.saved.resolve();
    await accountEnable;
    assert.equal(account.panel.children['[data-push-enable]'].props.hidden, true);
    assert.equal(account.panel.children['[data-push-status]'].text, 'Notifications are enabled for this browser.');

    const title = '<img src=x onerror=alert(1)>';
    account.receive({
        notification: { title, body: '<script>bad()</script>' },
        data: {
            notification_id: 'first-card',
            notification_type: 'new_offer',
            url: '/ar/offers/5',
            store_image: '/logo.png',
        },
    });
    await new Promise(setImmediate);
    const card = account.cards[0];
    assert.equal(account.toasts[0].options.delay, 5000);
    assert.equal(card.children['[data-notification-type-icon]'].props.class, 'ti ti-star fs-1 m-0 lh-1 w-auto h-auto');
    assert.equal(card.children['[data-notification-fallback]'].props.class, 'ti ti-star fs-1 m-0 lh-1 w-auto h-auto');
    assert.equal(account.animations.at(-1).from, 'translateX(calc(-100% - 16px))');
    assert.equal(account.toasts[0].options.animation, false, 'Use the shared slide instead of the default fade.');
    assert.equal(card.children['[data-notification-type]'].text, 'New offer');
    assert.equal(
        card.children['[data-notification-title]'].text,
        title,
        'Treat the notification content as text, never HTML.',
    );
    assert.equal(card.children['[data-notification-body]'].text, '<script>bad()</script>');
    assert.equal(card.children['[data-notification-link]'].props.href, 'https://cashafandy.test/ar/offers/5');
    assert.equal(card.children['[data-notification-action]'].props.hidden, false);
    assert.equal(card.children['[data-notification-link]'].text, 'View offer');
    const image = card.children['[data-notification-avatar]'].childrenList[0];
    image.events.error.call(image);
    assert.equal(image.props.removed, true);
    assert.equal(
        card.children['[data-notification-fallback]'].props.hidden,
        false,
        'Keep the fallback when a merchant logo fails.',
    );
    account.receive({ notification: { title: 'Duplicate' }, data: { notification_id: 'first-card' } });
    assert.equal(account.cards.length, 1, 'Do not display the same push twice.');
    account.receive({ notification: { title: 'No URL' }, data: { notification_id: 'without-url' } });
    await new Promise(setImmediate);
    assert.equal(
        account.cards[1].children['[data-notification-action]'].props.removed,
        true,
        'No link means the entire action footer is removed.',
    );
    assert.equal(account.cards[1].children['[data-notification-body]'].props.hidden, true, 'Omit an empty body.');
    account.receive({
        notification: { title: 'Unsafe URL' },
        data: { notification_id: 'unsafe-url', url: 'javascript:alert(1)', store_image: 'data:image/svg+xml,bad' },
    });
    await new Promise(setImmediate);
    assert.equal(account.cards[2].children['[data-notification-action]'].props.removed, true);
    assert.equal(account.cards[2].children['[data-notification-avatar]'], undefined);
    account.setHidden(true);
    account.receive({ notification: { title: 'Background' }, data: { notification_id: 'background' } });
    assert.equal(account.cards.length, 3, 'Leave background notifications to the system worker.');
    card.events.keydown({ key: 'Escape' });
    assert.notEqual(card.props.removed, true, 'Wait until the slide exit finishes before removing the card.');
    await new Promise(setImmediate);
    assert.equal(card.props.removed, true);
    assert.equal(account.toasts[0].disposed, true);
    account.setHidden(false);
    account.setDirection('ltr');
    account.receive({ notification: { title: 'Open details' }, data: { notification_id: 'click-link', url: '/ar/offers/6' } });
    await new Promise(setImmediate);
    assert.equal(account.animations.at(-1).from, 'translateX(calc(100% + 16px))');
    account.cards[3].children['[data-notification-link]'].events.click();
    assert.equal(account.animations.at(-1).to, 'translateX(calc(100% + 16px))');
    await new Promise(setImmediate);
    assert.equal(account.cards[3].props.removed, true, 'Dismiss the card when its details link is clicked.');
    account.setReducedMotion(true);
    const animationCount = account.animations.length;
    account.receive({ notification: { title: 'Reduced motion' }, data: { notification_id: 'reduced-motion' } });
    await new Promise(setImmediate);
    account.cards[4].events.keydown({ key: 'Escape' });
    assert.equal(account.cards[4].props.removed, true);
    assert.equal(account.animations.length, animationCount, 'Respect reduced motion for notification entry and exit.');
    account.toasts.forEach((toast) => { if (!toast.disposed) toast.hide(); });
    account.resize(240);
    const firstQueued = account.toasts.length;
    for (const id of ['room-one', 'room-two', 'room-three']) {
        account.receive({ notification: { title: id }, data: { notification_id: id } });
    }
    await new Promise(setImmediate);
    assert.equal(account.toasts[firstQueued].card.shown, true);
    assert.equal(account.toasts[firstQueued + 1].card.shown, true);
    assert.notEqual(account.toasts[firstQueued + 2].card.shown, true, 'Keep overflow pending without starting its timer.');
    assert.equal(account.toasts[firstQueued + 2].card.parent, null);
    account.toasts[firstQueued].hide();
    await new Promise(setImmediate);
    assert.equal(account.toasts[firstQueued + 2].card.shown, true, 'Display the oldest pending card after space is freed.');
    account.receive({ notification: { title: 'Wait for resize' }, data: { notification_id: 'room-four' } });
    assert.notEqual(account.toasts[firstQueued + 3].card.shown, true);
    account.resize(400);
    await new Promise(setImmediate);
    assert.equal(account.toasts[firstQueued + 3].card.shown, true, 'Use additional space after a viewport resize.');
    account.toasts.forEach((toast) => { if (!toast.disposed) toast.hide(); });
    account.resize(240);
    account.measureLayout = true;
    account.setReducedMotion(false);
    const transitionStart = account.toasts.length;
    for (const id of ['transition-one', 'transition-two', 'transition-three']) {
        account.receive({ notification: { title: id }, data: { notification_id: id } });
    }
    await new Promise(setImmediate);
    assert.ok(account.animations.some((animation) => animation.from === '0 110px'), 'Move existing cards smoothly when the stack grows.');
    account.toasts[transitionStart].hide();
    account.toasts[transitionStart + 1].hide();
    assert.notEqual(account.toasts[transitionStart + 2].card.shown, true, 'Do not enter while another card is exiting.');
    account.receive({ notification: { title: 'Arrival during exit' }, data: { notification_id: 'transition-four' } });
    assert.notEqual(account.toasts[transitionStart + 3].card.shown, true);
    await new Promise(setImmediate);
    assert.equal(account.toasts[transitionStart].disposed, true);
    assert.equal(account.toasts[transitionStart + 1].disposed, true);
    assert.equal(account.toasts[transitionStart + 2].card.shown, true);
    assert.equal(account.toasts[transitionStart + 3].card.shown, true);
    assert.equal(account.toasts[transitionStart + 2].options.delay, 5000);
    // A fresh browser with no saved tab storage resumes the account's server-side backlog.
    account.setReducedMotion(true);
    account.toasts.forEach((toast) => { if (!toast.disposed) toast.hide(); });
    await new Promise(setImmediate);
    const backlog = new Map();
    for (const id of ['server-one', 'server-two', 'server-three']) {
        backlog.set(id, { owner: 'user:1', shown: false, token: null, payload: { notification: { title: id }, data: { notification_id: id, url: '/ar/offers/7' } } });
    }
    const firstBrowser = browser(true, 'rtl', true, { database: backlog, viewportHeight: 240 });
    await new Promise(setImmediate);
    assert.equal(firstBrowser.toasts.filter((toast) => toast.card.shown).length, 2);
    assert.equal(backlog.get('server-three').shown, false);
    const nextBrowser = browser(true, 'rtl', true, { database: backlog });
    await new Promise(setImmediate);
    assert.deepEqual(nextBrowser.toasts.filter((toast) => toast.card.shown).map((toast) => toast.card.children['[data-notification-title]'].text), ['server-three']);
    const anotherAccount = browser(true, 'rtl', true, { database: backlog, recipientKey: 'user:2' });
    await new Promise(setImmediate);
    assert.equal(anotherAccount.cards.length, 0);
    const onePending = new Map([['one-owner', { owner: 'user:1', shown: false, token: null, payload: { notification: { title: 'Once only' }, data: { notification_id: 'one-owner' } } }]]);
    const tabOne = browser(true, 'rtl', true, { database: onePending });
    const tabTwo = browser(true, 'rtl', true, { database: onePending });
    await new Promise(setImmediate);
    assert.equal([...tabOne.toasts, ...tabTwo.toasts].filter((toast) => toast.card.shown).length, 1);
})().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
