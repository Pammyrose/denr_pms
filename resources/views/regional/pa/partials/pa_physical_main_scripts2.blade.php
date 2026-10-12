    <script>
        function addColumns(mainHeader, groupHeader, title, type) {
            if (groupHeader.children.length === 0) {
                for (let i = 0; i < 2; i++) {
                    const emptyTh = document.createElement("th");
                    groupHeader.appendChild(emptyTh);
                }
            }

            // Determine which columns to show for summary
            let periodIndexes = [];
            if (type === 'summary') {
                // Find current month and quarter
                const now = new Date();
                const currentMonth = now.getMonth(); // 0-based
                let monthCol = PERIODS.findIndex((p, idx) => p.type === 'month' && idx === currentMonth);
                if (monthCol === -1) monthCol = 0;
                let quarterCol = 0;
                if (currentMonth <= 2) quarterCol = 3; // Q1
                else if (currentMonth <= 5) quarterCol = 7; // Q2
                else if (currentMonth <= 8) quarterCol = 11; // Q3
                else quarterCol = 15; // Q4
                let annualCol = PERIODS.length - 1;
                // Order: annual, quarter, month
                periodIndexes = [annualCol, quarterCol, monthCol];
            }

            // For summary, double the columns for targets and accomplishments
            const thGroup = document.createElement("th");
            thGroup.colSpan = type === 'summary' ? 6 : (type === 'pending' ? 2 : COL_COUNT);
            thGroup.className = `group-header group-${type}`;
            thGroup.textContent = title;
            const remarksGroup = groupHeader.querySelector('.group-remarks');
            if (remarksGroup) {
                groupHeader.insertBefore(thGroup, remarksGroup);
            } else {
                groupHeader.appendChild(thGroup);
            }

            if (type === 'summary') {
                // Order: annual, quarter, month for targets, then annual, quarter, month for accomp
                const summaryOrder = ['annual', 'quarter', 'month'];
                // Target columns first
                summaryOrder.forEach(periodType => {
                    const idx = periodIndexes.find(i => PERIODS[i].type === periodType);
                    if (idx !== undefined && idx !== -1) {
                        const p = PERIODS[idx];
                        const thTarget = document.createElement("th");
                        thTarget.classList.add("month-header", "text-center");
                        thTarget.classList.add(`dynamic-header-${type}`);
                        thTarget.dataset.dynamicSection = type;
                        thTarget.dataset.periodType = p.type;
                        thTarget.innerHTML = p.label + ' Target';
                        if (p.type === "quarter") thTarget.classList.add("quarter");
                        if (p.type === "annual") thTarget.classList.add("annual");
                        const remarksHeader = mainHeader.querySelector('th[data-dynamic-section="remarks"]');
                        if (remarksHeader) {
                            mainHeader.insertBefore(thTarget, remarksHeader);
                        } else {
                            mainHeader.appendChild(thTarget);
                        }
                    }
                });
                // Accomplishment columns next
                summaryOrder.forEach(periodType => {
                    const idx = periodIndexes.find(i => PERIODS[i].type === periodType);
                    if (idx !== undefined && idx !== -1) {
                        const p = PERIODS[idx];
                        const thAccomp = document.createElement("th");
                        thAccomp.classList.add("month-header", "text-center");
                        thAccomp.classList.add(`dynamic-header-${type}`);
                        thAccomp.dataset.dynamicSection = type;
                        thAccomp.dataset.periodType = p.type;
                        thAccomp.innerHTML = p.label + ' Accomp';
                        if (p.type === "quarter") thAccomp.classList.add("quarter");
                        if (p.type === "annual") thAccomp.classList.add("annual");
                        const remarksHeader = mainHeader.querySelector('th[data-dynamic-section="remarks"]');
                        if (remarksHeader) {
                            mainHeader.insertBefore(thAccomp, remarksHeader);
                        } else {
                            mainHeader.appendChild(thAccomp);
                        }
                    }
                });
            } else if (type === 'pending') {
                const currentCol = getCurrentMonthPeriodIndex();
                ['Target', 'Accomplishment'].forEach((label, index) => {
                    const th = document.createElement("th");
                    th.classList.add("month-header", "text-center", "accomp-month");
                    th.classList.add("quarter");
                    th.classList.add(`dynamic-header-${type}`);
                    th.dataset.dynamicSection = type;
                    th.dataset.pendingKind = index === 0 ? 'target' : 'accomp';
                    th.dataset.periodType = 'quarter';
                    th.dataset.periodIndex = String(currentCol);
                    th.innerHTML = label;

                    const remarksHeader = mainHeader.querySelector('th[data-dynamic-section="remarks"]');
                    if (remarksHeader) {
                        mainHeader.insertBefore(th, remarksHeader);
                    } else {
                        mainHeader.appendChild(th);
                    }
                });
            } else {
                PERIODS.forEach((p, idx) => {
                    const th = document.createElement("th");
                    th.classList.add("month-header", "text-center");
                    th.classList.add(`dynamic-header-${type}`);
                    th.dataset.dynamicSection = type;
                    th.dataset.periodType = p.type;
                    th.dataset.periodIndex = String(idx);
                    th.dataset.periodIndex = String(idx);
                    if (p.type === "quarter") th.classList.add("quarter");
                    if (p.type === "annual") th.classList.add("annual");

                    let label = p.label;
                    if (type === "financial" && p.type === "annual") label = "Grand";
                    if (p.type === "quarter") label += '<div class="tiny-period">Quarter</div>';
                    if (p.type === "annual") label += '<div class="tiny-period">Total</div>';
                    th.innerHTML = label;

                    if ((type === "accomp" || type === "pending") && p.type === "month") {
                        th.classList.add("accomp-month");
                    }

                    const remarksHeader = mainHeader.querySelector('th[data-dynamic-section="remarks"]');
                    if (remarksHeader) {
                        mainHeader.insertBefore(th, remarksHeader);
                    } else {
                        mainHeader.appendChild(th);
                    }
                });
            }
        }

        function addRemarksColumn(mainHeader, groupHeader) {
            if (mainHeader.querySelector('th[data-dynamic-section="remarks"]')) {
                return;
            }

            if (groupHeader.children.length === 0) {
                for (let i = 0; i < 2; i++) {
                    const emptyTh = document.createElement('th');
                    groupHeader.appendChild(emptyTh);
                }
            }

            const groupCell = document.createElement('th');
            groupCell.colSpan = 1;
            groupCell.className = 'group-header group-remarks';
            groupCell.textContent = '';
            groupHeader.appendChild(groupCell);

            const th = document.createElement('th');
            th.className = 'month-header remarks-header text-center';
            th.dataset.dynamicSection = 'remarks';
            th.textContent = 'Remarks';
            mainHeader.appendChild(th);
        }

        function createSpacerElement(tagName, baseClass) {
            const spacer = document.createElement(tagName);
            spacer.className = `${baseClass} remarks-spacer`.trim();
            spacer.tabIndex = -1;
            spacer.readOnly = true;
            spacer.setAttribute('aria-hidden', 'true');

            if (tagName === 'textarea') {
                spacer.rows = 1;
            } else if (tagName === 'input') {
                spacer.type = 'text';
            }

            return spacer;
        }

        function buildAlignedOfficeLines({
            officeEntries,
            groupBreakIndices = [],
            groupPenroFlags = [],
            spacerFactory,
            renderOfficeInput
        }) {
            const normalizedEntries = officeEntries.length > 0
                ? officeEntries
                : [{ id: currentOfficeId || null, name: 'Office' }];

            const wrapper = document.createElement('div');
            wrapper.className = 'office-lines';

            const addSpacerLine = () => {
                if (typeof spacerFactory !== 'function') return;
                const spacerElement = spacerFactory();
                if (!spacerElement) return;

                const spacerLine = document.createElement('div');
                spacerLine.className = 'input-line';
                spacerLine.appendChild(spacerElement);
                wrapper.appendChild(spacerLine);
            };

            addSpacerLine();

            const groupRanges = [];
            let rangeStart = 0;
            const sortedBreaks = [...groupBreakIndices]
                .map(index => Number(index))
                .filter(index => Number.isInteger(index) && index >= 0)
                .sort((left, right) => left - right);

            sortedBreaks.forEach(breakIndex => {
                if (breakIndex >= rangeStart && breakIndex < normalizedEntries.length) {
                    groupRanges.push({ start: rangeStart, end: breakIndex });
                    rangeStart = breakIndex + 1;
                }
            });

            if (rangeStart < normalizedEntries.length) {
                groupRanges.push({ start: rangeStart, end: normalizedEntries.length - 1 });
            }

            const groupStartToIndex = new Map();
            groupRanges.forEach((range, idx) => {
                groupStartToIndex.set(range.start, idx);
            });

            normalizedEntries.forEach((office, officeIndex) => {
                const currentGroupIndex = groupStartToIndex.get(officeIndex);
                if (currentGroupIndex !== undefined && Boolean(groupPenroFlags[currentGroupIndex])) {
                    addSpacerLine();
                }

                const inputElement = renderOfficeInput(office, officeIndex, {
                    groupIndex: currentGroupIndex,
                    groupRange: currentGroupIndex !== undefined ? groupRanges[currentGroupIndex] : null,
                    entries: normalizedEntries
                });

                if (!inputElement) {
                    return;
                }

                const inputLine = document.createElement('div');
                inputLine.className = 'input-line';
                inputLine.appendChild(inputElement);
                wrapper.appendChild(inputLine);
            });

            return wrapper;
        }

        function addRemarksCells() {
            document.querySelectorAll('tbody tr[data-row-id]').forEach(row => {
                const existingRemarksCell = row.querySelector('td[data-dynamic-section="remarks"]');
                if (existingRemarksCell) {
                    existingRemarksCell.remove();
                }

                const programId = row.dataset.rowId;
                const indicatorId = row.dataset.indicatorId;
                const existingRowDataByOffice = (programId && indicatorId)
                    ? (((existingAccompByIndicator[String(programId)] || {})[String(indicatorId)]) || {})
                    : {};
                const assignedOffices = getAssignedOfficesForRow(row);
                const groupBreakIndices = getInputBreakIndicesForRow(row);
                const groupPenroFlags = getInputGroupPenroFlagsForRow(row);

                const td = document.createElement('td');
                td.classList.add('p-1');
                td.dataset.dynamicSection = 'remarks';

                const officeEntries = assignedOffices.length > 0
                    ? assignedOffices
                    : [{ id: currentOfficeId || null, name: 'Office' }];

                const wrapper = buildAlignedOfficeLines({
                    officeEntries,
                    groupBreakIndices,
                    groupPenroFlags,
                    spacerFactory: () => createSpacerElement('textarea', 'remarks-box'),
                    renderOfficeInput: (office) => {
                        const officeId = Number(office?.id || 0) || null;
                        const officeData = officeId ? existingRowDataByOffice[String(officeId)] : null;

                        const input = document.createElement('textarea');
                        input.className = 'remarks-box';
                        input.placeholder = 'Add comment';
                        input.rows = 1;
                        input.value = String(officeData?.remarks || '');
                        input.dataset.section = 'remarks';
                        input.dataset.officeId = officeId ? String(officeId) : '';

                        return input;
                    }
                });

                td.appendChild(wrapper);
                row.appendChild(td);
            });

            refreshGroupHeaderColspans();
        }

        function addInputCells(sectionType, selectedRows = null) {
            // For summary, only show current quarter, this month, and annual columns
            let periodIndexes = [];
            if (sectionType === 'summary') {
                const now = new Date();
                const currentMonth = now.getMonth();
                let monthCol = PERIODS.findIndex((p, idx) => p.type === 'month' && idx === currentMonth);
                if (monthCol === -1) monthCol = 0;
                let quarterCol = 0;
                if (currentMonth <= 2) quarterCol = 3;
                else if (currentMonth <= 5) quarterCol = 7;
                else if (currentMonth <= 8) quarterCol = 11;
                else quarterCol = 15;
                let annualCol = PERIODS.length - 1;
                // Order: annual, quarter, month
                periodIndexes = [annualCol, quarterCol, monthCol];
            }
            const inputRows = Array.from(selectedRows || window.pmsRowsNearViewport(document.querySelectorAll("#performanceTable tbody tr[data-row-id]")))
                .filter(row => row.style.display !== 'none' && !row.classList.contains('d-none')
                    && !row.querySelector(`td[data-dynamic-section="${sectionType}"]`));
            if (inputRows.length === 0) return;
            inputRows.forEach(row => {
                const programId = row.dataset.rowId;
                const indicatorId = row.dataset.indicatorId;
                const sourceData = sectionType === 'target'
                    ? existingTargetsByIndicator
                    : sectionType === 'accomp'
                        ? existingAccompByIndicator
                        : sectionType === 'summary'
                            ? existingTargetsByIndicator
                            : {}; // Pending starts empty and is computed from target minus accomplishment
                const programSourceData = programId ? (sourceData[String(programId)] || {}) : {};
                const existingRowDataByOffice = indicatorId ? (programSourceData[String(indicatorId)] || {}) : {};
                const indicatorType = getIndicatorTypeForRow(row);
                const assignedOffices = getAssignedOfficesForRow(row);
                const groupBreakIndices = getInputBreakIndicesForRow(row);
                const groupPenroFlags = getInputGroupPenroFlagsForRow(row);

            if (sectionType === 'summary') {
                // Order: annual, quarter, month for targets, then annual, quarter, month for accomp
                const summaryOrder = ['annual', 'quarter', 'month'];
                const officeEntries = assignedOffices.length > 0
                    ? assignedOffices
                    : [{ id: currentOfficeId || null, name: 'Office' }];
                const summarySpacerFactory = () => createSpacerElement('input', 'month-box');
                const targetProgramData = programId ? (existingTargetsByIndicator[String(programId)] || {}) : {};
                const accompProgramData = programId ? (existingAccompByIndicator[String(programId)] || {}) : {};
                const targetDataByIndicator = indicatorId ? (targetProgramData[String(indicatorId)] || {}) : {};
                const accompDataByIndicator = indicatorId ? (accompProgramData[String(indicatorId)] || {}) : {};

                const buildSummaryInput = ({ office, officeDataSource, periodKey, periodIndex, sectionLabel }) => {
                    const officeId = Number(office?.id || 0) || null;
                    const officeData = officeId ? officeDataSource[String(officeId)] : null;
                    const input = document.createElement('input');
                    input.type = 'number';
                    input.className = 'month-box summary-box';
                    input.style.width = '100%';
                    input.value = officeData && periodKey ? (officeData[periodKey] ?? 0) : 0;
                    input.readOnly = true;
                    input.dataset.summarySection = sectionLabel;
                    input.dataset.summaryPeriodKey = periodKey || '';
                    input.dataset.summaryPeriodIndex = Number.isInteger(periodIndex) ? String(periodIndex) : '';
                    input.dataset.officeId = officeId ? String(officeId) : '';
                    return input;
                };
                // Target columns first
                summaryOrder.forEach(periodType => {
                    const idx = periodIndexes.find(i => PERIODS[i].type === periodType);
                    if (idx !== undefined && idx !== -1) {
                        const period = PERIODS[idx];
                        const periodKey = PERIOD_KEYS[idx] || null;
                        const tdTarget = document.createElement("td");
                        tdTarget.classList.add("p-1", "text-center", `dynamic-cell-${sectionType}`);
                        tdTarget.dataset.dynamicSection = sectionType;
                        tdTarget.dataset.periodType = period.type;
                        const wrapperTarget = buildAlignedOfficeLines({
                            officeEntries,
                            groupBreakIndices,
                            groupPenroFlags,
                            spacerFactory: summarySpacerFactory,
                            renderOfficeInput: (office) => {
                                return buildSummaryInput({
                                    office,
                                    officeDataSource: targetDataByIndicator,
                                    periodKey,
                                    periodIndex: idx,
                                    sectionLabel: 'target',
                                });
                            }
                        });
                        tdTarget.appendChild(wrapperTarget);
                        const remarksCell = row.querySelector('td[data-dynamic-section="remarks"]');
                        if (remarksCell) {
                            row.insertBefore(tdTarget, remarksCell);
                        } else {
                            row.appendChild(tdTarget);
                        }
                    }
                });
                // Accomplishment columns next
                summaryOrder.forEach(periodType => {
                    const idx = periodIndexes.find(i => PERIODS[i].type === periodType);
                    if (idx !== undefined && idx !== -1) {
                        const period = PERIODS[idx];
                        const periodKey = PERIOD_KEYS[idx] || null;
                        const tdAccomp = document.createElement("td");
                        tdAccomp.classList.add("p-1", "text-center", `dynamic-cell-${sectionType}`);
                        tdAccomp.dataset.dynamicSection = sectionType;
                        tdAccomp.dataset.periodType = period.type;
                        const wrapperAccomp = buildAlignedOfficeLines({
                            officeEntries,
                            groupBreakIndices,
                            groupPenroFlags,
                            spacerFactory: summarySpacerFactory,
                            renderOfficeInput: (office) => {
                                return buildSummaryInput({
                                    office,
                                    officeDataSource: accompDataByIndicator,
                                    periodKey,
                                    periodIndex: idx,
                                    sectionLabel: 'accomp',
                                });
                            }
                        });
                        tdAccomp.appendChild(wrapperAccomp);
                        const remarksCell = row.querySelector('td[data-dynamic-section="remarks"]');
                        if (remarksCell) {
                            row.insertBefore(tdAccomp, remarksCell);
                        } else {
                            row.appendChild(tdAccomp);
                        }
                    }
                });
            } else if (sectionType === 'pending') {
                const currentCol = getCurrentMonthPeriodIndex();
                const officeEntries = assignedOffices.length > 0
                    ? assignedOffices
                    : [{ id: currentOfficeId || null, name: 'Office' }];

                const buildPendingValueInput = ({ sourceSection, officeId, isCarTotal = false, isGroupTotal = false, groupOfficeIds = [] }) => {
                    const input = document.createElement("input");
                    configurePeriodInput(input);
                    input.className = `month-box ${sectionType}-box`;
                    input.style.width = "70px";
                    input.style.maxWidth = "150px";
                    input.value = "0";
                    input.min = "0";
                    input.step = "any";
                    input.dataset.section = sectionType;
                    input.dataset.pendingKind = sourceSection;
                    input.dataset.sourceSection = sourceSection;
                    input.dataset.col = currentCol;
                    input.dataset.officeId = officeId ? String(officeId) : '';

            if (isCarTotal) {
                input.classList.add('car-total-box');
                input.dataset.carTotal = '1';
                input.dataset.officeId = 'car-total';
                const sectionTotal = officeEntries.reduce((sum, office) => {
                    const officeIdForTotal = String(office?.id || '').trim();
                    const value = officeIdForTotal ? getLivePeriodValue(row, sourceSection, officeIdForTotal, currentCol) : 0;
                    return sum + (Number.isFinite(value) ? value : 0);
                }, 0);
                input.value = sourceSection === 'target' && sectionTotal <= 0
                    ? getStoredPendingTotal(row, currentCol)
                    : sectionTotal;
                return input;
            }

                    if (isGroupTotal) {
                        input.classList.add('group-total-box');
                        input.dataset.groupTotal = '1';
                        input.dataset.groupOfficeIds = groupOfficeIds.join(',');
                        input.value = groupOfficeIds.reduce((sum, groupOfficeId) => {
                            const value = getLivePeriodValue(row, sourceSection, groupOfficeId, currentCol);
                            return sum + (Number.isFinite(value) ? value : 0);
                        }, 0);
                        return input;
                    }

                    const liveValue = getLivePeriodValue(row, sourceSection, officeId, currentCol);
                    input.value = sourceSection === 'target' && liveValue <= 0
                        ? getStoredPendingTotal(row, currentCol)
                        : liveValue;
                    return input;
                };

                const appendInputLine = (wrapper, input) => {
                    const inputLine = document.createElement('div');
                    inputLine.className = 'input-line';
                    inputLine.appendChild(input);
                    wrapper.appendChild(inputLine);
                };

                const appendPendingPairLine = (wrapper, targetInput, accompInput) => {
                    const inputLine = document.createElement('div');
                    inputLine.className = 'input-line';
                    inputLine.style.display = 'flex';
                    inputLine.style.justifyContent = 'center';
                    inputLine.style.gap = '4px';

                    targetInput.title = 'Target';
                    targetInput.placeholder = 'Target';
                    targetInput.setAttribute('aria-label', 'Target');
                    accompInput.title = 'Accomplishment';
                    accompInput.placeholder = 'Accom';
                    accompInput.setAttribute('aria-label', 'Accomplishment');
                    targetInput.style.width = '64px';
                    accompInput.style.width = '64px';

                    inputLine.appendChild(targetInput);
                    inputLine.appendChild(accompInput);
                    wrapper.appendChild(inputLine);
                };

                const groupRanges = [];
                let rangeStart = 0;
                const sortedBreaks = [...groupBreakIndices]
                    .map(index => Number(index))
                    .filter(index => Number.isInteger(index) && index >= 0)
                    .sort((left, right) => left - right);

                sortedBreaks.forEach(breakIndex => {
                    if (breakIndex >= rangeStart && breakIndex < officeEntries.length) {
                        groupRanges.push({ start: rangeStart, end: breakIndex });
                        rangeStart = breakIndex + 1;
                    }
                });

                if (rangeStart < officeEntries.length) {
                    groupRanges.push({ start: rangeStart, end: officeEntries.length - 1 });
                }

                const groupStartToIndex = new Map();
                groupRanges.forEach((range, rangeIndex) => {
                    groupStartToIndex.set(range.start, rangeIndex);
                });

                ['target', 'accomp'].forEach(sourceSection => {
                    const td = document.createElement("td");
                    td.classList.add("p-1", "text-center", `dynamic-cell-${sectionType}`);
                    td.dataset.dynamicSection = sectionType;
                    td.dataset.pendingKind = sourceSection;
                    td.dataset.periodType = 'quarter';
                    td.dataset.periodIndex = String(currentCol);

                    const wrapper = document.createElement("div");
                    wrapper.className = "office-lines";

                    appendInputLine(wrapper, buildPendingValueInput({
                        sourceSection,
                        officeId: 'car-total',
                        isCarTotal: true,
                    }));

                    officeEntries.forEach((office, officeIndex) => {
                        const currentGroupIndex = groupStartToIndex.get(officeIndex);
                        if (currentGroupIndex !== undefined && Boolean(groupPenroFlags[currentGroupIndex])) {
                            const currentRange = groupRanges[currentGroupIndex];
                            const groupOfficeIds = officeEntries
                                .slice(currentRange.start, currentRange.end + 1)
                                .map(item => String(item?.id || '').trim())
                                .filter(Boolean);
                            appendInputLine(wrapper, buildPendingValueInput({
                                sourceSection,
                                officeId: `group-total-${currentGroupIndex}`,
                                isGroupTotal: true,
                                groupOfficeIds,
                            }));
                        }

                        const officeId = Number(office?.id || 0) || null;
                        appendInputLine(wrapper, buildPendingValueInput({
                            sourceSection,
                            officeId: officeId ? String(officeId) : '',
                        }));
                    });

                    td.appendChild(wrapper);
                    const remarksCell = row.querySelector('td[data-dynamic-section="remarks"]');
                    if (remarksCell) {
                        row.insertBefore(td, remarksCell);
                    } else {
                        row.appendChild(td);
                    }
                });
            } else {
                PERIODS.forEach((period, idx) => {
                    const td = document.createElement("td");
                    td.classList.add("p-1", "text-center");
                    td.classList.add(`dynamic-cell-${sectionType}`);
                    td.dataset.dynamicSection = sectionType;
                    td.dataset.periodType = period.type;
                    td.dataset.periodIndex = String(idx);
                    td.dataset.periodIndex = String(idx);

                    const wrapper = document.createElement("div");
                    wrapper.className = "office-lines";

                    const officeEntries = assignedOffices.length > 0
                        ? assignedOffices
                        : [{ id: currentOfficeId || null, name: 'Office' }];
                    const rowCarTotalsKeyPrefix = `${programId}|${indicatorId}|`;

                    const carInput = document.createElement("input");
                    configurePeriodInput(carInput);
                    carInput.className = `month-box ${sectionType}-box car-total-box`;
                    carInput.style.width = "70px";
                    carInput.style.maxWidth = "150px";
                    carInput.value = "0";
                    carInput.readOnly = true;
                    carInput.dataset.section = sectionType;
                    carInput.dataset.col = idx;
                    carInput.dataset.officeId = 'car-total';
                    carInput.dataset.carTotal = '1';
                    const storedCarTotal = sectionType === 'target'
                        ? existingTargetCarTotalsByRow.get(`${rowCarTotalsKeyPrefix}${PERIOD_KEYS[idx]}`)
                        : sectionType === 'accomp'
                            ? existingAccompCarTotalsByRow.get(`${rowCarTotalsKeyPrefix}${PERIOD_KEYS[idx]}`)
                            : null;
                    if (Number.isFinite(Number(storedCarTotal))) {
                        carInput.value = Number(storedCarTotal);
                    }

                    const carInputLine = document.createElement('div');
                    carInputLine.className = 'input-line';
                    carInputLine.appendChild(carInput);
                    wrapper.appendChild(carInputLine);

                    const groupRanges = [];
                    let rangeStart = 0;
                    const sortedBreaks = [...groupBreakIndices]
                        .map(index => Number(index))
                        .filter(index => Number.isInteger(index) && index >= 0)
                        .sort((left, right) => left - right);

                    sortedBreaks.forEach(breakIndex => {
                        if (breakIndex >= rangeStart && breakIndex < officeEntries.length) {
                            groupRanges.push({ start: rangeStart, end: breakIndex });
                            rangeStart = breakIndex + 1;
                        }
                    });

                    if (rangeStart < officeEntries.length) {
                        groupRanges.push({ start: rangeStart, end: officeEntries.length - 1 });
                    }

                    const groupStartToIndex = new Map();
                    groupRanges.forEach((range, rangeIndex) => {
                        groupStartToIndex.set(range.start, rangeIndex);
                    });

                    officeEntries.forEach((office, officeIndex) => {
                        const currentGroupIndex = groupStartToIndex.get(officeIndex);
                        if (currentGroupIndex !== undefined) {
                            const currentRange = groupRanges[currentGroupIndex];
                            const shouldRenderGroupInput = Boolean(groupPenroFlags[currentGroupIndex]);
                            const groupOfficeIds = officeEntries
                                .slice(currentRange.start, currentRange.end + 1)
                                .map(item => String(item?.id || '').trim())
                                .filter(Boolean);

                            if (shouldRenderGroupInput) {
                                const groupInput = document.createElement("input");
                                configurePeriodInput(groupInput);
                                groupInput.className = `month-box ${sectionType}-box group-total-box`;
                                groupInput.style.width = "70px";
                                groupInput.style.maxWidth = "150px";
                                groupInput.value = "0";
                                groupInput.readOnly = true;
                                groupInput.dataset.section = sectionType;
                                groupInput.dataset.col = idx;
                                groupInput.dataset.groupTotal = '1';
                                groupInput.dataset.groupKey = `group-${currentGroupIndex}`;
                                groupInput.dataset.groupOfficeIds = groupOfficeIds.join(',');
                                groupInput.dataset.officeId = `group-total-${currentGroupIndex}`;

                                const groupInputLine = document.createElement('div');
                                groupInputLine.className = 'input-line';
                                groupInputLine.appendChild(groupInput);
                                wrapper.appendChild(groupInputLine);
                            }
                        }

                        const officeId = Number(office?.id || 0) || null;

                        const input = document.createElement("input");
                        configurePeriodInput(input);
                        input.className = `month-box ${sectionType}-box`;
                        input.style.width = "70px";
                        input.style.maxWidth = "150px";
                        input.value = "0";
                        input.min = "0";
                        input.step = "any";
                        input.dataset.section = sectionType;
                        input.dataset.col = idx;
                        input.dataset.officeId = officeId ? String(officeId) : '';

                        const periodKey = PERIOD_KEYS[idx] || null;
                        const officeData = officeId ? existingRowDataByOffice[String(officeId)] : null;
                        const isExcelImportedOfficeData = String(officeData?.imported_from || '') === 'excel';
                        if (officeData && periodKey && Object.prototype.hasOwnProperty.call(officeData, periodKey)) {
                            input.value = officeData[periodKey] ?? 0;
                        }

                        // For summary, always readOnly
                        if (sectionType === 'summary' || sectionType === 'pending' || (period.type !== "month" && !isExcelImportedOfficeData)) {
                            input.readOnly = true;
                            td.classList.add(
                                period.type === "quarter"
                                    ? (sectionType === "target" ? "target-total" : sectionType === 'summary' ? "quarter-total" : "quarter-total")
                                    : (sectionType === "target" ? "annual-target" : sectionType === 'summary' ? "annual-total" : "annual-total")
                            );
                        } else if (indicatorType === 'semi-cumulative') {
                            input.readOnly = period.type === 'annual';
                        }

                        const inputLine = document.createElement('div');
                        inputLine.className = 'input-line';
                        inputLine.appendChild(input);
                        wrapper.appendChild(inputLine);
                    });

                    td.appendChild(wrapper);

                    const remarksCell = row.querySelector('td[data-dynamic-section="remarks"]');
                    if (remarksCell) {
                        row.insertBefore(td, remarksCell);
                    } else {
                        row.appendChild(td);
                    }
                });
            }
            });

            if (!totalsListenerRegistered) {
                document.getElementById("performanceTable").addEventListener('input', updateTotals);
                totalsListenerRegistered = true;
            }

            recalculateSectionRows(sectionType);
            recalculateCarTotalsForSection(sectionType);
            applyMonthInputVisibility();
            refreshSummaryCards();
        }

        function recalculateSectionRows(sectionType) {
            document.querySelectorAll('tbody tr[data-row-id]').forEach(row => {
                const allInputs = row.querySelectorAll('.month-box');
                const officeIds = getOfficeIdsFromSectionInputs(allInputs, sectionType);
                const indicatorType = getIndicatorTypeForRow(row);

                officeIds.forEach(officeId => {
                    const monthInputs = Array.from(allInputs)
                        .filter(i => i.dataset.section === sectionType
                            && String(i.dataset.officeId || '') === String(officeId)
                            && PERIODS[Number(i.dataset.col)]?.type === 'month');

                    if (monthInputs.length === 12) {
                        updateSection(monthInputs, allInputs, sectionType, indicatorType, officeId, true);
                    }
                });
            });
        }

        function getAggregatePayloadForRow(row, sectionType) {
            const sectionInputs = Array.from(row.querySelectorAll(`.month-box[data-section="${sectionType}"]`));

            const car_totals = PERIOD_KEYS.reduce((acc, periodKey, index) => {
                const input = sectionInputs.find(candidate =>
                    candidate.dataset.carTotal === '1' && Number(candidate.dataset.col) === index
                );

                const value = parsePeriodInputValue(input?.value ?? 0);
                acc[periodKey] = Number.isFinite(value) ? value : 0;
                return acc;
            }, {});

            const group_totals = {};

            sectionInputs
                .filter(input => input.dataset.groupTotal === '1')
                .forEach(input => {
                    const groupKey = String(input.dataset.groupKey || '').trim();
                    const colIndex = Number(input.dataset.col);
                    const periodKey = PERIOD_KEYS[colIndex] || null;

                    if (!groupKey || !periodKey) return;

                    if (!group_totals[groupKey]) {
                        group_totals[groupKey] = {};
                    }

                    const value = parsePeriodInputValue(input.value);
                    group_totals[groupKey][periodKey] = Number.isFinite(value) ? value : 0;
                });

            return { car_totals, group_totals };
        }

        function getSectionPayloadForRow(row, sectionType) {
            const indicatorId = Number(row.dataset.indicatorId || 0);
            const rowId = Number(row.dataset.rowId || 0);
            const programId = Number(row.dataset.programId || rowId || 0);
            if (!indicatorId || !programId || !rowId) return [];

            const aggregatePayload = getAggregatePayloadForRow(row, sectionType);

            const inputs = Array.from(row.querySelectorAll('.month-box'))
                .filter(i => i.dataset.section === sectionType
                    && i.dataset.carTotal !== '1'
                    && i.dataset.groupTotal !== '1');

            if (inputs.length === 0) return [];

            const officeIds = getOfficeIdsFromSectionInputs(inputs, sectionType);
            if (officeIds.length === 0) return [];

            return officeIds.map(officeId => {
                const officeInputs = inputs
                    .filter(i => String(i.dataset.officeId || '') === String(officeId))
                    .sort((left, right) => Number(left.dataset.col) - Number(right.dataset.col));

                if (officeInputs.length !== PERIOD_KEYS.length) return null;

                const entry = {
                    program_id: programId,
                    row_id: rowId,
                    indicator_id: indicatorId,
                    office_id: Number(officeId) || (currentOfficeId || null),
                    year: currentYear,
                };

                PERIOD_KEYS.forEach((key, index) => {
                    const value = parsePeriodInputValue(officeInputs[index]?.value);
                    entry[key] = Number.isFinite(value) ? value : 0;
                });

                entry.car_totals = aggregatePayload.car_totals;
                entry.group_totals = aggregatePayload.group_totals;

                return entry;
            }).filter(Boolean);
        }

        function collectSectionEntries(sectionType) {
            return Array.from(document.querySelectorAll('tbody tr[data-row-id]'))
                .flatMap(row => getSectionPayloadForRow(row, sectionType) || [])
                .filter(Boolean);
        }

        function getStoredEntryByOffice(sourceByIndicator, programId, indicatorId, officeId) {
            const programKey = String(programId || '').trim();
            const indicatorKey = String(indicatorId || '').trim();
            const officeKey = String(officeId || '').trim();
            if (!programKey || !indicatorKey || !officeKey) return null;

            return sourceByIndicator?.[programKey]?.[indicatorKey]?.[officeKey] || null;
        }

        function hasAnyNonZeroPeriod(entry) {
            return PERIOD_KEYS.some(key => {
                const value = Number(entry?.[key] ?? 0);
                return Number.isFinite(value) && value !== 0;
            });
        }

        function hasPeriodDifferences(entry, storedEntry) {
            if (!storedEntry) {
                return hasAnyNonZeroPeriod(entry);
            }

            return PERIOD_KEYS.some(key => {
                const left = Number(entry?.[key] ?? 0);
                const right = Number(storedEntry?.[key] ?? 0);
                const safeLeft = Number.isFinite(left) ? left : 0;
                const safeRight = Number.isFinite(right) ? right : 0;
                return safeLeft !== safeRight;
            });
        }

        function hasEntryChanged(sectionType, entry) {
            const rowId = String(entry?.row_id || entry?.program_id || '').trim();
            const indicatorId = String(entry?.indicator_id || '').trim();
            const officeId = String(entry?.office_id || '').trim();
            if (!rowId || !indicatorId || !officeId) return false;

            const sourceByIndicator = sectionType === 'target'
                ? existingTargetsByIndicator
                : existingAccompByIndicator;
            const storedEntry = getStoredEntryByOffice(sourceByIndicator, rowId, indicatorId, officeId);

            const periodChanged = hasPeriodDifferences(entry, storedEntry);
            if (sectionType === 'target') {
                return periodChanged;
            }

            const incomingRemarks = String(entry?.remarks || '').trim();
            const storedRemarks = String(storedEntry?.remarks || '').trim();
            const remarksChanged = storedEntry
                ? incomingRemarks !== storedRemarks
                : incomingRemarks !== '';

            return periodChanged || remarksChanged;
        }

        function applySavedEntriesToExisting(sectionType, savedEntries = []) {
            if (!Array.isArray(savedEntries) || savedEntries.length === 0) return;

            const sourceByIndicator = sectionType === 'target'
                ? existingTargetsByIndicator
                : existingAccompByIndicator;

            savedEntries.forEach(entry => {
                const rowKey = String(entry?.row_id || entry?.program_id || '').trim();
                const indicatorKey = String(entry?.indicator_id || '').trim();
                const officeKey = String(entry?.office_id || '').trim();
                if (!rowKey || !indicatorKey || !officeKey) return;

                if (!sourceByIndicator[rowKey]) {
                    sourceByIndicator[rowKey] = {};
                }

                if (!sourceByIndicator[rowKey][indicatorKey]) {
                    sourceByIndicator[rowKey][indicatorKey] = {};
                }

                const normalized = {};
                PERIOD_KEYS.forEach(key => {
                    const value = Number(entry?.[key] ?? 0);
                    normalized[key] = Number.isFinite(value) ? value : 0;
                });

                if (sectionType === 'accomp') {
                    normalized.remarks = String(entry?.remarks || '').trim();
                }

                sourceByIndicator[rowKey][indicatorKey][officeKey] = {
                    ...(sourceByIndicator[rowKey][indicatorKey][officeKey] || {}),
                    ...normalized,
                };
            });
        }

        function collectChangedTargetEntries() {
            return collectSectionEntries('target').filter(entry => hasEntryChanged('target', entry));
        }

        function collectChangedAccomplishmentEntries() {
            return collectAccomplishmentEntries().filter(entry => hasEntryChanged('accomp', entry));
        }

        function getRemarksByOfficeForRow(row) {
            const remarksInputs = Array.from(row.querySelectorAll('.remarks-box'));
            return remarksInputs.reduce((acc, input) => {
                const officeId = String(input.dataset.officeId || '').trim();
                if (!officeId) return acc;
                acc[officeId] = String(input.value || '').trim();
                return acc;
            }, {});
        }

        function collectAccomplishmentEntries() {
            return Array.from(document.querySelectorAll('tbody tr[data-row-id]'))
                .flatMap(row => {
                    const indicatorId = Number(row.dataset.indicatorId || 0);
                    const rowId = Number(row.dataset.rowId || 0);
                    const programId = Number(row.dataset.programId || rowId || 0);
                    if (!indicatorId || !programId || !rowId) return [];

                    const aggregatePayload = getAggregatePayloadForRow(row, 'accomp');

                    const officeIds = new Set();
                    getAssignedOfficeIdsForRow(row).forEach(id => officeIds.add(String(id)));

                    const accompInputs = Array.from(row.querySelectorAll('.month-box[data-section="accomp"]'));
                    accompInputs.forEach(input => {
                        if (input.dataset.carTotal === '1') return;
                        if (input.dataset.groupTotal === '1') return;
                        const officeId = String(input.dataset.officeId || '').trim();
                        if (officeId) officeIds.add(officeId);
                    });

                    const remarksByOffice = getRemarksByOfficeForRow(row);
                    Object.keys(remarksByOffice).forEach(officeId => officeIds.add(String(officeId)));

                    if (officeIds.size === 0) {
                        officeIds.add(String(currentOfficeId || '0'));
                    }

                    const existingByOffice = ((existingAccompByIndicator[String(rowId)] || {})[String(indicatorId)]) || {};

                    return Array.from(officeIds).map(officeId => {
                        const entry = {
                            program_id: programId,
                            row_id: rowId,
                            indicator_id: indicatorId,
                            office_id: Number(officeId) || (currentOfficeId || null),
                            year: currentYear,
                        };

                        const officeInputs = accompInputs
                            .filter(input => String(input.dataset.officeId || '') === String(officeId));

                        const existingOfficeData = existingByOffice[String(officeId)] || {};

                        PERIOD_KEYS.forEach((key, index) => {
                            const matchingInput = officeInputs.find(input => Number(input.dataset.col) === index);
                            if (matchingInput) {
                                const value = parsePeriodInputValue(matchingInput.value);
                                entry[key] = Number.isFinite(value) ? value : 0;
                                return;
                            }

                            const existingValue = Number(existingOfficeData[key] ?? 0);
                            entry[key] = Number.isFinite(existingValue) ? existingValue : 0;
                        });

                        entry.car_totals = aggregatePayload.car_totals;
                        entry.group_totals = aggregatePayload.group_totals;
                        entry.remarks = String(remarksByOffice[String(officeId)] ?? existingOfficeData.remarks ?? '').trim();

                        return entry;
                    });
                })
                .filter(Boolean);
        }

        function getAssignedOfficeIdsForRow(row) {
            const raw = String(row?.dataset?.inputOfficeIds || row?.dataset?.officeIds || '').trim();
            if (!raw) return [];

            return raw
                .split(',')
                .map(value => Number(String(value).trim()))
                .filter(value => Number.isInteger(value) && value > 0);
        }

        function getAssignedOfficesForRow(row) {
            const ids = getAssignedOfficeIdsForRow(row);
            const names = String(row?.dataset?.inputOfficeNames || row?.dataset?.officeNames || '')
                .split('|')
                .map(value => value.trim())
                .filter(Boolean);
            return ids.map((id, index) => ({
                id,
                name: names[index] || `Office ${id}`,
            }));
        }

        function getOfficeIdsFromSectionInputs(inputs, sectionType) {
            const ids = Array.from(inputs)
                .filter(input => input.dataset.section === sectionType
                    && input.dataset.carTotal !== '1'
                    && input.dataset.groupTotal !== '1'
                    && String(input.dataset.officeId || '').trim() !== '')
                .map(input => String(input.dataset.officeId));

            return Array.from(new Set(ids));
        }

        function getInputBreakIndicesForRow(row) {
            const raw = String(row?.dataset?.inputBreakIndices || '').trim();
            if (!raw) return [];

            return raw
                .split(',')
                .map(value => Number(String(value).trim()))
                .filter(value => Number.isInteger(value) && value >= 0);
        }

        function getInputGroupPenroFlagsForRow(row) {
            const raw = String(row?.dataset?.inputGroupPenroFlags || '').trim();
            if (!raw) return [];

            return raw
                .split(',')
                .map(value => Number(String(value).trim()) === 1);
        }

        async function saveSectionEntries(sectionType, options = {}) {
            const {
                requireVisible = true,
                showAlerts = true,
                precomputedEntries = null,
            } = options;

            const isTarget = sectionType === 'target';
            const shouldBeVisible = isTarget ? targetsVisible : (accompVisible || remarksVisible);

            if (requireVisible && !shouldBeVisible) {
                if (showAlerts) {
                    showTopRightErrorAlert(`Please open ${isTarget ? 'Targets' : 'Accomplishments'} first before saving.`);
                }
                return { success: false, skipped: true, message: 'Section is not visible.' };
            }

            const entries = Array.isArray(precomputedEntries)
                ? precomputedEntries
                : (isTarget
                    ? collectChangedTargetEntries()
                    : collectChangedAccomplishmentEntries());
            if (entries.length === 0) {
                return { success: true, skipped: true, message: 'No rows to save.' };
            }

            const url = isTarget ? targetStoreUrl : accompStoreUrl;
            const tokenInput = document.querySelector('input[name="_token"]');
            const token = tokenInput ? tokenInput.value : '';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ entries }),
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || `Failed to save ${sectionType}.`);
                }

                if (showAlerts) {
                    showTopRightSuccessAlert('Data saved successfully.');
                }

                applySavedEntriesToExisting(isTarget ? 'target' : 'accomp', entries);

                return {
                    success: true,
                    message: 'Data saved successfully.',
                };
            } catch (error) {
                console.error(`${sectionType} save error:`, error);
                if (showAlerts) {
                    showTopRightErrorAlert(`Error saving ${isTarget ? 'targets' : 'accomplishments'}. Please try again.`);
                }

                return {
                    success: false,
                    message: error?.message || `Error saving ${isTarget ? 'targets' : 'accomplishments'}.`,
                };
            }
        }

        async function saveAllSectionEntries() {
            const saveAllBtn = document.getElementById('saveAllBtn');
            if (PMSButtonLoading.isBusy(saveAllBtn)) return;

            const targetEntries = collectChangedTargetEntries();
            const accompEntries = collectChangedAccomplishmentEntries();

            if (targetEntries.length === 0 && accompEntries.length === 0) {
                showTopRightErrorAlert('No input rows available to save.');
                return;
            }

            PMSButtonLoading.start(saveAllBtn);

            try {
                const [targetResult, accompResult] = await Promise.all([
                    saveSectionEntries('target', {
                        requireVisible: false,
                        showAlerts: false,
                        precomputedEntries: targetEntries,
                    }),
                    saveSectionEntries('accomp', {
                        requireVisible: false,
                        showAlerts: false,
                        precomputedEntries: accompEntries,
                    }),
                ]);

                const results = [targetResult, accompResult];
                const failed = results.filter(result => !result.success);
                if (failed.length > 0) {
                    showTopRightErrorAlert('Some entries failed to save. Please try again.');
                    return;
                }

                if (results.every(result => result.skipped)) {
                    showTopRightErrorAlert('No input rows available to save.');
                    return;
                }

                showTopRightSuccessAlert('Data saved successfully.');
            } finally {
                PMSButtonLoading.stop(saveAllBtn);
            }
        }

        function removeSectionColumns(groupRow, mainHeader, sectionType) {
            const groupCell = groupRow.querySelector(`.group-${sectionType}`);
            if (!groupCell) {
                refreshSummaryCards();
                return;
            }

            mainHeader
                .querySelectorAll(`th[data-dynamic-section="${sectionType}"]`)
                .forEach(cell => cell.remove());

            document.querySelectorAll(`tbody tr[data-row-id] td[data-dynamic-section="${sectionType}"]`)
                .forEach(cell => cell.remove());

            groupCell.remove();

            const hasDynamicGroups = groupRow.querySelectorAll('.group-header').length > 0;
            if (!hasDynamicGroups) {
                while (groupRow.firstChild) {
                    groupRow.removeChild(groupRow.firstChild);
                }
            }

            refreshSummaryCards();
        }

        function updateTotals(e) {
            const input = e.target;
            if (!input.classList.contains('month-box')) return;

            const row = input.closest('tr');
            if (!row) return;

            const indicatorType = getIndicatorTypeForRow(row);
            if (input.readOnly) return;

            const allInputs = row.querySelectorAll('.month-box');
            const officeId = String(input.dataset.officeId || '');
            const sectionType = String(input.dataset.section || '');
            const periodType = PERIODS[Number(input.dataset.col)]?.type || '';

            if (!officeId || !sectionType) return;

            if (sectionType === 'pending') {
                refreshEditablePendingTotals(row, String(input.dataset.sourceSection || input.dataset.pendingKind || ''));
                return;
            }

            const syncedRows = syncMonthValueAcrossCoreRows(input);

            if (periodType !== 'month') {
                syncedRows.forEach(syncedRow => {
                    recalculateCarTotalsForRow(syncedRow, sectionType, false);
                });

                recalculateCarTotalsForRow(row, sectionType, false);
                refreshSummaryCards();
                return;
            }

            const sectionInputs = Array.from(allInputs).filter(i =>
                i.dataset.section === sectionType
                && String(i.dataset.officeId || '') === officeId
                && PERIODS[Number(i.dataset.col)]?.type === 'month'
            );

            if (sectionInputs.length === 12) {
                updateSection(sectionInputs, allInputs, sectionType, indicatorType, officeId, false);
            }

            syncedRows.forEach(syncedRow => {
                recalculateRowOfficeSection(syncedRow, sectionType, officeId);
                recalculateCarTotalsForRow(syncedRow, sectionType, false);
            });

            recalculateCarTotalsForRow(row, sectionType, false);

            refreshSummaryCards();
        }

        function refreshEditablePendingTotals(row, sourceSection) {
            if (!row || !sourceSection) return;

            const colIndex = getCurrentMonthPeriodIndex();
            const sourceInputs = Array.from(row.querySelectorAll(`.month-box[data-section="pending"][data-source-section="${sourceSection}"][data-col="${colIndex}"]`));
            const officeInputs = sourceInputs.filter(input => input.dataset.carTotal !== '1' && input.dataset.groupTotal !== '1');

            sourceInputs
                .filter(input => input.dataset.groupTotal === '1')
                .forEach(groupInput => {
                    const groupOfficeIds = String(groupInput.dataset.groupOfficeIds || '')
                        .split(',')
                        .map(value => value.trim())
                        .filter(Boolean);
                    groupInput.value = officeInputs
                        .filter(input => groupOfficeIds.includes(String(input.dataset.officeId || '')))
                        .reduce((sum, input) => {
                            const value = parsePeriodInputValue(input.value);
                            return sum + (Number.isFinite(value) ? value : 0);
                        }, 0);
                });

            const carInput = sourceInputs.find(input => input.dataset.carTotal === '1');
            if (carInput) {
                const groupedOfficeIds = new Set();
                sourceInputs
                    .filter(input => input.dataset.groupTotal === '1')
                    .forEach(input => {
                        String(input.dataset.groupOfficeIds || '')
                            .split(',')
                            .map(value => value.trim())
                            .filter(Boolean)
                            .forEach(officeId => groupedOfficeIds.add(officeId));
                    });

                const directOfficeTotal = officeInputs
                    .filter(input => !groupedOfficeIds.has(String(input.dataset.officeId || '')))
                    .reduce((sum, input) => {
                        const value = parsePeriodInputValue(input.value);
                        return sum + (Number.isFinite(value) ? value : 0);
                    }, 0);
                const groupTotal = sourceInputs
                    .filter(input => input.dataset.groupTotal === '1')
                    .reduce((sum, input) => {
                        const value = parsePeriodInputValue(input.value);
                        return sum + (Number.isFinite(value) ? value : 0);
                    }, 0);

                carInput.value = directOfficeTotal + groupTotal;
            }
        }

        function recalculateRowOfficeSection(row, sectionType, officeId) {
            if (!row || !sectionType || !officeId) return;

            const indicatorType = getIndicatorTypeForRow(row);
            const allInputs = row.querySelectorAll('.month-box');
            const monthInputs = Array.from(allInputs).filter(i =>
                i.dataset.section === sectionType
                && String(i.dataset.officeId || '') === String(officeId)
                && PERIODS[Number(i.dataset.col)]?.type === 'month'
            );

            if (monthInputs.length === 12) {
                updateSection(monthInputs, allInputs, sectionType, indicatorType, officeId, false);
            }
        }

        function recalculateCarTotalsForSection(sectionType, preferStoredTotals = true) {
            document.querySelectorAll('tbody tr[data-row-id]').forEach(row => {
                recalculateCarTotalsForRow(row, sectionType, preferStoredTotals);
            });
        }

        function recalculateCarTotalsForRow(row, sectionType, preferStoredTotals = true) {
            if (!row || !sectionType) return;

            const sectionInputs = Array.from(row.querySelectorAll(`.month-box[data-section="${sectionType}"]`));
            if (sectionInputs.length === 0) return;

            const isFinancialSection = sectionType === 'financial' || sectionType === 'financial-accomp';
            const indicatorType = getIndicatorTypeForRow(row);
            const hasIndicatorType = String(row.dataset.indicatorType || '').trim() !== '';
            const programId = String(row.dataset.rowId || '').trim();
            const indicatorId = String(row.dataset.indicatorId || '').trim();
            const sourceInputs = sectionInputs.filter(input => input.dataset.carTotal !== '1' && input.dataset.groupTotal !== '1');
            const groupInputs = sectionInputs.filter(input => input.dataset.groupTotal === '1');
            const carInputs = sectionInputs.filter(input => input.dataset.carTotal === '1');
            if (carInputs.length === 0 && groupInputs.length === 0) return;

            const monthColIndices = [0, 1, 2, 4, 5, 6, 8, 9, 10, 12, 13, 14];
            const quarterColIndices = [3, 7, 11, 15];

            const getValuesForCol = (colIndex, officeSet = null) => {
                return sourceInputs
                    .filter(input => Number(input.dataset.col) === colIndex)
                    .filter(input => !officeSet || officeSet.has(String(input.dataset.officeId || '')))
                    .map(input => {
                        const value = parsePeriodInputValue(input.value);
                        return Number.isFinite(value) ? value : 0;
                    });
            };

            const aggregateValues = (values) => {
                if (values.length === 0) return 0;

                if (!isFinancialSection && indicatorType === 'non-cumulative') {
                    return Math.max(...values);
                }

                return values.reduce((sum, value) => sum + value, 0);
            };

            const buildComputedTotals = (officeSet = null) => {
                const totals = {};

                monthColIndices.forEach(colIndex => {
                    totals[colIndex] = aggregateValues(getValuesForCol(colIndex, officeSet));
                });

                if (!hasIndicatorType) {
                    PERIOD_KEYS.forEach((periodKey, colIndex) => {
                        totals[colIndex] = aggregateValues(getValuesForCol(colIndex, officeSet));
                    });
                    return totals;
                }

                if (!isFinancialSection && indicatorType === 'semi-cumulative') {
                    totals[3] = (totals[0] || 0) + (totals[1] || 0) + (totals[2] || 0);
                    totals[7] = (totals[4] || 0) + (totals[5] || 0) + (totals[6] || 0);
                    totals[11] = (totals[8] || 0) + (totals[9] || 0) + (totals[10] || 0);
                    totals[15] = (totals[12] || 0) + (totals[13] || 0) + (totals[14] || 0);
                    totals[16] = totals[3] + totals[7] + totals[11] + totals[15];
                    return totals;
                }

                const q1 = !isFinancialSection && indicatorType === 'non-cumulative'
                    ? window.pmsMostFrequentTotal([totals[0] || 0, totals[1] || 0, totals[2] || 0])
                    : (totals[0] || 0) + (totals[1] || 0) + (totals[2] || 0);

                const q2 = !isFinancialSection && indicatorType === 'non-cumulative'
                    ? window.pmsMostFrequentTotal([totals[4] || 0, totals[5] || 0, totals[6] || 0])
                    : (totals[4] || 0) + (totals[5] || 0) + (totals[6] || 0);

                const q3 = !isFinancialSection && indicatorType === 'non-cumulative'
                    ? window.pmsMostFrequentTotal([totals[8] || 0, totals[9] || 0, totals[10] || 0])
                    : (totals[8] || 0) + (totals[9] || 0) + (totals[10] || 0);

                const q4 = !isFinancialSection && indicatorType === 'non-cumulative'
                    ? window.pmsMostFrequentTotal([totals[12] || 0, totals[13] || 0, totals[14] || 0])
                    : (totals[12] || 0) + (totals[13] || 0) + (totals[14] || 0);

                totals[3] = q1;
                totals[7] = q2;
                totals[11] = q3;
                totals[15] = q4;
                totals[16] = !isFinancialSection && indicatorType === 'non-cumulative'
                    ? window.pmsMostFrequentTotal([q1, q2, q3, q4])
                    : q1 + q2 + q3 + q4;

                return totals;
            };

            const mergeStoredTotalsWithComputedFallback = (storedTotals, computedTotals, useComputedWhenStoredAllZero = false) => {
                if (!storedTotals) return computedTotals;

                const storedHasAnyNonZero = Object.values(storedTotals).some(value => Number(value) !== 0);
                const computedHasAnyNonZero = Object.values(computedTotals || {}).some(value => Number(value) !== 0);

                if (useComputedWhenStoredAllZero && !storedHasAnyNonZero && computedHasAnyNonZero) {
                    return computedTotals;
                }

                const merged = { ...computedTotals };
                PERIOD_KEYS.forEach((periodKey, colIndex) => {
                    if (!Object.prototype.hasOwnProperty.call(storedTotals, colIndex)) return;

                    const storedValue = Number(storedTotals[colIndex]);

                    if (!Number.isFinite(storedValue)) return;

                    merged[colIndex] = storedValue;
                });

                return merged;
            };

            const applyTotalsToInputs = (inputs, totals) => {
                inputs.forEach(input => {
                    const colIndex = Number(input.dataset.col);
                    if (!Number.isInteger(colIndex)) return;

                    if (Object.prototype.hasOwnProperty.call(totals, colIndex)) {
                        input.value = totals[colIndex];
                    }
                });
            };

            const groupedInputsByKey = groupInputs.reduce((acc, input) => {
                const key = String(input.dataset.groupKey || '').trim();
                if (!key) return acc;
                if (!acc[key]) acc[key] = [];
                acc[key].push(input);
                return acc;
            }, {});

            Object.values(groupedInputsByKey).forEach(inputs => {
                const groupKey = String(inputs[0]?.dataset?.groupKey || '').trim();
                const officeSet = new Set(
                    String(inputs[0]?.dataset?.groupOfficeIds || '')
                        .split(',')
                        .map(value => value.trim())
                        .filter(Boolean)
                );

                const storedGroupTotals = (() => {
                    if (!programId || !indicatorId || !groupKey) return null;

                    const source = sectionType === 'target'
                        ? existingTargetGroupTotalsByRow
                        : sectionType === 'accomp'
                            ? existingAccompGroupTotalsByRow
                            : null;
                    if (!source) return null;

                    const totals = {};
                    PERIOD_KEYS.forEach((periodKey, index) => {
                        const value = source.get(`${programId}|${indicatorId}|${groupKey}|${periodKey}`);
                        if (Number.isFinite(Number(value))) {
                            totals[index] = Number(value);
                        }
                    });

                    return Object.keys(totals).length > 0 ? totals : null;
                })();

                const computedTotals = buildComputedTotals(officeSet);
                const totals = computedTotals;
                applyTotalsToInputs(inputs, totals);
            });

            const groupedOfficeIds = new Set();
            groupInputs.forEach(input => {
                String(input.dataset.groupOfficeIds || '')
                    .split(',')
                    .map(value => value.trim())
                    .filter(Boolean)
                    .forEach(officeId => groupedOfficeIds.add(officeId));
            });

            const buildCarTotalsFromDisplayedRows = () => {
                const totals = {};

                PERIOD_KEYS.forEach((periodKey, colIndex) => {
                    const displayedValues = [];

                    sourceInputs
                        .filter(input => Number(input.dataset.col) === colIndex)
                        .filter(input => !groupedOfficeIds.has(String(input.dataset.officeId || '')))
                        .forEach(input => {
                            const value = parsePeriodInputValue(input.value);
                            displayedValues.push(Number.isFinite(value) ? value : 0);
                        });

                    groupInputs
                        .filter(input => Number(input.dataset.col) === colIndex)
                        .forEach(input => {
                            const value = parsePeriodInputValue(input.value);
                            displayedValues.push(Number.isFinite(value) ? value : 0);
                        });

                    totals[colIndex] = displayedValues.reduce((sum, value) => sum + value, 0);
                });

                return totals;
            };

            const storedCarTotals = (() => {
                if (!programId || !indicatorId) return null;

                const source = sectionType === 'target'
                    ? existingTargetCarTotalsByRow
                    : sectionType === 'accomp'
                        ? existingAccompCarTotalsByRow
                        : null;
                if (!source) return null;

                const totals = {};
                PERIOD_KEYS.forEach((periodKey, index) => {
                    const value = source.get(`${programId}|${indicatorId}|${periodKey}`);
                    if (Number.isFinite(Number(value))) {
                        totals[index] = Number(value);
                    }
                });

                return Object.keys(totals).length > 0 ? totals : null;
            })();

            const computedCarTotals = groupInputs.length > 0
                ? buildCarTotalsFromDisplayedRows()
                : buildComputedTotals(null);
            const carTotals = computedCarTotals;
            applyTotalsToInputs(carInputs, carTotals);
        }

        function syncMonthValueAcrossCoreRows(sourceInput) {
            const sourceRow = sourceInput.closest('tr[data-sync-key]');
            const syncKey = String(sourceRow?.dataset?.syncKey || '').trim();
            if (!syncKey) return [];

            const sectionType = String(sourceInput.dataset.section || '');
            const col = String(sourceInput.dataset.col || '');
            const officeId = String(sourceInput.dataset.officeId || '');
            if (!sectionType || col === '' || !officeId) return [];

            const touchedRows = new Set();

            document.querySelectorAll('.month-box').forEach(candidate => {
                if (candidate === sourceInput) return;
                if (String(candidate.dataset.section || '') !== sectionType) return;
                if (String(candidate.dataset.col || '') !== col) return;
                if (String(candidate.dataset.officeId || '') !== officeId) return;

                const candidateRow = candidate.closest('tr[data-sync-key]');
                if (!candidateRow) return;
                if (String(candidateRow.dataset.syncKey || '').trim() !== syncKey) return;

                candidate.value = sourceInput.value;
                touchedRows.add(candidateRow);
            });

            return Array.from(touchedRows);
        }

        function getIndicatorTypeForRow(row) {
            const rawType = String(row?.dataset?.indicatorType || '')
                .trim()
                .toLowerCase()
                .replace(/[_\s]+/g, '-')
                .replace(/-+/g, '-');
            const compactType = rawType.replace(/[^a-z]/g, '');

            if (
                rawType === 'semi-cumulative'
                || rawType === 'semi-comulative'
                || rawType === 'semicumulative'
                || compactType === 'semicumulative'
                || compactType === 'semicomulative'
                || compactType === 'semicummulative'
            ) {
                return 'semi-cumulative';
            }

            if (
                rawType === 'non-cumulative'
                || rawType === 'non-comulative'
                || rawType === 'noncumulative'
                || compactType === 'noncumulative'
                || compactType === 'noncomulative'
                || compactType === 'noncummulative'
            ) {
                return 'non-cumulative';
            }

            return 'cumulative';
        }

        function getSectionColInput(allInputs, section, colIndex, officeId = null) {
            return Array.from(allInputs).find(i =>
                i.dataset.section === section
                && Number(i.dataset.col) === colIndex
                && String(i.dataset.officeId || '') === String(officeId || '')
            ) || null;
        }

        function restoreStoredOfficePeriodValues(allInputs, section, officeId) {
            const row = Array.from(allInputs)[0]?.closest('tr');
            const programId = String(row?.dataset?.rowId || '').trim();
            const indicatorId = String(row?.dataset?.indicatorId || '').trim();
            const officeKey = String(officeId || '').trim();

            if (!programId || !indicatorId || !officeKey) return false;

            const sourceByIndicator = section === 'target'
                ? existingTargetsByIndicator
                : section === 'accomp'
                    ? existingAccompByIndicator
                    : {};
            const officeData = sourceByIndicator?.[programId]?.[indicatorId]?.[officeKey] || null;

            if (!officeData || typeof officeData !== 'object') return false;
            if (String(officeData.imported_from || '') !== 'excel' && String(officeKey) !== '1') return false;

            PERIOD_KEYS.forEach((periodKey, colIndex) => {
                if (!Object.prototype.hasOwnProperty.call(officeData, periodKey)) return;

                const input = getSectionColInput(allInputs, section, colIndex, officeKey);
                if (input) {
                    input.value = officeData[periodKey] ?? 0;
                }
            });

            return true;
        }

        function updateSection(monthInputs, allInputs, section, indicatorType = 'cumulative', officeId = null, preferStoredValues = false) {
            if (preferStoredValues && restoreStoredOfficePeriodValues(allInputs, section, officeId)
                && (indicatorType !== 'non-cumulative' || section === 'financial' || section === 'financial-accomp')) {
                return;
            }

            const values = monthInputs.map(inp => parsePeriodInputValue(inp.value));

            let q1 = 0;
            let q2 = 0;
            let q3 = 0;
            let q4 = 0;
            let annual = 0;

            if (section === 'financial' || section === 'financial-accomp') {
                q1 = values[0] + values[1] + values[2];
                q2 = values[3] + values[4] + values[5];
                q3 = values[6] + values[7] + values[8];
                q4 = values[9] + values[10] + values[11];
                annual = q1 + q2 + q3 + q4;
            } else if (indicatorType === 'non-cumulative') {
                q1 = window.pmsMostFrequentTotal([values[0] || 0, values[1] || 0, values[2] || 0]);
                q2 = window.pmsMostFrequentTotal([values[3] || 0, values[4] || 0, values[5] || 0]);
                q3 = window.pmsMostFrequentTotal([values[6] || 0, values[7] || 0, values[8] || 0]);
                q4 = window.pmsMostFrequentTotal([values[9] || 0, values[10] || 0, values[11] || 0]);
                annual = window.pmsMostFrequentTotal([q1, q2, q3, q4]);
            } else if (indicatorType === 'semi-cumulative') {
                q1 = values[0] + values[1] + values[2];
                q2 = values[3] + values[4] + values[5];
                q3 = values[6] + values[7] + values[8];
                q4 = values[9] + values[10] + values[11];
                annual = Math.max(q1, q2, q3, q4);
            } else {
                q1 = values[0] + values[1] + values[2];
                q2 = values[3] + values[4] + values[5];
                q3 = values[6] + values[7] + values[8];
                q4 = values[9] + values[10] + values[11];
                annual = q1 + q2 + q3 + q4;
            }

            const q1Input = getSectionColInput(allInputs, section, 3, officeId);
            const q2Input = getSectionColInput(allInputs, section, 7, officeId);
            const q3Input = getSectionColInput(allInputs, section, 11, officeId);
            const q4Input = getSectionColInput(allInputs, section, 15, officeId);
            const annualInput = getSectionColInput(allInputs, section, 16, officeId);

            if (q1Input?.readOnly) q1Input.value = q1;
            if (q2Input?.readOnly) q2Input.value = q2;
            if (q3Input?.readOnly) q3Input.value = q3;
            if (q4Input?.readOnly) q4Input.value = q4;
            if (annualInput) annualInput.value = annual;
        }

        let currentProgramIndicators = [];

        function setOfficeCheckboxes(officeIdsArray = []) {
            const officeIds = new Set((officeIdsArray || []).map(id => String(id)));
            document.querySelectorAll('.office-checkbox').forEach(checkbox => {
                checkbox.checked = officeIds.has(String(checkbox.value));
            });
        }

        function findIndicatorByName(programId, indicatorName) {
            if (!programId || !indicatorName) return null;
            const normalized = String(indicatorName).trim().toLowerCase();
            if (!normalized) return null;

            const source = indicatorsData[programId] || [];
            return source.find(item => String(item.name || '').trim().toLowerCase() === normalized) || null;
        }

        function normalizePapField(value) {
            return String(value || '').replace(/\s+/g, ' ').trim().toLowerCase();
        }

        function getNormalizedPapCoreKey(item) {
            return [
                normalizePapField(item?.title),
                normalizePapField(item?.program),
                normalizePapField(item?.project),
            ].join('|');
        }

        function getUniquePapCoreEntries() {
            const source = Array.isArray(papPrefillData) ? papPrefillData : [];
            const seen = new Set();

            return source.filter(item => {
                const key = getNormalizedPapCoreKey(item);
                if (seen.has(key)) return false;
                seen.add(key);
                return true;
            });
        }

        function buildPapDropdownValue(item) {
            return String(item?.title || '').trim();
        }

        function parsePapDropdownValue(rawValue) {
            const raw = String(rawValue || '').trim();
            if (!raw.includes('|')) return null;

            const parts = raw.split(/\s*\|\s*/);
            if (parts.length < 3) return null;

            return {
                title: parts[0]?.trim() || '',
                program: parts[1]?.trim() || '',
                project: parts.slice(2).join(' | ').trim(),
            };
        }

        function normalizePapCoreInputsFromDropdownValue() {
            const titleInput = document.getElementById('pap_title');
            const programInput = document.getElementById('pap_program');
            const projectInput = document.getElementById('pap_project');

            if (!titleInput) return null;

            const parsed = parsePapDropdownValue(titleInput.value);
            if (!parsed) return null;

            titleInput.value = parsed.title;
            if (programInput) {
                programInput.value = parsed.program;
            }
            if (projectInput) {
                projectInput.value = parsed.project;
            }

            return parsed;
        }

        function findPapByCoreFields({ titleValue, programValue, projectValue }) {
            const normalizedTitle = normalizePapField(titleValue);
            const normalizedProgram = normalizePapField(programValue);
            const normalizedProject = normalizePapField(projectValue);
            if (!normalizedTitle) return null;

            const byTitle = (papPrefillData || []).filter(item =>
                normalizePapField(item?.title) === normalizedTitle
            );
            if (byTitle.length === 0) return null;

            const byTitleProgram = normalizedProgram
                ? byTitle.filter(item => normalizePapField(item?.program) === normalizedProgram)
                : byTitle;

            const byCore = normalizedProject
                ? byTitleProgram.filter(item => normalizePapField(item?.project) === normalizedProject)
                : byTitleProgram;

            return byCore[0] || byTitleProgram[0] || byTitle[0] || null;
        }

        function applyPapFieldsFromTitleSelection() {
            const titleInput = document.getElementById('pap_title');
            const programInput = document.getElementById('pap_program');
            const projectInput = document.getElementById('pap_project');

            normalizePapCoreInputsFromDropdownValue();

            const matchedPap = findPapByCoreFields({
                titleValue: titleInput?.value,
                programValue: programInput?.value,
                projectValue: projectInput?.value,
            });
            if (!matchedPap) return null;

            const papActivitiesInput = document.getElementById('pap_activities');
            const papSubactivitiesInput = document.getElementById('pap_subactivities');
            const papSubSubactivitiesInput = document.getElementById('pap_subsubactivities');
            const papLevel6Input = document.getElementById('pap_level_6');
            const papLevel7Input = document.getElementById('pap_level_7');
            const papLevel8Input = document.getElementById('pap_level_8');

            if (programInput) programInput.value = String(matchedPap.program || '');
            if (projectInput) projectInput.value = String(matchedPap.project || '');
            if (papActivitiesInput) papActivitiesInput.value = String(matchedPap.activities || '');
            if (papSubactivitiesInput) papSubactivitiesInput.value = String(matchedPap.subactivities || '');
            if (papSubSubactivitiesInput) papSubSubactivitiesInput.value = String(matchedPap.subsubactivities || '');
            
            // Show and populate dynamic levels if they exist
            if (matchedPap.level_6) {
                showNextPapLevel(6);
                if (papLevel6Input) papLevel6Input.value = String(matchedPap.level_6);
            }
            if (matchedPap.level_7) {
                showNextPapLevel(7);
                if (papLevel7Input) papLevel7Input.value = String(matchedPap.level_7);
            }
            if (matchedPap.level_8) {
                showNextPapLevel(8);
                if (papLevel8Input) papLevel8Input.value = String(matchedPap.level_8);
            }

            return matchedPap;
        }

        function getPapInputValue(fieldId) {
            return normalizePapField(document.getElementById(fieldId)?.value);
        }

        function papMatchesParents(item, parentFieldIds = []) {
            const fieldMap = {
                pap_title: 'title',
                pap_program: 'program',
                pap_project: 'project',
                pap_activities: 'activities',
                pap_subactivities: 'subactivities',
                pap_subsubactivities: 'subsubactivities',
                pap_level_6: 'level_6',
                pap_level_7: 'level_7',
            };

            return parentFieldIds.every(parentFieldId => {
                const parentValue = getPapInputValue(parentFieldId);
                if (!parentValue) return true;

                const sourceField = fieldMap[parentFieldId];
                return sourceField ? normalizePapField(item?.[sourceField]) === parentValue : true;
            });
        }

        function populateFilteredPapOptions(datalistId, itemField, parentFieldIds = []) {
            const datalist = document.getElementById(datalistId);
            if (!datalist) return;
            window.pmsPopulatePapOptions(datalist, papPrefillData || [], itemField,
                parentFieldIds.map(getPapInputValue), item => papMatchesParents(item, parentFieldIds));
        }

        function clearPapDescendantFields(parentFieldId) {
            const descendantMap = {
                pap_title: ['pap_program', 'pap_project', 'pap_activities', 'pap_subactivities', 'pap_subsubactivities', 'pap_level_6', 'pap_level_7', 'pap_level_8'],
                pap_program: ['pap_project', 'pap_activities', 'pap_subactivities', 'pap_subsubactivities', 'pap_level_6', 'pap_level_7', 'pap_level_8'],
                pap_project: ['pap_activities', 'pap_subactivities', 'pap_subsubactivities', 'pap_level_6', 'pap_level_7', 'pap_level_8'],
                pap_activities: ['pap_subactivities', 'pap_subsubactivities', 'pap_level_6', 'pap_level_7', 'pap_level_8'],
                pap_subactivities: ['pap_subsubactivities', 'pap_level_6', 'pap_level_7', 'pap_level_8'],
                pap_subsubactivities: ['pap_level_6', 'pap_level_7', 'pap_level_8'],
                pap_level_6: ['pap_level_7', 'pap_level_8'],
                pap_level_7: ['pap_level_8'],
            };

            (descendantMap[parentFieldId] || []).forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) field.value = '';
            });
        }
        function populatePapChildDropdowns() {
            populateFilteredPapOptions('pap_program_options', 'program', ['pap_title']);
            populateFilteredPapOptions('pap_project_options', 'project', ['pap_title', 'pap_program']);
            populateFilteredPapOptions('pap_activity_options', 'activities', ['pap_title', 'pap_program', 'pap_project']);
            populateFilteredPapOptions('pap_subactivity_options', 'subactivities', ['pap_title', 'pap_program', 'pap_project', 'pap_activities']);
            populateFilteredPapOptions('pap_subsubactivity_options', 'subsubactivities', ['pap_title', 'pap_program', 'pap_project', 'pap_activities', 'pap_subactivities']);
            populateFilteredPapOptions('pap_level_6_options', 'level_6', ['pap_title', 'pap_program', 'pap_project', 'pap_activities', 'pap_subactivities', 'pap_subsubactivities']);
            populateFilteredPapOptions('pap_level_7_options', 'level_7', ['pap_title', 'pap_program', 'pap_project', 'pap_activities', 'pap_subactivities', 'pap_subsubactivities', 'pap_level_6']);
            populateFilteredPapOptions('pap_level_8_options', 'level_8', ['pap_title', 'pap_program', 'pap_project', 'pap_activities', 'pap_subactivities', 'pap_subsubactivities', 'pap_level_6', 'pap_level_7']);
        }
        function populatePapTitleDropdown() {
            populateFilteredPapOptions('pap_title_options', 'title');
        }

        function findMatchingPapFromModal() {
            const title = normalizePapField(document.getElementById('pap_title')?.value);
            const program = normalizePapField(document.getElementById('pap_program')?.value);
            const project = normalizePapField(document.getElementById('pap_project')?.value);
            const activities = normalizePapField(document.getElementById('pap_activities')?.value);
            const subactivities = normalizePapField(document.getElementById('pap_subactivities')?.value);
            const subsubactivities = normalizePapField(document.getElementById('pap_subsubactivities')?.value);
            const level6 = normalizePapField(document.getElementById('pap_level_6')?.value);
            const level7 = normalizePapField(document.getElementById('pap_level_7')?.value);
            const level8 = normalizePapField(document.getElementById('pap_level_8')?.value);

            if (!title && !program && !project && !activities && !subactivities && !subsubactivities && !level6 && !level7 && !level8) {
                return null;
            }

            return (papPrefillData || []).find(item =>
                normalizePapField(item?.title) === title
                && normalizePapField(item?.program) === program
                && normalizePapField(item?.project) === project
                && normalizePapField(item?.activities) === activities
                && normalizePapField(item?.subactivities) === subactivities
                && normalizePapField(item?.subsubactivities) === subsubactivities
                && normalizePapField(item?.level_6) === level6
                && normalizePapField(item?.level_7) === level7
                && normalizePapField(item?.level_8) === level8
            ) || null;
        }

        function getSelectedIndicatorFromPapMatch(matchedPap) {
            const indicatorNameInput = document.getElementById('modal_indicator_name');
            const normalizedIndicatorName = normalizePapField(indicatorNameInput?.value);
            const hasTypedIndicatorName = normalizedIndicatorName !== '';

            if (!matchedPap || !Array.isArray(matchedPap.indicators) || matchedPap.indicators.length === 0) {
                return null;
            }

            return hasTypedIndicatorName
                ? (matchedPap.indicators.find(i => normalizePapField(i?.name) === normalizedIndicatorName) || null)
                : (matchedPap.indicators.find(i => String(i?.name || '').trim() !== '') || matchedPap.indicators[0] || null);
        }

        function applyModalPrefillFromExistingPap() {
            const matchedPap = findMatchingPapFromModal();
            const indicatorIdInput = document.getElementById('indicator_id');
            const indicatorNameInput = document.getElementById('modal_indicator_name');
            const indicatorTypeInput = document.getElementById('modal_indicator_type');
            const indicatorTypeToggle = document.getElementById('use_indicator_type');
            const normalizedIndicatorName = normalizePapField(indicatorNameInput?.value);
            const hasTypedIndicatorName = normalizedIndicatorName !== '';

            if (!matchedPap || !Array.isArray(matchedPap.indicators) || matchedPap.indicators.length === 0) {
                if (indicatorIdInput) {
                    indicatorIdInput.value = '';
                    delete indicatorIdInput.dataset.rowId;
                }
                if (!hasTypedIndicatorName && indicatorNameInput) {
                    indicatorNameInput.value = '';
                }
                if (indicatorTypeInput) {
                    indicatorTypeInput.value = '';
                }
                if (indicatorTypeToggle) {
                    indicatorTypeToggle.checked = false;
                }
                toggleIndicatorTypeDropdown();
                setOfficeCheckboxes([]);
                return;
            }

            const selectedIndicator = getSelectedIndicatorFromPapMatch(matchedPap);

            if (!selectedIndicator) {
                const hadLinkedIndicator = Boolean(String(indicatorIdInput?.value || '').trim());

                if (indicatorIdInput) {
                    indicatorIdInput.value = '';
                    delete indicatorIdInput.dataset.rowId;
                }

                if (hadLinkedIndicator) {
                    if (indicatorTypeInput) {
                        indicatorTypeInput.value = '';
                    }
                    if (indicatorTypeToggle) {
                        indicatorTypeToggle.checked = false;
                    }
                    toggleIndicatorTypeDropdown();
                    setOfficeCheckboxes([]);
                }

                return;
            }

            if (indicatorNameInput) {
                indicatorNameInput.value = String(selectedIndicator.name || '').trim();
            }

            if (indicatorTypeInput) {
                indicatorTypeInput.value = String(selectedIndicator.indicator_type_id || '').trim();
            }

            if (indicatorTypeToggle) {
                indicatorTypeToggle.checked = Boolean(indicatorTypeInput?.value);
            }

            toggleIndicatorTypeDropdown();

            if (indicatorIdInput) {
                indicatorIdInput.value = String(selectedIndicator.id || '').trim();
                indicatorIdInput.dataset.rowId = String(selectedIndicator.row_id || matchedPap?.row_id || '').trim();
            }

            setOfficeCheckboxes(selectedIndicator.office_ids || []);
        }

        function toggleIndicatorTypeDropdown() {
            const indicatorTypeToggle = document.getElementById('use_indicator_type');
            const indicatorTypeWrapper = document.getElementById('indicator_type_wrapper');
            const indicatorTypeInput = document.getElementById('modal_indicator_type');

            const enabled = Boolean(indicatorTypeToggle?.checked);
            if (indicatorTypeWrapper) {
                indicatorTypeWrapper.style.display = enabled ? '' : 'none';
            }

            if (!enabled && indicatorTypeInput) {
                indicatorTypeInput.value = '';
            }
        }

        // Handle Add Indicator Form Submission
        document.addEventListener('DOMContentLoaded', function () {
            populatePapTitleDropdown();
            populatePapChildDropdowns();

            // Toggle all rows that share the same title/program/project core key.
            window.toggleRowsByCoreKey = function (coreKey) {
                if (!coreKey) return;

                const rows = Array.from(document.querySelectorAll('tbody tr.data-row'))
                    .filter(row => String(row.dataset.coreKey || '') === String(coreKey));
                if (!rows.length) return;

                const shouldShow = Array.from(rows).some(row => row.style.display === 'none');
                rows.forEach(row => {
                    row.style.display = shouldShow ? 'table-row' : 'none';
                });



                const headers = Array.from(document.querySelectorAll('tbody tr.program-header'))
                    .filter(row => String(row.dataset.coreKey || '') === String(coreKey))
                    .map(row => row.querySelector('.program-toggle-icon'))
                    .filter(Boolean);
                headers.forEach(icon => {
                    icon.classList.toggle('rotate-180', shouldShow);
                });
            };

            const papFieldIds = ['pap_title', 'pap_program', 'pap_project', 'pap_activities', 'pap_subactivities', 'pap_subsubactivities', 'pap_level_6', 'pap_level_7', 'pap_level_8'];
            let modalPrefillTimer = null;

            const titleField = document.getElementById('pap_title');
            if (titleField) {
                titleField.addEventListener('input', function () {
                    clearTimeout(modalPrefillTimer);
                    clearPapDescendantFields('pap_title');
                    modalPrefillTimer = setTimeout(() => {
                        populatePapChildDropdowns();
                        applyModalPrefillFromExistingPap();
                    }, 180);
                });

                titleField.addEventListener('change', function () {
                    clearTimeout(modalPrefillTimer);
                    clearPapDescendantFields('pap_title');
                    populatePapChildDropdowns();
                    applyModalPrefillFromExistingPap();
                });

                titleField.addEventListener('focus', function () {
                    populatePapTitleDropdown();
                });
            }

            papFieldIds.forEach(fieldId => {
                if (fieldId === 'pap_title') return;

                const field = document.getElementById(fieldId);
                if (!field) return;

                field.addEventListener('input', function () {
                    clearTimeout(modalPrefillTimer);
                    clearPapDescendantFields(fieldId);
                    modalPrefillTimer = setTimeout(() => {
                        populatePapChildDropdowns();
                        applyModalPrefillFromExistingPap();
                    }, 220);
                });

                field.addEventListener('change', function () {
                    clearTimeout(modalPrefillTimer);
                    clearPapDescendantFields(fieldId);
                    populatePapChildDropdowns();
                    applyModalPrefillFromExistingPap();
                });

                field.addEventListener('focus', function () {
                    populatePapChildDropdowns();
                });
            });

            const indicatorNameField = document.getElementById('modal_indicator_name');
            if (indicatorNameField) {
                indicatorNameField.addEventListener('input', function () {
                    clearTimeout(modalPrefillTimer);
                    modalPrefillTimer = setTimeout(() => {
                        applyModalPrefillFromExistingPap();
                    }, 180);
                });

                indicatorNameField.addEventListener('change', function () {
                    clearTimeout(modalPrefillTimer);
                    applyModalPrefillFromExistingPap();
                });
            }

            const indicatorTypeToggle = document.getElementById('use_indicator_type');
            if (indicatorTypeToggle) {
                indicatorTypeToggle.addEventListener('change', toggleIndicatorTypeDropdown);
            }

            toggleIndicatorTypeDropdown();
        });
    </script>

<script>
        // Make indicators data available to JavaScript
        const indicatorsData = {!! json_encode($indicatorsForJs ?? []) !!};
        const papPrefillData = {!! json_encode($papPrefillData ?? []) !!};
    </script>

