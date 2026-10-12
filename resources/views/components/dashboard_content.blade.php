<!-- Section Header -->
      <div class="dashboard-heading flex flex-col sm:flex-row sm:items-center justify-between animate-fade-in">
        <div>
          <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Performance Overview</h2>
          <p class="dashboard-description">Review targets, accomplishments, and financial performance across your offices and sectors.</p>
        </div>
      </div>

      @include('components.dashboard_overall_cards')

      <div class="dashboard-comparison-section">
        <p class="dashboard-chart-guide"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span>Bar lengths compare sector volumes within each panel. Percentages below the bars compare accomplishments with targets.</span></p>
        @include('components.dashboard_performance_rankings')
      </div>

      <div class="dashboard-trend-card bg-white overflow-hidden animate-fade-in">
        <div class="dashboard-trend-header border-b border-gray-100 d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
          <div>
            <h3 class="text-xl font-bold text-gray-900 flex items-center gap-3 mb-1">
              <i class="fa-solid fa-triangle-exclamation text-danger"></i> Delays by reporting period
            </h3>
            <p class="text-sm text-gray-500 mb-0">
              Delayed physical inputs for {{ (int) ($year ?? now()->year) }}{{ ($selectedSector ?? 'all') !== 'all' ? ' - ' . strtoupper((string) $selectedSector) : '' }}
            </p>
          </div>
          <div class="btn-group progress-trend-toggle" role="group" aria-label="Progress chart range">
            <button type="button" class="btn btn-outline-primary active" data-progress-view="monthly">Monthly</button>
            <button type="button" class="btn btn-outline-primary" data-progress-view="quarterly">Quarterly</button>
          </div>
        </div>

        <div class="dashboard-trend-body">
          <div id="progressTrendChart" class="progress-trend-chart" aria-label="Monthly delays chart"></div>
          <div class="dashboard-trend-legend d-flex flex-wrap justify-content-between align-items-center mt-3">
            <span><span class="legend-swatch d-inline-block rounded me-2" style="width: 12px; height: 12px; background: #dc2626;"></span>Delayed outputs</span>
            <span>Each bar counts inputs where accomplishment is below target.</span>
          </div>
        </div>
      </div>
<script type="application/json" id="dashboardTrendData">@json($progressTrend ?? ['monthly' => [], 'quarterly' => []])</script>
