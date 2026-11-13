import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../resources/js/notifications.js', import.meta.url), 'utf8');

function setup(storage = new Map()) {
    const elements = new Map();
    const listeners = new Map();
    const intervals = [];
    const requests = [];
    const events = [];
    let payload = { unread_count: 1, revision: 1, notifications: [{ id: 1, title: 'Appointment confirmed', body: 'Your visit is confirmed.', url: '/appointments', link_label: 'View appointments' }] };
    let fail = false;
    let dialogOpen = false;
    function element(selector) {
        if (!elements.has(selector)) {
            const classes = new Set(selector === '#patient-notification-toast' ? ['hidden'] : []);
            elements.set(selector, {
                dataset: { userId: '7', feedUrl: '/notifications/feed', notificationsUrl: '/notifications' },
                textContent: '',
                classList: {
                    contains: (name) => classes.has(name),
                    add: (name) => classes.add(name),
                    remove: (name) => classes.delete(name),
                    toggle: (name, add) => add ? classes.add(name) : classes.delete(name),
                },
                addEventListener: (name, callback) => listeners.set(`${selector}:${name}`, callback),
                querySelector: element,
                matches: () => false,
                contains: () => false,
            });
        }
        return elements.get(selector);
    }
    const document = {
        hidden: false,
        activeElement: null,
        querySelector: (selector) => selector === 'dialog[open]' ? (dialogOpen ? {} : null) : element(selector),
        querySelectorAll: () => [element('badge')],
        addEventListener: (name, callback) => listeners.set(`document:${name}`, callback),
    };
    const context = {
        document,
        window: {
            location: { origin: 'https://clinic.test' },
            addEventListener: (name, callback) => listeners.set(`window:${name}`, callback),
            dispatchEvent: (event) => events.push(event),
        },
        localStorage: { getItem: (key) => storage.get(key) ?? null, setItem: (key, value) => storage.set(key, value) },
        fetch: async (url) => {
            requests.push(String(url));
            if (fail) throw new Error('Offline');
            return { ok: true, status: 200, json: async () => payload };
        },
        URL,
        AbortSignal: { timeout: () => undefined },
        CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options.detail; } },
        setTimeout: () => 1,
        clearTimeout: () => {},
        setInterval: (callback, delay) => { intervals.push({ callback, delay }); },
    };
    vm.runInNewContext(source, context);
    return {
        storage, document, requests, events, element, listeners,
        ready: () => new Promise((resolve) => setImmediate(resolve)),
        poll: () => intervals[0].callback(),
        delay: () => intervals[0].delay,
        payload: (value) => { payload = value; },
        fail: (value) => { fail = value; },
        dialog: (value) => { dialogOpen = value; },
    };
}

test('shows confirmation, updates badge, and suppresses repeats after refresh', async () => {
    const app = setup();
    await app.ready();
    assert.equal(app.element('#patient-notification-title').textContent, 'Appointment confirmed');
    assert.equal(app.element('#patient-notification-link').href, '/appointments');
    assert.equal(app.element('[data-notification-number]').textContent, 1);
    assert.equal(app.element('#patient-notification-toast').classList.contains('hidden'), false);
    assert.equal(app.delay(), 30000);
    assert.equal(app.events[0].detail.revision, 1);
    app.listeners.get('#dismiss-patient-notification:click')();
    await app.poll();
    assert.equal(app.element('#patient-notification-toast').classList.contains('hidden'), true);
    const refreshed = setup(app.storage);
    await refreshed.ready();
    assert.match(refreshed.requests[0], /after=1/);
    assert.equal(refreshed.element('#patient-notification-toast').classList.contains('hidden'), true);
});

test('retries failures and skips polling in hidden tabs', async () => {
    const app = setup();
    await app.ready();
    app.fail(true);
    await app.poll();
    app.fail(false);
    app.payload({ unread_count: 0, revision: 2, notifications: [] });
    await app.poll();
    assert.equal(app.element('badge').classList.contains('hidden'), true);
    app.document.hidden = true;
    const count = app.requests.length;
    await app.poll();
    assert.equal(app.requests.length, count);
});

test('defers new toasts while a booking dialog is open', async () => {
    const app = setup();
    app.dialog(true);
    await app.ready();
    assert.equal(app.element('#patient-notification-toast').classList.contains('hidden'), true);
    assert.equal(app.storage.size, 0);
    app.dialog(false);
    app.listeners.get('document:close')();
    assert.equal(app.element('#patient-notification-toast').classList.contains('hidden'), false);
    assert.equal(app.storage.get('patient-notifications-seen:7'), '1');
});

test('groups multiple updates and keeps the badge capped at 99+', async () => {
    const app = setup();
    await app.ready();
    app.listeners.get('#dismiss-patient-notification:click')();
    app.payload({ unread_count: 101, revision: 3, notifications: [
        { id: 2, title: 'Visit updated', body: 'Updated time.', url: '/appointments', link_label: 'View appointments' },
        { id: 3, title: 'System alert', body: 'Clinic update.', url: '/notifications', link_label: 'View notifications' },
    ] });
    await app.poll();
    assert.equal(app.element('#patient-notification-title').textContent, '2 new updates');
    assert.equal(app.element('#patient-notification-link').href, '/notifications');
    assert.equal(app.element('[data-notification-number]').textContent, '99+');
    assert.equal(app.storage.get('patient-notifications-seen:7'), '3');
});
