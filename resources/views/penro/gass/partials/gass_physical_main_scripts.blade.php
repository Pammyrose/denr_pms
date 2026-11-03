    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <script>
            document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-timed-alert').forEach(alertElement => {
                const dismissAfter = Number(alertElement.dataset.dismissAfter || 4000);

                window.setTimeout(() => {
                    const alert = bootstrap.Alert.getOrCreateInstance(alertElement);
                    alert.close();
                }, dismissAfter);
            });

            const penroNames = ['benguet', 'ifugao', 'mt.province', 'apayao', 'abra', 'kalinga', 'ro'];
            // Add all known CENRO locations here (lowercase)
            const cenroNames = [
                'bangued', 'bucay', 'lagangilang', 'licuan-baay', 'malibcong', 'manabo', 'penarrubia', 'pidigan', 'pilar', 'sal-lapadan', 'san juan', 'san quintin', 'tubo', 'villaviciosa',
                'balbalan', 'lubuagan', 'pasil', 'pinukpuk', 'rizal', 'tabuk', 'tanudan', 'tinglayan',
                'calanasan', 'conner', 'florida blanca', 'kabugao', 'luna', 'pudtol', 'santa marcela',
                'aguinaldo', 'alfonso lista', 'asipulo', 'hingyon', 'hungduan', 'kiangan', 'lagawe', 'lamut', 'mayoyao', 'tinoc',
                'atok', 'bakun', 'buguias', 'itogon', 'kabayan', 'kapangan', 'kibungan', 'la trinidad', 'mankayan', 'sablan', 'tuba', 'tublay',
                'barlig', 'bauko', 'besao', 'bontoc', 'natonin', 'paracelis', 'sabangan', 'sadanga', 'sagada', 'tadian',
                'baguio', 'city', 'cenro' // keep 'cenro' for any generic matches
            ];
            const selectAllPenroRo = document.getElementById('selectAllPenroRo');
            const selectAllCenro = document.getElementById('selectAllCenro');
            function isPenroOrRo(name) {
                name = name.toLowerCase();
                return penroNames.some(penro => name.includes(penro)) || (name.includes('penro') && !name.includes('cenro'));
            }
            function isCenro(name) {
                name = name.toLowerCase();
                // If it matches any known CENRO location or contains 'cenro', and is not a PENRO/RO
                const penroOnly = ['benguet', 'ifugao', 'mt.province', 'apayao', 'abra', 'kalinga', 'ro'];
                if (penroOnly.some(penro => name.includes(penro)) && !name.includes('cenro')) return false;
                if (name.includes('penro') && !name.includes('cenro')) return false;
                return cenroNames.some(cenro => name.includes(cenro));
            }
            function setCheckboxesByType(typeFn, checked) {
                document.querySelectorAll('.office-checkbox').forEach(cb => {
                    const label = cb.closest('.form-check').querySelector('label');
                    if (label && typeFn(label.textContent || '')) {
                        cb.checked = checked;
                    }
                });
            }
            if (selectAllPenroRo) {
                selectAllPenroRo.addEventListener('change', function () {
                    setCheckboxesByType(isPenroOrRo, this.checked);
                });
            }
            if (selectAllCenro) {
                selectAllCenro.addEventListener('change', function () {
                    setCheckboxesByType(isCenro, this.checked);
                });
            }

            // Set year to current year when modal opens
            const addIndicatorModal = document.getElementById('addIndicatorModal');
            if (addIndicatorModal) {
                addIndicatorModal.addEventListener('show.bs.modal', function () {
                    const yearInput = document.getElementById('pap_year');
                    if (yearInput) {
                        yearInput.value = String(@json((int) ($year ?? now()->year)));
                    }
                });
            }

            const excelUploadForm = document.getElementById('gassExcelUploadForm');
            const excelUploadInput = document.getElementById('gassExcelUploadInput');
            const excelPreviewModalElement = document.getElementById('gassExcelPreviewModal');
            const excelPreviewModal = excelPreviewModalElement ? new bootstrap.Modal(excelPreviewModalElement) : null;
            const excelConfirmImportBtn = document.getElementById('gassExcelConfirmImportBtn');
            const excelPreviewLoading = document.getElementById('gassExcelPreviewLoading');
            const excelPreviewContent = document.getElementById('gassExcelPreviewContent');
            const excelPreviewStats = document.getElementById('gassExcelPreviewStats');
            const excelPreviewWarnings = document.getElementById('gassExcelPreviewWarnings');
            const excelPreviewWarningPanel = document.getElementById('gassExcelPreviewWarningPanel');
            const excelPreviewRows = document.getElementById('gassExcelPreviewRows');
            const excelPreviewSubtitle = document.getElementById('gassExcelPreviewSubtitle');

            const escapePreviewHtml = value => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const setExcelPreviewLoading = (isLoading) => {
                if (excelPreviewLoading) excelPreviewLoading.classList.toggle('d-none', !isLoading);
                if (excelPreviewContent) excelPreviewContent.classList.toggle('d-none', isLoading);
            };

            const renderExcelPreview = (preview) => {
                const warningCount = Number(preview?.warning_count || 0);
                const rows = Array.isArray(preview?.rows) ? preview.rows : [];
                const warnings = Array.isArray(preview?.warnings) ? preview.warnings : [];

                if (excelPreviewSubtitle) {
                    excelPreviewSubtitle.textContent = warningCount > 0
                        ? 'Review warnings before importing.'
                        : 'No sorting warnings found in the preview.';
                }

                if (excelPreviewStats) {
                    const stats = [
                        ['Parsed Items', preview?.parsed_rows ?? 0, 'text-bg-primary'],
                        ['Office Rows', preview?.imported ?? 0, 'text-bg-success'],
                        ['Skipped', preview?.skipped ?? 0, 'text-bg-secondary'],
                        ['Warnings', warningCount, warningCount > 0 ? 'text-bg-warning' : 'text-bg-light text-dark'],
                    ];

                    excelPreviewStats.innerHTML = stats.map(([label, value, badgeClass]) => `
                        <div class="col-6 col-md-3">
                            <div class="gass-preview-stat">
                                <span class="badge ${badgeClass}">${escapePreviewHtml(value)}</span>
                                <div>${escapePreviewHtml(label)}</div>
                            </div>
                        </div>
                    `).join('');
                }

                if (excelPreviewWarnings && excelPreviewWarningPanel) {
                    excelPreviewWarningPanel.classList.toggle('d-none', warnings.length === 0);
                    excelPreviewWarnings.innerHTML = warnings.map(warning => {
                        const row = warning?.row ? `Row ${warning.row}: ` : '';
                        const levelClass = warning?.level === 'danger' ? 'text-danger fw-semibold' : '';
                        return `<li class="${levelClass}">${escapePreviewHtml(row + (warning?.message || 'Please review this row.'))}</li>`;
                    }).join('');
                }

                if (excelPreviewRows) {
                    if (rows.length === 0) {
                        excelPreviewRows.innerHTML = `
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No importable physical rows were found.</td>
                            </tr>
                        `;
                    } else {
                        excelPreviewRows.innerHTML = rows.map(row => {
                            const hierarchyParts = window.pmsExcelPreviewHierarchy(row);
                            const hierarchy = hierarchyParts.length > 0
                                ? hierarchyParts.map((item, index) => `<div class="gass-preview-level level-${Math.min(index, 4)}">${escapePreviewHtml(item)}</div>`).join('')
                                : '<span class="text-muted">N/A</span>';
                            const offices = Array.isArray(row?.offices) && row.offices.length > 0
                                ? row.offices.slice(0, 8).map(office => `<span class="badge text-bg-light border">${escapePreviewHtml(office)}</span>`).join(' ')
                                : '<span class="text-muted">No matched office</span>';
                            const moreOffices = Array.isArray(row?.offices) && row.offices.length > 8
                                ? `<span class="badge text-bg-secondary">+${row.offices.length - 8}</span>`
                                : '';
                            const carBadge = row?.has_car_total
                                ? '<span class="badge text-bg-info ms-1">CAR total</span>'
                                : '';

                            return `
                                <tr>
                                    <td class="fw-semibold">${escapePreviewHtml(row?.row || '')}</td>
                                    <td>${hierarchy}</td>
                                    <td>${escapePreviewHtml(row?.indicator || '')}</td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">${offices}${moreOffices}${carBadge}</div>
                                        <div class="small text-muted mt-1">${escapePreviewHtml(row?.office_count || 0)} office row(s)</div>
                                    </td>
                                </tr>
                            `;
                        }).join('');
                    }
                }

                if (excelConfirmImportBtn) {
                    excelConfirmImportBtn.disabled = false;
                }
            };

            if (excelUploadForm && excelUploadInput && excelPreviewModal) {
                excelUploadInput.addEventListener('change', async function () {
                    if (!this.files || this.files.length === 0) {
                        return;
                    }

                    setExcelPreviewLoading(true);
                    if (excelConfirmImportBtn) excelConfirmImportBtn.disabled = true;
                    if (excelPreviewRows) excelPreviewRows.innerHTML = '';
                    if (excelPreviewWarnings) excelPreviewWarnings.innerHTML = '';
                    excelPreviewModal.show();

                    const previewUrl = excelUploadForm.dataset.previewUrl;
                    const token = excelUploadForm.querySelector('input[name="_token"]')?.value || '';
                    const formData = new FormData(excelUploadForm);

                    try {
                        const response = await fetch(previewUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        const data = await response.json();
                        if (!response.ok || !data?.success) {
                            throw new Error(data?.message || 'Failed to preview Excel file.');
                        }

                        renderExcelPreview(data.preview || {});
                    } catch (error) {
                        console.error('GASS Excel preview error:', error);
                        if (excelPreviewSubtitle) {
                            excelPreviewSubtitle.textContent = 'Preview failed.';
                        }
                        if (excelPreviewStats) {
                            excelPreviewStats.innerHTML = '';
                        }
                        if (excelPreviewWarningPanel) {
                            excelPreviewWarningPanel.classList.remove('d-none');
                        }
                        if (excelPreviewWarnings) {
                            excelPreviewWarnings.innerHTML = `<li class="text-danger fw-semibold">${escapePreviewHtml(error?.message || 'Failed to preview Excel file.')}</li>`;
                        }
                        if (excelPreviewRows) {
                            excelPreviewRows.innerHTML = `
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Fix the Excel file or try uploading again.</td>
                                </tr>
                            `;
                        }
                        if (excelConfirmImportBtn) {
                            excelConfirmImportBtn.disabled = true;
                        }
                    } finally {
                        setExcelPreviewLoading(false);
                    }
                });

                excelPreviewModalElement?.addEventListener('hidden.bs.modal', function () {
                    if (excelConfirmImportBtn?.dataset?.submitting === '1') {
                        return;
                    }

                    excelUploadInput.value = '';
                });
            }

            if (excelConfirmImportBtn && excelUploadForm) {
                excelConfirmImportBtn.addEventListener('click', function () {
                    if (!excelUploadInput?.files || excelUploadInput.files.length === 0) {
                        showTopRightErrorAlert('Please choose an Excel file first.');
                        return;
                    }

                    this.dataset.submitting = '1';
                    this.disabled = true;
                    this.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Importing...';
                    excelUploadForm.submit();
                });
            }
        });
        const PERIODS = [
            { label: "JAN", type: "month" },
            { label: "FEB", type: "month" },
            { label: "MAR", type: "month" },
            { label: "Q1", type: "quarter" },
            { label: "APR", type: "month" },
            { label: "MAY", type: "month" },
            { label: "JUN", type: "month" },
            { label: "Q2", type: "quarter" },
            { label: "JUL", type: "month" },
            { label: "AUG", type: "month" },
            { label: "SEP", type: "month" },
            { label: "Q3", type: "quarter" },
            { label: "OCT", type: "month" },
            { label: "NOV", type: "month" },
            { label: "DEC", type: "month" },
            { label: "Q4", type: "quarter" },
            { label: "ANNUAL", type: "annual" }
        ];

        const COL_COUNT = PERIODS.length; // 17

        let targetsVisible = false;
        let financialVisible = false;
        let summaryVisible = false;
        let accompVisible = false;
        let pendingVisible = false;
        let remarksVisible = false;
        let monthInputsVisible = false;
        let totalsListenerRegistered = false;
        function toggleSummaryColumns() {
            const table = document.getElementById("performanceTable");
            const headerRow = table.querySelector("thead tr:not(.group-row)");
            const groupRow = document.getElementById("groupHeaders");
            const hadVisibleSection = summaryVisible || targetsVisible || financialVisible || accompVisible || pendingVisible;

            if (!summaryVisible) {
                summaryVisible = true;
                if (!hadVisibleSection) {
                    monthInputsVisible = false;
                }
                document.getElementById("summaryBtn").innerHTML = '<i class="fa fa-eye-slash me-1"></i> Hide Summary';
                document.getElementById("summaryBtn").classList.replace("btn-info", "btn-outline-info");

                addColumns(headerRow, groupRow, "Summary", "summary");
                addInputCells("summary");
                refreshMonthButtonState();
                refreshSummaryCards();
            } else {
                summaryVisible = false;
                document.getElementById("summaryBtn").innerHTML = '<i class="fa fa-chart-bar me-1"></i> Summary';
                document.getElementById("summaryBtn").classList.replace("btn-outline-info", "btn-info");

                removeSectionColumns(groupRow, headerRow, 'summary');
                refreshMonthButtonState();
                refreshSummaryCards();
            }
        }

        const currentYear = Number(@json($year ?? now()->year));
        const currentOfficeId = Number(@json($office_id ?? 1));
        const targetStoreUrl = @json(route('users.gass_physical.targets.store'));
        const accompStoreUrl = @json(route('users.gass_physical.accomplishments.store'));
        const deletePhysicalRowUrl = @json(route('admin.gass_physical.rows.destroy'));
        const existingTargetsByIndicator = @json($targets ?? []);
        const existingAccompByIndicator = @json($accomplishments ?? []);
        const pendingTotalsByRow = @json($pendingTotalsByRow ?? []);
        const PERIOD_KEYS = [
            "jan", "feb", "mar", "q1",
            "apr", "may", "jun", "q2",
            "jul", "aug", "sep", "q3",
            "oct", "nov", "dec", "q4",
            "annual_total"
        ];
        const parsePeriodInputValue = value => {
            const normalized = String(value ?? '')
                .replace(/,/g, '')
                .replace(/%/g, '')
                .trim();
            const number = Number(normalized);
            return Number.isFinite(number) ? number : 0;
        };
        const configurePeriodInput = input => {
            input.type = "text";
            input.inputMode = "decimal";
            input.pattern = "[0-9,.%\\s-]*";
        };
        const existingTargetCarTotalsByRow = buildCarTotalsMapFromStored(existingTargetsByIndicator);
        const existingAccompCarTotalsByRow = buildCarTotalsMapFromStored(existingAccompByIndicator);
        const existingTargetGroupTotalsByRow = buildGroupTotalsMapFromStored(existingTargetsByIndicator);
        const existingAccompGroupTotalsByRow = buildGroupTotalsMapFromStored(existingAccompByIndicator);

        let saveSuccessAlertTimeout = null;
        let saveErrorAlertTimeout = null;

        function showTopRightSuccessAlert(message = 'Data saved successfully.', options = {}) {
            const {
                reload = false,
                duration = 1800,
            } = options;

            const saveSuccessAlertWrapper = document.getElementById('saveSuccessAlertWrapper');
            const saveSuccessAlert = document.getElementById('saveSuccessAlert');
            const saveSuccessMessage = document.getElementById('saveSuccessMessage');

            if (!saveSuccessAlertWrapper || !saveSuccessAlert) {
                console.warn(message);
                if (reload) location.reload();
                return;
            }

            if (saveSuccessMessage) {
                saveSuccessMessage.textContent = message;
            }

            saveSuccessAlertWrapper.classList.remove('d-none');

            if (saveSuccessAlertTimeout) {
                clearTimeout(saveSuccessAlertTimeout);
            }

            const closeButton = saveSuccessAlert.querySelector('.btn-close');
            if (closeButton) {
                closeButton.onclick = function () {
                    if (saveSuccessAlertTimeout) {
                        clearTimeout(saveSuccessAlertTimeout);
                    }
                    saveSuccessAlertWrapper.classList.add('d-none');
                    if (reload) location.reload();
                };
            }

            saveSuccessAlertTimeout = setTimeout(() => {
                saveSuccessAlertWrapper.classList.add('d-none');
                if (reload) location.reload();
            }, duration);
        }

        function showTopRightErrorAlert(message = 'An error occurred.', options = {}) {
            const {
                duration = 2200,
            } = options;

            const saveErrorAlertWrapper = document.getElementById('saveErrorAlertWrapper');
            const saveErrorAlert = document.getElementById('saveErrorAlert');
            const saveErrorMessage = document.getElementById('saveErrorMessage');

            if (!saveErrorAlertWrapper || !saveErrorAlert) {
                console.warn(message);
                return;
            }

            if (saveErrorMessage) {
                saveErrorMessage.textContent = message;
            }

            saveErrorAlertWrapper.classList.remove('d-none');

            if (saveErrorAlertTimeout) {
                clearTimeout(saveErrorAlertTimeout);
            }

            const closeButton = saveErrorAlert.querySelector('.btn-close');
            if (closeButton) {
                closeButton.onclick = function () {
                    if (saveErrorAlertTimeout) {
                        clearTimeout(saveErrorAlertTimeout);
                    }
                    saveErrorAlertWrapper.classList.add('d-none');
                };
            }

            saveErrorAlertTimeout = setTimeout(() => {
                saveErrorAlertWrapper.classList.add('d-none');
            }, duration);
        }

        const MONTH_COLS = [0, 1, 2, 4, 5, 6, 8, 9, 10, 12, 13, 14];

        function getCurrentMonthPeriodIndex() {
            return MONTH_COLS[new Date().getMonth()] ?? 0;
        }

        function buildMonthlyMapFromStored(sourceData) {
            const result = new Map();

            Object.entries(sourceData || {}).forEach(([programId, indicators]) => {
                Object.entries(indicators || {}).forEach(([indicatorId, offices]) => {
                    Object.entries(offices || {}).forEach(([officeId, officeData]) => {
                        MONTH_COLS.forEach(colIndex => {
                            const monthKey = PERIOD_KEYS[colIndex];
                            if (!monthKey) return;

                            const value = Number(officeData?.[monthKey] ?? 0);
                            const safeValue = Number.isFinite(value) ? value : 0;
                            const key = `${programId}|${indicatorId}|${officeId}|${monthKey}`;

                            result.set(key, safeValue);
                        });
                    });
                });
            });

            return result;
        }

        function buildCarTotalsMapFromStored(sourceData) {
            const result = new Map();

            Object.entries(sourceData || {}).forEach(([programId, indicators]) => {
                Object.entries(indicators || {}).forEach(([indicatorId, offices]) => {
                    const officeRecords = Object.values(offices || {});
                    const sourceRecord = officeRecords.find(record => record && typeof record === 'object' && record.car_totals);
                    const carTotals = sourceRecord?.car_totals || {};
                    if (Object.keys(carTotals).length === 0) return;

                    PERIOD_KEYS.forEach(periodKey => {
                        const value = Number(carTotals?.[periodKey] ?? 0);
                        result.set(`${programId}|${indicatorId}|${periodKey}`, Number.isFinite(value) ? value : 0);
                    });
                });
            });

            return result;
        }

        function buildGroupTotalsMapFromStored(sourceData) {
            const result = new Map();

            Object.entries(sourceData || {}).forEach(([programId, indicators]) => {
                Object.entries(indicators || {}).forEach(([indicatorId, offices]) => {
                    const officeRecords = Object.values(offices || {});
                    const sourceRecord = officeRecords.find(record => record && typeof record === 'object' && record.group_totals);
                    const groupTotals = sourceRecord?.group_totals || {};

                    Object.entries(groupTotals || {}).forEach(([rawGroupKey, totals]) => {
                        const normalizedGroupKey = normalizeStoredGroupKey(rawGroupKey);

                        PERIOD_KEYS.forEach(periodKey => {
                            const value = Number(totals?.[periodKey] ?? 0);
                            result.set(
                                `${programId}|${indicatorId}|${normalizedGroupKey}|${periodKey}`,
                                Number.isFinite(value) ? value : 0
                            );
                        });
                    });
                });
            });

            return result;
        }

        function normalizeStoredGroupKey(groupKey) {
            const match = String(groupKey || '').match(/^group-(\d+)$/);
            if (!match) return String(groupKey || '');

            const storedIndex = Number(match[1]);
            return Number.isInteger(storedIndex) && storedIndex > 0
                ? `group-${storedIndex - 1}`
                : `group-${storedIndex}`;
        }

        function applyMonthlyMapFromInputs(sectionType, targetMap) {
            const selector = `.month-box[data-section="${sectionType}"]`;

            Array.from(document.querySelectorAll(selector)).forEach(input => {
                if (input.dataset.carTotal === '1') return;
                if (input.dataset.groupTotal === '1') return;

                const colIndex = Number(input.dataset.col);
                if (!MONTH_COLS.includes(colIndex)) return;

                const monthKey = PERIOD_KEYS[colIndex];
                const officeId = String(input.dataset.officeId || '').trim();
                const row = input.closest('tr[data-indicator-id]');
                const programId = String(row?.dataset?.rowId || '').trim();
                const indicatorId = String(row?.dataset?.indicatorId || '').trim();

                if (!programId || !indicatorId || !officeId || !monthKey) return;

                const value = parsePeriodInputValue(input.value);
                const safeValue = Number.isFinite(value) ? value : 0;
                const key = `${programId}|${indicatorId}|${officeId}|${monthKey}`;

                targetMap.set(key, safeValue);
            });
        }
        function refreshSummaryCards() {
            refreshSummaryInputs();
            refreshPendingInputs();
        }

        function refreshSummaryInputs() {
            if (!summaryVisible) return;

            document.querySelectorAll('tbody tr[data-row-id]').forEach(row => {
                updateSummaryInputsForRow(row);
            });
        }

        function updateSummaryInputsForRow(row) {
            if (!row) return;

            const summaryInputs = row.querySelectorAll('.month-box.summary-box');
            if (summaryInputs.length === 0) return;

            const programId = Number(row.dataset.rowId || 0);
            const indicatorId = Number(row.dataset.indicatorId || 0);
            const allInputs = row.querySelectorAll('.month-box');

            summaryInputs.forEach(input => {
                const sectionType = String(input.dataset.summarySection || '').trim();
                const periodIndex = Number(input.dataset.summaryPeriodIndex);
                const periodKey = String(input.dataset.summaryPeriodKey || '').trim();
                const officeId = String(input.dataset.officeId || '').trim();
                if (!sectionType || !periodKey) return;

                let value = Number.NaN;

                if (Number.isInteger(periodIndex)) {
                    const sourceInput = getSectionColInput(allInputs, sectionType, periodIndex, officeId || null);
                    if (sourceInput) {
                        const parsed = parsePeriodInputValue(sourceInput.value);
                        if (Number.isFinite(parsed)) {
                            value = parsed;
                        }
                    }
                }

                if (!Number.isFinite(value)) {
                    value = getStoredValueForSummary(sectionType, programId, indicatorId, officeId, periodKey);
                }

                input.value = Number.isFinite(value) ? value : 0;
            });
        }

        function getStoredValueForSummary(sectionType, programId, indicatorId, officeId, periodKey) {
            if (!programId || !indicatorId || !periodKey) return 0;

            const source = sectionType === 'target'
                ? existingTargetsByIndicator
                : existingAccompByIndicator;

            const programEntry = source[String(programId)] || {};
            const indicatorEntry = programEntry[String(indicatorId)] || {};
            const officeKey = String(officeId || '').trim();
            const officeEntry = officeKey ? (indicatorEntry[officeKey] || null) : null;
            const rawValue = officeEntry && Object.prototype.hasOwnProperty.call(officeEntry, periodKey)
                ? officeEntry[periodKey]
                : 0;
            const value = Number(rawValue);

            return Number.isFinite(value) ? value : 0;
        }

        function getSourcePeriodValue(sourceData, programId, indicatorId, officeId, periodKey) {
            const programEntry = sourceData?.[String(programId)] || {};
            const indicatorEntry = programEntry?.[String(indicatorId)] || {};
            const officeEntry = indicatorEntry?.[String(officeId)] || null;
            const value = Number(officeEntry?.[periodKey] ?? 0);

            return Number.isFinite(value) ? value : 0;
        }

        function getLivePeriodValue(row, sectionType, officeId, colIndex) {
            const periodKey = PERIOD_KEYS[colIndex] || null;
            if (!row || !sectionType || !officeId || !periodKey) return 0;

            const liveInput = row.querySelector(`.month-box[data-section="${sectionType}"][data-office-id="${officeId}"][data-col="${colIndex}"]`);
            if (liveInput) {
                const liveValue = Number(liveInput.value);
                return Number.isFinite(liveValue) ? liveValue : 0;
            }

            const sourceData = sectionType === 'target' ? existingTargetsByIndicator : existingAccompByIndicator;
            return getSourcePeriodValue(sourceData, row.dataset.rowId, row.dataset.indicatorId, officeId, periodKey);
        }

        function getStoredCurrentMonthSectionTotal(row, sectionType, colIndex) {
            const periodKey = PERIOD_KEYS[colIndex] || null;
            if (!row || !sectionType || !periodKey) return 0;

            const rowTotalKey = `${row.dataset.rowId || ''}|${row.dataset.indicatorId || ''}|${periodKey}`;
            const rowTotalMap = sectionType === 'target'
                ? (typeof existingTargetCarTotalsByRow !== 'undefined' ? existingTargetCarTotalsByRow : null)
                : (typeof existingAccompCarTotalsByRow !== 'undefined' ? existingAccompCarTotalsByRow : null);
            const storedRowTotal = rowTotalMap?.get(rowTotalKey);
            if (Number.isFinite(Number(storedRowTotal))) {
                return Number(storedRowTotal);
            }

            const sourceData = sectionType === 'target' ? existingTargetsByIndicator : existingAccompByIndicator;
            const programEntry = sourceData?.[String(row.dataset.rowId || '')] || {};
            const indicatorEntry = programEntry?.[String(row.dataset.indicatorId || '')] || {};
            const officeEntries = Object.values(indicatorEntry || {});

            for (const entry of officeEntries) {
                const carValue = Number(entry?.car_totals?.[periodKey]);
                if (Number.isFinite(carValue)) {
                    return carValue;
                }
            }

            return officeEntries.reduce((sum, entry) => {
                const value = Number(entry?.[periodKey] ?? 0);
                return sum + (Number.isFinite(value) ? value : 0);
            }, 0);
        }

        function getCurrentMonthSectionTotal(row, sectionType, colIndex) {
            if (!row || !sectionType || !Number.isInteger(colIndex)) return 0;

            const carInput = row.querySelector(`.month-box[data-section="${sectionType}"][data-col="${colIndex}"][data-car-total="1"]`);
            if (carInput) {
                const carValue = parsePeriodInputValue(carInput.value);
                return Number.isFinite(carValue) ? carValue : 0;
            }

            const liveInputs = Array.from(row.querySelectorAll(`.month-box[data-section="${sectionType}"][data-col="${colIndex}"]`))
                .filter(input => input.dataset.carTotal !== '1' && input.dataset.groupTotal !== '1');

            if (liveInputs.length > 0) {
                return liveInputs.reduce((sum, input) => {
                    const value = parsePeriodInputValue(input.value);
                    return sum + (Number.isFinite(value) ? value : 0);
                }, 0);
            }

            return getStoredCurrentMonthSectionTotal(row, sectionType, colIndex);
        }

        function getPendingSourceSectionTotal(row, sourceSection, colIndex) {
            if (!row || !sourceSection || !Number.isInteger(colIndex)) return 0;

            const pendingInputs = Array.from(row.querySelectorAll(`.month-box[data-section="pending"][data-source-section="${sourceSection}"][data-col="${colIndex}"]`));
            const officeInputs = pendingInputs.filter(input => input.dataset.carTotal !== '1' && input.dataset.groupTotal !== '1');
            const groupInputs = pendingInputs.filter(input => input.dataset.groupTotal === '1');
            const groupedOfficeIds = new Set();

            const groupTotal = groupInputs.reduce((sum, groupInput) => {
                const groupOfficeIds = String(groupInput.dataset.groupOfficeIds || '')
                    .split(',')
                    .map(value => value.trim())
                    .filter(Boolean);

                groupOfficeIds.forEach(officeId => groupedOfficeIds.add(officeId));

                return sum + groupOfficeIds.reduce((groupSum, officeId) => {
                    const value = getLivePeriodValue(row, sourceSection, officeId, colIndex);
                    return groupSum + (Number.isFinite(value) ? value : 0);
                }, 0);
            }, 0);

            const directOfficeTotal = officeInputs
                .filter(input => !groupedOfficeIds.has(String(input.dataset.officeId || '')))
                .reduce((sum, input) => {
                    const officeId = String(input.dataset.officeId || '').trim();
                    const value = officeId ? getLivePeriodValue(row, sourceSection, officeId, colIndex) : 0;
                    return sum + (Number.isFinite(value) ? value : 0);
                }, 0);

            return directOfficeTotal + groupTotal;
        }

        function getCurrentMonthPendingTotal(row, colIndex) {
            if (!row || !Number.isInteger(colIndex)) return 0;

            const targetTotal = getPendingComparisonTotal(row, 'target', colIndex);
            const accompTotal = getPendingComparisonTotal(row, 'accomp', colIndex);
            const computedPending = targetTotal > 0 && targetTotal > accompTotal
                ? targetTotal - accompTotal
                : 0;

            if (computedPending > 0) {
                return computedPending;
            }

            return getStoredPendingTotal(row, colIndex);
        }

        function getStoredPendingTotal(row, colIndex) {
            const periodKey = PERIOD_KEYS[colIndex] || null;
            if (!row || !periodKey) return 0;

            const value = Number(
                pendingTotalsByRow?.[String(row.dataset.rowId || '')]?.[String(row.dataset.indicatorId || '')]?.[periodKey] ?? 0
            );

            return Number.isFinite(value) ? value : 0;
        }

        function getPendingComparisonTotal(row, sourceSection, colIndex) {
            const pendingCarInput = row?.querySelector(`.month-box[data-section="pending"][data-source-section="${sourceSection}"][data-col="${colIndex}"][data-car-total="1"]`);
            if (pendingCarInput) {
                const value = parsePeriodInputValue(pendingCarInput.value);
                return Number.isFinite(value) ? value : 0;
            }

            return getPendingSourceSectionTotal(row, sourceSection, colIndex);
        }

        function rowHasCurrentMonthPending(row) {
            if (!row) return false;

            const colIndex = getCurrentMonthPeriodIndex();

            const targetTotal = getPendingComparisonTotal(row, 'target', colIndex);
            const accompTotal = getPendingComparisonTotal(row, 'accomp', colIndex);


            if (Math.abs(targetTotal - accompTotal) < 0.000001) {
                return false;
            }

            return targetTotal > accompTotal;
        }

        function applyPendingRowFilter() {
            resetPendingPapRowspans();

            if (!pendingVisible) {
                document.querySelectorAll('tbody tr[data-row-id], tbody tr.program-header, tbody tr.sub-activity-label-row').forEach(row => {
                    row.style.display = '';
                });
                return;
            }

            const processedRows = new Set();
            const blocks = collectPendingPapBlocks();
            blocks.forEach(block => {
                const visibleRows = block.rows.filter(rowHasCurrentMonthPending);

                block.rows.forEach(row => {
                    processedRows.add(row);
                    row.style.display = visibleRows.includes(row) ? '' : 'none';
                });

                if (visibleRows.length > 0 && block.papCell) {
                    visibleRows[0].insertBefore(block.papCell, visibleRows[0].firstChild);
                    block.papCell.rowSpan = visibleRows.length;
                }
            });

            document.querySelectorAll('tbody tr[data-row-id]').forEach(row => {
                if (processedRows.has(row)) return;
                row.style.display = rowHasCurrentMonthPending(row) ? '' : 'none';
            });

            const hasVisiblePendingRows = Array.from(document.querySelectorAll('tbody tr[data-row-id]'))
                .some(row => row.style.display !== 'none');
            if (!hasVisiblePendingRows) {
                resetPendingPapRowspans();
                document.querySelectorAll('tbody tr[data-row-id], tbody tr.program-header, tbody tr.sub-activity-label-row').forEach(row => {
                    row.style.display = '';
                });
                return;
            }

            document.querySelectorAll('tbody tr.program-header').forEach(headerRow => {
                let hasVisiblePendingRow = false;
                let cursor = headerRow.nextElementSibling;
                while (cursor && !cursor.classList.contains('program-header')) {
                    if (cursor.matches('tr[data-row-id]') && cursor.style.display !== 'none') {
                        hasVisiblePendingRow = true;
                        break;
                    }
                    cursor = cursor.nextElementSibling;
                }

                headerRow.style.display = hasVisiblePendingRow ? '' : 'none';
            });

            document.querySelectorAll('tbody tr.sub-activity-label-row').forEach(labelRow => {
                let hasVisiblePendingRow = false;
                let cursor = labelRow.nextElementSibling;
                while (
                    cursor
                    && !cursor.classList.contains('program-header')
                    && !cursor.classList.contains('sub-activity-label-row')
                ) {
                    if (cursor.matches('tr[data-row-id]') && cursor.style.display !== 'none') {
                        hasVisiblePendingRow = true;
                        break;
                    }
                    cursor = cursor.nextElementSibling;
                }

                labelRow.style.display = hasVisiblePendingRow ? '' : 'none';
            });
        }

        let pendingPapBlockCounter = 0;

        function ensurePendingPapRowspanState() {
            document.querySelectorAll('tbody tr.first-indicator-row[data-row-id]').forEach(row => {
                if (row.dataset.pendingPapBlockId) return;

                const firstCell = row.firstElementChild;
                if (!firstCell || !firstCell.hasAttribute('rowspan')) return;

                const blockId = `pending-pap-${++pendingPapBlockCounter}`;
                row.dataset.pendingPapBlockId = blockId;
                firstCell.dataset.pendingPapCell = '1';
                firstCell.dataset.pendingPapBlockId = blockId;
                firstCell.dataset.originalRowspan = firstCell.getAttribute('rowspan') || '1';
            });
        }

        function resetPendingPapRowspans() {
            ensurePendingPapRowspanState();

            document.querySelectorAll('td[data-pending-pap-cell="1"]').forEach(cell => {
                const blockId = cell.dataset.pendingPapBlockId || '';
                const originalRow = blockId
                    ? document.querySelector(`tbody tr[data-pending-pap-block-id="${blockId}"]`)
                    : null;

                if (originalRow && originalRow.firstElementChild !== cell) {
                    originalRow.insertBefore(cell, originalRow.firstChild);
                }

                cell.rowSpan = Number(cell.dataset.originalRowspan || 1) || 1;
            });
        }

        function collectPendingPapBlocks() {
            ensurePendingPapRowspanState();

            const blocks = [];
            let currentBlock = null;

            Array.from(document.querySelectorAll('tbody tr')).forEach(row => {
                if (!row.matches('tr[data-row-id]')) {
                    if (row.classList.contains('program-header') || row.classList.contains('sub-activity-label-row')) {
                        currentBlock = null;
                    }
                    return;
                }

                if (row.dataset.pendingPapBlockId) {
                    currentBlock = {
                        id: row.dataset.pendingPapBlockId,
                        papCell: document.querySelector(`td[data-pending-pap-cell="1"][data-pending-pap-block-id="${row.dataset.pendingPapBlockId}"]`),
                        rows: [],
                    };
                    blocks.push(currentBlock);
                }

                if (currentBlock) {
                    currentBlock.rows.push(row);
                }
            });

            return blocks;
        }

        function refreshPendingInputs() {
            if (!pendingVisible) return;

            document.querySelectorAll('tbody tr[data-row-id]').forEach(row => {
                const pendingInputs = Array.from(row.querySelectorAll('.month-box[data-section="pending"]'))
                    .filter(input => input.dataset.carTotal !== '1' && input.dataset.groupTotal !== '1');

                pendingInputs.forEach(input => {
                    const officeId = String(input.dataset.officeId || '').trim();
                    const colIndex = Number(input.dataset.col);
                    if (!officeId || !Number.isInteger(colIndex)) return;

                    const pendingTotal = getCurrentMonthPendingTotal(row, colIndex);
                    if (pendingTotal <= 0) {
                        input.value = 0;
                        return;
                    }

                    const sourceSection = String(input.dataset.sourceSection || input.dataset.pendingKind || 'accomp');
                    const value = getLivePeriodValue(row, sourceSection, officeId, colIndex);
                    input.value = Number.isFinite(value) ? value : 0;
                });

                const colIndex = getCurrentMonthPeriodIndex();
                const pendingTotal = getCurrentMonthPendingTotal(row, colIndex);
                ['target', 'accomp'].forEach(sourceSection => {
const pendingCarInput = row.querySelector(`.month-box[data-section="pending"][data-source-section="${sourceSection}"][data-col="${colIndex}"][data-car-total="1"]`);
                    if (pendingCarInput) {
                        pendingCarInput.value = pendingTotal > 0
                            ? getPendingSourceSectionTotal(row, sourceSection, colIndex)
                            : 0;
                    }

                    row.querySelectorAll(`.month-box[data-section="pending"][data-source-section="${sourceSection}"][data-col="${colIndex}"][data-group-total="1"]`).forEach(groupInput => {
                        if (pendingTotal <= 0) {
                            groupInput.value = 0;
                            return;
                        }

                        const groupOfficeIds = String(groupInput.dataset.groupOfficeIds || '')
                            .split(',')
                            .map(value => value.trim())
                            .filter(Boolean);
                        groupInput.value = groupOfficeIds.reduce((sum, groupOfficeId) => {
                            const value = getLivePeriodValue(row, sourceSection, groupOfficeId, colIndex);
                            return sum + (Number.isFinite(value) ? value : 0);
                        }, 0);
                    });
                });
            });

        }

        function applyMonthInputVisibility() {
            document.querySelectorAll('th[data-period-type="month"]').forEach(cell => {
                // Always show month columns for summary section
                if (cell.dataset.dynamicSection === 'summary') {
                    cell.style.display = '';
                } else {
                    cell.style.display = monthInputsVisible ? '' : 'none';
                }
            });

            document.querySelectorAll('td[data-period-type="month"]').forEach(cell => {
                if (cell.dataset.dynamicSection === 'summary') {
                    cell.style.display = '';
                } else {
                    cell.style.display = monthInputsVisible ? '' : 'none';
                }
            });

            const pendingCurrentMonthIndex = getCurrentMonthPeriodIndex();
            document.querySelectorAll('th[data-dynamic-section="pending"], td[data-dynamic-section="pending"]').forEach(cell => {
                cell.style.display = Number(cell.dataset.periodIndex) === pendingCurrentMonthIndex ? '' : 'none';
            });

            refreshGroupHeaderColspans();
        }

        function refreshGroupHeaderColspans() {
            const groupRow = document.getElementById('groupHeaders');
            if (!groupRow) return;

            ['target', 'financial', 'accomp', 'pending', 'remarks'].forEach(sectionType => {
                const groupCell = groupRow.querySelector(`.group-${sectionType}`);
                if (!groupCell) return;

                const visibleCount = Array.from(
                    document.querySelectorAll(`thead tr:not(.group-row) th[data-dynamic-section="${sectionType}"]`)
                ).filter(cell => cell.style.display !== 'none').length;

                groupCell.colSpan = Math.max(visibleCount, 1);
            });
        }

        function refreshMonthButtonState() {
            const monthBtn = document.getElementById('monthBtn');
            if (!monthBtn) return;

            const canToggleMonths = targetsVisible || financialVisible || accompVisible || pendingVisible;
            if (!canToggleMonths) {
                monthInputsVisible = false;
                monthBtn.style.display = '';
            } else {
                monthBtn.style.display = '';
            }

            monthBtn.disabled = !canToggleMonths;
            monthBtn.innerHTML = monthInputsVisible
                ? '<i class="fa fa-eye-slash me-1"></i> Hide Months'
                : '<i class="fa fa-calendar-days me-1"></i> Show Months';

            applyMonthInputVisibility();
        }

        function toggleMonthInputs() {
            if (!targetsVisible && !financialVisible && !accompVisible && !pendingVisible) return;
            monthInputsVisible = !monthInputsVisible;
            refreshMonthButtonState();
        }

        function toggleTargetColumns() {
            const table = document.getElementById("performanceTable");
            const headerRow = table.querySelector("thead tr:not(.group-row)");
            const groupRow = document.getElementById("groupHeaders");
            const hadVisibleSection = targetsVisible || financialVisible || accompVisible || pendingVisible;

            if (!targetsVisible) {
                targetsVisible = true;
                if (!hadVisibleSection) {
                    monthInputsVisible = false;
                }
                document.getElementById("targetBtn").innerHTML = '<i class="fa fa-eye-slash me-1"></i> Hide Targets';
                document.getElementById("targetBtn").classList.replace("btn-primary", "btn-outline-primary");

                addColumns(headerRow, groupRow, "Physical Target", "target");
                addInputCells("target");
                refreshMonthButtonState();
                refreshSummaryCards();
            } else {
                targetsVisible = false;
                document.getElementById("targetBtn").innerHTML = '<i class="fa fa-plus me-1"></i> Targets';
                document.getElementById("targetBtn").classList.replace("btn-outline-primary", "btn-primary");

                removeSectionColumns(groupRow, headerRow, 'target');
                refreshMonthButtonState();
                refreshSummaryCards();
            }
        }

        function toggleFinancialColumns() {
            const table = document.getElementById("performanceTable");
            const headerRow = table.querySelector("thead tr:not(.group-row)");
            const groupRow = document.getElementById("groupHeaders");
            const hadVisibleSection = targetsVisible || financialVisible || accompVisible || pendingVisible;

            if (!financialVisible) {
                financialVisible = true;
                if (!hadVisibleSection) {
                    monthInputsVisible = false;
                }
                document.getElementById("financialBtn").innerHTML = '<i class="fa fa-eye-slash me-1"></i> Hide Financial';

                addColumns(headerRow, groupRow, "Financial", "financial");
                addInputCells("financial");
                refreshMonthButtonState();
                refreshSummaryCards();
            } else {
                financialVisible = false;
                document.getElementById("financialBtn").innerHTML = '<i class="fa fa-peso-sign me-1"></i> Financial';

                removeSectionColumns(groupRow, headerRow, 'financial');
                refreshMonthButtonState();
                refreshSummaryCards();
            }
        }
        function toggleAccompColumns() {
            const table = document.getElementById("performanceTable");
            const headerRow = table.querySelector("thead tr:not(.group-row)");
            const groupRow = document.getElementById("groupHeaders");
            const hadVisibleSection = targetsVisible || financialVisible || accompVisible || pendingVisible;

            if (!accompVisible) {
                accompVisible = true;
                if (!hadVisibleSection) {
                    monthInputsVisible = false;
                }
                document.getElementById("accompBtn").innerHTML = '<i class="fa fa-eye-slash me-1"></i> Hide Accomplishments';
                document.getElementById("accompBtn").classList.replace("btn-success", "btn-outline-success");

                addColumns(headerRow, groupRow, "Physical Accomplishment", "accomp");
                addInputCells("accomp");
                refreshMonthButtonState();
                refreshSummaryCards();
            } else {
                accompVisible = false;
                document.getElementById("accompBtn").innerHTML = '<i class="fa fa-plus me-1"></i> Accomplishments';
                document.getElementById("accompBtn").classList.replace("btn-outline-success", "btn-success");

                removeSectionColumns(groupRow, headerRow, 'accomp');
                refreshMonthButtonState();
                refreshSummaryCards();
            }
        }

        function togglePendingColumns() {
            const table = document.getElementById("performanceTable");
            const headerRow = table.querySelector("thead tr:not(.group-row)");
            const groupRow = document.getElementById("groupHeaders");
            const hadVisibleSection = targetsVisible || financialVisible || accompVisible || pendingVisible;

            if (!pendingVisible) {
                pendingVisible = true;
                if (!hadVisibleSection) {
                    monthInputsVisible = false;
                }
                document.getElementById("pendingBtn").innerHTML = '<i class="fa fa-eye-slash me-1"></i> Hide Pending';

                addColumns(headerRow, groupRow, "Pending", "pending");
                addInputCells("pending");
                applyPendingRowFilter();
                refreshMonthButtonState();
                refreshSummaryCards();
            } else {
                pendingVisible = false;
                document.getElementById("pendingBtn").innerHTML = '<i class="fa fa-hourglass-half me-1"></i> Pending';

                removeSectionColumns(groupRow, headerRow, 'pending');
                applyPendingRowFilter();
                refreshMonthButtonState();
                refreshSummaryCards();
            }
        }

        function toggleRemarksColumn() {
            const table = document.getElementById('performanceTable');
            const headerRow = table.querySelector('thead tr:not(.group-row)');
            const groupRow = document.getElementById('groupHeaders');

            if (!remarksVisible) {
                remarksVisible = true;
                document.getElementById('remarksBtn').innerHTML = '<i class="fa fa-eye-slash me-1"></i> Hide Remarks';
                document.getElementById('remarksBtn').classList.replace('btn-warning', 'btn-outline-warning');

                addRemarksColumn(headerRow, groupRow);
                addRemarksCells();
                refreshSummaryCards();
            } else {
                remarksVisible = false;
                document.getElementById('remarksBtn').innerHTML = '<i class="fa fa-plus me-1"></i> Remarks';
                document.getElementById('remarksBtn').classList.replace('btn-outline-warning', 'btn-warning');

                removeSectionColumns(groupRow, headerRow, 'remarks');
                refreshSummaryCards();
            }
        }
    </script>
