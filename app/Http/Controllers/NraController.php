<?php

namespace App\Http\Controllers;

use App\Models\Gass_Physical;
use App\Models\Nra_Indicator;
use App\Models\Nra_Target;
use App\Models\Nra_Accomplishment;
use App\Models\Office;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class NraController extends Controller
{
    /**
     * Display the physical accomplishments list
     * - If a specific program is provided → show only entries for that program
     * - Otherwise → show entries for all programs
     */
    public function index(Request $request, $program = null)
    {
        $year = $request->query('year', now()->year);
        $office_id = $this->officeIdForPhysicalPage($request);
        $search = trim((string) $request->query('search', ''));

        $programId = $program !== null ? (int) $program : null;

        $sortProgramHierarchy = function ($row) {
            return $this->sourceOrderedHierarchySortValue($row->title_source_order ?? null, $row->title ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->program_source_order ?? null, $row->program ?? '')
                . '|' . $this->programHierarchySortValue($row->subprogram ?? '', $this->hierarchySortValue($row->subprogram ?? ''))
                . '|' . $this->programHierarchySortValue($row->subsubprogram ?? '', $this->hierarchySortValue($row->subsubprogram ?? ''))
                . '|' . $this->programHierarchySortValue($row->subsubsubprogram ?? '', $this->hierarchySortValue($row->subsubsubprogram ?? ''))
                . '|' . $this->programHierarchySortValue($row->subsubsubsubprogram ?? '', $this->hierarchySortValue($row->subsubsubsubprogram ?? ''))
                . '|' . $this->programHierarchySortValue($row->subsubsubsubsubprogram ?? '', $this->hierarchySortValue($row->subsubsubsubsubprogram ?? ''))
                . '|' . $this->sourceOrderedHierarchySortValue($row->project_source_order ?? null, $row->project ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->activities_source_order ?? null, $row->activities ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->subactivities_source_order ?? null, $row->subactivities ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->subsubactivities_source_order ?? null, $row->subsubactivities ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->level_6_source_order ?? null, $row->level_6 ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->level_7_source_order ?? null, $row->level_7 ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->level_8_source_order ?? null, $row->level_8 ?? '');
        };

        $programsRaw = $this->getNraPrograms($programId, $search, (int) $year)
            ->sortBy($sortProgramHierarchy, SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        // Group by normalized group key (program, project, activities, subactivities)
        $programs = $programsRaw
            ->unique($sortProgramHierarchy)
            ->values();

        $programIds = $programsRaw
            ->flatMap(function ($row) {
                return [
                    (int) ($row->id ?? 0),
                    (int) ($row->row_id ?? $row->id ?? 0),
                    (int) ($row->sub_activity_row_id ?? 0),
                ];
            })
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $indicatorTypeOptions = DB::table('indicator_types')
            ->select('id', 'name')
            ->orderBy('id')
            ->get();

        $indicatorTypeMap = $indicatorTypeOptions
            ->mapWithKeys(fn ($row) => [(int) $row->id => (string) $row->name]);

        $entries = Schema::hasTable('gass_physical')
            ? Gass_Physical::whereIn('programs_id', $programIds)
                ->where('year', $year)
                ->whereIn('office_id', $this->officeIdsForPhysicalPageScope($office_id))
                ->get()
            : collect();

        // Fetch indicators grouped by program from section metadata.
        $indicators = $this->getIndicatorsGroupedByProgram($programIds, (int) $year);
        $indicators = $this->filterIndicatorsForOffice($indicators, $office_id);
        $programsRaw = $this->filterProgramRowsForOffice($programsRaw, $indicators, $office_id);
        $programs = $this->filterProgramRowsForOffice($programs, $indicators, $office_id);

        $targets = Nra_Target::where('years', $year)
            ->when($this->shouldScopeToUserOffice(), fn ($query) => $query->whereIn('office_ids', $this->officeIdsForPhysicalPageScope($office_id)))
            ->get();
        $accomplishments = Nra_Accomplishment::where('years', $year)
            ->when($this->shouldScopeToUserOffice(), fn ($query) => $query->whereIn('office_ids', $this->officeIdsForPhysicalPageScope($office_id)))
            ->get();

        $programs = $programsRaw
            ->unique($sortProgramHierarchy)
            ->values();

        // Expand programs to include separate activity rows when they have indicators
        $programs = $programs->flatMap(function ($row) use ($indicators) {
            $rows = [];

            // Check level 8 to level 9 (if we have both level_7 and level_8)
            if (filled($row->level_7 ?? null) && filled($row->level_8 ?? null) && (int) ($row->level_8_row_id ?? 0) > 0) {
                $level8RowId = (int) $row->level_8_row_id;
                $hasLevel8Indicators = isset($indicators[$level8RowId]) && $indicators[$level8RowId]->count() > 0;
                
                if ($hasLevel8Indicators) {
                    $level8Row = clone $row;
                    $level8Row->row_id = $level8RowId;
                    $level8Row->level_8 = null;
                    $level8Row->_sort_priority = 0;
                    $rows[] = $level8Row;
                }
            }

            // Check level 7 to level 8 (if we have both level_6 and level_7)
            if (filled($row->level_6 ?? null) && filled($row->level_7 ?? null) && (int) ($row->level_7_row_id ?? 0) > 0) {
                $level7RowId = (int) $row->level_7_row_id;
                $hasLevel7Indicators = isset($indicators[$level7RowId]) && $indicators[$level7RowId]->count() > 0;
                
                if ($hasLevel7Indicators) {
                    $level7Row = clone $row;
                    $level7Row->row_id = $level7RowId;
                    $level7Row->level_7 = null;
                    $level7Row->level_8 = null;
                    $level7Row->_sort_priority = 1;
                    $rows[] = $level7Row;
                }
            }

            // Check sub-sub-sub-activity to level 7 (if we have both subsubactivities and level_6)
            if (filled($row->subsubactivities ?? null) && filled($row->level_6 ?? null) && (int) ($row->sub_sub_sub_activity_row_id ?? 0) > 0) {
                $subSubSubActivityRowId = (int) $row->sub_sub_sub_activity_row_id;
                $hasSubSubSubActivityIndicators = isset($indicators[$subSubSubActivityRowId]) && $indicators[$subSubSubActivityRowId]->count() > 0;
                
                if ($hasSubSubSubActivityIndicators) {
                    $subSubSubActivityRow = clone $row;
                    $subSubSubActivityRow->row_id = $subSubSubActivityRowId;
                    $subSubSubActivityRow->level_6 = null;
                    $subSubSubActivityRow->level_7 = null;
                    $subSubSubActivityRow->level_8 = null;
                    $subSubSubActivityRow->_sort_priority = 2;
                    $rows[] = $subSubSubActivityRow;
                }
            }

            // Check sub-sub-activity level (if we have both sub-sub-activity and sub-sub-sub-activity)
            if (filled($row->subactivities ?? null) && filled($row->subsubactivities ?? null) && (int) ($row->sub_sub_activity_row_id ?? 0) > 0) {
                $subSubActivityRowId = (int) $row->sub_sub_activity_row_id;
                $hasSubSubActivityIndicators = isset($indicators[$subSubActivityRowId]) && $indicators[$subSubActivityRowId]->count() > 0;
                
                if ($hasSubSubActivityIndicators) {
                    $subSubActivityRow = clone $row;
                    $subSubActivityRow->row_id = $subSubActivityRowId;
                    $subSubActivityRow->subsubactivities = null;
                    $subSubActivityRow->level_6 = null;
                    $subSubActivityRow->level_7 = null;
                    $subSubActivityRow->level_8 = null;
                    $subSubActivityRow->_sort_priority = 3;
                    $rows[] = $subSubActivityRow;
                }
            }

            // Check sub-activity level (if we have both activity and subactivity)
            if (filled($row->activities ?? null) && filled($row->subactivities ?? null) && (int) ($row->sub_activity_row_id ?? 0) > 0) {
                $subActivityRowId = (int) $row->sub_activity_row_id;
                $hasActivityIndicators = isset($indicators[$subActivityRowId]) && $indicators[$subActivityRowId]->count() > 0;
                
                if ($hasActivityIndicators) {
                    $activityRow = clone $row;
                    $activityRow->row_id = $subActivityRowId;
                    $activityRow->subactivities = null;
                    $activityRow->subsubactivities = null;
                    $activityRow->level_6 = null;
                    $activityRow->level_7 = null;
                    $activityRow->level_8 = null;
                    $activityRow->_sort_priority = 4;
                    $rows[] = $activityRow;
                }
            }

            if (!isset($row->_sort_priority)) {
                $row->_sort_priority = 5;
            }
            $rows[] = $row;
            return $rows;
        })
        ->sortBy(function ($row) {
            $priority = $row->_sort_priority ?? 5;
            return $this->sourceOrderedHierarchySortValue($row->title_source_order ?? null, $row->title ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->program_source_order ?? null, $row->program ?? '')
                . '|' . $this->programHierarchySortValue($row->subprogram ?? '', $this->hierarchySortValue($row->subprogram ?? ''))
                . '|' . $this->programHierarchySortValue($row->subsubprogram ?? '', $this->hierarchySortValue($row->subsubprogram ?? ''))
                . '|' . $this->programHierarchySortValue($row->subsubsubprogram ?? '', $this->hierarchySortValue($row->subsubsubprogram ?? ''))
                . '|' . $this->programHierarchySortValue($row->subsubsubsubprogram ?? '', $this->hierarchySortValue($row->subsubsubsubprogram ?? ''))
                . '|' . $this->programHierarchySortValue($row->subsubsubsubsubprogram ?? '', $this->hierarchySortValue($row->subsubsubsubsubprogram ?? ''))
                . '|' . $this->sourceOrderedHierarchySortValue($row->project_source_order ?? null, $row->project ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->activities_source_order ?? null, $row->activities ?? '')
                . '|' . $priority
                . '|' . $this->sourceOrderedHierarchySortValue($row->subactivities_source_order ?? null, $row->subactivities ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->subsubactivities_source_order ?? null, $row->subsubactivities ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->level_6_source_order ?? null, $row->level_6 ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->level_7_source_order ?? null, $row->level_7 ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->level_8_source_order ?? null, $row->level_8 ?? '');
        }, SORT_NATURAL | SORT_FLAG_CASE)
        ->unique(function ($row) {
            return strtolower(trim((string) ($row->title ?? ''))) . '|'
                . strtolower(trim((string) ($row->program ?? ''))) . '|'
                . strtolower(trim((string) ($row->project ?? ''))) . '|'
                . strtolower(trim((string) ($row->activities ?? ''))) . '|'
                . strtolower(trim((string) ($row->subactivities ?? ''))) . '|'
                . (int) ($row->row_id ?? 0);
        })
        ->values();

        // Also expand programsRaw for consistency
        $programsRaw = $programsRaw->flatMap(function ($row) use ($indicators) {
            $rows = [];

            // Check level 8 to level 9
            if (filled($row->level_7 ?? null) && filled($row->level_8 ?? null) && (int) ($row->level_8_row_id ?? 0) > 0) {
                $level8RowId = (int) $row->level_8_row_id;
                $hasLevel8Indicators = isset($indicators[$level8RowId]) && $indicators[$level8RowId]->count() > 0;
                
                if ($hasLevel8Indicators) {
                    $level8Row = clone $row;
                    $level8Row->row_id = $level8RowId;
                    $level8Row->level_8 = null;
                    $level8Row->_sort_priority = 0;
                    $rows[] = $level8Row;
                }
            }

            // Check level 7 to level 8
            if (filled($row->level_6 ?? null) && filled($row->level_7 ?? null) && (int) ($row-> level_7_row_id ?? 0) > 0) {
                $level7RowId = (int) $row->level_7_row_id;
                $hasLevel7Indicators = isset($indicators[$level7RowId]) && $indicators[$level7RowId]->count() > 0;
                
                if ($hasLevel7Indicators) {
                    $level7Row = clone $row;
                    $level7Row->row_id = $level7RowId;
                    $level7Row->level_7 = null;
                    $level7Row->level_8 = null;
                    $level7Row->_sort_priority = 1;
                    $rows[] = $level7Row;
                }
            }

            // Check sub-sub-sub-activity to level 7
            if (filled($row->subsubactivities ?? null) && filled($row->level_6 ?? null) && (int) ($row->sub_sub_sub_activity_row_id ?? 0) > 0) {
                $subSubSubActivityRowId = (int) $row->sub_sub_sub_activity_row_id;
                $hasSubSubSubActivityIndicators = isset($indicators[$subSubSubActivityRowId]) && $indicators[$subSubSubActivityRowId]->count() > 0;
                
                if ($hasSubSubSubActivityIndicators) {
                    $subSubSubActivityRow = clone $row;
                    $subSubSubActivityRow->row_id = $subSubSubActivityRowId;
                    $subSubSubActivityRow->level_6 = null;
                    $subSubSubActivityRow->level_7 = null;
                    $subSubSubActivityRow->level_8 = null;
                    $subSubSubActivityRow->_sort_priority = 2;
                    $rows[] = $subSubSubActivityRow;
                }
            }

            // Check sub-sub-activity level
            if (filled($row->subactivities ?? null) && filled($row->subsubactivities ?? null) && (int) ($row->sub_sub_activity_row_id ?? 0) > 0) {
                $subSubActivityRowId = (int) $row->sub_sub_activity_row_id;
                $hasSubSubActivityIndicators = isset($indicators[$subSubActivityRowId]) && $indicators[$subSubActivityRowId]->count() > 0;
                
                if ($hasSubSubActivityIndicators) {
                    $subSubActivityRow = clone $row;
                    $subSubActivityRow->row_id = $subSubActivityRowId;
                    $subSubActivityRow->subsubactivities = null;
                    $subSubActivityRow->level_6 = null;
                    $subSubActivityRow->level_7 = null;
                    $subSubActivityRow->level_8 = null;
                    $subSubActivityRow->_sort_priority = 3;
                    $rows[] = $subSubActivityRow;
                }
            }

            // Check sub-activity level
            if (filled($row->activities ?? null) && filled($row->subactivities ?? null) && (int) ($row->sub_activity_row_id ?? 0) > 0) {
                $subActivityRowId = (int) $row->sub_activity_row_id;
                $hasActivityIndicators = isset($indicators[$subActivityRowId]) && $indicators[$subActivityRowId]->count() > 0;
                
                if ($hasActivityIndicators) {
                    $activityRow = clone $row;
                    $activityRow->row_id = $subActivityRowId;
                    $activityRow->subactivities = null;
                    $activityRow->subsubactivities = null;
                    $activityRow->level_6 = null;
                    $activityRow->level_7 = null;
                    $activityRow->level_8 = null;
                    $activityRow->_sort_priority = 4;
                    $rows[] = $activityRow;
                }
            }

            if (!isset($row->_sort_priority)) {
                $row->_sort_priority = 5;
            }
            $rows[] = $row;
            return $rows;
        })
        ->sortBy(function ($row) {
            $priority = $row->_sort_priority ?? 5;
            return $this->sourceOrderedHierarchySortValue($row->title_source_order ?? null, $row->title ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->program_source_order ?? null, $row->program ?? '')
                . '|' . $this->programHierarchySortValue($row->subprogram ?? '', $this->hierarchySortValue($row->subprogram ?? ''))
                . '|' . $this->programHierarchySortValue($row->subsubprogram ?? '', $this->hierarchySortValue($row->subsubprogram ?? ''))
                . '|' . $this->programHierarchySortValue($row->subsubsubprogram ?? '', $this->hierarchySortValue($row->subsubsubprogram ?? ''))
                . '|' . $this->programHierarchySortValue($row->subsubsubsubprogram ?? '', $this->hierarchySortValue($row->subsubsubsubprogram ?? ''))
                . '|' . $this->programHierarchySortValue($row->subsubsubsubsubprogram ?? '', $this->hierarchySortValue($row->subsubsubsubsubprogram ?? ''))
                . '|' . $this->sourceOrderedHierarchySortValue($row->project_source_order ?? null, $row->project ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->activities_source_order ?? null, $row->activities ?? '')
                . '|' . $priority
                . '|' . $this->sourceOrderedHierarchySortValue($row->subactivities_source_order ?? null, $row->subactivities ?? '');
        }, SORT_NATURAL | SORT_FLAG_CASE)
        ->unique(function ($row) {
            return strtolower(trim((string) ($row->title ?? ''))) . '|'
                . strtolower(trim((string) ($row->program ?? ''))) . '|'
                . strtolower(trim((string) ($row->project ?? ''))) . '|'
                . strtolower(trim((string) ($row->activities ?? ''))) . '|'
                . strtolower(trim((string) ($row->subactivities ?? ''))) . '|'
                . (int) ($row->row_id ?? 0);
        })
        ->values();

        // Also expand programsRaw for consistency (already expanded above)
        $programsRaw = $programsRaw->sortBy(function ($row) {
            $priority = $row->_sort_priority ?? 5;
            return $this->sourceOrderedHierarchySortValue($row->title_source_order ?? null, $row->title ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->program_source_order ?? null, $row->program ?? '')
                . '|' . $this->programHierarchySortValue($row->subprogram ?? '', $this->hierarchySortValue($row->subprogram ?? ''))
                . '|' . $this->programHierarchySortValue($row->subsubprogram ?? '', $this->hierarchySortValue($row->subsubprogram ?? ''))
                . '|' . $this->programHierarchySortValue($row->subsubsubprogram ?? '', $this->hierarchySortValue($row->subsubsubprogram ?? ''))
                . '|' . $this->programHierarchySortValue($row->subsubsubsubprogram ?? '', $this->hierarchySortValue($row->subsubsubsubprogram ?? ''))
                . '|' . $this->programHierarchySortValue($row->subsubsubsubsubprogram ?? '', $this->hierarchySortValue($row->subsubsubsubsubprogram ?? ''))
                . '|' . $this->sourceOrderedHierarchySortValue($row->project_source_order ?? null, $row->project ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->activities_source_order ?? null, $row->activities ?? '')
                . '|' . $priority
                . '|' . $this->sourceOrderedHierarchySortValue($row->subactivities_source_order ?? null, $row->subactivities ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->subsubactivities_source_order ?? null, $row->subsubactivities ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->level_6_source_order ?? null, $row->level_6 ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->level_7_source_order ?? null, $row->level_7 ?? '')
                . '|' . $this->sourceOrderedHierarchySortValue($row->level_8_source_order ?? null, $row->level_8 ?? '');
        }, SORT_NATURAL | SORT_FLAG_CASE)
        ->unique(function ($row) {
            return strtolower(trim((string) ($row->title ?? ''))) . '|'
                . strtolower(trim((string) ($row->program ?? ''))) . '|'
                . strtolower(trim((string) ($row->project ?? ''))) . '|'
                . strtolower(trim((string) ($row->activities ?? ''))) . '|'
                . strtolower(trim((string) ($row->subactivities ?? ''))) . '|'
                . strtolower(trim((string) ($row->subsubactivities ?? ''))) . '|'
                . strtolower(trim((string) ($row->level_6 ?? ''))) . '|'
                . strtolower(trim((string) ($row->level_7 ?? ''))) . '|'
                . strtolower(trim((string) ($row->level_8 ?? ''))) . '|'
                . (int) ($row->row_id ?? 0);
        })
        ->values();

        $allIndicatorOfficeIds = $indicators
            ->flatten(1)
            ->flatMap(function ($indicator) {
                return collect($indicator->office_id ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0);
            })
            ->unique()
            ->values();

        $officeNameById = $allIndicatorOfficeIds->isEmpty()
            ? collect()
            : Office::query()
                ->whereIn('id', $allIndicatorOfficeIds->all())
                ->get(['id', 'name'])
                ->mapWithKeys(fn ($office) => [(int) $office->id => (string) $office->name]);

        // Prepare indicators data for JavaScript (flat structure by program_id)
        $indicatorsForJs = [];
        foreach ($indicators as $programId => $programIndicators) {
            $indicatorsForJs[$programId] = $programIndicators->map(function ($indicator) use ($programId, $officeNameById, $indicatorTypeMap) {
                $officeIds = collect($indicator->office_id ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->values();

                $officeList = $officeIds
                    ->map(function ($officeId) use ($officeNameById) {
                        $name = (string) ($officeNameById->get((int) $officeId) ?? '');
                        if ($name === '') {
                            return null;
                        }

                        return [
                            'id' => (int) $officeId,
                            'name' => $name,
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'id' => $indicator->id,
                    'name' => $indicator->name,
                    'indicator_type_id' => (int) ($indicator->indicator_type_id ?? 0) ?: null,
                    'indicator_type' => $indicatorTypeMap[(int) ($indicator->indicator_type_id ?? 0)] ?? '',
                    'program_id' => (int) $programId,
                    'office_id' => $officeIds->first(),
                    'office_ids' => $officeIds->all(),
                    'office_list' => $officeList,
                ];
            })->toArray();
        }
        
        $existing = $entries->keyBy('programs_id');

        // Transform targets into keyed structure
        $targets = $targets
            ->reduce(function (array $carry, $row) {
                $meta = $this->parseSectionValues($row->values ?? null);
                $rowId = (int) ($meta['row_id'] ?? $meta['program_id'] ?? 0);
                $indicatorId = (int) ($meta['indicator_id'] ?? 0);
                if ($rowId <= 0 || $indicatorId <= 0) {
                    return $carry;
                }

                $officeKey = (string) ((int) ($row->office_ids ?? 0));
                $carry[(string) $rowId][(string) $indicatorId][$officeKey] = $this->formatSectionRecordForJs($row, $meta);

                return $carry;
            }, []);

        // Transform accomplishments into keyed structure
        $accomplishments = $accomplishments
            ->reduce(function (array $carry, $row) {
                $meta = $this->parseSectionValues($row->values ?? null);
                $rowId = (int) ($meta['row_id'] ?? $meta['program_id'] ?? 0);
                $indicatorId = (int) ($meta['indicator_id'] ?? 0);
                if ($rowId <= 0 || $indicatorId <= 0) {
                    return $carry;
                }

                $officeKey = (string) ((int) ($row->office_ids ?? 0));
                $carry[(string) $rowId][(string) $indicatorId][$officeKey] = $this->formatSectionRecordForJs($row, $meta);

                return $carry;
            }, []);

        $targets = $this->filterSectionDataForOffice($targets, $office_id);
        $accomplishments = $this->filterSectionDataForOffice($accomplishments, $office_id);
        $pendingTotalsByRow = $this->buildPendingTotalsByRow($targets, $accomplishments);

        $papTitles = $programs->pluck('title')
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->sort()
            ->values();

        $papProjects = $programs->pluck('project')
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->sort()
            ->values();

        $papActivities = $programs->pluck('activities')
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->sort()
            ->values();

        $papSubactivities = $programs->pluck('subactivities')
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->sort()
            ->values();

        $papSubSubactivities = $programs->pluck('subsubactivities')
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->sort()
            ->values();
        
        $papPrefillData = $programsRaw
            ->groupBy($sortProgramHierarchy)
            ->map(function ($groupPrograms) use ($indicators, $indicatorTypeMap) {
                $pap = $groupPrograms->first();

                $indicatorRows = $groupPrograms
                    ->flatMap(function ($row) use ($indicators) {
                        $rowId = (int) ($row->row_id ?? $row->id);
                        $rootId = (int) ($row->id ?? 0);
                        $indicatorCollection = $indicators[$rowId] ?? $indicators[$rootId] ?? collect();

                        return $indicatorCollection->map(function ($indicator) use ($rowId, $rootId) {
                            $indicatorClone = clone $indicator;
                            $indicatorClone->row_id = $rowId > 0 ? $rowId : $rootId;
                            return $indicatorClone;
                        });
                    })
                    ->unique(fn ($indicator) => (int) ($indicator->id ?? 0))
                    ->values();

                return [
                    'id' => (int) $pap->id,
                    'row_id' => (int) ($pap->row_id ?? $pap->id),
                    'title' => (string) ($pap->title ?? ''),
                    'subprogram' => (string) ($pap->subprogram ?? ''),
                    'subsubprogram' => (string) ($pap->subsubprogram ?? ''),
                    'subsubsubprogram' => (string) ($pap->subsubsubprogram ?? ''),
                    'subsubsubsubprogram' => (string) ($pap->subsubsubsubprogram ?? ''),
                    'subsubsubsubsubprogram' => (string) ($pap->subsubsubsubsubprogram ?? ''),
                    'program' => (string) ($pap->program ?? ''),
                    'project' => (string) ($pap->project ?? ''),
                    'activities' => (string) ($pap->activities ?? ''),
                    'subactivities' => (string) ($pap->subactivities ?? ''),
                    'subsubactivities' => (string) ($pap->subsubactivities ?? ''),
                    'level_6' => (string) ($pap->level_6 ?? ''),
                    'level_7' => (string) ($pap->level_7 ?? ''),
                    'level_8' => (string) ($pap->level_8 ?? ''),
                    'indicators' => $indicatorRows
                        ->map(function ($indicator) use ($indicatorTypeMap) {
                            return [
                                'id' => (int) $indicator->id,
                                'row_id' => (int) ($indicator->row_id ?? 0) ?: null,
                                'name' => (string) ($indicator->name ?? ''),
                                'indicator_type_id' => (int) ($indicator->indicator_type_id ?? 0) ?: null,
                                'indicator_type' => (string) ($indicatorTypeMap[(int) ($indicator->indicator_type_id ?? 0)] ?? ''),
                                'office_ids' => collect($indicator->office_id ?? [])
                                    ->map(fn ($id) => (int) $id)
                                    ->filter()
                                    ->values()
                                    ->all(),
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
        
        // Get all offices organized by parent for the modal
        $offices = Office::groupedForUi();

        // Build a clean descending list of years from the ppa database, always including the selected year.
        $currentYear = (int) now()->year;
        $yearOptions = DB::table('ppa')
            ->whereNotNull('year')
            ->where('year', '>', 0)
            ->distinct()
            ->pluck('year')
            ->map(fn ($y) => (int) $y)
            ->push((int) $year)
            ->unique()
            ->sort()
            ->reverse()
            ->values()
            ->all();

        return view($this->roleView('nra.nra_physical'), compact(
            'entries',
            'programs',
            'programsRaw',
            'existing',
            'indicators',
            'indicatorsForJs',
            'targets',
            'accomplishments',
            'pendingTotalsByRow',
            'offices',
            'papTitles',
            'papProjects',
            'papActivities',
            'papSubactivities',
            'papSubSubactivities',
            'papPrefillData',
            'indicatorTypeOptions',
            'year',
            'yearOptions',
            'office_id',
            'search',
            'program'
        ));
    }

    /**
     * Overview page showing all programs
     */
    public function overview()
    {
        $allPrograms = $this->getNraPrograms();
        $indicators = $this->getIndicatorsGroupedByProgram($allPrograms->pluck('id')->all());
        
        // Get all offices organized by parent with children loaded
        $offices = Office::groupedForUi();
        
        // Group programs by title, program, project, and activities
        $grouped = $allPrograms->groupBy(function ($program) {
            return json_encode([
                'title' => $program->title,
                'program' => $program->program,
                'project' => $program->project,
                'activities' => $program->activities,
            ]);
        })->map(function ($group) {
            // Build a map of subactivities to their original programs with indicators
            $subactivitiesMap = [];
            
            foreach ($group as $program) {
                $subs = collect(explode("\n", trim($program->subactivities ?? '')))->filter(fn($s) => trim($s));
                foreach ($subs as $sub) {
                    $trimmedSub = trim($sub);
                    if (!isset($subactivitiesMap[$trimmedSub])) {
                        $subactivitiesMap[$trimmedSub] = [];
                    }
                    $subactivitiesMap[$trimmedSub][] = [
                        'program_id' => $program->id,
                        'indicator' => ($program->indicators ?? collect())->first(),
                    ];
                }
            }

            // Use the first program as the base
            $base = $group->first();
            $base->subactivities_map = $subactivitiesMap;
            $base->group_programs = $group;
            
            return $base;
        })->values();

        $grouped->each(function ($program) use ($indicators) {
            $program->indicator = ($indicators[$program->id] ?? collect())->first();
            $program->indicators = $indicators[$program->id] ?? collect();
        });

        $programs = $grouped;
        return view('admin.nra.nra', compact('programs', 'offices'));
    }

    /**
     * Store or update physical accomplishment entries
     */
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), $this->getValidationRules());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $userId = Auth::id();
        $officeId = $request->input('office_id', 1);

        $createdCount = 0;
        $updatedCount = 0;
        $firstProgramId = null;

        foreach ($request->entries as $data) {

            // Capture the first program ID to use for redirect
            if ($firstProgramId === null) {
                $firstProgramId = $data['programs_id'] ?? null;
            }

            // Skip empty rows
            $hasMeaningfulData = collect($data)
                ->except(['programs_id', 'year', 'period_type'])
                ->filter(fn($v) => $v !== null && $v !== '' && $v !== '')
                ->isNotEmpty();

            if (!$hasMeaningfulData) {
                continue;
            }

            $indicatorName = trim((string) ($data['performance_indicator'] ?? ''));
            $matchAttributes = [
                'programs_id' => $data['programs_id'],
                'year' => $data['year'],
                'period_type' => $data['period_type'],
                'office_id' => $officeId,
                'performance_indicator' => $indicatorName !== '' ? $indicatorName : null,
            ];

            // Keep different indicators under the same hierarchy as separate rows.
            $existing = Gass_Physical::where($matchAttributes)->first();

            $record = Gass_Physical::updateOrCreate(
                $matchAttributes,
                [
                    'user_id' => $userId,
                    'performance_indicator' => $indicatorName !== '' ? $indicatorName : null,
                    'target' => $data['target'] ?? 0,
                    'jan' => $data['jan'] ?? 0,
                    'feb' => $data['feb'] ?? 0,
                    'mar' => $data['mar'] ?? 0,
                    'apr' => $data['apr'] ?? 0,
                    'may' => $data['may'] ?? 0,
                    'jun' => $data['jun'] ?? 0,
                    'jul' => $data['jul'] ?? 0,
                    'aug' => $data['aug'] ?? 0,
                    'sep' => $data['sep'] ?? 0,
                    'oct' => $data['oct'] ?? 0,
                    'nov' => $data['nov'] ?? 0,
                    'dec' => $data['dec'] ?? 0,
                    'q1' => $data['q1'] ?? 0,
                    'q2' => $data['q2'] ?? 0,
                    'q3' => $data['q3'] ?? 0,
                    'q4' => $data['q4'] ?? 0,
                    'first_half' => $data['first_half'] ?? 0,
                    'second_half' => $data['second_half'] ?? 0,
                    'annual_total' => $data['annual_total'] ?? 0,
                    'remarks' => $data['remarks'] ?? null,
                ]
            );

            if ($existing) {
                $updatedCount++;
            } else {
                $createdCount++;
            }
        }

        // Redirect back to the physical page with the program ID if available
        $redirectRoute = $firstProgramId 
            ? route('admin.nra_physical.physical', $firstProgramId)
            : route('admin.nra_physical.physical');

        return redirect($redirectRoute)
            ->with(
                'success',
                "$createdCount created, $updatedCount updated successfully."
            );
    }
           

    private function getValidationRules()
    {
        return [
            'entries' => 'required|array',
        ];
    }


    public function indicatorsIndex(Request $request)
    {
        $indicators = Nra_Indicator::latest()->get();

        return view('admin.nra.indicators', compact('indicators'));
    }

    /**
     * Show form to create new indicator
     */
    public function createIndicator()
    {
        return view('admin.nra.indicator_create');
    }

    public function storePap(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'program' => 'required_with:subprogram,subsubprogram,subsubsubprogram,subsubsubsubprogram,subsubsubsubsubprogram|nullable|string|max:150',
            'subprogram' => 'required_with:subsubprogram,subsubsubprogram,subsubsubsubprogram,subsubsubsubsubprogram|nullable|string|max:150',
            'subsubprogram' => 'required_with:subsubsubprogram,subsubsubsubprogram,subsubsubsubsubprogram|nullable|string|max:150',
            'subsubsubprogram' => 'required_with:subsubsubsubprogram,subsubsubsubsubprogram|nullable|string|max:150',
            'subsubsubsubprogram' => 'required_with:subsubsubsubsubprogram|nullable|string|max:150',
            'subsubsubsubsubprogram' => 'nullable|string|max:150',
            'project' => 'nullable|string|max:150',
            'activities' => 'nullable|string|max:255',
            'subactivities' => 'nullable|string|max:255',
            'subsubactivities' => 'nullable|string|max:255',
            'level_6' => 'nullable|string|max:255',
            'level_7' => 'nullable|string|max:255',
            'level_8' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:2000|max:2099',
        ]);

        DB::beginTransaction();
        try {
            $pap = $this->storePapHierarchyInPpa($validated);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'PAP saved successfully.',
                'pap' => $pap,
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    protected function storePapHierarchyInPpa(array $papData): object
    {
        $typeId = $this->getNraTypeId();
        $recordTypeIds = $this->getNraRecordTypeIds();
        $papYear = isset($papData['year']) ? (int) $papData['year'] : null;
        $forceDuplicateLeaf = !empty($papData['duplicate_leaf']);

        $levels = [
            ['record_type' => 'PROGRAM', 'name' => trim((string) ($papData['title'] ?? ''))],
            ['record_type' => 'PROJECT', 'name' => trim((string) ($papData['program'] ?? ''))],
            ['record_type' => 'MAIN ACTIVITY', 'name' => trim((string) ($papData['project'] ?? ''))],
            ['record_type' => 'SUB-ACTIVITY', 'name' => trim((string) ($papData['activities'] ?? ''))],
            ['record_type' => 'SUB-SUB-ACTIVITY', 'name' => trim((string) ($papData['subactivities'] ?? ''))],
            ['record_type' => 'SUB-SUB-SUB-ACTIVITY', 'name' => trim((string) ($papData['subsubactivities'] ?? ''))],
            ['record_type' => 'LEVEL-7', 'name' => trim((string) ($papData['level_6'] ?? ''))],
            ['record_type' => 'LEVEL-8', 'name' => trim((string) ($papData['level_7'] ?? ''))],
            ['record_type' => 'LEVEL-9', 'name' => trim((string) ($papData['level_8'] ?? ''))],
        ];
        $lastLevelIndex = collect($levels)
            ->keys()
            ->filter(fn ($index) => $levels[$index]['name'] !== '')
            ->last();

        $parentDetailId = null;
        $rootPpaId = null;
        $leafPpaId = null;
        $isNewHierarchy = true;

        foreach ($levels as $index => $level) {
            if ($level['name'] === '') {
                continue;
            }

            $recordTypeId = $recordTypeIds[$level['record_type']] ?? null;

            if (!$recordTypeId) {
                throw new \RuntimeException("Record type {$level['record_type']} is not configured.");
            }

            $existingNode = null;
            if (!$forceDuplicateLeaf || $index !== $lastLevelIndex) {
                $existingNode = DB::table('ppa_details as details')
                    ->join('ppa', 'ppa.ppa_details_id', '=', 'details.id')
                    ->where('ppa.types_id', $typeId)
                    ->where('ppa.record_type_id', $recordTypeId)
                    ->where('details.column_order', $index + 1)
                    ->when($index === 0, function ($query) use ($papData) {
                        foreach (['subprogram', 'subsubprogram', 'subsubsubprogram', 'subsubsubsubprogram', 'subsubsubsubsubprogram'] as $field) {
                            $value = trim((string) ($papData[$field] ?? ''));
                            $column = DB::connection()->getQueryGrammar()->wrap('details.'.$field);
                            $query->whereRaw("LOWER(TRIM(COALESCE({$column}, ''))) = ?", [strtolower($value)]);
                        }
                    })
                    ->when(
                        $parentDetailId === null,
                        fn ($query) => $query->whereNull('details.parent_id'),
                        fn ($query) => $query->where('details.parent_id', $parentDetailId)
                    )
                    ->whereRaw('LOWER(TRIM('.DB::connection()->getQueryGrammar()->wrap('ppa.name').')) = ?', [strtolower($level['name'])])
                    ->when($index === 0 && $papYear !== null, function ($query) use ($papYear) {
                        $query->where('ppa.year', $papYear);
                    })
                    ->orderBy('ppa.id')
                    ->select('ppa.id', 'details.id as detail_id')
                    ->first();
            }

            if ($existingNode) {
                $detailId = (int) $existingNode->detail_id;
                $ppaId = (int) $existingNode->id;
            } else {
                $isNewHierarchy = false;
                $detailId = DB::table('ppa_details')->insertGetId([
                    'parent_id' => $parentDetailId,
                    'column_order' => $index + 1,
                    'subprogram' => $index === 0 ? (trim((string) ($papData['subprogram'] ?? '')) ?: null) : null,
                    'subsubprogram' => $index === 0 ? (trim((string) ($papData['subsubprogram'] ?? '')) ?: null) : null,
                    'subsubsubprogram' => $index === 0 ? (trim((string) ($papData['subsubsubprogram'] ?? '')) ?: null) : null,
                    'subsubsubsubprogram' => $index === 0 ? (trim((string) ($papData['subsubsubsubprogram'] ?? '')) ?: null) : null,
                    'subsubsubsubsubprogram' => $index === 0 ? (trim((string) ($papData['subsubsubsubsubprogram'] ?? '')) ?: null) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $ppaInsertData = [
                    'name' => $level['name'],
                    'types_id' => $typeId,
                    'record_type_id' => $recordTypeId,
                    'ppa_details_id' => $detailId,
                    'indicator_id' => null,
                    'office_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Add year to the root (first level) PPA record
                if ($index === 0 && $papYear !== null) {
                    $ppaInsertData['year'] = $papYear;
                }

                $ppaId = DB::table('ppa')->insertGetId($ppaInsertData);
            }

            if ($rootPpaId === null) {
                $rootPpaId = $ppaId;
            }

            $leafPpaId = $ppaId;
            $parentDetailId = $detailId;
        }

        if ($rootPpaId === null) {
            throw new \RuntimeException('No PPA hierarchy rows were created.');
        }

        return (object) [
            'id' => $rootPpaId,
            'row_id' => $leafPpaId ?? $rootPpaId,
            'title' => (string) ($papData['title'] ?? ''),
            'program' => (string) ($papData['program'] ?? ''),
            'subprogram' => (string) ($papData['subprogram'] ?? ''),
            'subsubprogram' => (string) ($papData['subsubprogram'] ?? ''),
            'subsubsubprogram' => (string) ($papData['subsubsubprogram'] ?? ''),
            'subsubsubsubprogram' => (string) ($papData['subsubsubsubprogram'] ?? ''),
            'subsubsubsubsubprogram' => (string) ($papData['subsubsubsubsubprogram'] ?? ''),
            'project' => (string) ($papData['project'] ?? ''),
            'activities' => (string) ($papData['activities'] ?? ''),
            'subactivities' => (string) ($papData['subactivities'] ?? ''),
        ];
    }

    private function hierarchySortValue($value, bool $singleIAsRoman = false): string
    {
        $raw = trim((string) ($value ?? ''));
        $normalized = strtolower($raw);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        if ($normalized === '') {
            return '4|999999.999999.999999.999999.999999|';
        }

        if (
            preg_match('/^(\d+(?:\.\d+)*)([.)-]+)(?=\s|[A-Za-z(]|$)/u', $normalized, $matches)
            || preg_match('/^(\d+(?:\.\d+)+)(?=\s|[A-Za-z(]|$)/u', $normalized, $matches)
        ) {
            if (($matches[2] ?? '') !== '' || str_contains($matches[1], '.')) {
                $segments = array_map('intval', explode('.', rtrim($matches[1], '.')));
                $segments = array_pad($segments, 5, 0);
                $numericKey = collect(array_slice($segments, 0, 5))
                    ->map(fn ($segment) => str_pad((string) $segment, 6, '0', STR_PAD_LEFT))
                    ->implode('.');

                return '0|' . $numericKey . '|' . $normalized;
            }
        }

        if (preg_match('/^([VX]|[IVXLCDM]{2,})[.)-]\s*/', $raw, $matches) || ($singleIAsRoman && preg_match('/^(I)[.)-]\s*/', $raw, $matches))) {
            return '1|' . str_pad((string) $this->romanToInteger($matches[1]), 6, '0', STR_PAD_LEFT) . '|' . $normalized;
        }

        if (preg_match('/^([A-Za-z])[.)-]\s*/', $raw, $matches)) {
            return '2|' . str_pad((string) (ord(strtoupper($matches[1])) - 64), 6, '0', STR_PAD_LEFT) . '|' . $normalized;
        }

        return '3|' . $normalized;
    }

    private function sourceOrderedHierarchySortValue($sourceOrder, $value): string
    {
        $sourceOrder = (int) ($sourceOrder ?? 0);
        if ($sourceOrder > 0 && trim((string) ($value ?? '')) !== '') {
            return '0|' . str_pad((string) $sourceOrder, 10, '0', STR_PAD_LEFT)
                . '|' . $this->hierarchySortValue($value);
        }

        return '1|' . $this->hierarchySortValue($value);
    }

    private function romanToInteger(string $roman): int
    {
        $map = ['i' => 1, 'v' => 5, 'x' => 10, 'l' => 50, 'c' => 100, 'd' => 500, 'm' => 1000];
        $roman = strtolower($roman);
        $value = 0;
        $length = strlen($roman);

        for ($index = 0; $index < $length; $index++) {
            $current = $map[$roman[$index]] ?? 0;
            $next = $index + 1 < $length ? ($map[$roman[$index + 1]] ?? 0) : 0;
            $value += $current < $next ? -$current : $current;
        }

        return $value;
    }

    public function destroyPap($program)
    {
        DB::beginTransaction();
        try {
               $groupIds = collect(request()->input('group_ids', []))
                   ->map(fn ($id) => (int) $id)
                   ->filter(fn ($id) => $id > 0)
                   ->unique()
                   ->values()
                   ->all();

               if (empty($groupIds)) {
                   $groupIds = [(int) $program];
               }

               foreach ($groupIds as $programId) {
                   $programRow = $this->findNraProgram($programId);
                   if (!$programRow) {
                       continue;
                   }

                   $detailIds = $this->collectPpaDetailTreeIds((int) $programRow->ppa_details_id);

                   $this->deleteProgramSectionRows($programRow->id, Nra_Target::query()->get());
                   $this->deleteProgramSectionRows($programRow->id, Nra_Accomplishment::query()->get());

                   DB::table('ppa')->whereIn('ppa_details_id', $detailIds)->delete();
                   DB::table('ppa_details')->whereIn('id', array_reverse($detailIds))->delete();
               }

            DB::commit();
            return redirect()->back()->with('success', 'PAP deleted successfully.');
        } catch (\Throwable $exception) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete PAP. Please try again.');
        }
    }

    /**
     * Store new indicator
     */
    public function storeIndicator(Request $request)
    {
        $programExistsRule = Rule::exists('ppa', 'id')->where(function ($query) {
            $query->where('types_id', $this->getNraTypeId())
                ->where('record_type_id', $this->getNraRecordTypeIds()['PROGRAM']);
        });

        $rowExistsRule = Rule::exists('ppa', 'id')->where(function ($query) {
            $query->where('types_id', $this->getNraTypeId());
        });

        $baseValidated = $request->validate([
            'indicator_name' => 'required|string',
            'indicator_type_id' => 'nullable|exists:indicator_types,id',
            'program_id' => ['required', $programExistsRule],
            'row_id' => ['nullable', $rowExistsRule],
            'office_id' => 'required|array|min:1',
            'office_id.*' => 'required|exists:offices,id',
        ]);

        $indicatorName = trim($baseValidated['indicator_name']);
        $targetRowId = (int) ($baseValidated['row_id'] ?? $baseValidated['program_id'] ?? 0);

        $indicator = new Nra_Indicator();
        $indicator->name = $indicatorName;

        if ($this->hasIndicatorColumn('user_id')) {
            $indicator->user_id = Auth::id();
        }

        if ($this->hasIndicatorColumn('indicator_type_id')) {
            $indicator->indicator_type_id = array_key_exists('indicator_type_id', $baseValidated)
                ? ((int) ($baseValidated['indicator_type_id'] ?? 0) ?: null)
                : null;
        }

        $selectedOfficeIds = collect($baseValidated['office_id'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($this->hasIndicatorColumn('office_id')) {
            $indicator->office_id = $selectedOfficeIds;
        }

        $indicator->save();

        $resolvedRowId = $this->resolveIndicatorTargetRowId($targetRowId, $indicatorName);
        $this->syncProgramIndicatorInPpa($resolvedRowId, (int) $indicator->id, $selectedOfficeIds);

        $createdCount = 1;
        $updatedCount = 0;
        $message = "$createdCount created, $updatedCount updated successfully.";

        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'message' => $message,
                'success' => true,
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'row_id' => $resolvedRowId,
                'indicator' => $indicator,
            ]);
        }

        return redirect()->route('gass_physical')
            ->with('success', $message);
    }

    /**
     * Update indicator
     */
    public function editProgram($program)
    {
        $program = $this->findNraProgram((int) $program);

        if (!$program) {
            abort(404);
        }

        return view('admin.nra.program_edit', compact('program'));
    }

public function update(Request $request, Nra_Indicator $indicator)
{
    $programExistsRule = Rule::exists('ppa', 'id')->where(function ($query) {
        $query->where('types_id', $this->getNraTypeId())
            ->where('record_type_id', $this->getNraRecordTypeIds()['PROGRAM']);
    });

    $rowExistsRule = Rule::exists('ppa', 'id')->where(function ($query) {
        $query->where('types_id', $this->getNraTypeId());
    });

    $validated = $request->validate([
        'indicator_name' => 'nullable|string',
        'indicator_type_id' => 'nullable|exists:indicator_types,id',
        'program_id' => ['nullable', $programExistsRule],
        'row_id' => ['nullable', $rowExistsRule],
        'office_id' => 'nullable|array|min:1',
        'office_id.*' => 'required|exists:offices,id',
    ]);

    $targetRowId = (int) ($validated['row_id'] ?? $validated['program_id'] ?? 0);
    $newName = array_key_exists('indicator_name', $validated) ? trim((string) ($validated['indicator_name'] ?? '')) : null;
    $nameChanged = $newName !== null && $newName !== '' && $newName !== $indicator->name;

    $selectedOfficeIds = array_key_exists('office_id', $validated)
        ? collect($validated['office_id'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all()
        : null;

    $currentOfficeIds = $this->hasIndicatorColumn('office_id')
        ? $this->parseJsonIdArray($indicator->office_id ?? [])
        : [];

    $typeProvided = array_key_exists('indicator_type_id', $validated);
    $requestedTypeId = $typeProvided ? ((int) ($validated['indicator_type_id'] ?? 0) ?: null) : null;
    $typeChanged = $this->hasIndicatorColumn('indicator_type_id')
        && $typeProvided
        && ((int) ($indicator->indicator_type_id ?? 0) ?: null) !== $requestedTypeId;

    $officeChanged = $this->hasIndicatorColumn('office_id')
        && $selectedOfficeIds !== null
        && $selectedOfficeIds !== $currentOfficeIds;

    $hasMeaningfulChange = $nameChanged || $typeChanged || $officeChanged;
    $shouldCreateSnapshot = ! $request->boolean('update_in_place') && (
        $nameChanged
        || ($hasMeaningfulChange && $this->isIndicatorAssignedToOtherRows((int) $indicator->id, $targetRowId))
    );

    if ($shouldCreateSnapshot) {
        $newIndicator = new Nra_Indicator();
        $newIndicator->name = $newName !== null && $newName !== '' ? $newName : (string) $indicator->name;

        if ($this->hasIndicatorColumn('user_id')) {
            $newIndicator->user_id = Auth::id();
        }

        if ($this->hasIndicatorColumn('indicator_type_id')) {
            $newIndicator->indicator_type_id = $typeProvided
                ? $requestedTypeId
                : (((int) ($indicator->indicator_type_id ?? 0)) ?: null);
        }

        if ($this->hasIndicatorColumn('office_id')) {
            $newIndicator->office_id = $selectedOfficeIds ?? $currentOfficeIds;
        }

        $newIndicator->save();

        if ($targetRowId > 0) {
            $this->syncProgramIndicatorInPpa($targetRowId, (int) $newIndicator->id, $selectedOfficeIds ?? $currentOfficeIds);
        }

        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'message' => 'Indicator changed. A new indicator ID was created.',
                'success' => true,
                'indicator' => $newIndicator,
                'created_new' => true,
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Indicator changed. A new indicator ID was created.');
    }

    $updateData = [];
    if ($newName !== null && $newName !== '') {
        $updateData['name'] = $newName;
    }
    if ($this->hasIndicatorColumn('indicator_type_id') && $typeProvided) {
        $updateData['indicator_type_id'] = $requestedTypeId;
    }
    if ($this->hasIndicatorColumn('office_id') && $selectedOfficeIds !== null) {
        $updateData['office_id'] = $selectedOfficeIds;
    }

    if (!empty($updateData)) {
        $indicator->update($updateData);
        $indicator->refresh();
    }

    if ($targetRowId > 0) {
        $this->syncProgramIndicatorInPpa($targetRowId, (int) $indicator->id, $selectedOfficeIds ?? $this->parseJsonIdArray($indicator->office_id ?? []));
    }

    if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
        return response()->json([
            'message' => 'Indicator updated successfully.',
            'success' => true,
            'indicator' => $indicator
        ]);
    }

    return redirect()
        ->back()
        ->with('success', 'Indicator updated successfully.');
}

public function destroyIndicator(Request $request, Nra_Indicator $indicator)
{
    $indicator->delete();

    if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
        return response()->json([
            'message' => 'Indicator deleted successfully.',
            'success' => true,
        ]);
    }

    return redirect()
        ->back()
        ->with('success', 'Indicator deleted successfully.');
}

public function destroyPhysicalRow(Request $request)
{
    $validated = $request->validate([
        'row_id' => [
            'required',
            'integer',
            Rule::exists('ppa', 'id')->where(fn ($query) => $query->where('types_id', $this->getNraTypeId())),
        ],
        'indicator_id' => 'nullable|integer|exists:indicators,id',
        'indicator_ids' => 'nullable|array',
        'indicator_ids.*' => 'integer|exists:indicators,id',
        'year' => 'required|integer|min:2000|max:2100',
        'office_ids' => 'nullable|array',
        'office_ids.*' => 'integer|exists:offices,id',
    ]);

    $rowId = (int) $validated['row_id'];
    $indicatorIds = collect($validated['indicator_ids'] ?? [])
        ->push($validated['indicator_id'] ?? null)
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->unique()
        ->values()
        ->all();
    $year = (int) $validated['year'];
    $officeIds = collect($validated['office_ids'] ?? [])
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->unique()
        ->values()
        ->all();

    DB::beginTransaction();
    try {
        $ppaRow = DB::table('ppa')
            ->where('id', $rowId)
            ->where('types_id', $this->getNraTypeId())
            ->first();

        $detailIdsToDelete = [];
        $ppaIdsToDelete = [$rowId];

        if ($ppaRow && (int) ($ppaRow->ppa_details_id ?? 0) > 0) {
            $detailIdsToDelete = $this->collectPpaDetailTreeIds((int) $ppaRow->ppa_details_id);
            $detailIdsToDelete = array_values(array_unique(array_merge(
                $detailIdsToDelete,
                $this->collectEmptyActivityParentDetailIds((int) $ppaRow->ppa_details_id, $detailIdsToDelete)
            )));
            $ppaIdsToDelete = DB::table('ppa')
                ->whereIn('ppa_details_id', $detailIdsToDelete)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
        }

        if (empty($ppaIdsToDelete)) {
            $ppaIdsToDelete = [$rowId];
        }

        $deletedTargets = $this->deleteSectionRowsForPhysicalRows(Nra_Target::class, $ppaIdsToDelete, $indicatorIds, $year, $officeIds);
        $deletedAccomplishments = $this->deleteSectionRowsForPhysicalRows(Nra_Accomplishment::class, $ppaIdsToDelete, $indicatorIds, $year, $officeIds);

        $indicatorNames = empty($indicatorIds)
            ? []
            : Nra_Indicator::query()
                ->whereIn('id', $indicatorIds)
                ->pluck('name')
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->unique()
                ->values()
                ->all();
        $deletedPhysical = 0;

        if (Schema::hasTable('gass_physical')) {
            $physicalQuery = Gass_Physical::query()
                ->whereIn('programs_id', $ppaIdsToDelete)
                ->where('year', $year);

            if (!empty($officeIds)) {
                $physicalQuery->whereIn('office_id', $officeIds);
            }

            if (!empty($indicatorNames)) {
                $physicalQuery->whereIn('performance_indicator', $indicatorNames);
            }

            $deletedPhysical = $physicalQuery->delete();
        }

        DB::table('ppa')->whereIn('id', $ppaIdsToDelete)->delete();

        if (!empty($detailIdsToDelete)) {
            DB::table('ppa_details')->whereIn('id', array_reverse($detailIdsToDelete))->delete();
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Row deleted successfully.',
            'deleted' => [
                'targets' => $deletedTargets,
                'accomplishments' => $deletedAccomplishments,
                'physical' => $deletedPhysical,
                'ppa' => count($ppaIdsToDelete),
            ],
        ]);
    } catch (\Throwable $exception) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to delete row. Please try again.',
        ], 500);
    }
}

private function deleteSectionRowsForPhysicalRows(string $modelClass, array $rowIds, array $indicatorIds, int $year, array $officeIds = []): int
{
    $rowIds = collect($rowIds)
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->unique()
        ->values()
        ->all();

    if (empty($rowIds)) {
        return 0;
    }

    $query = $modelClass::query()->where('years', $year);

    if (!empty($officeIds)) {
        $query->whereIn('office_ids', $officeIds);
    }

    $deletedCount = 0;
    foreach ($query->get() as $record) {
        $meta = $this->parseSectionValues($record->values ?? null);
        $recordRowIds = collect([
            $record->program_id ?? null,
            $meta['row_id'] ?? null,
            $meta['program_id'] ?? null,
        ])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $recordIndicatorId = (int) ($record->indicator_id ?? $meta['indicator_id'] ?? 0);

        if (empty(array_intersect($rowIds, $recordRowIds))) {
            continue;
        }

        if (!empty($indicatorIds) && !in_array($recordIndicatorId, $indicatorIds, true)) {
            continue;
        }

        $record->delete();
        $deletedCount++;
    }

    return $deletedCount;
}

public function storeTargets(Request $request)
{
    $programExistsRule = Rule::exists('ppa', 'id')->where(function ($query) {
        $query->where('types_id', $this->getNraTypeId())
            ->where('record_type_id', $this->getNraRecordTypeIds()['PROGRAM']);
    });

    $rowExistsRule = Rule::exists('ppa', 'id')->where(function ($query) {
        $query->where('types_id', $this->getNraTypeId());
    });

    $validated = $request->validate([
        'entries' => 'required|array',
        'entries.*.program_id' => ['required', $programExistsRule],
        'entries.*.row_id' => ['nullable', $rowExistsRule],
        'entries.*.indicator_id' => 'required|exists:indicators,id',
            'entries.*.year' => 'required|integer|min:2000|max:2100',
        'entries.*.office_id' => 'nullable|exists:offices,id',
        'entries.*.jan' => 'nullable|numeric|min:0',
        'entries.*.feb' => 'nullable|numeric|min:0',
        'entries.*.mar' => 'nullable|numeric|min:0',
        'entries.*.q1' => 'nullable|numeric|min:0',
        'entries.*.apr' => 'nullable|numeric|min:0',
        'entries.*.may' => 'nullable|numeric|min:0',
        'entries.*.jun' => 'nullable|numeric|min:0',
        'entries.*.q2' => 'nullable|numeric|min:0',
        'entries.*.jul' => 'nullable|numeric|min:0',
        'entries.*.aug' => 'nullable|numeric|min:0',
        'entries.*.sep' => 'nullable|numeric|min:0',
        'entries.*.q3' => 'nullable|numeric|min:0',
        'entries.*.oct' => 'nullable|numeric|min:0',
        'entries.*.nov' => 'nullable|numeric|min:0',
        'entries.*.dec' => 'nullable|numeric|min:0',
        'entries.*.q4' => 'nullable|numeric|min:0',
        'entries.*.annual_total' => 'nullable|numeric|min:0',
        'entries.*.car_totals' => 'nullable|array',
        'entries.*.group_totals' => 'nullable|array',
    ]);

    $entries = $validated['entries'] ?? [];

    $years = collect($entries)
        ->map(fn ($entry) => (int) ($entry['years'] ?? $entry['year']))
        ->unique()
        ->values();

    $officeIds = collect($entries)
        ->map(function ($entry) {
            if (!array_key_exists('office_id', $entry) || $entry['office_id'] === null || $entry['office_id'] === '') {
                return null;
            }

            return (int) $entry['office_id'];
        })
        ->values();

    $hasNullOffice = $officeIds->contains(fn ($officeId) => $officeId === null);
    $nonNullOfficeIds = $officeIds
        ->filter(fn ($officeId) => $officeId !== null)
        ->unique()
        ->values();

    // Preload possible existing rows once, then match in-memory by year+office+program+indicator.
    $existingRows = Nra_Target::query()
        ->whereIn('years', $years->all())
        ->where(function ($query) use ($nonNullOfficeIds, $hasNullOffice) {
            if ($nonNullOfficeIds->isNotEmpty()) {
                $query->whereIn('office_ids', $nonNullOfficeIds->all());
            }

            if ($hasNullOffice) {
                if ($nonNullOfficeIds->isNotEmpty()) {
                    $query->orWhereNull('office_ids');
                } else {
                    $query->whereNull('office_ids');
                }
            }
        })
        ->get();

    $existingByKey = [];
    foreach ($existingRows as $candidate) {
        $meta = $this->parseSectionValues($candidate->values ?? null);
        $rowId = (int) ($meta['row_id'] ?? $meta['program_id'] ?? 0);
        $indicatorId = (int) ($meta['indicator_id'] ?? 0);

        if ($rowId <= 0 || $indicatorId <= 0) {
            continue;
        }

        $officeKey = $candidate->office_ids === null ? 'null' : (string) ((int) $candidate->office_ids);
        $lookupKey = ((int) $candidate->years) . '|' . $officeKey . '|' . $rowId . '|' . $indicatorId;
        $existingByKey[$lookupKey] = $candidate;
    }

    $userId = Auth::id();
    $createdCount = 0;
    $updatedCount = 0;

    foreach ($entries as $entry) {
        $entryYear = (int) ($entry['years'] ?? $entry['year']);
        $officeId = (array_key_exists('office_id', $entry) && $entry['office_id'] !== null && $entry['office_id'] !== '')
            ? (int) $entry['office_id']
            : null;
        $programId = (int) $entry['program_id'];
        $rowId = (int) ($entry['row_id'] ?? $programId);
        $indicatorId = (int) $entry['indicator_id'];

        $officeKey = $officeId === null ? 'null' : (string) $officeId;
        $lookupKey = $entryYear . '|' . $officeKey . '|' . $rowId . '|' . $indicatorId;

        $record = $existingByKey[$lookupKey] ?? new Nra_Target();

        $wasExisting = $record->exists;

        $record->office_ids = $officeId;
        $record->years = $entryYear;
        $record->jan = $entry['jan'] ?? 0;
        $record->feb = $entry['feb'] ?? 0;
        $record->mar = $entry['mar'] ?? 0;
        $record->q1 = $entry['q1'] ?? 0;
        $record->apr = $entry['apr'] ?? 0;
        $record->may = $entry['may'] ?? 0;
        $record->jun = $entry['jun'] ?? 0;
        $record->q2 = $entry['q2'] ?? 0;
        $record->jul = $entry['jul'] ?? 0;
        $record->aug = $entry['aug'] ?? 0;
        $record->sep = $entry['sep'] ?? 0;
        $record->q3 = $entry['q3'] ?? 0;
        $record->oct = $entry['oct'] ?? 0;
        $record->nov = $entry['nov'] ?? 0;
        $record->dec = $entry['dec'] ?? 0;
        $record->q4 = $entry['q4'] ?? 0;
        $record->annual_total = $entry['annual_total'] ?? 0;
        $record->values = json_encode([
            'user_id' => $userId,
            'program_id' => $programId,
            'row_id' => $rowId,
            'indicator_id' => $indicatorId,
            'car_totals' => $entry['car_totals'] ?? [],
            'group_totals' => $entry['group_totals'] ?? [],
        ]);

        $record->save();

        if (!$wasExisting) {
            $existingByKey[$lookupKey] = $record;
        }

        if ($wasExisting) {
            $updatedCount++;
        } else {
            $createdCount++;
        }
    }

    return response()->json([
        'success' => true,
        'message' => "$createdCount created, $updatedCount updated successfully.",
        'created_count' => $createdCount,
        'updated_count' => $updatedCount,
    ]);
}

public function storeAccomplishments(Request $request)
{
    $programExistsRule = Rule::exists('ppa', 'id')->where(function ($query) {
        $query->where('types_id', $this->getNraTypeId())
            ->where('record_type_id', $this->getNraRecordTypeIds()['PROGRAM']);
    });

    $rowExistsRule = Rule::exists('ppa', 'id')->where(function ($query) {
        $query->where('types_id', $this->getNraTypeId());
    });

    $validated = $request->validate([
        'entries' => 'required|array',
        'entries.*.program_id' => ['required', $programExistsRule],
        'entries.*.row_id' => ['nullable', $rowExistsRule],
        'entries.*.indicator_id' => 'required|exists:indicators,id',
            'entries.*.year' => 'required|integer|min:2000|max:2100',
        'entries.*.office_id' => 'nullable|exists:offices,id',
        'entries.*.jan' => 'nullable|numeric|min:0',
        'entries.*.feb' => 'nullable|numeric|min:0',
        'entries.*.mar' => 'nullable|numeric|min:0',
        'entries.*.q1' => 'nullable|numeric|min:0',
        'entries.*.apr' => 'nullable|numeric|min:0',
        'entries.*.may' => 'nullable|numeric|min:0',
        'entries.*.jun' => 'nullable|numeric|min:0',
        'entries.*.q2' => 'nullable|numeric|min:0',
        'entries.*.jul' => 'nullable|numeric|min:0',
        'entries.*.aug' => 'nullable|numeric|min:0',
        'entries.*.sep' => 'nullable|numeric|min:0',
        'entries.*.q3' => 'nullable|numeric|min:0',
        'entries.*.oct' => 'nullable|numeric|min:0',
        'entries.*.nov' => 'nullable|numeric|min:0',
        'entries.*.dec' => 'nullable|numeric|min:0',
        'entries.*.q4' => 'nullable|numeric|min:0',
        'entries.*.annual_total' => 'nullable|numeric|min:0',
        'entries.*.car_totals' => 'nullable|array',
        'entries.*.group_totals' => 'nullable|array',
        'entries.*.remarks' => 'nullable|string',
    ]);

    $entries = $validated['entries'] ?? [];

    $years = collect($entries)
        ->map(fn ($entry) => (int) ($entry['years'] ?? $entry['year']))
        ->unique()
        ->values();

    $officeIds = collect($entries)
        ->map(function ($entry) {
            if (!array_key_exists('office_id', $entry) || $entry['office_id'] === null || $entry['office_id'] === '') {
                return null;
            }

            return (int) $entry['office_id'];
        })
        ->values();

    $hasNullOffice = $officeIds->contains(fn ($officeId) => $officeId === null);
    $nonNullOfficeIds = $officeIds
        ->filter(fn ($officeId) => $officeId !== null)
        ->unique()
        ->values();

    // Preload possible existing rows once, then match in-memory by year+office+program+indicator.
    $existingRows = Nra_Accomplishment::query()
        ->whereIn('years', $years->all())
        ->where(function ($query) use ($nonNullOfficeIds, $hasNullOffice) {
            if ($nonNullOfficeIds->isNotEmpty()) {
                $query->whereIn('office_ids', $nonNullOfficeIds->all());
            }

            if ($hasNullOffice) {
                if ($nonNullOfficeIds->isNotEmpty()) {
                    $query->orWhereNull('office_ids');
                } else {
                    $query->whereNull('office_ids');
                }
            }
        })
        ->get();

    $existingByKey = [];
    foreach ($existingRows as $candidate) {
        $meta = $this->parseSectionValues($candidate->values ?? null);
        $rowId = (int) ($meta['row_id'] ?? $meta['program_id'] ?? 0);
        $indicatorId = (int) ($meta['indicator_id'] ?? 0);

        if ($rowId <= 0 || $indicatorId <= 0) {
            continue;
        }

        $officeKey = $candidate->office_ids === null ? 'null' : (string) ((int) $candidate->office_ids);
        $lookupKey = ((int) $candidate->years) . '|' . $officeKey . '|' . $rowId . '|' . $indicatorId;
        $existingByKey[$lookupKey] = $candidate;
    }

    $userId = Auth::id();
    $createdCount = 0;
    $updatedCount = 0;

    foreach ($entries as $entry) {
        $entryYear = (int) ($entry['years'] ?? $entry['year']);
        $officeId = (array_key_exists('office_id', $entry) && $entry['office_id'] !== null && $entry['office_id'] !== '')
            ? (int) $entry['office_id']
            : null;
        $programId = (int) $entry['program_id'];
        $rowId = (int) ($entry['row_id'] ?? $programId);
        $indicatorId = (int) $entry['indicator_id'];

        $officeKey = $officeId === null ? 'null' : (string) $officeId;
        $lookupKey = $entryYear . '|' . $officeKey . '|' . $rowId . '|' . $indicatorId;

        $record = $existingByKey[$lookupKey] ?? new Nra_Accomplishment();

        $wasExisting = $record->exists;

        $record->office_ids = $officeId;
        $record->years = $entryYear;
        $record->jan = array_key_exists('jan', $entry) ? ($entry['jan'] ?? 0) : ($record->jan ?? 0);
        $record->feb = array_key_exists('feb', $entry) ? ($entry['feb'] ?? 0) : ($record->feb ?? 0);
        $record->mar = array_key_exists('mar', $entry) ? ($entry['mar'] ?? 0) : ($record->mar ?? 0);
        $record->q1 = array_key_exists('q1', $entry) ? ($entry['q1'] ?? 0) : ($record->q1 ?? 0);
        $record->apr = array_key_exists('apr', $entry) ? ($entry['apr'] ?? 0) : ($record->apr ?? 0);
        $record->may = array_key_exists('may', $entry) ? ($entry['may'] ?? 0) : ($record->may ?? 0);
        $record->jun = array_key_exists('jun', $entry) ? ($entry['jun'] ?? 0) : ($record->jun ?? 0);
        $record->q2 = array_key_exists('q2', $entry) ? ($entry['q2'] ?? 0) : ($record->q2 ?? 0);
        $record->jul = array_key_exists('jul', $entry) ? ($entry['jul'] ?? 0) : ($record->jul ?? 0);
        $record->aug = array_key_exists('aug', $entry) ? ($entry['aug'] ?? 0) : ($record->aug ?? 0);
        $record->sep = array_key_exists('sep', $entry) ? ($entry['sep'] ?? 0) : ($record->sep ?? 0);
        $record->q3 = array_key_exists('q3', $entry) ? ($entry['q3'] ?? 0) : ($record->q3 ?? 0);
        $record->oct = array_key_exists('oct', $entry) ? ($entry['oct'] ?? 0) : ($record->oct ?? 0);
        $record->nov = array_key_exists('nov', $entry) ? ($entry['nov'] ?? 0) : ($record->nov ?? 0);
        $record->dec = array_key_exists('dec', $entry) ? ($entry['dec'] ?? 0) : ($record->dec ?? 0);
        $record->q4 = array_key_exists('q4', $entry) ? ($entry['q4'] ?? 0) : ($record->q4 ?? 0);
        $record->annual_total = array_key_exists('annual_total', $entry) ? ($entry['annual_total'] ?? 0) : ($record->annual_total ?? 0);
        $rawRemarks = array_key_exists('remarks', $entry)
            ? ($entry['remarks'] ?? null)
            : $this->decodeRemarksFromStorage($record->remarks ?? null);
        $record->remarks = $this->encodeRemarksForStorage($rawRemarks);
        $record->values = json_encode([
            'user_id' => $userId,
            'program_id' => $programId,
            'row_id' => $rowId,
            'indicator_id' => $indicatorId,
            'car_totals' => $entry['car_totals'] ?? [],
            'group_totals' => $entry['group_totals'] ?? [],
        ]);

        $record->save();

        if (!$wasExisting) {
            $existingByKey[$lookupKey] = $record;
        }

        if ($wasExisting) {
            $updatedCount++;
        } else {
            $createdCount++;
        }
    }

    return response()->json([
        'success' => true,
        'message' => "$createdCount created, $updatedCount updated successfully.",
        'created_count' => $createdCount,
        'updated_count' => $updatedCount,
    ]);
}

private function formatSectionRecordForJs($row, array $meta = []): array
{
    return [
        'jan' => (float) ($row->jan ?? 0),
        'feb' => (float) ($row->feb ?? 0),
        'mar' => (float) ($row->mar ?? 0),
        'q1' => (float) ($row->q1 ?? 0),
        'apr' => (float) ($row->apr ?? 0),
        'may' => (float) ($row->may ?? 0),
        'jun' => (float) ($row->jun ?? 0),
        'q2' => (float) ($row->q2 ?? 0),
        'jul' => (float) ($row->jul ?? 0),
        'aug' => (float) ($row->aug ?? 0),
        'sep' => (float) ($row->sep ?? 0),
        'q3' => (float) ($row->q3 ?? 0),
        'oct' => (float) ($row->oct ?? 0),
        'nov' => (float) ($row->nov ?? 0),
        'dec' => (float) ($row->dec ?? 0),
        'q4' => (float) ($row->q4 ?? 0),
        'annual_total' => (float) ($row->annual_total ?? 0),
        'imported_from' => (string) ($meta['imported_from'] ?? ''),
        'car_totals' => is_array($meta['car_totals'] ?? null) ? $meta['car_totals'] : [],
        'group_totals' => is_array($meta['group_totals'] ?? null) ? $meta['group_totals'] : [],
        'remarks' => $this->decodeRemarksFromStorage($row->remarks ?? null),
    ];
}

private function buildPendingTotalsByRow(array $targets, array $accomplishments): array
{
    $periodKeys = [
        'jan', 'feb', 'mar', 'q1',
        'apr', 'may', 'jun', 'q2',
        'jul', 'aug', 'sep', 'q3',
        'oct', 'nov', 'dec', 'q4', 'annual_total',
    ];

    $pending = [];

    foreach ($targets as $rowId => $indicators) {
        foreach ($indicators as $indicatorId => $offices) {
            foreach ($periodKeys as $periodKey) {
                $targetTotal = $this->sectionPeriodTotal($offices, $periodKey);
                $accompTotal = $this->sectionPeriodTotal($accomplishments[$rowId][$indicatorId] ?? [], $periodKey);

                $pending[(string) $rowId][(string) $indicatorId][$periodKey] = max($targetTotal - $accompTotal, 0);
            }
        }
    }

    return $pending;
}

private function sectionPeriodTotal(array $offices, string $periodKey): float
{
    foreach ($offices as $entry) {
        $carValue = $entry['car_totals'][$periodKey] ?? null;
        if (is_numeric($carValue)) {
            return (float) $carValue;
        }
    }

    return (float) collect($offices)->sum(function ($entry) use ($periodKey) {
        $value = $entry[$periodKey] ?? 0;
        return is_numeric($value) ? (float) $value : 0;
    });
}

private function encodeRemarksForStorage($remarks): ?string
{
    if ($remarks === null) {
        return null;
    }

    $normalized = trim((string) $remarks);
    if ($normalized === '') {
        return null;
    }

    return json_encode($normalized);
}

private function decodeRemarksFromStorage($raw): string
{
    if ($raw === null) {
        return '';
    }

    if (is_string($raw)) {
        $decoded = json_decode($raw, true);

        if (is_string($decoded)) {
            return $decoded;
        }

        if (is_array($decoded)) {
            if (isset($decoded['text']) && is_string($decoded['text'])) {
                return $decoded['text'];
            }

            return '';
        }

        return $raw;
    }

    return (string) $raw;
}

protected function parseSectionValues($raw): array
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

private function resolveIndicatorTargetRowId(int $rowId, string $indicatorName = ''): int
{
    $rowId = (int) $rowId;
    if ($rowId <= 0) {
        return $rowId;
    }

    $existingRow = DB::table('ppa')->where('id', $rowId)->first();
    if (!$existingRow) {
        return $rowId;
    }

    $currentIndicatorId = (int) ($existingRow->indicator_id ?? 0);
    if ($currentIndicatorId <= 0) {
        return $rowId;
    }

    $normalizedIndicatorName = mb_strtolower(trim($indicatorName));
    if ($normalizedIndicatorName !== '') {
        $currentIndicatorName = Nra_Indicator::query()
            ->whereKey($currentIndicatorId)
            ->value('name');

        if ($currentIndicatorName !== null && mb_strtolower(trim((string) $currentIndicatorName)) === $normalizedIndicatorName) {
            return $rowId;
        }
    }

    return (int) DB::table('ppa')->insertGetId([
        'name' => $existingRow->name,
        'types_id' => $existingRow->types_id,
        'record_type_id' => $existingRow->record_type_id,
        'ppa_details_id' => $existingRow->ppa_details_id,
        'indicator_id' => null,
        'year' => $existingRow->year,
        'office_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

protected function syncProgramIndicatorInPpa(int $programId, int $indicatorId, array $officeIds = []): void
{
    if ($programId <= 0 || $indicatorId <= 0) {
        return;
    }

    $normalizedOfficeIds = collect($officeIds)
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->unique()
        ->values()
        ->all();

    DB::table('ppa')
        ->where('id', $programId)
        ->update([
            'indicator_id' => $indicatorId,
            'office_id' => !empty($normalizedOfficeIds) ? json_encode($normalizedOfficeIds) : null,
            'updated_at' => now(),
        ]);
}

private function isIndicatorAssignedToOtherRows(int $indicatorId, ?int $exceptPpaId = null): bool
{
    if ($indicatorId <= 0) {
        return false;
    }

    return DB::table('ppa')
        ->where('indicator_id', $indicatorId)
        ->when($exceptPpaId !== null && $exceptPpaId > 0, function ($query) use ($exceptPpaId) {
            $query->where('id', '<>', $exceptPpaId);
        })
        ->exists();
}

protected function hasIndicatorColumn(string $column): bool
{
    static $columnCache = [];

    if (!array_key_exists($column, $columnCache)) {
        $columnCache[$column] = Schema::hasColumn('indicators', $column);
    }

    return $columnCache[$column];
}

private function getIndicatorsGroupedByProgram(array $programIds, ?int $year = null): Collection
{
    $programIds = collect($programIds)
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->values();

    $grouped = $programIds->mapWithKeys(fn ($id) => [$id => collect()]);
    if ($programIds->isEmpty()) {
        return $grouped;
    }

    $programIdLookup = array_fill_keys($programIds->all(), true);

    $indicatorAssignments = collect();

    DB::table('ppa')
        ->whereIn('id', $programIds->all())
        ->whereNotNull('indicator_id')
        ->get(['id', 'indicator_id', 'office_id'])
        ->each(function ($row) use (&$indicatorAssignments) {
            $programId = (int) ($row->id ?? 0);
            $indicatorId = (int) ($row->indicator_id ?? 0);
            if ($programId <= 0 || $indicatorId <= 0) {
                return;
            }

            $indicatorAssignments->push([
                'program_id' => $programId,
                'indicator_id' => $indicatorId,
                'office_ids' => $this->parseJsonIdArray($row->office_id ?? null),
                'sort_order' => PHP_INT_MAX,
            ]);
        });

    Nra_Target::query()
        ->when($year !== null, fn ($query) => $query->where('years', $year))
        ->orderBy('id')
        ->get(['id', 'office_ids', 'values'])
        ->each(function ($target) use (&$indicatorAssignments, $programIdLookup) {
            $meta = $this->parseSectionValues($target->values ?? null);
            $programId = (int) ($meta['row_id'] ?? $meta['program_id'] ?? 0);
            $indicatorId = (int) ($meta['indicator_id'] ?? 0);

            if ($programId <= 0 || $indicatorId <= 0 || !isset($programIdLookup[$programId])) {
                return;
            }

            $officeId = (int) ($target->office_ids ?? 0);
            $indicatorAssignments->push([
                'program_id' => $programId,
                'indicator_id' => $indicatorId,
                'office_ids' => $officeId > 0 ? [$officeId] : [],
                'sort_order' => (int) ($target->id ?? PHP_INT_MAX),
            ]);
        });

    Nra_Accomplishment::query()
        ->when($year !== null, fn ($query) => $query->where('years', $year))
        ->orderBy('id')
        ->get(['id', 'office_ids', 'values'])
        ->each(function ($accomplishment) use (&$indicatorAssignments, $programIdLookup) {
            $meta = $this->parseSectionValues($accomplishment->values ?? null);
            $programId = (int) ($meta['row_id'] ?? $meta['program_id'] ?? 0);
            $indicatorId = (int) ($meta['indicator_id'] ?? 0);

            if ($programId <= 0 || $indicatorId <= 0 || !isset($programIdLookup[$programId])) {
                return;
            }

            $officeId = (int) ($accomplishment->office_ids ?? 0);
            $indicatorAssignments->push([
                'program_id' => $programId,
                'indicator_id' => $indicatorId,
                'office_ids' => $officeId > 0 ? [$officeId] : [],
                'sort_order' => (int) ($accomplishment->id ?? PHP_INT_MAX),
            ]);
        });

    $indicatorAssignments = $indicatorAssignments
        ->groupBy(fn ($row) => (int) $row['program_id'] . '|' . (int) $row['indicator_id'])
        ->map(function ($rows) {
            $first = $rows->first();

            return [
                'program_id' => (int) ($first['program_id'] ?? 0),
                'indicator_id' => (int) ($first['indicator_id'] ?? 0),
                'office_ids' => $rows
                    ->flatMap(fn ($row) => $row['office_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all(),
                'sort_order' => $rows
                    ->pluck('sort_order')
                    ->map(fn ($value) => (int) $value)
                    ->min() ?? PHP_INT_MAX,
            ];
        })
        ->sortBy('sort_order')
        ->values();

    $allIndicatorIds = $indicatorAssignments
        ->pluck('indicator_id')
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->unique()
        ->values();

    if ($allIndicatorIds->isEmpty()) {
        return $grouped;
    }

    $indicatorsById = Nra_Indicator::query()
        ->whereIn('id', $allIndicatorIds->all())
        ->get()
        ->keyBy(fn ($row) => (int) $row->id);

    foreach ($indicatorAssignments as $assignment) {
        $programId = (int) ($assignment['program_id'] ?? 0);
        $indicatorId = (int) ($assignment['indicator_id'] ?? 0);
        if ($programId <= 0 || $indicatorId <= 0) {
            continue;
        }

        $indicator = $indicatorsById->get($indicatorId);
        if (!$indicator) {
            continue;
        }

        // Clone the indicator to avoid mutating the original object in the collection
        $indicatorClone = clone $indicator;
        $indicatorClone->office_id = $assignment['office_ids'] ?? [];

        // Append indicator to the program's collection
        if (!$grouped->has($programId)) {
            $grouped->put($programId, collect([$indicatorClone]));
        } else {
            $grouped[$programId]->push($indicatorClone);
        }
    }

    return $grouped;
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

    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
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

private function collectProgramIndicatorIds(array &$indicatorIdsByProgram, Collection $rows): void
{
    $validProgramIds = array_fill_keys(array_map('intval', array_keys($indicatorIdsByProgram)), true);

    foreach ($rows as $row) {
        $meta = $this->parseSectionValues($row->values ?? null);
        $programId = (int) ($meta['program_id'] ?? 0);
        $indicatorId = (int) ($meta['indicator_id'] ?? 0);

        if ($programId <= 0 || $indicatorId <= 0) {
            continue;
        }

        if (!isset($validProgramIds[$programId])) {
            continue;
        }

        $indicatorIdsByProgram[$programId][] = $indicatorId;
    }
}

private function deleteProgramSectionRows(int $programId, Collection $rows): void
{
    $idsToDelete = $rows
        ->filter(function ($row) use ($programId) {
            $meta = $this->parseSectionValues($row->values ?? null);
            return (int) ($meta['program_id'] ?? 0) === $programId;
        })
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->values()
        ->all();

    if (empty($idsToDelete)) {
        return;
    }

    $modelClass = get_class($rows->first());
    $modelClass::query()->whereIn('id', $idsToDelete)->delete();
}

private function getNraPrograms(?int $programId = null, string $search = '', ?int $year = null): Collection
{
    $recordTypeIds = $this->getNraRecordTypeIds();
    $typeId = $this->getNraTypeId();

    $query = DB::table('ppa as program_ppa')
        ->join('ppa_details as program_detail', 'program_detail.id', '=', 'program_ppa.ppa_details_id')
        ->leftJoin('ppa_details as project_detail', function ($join) {
            $join->on('project_detail.parent_id', '=', 'program_detail.id')
                ->where('project_detail.column_order', '=', 2);
        })
        ->leftJoin('ppa as project_ppa', function ($join) use ($recordTypeIds) {
            $join->on('project_ppa.ppa_details_id', '=', 'project_detail.id')
                ->where('project_ppa.record_type_id', '=', $recordTypeIds['PROJECT']);
        })
        ->leftJoin('ppa_details as main_activity_detail', function ($join) {
            $join->on('main_activity_detail.parent_id', '=', 'project_detail.id')
                ->where('main_activity_detail.column_order', '=', 3);
        })
        ->leftJoin('ppa as main_activity_ppa', function ($join) use ($recordTypeIds) {
            $join->on('main_activity_ppa.ppa_details_id', '=', 'main_activity_detail.id')
                ->where('main_activity_ppa.record_type_id', '=', $recordTypeIds['MAIN ACTIVITY']);
        })
        ->leftJoin('ppa_details as sub_activity_detail', function ($join) {
            $join->on('sub_activity_detail.parent_id', '=', 'main_activity_detail.id')
                ->where('sub_activity_detail.column_order', '=', 4);
        })
        ->leftJoin('ppa as sub_activity_ppa', function ($join) use ($recordTypeIds) {
            $join->on('sub_activity_ppa.ppa_details_id', '=', 'sub_activity_detail.id')
                ->where('sub_activity_ppa.record_type_id', '=', $recordTypeIds['SUB-ACTIVITY']);
        })
        ->leftJoin('ppa_details as sub_sub_activity_detail', function ($join) {
            $join->on('sub_sub_activity_detail.parent_id', '=', 'sub_activity_detail.id')
                ->where('sub_sub_activity_detail.column_order', '=', 5);
        })
        ->leftJoin('ppa as sub_sub_activity_ppa', function ($join) use ($recordTypeIds) {
            $join->on('sub_sub_activity_ppa.ppa_details_id', '=', 'sub_sub_activity_detail.id')
                ->where('sub_sub_activity_ppa.record_type_id', '=', $recordTypeIds['SUB-SUB-ACTIVITY']);
        })
        ->leftJoin('ppa_details as sub_sub_sub_activity_detail', function ($join) {
            $join->on('sub_sub_sub_activity_detail.parent_id', '=', 'sub_sub_activity_detail.id')
                ->where('sub_sub_sub_activity_detail.column_order', '=', 6);
        })
        ->leftJoin('ppa as sub_sub_sub_activity_ppa', function ($join) use ($recordTypeIds) {
            $join->on('sub_sub_sub_activity_ppa.ppa_details_id', '=', 'sub_sub_sub_activity_detail.id')
                ->where('sub_sub_sub_activity_ppa.record_type_id', '=', $recordTypeIds['SUB-SUB-SUB-ACTIVITY']);
        })
        ->leftJoin('ppa_details as level_7_detail', function ($join) {
            $join->on('level_7_detail.parent_id', '=', 'sub_sub_sub_activity_detail.id')
                ->where('level_7_detail.column_order', '=', 7);
        })
        ->leftJoin('ppa as level_7_ppa', function ($join) use ($recordTypeIds) {
            $join->on('level_7_ppa.ppa_details_id', '=', 'level_7_detail.id')
                ->where('level_7_ppa.record_type_id', '=', $recordTypeIds['LEVEL-7']);
        })
        ->leftJoin('ppa_details as level_8_detail', function ($join) {
            $join->on('level_8_detail.parent_id', '=', 'level_7_detail.id')
                ->where('level_8_detail.column_order', '=', 8);
        })
        ->leftJoin('ppa as level_8_ppa', function ($join) use ($recordTypeIds) {
            $join->on('level_8_ppa.ppa_details_id', '=', 'level_8_detail.id')
                ->where('level_8_ppa.record_type_id', '=', $recordTypeIds['LEVEL-8']);
        })
        ->leftJoin('ppa_details as level_9_detail', function ($join) {
            $join->on('level_9_detail.parent_id', '=', 'level_8_detail.id')
                ->where('level_9_detail.column_order', '=', 9);
        })
        ->leftJoin('ppa as level_9_ppa', function ($join) use ($recordTypeIds) {
            $join->on('level_9_ppa.ppa_details_id', '=', 'level_9_detail.id')
                ->where('level_9_ppa.record_type_id', '=', $recordTypeIds['LEVEL-9']);
        })
        ->where('program_ppa.types_id', $typeId)
        ->where('program_ppa.record_type_id', $recordTypeIds['PROGRAM'])
        ->when($year !== null, function ($query) use ($year) {
            $query->where('program_ppa.year', $year);
        })
        ->select([
            'program_ppa.id',
            'program_ppa.id as program_row_id',
            'project_ppa.id as project_row_id',
            'main_activity_ppa.id as main_activity_row_id',
            'sub_activity_ppa.id as sub_activity_row_id',
            'sub_sub_activity_ppa.id as sub_sub_activity_row_id',
            'sub_sub_sub_activity_ppa.id as sub_sub_sub_activity_row_id',
            'level_7_ppa.id as level_7_row_id',
            'level_8_ppa.id as level_8_row_id',
            'level_9_ppa.id as level_9_row_id',
            DB::raw('COALESCE('.implode(', ', array_map(fn ($column) => DB::connection()->getQueryGrammar()->wrap($column), ['level_9_ppa.id', 'level_8_ppa.id', 'level_7_ppa.id', 'sub_sub_sub_activity_ppa.id', 'sub_sub_activity_ppa.id', 'sub_activity_ppa.id', 'main_activity_ppa.id', 'project_ppa.id', 'program_ppa.id'])).') as row_id'),
            'program_ppa.ppa_details_id',
            'program_ppa.created_at',
            'program_ppa.updated_at',
            'program_ppa.name as title',
            'program_detail.subprogram',
            'program_detail.subsubprogram',
            'program_detail.subsubsubprogram',
            'program_detail.subsubsubsubprogram',
            'program_detail.subsubsubsubsubprogram',
            'program_detail.source_order as title_source_order',
            'project_ppa.name as program',
            'project_detail.source_order as program_source_order',
            'main_activity_ppa.name as project',
            'main_activity_detail.source_order as project_source_order',
            'sub_activity_ppa.name as activities',
            'sub_activity_detail.source_order as activities_source_order',
            'sub_sub_activity_ppa.name as subactivities',
            'sub_sub_activity_detail.source_order as subactivities_source_order',
            'sub_sub_sub_activity_ppa.name as subsubactivities',
            'sub_sub_sub_activity_detail.source_order as subsubactivities_source_order',
            'level_7_ppa.name as level_6',
            'level_7_detail.source_order as level_6_source_order',
            'level_8_ppa.name as level_7',
            'level_8_detail.source_order as level_7_source_order',
            'level_9_ppa.name as level_8',
            'level_9_detail.source_order as level_8_source_order',
        ])
        ->orderBy('program_ppa.created_at')
        ->orderBy('program_ppa.id');

    if ($programId !== null) {
        $query->where('program_ppa.id', $programId);
    }

    $normalizeHierarchyValue = static fn ($value) => mb_strtolower(trim((string) $value));

    $programs = $query->get()
        ->map(function ($row) {
            $row->id = (int) $row->id;
            $row->program_row_id = (int) ($row->program_row_id ?? $row->id);
            $row->project_row_id = (int) ($row->project_row_id ?? 0);
            $row->main_activity_row_id = (int) ($row->main_activity_row_id ?? 0);
            $row->sub_activity_row_id = (int) ($row->sub_activity_row_id ?? 0);
            $row->sub_sub_activity_row_id = (int) ($row->sub_sub_activity_row_id ?? 0);
            $row->sub_sub_sub_activity_row_id = (int) ($row->sub_sub_sub_activity_row_id ?? 0);
            $row->level_7_row_id = (int) ($row->level_7_row_id ?? 0);
            $row->level_8_row_id = (int) ($row->level_8_row_id ?? 0);
            $row->level_9_row_id = (int) ($row->level_9_row_id ?? 0);
            $row->row_id = (int) ($row->row_id ?? $row->id);
            $row->ppa_details_id = (int) $row->ppa_details_id;
            return $row;
        })
        ->groupBy(function ($row) use ($normalizeHierarchyValue) {
            return $normalizeHierarchyValue($row->title ?? '') . '|'
                . $normalizeHierarchyValue($row->program ?? '') . '|' . $normalizeHierarchyValue($row->subprogram ?? '') . '|' . $normalizeHierarchyValue($row->subsubprogram ?? '') . '|' . $normalizeHierarchyValue($row->subsubsubprogram ?? '') . '|' . $normalizeHierarchyValue($row->subsubsubsubprogram ?? '') . '|' . $normalizeHierarchyValue($row->subsubsubsubsubprogram ?? '') . '|'
                . $normalizeHierarchyValue($row->project ?? '') . '|'
                . $normalizeHierarchyValue($row->activities ?? '') . '|'
                . $normalizeHierarchyValue($row->subactivities ?? '') . '|'
                . $normalizeHierarchyValue($row->subsubactivities ?? '') . '|'
                . $normalizeHierarchyValue($row->level_6 ?? '') . '|'
                . $normalizeHierarchyValue($row->level_7 ?? '') . '|'
                . $normalizeHierarchyValue($row->level_8 ?? '');
        })
        ->flatMap(function ($group) {
            $usedRowIds = [];

            return $group->map(function ($row) use (&$usedRowIds) {
                $candidateRowIds = [
                    (int) ($row->row_id ?? 0),
                    (int) ($row->level_9_row_id ?? 0),
                    (int) ($row->level_8_row_id ?? 0),
                    (int) ($row->level_7_row_id ?? 0),
                    (int) ($row->sub_sub_sub_activity_row_id ?? 0),
                    (int) ($row->sub_sub_activity_row_id ?? 0),
                    (int) ($row->sub_activity_row_id ?? 0),
                    (int) ($row->main_activity_row_id ?? 0),
                    (int) ($row->project_row_id ?? 0),
                    (int) ($row->program_row_id ?? 0),
                    (int) ($row->id ?? 0),
                ];

                foreach ($candidateRowIds as $candidateRowId) {
                    if ($candidateRowId > 0 && !in_array($candidateRowId, $usedRowIds, true)) {
                        $row->row_id = $candidateRowId;
                        $usedRowIds[] = $candidateRowId;
                        break;
                    }
                }

                return $row;
            });
        })
        ->unique(function ($row) use ($normalizeHierarchyValue) {
            return $normalizeHierarchyValue($row->title ?? '') . '|'
                . $normalizeHierarchyValue($row->program ?? '') . '|' . $normalizeHierarchyValue($row->subprogram ?? '') . '|' . $normalizeHierarchyValue($row->subsubprogram ?? '') . '|' . $normalizeHierarchyValue($row->subsubsubprogram ?? '') . '|' . $normalizeHierarchyValue($row->subsubsubsubprogram ?? '') . '|' . $normalizeHierarchyValue($row->subsubsubsubsubprogram ?? '') . '|'
                . $normalizeHierarchyValue($row->project ?? '') . '|'
                . $normalizeHierarchyValue($row->activities ?? '') . '|'
                . $normalizeHierarchyValue($row->subactivities ?? '') . '|'
                . $normalizeHierarchyValue($row->subsubactivities ?? '') . '|'
                . $normalizeHierarchyValue($row->level_6 ?? '') . '|'
                . $normalizeHierarchyValue($row->level_7 ?? '') . '|'
                . $normalizeHierarchyValue($row->level_8 ?? '') . '|'
                . (int) ($row->row_id ?? 0);
        })
        ->values();

    if ($search === '') {
        return $programs->values();
    }

    $needle = mb_strtolower($search);
    $programIds = $programs->pluck('id')->all();
    $matchingOfficeIds = Office::query()
        ->where('name', 'like', "%{$search}%")
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->all();

    $indicators = $this->getIndicatorsGroupedByProgram($programIds);

    return $programs->filter(function ($program) use ($needle, $matchingOfficeIds, $indicators) {
        $fields = [
            $program->title,
            $program->program,
            $program->project,
            $program->activities,
            $program->subactivities,
            $program->subsubactivities ?? null,
            $program->level_6 ?? null,
            $program->level_7 ?? null,
            $program->level_8 ?? null,
        ];

        foreach ($fields as $field) {
            if ($field !== null && str_contains(mb_strtolower((string) $field), $needle)) {
                return true;
            }
        }

        foreach (($indicators[$program->id] ?? collect()) as $indicator) {
            if (str_contains(mb_strtolower((string) $indicator->name), $needle)) {
                return true;
            }

            $indicatorOfficeIds = collect($indicator->office_id ?? [])->map(fn ($id) => (int) $id)->all();
            if (!empty(array_intersect($matchingOfficeIds, $indicatorOfficeIds))) {
                return true;
            }
        }

        return false;
    })->values();
}

private function findNraProgram(int $programId): ?object
{
    return $this->getNraPrograms($programId)->first();
}

private function collectPpaDetailTreeIds(int $rootDetailId): array
{
    $detailIds = [];
    $queue = [$rootDetailId];

    while (!empty($queue)) {
        $currentId = array_shift($queue);
        if (in_array($currentId, $detailIds, true)) {
            continue;
        }

        $detailIds[] = $currentId;

        $childIds = DB::table('ppa_details')
            ->where('parent_id', $currentId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($childIds as $childId) {
            $queue[] = $childId;
        }
    }

    return $detailIds;
}

private function collectEmptyActivityParentDetailIds(int $rootDetailId, array $detailIdsToDelete): array
{
    $extraDetailIds = [];
    $scheduledDetailIds = collect($detailIdsToDelete)
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->unique()
        ->values()
        ->all();

    $current = DB::table('ppa_details')
        ->where('id', $rootDetailId)
        ->first();

    while ($current && (int) ($current->parent_id ?? 0) > 0) {
        $parent = DB::table('ppa_details')
            ->where('id', (int) $current->parent_id)
            ->first();

        if (!$parent || (int) ($parent->column_order ?? 0) < 4) {
            break;
        }

        $remainingChildCount = DB::table('ppa_details')
            ->where('parent_id', (int) $parent->id)
            ->whereNotIn('id', $scheduledDetailIds)
            ->count();

        if ($remainingChildCount > 0) {
            break;
        }

        $extraDetailIds[] = (int) $parent->id;
        $scheduledDetailIds[] = (int) $parent->id;
        $current = $parent;
    }

    return $extraDetailIds;
}

private function getNraTypeId(): int
{
    $typeId = DB::table('types')
        ->where('code', 'NRA')
        ->value('id');

    if (!$typeId) {
        throw new \RuntimeException('NRA type is not configured.');
    }

    return (int) $typeId;
}

private function getNraRecordTypeIds(): array
{
    $recordTypeIds = DB::table('record_types')
        ->whereIn('name', [
            'PROGRAM',
            'PROJECT',
            'MAIN ACTIVITY',
            'SUB-ACTIVITY',
            'SUB-SUB-ACTIVITY',
            'SUB-SUB-SUB-ACTIVITY',
            'LEVEL-7',
            'LEVEL-8',
            'LEVEL-9',
        ])
        ->pluck('id', 'name')
        ->map(fn ($id) => (int) $id)
        ->all();

    $requiredNames = [
        'PROGRAM',
        'PROJECT',
        'MAIN ACTIVITY',
        'SUB-ACTIVITY',
        'SUB-SUB-ACTIVITY',
        'SUB-SUB-SUB-ACTIVITY',
        'LEVEL-7',
        'LEVEL-8',
        'LEVEL-9',
    ];

    foreach ($requiredNames as $name) {
        if (!isset($recordTypeIds[$name])) {
            throw new \RuntimeException("Record type {$name} is not configured.");
        }
    }

    return $recordTypeIds;
}
}
