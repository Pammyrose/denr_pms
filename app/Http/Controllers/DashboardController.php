<?php

namespace App\Http\Controllers;

use App\Models\Office;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private array $tableExistsCache = [];
    private array $tableColumnsCache = [];
    private array $monthlySumsCache = [];
    private array $sectorMonthlySumsCache = [];
    private array $physicalRowsCache = [];
    private array $ppaAssignmentRowsCache = [];
    private array $dashboardSectorKeys = [];
    private array $ppaRowIdsByDetailSetCache = [];
    private ?array $ppaDetailChildrenByParent = null;

    public function index(\Illuminate\Http\Request $request)
    {
        $currentYear = (int) now()->year;
        $year = (int) $request->query('year', $currentYear);

        if ($year < 2000 || $year > 2100) {
            $year = $currentYear;
        }

        $selectedSector = strtolower((string) $request->query('sector', 'all'));
        $officeFilter = $this->dashboardOfficeFilter($request);
        $officeScope = $officeFilter['scope'];
        $officeScopeKey = empty($officeScope) ? 'all' : implode('-', $officeScope);

        $fieldConfigs = [
            ['key' => 'gass', 'label' => 'GASS', 'type_code' => 'GASS', 'targets' => 'physical_targets', 'accomp' => 'physical_accomplishments'],
            ['key' => 'sto', 'label' => 'STO', 'type_code' => 'STO', 'targets' => 'physical_targets', 'accomp' => 'physical_accomplishments'],
            ['key' => 'enf', 'label' => 'ENF', 'type_code' => 'ENF', 'targets' => 'physical_targets', 'accomp' => 'physical_accomplishments'],
            ['key' => 'pa', 'label' => 'PA', 'type_code' => 'Biodiv', 'targets' => 'physical_targets', 'accomp' => 'physical_accomplishments'],
            ['key' => 'engp', 'label' => 'ENGP', 'type_code' => 'ENGP', 'targets' => 'physical_targets', 'accomp' => 'physical_accomplishments'],
            ['key' => 'lands', 'label' => 'LANDS', 'type_code' => 'Lands', 'targets' => 'physical_targets', 'accomp' => 'physical_accomplishments'],
            ['key' => 'soilcon', 'label' => 'SOILCON', 'type_code' => 'Soilcon', 'targets' => 'physical_targets', 'accomp' => 'physical_accomplishments'],
            ['key' => 'nra', 'label' => 'NRA', 'type_code' => 'NRA', 'targets' => 'physical_targets', 'accomp' => 'physical_accomplishments'],
            ['key' => 'paria', 'label' => 'PARIA', 'type_code' => 'PARIA', 'targets' => 'physical_targets', 'accomp' => 'physical_accomplishments'],
            ['key' => 'cobb', 'label' => 'COBB', 'type_code' => 'COBB', 'targets' => 'physical_targets', 'accomp' => 'physical_accomplishments'],
            ['key' => 'continuing', 'label' => 'CONTINUING', 'type_code' => 'CONTINUING', 'targets' => 'physical_targets', 'accomp' => 'physical_accomplishments'],
        ];

        $sectorOptions = collect($fieldConfigs)
            ->map(fn (array $config) => [
                'key' => $config['key'],
                'label' => $config['label'],
            ]);

        if ($selectedSector !== 'all' && !$sectorOptions->contains('key', $selectedSector)) {
            $selectedSector = 'all';
        }

        $visibleFieldConfigs = collect($fieldConfigs)
            ->when($selectedSector !== 'all', fn ($configs) => $configs->where('key', $selectedSector))
            ->values();

        $list = $request->header('X-Dashboard-List');
        if (in_array($list, ['pap', 'indicator'], true)) {
            $records = Cache::flexible(
                'dashboard.list.v1.' . $list . '.' . $year . '.sector.' . $selectedSector . '.offices.' . $officeScopeKey,
                [120, 1800],
                fn () => $list === 'pap'
                    ? $this->papListForYear($visibleFieldConfigs->all(), $year, $officeScope)
                    : $this->indicatorListForYear($visibleFieldConfigs->all(), $year, $officeScope)
            );

            return view('components.dashboard_' . $list . '_list', [$list . 'List' => $records]);
        }

        $dashboardSummary = Cache::flexible(
            'dashboard.summary.v27.' . $year . '.sector.' . $selectedSector . '.offices.' . $officeScopeKey,
            [120, 1800],
            fn () => $this->buildDashboardSummary($visibleFieldConfigs->all(), $year, $officeScope)
        );

        $fieldStats = $dashboardSummary['fieldStats'];
        $overallTarget = $dashboardSummary['overallTarget'];
        $overallAccomp = $dashboardSummary['overallAccomp'];
        $overallProgress = $dashboardSummary['overallProgress'];
        $financialUtilization = $dashboardSummary['financialUtilization'] ?? null;
        $progressTrend = $dashboardSummary['progressTrend'];
        $physicalTargetsProgress = $dashboardSummary['physicalTargetsProgress'];
        $activeFields = $dashboardSummary['activeFields'];
        $activeFieldsProgress = $dashboardSummary['activeFieldsProgress'];
        $officeStats = $dashboardSummary['officeStats'] ?? collect();
        $performanceComparison = $dashboardSummary['performanceComparison'] ?? [];
        $totalPap = $dashboardSummary['totalPap'] ?? 0;
        $totalIndicators = $dashboardSummary['totalIndicators'] ?? 0;
        $dashboardOfficeScopeNames = $this->dashboardOfficeScopeNames($officeScope);

        $yearOptions = Cache::remember(
            'dashboard.year_options.v2.' . $currentYear . '.offices.' . $officeScopeKey,
            now()->addMinutes(5),
            fn () => $this->yearOptions($fieldConfigs, $currentYear, $officeScope)
        );

        return view($request->header('X-Dashboard-Partial') === '1' ? 'components.dashboard_content' : $this->roleView('index'), [
            'year' => $year,
            'yearOptions' => $yearOptions,
            'selectedSector' => $selectedSector,
            'sectorOptions' => $sectorOptions,
            'selectedOffice' => $officeFilter['selected'],
            'officeOptions' => $officeFilter['options'],
            'officeAllowsAll' => $officeFilter['allows_all'],
            'officeAllLabel' => $officeFilter['all_label'],
            'fieldStats' => $fieldStats,
            'officeStats' => $officeStats,
            'performanceComparison' => $performanceComparison,
            'overallProgress' => $overallProgress,
            'progressTrend' => $progressTrend,
            'physicalTargetsProgress' => $physicalTargetsProgress,
            'overallTarget' => $overallTarget,
            'overallAccomp' => $overallAccomp,
            'totalPap' => $totalPap,
            'totalIndicators' => $totalIndicators,
            'dashboardOfficeScopeNames' => $dashboardOfficeScopeNames,
            'activeFields' => $activeFields,
            'activeFieldsProgress' => $activeFieldsProgress,
            'totalFields' => $fieldStats->count(),
            'financialUtilization' => $financialUtilization,
        ]);
    }

    private function statusLabel(float $progress): string
    {
        if ($progress >= 80) {
            return 'On Track';
        }

        if ($progress >= 60) {
            return 'Needs Attention';
        }

        return 'Delayed';
    }

    private function buildDashboardSummary(array $fieldConfigs, int $year, int|array|null $officeScope = null): array
    {
        $this->dashboardSectorKeys = array_column($fieldConfigs, 'key');
        $fieldStats = collect($fieldConfigs)->map(function (array $config) use ($year, $officeScope) {
            $totals = $this->physicalTotalsForYear($config['targets'], $config['accomp'], $year, $officeScope, $config['key']);
            $targetTotal = $totals['target_total'];
            $accompTotal = $totals['accomp_total'];

            $progress = $targetTotal > 0
                ? round(min(100, ($accompTotal / $targetTotal) * 100), 2)
                : 0.0;

            return [
                'key' => $config['key'],
                'label' => $config['label'],
                'target_total' => $targetTotal,
                'accomp_total' => $accompTotal,
                'progress' => $progress,
                'status' => $this->statusLabel($progress),
            ];
        })
            ->sortByDesc('progress')
            ->values();

        $overallTarget = (float) $fieldStats->sum('target_total');
        $overallAccomp = (float) $fieldStats->sum('accomp_total');
        $overallProgress = $this->physicalInputProgressForYear($fieldConfigs, $year, $officeScope);

        $fieldsWithTargets = $fieldStats->filter(fn ($row) => (float) ($row['target_total'] ?? 0) > 0)->count();
        $physicalTargetsProgress = $fieldStats->count() > 0
            ? round(($fieldsWithTargets / $fieldStats->count()) * 100, 2)
            : 0.0;

        $activeFields = $fieldStats->filter(fn ($row) => $row['target_total'] > 0 || $row['accomp_total'] > 0)->count();
        $activeFieldsProgress = $fieldStats->count() > 0
            ? round(($activeFields / $fieldStats->count()) * 100, 2)
            : 0.0;

        $papIndicatorTotals = $this->papIndicatorTotalsForYear($fieldConfigs, $year, $officeScope);

        return [
            'fieldStats' => $fieldStats,
            'overallTarget' => $overallTarget,
            'overallAccomp' => $overallAccomp,
            'totalPap' => $papIndicatorTotals['pap_total'],
            'totalIndicators' => $papIndicatorTotals['indicator_total'],
            'overallProgress' => $overallProgress,
            'financialUtilization' => $this->financialUtilizationForYear($fieldConfigs, $year, $officeScope),
            'progressTrend' => $this->progressTrend($fieldConfigs, $year, $officeScope),
            'officeStats' => $this->officePerformanceStatsForYear($fieldConfigs, $year, $officeScope),
            'performanceComparison' => $this->performanceComparisonForYear($fieldConfigs, $year, $officeScope),
            'physicalTargetsProgress' => $physicalTargetsProgress,
            'activeFields' => $activeFields,
            'activeFieldsProgress' => $activeFieldsProgress,
        ];
    }

    private function papListForYear(array $fieldConfigs, int $year, int|array|null $officeScope = null)
    {
        if (!$this->hasTable('ppa') || !$this->hasTable('types') || !$this->hasTable('record_types')) {
            return collect();
        }

        $typeCodes = collect($fieldConfigs)
            ->pluck('type_code')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($typeCodes)) {
            return collect();
        }

        $typeIds = DB::table('types')
            ->whereIn('code', $typeCodes)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $programRecordTypeId = (int) DB::table('record_types')
            ->where('name', 'PROGRAM')
            ->value('id');

        if (empty($typeIds) || $programRecordTypeId <= 0) {
            return collect();
        }

        $query = DB::table('ppa')
            ->leftJoin('types', 'types.id', '=', 'ppa.types_id')
            ->whereIn('ppa.types_id', $typeIds)
            ->where('ppa.record_type_id', $programRecordTypeId)
            ->where('ppa.year', $year)
            ->select([
                'ppa.id',
                'ppa.name',
                'ppa.types_id',
                'ppa.ppa_details_id',
                'ppa.office_id',
                'ppa.year',
                'types.code as sector_code',
            ])
            ->orderBy('types.code')
            ->orderBy('ppa.name');

        $rows = $query->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $sectorKeyByTypeCode = collect($fieldConfigs)
            ->mapWithKeys(fn (array $config) => [strtolower((string) ($config['type_code'] ?? '')) => (string) ($config['key'] ?? '')])
            ->filter()
            ->all();

        $officeIds = $this->normalizeOfficeScope($officeScope);
        $primaryOfficeId = $officeIds[0] ?? null;
        $officeNames = !$this->hasTable('offices')
            ? collect()
            : DB::table('offices')
                ->when(!empty($officeIds), fn ($query) => $query->whereIn('id', $officeIds))
                ->pluck('name', 'id');

        return $rows->map(function ($row) use ($typeIds, $sectorKeyByTypeCode, $officeIds, $primaryOfficeId, $officeNames) {
            $rootOfficeIds = $this->parseJsonIdArray($row->office_id ?? null);
            $detailIds = $this->hasTable('ppa_details')
                ? $this->descendantPpaDetailIds([$row->ppa_details_id])
                : [];

            $detailIdSet = array_fill_keys($detailIds, true);
            $indicatorRows = $this->ppaAssignmentRows($typeIds)->filter(function ($assignment) use ($row, $detailIdSet, $officeIds) {
                return $assignment->indicator_id !== null
                    && ((int) $assignment->id === (int) $row->id || isset($detailIdSet[(int) $assignment->ppa_details_id]))
                    && (empty($officeIds) || !empty(array_intersect($officeIds, $this->parseJsonIdArray($assignment->office_id))));
            });
            $indicatorCount = $indicatorRows->pluck('indicator_id')->filter()->unique()->count();
            $assignedOfficeIds = $indicatorRows
                ->flatMap(fn ($indicatorRow) => $this->parseJsonIdArray($indicatorRow->office_id ?? null))
                ->when(!empty($officeIds), fn ($ids) => $ids->intersect($officeIds))
                ->unique()
                ->values();
            $assignedOfficeNames = $assignedOfficeIds
                ->map(fn ($officeId) => (string) ($officeNames[(int) $officeId] ?? ''))
                ->filter()
                ->values()
                ->all();
            $sectorKey = $sectorKeyByTypeCode[strtolower((string) ($row->sector_code ?? ''))] ?? '';
            $routeParams = [
                'program' => (int) $row->id,
                'year' => (int) ($row->year ?? 0),
            ];

            if ($primaryOfficeId !== null) {
                $routeParams['office_id'] = $primaryOfficeId;
            } elseif ($assignedOfficeIds->isNotEmpty()) {
                $routeParams['office_id'] = (int) $assignedOfficeIds->first();
            } elseif (!empty($rootOfficeIds)) {
                $routeParams['office_id'] = $rootOfficeIds[0];
            }

            return [
                'id' => (int) $row->id,
                'name' => (string) ($row->name ?? ''),
                'sector' => (string) ($row->sector_code ?? ''),
                'year' => (int) ($row->year ?? 0),
                'indicator_count' => (int) $indicatorCount,
                'offices' => $assignedOfficeNames,
                'url' => $sectorKey !== '' ? route($sectorKey . '_physical', $routeParams) : '#',
            ];
        })
            ->filter(fn (array $pap) => (int) ($pap['indicator_count'] ?? 0) > 0)
            ->values();
    }

    private function indicatorListForYear(array $fieldConfigs, int $year, int|array|null $officeScope = null)
    {
        if (!$this->hasTable('ppa') || !$this->hasTable('types') || !$this->hasTable('record_types') || !$this->hasTable('indicators')) {
            return collect();
        }

        $typeCodes = collect($fieldConfigs)
            ->pluck('type_code')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($typeCodes)) {
            return collect();
        }

        $officeIds = $this->normalizeOfficeScope($officeScope);
        $primaryOfficeId = $officeIds[0] ?? null;
        $officeNames = !$this->hasTable('offices')
            ? collect()
            : DB::table('offices')
                ->when(!empty($officeIds), fn ($query) => $query->whereIn('id', $officeIds))
                ->pluck('name', 'id');

        $typeRows = DB::table('types')
            ->whereIn('code', $typeCodes)
            ->get(['id', 'code']);

        $typeIds = $typeRows
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $typeCodeById = $typeRows
            ->mapWithKeys(fn ($row) => [(int) $row->id => (string) $row->code])
            ->all();

        $sectorKeyByTypeCode = collect($fieldConfigs)
            ->mapWithKeys(fn (array $config) => [strtolower((string) ($config['type_code'] ?? '')) => (string) ($config['key'] ?? '')])
            ->filter()
            ->all();

        $programRecordTypeId = (int) DB::table('record_types')
            ->where('name', 'PROGRAM')
            ->value('id');

        if (empty($typeIds) || $programRecordTypeId <= 0) {
            return collect();
        }

        $baseQuery = DB::table('ppa')
            ->whereIn('types_id', $typeIds)
            ->where('year', $year);

        $papRows = (clone $baseQuery)
            ->where('record_type_id', $programRecordTypeId)
            ->get(['id', 'name', 'types_id', 'ppa_details_id', 'office_id', 'year']);

        if ($papRows->isEmpty()) {
            return collect();
        }

        $rowMetaById = collect();

        foreach ($papRows as $papRow) {
            $rootId = (int) ($papRow->id ?? 0);
            $typeId = (int) ($papRow->types_id ?? 0);
            $detailIds = $this->hasTable('ppa_details')
                ? $this->descendantPpaDetailIds([$papRow->ppa_details_id])
                : [];

            $rowIds = collect([$rootId]);

            if (!empty($detailIds)) {
                $rowIds = $rowIds->merge($this->ppaRowIdsForDetailIds($typeIds, $detailIds));
            }

            foreach ($rowIds->filter(fn ($id) => $id > 0)->unique() as $rowId) {
                $rowMetaById->put((int) $rowId, [
                    'root_id' => $rootId,
                    'pap_name' => (string) ($papRow->name ?? ''),
                    'type_id' => $typeId,
                    'sector' => (string) ($typeCodeById[$typeId] ?? ''),
                    'year' => (int) ($papRow->year ?? $year),
                    'office_ids' => $this->parseJsonIdArray($papRow->office_id ?? null),
                ]);
            }
        }

        $performanceIndicatorRowIds = $rowMetaById->keys()
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        $indicatorAssignments = $performanceIndicatorRowIds->isEmpty()
            ? collect()
            : DB::table('ppa')
                ->whereIn('id', $performanceIndicatorRowIds->all())
                ->whereNotNull('indicator_id')
                ->get(['id', 'indicator_id', 'office_id'])
                ->map(fn ($row) => [
                    'program_id' => (int) ($row->id ?? 0),
                    'indicator_id' => (int) ($row->indicator_id ?? 0),
                    'office_ids' => $this->parseJsonIdArray($row->office_id ?? null),
                ]);

        foreach ($fieldConfigs as $config) {
            $targetTable = $config['targets'] ?? null;

            if (!is_string($targetTable) || !$this->hasTable($targetTable)) {
                continue;
            }

            $targetRows = $this->physicalRowsForYear(
                $targetTable,
                $year,
                empty($officeIds) ? null : $officeIds,
                $config['key']
            );

            foreach ($targetRows as $targetRow) {
                $meta = $this->parseValuesJson($targetRow->values ?? null);
                $programId = (int) ($targetRow->row_id ?? $targetRow->program_id ?? $meta['row_id'] ?? $meta['program_id'] ?? 0);
                $indicatorId = (int) ($targetRow->indicator_id ?? $meta['indicator_id'] ?? 0);

                if ($programId <= 0 || $indicatorId <= 0 || !$rowMetaById->has($programId)) {
                    continue;
                }

                $targetOfficeId = (int) ($targetRow->office_id ?? $targetRow->office_ids ?? 0);

                $indicatorAssignments->push([
                    'program_id' => $programId,
                    'indicator_id' => $indicatorId,
                    'office_ids' => $targetOfficeId > 0 ? [$targetOfficeId] : [],
                ]);
            }
        }

        $indicatorAssignments = $indicatorAssignments
            ->filter(fn (array $assignment) => (int) ($assignment['program_id'] ?? 0) > 0 && (int) ($assignment['indicator_id'] ?? 0) > 0)
            ->map(function (array $assignment) use ($officeIds) {
                $assignmentOfficeIds = collect($assignment['office_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn (int $id) => $id > 0)
                    ->unique();

                if (!empty($officeIds)) {
                    $assignmentOfficeIds = $assignmentOfficeIds->intersect($officeIds);
                }

                $assignment['office_ids'] = $assignmentOfficeIds->values()->all();

                return $assignment;
            })
            ->filter(fn (array $assignment) => !empty($assignment['office_ids']))
            ->unique(fn (array $assignment) => (int) $assignment['program_id'] . '|' . (int) $assignment['indicator_id'])
            ->values();

        $indicatorNames = $indicatorAssignments->isEmpty()
            ? collect()
            : DB::table('indicators')
                ->whereIn('id', $indicatorAssignments->pluck('indicator_id')->unique()->values()->all())
                ->pluck('name', 'id');

        return $indicatorAssignments
            ->filter(fn (array $assignment) => $indicatorNames->has((int) ($assignment['indicator_id'] ?? 0)) && $rowMetaById->has((int) ($assignment['program_id'] ?? 0)))
            ->map(function (array $assignment) use ($indicatorNames, $rowMetaById, $sectorKeyByTypeCode, $primaryOfficeId, $officeIds, $officeNames) {
                $rowMeta = $rowMetaById->get((int) $assignment['program_id']);
                $sector = (string) ($rowMeta['sector'] ?? '');
                $sectorKey = $sectorKeyByTypeCode[strtolower($sector)] ?? '';
                $assignmentOfficeIds = collect($assignment['office_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->values()
                    ->all();
                $rootOfficeIds = $rowMeta['office_ids'] ?? [];
                $assignedOfficeNames = collect($assignmentOfficeIds)
                    ->map(fn ($officeId) => (string) ($officeNames[(int) $officeId] ?? ''))
                    ->filter()
                    ->values()
                    ->all();
                $routeParams = [
                    'program' => (int) ($rowMeta['root_id'] ?? 0),
                    'year' => (int) ($rowMeta['year'] ?? 0),
                    'highlight_row_id' => (int) ($assignment['program_id'] ?? 0),
                    'highlight_indicator_id' => (int) ($assignment['indicator_id'] ?? 0),
                ];

                if ($primaryOfficeId !== null) {
                    $routeParams['office_id'] = $primaryOfficeId;
                } elseif (!empty($assignmentOfficeIds)) {
                    $routeParams['office_id'] = $assignmentOfficeIds[0];
                } elseif (!empty($rootOfficeIds)) {
                    $routeParams['office_id'] = $rootOfficeIds[0];
                }

                return [
                    'id' => (int) ($assignment['indicator_id'] ?? 0),
                    'name' => (string) ($indicatorNames[(int) ($assignment['indicator_id'] ?? 0)] ?? ''),
                    'pap_name' => (string) ($rowMeta['pap_name'] ?? ''),
                    'sector' => $sector,
                    'year' => (int) ($rowMeta['year'] ?? 0),
                    'offices' => $assignedOfficeNames,
                    'url' => $sectorKey !== '' ? route($sectorKey . '_physical', $routeParams) : '#',
                ];
            })
            ->sortBy([
                ['sector', 'asc'],
                ['pap_name', 'asc'],
                ['name', 'asc'],
            ])
            ->values();
    }

    private function officePerformanceStatsForYear(array $fieldConfigs, int $year, int|array|null $officeScope = null)
    {
        if (!$this->hasTable('offices')) {
            return collect();
        }

        $officesQuery = DB::table('offices')
            ->select(['id', 'name', 'office_types_id'])
            ->orderBy('office_types_id')
            ->orderBy('name');

        $officeIds = $this->normalizeOfficeScope($officeScope);

        if (!empty($officeIds)) {
            $officesQuery->whereIn('id', $officeIds);
        }

        $sectorKeys = collect($fieldConfigs)
            ->pluck('key')
            ->filter()
            ->unique()
            ->values()
            ->all();
        $targetTable = (string) ($fieldConfigs[0]['targets'] ?? 'physical_targets');
        $accomplishmentTable = (string) ($fieldConfigs[0]['accomp'] ?? 'physical_accomplishments');
        $targetTotalsByOffice = $this->totalsByOfficeFromAggregateRows(
            $this->physicalMonthlySumsForYear($targetTable, $year, $officeScope, $sectorKeys)
        );
        $accomplishmentTotalsByOffice = $this->totalsByOfficeFromAggregateRows(
            $this->physicalMonthlySumsForYear($accomplishmentTable, $year, $officeScope, $sectorKeys)
        );

        return $officesQuery
            ->get()
            ->map(function ($office) use ($targetTotalsByOffice, $accomplishmentTotalsByOffice) {
                $officeId = (int) $office->id;
                $targetTotal = (float) ($targetTotalsByOffice[$officeId] ?? 0.0);
                $accompTotal = (float) ($accomplishmentTotalsByOffice[$officeId] ?? 0.0);

                $progress = $targetTotal > 0
                    ? round(min(100, ($accompTotal / $targetTotal) * 100), 2)
                    : 0.0;

                return [
                    'id' => (int) $office->id,
                    'label' => (string) $office->name,
                    'office_type_id' => (int) ($office->office_types_id ?? 0),
                    'target_total' => $targetTotal,
                    'accomp_total' => $accompTotal,
                    'progress' => $progress,
                    'status' => $this->statusLabel($progress),
                ];
            })
            ->sortBy([
                ['accomp_total', 'desc'],
                ['label', 'asc'],
            ])
            ->values();
    }

    private function papIndicatorTotalsForYear(array $fieldConfigs, int $year, int|array|null $officeScope = null): array
    {
        if (!$this->hasTable('ppa') || !$this->hasTable('types') || !$this->hasTable('record_types') || !$this->hasTable('indicators')) {
            return [
                'pap_total' => 0,
                'indicator_total' => 0,
            ];
        }

        $officeIds = $this->normalizeOfficeScope($officeScope);

        $typeCodes = collect($fieldConfigs)
            ->pluck('type_code')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($typeCodes)) {
            return [
                'pap_total' => 0,
                'indicator_total' => 0,
            ];
        }

        $typeIds = DB::table('types')
            ->whereIn('code', $typeCodes)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $programRecordTypeId = (int) DB::table('record_types')
            ->where('name', 'PROGRAM')
            ->value('id');

        if (empty($typeIds) || $programRecordTypeId <= 0) {
            return [
                'pap_total' => 0,
                'indicator_total' => 0,
            ];
        }

        $baseQuery = DB::table('ppa')
            ->whereIn('types_id', $typeIds)
            ->where('year', $year);

        $papRows = (clone $baseQuery)
            ->where('record_type_id', $programRecordTypeId)
            ->get(['id', 'ppa_details_id']);

        $rootProgramIdByRowId = collect();

        foreach ($papRows as $papRow) {
            $rootProgramId = (int) ($papRow->id ?? 0);
            $rowIds = collect([$rootProgramId]);
            $detailIds = $this->hasTable('ppa_details')
                ? $this->descendantPpaDetailIds([$papRow->ppa_details_id])
                : [];

            if (!empty($detailIds)) {
                $rowIds = $rowIds->merge($this->ppaRowIdsForDetailIds($typeIds, $detailIds));
            }

            foreach ($rowIds->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique() as $rowId) {
                $rootProgramIdByRowId->put($rowId, $rootProgramId);
            }
        }

        $programIds = $papRows
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();
        $performanceIndicatorRowIds = $rootProgramIdByRowId->keys();

        $indicatorAssignments = $performanceIndicatorRowIds->isEmpty()
            ? collect()
            : DB::table('ppa')
                ->whereIn('id', $performanceIndicatorRowIds->all())
                ->whereNotNull('indicator_id')
                ->get(['id', 'indicator_id', 'office_id'])
                ->map(fn ($row) => [
                    'program_id' => (int) ($row->id ?? 0),
                    'indicator_id' => (int) ($row->indicator_id ?? 0),
                    'office_ids' => $this->parseJsonIdArray($row->office_id ?? null),
                ]);

        if (!$programIds->isEmpty()) {
            foreach ($fieldConfigs as $config) {
                $targetTable = $config['targets'] ?? null;

                if (!is_string($targetTable) || !$this->hasTable($targetTable)) {
                    continue;
                }

                $targetRows = $this->physicalRowsForYear(
                    $targetTable,
                    $year,
                    empty($officeIds) ? null : $officeIds,
                    $config['key']
                );

                foreach ($targetRows as $targetRow) {
                    $meta = $this->parseValuesJson($targetRow->values ?? null);
                    $programId = (int) ($targetRow->row_id ?? $targetRow->program_id ?? $meta['row_id'] ?? $meta['program_id'] ?? 0);
                    $indicatorId = (int) ($targetRow->indicator_id ?? $meta['indicator_id'] ?? 0);

                    if ($programId <= 0 || $indicatorId <= 0 || !$performanceIndicatorRowIds->contains($programId)) {
                        continue;
                    }

                    $targetOfficeId = (int) ($targetRow->office_id ?? $targetRow->office_ids ?? 0);

                    $indicatorAssignments->push([
                        'program_id' => $programId,
                        'indicator_id' => $indicatorId,
                        'office_ids' => $targetOfficeId > 0 ? [$targetOfficeId] : [],
                    ]);
                }
            }
        }

        $indicatorAssignments = $indicatorAssignments
            ->filter(fn (array $assignment) => (int) ($assignment['program_id'] ?? 0) > 0 && (int) ($assignment['indicator_id'] ?? 0) > 0)
            ->flatMap(function (array $assignment) use ($officeIds) {
                $assignmentOfficeIds = collect($assignment['office_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn (int $id) => $id > 0)
                    ->unique();

                if (!empty($officeIds)) {
                    $assignmentOfficeIds = $assignmentOfficeIds->intersect($officeIds);
                }

                return $assignmentOfficeIds->map(function (int $officeId) use ($assignment) {
                    $assignment['office_id'] = $officeId;
                    $assignment['office_ids'] = [$officeId];

                    return $assignment;
                });
            })
            ->unique(fn (array $assignment) => implode('|', [
                (int) $assignment['program_id'],
                (int) $assignment['indicator_id'],
                (int) $assignment['office_id'],
            ]))
            ->values();

        $validIndicatorIds = $indicatorAssignments
            ->pluck('indicator_id')
            ->unique()
            ->values();

        $existingIndicatorIds = $validIndicatorIds->isEmpty()
            ? collect()
            : DB::table('indicators')
                ->whereIn('id', $validIndicatorIds->all())
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

        $indicatorTotal = $indicatorAssignments
            ->filter(fn (array $assignment) => $existingIndicatorIds->contains((int) ($assignment['indicator_id'] ?? 0)))
            ->count();

        $papTotal = $indicatorAssignments
            ->filter(fn (array $assignment) => $existingIndicatorIds->contains((int) ($assignment['indicator_id'] ?? 0)))
            ->map(function (array $assignment) use ($rootProgramIdByRowId) {
                $rootProgramId = (int) $rootProgramIdByRowId->get((int) ($assignment['program_id'] ?? 0), 0);
                $officeId = (int) ($assignment['office_id'] ?? 0);

                return $rootProgramId > 0 && $officeId > 0
                    ? $rootProgramId . '|' . $officeId
                    : null;
            })
            ->filter()
            ->unique()
            ->count();

        return [
            'pap_total' => $papTotal,
            'indicator_total' => $indicatorTotal,
        ];
    }

    private function descendantPpaDetailIds(array $rootDetailIds): array
    {
        $allDetailIds = collect($rootDetailIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($allDetailIds->isEmpty()) {
            return [];
        }

        if ($this->ppaDetailChildrenByParent === null) {
            $this->ppaDetailChildrenByParent = DB::table('ppa_details')
                ->get(['id', 'parent_id'])
                ->reduce(function (array $childrenByParent, $row) {
                    $parentId = (int) ($row->parent_id ?? 0);
                    $childId = (int) ($row->id ?? 0);

                    if ($parentId > 0 && $childId > 0) {
                        $childrenByParent[$parentId][] = $childId;
                    }

                    return $childrenByParent;
                }, []);
        }

        $frontier = $allDetailIds->all();
        $seen = array_fill_keys($frontier, true);

        while (!empty($frontier)) {
            $children = [];

            foreach ($frontier as $parentId) {
                foreach ($this->ppaDetailChildrenByParent[$parentId] ?? [] as $childId) {
                    if (!isset($seen[$childId])) {
                        $seen[$childId] = true;
                        $children[] = $childId;
                    }
                }
            }

            $frontier = $children;
        }

        return array_map('intval', array_keys($seen));
    }

    private function ppaRowIdsForDetailIds(array $typeIds, array $detailIds): array
    {
        $typeIds = collect($typeIds)->map(fn ($id) => (int) $id)->filter()->unique()->sort()->values()->all();
        $detailIds = collect($detailIds)->map(fn ($id) => (int) $id)->filter()->unique()->sort()->values()->all();

        if (empty($typeIds) || empty($detailIds)) {
            return [];
        }

        $cacheKey = implode(',', $typeIds).'|'.implode(',', $detailIds);

        if (!array_key_exists($cacheKey, $this->ppaRowIdsByDetailSetCache)) {
            $detailIdSet = array_fill_keys($detailIds, true);
            $this->ppaRowIdsByDetailSetCache[$cacheKey] = $this->ppaAssignmentRows($typeIds)
                ->filter(fn ($row) => isset($detailIdSet[(int) $row->ppa_details_id]))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return $this->ppaRowIdsByDetailSetCache[$cacheKey];
    }

    private function ppaAssignmentRows(array $typeIds)
    {
        sort($typeIds);
        $key = implode(',', $typeIds);

        return $this->ppaAssignmentRowsCache[$key] ??= DB::table('ppa')
            ->whereIn('types_id', $typeIds)
            ->get(['id', 'ppa_details_id', 'indicator_id', 'office_id']);
    }

    private function parseJsonIdArray($raw): array
    {
        if (is_array($raw)) {
            return collect($raw)
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
        }

        if (is_numeric($raw)) {
            $id = (int) $raw;

            return $id > 0 ? [$id] : [];
        }

        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (is_numeric($decoded)) {
            $id = (int) $decoded;

            return $id > 0 ? [$id] : [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function physicalTotalsForYear(string $targetTable, string $accompTable, int $year, int|array|null $officeScope = null, ?string $sector = null): array
    {
        $targetMap = $this->monthlyMapFromAggregateRows($this->physicalMonthlySumsForYear($targetTable, $year, $officeScope, $sector));
        $accompMap = $this->monthlyMapFromAggregateRows($this->physicalMonthlySumsForYear($accompTable, $year, $officeScope, $sector));
        $targetTotal = 0.0;
        $accompTotal = 0.0;

        foreach ($targetMap as $targetValue) {
            $targetTotal += max((float) $targetValue, 0.0);
        }

        foreach ($accompMap as $accompValue) {
            $accompTotal += max((float) $accompValue, 0.0);
        }

        return [
            'target_total' => $targetTotal,
            'accomp_total' => $accompTotal,
        ];
    }

    /**
     * Average the attainment of each physical monthly input independently so
     * values with different units are never added together. Each target input
     * contributes equally, and over-accomplishment is capped at 100%.
     */
    private function physicalInputProgressForYear(array $fieldConfigs, int $year, int|array|null $officeScope = null): float
    {
        if (empty($fieldConfigs)) {
            return 0.0;
        }

        $attainmentTotal = 0.0;
        $targetInputCount = 0;
        $sectorKeys = collect($fieldConfigs)->pluck('key')->all();
        $targetMap = $this->monthlyMapFromAggregateRows(
            $this->physicalMonthlySumsForYear($fieldConfigs[0]['targets'], $year, $officeScope, $sectorKeys)
        );
        $accomplishmentMap = $this->monthlyMapFromAggregateRows(
            $this->physicalMonthlySumsForYear($fieldConfigs[0]['accomp'], $year, $officeScope, $sectorKeys)
        );

        foreach ($targetMap as $key => $target) {
            $target = max((float) $target, 0.0);

            if ($target <= 0) {
                continue;
            }

            $accomplishment = max((float) ($accomplishmentMap[$key] ?? 0), 0.0);
            $attainmentTotal += min($accomplishment / $target, 1.0);
            $targetInputCount++;
        }

        return $targetInputCount > 0
            ? round(($attainmentTotal / $targetInputCount) * 100, 2)
            : 0.0;
    }

    private function financialUtilizationForYear(array $fieldConfigs, int $year, int|array|null $officeScope = null): ?float
    {
        if (empty($fieldConfigs)) {
            return null;
        }

        $targetTotal = 0.0;
        $accomplishmentTotal = 0.0;
        $sectorKeys = collect($fieldConfigs)->pluck('key')->all();
        $targetMap = $this->monthlyMapFromAggregateRows(
            $this->physicalMonthlySumsForYear('financial_target', $year, $officeScope, $sectorKeys)
        );
        $accomplishmentMap = $this->monthlyMapFromAggregateRows(
            $this->physicalMonthlySumsForYear('financial_accomplishment', $year, $officeScope, $sectorKeys)
        );

        foreach ($targetMap as $key => $target) {
            $target = max((float) $target, 0.0);

            if ($target <= 0) {
                continue;
            }

            $targetTotal += $target;
            $accomplishmentTotal += max((float) ($accomplishmentMap[$key] ?? 0), 0.0);
        }

        if ($targetTotal <= 0) {
            return null;
        }

        return round(min(100, ($accomplishmentTotal / $targetTotal) * 100), 2);
    }

    /**
     * Build one period-by-period data set for the two comparison cards.
     * Keeping target and accomplishment values in the same rows guarantees
     * that both cards use an identical sector order and period selection.
     */
    private function performanceComparisonForYear(array $fieldConfigs, int $year, int|array|null $officeScope = null): array
    {
        $dataSets = [
            'physical' => [
                'target' => 'physical_targets',
                'accomplishment' => 'physical_accomplishments',
                'aggregation' => 'input_count',
            ],
            'financial' => [
                'target' => 'financial_target',
                'accomplishment' => 'financial_accomplishment',
                'aggregation' => 'sum',
            ],
        ];

        $sectorKeys = collect($fieldConfigs)->pluck('key')->all();

        return collect($dataSets)->map(function (array $tables) use ($fieldConfigs, $sectorKeys, $year, $officeScope) {
            $aggregate = ($tables['aggregation'] ?? 'sum') === 'input_count'
                ? fn (string $table) => $this->periodInputCountsBySectorForYear($table, $year, $officeScope, $sectorKeys)
                : fn (string $table) => $this->periodTotalsBySectorForYear($table, $year, $officeScope, $sectorKeys);
            $targetsBySector = $aggregate($tables['target']);
            $accomplishmentsBySector = $aggregate($tables['accomplishment']);

            return collect($fieldConfigs)->map(function (array $config) use ($targetsBySector, $accomplishmentsBySector) {
                $targetPeriods = $targetsBySector[$config['key']] ?? array_fill_keys($this->comparisonPeriodColumns(), 0.0);
                $accomplishmentPeriods = $accomplishmentsBySector[$config['key']] ?? array_fill_keys($this->comparisonPeriodColumns(), 0.0);

                return [
                    'key' => $config['key'],
                    'label' => $config['label'],
                    'target' => $targetPeriods,
                    'accomplishment' => $accomplishmentPeriods,
                ];
            })->values()->all();
        })->all();
    }

    /**
     * Count positive physical input cells by sector and period. The physical
     * columns default to zero, so positive values represent populated inputs.
     */
    private function periodInputCountsBySectorForYear(
        string $table,
        int $year,
        int|array|null $officeScope,
        array $sectors
    ): array {
        $emptyCounts = array_fill_keys($this->comparisonPeriodColumns(), 0.0);
        $counts = collect($sectors)->mapWithKeys(fn (string $sector) => [$sector => $emptyCounts])->all();

        if (!$this->hasTable($table) || !$this->hasColumn($table, 'sector') || empty($sectors)) {
            return $counts;
        }

        $yearColumn = $this->resolveYearColumn($table);

        if ($yearColumn === null) {
            return $counts;
        }

        $selects = collect($this->comparisonPeriodColumns())
            ->filter(fn (string $column) => $this->hasColumn($table, $column))
            ->map(fn (string $column) => DB::raw("SUM(CASE WHEN `{$column}` > 0 THEN 1 ELSE 0 END) as `{$column}`"))
            ->values()
            ->all();

        if (empty($selects)) {
            return $counts;
        }

        $query = DB::table($table)
            ->select(array_merge(['sector'], $selects))
            ->whereIn('sector', $sectors)
            ->groupBy('sector');
        $this->applyYearFilter($query, $yearColumn, $year);

        $officeColumn = $this->hasColumn($table, 'office_id') ? 'office_id' : 'office_ids';
        $officeIds = $this->normalizeOfficeScope($officeScope);

        if (!empty($officeIds) && $this->hasColumn($table, $officeColumn)) {
            $query->whereIn($officeColumn, $officeIds);
        }

        foreach ($query->get() as $row) {
            $sector = strtolower((string) ($row->sector ?? ''));

            if (!array_key_exists($sector, $counts)) {
                continue;
            }

            foreach ($this->comparisonPeriodColumns() as $periodColumn) {
                $counts[$sector][$periodColumn] = (float) ($row->{$periodColumn} ?? 0);
            }
        }

        return $counts;
    }

    private function periodTotalsBySectorForYear(
        string $table,
        int $year,
        int|array|null $officeScope,
        array $sectors
    ): array
    {
        $emptyTotals = array_fill_keys($this->comparisonPeriodColumns(), 0.0);
        $totals = collect($sectors)->mapWithKeys(fn (string $sector) => [$sector => $emptyTotals])->all();

        if (!$this->hasTable($table) || !$this->hasColumn($table, 'sector') || empty($sectors)) {
            return $totals;
        }

        $yearColumn = $this->resolveYearColumn($table);

        if ($yearColumn === null) {
            return $totals;
        }

        $selects = collect($this->comparisonPeriodColumns())
            ->filter(fn (string $column) => $this->hasColumn($table, $column))
            ->map(fn (string $column) => DB::raw("COALESCE(SUM(`{$column}`), 0) as `{$column}`"))
            ->values()
            ->all();

        if (empty($selects)) {
            return $totals;
        }

        $query = DB::table($table)
            ->select(array_merge(['sector'], $selects))
            ->whereIn('sector', $sectors)
            ->groupBy('sector');
        $this->applyYearFilter($query, $yearColumn, $year);

        $officeColumn = $this->hasColumn($table, 'office_id') ? 'office_id' : 'office_ids';
        $officeIds = $this->normalizeOfficeScope($officeScope);

        if (!empty($officeIds) && $this->hasColumn($table, $officeColumn)) {
            $query->whereIn($officeColumn, $officeIds);
        }

        foreach ($query->get() as $row) {
            $sector = strtolower((string) ($row->sector ?? ''));

            if (!array_key_exists($sector, $totals)) {
                continue;
            }

            foreach ($this->comparisonPeriodColumns() as $periodColumn) {
                $totals[$sector][$periodColumn] = max((float) ($row->{$periodColumn} ?? 0), 0.0);
            }
        }

        return $totals;
    }

    private function progressTrend(array $fieldConfigs, int $year, int|array|null $officeScope = null): array
    {
        $monthlyLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthColumns = $this->monthColumns();
        $monthlyDelays = array_fill_keys($monthColumns, 0.0);

        foreach ($fieldConfigs as $config) {
            $totals = $this->physicalDelayTrendForYear(
                $config['targets'],
                $config['accomp'],
                $year,
                $officeScope,
                $config['key']
            );

            foreach ($monthColumns as $monthColumn) {
                $monthlyDelays[$monthColumn] += (float) ($totals[$monthColumn] ?? 0);
            }
        }

        $monthly = [];
        $maxMonthlyDelay = max($monthlyDelays);

        foreach ($monthColumns as $index => $monthColumn) {
            $delay = $monthlyDelays[$monthColumn];

            $monthly[] = [
                'label' => $monthlyLabels[$index],
                'delay' => round($delay, 2),
                'progress' => $maxMonthlyDelay > 0 ? round(($delay / $maxMonthlyDelay) * 100, 2) : 0.0,
            ];
        }

        $quarters = [
            'Q1' => ['jan', 'feb', 'mar'],
            'Q2' => ['apr', 'may', 'jun'],
            'Q3' => ['jul', 'aug', 'sep'],
            'Q4' => ['oct', 'nov', 'dec'],
        ];

        $quarterly = [];
        $quarterlyDelays = [];

        foreach ($quarters as $label => $quarterMonths) {
            $quarterlyDelays[$label] = array_sum(array_intersect_key($monthlyDelays, array_flip($quarterMonths)));
        }

        $maxQuarterlyDelay = max($quarterlyDelays);

        foreach ($quarterlyDelays as $label => $delay) {

            $quarterly[] = [
                'label' => $label,
                'delay' => round($delay, 2),
                'progress' => $maxQuarterlyDelay > 0 ? round(($delay / $maxQuarterlyDelay) * 100, 2) : 0.0,
            ];
        }

        return [
            'monthly' => $monthly,
            'quarterly' => $quarterly,
        ];
    }

    private function physicalDelayTrendForYear(
        string $targetTable,
        string $accompTable,
        int $year,
        int|array|null $officeScope = null,
        ?string $sector = null
    ): array
    {
        $delays = array_fill_keys($this->monthColumns(), 0.0);
        $targetMap = $this->monthlyMapFromAggregateRows(
            $this->physicalMonthlySumsForYear($targetTable, $year, $officeScope, $sector)
        );
        $accomplishmentMap = $this->monthlyMapFromAggregateRows(
            $this->physicalMonthlySumsForYear($accompTable, $year, $officeScope, $sector)
        );

        foreach ($targetMap as $key => $target) {
            $separatorPosition = strrpos($key, '|');
            $monthColumn = $separatorPosition === false ? '' : substr($key, $separatorPosition + 1);

            if (!array_key_exists($monthColumn, $delays)) {
                continue;
            }

            if ((float) $target > (float) ($accomplishmentMap[$key] ?? 0)) {
                $delays[$monthColumn]++;
            }
        }

        return $delays;
    }

    private function physicalRowsForYear(string $table, int $year, int|array|null $officeScope = null, string|array|null $sector = null)
    {
        if (!$this->hasTable($table)) {
            return collect();
        }

        $officeIds = $this->normalizeOfficeScope($officeScope);
        sort($officeIds);
        $sectorKeys = collect(is_array($sector) ? $sector : [$sector])
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->map(fn ($value) => strtolower((string) $value))
            ->unique()
            ->sort()
            ->values()
            ->all();
        $cacheKey = implode('|', [
            $table,
            $year,
            implode(',', $officeIds),
            implode(',', $sectorKeys),
        ]);

        if (array_key_exists($cacheKey, $this->physicalRowsCache)) {
            return $this->physicalRowsCache[$cacheKey];
        }

        // Load the shared table once per year/office scope, then reuse it for each sector.
        if (count($this->dashboardSectorKeys) > 1 && !empty($sectorKeys) && $this->hasColumn($table, 'sector')) {
            return $this->physicalRowsCache[$cacheKey] = $this->physicalRowsForYear($table, $year, $officeScope)
                ->filter(fn ($row) => in_array(strtolower((string) $row->sector), $sectorKeys, true))
                ->values();
        }

        $yearColumn = $this->resolveYearColumn($table);

        if ($yearColumn === null) {
            return collect();
        }

        $columns = collect(['sector', 'office_id', 'office_ids', 'program_id', 'row_id', 'indicator_id', 'values', 'annual_total'])
            ->merge($this->monthColumns())
            ->filter(fn (string $column) => $this->hasColumn($table, $column))
            ->values()
            ->all();

        $query = DB::table($table)->select($columns);
        $this->applyYearFilter($query, $yearColumn, $year);

        $officeColumn = $this->hasColumn($table, 'office_id') ? 'office_id' : 'office_ids';

        if (!empty($officeIds) && $this->hasColumn($table, $officeColumn)) {
            $query->whereIn($officeColumn, $officeIds);
        }

        if (!empty($sectorKeys) && $this->hasColumn($table, 'sector')) {
            $query->whereIn('sector', $sectorKeys);
        } elseif (count($this->dashboardSectorKeys) > 1 && $this->hasColumn($table, 'sector')) {
            $query->whereIn('sector', $this->dashboardSectorKeys);
        }

        return $this->physicalRowsCache[$cacheKey] = $query->get();
    }

    private function physicalMonthlySumsForYear(string $table, int $year, int|array|null $officeScope = null, string|array|null $sector = null)
    {
        if (!$this->hasTable($table)) {
            return collect();
        }

        $yearColumn = $this->resolveYearColumn($table);

        $officeColumn = $this->hasColumn($table, 'office_id') ? 'office_id' : 'office_ids';

        if ($yearColumn === null || !$this->hasColumn($table, $officeColumn)) {
            return collect();
        }

        $officeIds = $this->normalizeOfficeScope($officeScope);
        sort($officeIds);
        $sectorKeys = collect(is_array($sector) ? $sector : [$sector])
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->map(fn ($value) => strtolower((string) $value))
            ->unique()
            ->sort()
            ->values()
            ->all();
        $cacheKey = implode('|', [
            $table,
            $year,
            implode(',', $officeIds),
            implode(',', $sectorKeys),
        ]);

        if (array_key_exists($cacheKey, $this->monthlySumsCache)) {
            return $this->monthlySumsCache[$cacheKey];
        }

        $batchBySector = count($this->dashboardSectorKeys) > 1 && is_string($sector) && count($sectorKeys) === 1 && $this->hasColumn($table, 'sector');
        $batchKey = $table.'|'.$year.'|'.implode(',', $officeIds);

        if ($batchBySector && array_key_exists($batchKey, $this->sectorMonthlySumsCache)) {
            return $this->monthlySumsCache[$cacheKey] = $this->sectorMonthlySumsCache[$batchKey]
                ->filter(fn ($row) => strtolower((string) $row->sector) === $sectorKeys[0])
                ->values();
        }

        $monthColumns = collect($this->monthColumns())
            ->filter(fn (string $column) => $this->hasColumn($table, $column))
            ->values();

        if ($monthColumns->isEmpty()) {
            return collect();
        }

        // Consolidated rows already expose their identity columns. Reuse the
        // request's rows instead of asking MySQL to sort the same data repeatedly.
        if ($this->hasColumn($table, 'row_id') && $this->hasColumn($table, 'indicator_id') && ! $this->hasColumn($table, 'values')) {
            $rows = $this->physicalRowsForYear($table, $year, $officeScope, $batchBySector ? null : $sector);
            $groups = [];

            foreach ($rows as $row) {
                $officeId = $row->{$officeColumn} ?? null;
                if ($officeId === null) {
                    continue;
                }

                $groupKey = json_encode([$row->row_id, $row->indicator_id, $officeId, $row->program_id ?? null, $batchBySector ? $row->sector : null]);
                if (!isset($groups[$groupKey])) {
                    $groups[$groupKey] = (object) array_merge([
                        'dashboard_program_id' => $row->row_id,
                        'dashboard_indicator_id' => $row->indicator_id,
                        'office_ids' => $officeId,
                        'sector' => $row->sector ?? null,
                    ], array_fill_keys($monthColumns->all(), 0.0));
                }

                foreach ($monthColumns as $column) {
                    $groups[$groupKey]->{$column} += (float) ($row->{$column} ?? 0);
                }
            }

            $sums = collect(array_values($groups));
            if ($batchBySector) {
                $this->sectorMonthlySumsCache[$batchKey] = $sums;
                $sums = $sums->filter(fn ($row) => strtolower((string) $row->sector) === $sectorKeys[0])->values();
            }

            return $this->monthlySumsCache[$cacheKey] = $sums;
        }

        $programExpression = $this->dashboardProgramIdExpression($table);
        $indicatorExpression = $this->dashboardIndicatorIdExpression($table);

        $selects = [
            DB::raw("{$programExpression} as dashboard_program_id"),
            DB::raw("{$indicatorExpression} as dashboard_indicator_id"),
            DB::raw("`{$officeColumn}` as office_ids"),
        ];

        foreach ($monthColumns as $column) {
            $selects[] = DB::raw("COALESCE(SUM(`{$column}`), 0) as `{$column}`");
        }

        $query = DB::table($table)
            ->select($selects)
            ->whereNotNull($officeColumn)
            ->groupBy(DB::raw($programExpression), DB::raw($indicatorExpression), $officeColumn);

        if ($batchBySector) {
            $query->addSelect('sector')->groupBy('sector')->whereIn('sector', $this->dashboardSectorKeys);
        }

        if ($this->hasColumn($table, 'program_id')) {
            $query->groupBy('program_id');
        }

        if ($this->hasColumn($table, 'indicator_id')) {
            $query->groupBy('indicator_id');
        }

        if ($this->hasColumn($table, 'values')) {
            $query->groupBy('values');
        }

        $this->applyYearFilter($query, $yearColumn, $year);

        if (!empty($officeIds)) {
            $query->whereIn($officeColumn, $officeIds);
        }

        if (!$batchBySector && !empty($sectorKeys) && $this->hasColumn($table, 'sector')) {
            $query->whereIn('sector', $sectorKeys);
        }

        if ($batchBySector) {
            $this->sectorMonthlySumsCache[$batchKey] = $query->get();

            return $this->monthlySumsCache[$cacheKey] = $this->sectorMonthlySumsCache[$batchKey]
                ->filter(fn ($row) => strtolower((string) $row->sector) === $sectorKeys[0])
                ->values();
        }

        return $this->monthlySumsCache[$cacheKey] = $query->get();
    }

    private function totalsByOfficeFromAggregateRows($rows): array
    {
        $totals = [];

        foreach ($rows as $row) {
            $officeId = (int) ($row->office_ids ?? 0);

            if ($officeId <= 0) {
                continue;
            }

            foreach ($this->monthColumns() as $column) {
                $totals[$officeId] = ($totals[$officeId] ?? 0.0)
                    + max((float) ($row->{$column} ?? 0), 0.0);
            }
        }

        return $totals;
    }

    private function dashboardOfficeFilter(\Illuminate\Http\Request $request): array
    {
        $user = auth()->user();
        $allOffices = $this->hasTable('offices')
            ? Office::query()
                ->orderBy('office_types_id')
                ->orderBy('name')
                ->get(['id', 'name', 'office_types_id'])
            : collect();

        $canViewAllOffices = $user === null || $user->isAdmin() || $user->isRegionalOffice();
        $allowsAll = $canViewAllOffices || ($user?->isPenro() ?? false);
        $allLabel = $canViewAllOffices ? 'All Offices' : 'All Service Area';

        if ($canViewAllOffices) {
            $allowedOfficeIds = $allOffices->pluck('id')->map(fn ($id) => (int) $id)->all();
            $allScope = null;
        } elseif ($user?->isPenro()) {
            $allowedOfficeIds = Office::serviceAreaOfficeIdsForPenro((int) $user->office_id);
            $allScope = $allowedOfficeIds;
        } else {
            $assignedOfficeId = (int) ($user?->office_id ?? 0);
            $allowedOfficeIds = $assignedOfficeId > 0 ? [$assignedOfficeId] : [-1];
            $allScope = $allowedOfficeIds;
        }

        $allowedOfficeIds = $this->normalizeOfficeScope($allowedOfficeIds);
        $officeOptions = $allOffices
            ->whereIn('id', $allowedOfficeIds)
            ->map(fn (Office $office) => [
                'id' => (int) $office->id,
                'name' => (string) $office->name,
                'type' => match ((int) ($office->office_types_id ?? 0)) {
                    1 => 'Regional Office',
                    2 => 'PENRO',
                    3 => 'CENRO',
                    default => 'Office',
                },
            ])
            ->values();

        $defaultSelection = $allowsAll
            ? 'all'
            : (string) ($allowedOfficeIds[0] ?? '');
        $requestedSelection = strtolower(trim((string) $request->query('office_id', $defaultSelection)));

        if ($allowsAll && $requestedSelection === 'all') {
            return [
                'selected' => 'all',
                'scope' => $allScope,
                'options' => $officeOptions,
                'allows_all' => true,
                'all_label' => $allLabel,
            ];
        }

        $requestedOfficeId = ctype_digit($requestedSelection) ? (int) $requestedSelection : 0;

        if (!in_array($requestedOfficeId, $allowedOfficeIds, true)) {
            if ($defaultSelection === 'all') {
                return [
                    'selected' => 'all',
                    'scope' => $allScope,
                    'options' => $officeOptions,
                    'allows_all' => true,
                    'all_label' => $allLabel,
                ];
            }

            $requestedOfficeId = (int) $defaultSelection;
        }

        return [
            'selected' => (string) $requestedOfficeId,
            'scope' => $requestedOfficeId !== 0 ? [$requestedOfficeId] : [-1],
            'options' => $officeOptions,
            'allows_all' => $allowsAll,
            'all_label' => $allLabel,
        ];
    }

    private function dashboardOfficeScopeNames(int|array|null $officeScope): array
    {
        $officeIds = $this->normalizeOfficeScope($officeScope);

        if (empty($officeIds) || !$this->hasTable('offices')) {
            return [];
        }

        $namesById = DB::table('offices')
            ->whereIn('id', $officeIds)
            ->pluck('name', 'id');

        return collect($officeIds)
            ->map(fn (int $scopedOfficeId) => (string) ($namesById[$scopedOfficeId] ?? ''))
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeOfficeScope(int|array|null $officeScope): array
    {
        return collect(is_array($officeScope) ? $officeScope : [$officeScope])
            ->map(fn ($officeId) => (int) $officeId)
            ->filter(fn (int $officeId) => $officeId !== 0)
            ->unique()
            ->values()
            ->all();
    }

    private function monthlyMap($rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $meta = $this->parseValuesJson($row->values ?? null);
            $rowId = (int) ($row->program_id ?? $meta['row_id'] ?? $meta['program_id'] ?? 0);
            $indicatorId = (int) ($row->indicator_id ?? $meta['indicator_id'] ?? 0);
            $officeId = (int) ($row->office_ids ?? 0);

            if ($rowId <= 0 || $indicatorId <= 0 || $officeId <= 0) {
                continue;
            }

            foreach ($this->monthColumns() as $monthColumn) {
                $key = "{$rowId}|{$indicatorId}|{$officeId}|{$monthColumn}";
                $map[$key] = (float) ($row->{$monthColumn} ?? 0);
            }
        }

        return $map;
    }

    private function monthlyMapFromAggregateRows($rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $rowId = (int) ($row->dashboard_program_id ?? 0);
            $indicatorId = (int) ($row->dashboard_indicator_id ?? 0);
            $officeId = (int) ($row->office_ids ?? 0);

            if ($rowId <= 0 || $indicatorId <= 0 || $officeId <= 0) {
                continue;
            }

            foreach ($this->monthColumns() as $monthColumn) {
                $key = "{$rowId}|{$indicatorId}|{$officeId}|{$monthColumn}";
                $map[$key] = (float) ($row->{$monthColumn} ?? 0);
            }
        }

        return $map;
    }

    private function dashboardProgramIdExpression(string $table): string
    {
        if ($this->hasColumn($table, 'row_id')) {
            return '`row_id`';
        }

        if ($this->hasColumn($table, 'program_id') && $this->hasColumn($table, 'values')) {
            return "COALESCE(NULLIF(`program_id`, 0), NULLIF(JSON_UNQUOTE(JSON_EXTRACT(`values`, '$.row_id')), ''), NULLIF(JSON_UNQUOTE(JSON_EXTRACT(`values`, '$.program_id')), ''), 0)";
        }

        if ($this->hasColumn($table, 'program_id')) {
            return '`program_id`';
        }

        return "COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(`values`, '$.row_id')), ''), NULLIF(JSON_UNQUOTE(JSON_EXTRACT(`values`, '$.program_id')), ''), 0)";
    }

    private function dashboardIndicatorIdExpression(string $table): string
    {
        if ($this->hasColumn($table, 'indicator_id') && $this->hasColumn($table, 'values')) {
            return "COALESCE(NULLIF(`indicator_id`, 0), NULLIF(JSON_UNQUOTE(JSON_EXTRACT(`values`, '$.indicator_id')), ''), 0)";
        }

        if ($this->hasColumn($table, 'indicator_id')) {
            return '`indicator_id`';
        }

        return "COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(`values`, '$.indicator_id')), ''), 0)";
    }

    private function monthColumns(): array
    {
        return ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
    }

    private function comparisonPeriodColumns(): array
    {
        return [
            'jan', 'feb', 'mar', 'q1',
            'apr', 'may', 'jun', 'q2',
            'jul', 'aug', 'sep', 'q3',
            'oct', 'nov', 'dec', 'q4',
        ];
    }

    private function parseValuesJson($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function applyYearFilter($query, string $yearColumn, int $year): void
    {
        $query->where($yearColumn, $year);
    }

    private function yearOptions(array $fieldConfigs, int $currentYear, int|array|null $officeScope = null)
    {
        $tables = collect($fieldConfigs)
            ->flatMap(fn (array $config) => [$config['targets'], $config['accomp']])
            ->merge(['financial_target', 'financial_accomplishment'])
            ->unique()
            ->values();

        $years = collect();

        foreach ($tables as $table) {
            if (!$this->hasTable($table)) {
                continue;
            }

            $yearColumn = $this->resolveYearColumn($table);
            if ($yearColumn === null) {
                continue;
            }

            $query = DB::table($table)
                ->whereNotNull($yearColumn);

            $officeColumn = $this->hasColumn($table, 'office_id') ? 'office_id' : 'office_ids';

            $officeIds = $this->normalizeOfficeScope($officeScope);

            if (!empty($officeIds) && $this->hasColumn($table, $officeColumn)) {
                $query->whereIn($officeColumn, $officeIds);
            }

            $years = $years->merge($query
                ->distinct()
                ->pluck($yearColumn)
                ->map(fn ($value) => $this->normalizeYearValue($value))
                ->filter(fn (int $value) => $value >= 2000 && $value <= 2100));
        }

        $years = $years
            ->push($currentYear)
            ->unique()
            ->sortDesc()
            ->values();

        return $years->isNotEmpty()
            ? $years
            : collect(range($currentYear + 1, 2020))->values();
    }

    private function normalizeYearValue($value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_numeric($decoded)) {
                return (int) $decoded;
            }

            return (int) trim($value, "\"' ");
        }

        return 0;
    }

    private function resolveYearColumn(string $table): ?string
    {
        if ($this->hasColumn($table, 'years')) {
            return 'years';
        }

        if ($this->hasColumn($table, 'year')) {
            return 'year';
        }

        return null;
    }

    private function hasTable(string $table): bool
    {
        return $this->tableExistsCache[$table] ??= Schema::hasTable($table);
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (!array_key_exists($table, $this->tableColumnsCache)) {
            $this->tableColumnsCache[$table] = collect(Schema::getColumnListing($table))
                ->mapWithKeys(fn (string $name) => [strtolower($name) => true])
                ->all();
        }

        return isset($this->tableColumnsCache[$table][strtolower($column)]);
    }
}
