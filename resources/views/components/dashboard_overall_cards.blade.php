<div class="dashboard-overall">
  <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-3">
    <h3 class="dashboard-section-heading text-xl font-bold text-gray-800 mb-0 d-flex align-items-center gap-3">
      <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-white bg-primary" style="width: 36px; height: 36px;">
        <i class="fa-solid fa-chart-pie"></i>
      </span>
      Overall Performance
    </h3>

    @include('components.dashboard_filters')
  </div>

  <div class="dashboard-summary-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="dashboard-summary-card bg-white rounded-2xl shadow-lg p-6 border border-primary-subtle position-relative overflow-hidden group hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
      <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background: #2563eb;"></div>
      <div class="d-flex align-items-center justify-content-between mb-4">
        <p class="dashboard-summary-label text-md text-black font-bold uppercase tracking-wide mb-0">Overall Progress</p>
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-primary" style="width: 38px; height: 38px; background: #eff6ff;">
          <i class="fa-solid fa-gauge-high"></i>
        </span>
      </div>
      <div class="flex items-center justify-center">
        <div class="dashboard-summary-ring relative w-28 h-28">
          <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="44" stroke="#e5e7eb" stroke-width="12" fill="none" />
            <circle cx="50" cy="50" r="44" stroke="#2563eb" stroke-width="12" stroke-dasharray="276.46" stroke-dashoffset="{{ 276.46 * (1 - ((float) ($overallProgress ?? 0) / 100)) }}" stroke-linecap="round" fill="none" />
          </svg>
          <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-4xl font-extrabold text-blue-600">{{ number_format((float) ($overallProgress ?? 0), 0) }}%</span>
          </div>
        </div>
      </div>
    </div>

    <button type="button" class="dashboard-summary-card bg-white rounded-2xl shadow-lg p-6 border border-success-subtle position-relative overflow-hidden group hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 text-start w-100"
      data-bs-toggle="modal" data-bs-target="#papListModal" aria-label="View all PAP records">
      <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background: #059669;"></div>
      <div class="d-flex align-items-center justify-content-between mb-4">
        <p class="dashboard-summary-label text-md text-black font-bold uppercase tracking-wide mb-0">PAP Records</p>
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-success" style="width: 38px; height: 38px; background: #ecfdf5;">
          <i class="fa-solid fa-folder-tree"></i>
        </span>
      </div>
      <div class="d-flex align-items-end justify-content-center gap-3">
        <p class="dashboard-summary-value text-7xl font-extrabold text-emerald-600 mb-0">{{ number_format((float) ($totalPap ?? 0), 0) }}</p>
      </div>
      <p class="dashboard-summary-note">Programs, activities &amp; projects <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></p>
    </button>

    <button type="button" class="dashboard-summary-card bg-white rounded-2xl shadow-lg p-6 border border-info-subtle position-relative overflow-hidden group hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 text-start w-100"
      data-bs-toggle="modal" data-bs-target="#indicatorListModal" aria-label="View all indicator records">
      <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background: #0891b2;"></div>
      <div class="d-flex align-items-center justify-content-between mb-4">
        <p class="dashboard-summary-label text-md text-black font-bold uppercase tracking-wide mb-0">Indicators</p>
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-info" style="width: 38px; height: 38px; background: #ecfeff;">
          <i class="fa-solid fa-list-check"></i>
        </span>
      </div>
      <div class="d-flex align-items-end justify-content-center gap-3">
        <p class="dashboard-summary-value text-7xl font-extrabold text-blue-700 mb-0">{{ number_format((float) ($totalIndicators ?? 0), 0) }}</p>
      </div>
      <p class="dashboard-summary-note">View indicator records <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></p>
    </button>

    <div class="dashboard-summary-card bg-white rounded-2xl shadow-lg p-6 border border-warning-subtle position-relative overflow-hidden group hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
      <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background: #f59e0b;"></div>
      <div class="d-flex align-items-center justify-content-between mb-4">
        <p class="dashboard-summary-label text-md text-black font-bold uppercase tracking-wide mb-0">Financial Utilization</p>
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-warning" style="width: 38px; height: 38px; background: #fffbeb;">
          <i class="fa-solid fa-coins"></i>
        </span>
      </div>
      <div class="flex items-center justify-center">
        <div class="dashboard-summary-ring relative w-28 h-28">
          <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="44" stroke="#e5e7eb" stroke-width="12" fill="none" />
            <circle cx="50" cy="50" r="44" stroke="#f59e0b" stroke-width="12" stroke-dasharray="276.46" stroke-dashoffset="{{ is_numeric($financialUtilization ?? null) ? 276.46 * (1 - ((float) $financialUtilization / 100)) : 276.46 }}" stroke-linecap="round" fill="none" />
          </svg>
          <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-4xl font-extrabold text-amber-500">{{ is_numeric($financialUtilization ?? null) ? number_format((float) $financialUtilization, 0) . '%' : 'N/A' }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="indicatorListModal" tabindex="-1" aria-labelledby="indicatorListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header text-white" style="background: #0891b2;">
          <div>
            <h5 class="modal-title fw-bold" id="indicatorListModalLabel">Indicator List</h5>
            <p class="mb-0 small text-white-50">
              {{ number_format((float) ($totalIndicators ?? 0), 0) }} record{{ (int) ($totalIndicators ?? 0) === 1 ? '' : 's' }}
              for {{ (int) ($year ?? now()->year) }}{{ ($selectedSector ?? 'all') !== 'all' ? ' - ' . strtoupper((string) $selectedSector) : '' }}
            </p>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-0" data-dashboard-list="indicator" aria-live="polite">
          <p class="p-5 mb-0 text-center text-muted">Loading records...</p>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="papListModal" tabindex="-1" aria-labelledby="papListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header text-white" style="background: #059669;">
          <div>
            <h5 class="modal-title fw-bold" id="papListModalLabel">P/A/P List</h5>
            <p class="mb-0 small text-white-50">
              {{ number_format((float) ($totalPap ?? 0), 0) }} record{{ (int) ($totalPap ?? 0) === 1 ? '' : 's' }}
              for {{ (int) ($year ?? now()->year) }}{{ ($selectedSector ?? 'all') !== 'all' ? ' - ' . strtoupper((string) $selectedSector) : '' }}
            </p>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-0" data-dashboard-list="pap" aria-live="polite">
          <p class="p-5 mb-0 text-center text-muted">Loading records...</p>
        </div>
      </div>
    </div>
  </div>

  <style>
    #papListModal .dashboard-link-row,
    #indicatorListModal .dashboard-link-row {
      cursor: pointer;
    }

    #papListModal .dashboard-link-row:focus,
    #indicatorListModal .dashboard-link-row:focus {
      outline: 2px solid #059669;
      outline-offset: -2px;
    }

    #papListModal .pap-sector-heading td {
      background: #f0fdf4;
      border-bottom-color: #d1fae5;
    }

    #papListModal .pap-sector-toggle {
      align-items: center;
      background: transparent;
      border: 0;
      color: #1f2937;
      display: flex;
      font-weight: 700;
      justify-content: space-between;
      padding: 0.9rem 1.5rem;
      text-align: left;
      width: 100%;
    }

    #papListModal .pap-sector-toggle:hover,
    #papListModal .pap-sector-toggle:focus-visible {
      background: #dcfce7;
      outline: none;
    }

    #papListModal .pap-sector-chevron {
      align-items: center;
      color: #059669;
      display: inline-flex;
      justify-content: center;
      transition: transform 160ms ease;
      width: 1rem;
    }

    #papListModal .pap-sector-toggle[aria-expanded="true"] .pap-sector-chevron {
      transform: rotate(90deg);
    }

    #papListModal .pap-sector-summary {
      color: #64748b;
      font-size: 0.78rem;
      font-weight: 600;
    }

    #papListModal .pap-sector-row td:first-child {
      border-left: 3px solid #86efac;
    }

    #indicatorListModal .indicator-sector-heading td {
      background: #ecfeff;
      border-bottom-color: #cffafe;
    }

    #indicatorListModal .indicator-sector-toggle {
      align-items: center;
      background: transparent;
      border: 0;
      color: #1f2937;
      display: flex;
      font-weight: 700;
      justify-content: space-between;
      padding: 0.9rem 1.5rem;
      text-align: left;
      width: 100%;
    }

    #indicatorListModal .indicator-sector-toggle:hover,
    #indicatorListModal .indicator-sector-toggle:focus-visible {
      background: #cffafe;
      outline: none;
    }

    #indicatorListModal .indicator-sector-chevron {
      align-items: center;
      color: #0891b2;
      display: inline-flex;
      justify-content: center;
      transition: transform 160ms ease;
      width: 1rem;
    }

    #indicatorListModal .indicator-sector-toggle[aria-expanded="true"] .indicator-sector-chevron {
      transform: rotate(90deg);
    }

    #indicatorListModal .indicator-sector-summary {
      color: #64748b;
      font-size: 0.78rem;
      font-weight: 600;
    }

    #indicatorListModal .indicator-sector-row td:first-child {
      border-left: 3px solid #67e8f9;
    }

    @media (max-width: 575.98px) {
      #papListModal .pap-sector-toggle,
      #indicatorListModal .indicator-sector-toggle {
        align-items: flex-start;
        flex-direction: column;
        gap: 0.5rem;
        padding-inline: 1rem;
      }

      #papListModal .pap-sector-summary,
      #indicatorListModal .indicator-sector-summary {
        padding-left: 1.75rem;
      }
    }
  </style>


</div>
