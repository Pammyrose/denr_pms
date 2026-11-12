const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('resources/views/components/financial_input_persistence.blade.php', 'utf8');
const entry = { row_id: 1, program_id: 1, indicator_id: 2, office_id: 3, year: 2026, jan: 12 };
const response = (status, data) => ({ ok: status >= 200 && status < 300, json: async () => data });

function saveHarness(fetch) {
    const physicalSaved = [];
    const requests = [];
    const context = {
        config: { storeUrl: '/financial-inputs/gass/store', existing: {}, existingAccomplishments: {} },
        accompStoreUrl: '/users/gass_physical/accomplishments/store',
        document: { querySelector: () => ({ value: 'csrf-token' }) },
        console: { error() {} },
        applySavedEntriesToExisting: (section, entries) => physicalSaved.push({ section, entries }),
        fetch: async (url, options) => {
            requests.push({ url, options });
            return fetch(url, options);
        },
    };
    const start = source.indexOf('    const rememberSavedEntries =');
    const end = source.indexOf('    const hydrateInputs =', start);
    assert.ok(start >= 0 && end > start);
    vm.runInNewContext(source.slice(start, end) + '\nthis.financial = saveEntries; this.physical = savePhysicalAccomplishments;', context);
    return { context, requests, physicalSaved };
}

test('Successful saves send CSRF and JSON headers and remember confirmed values', async () => {
    const app = saveHarness(async () => response(200, { success: true }));
    assert.equal((await app.context.financial([entry], 'accomplishment')).success, true);
    assert.equal((await app.context.physical([entry])).success, true);
    assert.equal(app.context.config.existingAccomplishments[1][2][3].jan, 12);
    assert.equal(app.physicalSaved.length, 1);
    for (const { options } of app.requests) {
        assert.equal(options.headers['X-CSRF-TOKEN'], 'csrf-token');
        assert.equal(options.headers.Accept, 'application/json');
        assert.equal(JSON.parse(options.body).entries[0].jan, 12);
    }
});

test('Failed, expired-session, malformed and offline saves do not mark edits as saved', async () => {
    const failures = [
        ...[401, 403, 419, 422, 500].map(status => async () => response(status, { message: 'Rejected' })),
        async () => ({ ok: false, json: async () => { throw new SyntaxError('Invalid JSON'); } }),
        async () => { throw new Error('Network unavailable'); },
    ];
    for (const fail of failures) {
        const app = saveHarness(fail);
        assert.equal((await app.context.financial([entry], 'accomplishment')).success, false);
        assert.equal((await app.context.physical([entry])).success, false);
        assert.deepEqual(Object.keys(app.context.config.existingAccomplishments), []);
        assert.equal(app.physicalSaved.length, 0);
        assert.equal(entry.jan, 12);
    }
});

test('Pending approvals stay distinct from saved values and failed saves can be retried', async () => {
    let result = response(200, { success: true, pending_approval: true });
    const app = saveHarness(async () => result);
    assert.equal((await app.context.financial([entry], 'accomplishment')).pending_approval, true);
    assert.equal((await app.context.physical([entry])).pending_approval, true);
    assert.equal(app.physicalSaved.length, 0);
    assert.deepEqual(Object.keys(app.context.config.existingAccomplishments), []);
    result = response(422, { success: false });
    await app.context.financial([entry], 'accomplishment');
    result = response(200, { success: true });
    await app.context.financial([entry], 'accomplishment');
    assert.equal(app.context.config.existingAccomplishments[1][2][3].jan, 12);
});

function saveAllHarness(results, locked = false, reason = '') {
    const alerts = [];
    const button = { disabled: false, innerHTML: 'Save' };
    let calls = 0;
    const save = async () => results[calls++ % 4];
    const context = {
        saveAllSectionEntries() {},
        document: { getElementById: () => button },
        config: { accomplishmentsOnly: false, canRequestLockedChanges: true },
        collectChangedTargetEntries: () => [entry],
        collectChangedAccomplishmentEntries: () => [entry],
        collectChangedEntries: () => [entry],
        attachTouchedPeriods: entries => entries,
        entryChangesLockedMonth: () => locked,
        addLockedChangeReasons: entries => entries.map(item => ({ ...item, change_reason: reason })),
        saveSectionEntries: save, savePhysicalAccomplishments: save, saveEntries: save,
        PMSButtonLoading: {
            isBusy: () => false,
            start: target => { target.disabled = true; },
            stop: target => { target.disabled = false; },
        },
        showTopRightErrorAlert: message => alerts.push({ kind: 'error', message }),
        showTopRightSuccessAlert: message => alerts.push({ kind: 'success', message }),
    };
    const start = source.indexOf('      saveAllSectionEntries = async function () {');
    const end = source.indexOf('\n      };', start) + '\n      };'.length;
    assert.ok(start >= 0 && end > start);
    vm.runInNewContext(source.slice(start, end), context);
    return { context, button, alerts, calls: () => calls };
}

test('Save All reports partial failure, restores its button, and permits retry', async () => {
    const results = [{ success: true }, { success: false }, { success: true }, { success: true }];
    const app = saveAllHarness(results);
    await app.context.saveAllSectionEntries();
    assert.equal(app.alerts[0].kind, 'error');
    assert.equal(app.button.disabled, false);
    assert.equal(app.button.innerHTML, 'Save');
    results[1] = { success: true };
    await app.context.saveAllSectionEntries();
    assert.equal(app.alerts[1].kind, 'success');
    assert.equal(app.calls(), 8);
});

test('Save All validates locked-period reasons before making requests', async () => {
    const app = saveAllHarness([], true);
    await app.context.saveAllSectionEntries();
    assert.equal(app.calls(), 0);
    assert.match(app.alerts[0].message, /reason/);
    assert.equal(app.button.disabled, false);
});

test('Save All reports requests pending approval rather than confirmed saves', async () => {
    const app = saveAllHarness([{ success: true }, { success: true, pending_approval: true }, { success: true }, { success: true }], true, 'Source corrected.');
    await app.context.saveAllSectionEntries();
    assert.equal(app.alerts[0].kind, 'success');
    assert.match(app.alerts[0].message, /submitted.*approval/);
    assert.equal(app.button.disabled, false);
});

test('Regular physical Save attaches a locked-month reason and reports queued approval', async () => {
    const alerts = [];
    let savedEntries = null;
    const context = {
        saveSectionEntries: async () => ({ success: true }),
        accompVisible: true,
        remarksVisible: false,
        config: { canRequestLockedChanges: true },
        collectChangedAccomplishmentEntries: () => [entry],
        attachTouchedPeriods: entries => entries.map(item => ({ ...item, changed_periods: ['jan'] })),
        entryChangesLockedMonth: item => item.changed_periods?.includes('jan'),
        addLockedChangeReasons: entries => entries.map(item => ({ ...item, change_reason: 'Source corrected.' })),
        savePhysicalAccomplishments: async entries => {
            savedEntries = entries;
            return { success: true, pending_approval: true };
        },
        showTopRightErrorAlert: message => alerts.push({ kind: 'error', message }),
        showTopRightSuccessAlert: message => alerts.push({ kind: 'success', message }),
    };
    const start = source.indexOf('    if (typeof saveSectionEntries === \'function\') {');
    const end = source.indexOf('\n    if (typeof saveAllSectionEntries === \'function\') {', start);
    assert.ok(start >= 0 && end > start);
    vm.runInNewContext(source.slice(start, end) + '\nthis.savePhysicalSection = saveSectionEntries;', context);

    const result = await context.savePhysicalSection('accomp');
    assert.equal(result.pending_approval, true);
    assert.equal(savedEntries[0].change_reason, 'Source corrected.');
    assert.match(alerts[0].message, /submitted.*approval/);
});
