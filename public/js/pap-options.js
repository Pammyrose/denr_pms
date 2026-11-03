(() => {
    const programLevels = ['subprogram', 'subsubprogram', 'subsubsubprogram', 'subsubsubsubprogram', 'subsubsubsubsubprogram'];
    window.pmsExcelPreviewHierarchy = row => [
        row?.title, row?.program, ...programLevels.map(field => row?.[field]),
        row?.project, ...(Array.isArray(row?.hierarchy) ? row.hierarchy : []),
    ].map(value => String(value ?? '').trim())
        .filter(value => value && !['n/a', 'na', 'not applicable'].includes(value.toLowerCase()));
    window.pmsShowProgramLevel = (level, focus = true) => {
        const index = programLevels.indexOf(level);
        if (index < 0) return;
        programLevels.slice(0, index + 1).forEach(name => {
            const container = document.getElementById(`pap_${name}_container`);
            const button = document.getElementById(`add_${name}_btn`);
            if (container) container.style.display = '';
            if (button) {
                button.style.display = 'none';
                button.setAttribute('aria-expanded', 'true');
            }
        });
        if (focus) document.getElementById(`pap_${level}`)?.focus();
    };
    window.pmsSyncProgramLevels = () => {
        programLevels.forEach(level => {
            if (document.getElementById(`pap_${level}`)?.value?.trim()) {
                window.pmsShowProgramLevel(level, false);
            }
        });
    };
    window.pmsResetProgramLevels = () => {
        programLevels.forEach(level => {
            const container = document.getElementById(`pap_${level}_container`);
            const input = document.getElementById(`pap_${level}`);
            const button = document.getElementById(`add_${level}_btn`);
            if (container) container.style.display = 'none';
            if (input) input.value = '';
            if (button) {
                button.style.display = '';
                button.setAttribute('aria-expanded', 'false');
            }
        });
    };
    const previousFilters = new WeakMap();
    window.pmsPopulatePapOptions = (datalist, source, itemField, parentValues, matchesParents) => {
        const key = JSON.stringify([itemField, parentValues]);
        const previous = previousFilters.get(datalist);
        if (previous?.key === key && previous.source === source) return;

        const seen = new Set();
        const fragment = document.createDocumentFragment();
        source.forEach(item => {
            if (!matchesParents(item)) return;
            const value = String(item?.[itemField] || '').trim();
            const normalized = value.replace(/\s+/g, ' ').toLowerCase();
            if (!normalized || seen.has(normalized)) return;
            seen.add(normalized);
            const option = document.createElement('option');
            option.value = value;
            fragment.appendChild(option);
        });
        datalist.replaceChildren(fragment);
        previousFilters.set(datalist, { key, source });
    };
})();
