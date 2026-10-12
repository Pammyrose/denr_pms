    <script>
        function showNextPapLevel(level) {
            const container = document.getElementById(`pap_level_${level}_container`);
            const button = document.getElementById(`add_level_${level}_btn`);
            
            if (container) {
                container.style.display = '';
                if (button) {
                    button.style.display = 'none'; // Hide the button that was clicked
                }
            }
        }

        // Function to reset PAP levels (hide all dynamic levels)
        function resetPapLevels() {
            for (let level = 6; level <= 8; level++) {
                const container = document.getElementById(`pap_level_${level}_container`);
                const input = document.getElementById(`pap_level_${level}`);
                const button = document.getElementById(`add_level_${level}_btn`);
                
                if (container) container.style.display = 'none';
                if (input) input.value = '';
                if (button) button.style.display = '';
            }
        }

        document.getElementById('addIndicatorForm')?.addEventListener('submit', async function (e) {
            e.preventDefault();

            const form = this;
            const token = document.querySelector('input[name="_token"]')?.value || '';
            const actionUrl = form.getAttribute('action');
            const submitButton = form.querySelector('button[type="submit"]');
            if (PMSButtonLoading.isBusy(submitButton)) return;
            const selectedOffices = Array.from(document.querySelectorAll('.office-checkbox:checked'))
                .map(checkbox => checkbox.value);

            const papTitle = document.getElementById('pap_title')?.value?.trim() || '';
            const papProgram = document.getElementById('pap_program')?.value?.trim() || '';
            const papProject = document.getElementById('pap_project')?.value?.trim() || '';
            const papActivities = document.getElementById('pap_activities')?.value?.trim() || '';
            const papSubactivities = document.getElementById('pap_subactivities')?.value?.trim() || '';
            const papSubSubactivities = document.getElementById('pap_subsubactivities')?.value?.trim() || '';
            const papLevel6 = document.getElementById('pap_level_6')?.value?.trim() || '';
            const papLevel7 = document.getElementById('pap_level_7')?.value?.trim() || '';
            const papLevel8 = document.getElementById('pap_level_8')?.value?.trim() || '';
            const papYear = document.getElementById('pap_year')?.value?.trim() || '';
            const indicatorId = String(document.getElementById('indicator_id')?.value || '').trim();

            const indicatorName = document.getElementById('modal_indicator_name').value.trim();
            const indicatorTypeToggle = document.getElementById('use_indicator_type');
            const indicatorTypeId = (indicatorTypeToggle?.checked ? document.getElementById('modal_indicator_type').value : '').trim();

            if (!papTitle) {
                showTopRightErrorAlert('Please input title.');
                return;
            }

            if (!indicatorName) {
                showTopRightErrorAlert('Please input a performance indicator.');
                return;
            }

            if (selectedOffices.length === 0) {
                showTopRightErrorAlert('Please select at least one office.');
                return;
            }

            PMSButtonLoading.start(submitButton);

            try {
                const matchedPap = findMatchingPapFromModal();
                const matchedIndicator = getSelectedIndicatorFromPapMatch(matchedPap);
                const indicatorIdInput = document.getElementById('indicator_id');
                const selectedIndicatorRowId = String(matchedIndicator?.row_id || indicatorIdInput?.dataset?.rowId || '').trim();
                let programId = matchedPap?.id ? String(matchedPap.id) : '';
                let rowId = selectedIndicatorRowId || (matchedPap?.row_id ? String(matchedPap.row_id) : '');

                // Always create/update PAP (including year) when PAP fields are filled
                if (papTitle) {
                    const papFormData = new FormData();
                    papFormData.append('_token', token);
                    papFormData.append('title', papTitle);
                    papFormData.append('program', papProgram);
                    papFormData.append('project', papProject);
                    papFormData.append('activities', papActivities);
                    papFormData.append('subactivities', papSubactivities);
                    papFormData.append('subsubactivities', papSubSubactivities);
                    papFormData.append('level_6', papLevel6);
                    papFormData.append('level_7', papLevel7);
                    papFormData.append('level_8', papLevel8);
                    papFormData.append('year', papYear || String(currentYear || ''));

                    const papUpdateUrl = String(form.dataset.papUpdateUrl || '');
                    const isEditingPap = form.dataset.editMode === 'true' && papUpdateUrl !== '';
                    if (isEditingPap) papFormData.append('_method', 'PATCH');
                    const papResponse = await fetch(isEditingPap ? papUpdateUrl : @json(route('admin.pa_physical.pap.store')), {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                        },
                        body: papFormData,
                    });

                    const papData = await papResponse.json();
                    if (!papResponse.ok || !papData?.pap?.id) {
                        const firstError = papData?.errors ? Object.values(papData.errors)[0]?.[0] : null;
                        throw new Error(firstError || papData?.message || 'Failed to save PAP.');
                    }

                    programId = String(papData.pap.id);
                    rowId = String(papData?.pap?.row_id || papData?.pap?.id || papData.pap.id);
                }

                const formData = new FormData();
                formData.append('_token', token);
                if (form.dataset.editMode === 'true') formData.append('update_in_place', '1');
                formData.append('program_id', programId);
                if (rowId) {
                    formData.append('row_id', rowId);
                }
                formData.append('indicator_name', indicatorName);
                if (indicatorTypeId) {
                    formData.append('indicator_type_id', indicatorTypeId);
                }
                selectedOffices.forEach(officeId => formData.append('office_id[]', officeId));

                const updateRouteTemplate = form.dataset.updateRouteTemplate || '';
                const shouldUpdateExistingIndicator = Boolean(
                    indicatorId
                    && programId
                    && rowId
                    && matchedIndicator
                    && String(matchedIndicator.id || '') === indicatorId
                );

                let indicatorResponse;
                if (shouldUpdateExistingIndicator && updateRouteTemplate) {
                    const updateUrl = updateRouteTemplate.replace(':id', indicatorId);
                    formData.append('_method', 'PATCH');

                    indicatorResponse = await fetch(updateUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });
                } else {
                    indicatorResponse = await fetch(actionUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: formData
                    });
                }

                if (!indicatorResponse.ok) {
                    const text = await indicatorResponse.text();
                    throw new Error(`HTTP ${indicatorResponse.status}: ${text}`);
                }

                const indicatorData = await indicatorResponse.json();
                if (!indicatorData.success) {
                    throw new Error('Failed to save indicator.');
                }

                const modal = bootstrap.Modal.getInstance(document.getElementById('addIndicatorModal'));
                if (modal) modal.hide();
                form.reset();
                resetPapLevels(); // Reset dynamic PAP levels
                const indicatorIdField = document.getElementById('indicator_id');
                if (indicatorIdField) {
                    indicatorIdField.value = '';
                    delete indicatorIdField.dataset.rowId;
                }
                document.querySelectorAll('.office-checkbox').forEach(cb => cb.checked = false);
                currentProgramIndicators = [];
                const successMessage = shouldUpdateExistingIndicator
                    ? 'Data updated successfully.'
                    : 'Data saved successfully.';

                showTopRightSuccessAlert(successMessage, { reload: true, duration: 1800 });
            } catch (error) {
                console.error('Save error:', error);
                showTopRightErrorAlert(error?.message || 'An error occurred while saving PAP and indicator.');
            } finally {
                PMSButtonLoading.stop(submitButton);
            }
        });

        const papSearchForm = document.getElementById('papSearchForm');
        const papSearchInput = document.getElementById('papSearchInput');

        if (papSearchForm && papSearchInput) {
            let searchDebounceTimer = null;

            const table = document.getElementById('performanceTable');
            const programHeaders = Array.from(
                table?.querySelectorAll('tr.program-header[data-core-key]') || []
            );
            const dataRows = Array.from(table?.querySelectorAll('tr.data-row[data-core-key]') || []);

            const normalizeSearchText = (value) => String(value || '')
                .toLowerCase()
                .replace(/\s+/g, ' ')
                .trim();

            const rowsForHeader = (header) => {
                const coreKey = header.dataset.coreKey || '';
                return dataRows.filter(row => (row.dataset.coreKey || '') === coreKey);
            };

            const rowSearchText = (row) => normalizeSearchText([
                row.dataset.searchText,
                row.dataset.indicatorType,
                row.dataset.officeNames,
                row.dataset.inputOfficeNames,
                row.innerText,
            ].filter(Boolean).join(' '));

            const initialRowDisplay = new WeakMap();
            programHeaders.forEach(header => {
                rowsForHeader(header).forEach(row => {
                    initialRowDisplay.set(row, row.style.display || '');
                });
            });

            const applyProgramSearch = () => {
                const query = normalizeSearchText(papSearchInput.value);

                programHeaders.forEach(header => {
                    const rows = rowsForHeader(header);
                    const headerText = normalizeSearchText(`${header.dataset.searchText || ''} ${header.innerText || ''}`);
                    const headerMatches = query === '' || headerText.includes(query);
                    const rowMatches = rows.map(row => rowSearchText(row).includes(query));
                    const matchedRowIds = new Set(rows
                        .filter((row, index) => rowMatches[index] && row.dataset.rowId)
                        .map(row => row.dataset.rowId));
                    const isMatch = query === '' || headerMatches || rowMatches.some(Boolean);

                    header.style.display = isMatch ? 'table-row' : 'none';

                    if (!isMatch) {
                        rows.forEach(row => {
                            row.style.display = 'none';
                        });
                        return;
                    }

                    if (query === '') {
                        rows.forEach(row => {
                            row.style.display = initialRowDisplay.get(row) ?? '';
                        });
                    } else {
                        rows.forEach((row, index) => {
                            const rowIdMatches = row.dataset.rowId && matchedRowIds.has(row.dataset.rowId);
                            row.style.display = (headerMatches || rowMatches[index] || rowIdMatches) ? 'table-row' : 'none';
                        });
                    }
                });
            };
            papSearchForm.addEventListener('submit', function (event) {
                event.preventDefault();
                applyProgramSearch();
            });

            papSearchInput.addEventListener('input', function () {
                clearTimeout(searchDebounceTimer);

                searchDebounceTimer = setTimeout(() => {
                    applyProgramSearch();
                }, 180);
            });

            papSearchInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    clearTimeout(searchDebounceTimer);
                    applyProgramSearch();
                }
            });

            applyProgramSearch();

            document.addEventListener('DOMContentLoaded', function () {
                const applyStickyHeaderOffsets = () => {
                    const appHeader = document.getElementById('appHeader');
                    const headerRow = document.querySelector('#performanceTable thead tr:not(.group-row)');

                    const stickyTop = appHeader ? appHeader.getBoundingClientRect().height : 0;
                    const firstHeaderHeight = headerRow ? headerRow.getBoundingClientRect().height : 46;

                    document.documentElement.style.setProperty('--table-sticky-top', `${Math.round(stickyTop)}px`);
                    document.documentElement.style.setProperty('--table-header-row-height', `${Math.round(firstHeaderHeight)}px`);
                };

                applyStickyHeaderOffsets();
                window.addEventListener('resize', applyStickyHeaderOffsets);
            });

            refreshSummaryCards();
        }

        // Delete confirmation – runs immediately, outside any conditional block
        (function () {
            const deleteModalElement = document.getElementById('deleteProgramConfirmModal');
            const confirmDeleteBtn = document.getElementById('confirmDeleteProgramBtn');
            if (!deleteModalElement || !confirmDeleteBtn) return;

            let selectedDeleteFormId = '';

            deleteModalElement.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                selectedDeleteFormId = trigger?.getAttribute('data-delete-form-id') || '';
            });

            confirmDeleteBtn.addEventListener('click', function () {
                if (!selectedDeleteFormId) return;

                const form = document.getElementById(selectedDeleteFormId);
                if (!form) return;

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            });
        })();

        (function () {
            const deleteModalElement = document.getElementById('deletePhysicalRowConfirmModal');
            const confirmDeleteBtn = document.getElementById('confirmDeletePhysicalRowBtn');
            if (!deleteModalElement || !confirmDeleteBtn) return;

            let selectedPayload = null;

            deleteModalElement.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                const indicatorId = Number(trigger?.getAttribute('data-indicator-id') || 0);
                const indicatorIds = String(trigger?.getAttribute('data-indicator-ids') || '')
                    .split(',')
                    .map(value => Number(value.trim()))
                    .filter(value => Number.isInteger(value) && value > 0);
                selectedPayload = {
                    row_id: Number(trigger?.getAttribute('data-row-id') || 0),
                    year: currentYear,
                    office_ids: String(trigger?.getAttribute('data-office-ids') || '')
                        .split(',')
                        .map(value => Number(value.trim()))
                        .filter(value => Number.isInteger(value) && value > 0),
                };
                if (indicatorId > 0) {
                    selectedPayload.indicator_id = indicatorId;
                }
                if (indicatorIds.length > 0) {
                    selectedPayload.indicator_ids = indicatorIds;
                }
            });

            confirmDeleteBtn.addEventListener('click', async function () {
                if (!selectedPayload || !selectedPayload.row_id) {
                    showTopRightErrorAlert('No row selected for delete.');
                    return;
                }

                const tokenInput = document.querySelector('input[name="_token"]');
                const token = tokenInput ? tokenInput.value : '';
                const originalHtml = confirmDeleteBtn.innerHTML;
                confirmDeleteBtn.disabled = true;
                confirmDeleteBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Deleting...';

                try {
                    const response = await fetch(deletePhysicalRowUrl, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(selectedPayload),
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Failed to delete row.');
                    }

                    const modal = bootstrap.Modal.getInstance(deleteModalElement);
                    if (modal) modal.hide();
                    showTopRightSuccessAlert('Row deleted successfully.', { reload: true, duration: 900 });
                } catch (error) {
                    console.error('row delete error:', error);
                    showTopRightErrorAlert(error?.message || 'Failed to delete row. Please try again.');
                } finally {
                    confirmDeleteBtn.disabled = false;
                    confirmDeleteBtn.innerHTML = originalHtml;
                }
            });
        })();
    </script>
