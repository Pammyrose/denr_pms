<style>
  /* Nested column menus must open only through Bootstrap's click-added .show state. */
  #columnOptionsDropdown + .dropdown-menu .dropend > .dropdown-menu:not(.show) {
    display: none !important;
  }

  #performanceTable tr.dashboard-highlight-row > td {
    animation: dashboardIndicatorPulse 1.4s ease-in-out 2;
    background-color: #fff7cc !important;
    box-shadow: inset 4px 0 0 #f59e0b;
  }

  .edit-physical-program-btn {
    color: #2563eb;
  }

  .edit-physical-program-btn:hover,
  .edit-physical-program-btn:focus-visible,
  .edit-physical-row-btn:hover,
  .edit-physical-row-btn:focus-visible {
    color: #1d4ed8;
  }

  .edit-physical-row-btn {
    color: #2563eb;
    top: 2.35rem;
    left: 0.75rem;
  }

  @keyframes dashboardIndicatorPulse {
    0%, 100% {
      background-color: #fff7cc;
    }

    50% {
      background-color: #fde68a;
    }
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const rowId = String(params.get('highlight_row_id') || '').trim();
    const indicatorId = String(params.get('highlight_indicator_id') || '').trim();

    if (!rowId && !indicatorId) {
      return;
    }

    const rows = Array.from(document.querySelectorAll('#performanceTable tbody tr[data-row-id]'));
    const idMatches = (value, expected) => expected && String(value || '') === expected;
    const targetRow = rows.find((row) => {
      const rowMatches = !rowId
        || idMatches(row.dataset.rowId, rowId)
        || idMatches(row.dataset.programId, rowId);
      const indicatorMatches = !indicatorId || String(row.dataset.indicatorId || '') === indicatorId;

      return rowMatches && indicatorMatches;
    }) || rows.find((row) => indicatorId && String(row.dataset.indicatorId || '') === indicatorId);

    if (!targetRow) {
      return;
    }

    const coreKey = String(targetRow.dataset.coreKey || '');

    if (coreKey) {
      document.querySelectorAll('#performanceTable tbody tr').forEach((row) => {
        if (String(row.dataset.coreKey || '') === coreKey) {
          row.style.display = '';
        }
      });

      document.querySelectorAll('#performanceTable tbody tr.program-header').forEach((headerRow) => {
        if (String(headerRow.dataset.coreKey || '') !== coreKey) {
          return;
        }

        const icon = headerRow.querySelector('.program-toggle-icon');
        if (icon) {
          icon.classList.add('rotate-180');
        }
      });
    }

    targetRow.style.display = '';

    window.setTimeout(() => {
      targetRow.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
      targetRow.classList.add('dashboard-highlight-row');
    }, 50);

    window.setTimeout(() => {
      targetRow.classList.remove('dashboard-highlight-row');
    }, 5000);
  });
</script>

@if(auth()->user()?->isAdmin())
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('performanceTable');
    const modalElement = document.getElementById('addIndicatorModal');
    const form = document.getElementById('addIndicatorForm');

    if (!table || !modalElement || !form) {
      return;
    }

    const modalTitle = modalElement.querySelector('.modal-title');
    const submitButton = form.querySelector('button[type="submit"]');
    const indicatorIdInput = document.getElementById('indicator_id');
    const indicatorNameInput = document.getElementById('modal_indicator_name');
    const indicatorTypeInput = document.getElementById('modal_indicator_type');
    const indicatorTypeToggle = document.getElementById('use_indicator_type');
    const prefillRows = typeof papPrefillData !== 'undefined' && Array.isArray(papPrefillData)
      ? papPrefillData
      : [];

    let editState = null;

    const normalizeValue = (value) => String(value || '')
      .replace(/\s+/g, ' ')
      .trim()
      .toLowerCase();

    const getCoreKey = (item) => [
      normalizeValue(item?.title),
      normalizeValue(item?.program),
      normalizeValue(item?.project),
      normalizeValue(item?.subprogram),
      normalizeValue(item?.subsubprogram),
      normalizeValue(item?.subsubsubprogram),
      normalizeValue(item?.subsubsubsubprogram),
      normalizeValue(item?.subsubsubsubsubprogram),
    ].join('|');

    const setModalMode = (editing) => {
      if (modalTitle) {
        modalTitle.textContent = editing ? 'Edit Performance Indicator' : 'Add Performance Indicator';
      }

      if (submitButton) {
        submitButton.textContent = editing ? 'Update' : 'Save';
      }

      form.dataset.editMode = editing ? 'true' : 'false';
    };

    const clearEditState = ({ resetForm = false } = {}) => {
      editState = null;
      delete form.dataset.editingIndicatorId;
      delete form.dataset.editingRowId;
      delete form.dataset.papUpdateUrl;
      setModalMode(false);

      if (resetForm) {
        form.reset();
        if (typeof resetPapLevels === 'function') {
          resetPapLevels();
        }
        if (indicatorIdInput) {
          indicatorIdInput.value = '';
          delete indicatorIdInput.dataset.rowId;
        }
        document.querySelectorAll('.office-checkbox').forEach((checkbox) => {
          checkbox.checked = false;
        });
        if (typeof toggleIndicatorTypeDropdown === 'function') {
          toggleIndicatorTypeDropdown();
        }
      }
    };

    const fillEditForm = (pap, preferredIndicatorId = '', preferredRowId = '') => {
      form.reset();
      if (typeof resetPapLevels === 'function') {
        resetPapLevels();
      }

      const fields = {
        pap_title: pap?.title,
        pap_program: pap?.program,
        pap_subprogram: pap?.subprogram,
        pap_subsubprogram: pap?.subsubprogram,
        pap_subsubsubprogram: pap?.subsubsubprogram,
        pap_subsubsubsubprogram: pap?.subsubsubsubprogram,
        pap_subsubsubsubsubprogram: pap?.subsubsubsubsubprogram,
        pap_project: pap?.project,
        pap_activities: pap?.activities,
        pap_subactivities: pap?.subactivities,
        pap_subsubactivities: pap?.subsubactivities,
        pap_level_6: pap?.level_6,
        pap_level_7: pap?.level_7,
        pap_level_8: pap?.level_8,
      };

      Object.entries(fields).forEach(([fieldId, value]) => {
        const input = document.getElementById(fieldId);
        if (input) {
          input.value = String(value || '');
        }
      });

      window.pmsSyncProgramLevels?.();

      [6, 7, 8].forEach((level) => {
        if (String(pap?.[`level_${level}`] || '').trim() && typeof showNextPapLevel === 'function') {
          showNextPapLevel(level);
        }
      });

      const yearInput = document.getElementById('pap_year');
      if (yearInput && pap?.year) {
        yearInput.value = String(pap.year);
      }

      const preferredIndicator = Array.isArray(pap?.indicators)
        ? pap.indicators.find((item) => {
            const indicatorMatches = !preferredIndicatorId
              || String(item?.id || '') === String(preferredIndicatorId);
            const rowMatches = !preferredRowId
              || String(item?.row_id || pap?.row_id || '') === String(preferredRowId);

            return indicatorMatches && rowMatches;
          })
        : null;
      const indicator = Array.isArray(pap?.indicators)
        ? (preferredIndicator
          || pap.indicators.find((item) => String(item?.name || '').trim() !== '')
          || pap.indicators[0]
          || null)
        : null;
      const rowId = String(preferredRowId || indicator?.row_id || pap?.row_id || pap?.id || '').trim();

      editState = {
        pap,
        indicator: indicator ? { ...indicator, row_id: rowId } : null,
        rowId,
      };

      if (indicatorIdInput) {
        indicatorIdInput.value = String(indicator?.id || '');
        indicatorIdInput.dataset.rowId = rowId;
      }

      if (indicatorNameInput) {
        indicatorNameInput.value = String(indicator?.name || '');
      }

      if (indicatorTypeInput) {
        indicatorTypeInput.value = String(indicator?.indicator_type_id || '');
      }

      if (indicatorTypeToggle) {
        indicatorTypeToggle.checked = Boolean(indicator?.indicator_type_id);
      }

      if (typeof toggleIndicatorTypeDropdown === 'function') {
        toggleIndicatorTypeDropdown();
      }

      const selectedOfficeIds = Array.isArray(indicator?.office_ids) ? indicator.office_ids : [];
      if (typeof setOfficeCheckboxes === 'function') {
        setOfficeCheckboxes(selectedOfficeIds);
      } else {
        const officeIds = new Set(selectedOfficeIds.map((id) => String(id)));
        document.querySelectorAll('.office-checkbox').forEach((checkbox) => {
          checkbox.checked = officeIds.has(String(checkbox.value));
        });
      }

      form.dataset.editingIndicatorId = String(indicator?.id || '');
      form.dataset.editingRowId = rowId;
      const sectorMatch = String(form.getAttribute('action') || '').match(/\/admin\/([^/]+)_physical\/indicators(?:\/|$)/i);
      if (sectorMatch?.[1] && rowId) {
        form.dataset.papUpdateUrl = @json(url('/admin/physical-inputs/__sector__/pap/__row__'))
          .replace('__sector__', encodeURIComponent(sectorMatch[1]))
          .replace('__row__', encodeURIComponent(rowId));
      }
      setModalMode(true);
    };

    table.querySelectorAll('tr.program-header').forEach((header) => {
      const actions = header.querySelector('span.flex.items-center');
      const deleteForm = actions?.querySelector('.delete-program-form');

      if (!actions || !deleteForm || actions.querySelector('.edit-physical-program-btn')) {
        return;
      }

      const editButton = document.createElement('button');
      editButton.type = 'button';
      editButton.className = 'btn btn-sm edit-physical-program-btn py-0 px-1 me-2 border-0 bg-transparent';
      editButton.title = 'Edit PAP and performance indicator';
      editButton.setAttribute('aria-label', 'Edit PAP and performance indicator');
      editButton.setAttribute('data-bs-toggle', 'modal');
      editButton.setAttribute('data-bs-target', '#addIndicatorModal');
      editButton.dataset.coreKey = String(header.dataset.coreKey || '');
      editButton.innerHTML = '<i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>';
      editButton.addEventListener('click', function (event) {
        event.stopPropagation();
      });

      deleteForm.before(editButton);
    });

    table.querySelectorAll('.delete-physical-row-btn').forEach((deleteButton) => {
      const cell = deleteButton.closest('td');
      if (!cell || cell.querySelector('.edit-physical-row-btn')) {
        return;
      }

      const editButton = document.createElement('button');
      editButton.type = 'button';
      editButton.className = 'btn btn-sm edit-physical-row-btn d-inline-flex align-items-center justify-content-center position-absolute';
      editButton.title = 'Edit this activity and performance indicator';
      editButton.setAttribute('aria-label', 'Edit this activity and performance indicator');
      editButton.setAttribute('data-bs-toggle', 'modal');
      editButton.setAttribute('data-bs-target', '#addIndicatorModal');
      editButton.dataset.rowId = String(deleteButton.dataset.rowId || '');
      editButton.dataset.indicatorId = String(deleteButton.dataset.indicatorId || '');
      editButton.innerHTML = '<i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>';
      editButton.addEventListener('click', function (event) {
        event.stopPropagation();
      });

      deleteButton.after(editButton);
    });

    modalElement.addEventListener('show.bs.modal', function (event) {
      const trigger = event.relatedTarget;
      const isProgramEdit = trigger?.classList?.contains('edit-physical-program-btn');
      const isRowEdit = trigger?.classList?.contains('edit-physical-row-btn');
      if (!isProgramEdit && !isRowEdit) {
        clearEditState({ resetForm: true });
        return;
      }

      if (isRowEdit) {
        const requestedRowId = String(trigger.dataset.rowId || '');
        const requestedIndicatorId = String(trigger.dataset.indicatorId || '');
        const pap = prefillRows.find((item) => {
          const indicators = Array.isArray(item?.indicators) ? item.indicators : [];
          const indicatorMatch = indicators.some((indicator) => {
            const idMatches = !requestedIndicatorId
              || String(indicator?.id || '') === requestedIndicatorId;
            const rowMatches = !requestedRowId
              || String(indicator?.row_id || item?.row_id || '') === requestedRowId;

            return idMatches && rowMatches;
          });

          return indicatorMatch
            || (requestedRowId && String(item?.row_id || '') === requestedRowId);
        }) || null;

        if (!pap) {
          clearEditState({ resetForm: true });
          return;
        }

        fillEditForm(pap, requestedIndicatorId, requestedRowId);
        return;
      }

      const requestedCoreKey = String(trigger.dataset.coreKey || '');
      const matchingRows = prefillRows.filter((item) => getCoreKey(item) === requestedCoreKey);
      const pap = matchingRows.find((item) => Array.isArray(item?.indicators) && item.indicators.length > 0)
        || matchingRows[0]
        || null;

      if (!pap) {
        clearEditState({ resetForm: true });
        return;
      }

      fillEditForm(pap);
    });

    modalElement.addEventListener('hidden.bs.modal', function () {
      clearEditState({ resetForm: true });
    });

    const originalIndicatorMatcher = window.getSelectedIndicatorFromPapMatch;
    if (typeof originalIndicatorMatcher === 'function') {
      window.getSelectedIndicatorFromPapMatch = function (matchedPap) {
        // In explicit edit mode, keep the originally selected indicator linked
        // even when its name or PAP hierarchy is changed by the user.
        if (editState?.indicator) {
          return editState.indicator;
        }

        return originalIndicatorMatcher(matchedPap);
      };
    }

    const originalApplyPrefill = window.applyModalPrefillFromExistingPap;
    if (typeof originalApplyPrefill === 'function') {
      window.applyModalPrefillFromExistingPap = function () {
        // Edit values are populated once when the pencil button is clicked.
        // Do not automatically replace indicator data while the user is typing.
        if (editState) {
          return editState.pap;
        }

        return originalApplyPrefill();
      };
    }

    const originalClearDescendants = window.clearPapDescendantFields;
    if (typeof originalClearDescendants === 'function') {
      window.clearPapDescendantFields = function (parentFieldId) {
        // Changing a parent field during Edit must not erase the user's existing
        // project, activity, or lower hierarchy values.
        if (editState) {
          return;
        }

        return originalClearDescendants(parentFieldId);
      };
    }
  });
</script>
@endif

@include('components.financial_input_persistence')
