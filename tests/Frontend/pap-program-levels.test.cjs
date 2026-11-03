const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

test('Excel preview lists Project, Program, five program levels, and activities in order', () => {
    const window = {};
    vm.runInNewContext(fs.readFileSync('public/js/pap-options.js', 'utf8'), { window });
    const path = window.pmsExcelPreviewHierarchy({
        title: 'Project', program: 'Program', project: 'N/A',
        subprogram: 'One', subsubprogram: 'Two', subsubsubprogram: 'Three',
        subsubsubsubprogram: 'Four', subsubsubsubsubprogram: 'Five',
        hierarchy: ['Survey', 'N/A', 'Assess'],
    });
    assert.deepEqual(Array.from(path), ['Project', 'Program', 'One', 'Two', 'Three', 'Four', 'Five', 'Survey', 'Assess']);
    assert.deepEqual(Array.from(window.pmsExcelPreviewHierarchy({ title: 'Legacy', hierarchy: [] })), ['Legacy']);
});

test('Program plus controls reveal sequential levels, restore saved levels, and reset drafts', () => {
    const fields = {};
    for (const name of ["subprogram","subsubprogram","subsubsubprogram","subsubsubsubprogram","subsubsubsubsubprogram"]) {
        fields[`pap_${name}`] = { value: '', focus() { this.focused = true; } };
        fields[`pap_${name}_container`] = { style: { display: 'none' } };
        fields[`add_${name}_btn`] = { style: {}, setAttribute(name, value) { this[name] = value; } };
    }
    const window = {};
    vm.runInNewContext(fs.readFileSync('public/js/pap-options.js', 'utf8'), {
        window, document: { getElementById: id => fields[id] },
    });
    window.pmsShowProgramLevel('subprogram');
    assert.equal(fields.pap_subprogram_container.style.display, '');
    assert.equal(fields.pap_subsubprogram_container.style.display, 'none');
    assert.equal(fields.pap_subprogram.focused, true);
    assert.equal(fields.add_subprogram_btn['aria-expanded'], 'true');
    window.pmsShowProgramLevel('subsubsubsubsubprogram');
    for (const name of ["subprogram","subsubprogram","subsubsubprogram","subsubsubsubprogram","subsubsubsubsubprogram"]) assert.equal(fields[`pap_${name}_container`].style.display, '');
    window.pmsShowProgramLevel('subsubsubsubsubsubprogram');
    assert.equal(fields.pap_subsubsubsubsubsubprogram_container, undefined);
    fields.pap_subprogram.value = 'Parent';
    fields.pap_subsubsubsubsubprogram.value = 'Child';
    window.pmsResetProgramLevels();
    for (const name of ["subprogram","subsubprogram","subsubsubprogram","subsubsubsubprogram","subsubsubsubsubprogram"]) {
        assert.equal(fields[`pap_${name}`].value, '');
        assert.equal(fields[`pap_${name}_container`].style.display, 'none');
        assert.equal(fields[`add_${name}_btn`]['aria-expanded'], 'false');
    }
    fields.pap_subsubsubsubsubprogram.value = 'Saved child';
    window.pmsSyncProgramLevels();
    assert.equal(fields.pap_subprogram_container.style.display, '');
    assert.equal(fields.pap_subsubsubsubsubprogram_container.style.display, '');
    assert.equal(fields.pap_subsubsubsubsubprogram.value, 'Saved child');
});
