const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

for (const role of ['admin', 'regional', 'penro', 'users']) {
    for (const sector of ['gass', 'sto', 'enf', 'pa', 'engp', 'lands', 'soilcon', 'nra', 'paria', 'cobb', 'continuing']) {
        test(`${role}/${sector}: Add PAP preserves drafts and manual selections during edits`, () => {
            const source = fs.readFileSync(`resources/views/${role}/${sector}/partials/${sector}_physical_main_scripts2.blade.php`, 'utf8');
            const fields = {};
            const ids = ['pap_title', 'pap_program', 'pap_subprogram', 'pap_subsubprogram', 'pap_subsubsubprogram', 'pap_subsubsubsubprogram', 'pap_subsubsubsubsubprogram', 'pap_project', 'pap_activities', 'pap_subactivities', 'pap_subsubactivities', 'pap_level_6', 'pap_level_7', 'pap_level_8', 'modal_indicator_name', 'indicator_id', 'modal_indicator_type', 'use_indicator_type'];
            for (const id of ids) {
                fields[id] = {
                    value: '', dataset: {}, checked: false, handlers: {},
                    addEventListener(event, callback) { this.handlers[event] = callback; },
                };
            }
            let offices = ['manual-office'];
            let timer;
            const context = {
                document: { getElementById: id => fields[id] },
                normalizePapField: value => String(value || '').replace(/\s+/g, ' ').trim().toLowerCase(),
                papPrefillData: [{ title: 'Existing', program: 'Program', row_id: 7, indicators: [{ id: 3, name: 'Existing indicator', indicator_type_id: 2, office_ids: ['existing-office'] }] }],
                setOfficeCheckboxes: values => { offices = Array.from(values); },
                toggleIndicatorTypeDropdown() {}, populatePapChildDropdowns() {}, populatePapTitleDropdown() {},
                setTimeout: callback => { timer = callback; return 1; },
                clearTimeout: () => { timer = null; },
            };
            const functionStart = source.indexOf('        function findMatchingPapFromModal() {');
            const functionEnd = source.indexOf('        function toggleIndicatorTypeDropdown() {', functionStart);
            const eventStart = source.indexOf('            const papFieldIds =');
            const eventEnd = source.indexOf("            const indicatorTypeToggle = document.getElementById('use_indicator_type');", eventStart);
            assert.ok(functionStart >= 0 && functionEnd > functionStart && eventStart >= 0 && eventEnd > eventStart);
            vm.runInNewContext(source.slice(functionStart, functionEnd) + source.slice(eventStart, eventEnd), context);
            const edit = (id, value) => {
                fields[id].value = value;
                fields[id].handlers.input();
                if (timer) { const pending = timer; timer = null; pending(); }
                fields[id].handlers.change();
            };

            fields.pap_program.value = 'Draft program';
            fields.pap_level_8.value = 'Draft deepest level';
            fields.modal_indicator_name.value = 'Draft indicator';
            fields.modal_indicator_type.value = '1';
            fields.use_indicator_type.checked = true;
            edit('pap_title', 'New title');
            edit('pap_activities', 'New activity');
            edit('modal_indicator_name', 'New indicator');
            assert.equal(fields.pap_program.value, 'Draft program');
            assert.equal(fields.pap_level_8.value, 'Draft deepest level');
            assert.equal(fields.modal_indicator_name.value, 'New indicator');
            assert.equal(fields.modal_indicator_type.value, '1');
            assert.equal(fields.use_indicator_type.checked, true);
            assert.deepEqual(offices, ['manual-office']);

            for (const id of ids) fields[id].value = '';
            fields.pap_title.value = 'Existing';
            edit('pap_program', 'Program');
            assert.equal(fields.indicator_id.value, '3');
            assert.equal(fields.indicator_id.dataset.rowId, '7');
            assert.equal(fields.modal_indicator_name.value, 'Existing indicator');
            assert.equal(fields.modal_indicator_type.value, '2');
            assert.deepEqual(offices, ['existing-office']);

            fields.modal_indicator_type.value = '1';
            offices = ['manual-office'];
            edit('modal_indicator_name', 'Existing indicator');
            assert.equal(fields.modal_indicator_type.value, '1');
            assert.deepEqual(offices, ['manual-office']);

            edit('pap_subsubsubsubsubprogram', 'Different fifth sub-program');
            assert.equal(fields.indicator_id.value, '');
            assert.equal(fields.modal_indicator_type.value, '1');
            assert.deepEqual(offices, ['manual-office']);
            edit('modal_indicator_name', 'Replacement indicator');
            assert.equal(fields.indicator_id.value, '');
            assert.equal(fields.indicator_id.dataset.rowId, undefined);
            assert.equal(fields.modal_indicator_type.value, '1');
            assert.deepEqual(offices, ['manual-office']);
        });
    }
}
