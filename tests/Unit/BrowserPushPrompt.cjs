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

function browser(reducedMotion = false, direction = 'rtl') {
    const element = () => ({ props: { hidden: true }, children: {}, events: {} });
    const prompt = element();
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
        permission: deferred(),
        token: deferred(),
        saved: deferred(),
        permissionRequests: 0,
        tokenRequests: 0,
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
        if (target === '[data-browser-notifications]') return new Collection([]);
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
})().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
