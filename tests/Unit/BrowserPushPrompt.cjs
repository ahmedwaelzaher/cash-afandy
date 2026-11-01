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

function browser(reducedMotion = false, direction = 'rtl', withPanel = false) {
    const element = () => ({ props: { hidden: true }, children: {}, events: {} });
    const prompt = element();
    const panel = element();
    const toggle = element();
    panel.children['[data-push-toggle]'] = toggle;
    const document = element();
    const window = Object.assign(element(), {
        AppPushConfig: { enabled: true, sdkVersion: 'test', firebase: {}, vapidKey: 'public-key' },
        WebsitePush: { storeUrl: '/subscribe', destroyUrl: '/unsubscribe' },
        AppServiceWorker: Promise.resolve({ active: { state: 'activated' } }),
        isSecureContext: true,
        matchMedia: () => ({ matches: reducedMotion }),
        getComputedStyle: () => ({ direction, insetInlineEnd: '16px', transform: 'none' }),
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
    };
    prompt.animate = (frames, options) => {
        state.animations.push({ from: frames[0].transform, to: frames[1].transform, duration: options.duration });
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
    }
    function $(target) {
        if (typeof target === 'function') return target();
        if (target === '[data-browser-notifications]') return new Collection(withPanel ? [panel] : []);
        return new Collection([target === '[data-push-prompt]' ? prompt : target]);
    }
    $.ajax = (options) => {
        state.posts.push(options);
        return state.saved.promise;
    };
    const storage = new Map();
    const Notification = {
        permission: 'default',
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
        onMessage: () => {},
        getToken: () => {
            state.tokenRequests++;
            return state.token.promise;
        },
        deleteToken: async () => { state.tokenDeletes++; },
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
})().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
