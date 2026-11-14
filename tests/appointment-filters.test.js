import test from 'node:test';
import assert from 'node:assert/strict';
import { initializeAppointmentFilters } from '../resources/js/appointment-filters.js';

function element(dataset = {}) {
    const classes = new Set();
    const events = {};
    const count = { textContent: '' };
    return {
        dataset, events, count, attributes: {},
        classList: { toggle: (name, enabled) => enabled ? classes.add(name) : classes.delete(name), contains: (name) => classes.has(name) },
        querySelector: () => count,
        setAttribute(name, value) { this.attributes[name] = value; },
        addEventListener(name, callback) { events[name] = callback; },
    };
}

test('filters by status, preserves selection after AJAX changes, and updates counts', () => {
    const past = new Date(Date.now() - 3600000).toISOString();
    const future = new Date(Date.now() + 3600000).toISOString();
    const pending = element({ appointmentStatus: 'Pending assignment', scheduledAt: future });
    const expired = element({ appointmentStatus: 'Pending assignment', scheduledAt: past });
    const scheduled = element({ appointmentStatus: 'Scheduled', scheduledAt: future });
    let cards = [pending, expired, scheduled];
    const list = { querySelectorAll: () => cards, querySelector: () => null };
    const buttons = ['all', 'pending', 'scheduled', 'reschedule'].map((filter) => element({ appointmentFilter: filter }));
    const empty = element();
    const refresh = initializeAppointmentFilters(list, { querySelectorAll: () => buttons }, empty);
    refresh();
    assert.deepEqual(buttons.map((button) => button.count.textContent), [3, 1, 1, 1]);
    buttons[1].events.click();
    assert.equal(pending.classList.contains('hidden'), false);
    assert.equal(expired.classList.contains('hidden'), true);
    assert.equal(scheduled.classList.contains('hidden'), true);
    pending.dataset.appointmentStatus = 'Scheduled';
    refresh();
    assert.equal(buttons[1].attributes['aria-selected'], 'true');
    assert.equal(empty.classList.contains('hidden'), false);
    assert.deepEqual(buttons.map((button) => button.count.textContent), [3, 0, 2, 1]);
    const newPending = element({ appointmentStatus: 'Pending assignment', scheduledAt: future });
    cards.push(newPending);
    refresh();
    assert.equal(newPending.classList.contains('hidden'), false);
    assert.equal(empty.classList.contains('hidden'), true);
    newPending.dataset.scheduledAt = past;
    refresh();
    assert.equal(newPending.classList.contains('hidden'), true);
    assert.equal(buttons[3].count.textContent, 2);
    cards = [];
    buttons[0].events.click();
    assert.equal(empty.classList.contains('hidden'), false);
    assert.equal(buttons[0].count.textContent, 0);
});

test('keeps the original empty state for all and uses filter-specific empty state otherwise', () => {
    const original = element();
    const empty = element();
    const buttons = ['all', 'pending'].map((filter) => element({ appointmentFilter: filter }));
    const refresh = initializeAppointmentFilters({ querySelectorAll: () => [], querySelector: () => original }, { querySelectorAll: () => buttons }, empty);
    refresh();
    assert.equal(empty.classList.contains('hidden'), true);
    assert.equal(original.classList.contains('hidden'), false);
    buttons[1].events.click();
    assert.equal(empty.classList.contains('hidden'), false);
    assert.equal(original.classList.contains('hidden'), true);
    assert.doesNotThrow(initializeAppointmentFilters(null, null, null));
});

test('keeps proposals separate from scheduled appointments and preserves history selection', () => {
    const proposal = element({ appointmentStatus: 'Awaiting patient approval', scheduledAt: new Date(Date.now() + 3600000).toISOString() });
    const buttons = ['all', 'scheduled', 'approval', 'history'].map((filter) => element({ appointmentFilter: filter }));
    const upcoming = element();
    const history = element();
    const controls = { querySelectorAll: () => buttons, ownerDocument: { querySelector: () => history } };
    const refresh = initializeAppointmentFilters({ querySelectorAll: () => [proposal], querySelector: () => null, closest: () => upcoming }, controls, element());
    refresh();
    assert.equal(buttons[1].count.textContent, 0);
    assert.equal(buttons[2].count.textContent, 1);
    buttons[2].events.click();
    assert.equal(proposal.classList.contains('hidden'), false);
    buttons[3].events.click();
    refresh();
    assert.equal(upcoming.classList.contains('hidden'), true);
    assert.equal(history.classList.contains('hidden'), false);
    assert.equal(buttons[3].attributes['aria-selected'], 'true');
    buttons[2].events.click();
    proposal.dataset.appointmentStatus = 'Scheduled';
    refresh();
    assert.equal(buttons[2].count.textContent, 0);
    assert.equal(buttons[1].count.textContent, 1);
    assert.equal(proposal.classList.contains('hidden'), true);
});

