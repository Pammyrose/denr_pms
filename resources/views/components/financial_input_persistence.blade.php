<style>
  #performanceTable th.month-header[data-dynamic-section="financial"]:not(.annual) {
    color: #fff !important;
  }

  #performanceTable .group-financial-accomp {
    background: #f5f3ff;
    color: #000;
  }

  #performanceTable th.month-header[data-dynamic-section="financial-accomp"] {
    background: #ddd6fe !important;
    border-color: #c4b5fd !important;
    color: #000 !important;
  }

  #performanceTable th.month-header.quarter[data-dynamic-section="financial-accomp"] {
    background: #c4b5fd !important;
    color: #000 !important;
  }

  #performanceTable th.month-header.annual[data-dynamic-section="financial-accomp"] {
    background: #a78bfa !important;
    color: #000 !important;
  }

  #performanceTable .group-financial-pending {
    background: #f3e8ff;
    color: #581c87;
  }

  #performanceTable th.month-header[data-dynamic-section="financial-pending"] {
    background: #e9d5ff !important;
    border-color: #d8b4fe !important;
    color: #581c87 !important;
  }

  #performanceTable td[data-dynamic-section="financial-pending"] .month-box {
    background: #faf5ff;
    border-color: #e9d5ff;
    color: #581c87;
  }

  #performanceTable td[data-dynamic-section="financial-pending"] .month-box.car-total-box {
    background: #eef2ff !important;
    border-color: #000;
    color: #1e3a8a;
    font-weight: 700;
  }

  #performanceTable .month-box.car-total-box,
  #performanceTable .month-box.group-total-box {
    border-color: #000 !important;
  }

  #performanceTable .month-box.target-not-accomplished {
    border: 2px solid #dc2626 !important;
  }

  #performanceTable .month-box.locked-change-request:not(.target-not-accomplished) {
    border-style: dashed;
    border-color: #b45309 !important;
  }

  #performanceTable th.summary-header {
    width: 96px !important;
    min-width: 96px !important;
    max-width: 96px !important;
    padding: 6px 4px !important;
    color: #fff !important;
    line-height: 1.1;
    white-space: normal;
  }

  #performanceTable td[data-dynamic-section="summary"] {
    width: 96px !important;
    min-width: 96px !important;
    max-width: 96px !important;
    padding-left: 6px !important;
    padding-right: 6px !important;
  }

  #performanceTable td[data-dynamic-section="summary"] .month-box.summary-box {
    width: 82px !important;
    min-width: 82px !important;
    max-width: 82px !important;
  }

  #performanceTable th.summary-header[data-summary-period="annual"] {
    background: #1f3a63 !important;
  }

  #performanceTable th.summary-header[data-summary-period="quarter"] {
    background: #f59e0b !important;
  }

  #performanceTable th.summary-header[data-summary-period="to-date"] {
    background: #4f70c9 !important;
  }

  #performanceTable th.summary-section-start,
  #performanceTable td.summary-section-start {
    border-left: 3px solid #0f2747 !important;
  }

  #performanceTable th.summary-header .tiny-period {
    color: inherit;
    opacity: .95;
  }

  #performanceTable .group-summary[data-summary-kind="physical-target"] {
    background: #c9f7df !important;
    color: #065f46 !important;
  }

  #performanceTable th.summary-header[data-summary-kind="physical-target"] {
    background: #c9f7df !important;
    color: #065f46 !important;
  }

  #performanceTable .group-summary[data-summary-kind="physical-accomplishment"] {
    background: #dbeafe !important;
    color: #1e3a8a !important;
  }

  #performanceTable th.summary-header[data-summary-kind="physical-accomplishment"] {
    background: #dbeafe !important;
    color: #1e3a8a !important;
  }

  #performanceTable .group-summary[data-summary-kind="physical-percentage"],
  #performanceTable th.summary-header[data-summary-kind="physical-percentage"] {
    background: #fff !important;
    color: #111827 !important;
  }

  #performanceTable td[data-summary-kind="physical-percentage"] .summary-box {
    font-weight: 700;
  }

  #performanceTable .physical-performance-title-row th {
    position: sticky;
    top: calc(var(--table-sticky-top, 0px) + var(--table-header-row-height, 46px));
    z-index: 11;
    height: 28px;
    padding: 4px 6px;
    line-height: 1.2;
  }

  #performanceTable .physical-performance-title-row + tr.group-row th {
    top: calc(
      var(--table-sticky-top, 0px)
      + var(--table-header-row-height, 46px)
      + var(--physical-performance-title-row-height, 28px)
    );
    z-index: 10;
  }

  #performanceTable .physical-performance-title-row .physical-performance-title,
  #performanceTable .physical-performance-title-row .financial-performance-title {
    background: #fff !important;
    color: #111827 !important;
    border: 1px solid #111827 !important;
    font-size: 14px;
    font-weight: 700;
    text-align: center;
  }

  #performanceTable .physical-performance-title-row .physical-performance-title-spacer {
    padding: 0 !important;
    border: 0 !important;
    background: transparent !important;
  }

  #performanceTable .group-summary[data-summary-kind="financial-target"] {
    background: #fef3c7 !important;
    color: #92400e !important;
  }

  #performanceTable th.summary-header[data-summary-kind="financial-target"] {
    background: #fef3c7 !important;
    color: #92400e !important;
  }

  #performanceTable .group-summary[data-summary-kind="financial-accomplishment"] {
    background: #ede9fe !important;
    color: #5b21b6 !important;
  }

  #performanceTable th.summary-header[data-summary-kind="financial-accomplishment"] {
    background: #ede9fe !important;
    color: #5b21b6 !important;
  }

  #performanceTable .group-summary[data-summary-kind="financial-bur"],
  #performanceTable th.summary-header[data-summary-kind="financial-bur"] {
    background: #e0f2fe !important;
    color: #075985 !important;
  }

</style>

@unless(
  (auth()->user()?->isAdmin() ?? false)
  || (auth()->user()?->isRegionalOffice() ?? false)
)
<div class="modal fade" id="lockedMonthEditConfirmModal" tabindex="-1"
     aria-labelledby="lockedMonthEditConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-primary">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="lockedMonthEditConfirmModalLabel">
          <i class="fa-solid fa-lock me-2"></i>Edit Locked Accomplishment
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">Are you sure you want to edit this locked accomplishment month?</p>
        <div id="lockedMonthEditDescription" class="small text-muted"></div>
        <div id="lockedMonthReasonGroup" class="mt-3">
          <label for="lockedMonthEditReason" class="form-label fw-semibold">Reason for change</label>
          <textarea id="lockedMonthEditReason" class="form-control" rows="3" maxlength="1000"></textarea>
          <div class="invalid-feedback">Please enter a reason before continuing.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmLockedMonthEditBtn">
          <i class="fa-solid fa-lock-open me-1"></i> Yes
        </button>
      </div>
    </div>
  </div>
</div>
@endunless

<script src="{{ asset('js/pap-options.js') }}"></script>
<script src="{{ asset('js/performance-viewport.js') }}"></script>
<script src="{{ asset('js/physical-totals.js') }}"></script>
<script>
  (() => {
    const config = {
      sector: @json($financialSector ?? ''),
      storeUrl: @json(route('financial_inputs.store', ['sector' => $financialSector ?? 'unknown'])),
      existing: @json($financials ?? []),
      existingAccomplishments: @json($financialAccomplishments ?? []),
      accomplishmentsOnly: @json(auth()->user()?->hasAccomplishmentOnlyAccess() ?? false),
      selectedYear: @json((int) ($year ?? now('Asia/Manila')->year)),
      currentYear: @json((int) now('Asia/Manila')->year),
      currentMonth: @json((int) now('Asia/Manila')->month),
      bypassLockedMonths: @json(
        (auth()->user()?->isAdmin() ?? false)
        || (auth()->user()?->isRegionalOffice() ?? false)
      ),
      canRequestLockedChanges: @json(
        auth()->check()
        && ! (auth()->user()?->isAdmin() ?? false)
        && ! (auth()->user()?->isRegionalOffice() ?? false)
      ),
    };

    const lockTargetInputs = (root = document) => {
      if (!config.accomplishmentsOnly) return;

      const selector = [
        '.month-box[data-section="target"]',
        '.month-box[data-section="financial"]',
        '.month-box[data-section="pending"][data-source-section="target"]',
        '.month-box[data-section="financial-pending"][data-source-section="financial"]',
      ].join(', ');
      const inputs = [
        ...(root.matches?.(selector) ? [root] : []),
        ...(root.querySelectorAll?.(selector) || []),
      ];

      inputs.forEach(input => {
          input.readOnly = true;
          input.setAttribute('aria-readonly', 'true');
          input.title = 'Targets are read-only for user accounts.';
          input.classList.add('bg-light');
      });
    };

    if (config.accomplishmentsOnly) {
      document.querySelectorAll('[data-bs-target="#addIndicatorModal"]')
        .forEach(button => button.classList.add('d-none'));
      lockTargetInputs();
      new MutationObserver(mutations => {
        mutations.forEach(mutation => mutation.addedNodes.forEach(node => {
          if (node.nodeType === Node.ELEMENT_NODE) lockTargetInputs(node);
        }));
      }).observe(document.body, { childList: true, subtree: true });
    }

    const monthColumnIndexes = [0, 1, 2, 4, 5, 6, 8, 9, 10, 12, 13, 14];
    const passedMonthCount = config.selectedYear < config.currentYear
      ? 12
      : (config.selectedYear === config.currentYear ? config.currentMonth - 1 : 0);
    const lockedMonthColumns = new Set(monthColumnIndexes.slice(0, passedMonthCount));

    const lockPassedAccomplishmentMonths = (root = document) => {
      if (config.bypassLockedMonths || lockedMonthColumns.size === 0) return;

      const selector = [
        '.month-box[data-section="accomp"]',
        '.month-box[data-section="financial-accomp"]',
      ].join(', ');
      const inputs = [
        ...(root.matches?.(selector) ? [root] : []),
        ...(root.querySelectorAll?.(selector) || []),
      ];

      inputs.forEach((input) => {
        if (!lockedMonthColumns.has(Number(input.dataset.col))) return;
        const isAggregate = input.dataset.carTotal === '1' || input.dataset.groupTotal === '1';
        const canOpenLockedMonth = config.canRequestLockedChanges;
        if (!isAggregate) {
          input.readOnly = true;
          input.setAttribute('aria-readonly', 'true');
          if (canOpenLockedMonth) {
            input.classList.add('locked-change-request');
          } else {
            input.classList.remove('locked-change-request');
          }
        }
        input.title = !canOpenLockedMonth
          ? 'This month is locked. Admin and Regional Office accounts review change requests from Notifications.'
          : 'This month has passed. Click to confirm the edit and provide a reason.';
        input.classList.add('bg-light');
      });
    };

    lockPassedAccomplishmentMonths();
    new MutationObserver((mutations) => {
      mutations.forEach(mutation => mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) lockPassedAccomplishmentMonths(node);
      }));
    }).observe(document.body, { childList: true, subtree: true });

    const lockedMonthModalElement = document.getElementById('lockedMonthEditConfirmModal');
    const lockedMonthModal = lockedMonthModalElement && window.bootstrap
      ? bootstrap.Modal.getOrCreateInstance(lockedMonthModalElement)
      : null;
    const lockedMonthDescription = document.getElementById('lockedMonthEditDescription');
    const lockedMonthReasonGroup = document.getElementById('lockedMonthReasonGroup');
    const lockedMonthReasonInput = document.getElementById('lockedMonthEditReason');
    const lockedChangeReasons = new Map();
    let pendingLockedMonthInput = null;

    if (lockedMonthReasonGroup && !config.canRequestLockedChanges) {
      lockedMonthReasonGroup.classList.add('d-none');
    }

    document.getElementById('performanceTable')?.addEventListener('click', (event) => {
      const input = event.target;
      if (!input?.classList?.contains('locked-change-request') || !input.readOnly) return;

      pendingLockedMonthInput = input;
      const monthName = periodKeys[Number(input.dataset.col)] || 'selected month';
      const sectionName = input.dataset.section === 'financial-accomp' ? 'Financial' : 'Physical';
      if (lockedMonthDescription) {
        lockedMonthDescription.textContent = `${sectionName} accomplishment · ${monthName.toUpperCase()} ${config.selectedYear}`;
      }
      if (lockedMonthReasonInput) {
        const row = input.closest('tr[data-row-id]');
        const reasonKey = touchedEntryKey(
          input.dataset.section,
          row?.dataset?.rowId,
          row?.dataset?.indicatorId,
          input.dataset.officeId,
        );
        lockedMonthReasonInput.value = lockedChangeReasons.get(reasonKey) || '';
        lockedMonthReasonInput.classList.remove('is-invalid');
      }
      lockedMonthModal?.show();
    });

    document.getElementById('confirmLockedMonthEditBtn')?.addEventListener('click', () => {
      const input = pendingLockedMonthInput;
      if (!input) return;

      if (!config.canRequestLockedChanges) {
        pendingLockedMonthInput = null;
        lockedMonthModal?.hide();
        return;
      }

      if (config.canRequestLockedChanges) {
        const reason = String(lockedMonthReasonInput?.value || '').trim();
        if (!reason) {
          lockedMonthReasonInput?.classList.add('is-invalid');
          lockedMonthReasonInput?.focus();
          return;
        }

        const row = input.closest('tr[data-row-id]');
        const reasonKey = touchedEntryKey(
          input.dataset.section,
          row?.dataset?.rowId,
          row?.dataset?.indicatorId,
          input.dataset.officeId,
        );
        lockedChangeReasons.set(reasonKey, reason);
      }

      input.readOnly = false;
      input.removeAttribute('aria-readonly');
      input.dataset.lockedEditConfirmed = '1';
      input.title = 'Locked month opened for editing. Your reason will be submitted when you save.';
      pendingLockedMonthInput = null;
      lockedMonthModal?.hide();
      window.setTimeout(() => {
        input.focus();
        input.select?.();
      }, 200);
    });

    lockedMonthModalElement?.addEventListener('hidden.bs.modal', () => {
      pendingLockedMonthInput = null;
    });

    const periodKeys = [
      'jan', 'feb', 'mar', 'q1',
      'apr', 'may', 'jun', 'q2',
      'jul', 'aug', 'sep', 'q3',
      'oct', 'nov', 'dec', 'q4',
      'annual_total',
    ];

    const touchedPeriods = new Map();

    const touchedEntryKey = (section, rowId, indicatorId, officeId) =>
      [section, rowId, indicatorId, officeId].map(value => String(value || '').trim()).join('|');

    const attachTouchedPeriods = (entries, section) => entries.map(entry => ({
      ...entry,
      changed_periods: Array.from(touchedPeriods.get(touchedEntryKey(
        section,
        entry?.row_id || entry?.program_id,
        entry?.indicator_id,
        entry?.office_id,
      )) || []),
    }));

    const lockedMonthKeys = new Set(
      Array.from(lockedMonthColumns).map(column => periodKeys[column]).filter(Boolean)
    );
    const entryChangesLockedMonth = entry => Array.from(entry?.changed_periods || [])
      .some(period => lockedMonthKeys.has(period));
    const addLockedChangeReasons = (entries, section) => entries.map((entry) => {
      if (!entryChangesLockedMonth(entry)) return entry;

      const reasonKey = touchedEntryKey(
        section,
        entry?.row_id || entry?.program_id,
        entry?.indicator_id,
        entry?.office_id,
      );

      return { ...entry, change_reason: lockedChangeReasons.get(reasonKey) || '' };
    });

    document.getElementById('performanceTable')?.addEventListener('input', event => {
      const input = event.target;
      const section = String(input?.dataset?.section || '');
      if (!input?.classList?.contains('month-box') || !['accomp', 'financial-accomp'].includes(section)) return;
      if (input.readOnly || input.dataset.carTotal === '1' || input.dataset.groupTotal === '1') return;

      const row = input.closest('tr[data-row-id]');
      const period = periodKeys[Number(input.dataset.col)];
      const officeId = String(input.dataset.officeId || '').trim();
      if (!row || !period || !officeId) return;

      const key = touchedEntryKey(section, row.dataset.rowId, row.dataset.indicatorId, officeId);
      const periods = touchedPeriods.get(key) || new Set();
      periods.add(period);
      touchedPeriods.set(key, periods);
    }, true);

    const storedEntry = (entry, kind) => {
      const rowId = String(entry?.row_id || entry?.program_id || '').trim();
      const indicatorId = String(entry?.indicator_id || '').trim();
      const officeId = String(entry?.office_id || '').trim();
      const source = kind === 'accomplishment' ? config.existingAccomplishments : config.existing;

      return source?.[rowId]?.[indicatorId]?.[officeId] || null;
    };

    const numericValue = (value) => {
      if (typeof parsePeriodInputValue === 'function') {
        return parsePeriodInputValue(value);
      }

      const parsed = Number(String(value ?? '').replace(/,/g, '').replace(/%/g, '').trim());
      return Number.isFinite(parsed) ? parsed : 0;
    };

    const dueMonthCount = config.selectedYear < config.currentYear
      ? 12
      : (config.selectedYear === config.currentYear ? config.currentMonth : 0);
    const dueMonthColumns = new Set(monthColumnIndexes.slice(0, dueMonthCount));

    const targetSectionFor = accomplishmentSection => accomplishmentSection === 'financial-accomp'
      ? 'financial'
      : 'target';

    const targetSourceFor = targetSection => targetSection === 'financial'
      ? config.existing
      : (typeof existingTargetsByIndicator !== 'undefined' ? existingTargetsByIndicator : {});

    const storedTargetValue = (row, targetSection, officeId, column) => {
      const rowId = String(row?.dataset?.rowId || row?.dataset?.programId || '').trim();
      const indicatorId = String(row?.dataset?.indicatorId || '').trim();
      const period = periodKeys[column] || '';
      if (!rowId || !indicatorId || !officeId || !period) return 0;

      return numericValue(targetSourceFor(targetSection)?.[rowId]?.[indicatorId]?.[officeId]?.[period]);
    };

    const targetOfficeIdsForInput = (row, input) => {
      if (input.dataset.groupTotal === '1') {
        return String(input.dataset.groupOfficeIds || '')
          .split(',')
          .map(value => value.trim())
          .filter(Boolean);
      }

      if (input.dataset.carTotal === '1') {
        const assignedIds = typeof getAssignedOfficeIdsForRow === 'function'
          ? getAssignedOfficeIdsForRow(row).map(String)
          : [];
        if (assignedIds.length > 0) return assignedIds;

        const rowId = String(row?.dataset?.rowId || row?.dataset?.programId || '').trim();
        const indicatorId = String(row?.dataset?.indicatorId || '').trim();
        const source = targetSourceFor(targetSectionFor(input.dataset.section));
        return Object.keys(source?.[rowId]?.[indicatorId] || {});
      }

      const officeId = String(input.dataset.officeId || '').trim();
      return officeId ? [officeId] : [];
    };

    let targetInputIndexes = new WeakMap();
    const targetInputKey = input => JSON.stringify([
      Number(input.dataset.col),
      input.dataset.carTotal === '1',
      input.dataset.groupTotal === '1',
      String(input.dataset.groupTotal === '1' ? (input.dataset.groupKey || '') : (input.dataset.officeId || '')),
    ]);

    const liveTargetInput = (row, accomplishmentInput, targetSection) => {
      let sections = targetInputIndexes.get(row);
      if (!sections) {
        sections = new Map();
        targetInputIndexes.set(row, sections);
      }
      if (!sections.has(targetSection)) {
        const inputs = new Map();
        row.querySelectorAll(`.month-box[data-section="${targetSection}"]`).forEach(candidate => {
          const key = targetInputKey(candidate);
          if (!inputs.has(key)) inputs.set(key, candidate);
        });
        sections.set(targetSection, inputs);
      }
      return sections.get(targetSection).get(targetInputKey(accomplishmentInput)) || null;
    };

    const targetValueForAccomplishment = (row, input) => {
      const targetSection = targetSectionFor(input.dataset.section);
      const liveInput = liveTargetInput(row, input, targetSection);
      if (liveInput) return numericValue(liveInput.value);

      const column = Number(input.dataset.col);
      const officeValues = targetOfficeIdsForInput(row, input)
        .map(officeId => storedTargetValue(row, targetSection, officeId, column));

      if (input.dataset.carTotal !== '1' && input.dataset.groupTotal !== '1') {
        return officeValues[0] || 0;
      }

      const isSemiCumulativePhysical = targetSection === 'target'
        && typeof getIndicatorTypeForRow === 'function'
        && getIndicatorTypeForRow(row) === 'semi-cumulative';

      return isSemiCumulativePhysical
        ? Math.max(0, ...officeValues)
        : officeValues.reduce((total, value) => total + value, 0);
    };

    const refreshTargetMissBorders = () => {
      targetInputIndexes = new WeakMap();
      document.querySelectorAll([
        '#performanceTable .month-box[data-section="accomp"]',
        '#performanceTable .month-box[data-section="financial-accomp"]',
      ].join(', ')).forEach((input) => {
        const column = Number(input.dataset.col);
        const row = input.closest('tr[data-row-id]');
        const isDueMonth = dueMonthColumns.has(column);
        const target = row && isDueMonth ? targetValueForAccomplishment(row, input) : 0;
        const accomplishment = numericValue(input.value);
        const missedTarget = target > 0 && accomplishment + 0.000001 < target;

        input.classList.toggle('target-not-accomplished', missedTarget);
        if (missedTarget) {
          if (input.dataset.defaultStatusTitle === undefined) {
            input.dataset.defaultStatusTitle = input.getAttribute('title') || '';
          }
          input.title = `Target not accomplished: ${accomplishment} of ${target}`;
        } else if (input.dataset.defaultStatusTitle !== undefined) {
          const defaultTitle = input.dataset.defaultStatusTitle;
          delete input.dataset.defaultStatusTitle;
          if (defaultTitle) {
            input.title = defaultTitle;
          } else {
            input.removeAttribute('title');
          }
        }
      });
    };

    let targetMissRefreshQueued = false;
    const scheduleTargetMissBorderRefresh = () => {
      if (targetMissRefreshQueued) return;
      targetMissRefreshQueued = true;
      requestAnimationFrame(() => {
        targetMissRefreshQueued = false;
        refreshTargetMissBorders();
      });
    };

    document.getElementById('performanceTable')?.addEventListener('input', (event) => {
      if (!event.target?.classList?.contains('month-box')) return;
      scheduleTargetMissBorderRefresh();
    });

    new MutationObserver(scheduleTargetMissBorderRefresh)
      .observe(document.getElementById('performanceTable'), { childList: true, subtree: true });

    const summaryMonthIndex = Math.max(0, Math.min(11, Number(@json(now()->month)) - 1));
    const summaryMonthColumns = [0, 1, 2, 4, 5, 6, 8, 9, 10, 12, 13, 14];
    const summaryQuarterColumns = [3, 7, 11, 15];
    const summaryQuarterIndex = Math.floor(summaryMonthIndex / 3);
    const summaryPeriods = [
      { key: 'annual', label: 'ANNUAL', column: 16 },
      { key: 'quarter', label: `Q${summaryQuarterIndex + 1}`, column: summaryQuarterColumns[summaryQuarterIndex] },
      { key: 'to-date', label: 'TO DATE', column: null },
    ];
    const financialTargetSummaryPeriods = summaryPeriods.map(period => ({
      ...period,
      label: period.key === 'annual'
        ? 'Allotment'
        : period.key === 'quarter'
          ? 'This Quarter'
          : 'To Date',
    }));
    const financialAccomplishmentSummaryPeriods = financialTargetSummaryPeriods
      .filter(period => period.key !== 'annual');
    const financialBurSummaryPeriods = [
      {
        key: 'obligation-allotment',
        label: '(Oblig/Allot) *100',
        numeratorSection: 'financial-target',
        numeratorPeriod: 'to-date',
        denominatorSection: 'financial-target',
        denominatorPeriod: 'annual',
      },
      {
        key: 'disbursement-allotment',
        label: '(Disb/Allot) *100',
        numeratorSection: 'financial-accomplishment',
        numeratorPeriod: 'to-date',
        denominatorSection: 'financial-target',
        denominatorPeriod: 'annual',
      },
      {
        key: 'disbursement-obligation',
        label: '(Disb/Oblig) *100',
        numeratorSection: 'financial-accomplishment',
        numeratorPeriod: 'to-date',
        denominatorSection: 'financial-target',
        denominatorPeriod: 'to-date',
      },
    ];
    const physicalSummarySections = @json(\App\Support\PhysicalPerformanceSummaryLayout::sections());
    const physicalSummarySources = {
      'physical-target': () => existingTargetsByIndicator,
      'physical-accomplishment': () => existingAccompByIndicator,
      'physical-percentage': () => ({}),
    };
    const summarySections = [
      ...physicalSummarySections.map(section => ({
        ...section,
        source: physicalSummarySources[section.key],
      })),
      {
        key: 'financial-target',
        label: 'Obligation',
        inputSection: 'financial',
        source: () => config.existing,
        periods: financialTargetSummaryPeriods,
      },
      {
        key: 'financial-accomplishment',
        label: 'Disbursement',
        inputSection: 'financial-accomp',
        source: () => config.existingAccomplishments,
        periods: financialAccomplishmentSummaryPeriods,
      },
      {
        key: 'financial-bur',
        label: '% Budget Utilization Rate (BUR)',
        isPercentage: true,
        isFinancialPercentage: true,
        periods: financialBurSummaryPeriods,
      },
    ];
    const summaryPeriodsForSection = section => section.periods || summaryPeriods;

    const summaryStoredValue = (source, rowId, indicatorId, officeId, column) => {
      const periodKey = periodKeys[column] || '';
      if (!periodKey) return 0;

      return numericValue(source?.[String(rowId)]?.[String(indicatorId)]?.[String(officeId)]?.[periodKey]);
    };

    const summaryColumnValue = (row, summarySection, officeId, column) => {
      const liveInput = row.querySelector(
        `.month-box[data-section="${summarySection.inputSection}"][data-office-id="${officeId}"][data-col="${column}"]`
      );

      if (liveInput) return numericValue(liveInput.value);

      return summaryStoredValue(
        summarySection.source(),
        row.dataset.rowId || row.dataset.programId,
        row.dataset.indicatorId,
        officeId,
        column
      );
    };

    const summaryIndicatorType = (row, summarySection) => {
      const isFinancial = ['financial', 'financial-accomp'].includes(summarySection.inputSection);
      return isFinancial || typeof getIndicatorTypeForRow !== 'function'
        ? 'cumulative'
        : getIndicatorTypeForRow(row);
    };

    const summaryValueFromMonthlyValues = (row, summarySection, monthlyValues, period) => {
      const indicatorType = summaryIndicatorType(row, summarySection);
      const quarterValue = values => indicatorType === 'non-cumulative'
        ? window.pmsMostFrequentTotal(values)
        : values.reduce((total, value) => total + value, 0);
      const quarterValues = [0, 1, 2, 3].map(quarterIndex => {
        const start = quarterIndex * 3;
        return quarterValue(monthlyValues.slice(start, start + 3));
      });

      if (period.key === 'quarter') {
        return quarterValues[summaryQuarterIndex];
      }

      if (period.key === 'annual') {
        if (indicatorType === 'non-cumulative') return window.pmsMostFrequentTotal(quarterValues);
        return indicatorType === 'semi-cumulative'
          ? Math.max(0, ...quarterValues)
          : quarterValues.reduce((total, value) => total + value, 0);
      }

      const valuesThroughCurrentMonth = monthlyValues.slice(0, summaryMonthIndex + 1);
      if (indicatorType === 'cumulative') {
        return valuesThroughCurrentMonth.reduce((total, value) => total + value, 0);
      }

      const quarterValuesToDate = [];
      for (let start = 0; start < valuesThroughCurrentMonth.length; start += 3) {
        quarterValuesToDate.push(quarterValue(valuesThroughCurrentMonth.slice(start, start + 3)));
      }

      return indicatorType === 'semi-cumulative'
        ? Math.max(0, ...quarterValuesToDate)
        : quarterValuesToDate.reduce((total, value) => total + value, 0);
    };

    const summaryValue = (row, summarySection, officeId, period) => {
      const monthlyValues = summaryMonthColumns.map(
        column => summaryColumnValue(row, summarySection, officeId, column)
      );

      return summaryValueFromMonthlyValues(row, summarySection, monthlyValues, period);
    };

    const physicalPercentageValue = (row, period, officeId = '', aggregateOfficeIds = []) => {
      const targetSection = summarySections.find(section => section.key === 'physical-target');
      const accomplishmentSection = summarySections.find(section => section.key === 'physical-accomplishment');
      const accomplishmentPeriod = summaryPeriods.find(summaryPeriod => summaryPeriod.key === 'to-date');
      const targetPeriod = summaryPeriods.find(summaryPeriod => summaryPeriod.key === period.targetPeriod);
      if (!targetSection || !accomplishmentSection || !accomplishmentPeriod || !targetPeriod) return 0;

      const isAggregate = aggregateOfficeIds.length > 0;
      const accomplishment = isAggregate
        ? summaryAggregateValue(row, accomplishmentSection, aggregateOfficeIds, accomplishmentPeriod)
        : summaryValue(row, accomplishmentSection, officeId, accomplishmentPeriod);
      const target = isAggregate
        ? summaryAggregateValue(row, targetSection, aggregateOfficeIds, targetPeriod)
        : summaryValue(row, targetSection, officeId, targetPeriod);

      return target > 0 ? (accomplishment / target) * 100 : 0;
    };

    const formatSummaryPercentage = value => `${numericValue(value).toFixed(2)}%`;

    const financialBurValue = (row, period, officeId = '', aggregateOfficeIds = []) => {
      const sectionValue = (sectionKey, periodKey) => {
        const section = summarySections.find(item => item.key === sectionKey);
        const sourcePeriod = summaryPeriodsForSection(section || {}).find(
          item => item.key === periodKey
        );
        if (!section || !sourcePeriod) return 0;

        return aggregateOfficeIds.length > 0
          ? summaryAggregateValue(row, section, aggregateOfficeIds, sourcePeriod)
          : summaryValue(row, section, officeId, sourcePeriod);
      };

      const numerator = sectionValue(period.numeratorSection, period.numeratorPeriod);
      const denominator = sectionValue(period.denominatorSection, period.denominatorPeriod);

      return denominator > 0 ? (numerator / denominator) * 100 : 0;
    };

    const summaryAggregateValue = (row, summarySection, officeIds, period) => {
      const indicatorType = summaryIndicatorType(row, summarySection);
      const monthlyValues = summaryMonthColumns.map((column) => {
        const officeValues = officeIds.map(officeId => summaryColumnValue(
          row,
          summarySection,
          officeId,
          column
        ));

        return indicatorType === 'non-cumulative'
          ? Math.max(0, ...officeValues)
          : officeValues.reduce((total, value) => total + value, 0);
      });

      return summaryValueFromMonthlyValues(row, summarySection, monthlyValues, period);
    };

    const insertBeforeRemarks = (row, cell) => {
      const remarksCell = row.querySelector('td[data-dynamic-section="remarks"]');
      if (remarksCell) {
        row.insertBefore(cell, remarksCell);
      } else {
        row.appendChild(cell);
      }
    };

    const getSummaryAggregateLines = (officeEntries, groupBreakIndices, groupPenroFlags) => {
      const sortedBreaks = [...groupBreakIndices]
        .map(index => Number(index))
        .filter(index => Number.isInteger(index) && index >= 0)
        .sort((left, right) => left - right);
      const groupRanges = [];
      let rangeStart = 0;

      sortedBreaks.forEach((breakIndex) => {
        if (breakIndex >= rangeStart && breakIndex < officeEntries.length) {
          groupRanges.push({ start: rangeStart, end: breakIndex });
          rangeStart = breakIndex + 1;
        }
      });

      if (rangeStart < officeEntries.length) {
        groupRanges.push({ start: rangeStart, end: officeEntries.length - 1 });
      }

      const officeIdsForRange = range => officeEntries
        .slice(range.start, range.end + 1)
        .map(office => String(office?.id || '').trim())
        .filter(Boolean);

      return [
        {
          kind: 'car',
          officeIds: officeEntries
            .map(office => String(office?.id || '').trim())
            .filter(Boolean),
        },
        ...groupRanges
          .map((range, groupIndex) => ({
            kind: 'province',
            officeIds: officeIdsForRange(range),
            visible: Boolean(groupPenroFlags[groupIndex]),
          }))
          .filter(aggregate => aggregate.visible),
      ];
    };

    const createSummaryInput = (section, period, options = {}) => {
      const { officeId = '', aggregate = null } = options;
      const input = document.createElement('input');
      input.type = section.isPercentage ? 'text' : 'number';
      input.className = 'month-box summary-box';
      input.style.width = '100%';
      input.readOnly = true;
      input.setAttribute('aria-readonly', 'true');
      input.dataset.summaryKind = section.key;
      input.dataset.summaryPeriod = period.key;

      if (aggregate) {
        input.dataset.summaryAggregate = aggregate.kind;
        input.dataset.summaryOfficeIds = aggregate.officeIds.join(',');
        input.setAttribute('aria-label', aggregate.kind === 'car' ? 'CAR total' : 'Province total');
        input.classList.add(aggregate.kind === 'car' ? 'car-total-box' : 'group-total-box');
      } else {
        input.dataset.officeId = String(officeId || '');
      }

      return input;
    };

    const addSummaryHeaders = (mainHeader, groupHeader) => {
      if (groupHeader.children.length === 0) {
        for (let index = 0; index < 2; index += 1) {
          groupHeader.appendChild(document.createElement('th'));
        }
      }

      summarySections.forEach((section) => {
        const sectionPeriods = summaryPeriodsForSection(section);
        const summaryGroup = document.createElement('th');
        summaryGroup.colSpan = sectionPeriods.length;
        summaryGroup.className = 'group-header group-summary';
        summaryGroup.dataset.summaryKind = section.key;
        summaryGroup.textContent = section.label;
        const remarksGroup = groupHeader.querySelector('.group-remarks');
        if (remarksGroup) {
          groupHeader.insertBefore(summaryGroup, remarksGroup);
        } else {
          groupHeader.appendChild(summaryGroup);
        }

        sectionPeriods.forEach((period, periodIndex) => {
          const header = document.createElement('th');
          header.className = 'month-header text-center dynamic-header-summary summary-header';
          if (periodIndex === 0) header.classList.add('summary-section-start');
          header.dataset.dynamicSection = 'summary';
          header.dataset.summaryKind = section.key;
          header.dataset.summaryPeriod = period.key;
          header.textContent = period.label;

          const remarksHeader = mainHeader.querySelector('th[data-dynamic-section="remarks"]');
          if (remarksHeader) {
            mainHeader.insertBefore(header, remarksHeader);
          } else {
            mainHeader.appendChild(header);
          }
        });
      });

    };

    let physicalPerformanceTitleObserver = null;

    const removePhysicalPerformanceTitleRow = (table) => {
      if (physicalPerformanceTitleObserver) {
        physicalPerformanceTitleObserver.disconnect();
        physicalPerformanceTitleObserver = null;
      }
      table.querySelector('thead .physical-performance-title-row')?.remove();
      table.closest('.table-container')?.style.removeProperty('--physical-performance-title-row-height');
    };

    const addPhysicalPerformanceTitleRow = (table, mainHeader, groupHeader) => {
      table.querySelector('thead .physical-performance-title-row')?.remove();

      let leadingColumns = 0;
      let physicalColumns = 0;
      let betweenPerformanceColumns = 0;
      let financialColumns = 0;
      let trailingColumns = 0;
      let physicalSectionStarted = false;
      let financialSectionStarted = false;

      Array.from(mainHeader.children).forEach((header) => {
        const columnCount = Number(header.colSpan) || 1;
        const isPhysicalSummary = header.dataset.dynamicSection === 'summary'
          && String(header.dataset.summaryKind || '').startsWith('physical-');
        const isFinancialSummary = header.dataset.dynamicSection === 'summary'
          && String(header.dataset.summaryKind || '').startsWith('financial-');

        if (isPhysicalSummary) {
          physicalSectionStarted = true;
          physicalColumns += columnCount;
        } else if (isFinancialSummary) {
          financialSectionStarted = true;
          financialColumns += columnCount;
        } else if (financialSectionStarted) {
          trailingColumns += columnCount;
        } else if (physicalSectionStarted) {
          betweenPerformanceColumns += columnCount;
        } else {
          leadingColumns += columnCount;
        }
      });

      if (physicalColumns === 0) return;

      const titleRow = document.createElement('tr');
      titleRow.className = 'group-row physical-performance-title-row';

      const appendSpacer = (columnCount) => {
        if (columnCount === 0) return;
        const spacer = document.createElement('th');
        spacer.colSpan = columnCount;
        spacer.className = 'physical-performance-title-spacer';
        spacer.setAttribute('aria-hidden', 'true');
        titleRow.appendChild(spacer);
      };

      appendSpacer(leadingColumns);

      const title = document.createElement('th');
      title.colSpan = physicalColumns;
      title.className = 'physical-performance-title';
      title.textContent = @json(\App\Support\PhysicalPerformanceSummaryLayout::title());
      titleRow.appendChild(title);

      appendSpacer(betweenPerformanceColumns);

      if (financialColumns > 0) {
        const financialTitle = document.createElement('th');
        financialTitle.colSpan = financialColumns;
        financialTitle.className = 'financial-performance-title';
        financialTitle.textContent = 'Financial Performance';
        titleRow.appendChild(financialTitle);
      }

      appendSpacer(trailingColumns);
      groupHeader.parentNode.insertBefore(titleRow, groupHeader);

      const titleRowHeight = Math.ceil(titleRow.getBoundingClientRect().height) || 28;
      table.closest('.table-container')?.style.setProperty(
        '--physical-performance-title-row-height',
        `${titleRowHeight}px`
      );
    };

    const showPhysicalPerformanceTitleRow = (table, mainHeader, groupHeader) => {
      addPhysicalPerformanceTitleRow(table, mainHeader, groupHeader);
      physicalPerformanceTitleObserver?.disconnect();
      physicalPerformanceTitleObserver = new MutationObserver(() => {
        addPhysicalPerformanceTitleRow(table, mainHeader, groupHeader);
      });
      physicalPerformanceTitleObserver.observe(mainHeader, { childList: true });
    };

    const addSummaryCells = (selectedRows = null) => {
      Array.from(selectedRows || window.pmsRowsNearViewport(document.querySelectorAll('#performanceTable tbody tr[data-row-id]')))
        .filter(row => row.style.display !== 'none' && !row.classList.contains('d-none')
          && !row.querySelector('td[data-dynamic-section="summary"]')).forEach((row) => {
        const officeEntries = getAssignedOfficesForRow(row);
        const offices = officeEntries.length > 0
          ? officeEntries
          : [{ id: currentOfficeId || null, name: 'Office' }];
        const groupBreakIndices = getInputBreakIndicesForRow(row);
        const groupPenroFlags = getInputGroupPenroFlagsForRow(row);
        const summaryAggregateLines = getSummaryAggregateLines(
          offices,
          groupBreakIndices,
          groupPenroFlags
        );

        summarySections.forEach((section) => {
          summaryPeriodsForSection(section).forEach((period, periodIndex) => {
            const cell = document.createElement('td');
            cell.className = 'p-1 text-center dynamic-cell-summary';
            if (periodIndex === 0) cell.classList.add('summary-section-start');
            cell.dataset.dynamicSection = 'summary';
            cell.dataset.summaryKind = section.key;
            cell.dataset.summaryPeriod = period.key;

            if (section.key.startsWith('financial-')) {
              cell.classList.add('financial-summary-detail-empty');
              cell.setAttribute('aria-hidden', 'true');
              insertBeforeRemarks(row, cell);
              return;
            }

            let aggregateLineIndex = 0;
            const wrapper = buildAlignedOfficeLines({
              officeEntries: offices,
              groupBreakIndices,
              groupPenroFlags,
              spacerFactory: () => {
                const aggregate = summaryAggregateLines[aggregateLineIndex++];
                return createSummaryInput(section, period, { aggregate });
              },
              renderOfficeInput: office => createSummaryInput(section, period, {
                officeId: office?.id,
              }),
            });

            cell.appendChild(wrapper);
            insertBeforeRemarks(row, cell);
          });
        });
      });

      addDefaultFinancialSummaryCells();
    };

    const normalizeDefaultOfficeUnit = value => String(value || '')
      .toUpperCase()
      .replace(/\b(PENRO|CENRO|TOTAL)\b/g, '')
      .replace(/\s+/g, ' ')
      .trim();

    const officeIdsForDefaultFinancialUnit = (row, officeUnit) => {
      const normalizedOfficeUnit = normalizeDefaultOfficeUnit(officeUnit);
      const offices = getAssignedOfficesForRow(row);

      if (normalizedOfficeUnit === 'CAR') {
        return offices.map(office => String(office?.id || '')).filter(Boolean);
      }

      if (normalizedOfficeUnit === 'RO') {
        return offices
          .filter(office => normalizeDefaultOfficeUnit(office?.name) === 'RO')
          .map(office => String(office?.id || ''))
          .filter(Boolean);
      }

      const sortedBreaks = getInputBreakIndicesForRow(row)
        .map(index => Number(index))
        .filter(index => Number.isInteger(index) && index >= 0)
        .sort((left, right) => left - right);
      const groupRanges = [];
      let rangeStart = 0;

      sortedBreaks.forEach((breakIndex) => {
        if (breakIndex >= rangeStart && breakIndex < offices.length) {
          groupRanges.push({ start: rangeStart, end: breakIndex });
          rangeStart = breakIndex + 1;
        }
      });
      if (rangeStart < offices.length) {
        groupRanges.push({ start: rangeStart, end: offices.length - 1 });
      }

      const groupNames = String(row.dataset.officeNames || '')
        .split('|')
        .map(normalizeDefaultOfficeUnit);
      const groupPenroFlags = getInputGroupPenroFlagsForRow(row);
      const groupIndex = groupNames.findIndex((name, index) => (
        Boolean(groupPenroFlags[index]) && name === normalizedOfficeUnit
      ));
      const groupRange = groupRanges[groupIndex];
      if (!groupRange) return [];

      return offices
        .slice(groupRange.start, groupRange.end + 1)
        .map(office => String(office?.id || ''))
        .filter(Boolean);
    };

    const defaultFinancialUnitValue = (defaultRow, sectionKey, periodKey, officeUnit) => {
      const coreKey = String(defaultRow.dataset.coreKey || '');
      const section = summarySections.find(item => item.key === sectionKey);
      const period = summaryPeriodsForSection(section || {}).find(item => item.key === periodKey);
      if (!section || !period) return 0;

      const programRows = Array.from(document.querySelectorAll('#performanceTable tbody tr[data-row-id]'))
        .filter(row => String(row.dataset.coreKey || '') === coreKey);

      return programRows.reduce((total, row) => {
        const officeIds = officeIdsForDefaultFinancialUnit(row, officeUnit);
        return officeIds.length > 0
          ? total + summaryAggregateValue(row, section, officeIds, period)
          : total;
      }, 0);
    };

    const createDefaultFinancialSummaryInput = (section, period, officeUnit, isProvince) => {
      const input = document.createElement('input');
      input.type = section.isPercentage ? 'text' : 'number';
      input.className = 'month-box summary-box program-financial-summary-box';
      input.style.width = '100%';
      input.value = section.isPercentage ? '0.00%' : '0';
      input.readOnly = true;
      input.setAttribute('aria-readonly', 'true');
      input.setAttribute('aria-label', `${officeUnit} ${section.label} ${period.label}`);
      input.dataset.summaryKind = section.key;
      input.dataset.summaryPeriod = period.key;
      input.dataset.defaultFinancialUnit = officeUnit;

      if (officeUnit === 'CAR') input.classList.add('car-total-box');
      if (isProvince) input.classList.add('group-total-box');

      return input;
    };

    const addDefaultFinancialSummaryCells = () => {
      document.querySelectorAll('#performanceTable tbody tr.default-office-unit-row').forEach((row) => {
        if (row.querySelector('td[data-dynamic-section="summary"]')) return;

        const officeUnits = Array.from(row.querySelectorAll('[data-default-office-unit]'))
          .map(label => String(label.dataset.defaultOfficeUnit || '').trim())
          .filter(Boolean);

        summarySections.forEach((section) => {
          summaryPeriodsForSection(section).forEach((period, periodIndex) => {
            const cell = document.createElement('td');
            cell.className = 'p-1 text-center dynamic-cell-summary';
            if (periodIndex === 0) cell.classList.add('summary-section-start');
            cell.dataset.dynamicSection = 'summary';
            cell.dataset.summaryKind = section.key;
            cell.dataset.summaryPeriod = period.key;

            if (section.key.startsWith('financial-')) {
              const wrapper = document.createElement('div');
              wrapper.className = 'office-lines';

              officeUnits.forEach((officeUnit) => {
                const line = document.createElement('div');
                line.className = 'input-line';
                const isProvince = !['CAR', 'RO'].includes(officeUnit);
                line.appendChild(createDefaultFinancialSummaryInput(
                  section,
                  period,
                  officeUnit,
                  isProvince
                ));
                wrapper.appendChild(line);
              });

              cell.appendChild(wrapper);
            } else {
              cell.setAttribute('aria-hidden', 'true');
            }

            insertBeforeRemarks(row, cell);
          });
        });
      });
    };

    const refreshDefaultFinancialSummaryInputs = () => {
      document.querySelectorAll('#performanceTable .program-financial-summary-box').forEach((input) => {
        const defaultRow = input.closest('tr.default-office-unit-row');
        if (!defaultRow) return;

        const section = summarySections.find(item => item.key === input.dataset.summaryKind);
        const period = summaryPeriodsForSection(section || {}).find(
          item => item.key === input.dataset.summaryPeriod
        );
        if (!section || !period) return;

        if (section.isFinancialPercentage) {
          const numerator = defaultFinancialUnitValue(
            defaultRow,
            period.numeratorSection,
            period.numeratorPeriod,
            input.dataset.defaultFinancialUnit
          );
          const denominator = defaultFinancialUnitValue(
            defaultRow,
            period.denominatorSection,
            period.denominatorPeriod,
            input.dataset.defaultFinancialUnit
          );
          input.value = formatSummaryPercentage(
            denominator > 0 ? (numerator / denominator) * 100 : 0
          );
          return;
        }

        input.value = defaultFinancialUnitValue(
          defaultRow,
          section.key,
          period.key,
          input.dataset.defaultFinancialUnit
        );
      });
    };

    window.refreshSummaryInputs = function () {
      if (!summaryVisible) return;

      document.querySelectorAll('#performanceTable tbody tr[data-row-id]').forEach((row) => {
        row.querySelectorAll('.month-box.summary-box').forEach((input) => {
          const section = summarySections.find(item => item.key === input.dataset.summaryKind);
          const period = summaryPeriodsForSection(section || {}).find(
            item => item.key === input.dataset.summaryPeriod
          );
          const aggregateOfficeIds = String(input.dataset.summaryOfficeIds || '')
            .split(',')
            .map(value => value.trim())
            .filter(Boolean);
          const officeId = String(input.dataset.officeId || '').trim();
          if (!section || !period) {
            input.value = 0;
            return;
          }

          if (section.isFinancialPercentage) {
            input.value = formatSummaryPercentage(financialBurValue(
              row,
              period,
              officeId,
              aggregateOfficeIds
            ));
            return;
          }

          if (section.isPercentage) {
            input.value = formatSummaryPercentage(physicalPercentageValue(
              row,
              period,
              officeId,
              aggregateOfficeIds
            ));
            return;
          }

          if (input.dataset.summaryAggregate) {
            input.value = summaryAggregateValue(row, section, aggregateOfficeIds, period);
            return;
          }

          if (!officeId) {
            input.value = 0;
            return;
          }

          input.value = summaryValue(row, section, officeId, period);
        });
      });

      refreshDefaultFinancialSummaryInputs();
    };

    window.toggleSummaryColumns = function () {
      const table = document.getElementById('performanceTable');
      const headerRow = table.querySelector('thead tr:not(.group-row)');
      const groupRow = document.getElementById('groupHeaders');
      const hadVisibleSection = summaryVisible || targetsVisible || financialVisible || accompVisible || pendingVisible;

      if (!summaryVisible) {
        summaryVisible = true;
        if (!hadVisibleSection) monthInputsVisible = false;
        document.getElementById('summaryBtn').innerHTML = '<i class="fa fa-eye-slash me-1"></i> Hide Summary';
        document.getElementById('summaryBtn').classList.replace('btn-info', 'btn-outline-info');
        addSummaryHeaders(headerRow, groupRow);
        showPhysicalPerformanceTitleRow(table, headerRow, groupRow);
        addSummaryCells();
      } else {
        summaryVisible = false;
        document.getElementById('summaryBtn').innerHTML = '<i class="fa fa-chart-bar me-1"></i> Summary';
        document.getElementById('summaryBtn').classList.replace('btn-outline-info', 'btn-info');
        removePhysicalPerformanceTitleRow(table);
        removeSectionColumns(groupRow, headerRow, 'summary');
        table.querySelectorAll('tbody td[data-dynamic-section="summary"]')
          .forEach(cell => cell.remove());
        groupRow.querySelectorAll('.group-summary').forEach(group => group.remove());
        if (groupRow.querySelectorAll('.group-header').length === 0) {
          groupRow.replaceChildren();
        }
      }

      refreshMonthButtonState();
      refreshSummaryCards();
    };

    const entryChanged = (entry, kind) => {
      const stored = storedEntry(entry, kind);

      return periodKeys.some((key) => numericValue(entry?.[key]) !== numericValue(stored?.[key]));
    };

    const collectChangedEntries = (section, kind) => {
      if (typeof collectSectionEntries !== 'function') return [];
      return collectSectionEntries(section).filter(entry => entryChanged(entry, kind));
    };

    const rememberSavedEntries = (entries, kind) => {
      const destination = kind === 'accomplishment' ? config.existingAccomplishments : config.existing;

      entries.forEach((entry) => {
        const rowId = String(entry?.row_id || entry?.program_id || '').trim();
        const indicatorId = String(entry?.indicator_id || '').trim();
        const officeId = String(entry?.office_id || '').trim();
        if (!rowId || !indicatorId || !officeId) return;

        destination[rowId] ||= {};
        destination[rowId][indicatorId] ||= {};
        destination[rowId][indicatorId][officeId] = {
          ...(destination[rowId][indicatorId][officeId] || {}),
          ...entry,
          kind,
        };
      });
    };

    const saveEntries = async (entries, kind) => {
      if (!Array.isArray(entries) || entries.length === 0) {
        return { success: true, skipped: true, message: 'No financial rows to save.' };
      }

      const token = document.querySelector('input[name="_token"]')?.value || '';
      const payloadEntries = entries.map(entry => ({ ...entry, kind }));

      try {
        const response = await fetch(config.storeUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
          },
          body: JSON.stringify({ entries: payloadEntries }),
        });
        const data = await response.json();

        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Failed to save financial inputs.');
        }

        if (!data.pending_approval) {
          rememberSavedEntries(payloadEntries, kind);
        }
        return {
          success: true,
          pending_approval: Boolean(data.pending_approval),
          message: data.message || 'Financial inputs saved.',
        };
      } catch (error) {
        console.error('Financial input save error:', error);
        return { success: false, message: error?.message || 'Error saving financial inputs.' };
      }
    };

    const savePhysicalAccomplishments = async (entries) => {
      if (!Array.isArray(entries) || entries.length === 0) {
        return { success: true, skipped: true, message: 'No physical accomplishment rows to save.' };
      }

      const token = document.querySelector('input[name="_token"]')?.value || '';

      try {
        const response = await fetch(accompStoreUrl, {
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
          throw new Error(data.message || 'Failed to save physical accomplishments.');
        }

        if (!data.pending_approval && typeof applySavedEntriesToExisting === 'function') {
          applySavedEntriesToExisting('accomp', entries);
        }

        return {
          success: true,
          pending_approval: Boolean(data.pending_approval),
          message: data.message || 'Physical accomplishments saved.',
        };
      } catch (error) {
        console.error('Physical accomplishment save error:', error);
        return { success: false, message: error?.message || 'Error saving physical accomplishments.' };
      }
    };

    const hydrateInputs = (section, source, selectedRows = null) => {
      Array.from(selectedRows || document.querySelectorAll('#performanceTable tbody tr[data-row-id]')).forEach((row) => {
        const rowId = String(row.dataset.rowId || row.dataset.programId || '').trim();
        const indicatorId = String(row.dataset.indicatorId || '').trim();
        if (!rowId || !indicatorId) return;

        row.querySelectorAll(`.month-box[data-section="${section}"]`).forEach((input) => {
          if (input.dataset.carTotal === '1' || input.dataset.groupTotal === '1') return;

          const officeId = String(input.dataset.officeId || '').trim();
          const periodKey = periodKeys[Number(input.dataset.col)] || '';
          const value = source?.[rowId]?.[indicatorId]?.[officeId]?.[periodKey];
          if (value !== undefined && value !== null) input.value = value;
        });
      });
    };

    const recalculateFinancialAccomplishmentRow = (row) => {
      const section = 'financial-accomp';
      const editableInputs = Array.from(row.querySelectorAll(`.month-box[data-section="${section}"]`))
        .filter(input => input.dataset.carTotal !== '1' && input.dataset.groupTotal !== '1');
      const officeIds = Array.from(new Set(
        editableInputs.map(input => String(input.dataset.officeId || '')).filter(Boolean)
      ));

      officeIds.forEach((officeId) => {
        const inputs = editableInputs.filter(input => String(input.dataset.officeId || '') === officeId);
        const byColumn = new Map(inputs.map(input => [Number(input.dataset.col), input]));
        const monthValue = index => numericValue(byColumn.get(index)?.value);
        const totals = {
          3: monthValue(0) + monthValue(1) + monthValue(2),
          7: monthValue(4) + monthValue(5) + monthValue(6),
          11: monthValue(8) + monthValue(9) + monthValue(10),
          15: monthValue(12) + monthValue(13) + monthValue(14),
        };
        totals[16] = totals[3] + totals[7] + totals[11] + totals[15];

        Object.entries(totals).forEach(([column, value]) => {
          const input = byColumn.get(Number(column));
          if (input) input.value = value;
        });
      });

      const groupInputs = Array.from(row.querySelectorAll(
        `.month-box[data-section="${section}"][data-group-total="1"]`
      ));
      const groupedInputs = groupInputs.reduce((groups, input) => {
        const groupKey = String(input.dataset.groupKey || '').trim();
        if (!groupKey) return groups;
        if (!groups.has(groupKey)) groups.set(groupKey, []);
        groups.get(groupKey).push(input);
        return groups;
      }, new Map());

      groupedInputs.forEach((inputs) => {
        const groupOfficeIds = new Set(
          String(inputs[0]?.dataset.groupOfficeIds || '')
            .split(',')
            .map(value => value.trim())
            .filter(Boolean)
        );
        const byColumn = new Map(inputs.map(input => [Number(input.dataset.col), input]));
        const summedMonth = column => Array.from(groupOfficeIds).reduce((sum, officeId) => {
          const officeInput = editableInputs.find(candidate =>
            String(candidate.dataset.officeId || '') === officeId
              && Number(candidate.dataset.col) === column
          );
          return sum + numericValue(officeInput?.value);
        }, 0);
        const totals = {
          0: summedMonth(0), 1: summedMonth(1), 2: summedMonth(2),
          4: summedMonth(4), 5: summedMonth(5), 6: summedMonth(6),
          8: summedMonth(8), 9: summedMonth(9), 10: summedMonth(10),
          12: summedMonth(12), 13: summedMonth(13), 14: summedMonth(14),
        };
        totals[3] = totals[0] + totals[1] + totals[2];
        totals[7] = totals[4] + totals[5] + totals[6];
        totals[11] = totals[8] + totals[9] + totals[10];
        totals[15] = totals[12] + totals[13] + totals[14];
        totals[16] = totals[3] + totals[7] + totals[11] + totals[15];

        Object.entries(totals).forEach(([column, value]) => {
          const input = byColumn.get(Number(column));
          if (input) input.value = value;
        });
      });

      const carInputs = Array.from(row.querySelectorAll(
        `.month-box[data-section="${section}"][data-car-total="1"]`
      ));
      const carByColumn = new Map(carInputs.map(input => [Number(input.dataset.col), input]));
      const summedCarMonth = column => officeIds.reduce((sum, officeId) => {
        const officeInput = editableInputs.find(input =>
          String(input.dataset.officeId || '') === officeId && Number(input.dataset.col) === column
        );
        return sum + numericValue(officeInput?.value);
      }, 0);
      const carTotals = {
        0: summedCarMonth(0), 1: summedCarMonth(1), 2: summedCarMonth(2),
        4: summedCarMonth(4), 5: summedCarMonth(5), 6: summedCarMonth(6),
        8: summedCarMonth(8), 9: summedCarMonth(9), 10: summedCarMonth(10),
        12: summedCarMonth(12), 13: summedCarMonth(13), 14: summedCarMonth(14),
      };
      carTotals[3] = carTotals[0] + carTotals[1] + carTotals[2];
      carTotals[7] = carTotals[4] + carTotals[5] + carTotals[6];
      carTotals[11] = carTotals[8] + carTotals[9] + carTotals[10];
      carTotals[15] = carTotals[12] + carTotals[13] + carTotals[14];
      carTotals[16] = carTotals[3] + carTotals[7] + carTotals[11] + carTotals[15];

      Object.entries(carTotals).forEach(([column, value]) => {
        const input = carByColumn.get(Number(column));
        if (input) input.value = value;
      });
    };

    const recalculateFinancialAccomplishments = (sourceRow = null) => {
      let rows = Array.from(document.querySelectorAll('#performanceTable tbody tr[data-row-id]'));

      if (sourceRow) {
        const coreKey = String(sourceRow.dataset.coreKey || '');
        rows = coreKey
          ? rows.filter(row => String(row.dataset.coreKey || '') === coreKey)
          : [sourceRow];
      }

      rows.forEach(recalculateFinancialAccomplishmentRow);
      if (typeof refreshSummaryCards === 'function') refreshSummaryCards();
    };

    const hydrateFinancialTarget = () => {
      hydrateInputs('financial', config.existing);

      if (typeof recalculateSectionRows === 'function') recalculateSectionRows('financial');
      if (typeof recalculateCarTotalsForSection === 'function') recalculateCarTotalsForSection('financial');
      if (typeof refreshSummaryCards === 'function') refreshSummaryCards();
    };

    const hydrateFinancialAccomplishments = () => {
      hydrateInputs('financial-accomp', config.existingAccomplishments);
      recalculateFinancialAccomplishments();
    };

    let financialTargetVisible = false;
    let financialAccompVisible = false;

    const syncFinancialVisibility = () => {
      financialVisible = financialTargetVisible || financialAccompVisible;
    };

    const refreshFinancialButton = (id, visible, label, icon) => {
      const button = document.getElementById(id);
      if (!button) return;
      button.innerHTML = visible
        ? `<i class="fa fa-eye-slash me-1"></i> Hide ${label}`
        : `<i class="fa ${icon} me-1"></i> ${label}`;
    };

    if (typeof toggleFinancialColumns === 'function') {
      toggleFinancialColumns = function () {
        const table = document.getElementById('performanceTable');
        const headerRow = table.querySelector('thead tr:not(.group-row)');
        const groupRow = document.getElementById('groupHeaders');
        const hadVisibleSection = targetsVisible || financialVisible || accompVisible || pendingVisible;

        if (!financialTargetVisible) {
          financialTargetVisible = true;
          if (!hadVisibleSection) monthInputsVisible = false;
          addColumns(headerRow, groupRow, 'Financial Target', 'financial');
          addInputCells('financial');
          hydrateFinancialTarget();
        } else {
          financialTargetVisible = false;
          removeSectionColumns(groupRow, headerRow, 'financial');
        }

        syncFinancialVisibility();
        refreshFinancialButton('financialBtn', financialTargetVisible, 'Target', 'fa-bullseye');
        refreshMonthButtonState();
        refreshSummaryCards();
      };
    }

    window.toggleFinancialAccomplishmentColumns = function () {
      const table = document.getElementById('performanceTable');
      const headerRow = table.querySelector('thead tr:not(.group-row)');
      const groupRow = document.getElementById('groupHeaders');
      const hadVisibleSection = targetsVisible || financialVisible || accompVisible || pendingVisible;

      if (!financialAccompVisible) {
        financialAccompVisible = true;
        if (!hadVisibleSection) monthInputsVisible = false;
        addColumns(headerRow, groupRow, 'Financial Accomplishment', 'financial-accomp');

        const annualHeader = headerRow.querySelector(
          'th[data-dynamic-section="financial-accomp"][data-period-type="annual"]'
        );
        if (annualHeader) annualHeader.innerHTML = 'Grand<div class="tiny-period">Total</div>';

        addInputCells('financial-accomp');
        hydrateFinancialAccomplishments();
      } else {
        financialAccompVisible = false;
        removeSectionColumns(groupRow, headerRow, 'financial-accomp');
      }

      syncFinancialVisibility();
      refreshFinancialButton(
        'financialAccompBtn',
        financialAccompVisible,
        'Accomplishment',
        'fa-list-check'
      );
      refreshMonthButtonState();
      refreshSummaryCards();
    };

    const pendingMonthColumn = () => getCurrentMonthPeriodIndex();
    const pendingOffices = row => {
      const assigned = getAssignedOfficesForRow(row);
      return assigned.length > 0 ? assigned : [{ id: currentOfficeId || null, name: 'Office' }];
    };
    const pendingSectionValue = (row, section, officeId) => {
      const summarySection = summarySections.find(item => item.inputSection === section);
      return summarySection ? summaryColumnValue(row, summarySection, officeId, pendingMonthColumn()) : 0;
    };
    const financialPendingTotal = (row, section) => pendingOffices(row).reduce((total, office) => {
      const officeId = String(office?.id || '').trim();
      return total + (officeId ? pendingSectionValue(row, section, officeId) : 0);
    }, 0);
    const financialSourcePendingAmount = row => {
      const target = financialPendingTotal(row, 'financial');
      const accomplishment = financialPendingTotal(row, 'financial-accomp');
      return target > 0 && target > accomplishment ? target - accomplishment : 0;
    };
    const financialPendingAmount = row => {
      const targetInput = row?.querySelector('.financial-pending-box[data-pending-kind="target"][data-car-total="1"]');
      const accomplishmentInput = row?.querySelector('.financial-pending-box[data-pending-kind="accomp"][data-car-total="1"]');
      if (!targetInput || !accomplishmentInput) return financialSourcePendingAmount(row);
      const target = numericValue(targetInput.value);
      const accomplishment = numericValue(accomplishmentInput.value);
      return target > 0 && target > accomplishment ? target - accomplishment : 0;
    };
    const originalPhysicalPendingRowMatcher = rowHasCurrentMonthPending;
    rowHasCurrentMonthPending = function (row) {
      if (!row) return false;
      return originalPhysicalPendingRowMatcher(row)
        || financialPendingAmount(row) > 0;
    };

    const addFinancialPendingColumns = () => {
      const table = document.getElementById('performanceTable');
      const mainHeader = table.querySelector('thead tr:not(.group-row)');
      const groupRow = document.getElementById('groupHeaders');
      const physicalGroup = groupRow.querySelector('.group-pending');
      if (!physicalGroup) return;
      if (!groupRow.querySelector('.group-financial-pending')) {
      physicalGroup.textContent = 'Physical Pending';
      const financialGroup = physicalGroup.cloneNode(true);
      financialGroup.classList.add('group-financial-pending');
      financialGroup.textContent = 'Financial Pending';
      const remarksGroup = groupRow.querySelector('.group-remarks');
      groupRow.insertBefore(financialGroup, remarksGroup || null);

      mainHeader.querySelectorAll('th[data-dynamic-section="pending"]').forEach(physicalHeader => {
        const header = physicalHeader.cloneNode(true);
        header.classList.add('dynamic-header-financial-pending');
        header.dataset.dynamicSection = 'financial-pending';
        const remarksHeader = mainHeader.querySelector('th[data-dynamic-section="remarks"]');
        mainHeader.insertBefore(header, remarksHeader || null);
      });
      }

      window.pmsRowsNearViewport(document.querySelectorAll('#performanceTable tbody tr[data-row-id]')).forEach(row => {
        if (row.style.display === 'none' || row.classList.contains('d-none')
          || row.querySelector('td[data-dynamic-section="financial-pending"]')) return;
        row.querySelectorAll('td[data-dynamic-section="pending"]').forEach(physicalCell => {
          const section = physicalCell.dataset.pendingKind === 'target' ? 'financial' : 'financial-accomp';
          const cell = physicalCell.cloneNode(true);
          cell.classList.add('dynamic-cell-financial-pending');
          cell.dataset.dynamicSection = 'financial-pending';
          cell.querySelectorAll('.month-box').forEach(input => {
            input.classList.add('financial-pending-box');
            input.value = '0';
            input.dataset.section = 'financial-pending';
            input.dataset.sourceSection = section;
            input.setAttribute('aria-label', section === 'financial' ? 'Financial Target' : 'Financial Accomplishment');
          });
          lockTargetInputs(cell);
          const remarksCell = row.querySelector('td[data-dynamic-section="remarks"]');
          row.insertBefore(cell, remarksCell || null);
        });
      });
    };

    const refreshFinancialPendingInputs = () => {
      if (!pendingVisible) return;
      document.querySelectorAll('#performanceTable tbody tr[data-row-id]').forEach(row => {
        const hasFinancialPending = financialSourcePendingAmount(row) > 0;
        row.querySelectorAll('.financial-pending-box').forEach(input => {
          const section = input.dataset.sourceSection;
          const officeId = String(input.dataset.officeId || '').trim();
          input.value = hasFinancialPending
            ? (input.dataset.carTotal === '1'
                ? financialPendingTotal(row, section)
                : pendingSectionValue(row, section, officeId))
            : 0;
        });
      });
    };

    document.getElementById('performanceTable')?.addEventListener('input', event => {
      const input = event.target;
      if (input?.dataset?.section !== 'financial-pending') return;
      event.stopPropagation();
      if (input.readOnly) return;
      const row = input.closest('tr[data-row-id]');
      if (!row) return;
      const sectionInputs = Array.from(row.querySelectorAll(
        `.financial-pending-box[data-source-section="${input.dataset.sourceSection}"]`
      ));
      const carInput = sectionInputs.find(candidate => candidate.dataset.carTotal === '1');
      if (carInput) {
        carInput.value = sectionInputs
          .filter(candidate => candidate.dataset.carTotal !== '1' && candidate.dataset.groupTotal !== '1')
          .reduce((total, candidate) => total + numericValue(candidate.value), 0);
      }
      applyPendingRowFilter();
    }, true);

    const originalRefreshSummaryCards = refreshSummaryCards;
    refreshSummaryCards = function () {
      originalRefreshSummaryCards();
      refreshFinancialPendingInputs();
      scheduleTargetMissBorderRefresh();
      if (pendingVisible) applyPendingRowFilter();
    };

    const originalTogglePendingColumns = togglePendingColumns;
    togglePendingColumns = function () {
      const opening = !pendingVisible;
      originalTogglePendingColumns();
      if (opening) {
        addFinancialPendingColumns();
        refreshFinancialPendingInputs();
        applyPendingRowFilter();
      } else {
        document.querySelectorAll('#performanceTable [data-dynamic-section="financial-pending"], #performanceTable .group-financial-pending')
          .forEach(cell => cell.remove());
      }
      refreshGroupHeaderColspans();
    };

    document.getElementById('performanceTable')?.addEventListener('input', event => {
      const input = event.target;
      if (!input.classList?.contains('month-box') || input.dataset.section !== 'financial-accomp') return;
      const row = input.closest('tr[data-row-id]');
      if (row) recalculateFinancialAccomplishments(row);
    });

    // Collapsed PAP groups keep their records in the existing JSON data. Only
    // expanded rows need thousands of editable DOM nodes.
    const initializeVisiblePerformanceInputs = () => {
      const table = document.getElementById('performanceTable');
      if (!table) return;
      const rows = window.pmsRowsNearViewport(table.querySelectorAll('tbody tr[data-row-id]'));
      if (rows.length === 0) return;
      let changed = false;

      for (const section of ['target', 'accomp', 'financial', 'financial-accomp', 'pending']) {
        if (!table.querySelector(`th[data-dynamic-section="${section}"]`)) continue;
        const newRows = rows.filter(row => !row.querySelector(`td[data-dynamic-section="${section}"]`));
        if (newRows.length === 0) continue;
        changed = true;
        addInputCells(section, newRows);
        if (section === 'financial') {
          hydrateInputs(section, config.existing, newRows);
          recalculateSectionRows(section);
          recalculateCarTotalsForSection(section);
        } else if (section === 'financial-accomp') {
          hydrateInputs(section, config.existingAccomplishments, newRows);
          newRows.forEach(recalculateFinancialAccomplishmentRow);
        }
      }
      if (summaryVisible && rows.some(row => !row.querySelector('td[data-dynamic-section="summary"]'))) {
        addSummaryCells(rows);
        changed = true;
      }
      if (pendingVisible && rows.some(row => !row.querySelector('td[data-dynamic-section="financial-pending"]'))) {
        addFinancialPendingColumns();
        changed = true;
      }
      if (!changed) return;
      refreshMonthButtonState();
      refreshSummaryCards();
    };
    let visibleInputsQueued = false;
    const scheduleVisiblePerformanceInputs = () => {
      if (visibleInputsQueued) return;
      visibleInputsQueued = true;
      requestAnimationFrame(() => {
        visibleInputsQueued = false;
        initializeVisiblePerformanceInputs();
      });
    };
    const performanceBody = document.querySelector('#performanceTable tbody');
    if (performanceBody) {
      new MutationObserver(mutations => {
        if (mutations.some(mutation => mutation.target.matches('tr[data-row-id]')
          && mutation.target.style.display !== 'none')) scheduleVisiblePerformanceInputs();
      }).observe(performanceBody, { attributes: true, attributeFilter: ['style', 'class'], subtree: true });
      document.getElementById('performanceTable').closest('.table-container')
        ?.addEventListener('scroll', scheduleVisiblePerformanceInputs, { passive: true });
      window.addEventListener('scroll', scheduleVisiblePerformanceInputs, { passive: true });
      window.addEventListener('resize', scheduleVisiblePerformanceInputs, { passive: true });
    }

    if (typeof saveAllSectionEntries === 'function') {
      saveAllSectionEntries = async function () {
        const saveAllBtn = document.getElementById('saveAllBtn');
        if (PMSButtonLoading.isBusy(saveAllBtn)) return;
        const targetEntries = config.accomplishmentsOnly ? [] : collectChangedTargetEntries();
        let accompEntries = attachTouchedPeriods(collectChangedAccomplishmentEntries(), 'accomp');
        const financialTargetEntries = config.accomplishmentsOnly ? [] : collectChangedEntries('financial', 'target');
        let financialAccompEntries = attachTouchedPeriods(
          collectChangedEntries('financial-accomp', 'accomplishment'),
          'financial-accomp',
        );

        if (targetEntries.length === 0 && accompEntries.length === 0
          && financialTargetEntries.length === 0 && financialAccompEntries.length === 0) {
          showTopRightErrorAlert('No input rows available to save.');
          return;
        }

        const hasLockedChanges = [...accompEntries, ...financialAccompEntries]
          .some(entryChangesLockedMonth);
        if (hasLockedChanges && config.canRequestLockedChanges) {
          accompEntries = addLockedChangeReasons(accompEntries, 'accomp');
          financialAccompEntries = addLockedChangeReasons(financialAccompEntries, 'financial-accomp');

          const missingReason = [...accompEntries, ...financialAccompEntries]
            .some(entry => entryChangesLockedMonth(entry) && !String(entry.change_reason || '').trim());
          if (missingReason) {
            showTopRightErrorAlert('Please enter a reason in the locked-accomplishment modal before saving.');
            return;
          }
        }

        PMSButtonLoading.start(saveAllBtn);

        try {
          const results = await Promise.all([
            saveSectionEntries('target', {
              requireVisible: false,
              showAlerts: false,
              precomputedEntries: targetEntries,
            }),
            savePhysicalAccomplishments(accompEntries),
            saveEntries(financialTargetEntries, 'target'),
            saveEntries(financialAccompEntries, 'accomplishment'),
          ]);

          if (results.some(result => !result.success)) {
            showTopRightErrorAlert('Some entries failed to save. Please try again.');
            return;
          }

          if (results.every(result => result.skipped)) {
            showTopRightErrorAlert('No input rows available to save.');
            return;
          }

          const hasPendingApproval = results.some(result => result.pending_approval);
          showTopRightSuccessAlert(hasPendingApproval
            ? 'Locked-month change request submitted for Regional Office/admin approval.'
            : 'Data saved successfully.');
        } finally {
          PMSButtonLoading.stop(saveAllBtn);
        }
      };
    }
  })();
</script>
