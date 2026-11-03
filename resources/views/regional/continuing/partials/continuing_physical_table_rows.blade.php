@php
    $indicatorTypeNameById = collect($indicatorTypeOptions ?? [])
        ->mapWithKeys(fn($type) => [(int) ($type->id ?? 0) => (string) ($type->name ?? '')])
        ->all();
@endphp
@php
    $normalizeGroupValue = function ($value) {
        $normalized = strtolower(trim((string) ($value ?? '')));
        return preg_replace('/\s+/', ' ', $normalized);
    };

    $isEmptyOrNaHierarchyValue = function ($value) use ($normalizeGroupValue) {
        $normalized = $normalizeGroupValue($value);
        return $normalized === '' || in_array($normalized, ['n/a', 'na', 'not applicable'], true);
    };

    $hierarchySortValue = function ($value, bool $singleIAsRoman = false) use ($normalizeGroupValue) {
        $raw = trim((string) ($value ?? ''));
        $normalized = $normalizeGroupValue($value);

        if ($normalized === '') {
            return '4|999999.999999.999999.999999.999999|';
        }

        if (preg_match('/^(\d+(?:\.\d+)*)([.)-]+)?(?:\s|$)/', $normalized, $matches)) {
            if (($matches[2] ?? '') !== '' || str_contains($matches[1], '.')) {
                $segments = array_map('intval', explode('.', rtrim($matches[1], '.')));
                $segments = array_pad($segments, 5, 0);
                $numericKey = collect(array_slice($segments, 0, 5))
                    ->map(fn($segment) => str_pad((string) $segment, 6, '0', STR_PAD_LEFT))
                    ->implode('.');

                return '0|' . $numericKey . '|' . $normalized;
            }
        }

        if (preg_match('/^([VX]|[IVXLCDM]{2,})[.)-]\s*/', $raw, $matches) || ($singleIAsRoman && preg_match('/^(I)[.)-]\s*/', $raw, $matches))) {
            $romanMap = ['i' => 1, 'v' => 5, 'x' => 10, 'l' => 50, 'c' => 100, 'd' => 500, 'm' => 1000];
            $roman = strtolower($matches[1]);
            $value = 0;
            $length = strlen($roman);

            for ($index = 0; $index < $length; $index++) {
                $current = $romanMap[$roman[$index]] ?? 0;
                $next = $index + 1 < $length ? ($romanMap[$roman[$index + 1]] ?? 0) : 0;
                $value += $current < $next ? -$current : $current;
            }

            return '1|' . str_pad((string) $value, 6, '0', STR_PAD_LEFT) . '|' . $normalized;
        }

        if (preg_match('/^([A-Za-z])[.)-]\s*/', $raw, $matches)) {
            return '2|' . str_pad((string) (ord(strtoupper($matches[1])) - 64), 6, '0', STR_PAD_LEFT) . '|' . $normalized;
        }

        return '3|' . $normalized;
    };

    $hasRomanSequence = function ($rows, string $field): bool {
        return collect($rows)->contains(function ($row) use ($field) {
            return preg_match('/^([VX]|[IVXLCDM]{2,})[.)-]\s*/', trim((string) ($row->{$field} ?? ''))) === 1;
        });
    };

    $groupedPrograms = collect($programsRaw ?? $programs)
        ->groupBy(function ($row) use ($normalizeGroupValue) {
            return $normalizeGroupValue($row->title ?? '') . '|'
                . $normalizeGroupValue($row->program ?? '') . '|'
                . $normalizeGroupValue($row->project ?? '') . '|' . $normalizeGroupValue($row->subprogram ?? '') . '|' . $normalizeGroupValue($row->subsubprogram ?? '') . '|' . $normalizeGroupValue($row->subsubsubprogram ?? '') . '|' . $normalizeGroupValue($row->subsubsubsubprogram ?? '') . '|' . $normalizeGroupValue($row->subsubsubsubsubprogram ?? '');
        })
        ->values();

    $buildOfficeMeta = function (array $officeIds) use ($offices) {
        $selectedParentGroups = collect($offices ?? [])
            ->map(function ($parent) use ($officeIds) {
                $parentId = (int) ($parent->id ?? 0);
                $parentSelected = in_array($parentId, $officeIds, true);
                $children = collect($parent->children ?? []);
                $selectedChildren = $children
                    ->filter(fn($child) => in_array((int) ($child->id ?? 0), $officeIds, true))
                    ->values();

                if (!$parentSelected && $selectedChildren->isEmpty()) {
                    return null;
                }

                return [
                    'id' => $parentId,
                    'name' => (string) ($parent->name ?? ''),
                    'office_types_id' => (int) ($parent->office_types_id ?? 0),
                    'selected_parent' => $parentSelected,
                    'children' => $selectedChildren
                        ->map(fn($child) => [
                            'id' => (int) ($child->id ?? 0),
                            'name' => (string) ($child->name ?? ''),
                            'office_types_id' => (int) ($child->office_types_id ?? 0),
                        ])
                        ->filter(fn($child) => $child['id'] > 0)
                        ->values()
                        ->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();

        $inputOffices = collect($selectedParentGroups)
            ->flatMap(function ($group) {
                $selectedParent = (bool) ($group['selected_parent'] ?? false);
                $children = collect($group['children'] ?? [])->map(fn($child) => [
                    'id' => (int) ($child['id'] ?? 0),
                    'name' => (string) ($child['name'] ?? ''),
                    'is_parent' => false,
                ]);
                $parentCollection = $selectedParent ? collect([[
                    'id' => (int) ($group['id'] ?? 0),
                    'name' => (string) ($group['name'] ?? ''),
                    'is_parent' => true,
                ]]) : collect();

                return $parentCollection->merge($children);
            })
            ->filter(fn($office) => !empty($office['id']))
            ->unique('id')
            ->values()
            ->all();

        $groupSizes = collect($selectedParentGroups)
            ->map(function ($group) {
                $selectedParent = (bool) ($group['selected_parent'] ?? false);
                $childrenCount = collect($group['children'] ?? [])->count();
                return ($selectedParent ? 1 : 0) + $childrenCount;
            })
            ->values();

        $groupPenroFlags = collect($selectedParentGroups)
            ->map(function ($group) {
                $officeTypeId = (int) ($group['office_types_id'] ?? 0);
                return $officeTypeId === 2 || preg_match('/\bPENRO\b/i', (string) ($group['name'] ?? '')) === 1;
            })
            ->values();

        $groupBreakIndices = [];
        $runningTotal = 0;
        foreach ($groupSizes as $index => $size) {
            $runningTotal += (int) $size;
            if ($index < ($groupSizes->count() - 1)) {
                $groupBreakIndices[] = $runningTotal - 1;
            }
        }

        return [
            'selected_parent_groups' => $selectedParentGroups,
            'input_offices' => $inputOffices,
            'office_names_csv' => collect($selectedParentGroups)
                ->pluck('name')
                ->map(fn($name) => str_replace('|', '/', (string) $name))
                ->implode('|'),
            'input_office_ids_csv' => collect($inputOffices)->pluck('id')->implode(','),
            'input_office_names_csv' => collect($inputOffices)
                ->pluck('name')
                ->map(fn($name) => str_replace('|', '/', (string) $name))
                ->implode('|'),
            'group_break_indices_csv' => implode(',', $groupBreakIndices),
            'group_penro_flags_csv' => implode(',', $groupPenroFlags->all()),
        ];
    };

    $indicatorOfficeMeta = [];
    collect($indicators ?? [])->flatten(1)->each(function ($indicator) use (&$indicatorOfficeMeta, $buildOfficeMeta) {
        $officeIds = collect($indicator->office_id ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->values()
            ->all();

        $signature = implode(',', $officeIds);
        if (!array_key_exists($signature, $indicatorOfficeMeta)) {
            $indicatorOfficeMeta[$signature] = $buildOfficeMeta($officeIds);
        }
    });
@endphp
@foreach($groupedPrograms as $groupPrograms)
    @php
        $program = $groupPrograms->first();
        $programCoreKey = $normalizeGroupValue($program->title ?? '') . '|' . $normalizeGroupValue($program->program ?? '') . '|' . $normalizeGroupValue($program->project ?? '') . '|' . $normalizeGroupValue($program->subprogram ?? '') . '|' . $normalizeGroupValue($program->subsubprogram ?? '') . '|' . $normalizeGroupValue($program->subsubsubprogram ?? '') . '|' . $normalizeGroupValue($program->subsubsubsubprogram ?? '') . '|' . $normalizeGroupValue($program->subsubsubsubsubprogram ?? '');
        $programSearchText = collect([
            $program->title ?? '',
            $program->subprogram ?? '',
            $program->subsubprogram ?? '',
            $program->subsubsubprogram ?? '',
            $program->subsubsubsubprogram ?? '',
            $program->subsubsubsubsubprogram ?? '',
            $program->program ?? '',
            $program->project ?? '',
        ])->filter()->implode(' ');
    @endphp
    <tr class="program-header group" data-program-id="{{ $program->id }}"
        data-core-key="{{ $programCoreKey }}"
        data-search-text="{{ e($programSearchText) }}"
        onclick='toggleRowsByCoreKey(@json($programCoreKey))'>
        <td class="px-6 py-4" colspan="2">
            <div class="flex items-center justify-between">
                <div class="flex flex-col items-start">
                    @include('components.pap_program_heading')
                </div>
                <span class="flex items-center">
                    @php
                        $hasIndicatorDataForIcon = $groupPrograms->contains(function ($groupProgram) use ($indicators) {
                            $rowKey = (int) ($groupProgram->row_id ?? $groupProgram->id);
                            $programKey = (int) ($groupProgram->id ?? 0);
                            return (isset($indicators[$rowKey]) && $indicators[$rowKey]->count() > 0)
                                || (isset($indicators[$programKey]) && $indicators[$programKey]->count() > 0);
                        });
                    @endphp
                    @if($hasIndicatorDataForIcon)
                        <i class="fa-solid fa-circle-check text-success me-2 ml-2" title="Indicator data available"></i>
                    @else
                        <i class="fa-solid fa-circle-xmark text-danger me-2" title="No indicator data yet"></i>
                    @endif
                    <form method="POST"
                        action="{{ route('admin.continuing_physical.pap.destroy', ['program' => $program->id]) }}"
                        class="me-2 delete-program-form"
                        id="deleteProgramForm-{{ $program->id }}">
                        @csrf
                        @method('DELETE')
                        @foreach($groupPrograms as $gp)
                            <input type="hidden" name="group_ids[]" value="{{ $gp->id }}">
                        @endforeach
                        <button type="button"
                            class="btn btn-sm text-danger py-0 px-1 border-0 bg-transparent"
                            title="Delete PAP" data-bs-toggle="modal"
                            data-bs-target="#deleteProgramConfirmModal"
                            data-delete-form-id="deleteProgramForm-{{ $program->id }}"
                            onclick="event.stopPropagation();">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                    <i id="icon-{{ $program->id }}"
                        class="fa-solid fa-chevron-down program-toggle-icon transition-transform group-hover:text-indigo-600"></i>
                </span>
            </div>
        </td>
    </tr>
    @php
        $activitiesUseRomanSequence = $hasRomanSequence($groupPrograms, 'activities');
        $subActivityGroups = $groupPrograms
            ->groupBy(function($row) {
                return strtolower(trim((string)($row->activities ?? '')));
            })->values();
    @endphp
    @foreach($subActivityGroups as $subActivityGroup)
        @php
            $subActivityName = (string)($subActivityGroup->first()->activities ?? '');
            $hasSubSubActivities = $subActivityGroup->contains(fn($r) => filled($r->subactivities));
            $showAsGroup = filled($subActivityName);
        @endphp
        @if($showAsGroup)
            <tr class="data-row sub-activity-label-row" data-core-key="{{ $programCoreKey }}"
                data-search-text="{{ e($programSearchText . ' ' . $subActivityName) }}" style="display:none;">
                <td colspan="2" class="px-4 py-2 fw-bold" style="background: linear-gradient(to right, #428882, #5caaa4); color:#ffffff; border-left:5px solid #134e4a; letter-spacing:0.03em; font-size:0.85rem; text-transform:uppercase;">
                    <i class="fa-solid fa-layer-group me-2" style="opacity:0.85;"></i>{{ $subActivityName }}
                </td>
            </tr>
        @endif
        @php
            $subactivitiesUseRomanSequence = $hasRomanSequence($subActivityGroup, 'subactivities');
            $subSubActivityGroups = $subActivityGroup
                ->groupBy(function($row) {
                    return strtolower(trim((string)($row->subactivities ?? ''))) . '|'
                        . strtolower(trim((string)(isset($row->subsubactivities) ? $row->subsubactivities : ''))) . '|'
                        . strtolower(trim((string)(isset($row->level_6) ? $row->level_6 : ''))) . '|'
                        . strtolower(trim((string)(isset($row->level_7) ? $row->level_7 : ''))) . '|'
                        . strtolower(trim((string)(isset($row->level_8) ? $row->level_8 : '')));
                })->values();
            $previousPapHierarchyLevels = [];
            $renderedPromotedHierarchyGroupLabels = [];
        @endphp
        @foreach($subSubActivityGroups as $subSubActivityGroup)
            @php
                $groupHasIndicatorData = $subSubActivityGroup->contains(function($sp) use ($indicators) {
                    $rowKey = (int) ($sp->row_id ?? $sp->id);
                    $programKey = (int) ($sp->id ?? 0);
                    $indicatorCollection = $indicators[$rowKey] ?? $indicators[$programKey] ?? collect();
                    return $indicatorCollection->count() > 0;
                });
                $totalIndicatorCount = $subSubActivityGroup->sum(function($sp) use ($indicators) {
                    $rowKey = (int) ($sp->row_id ?? $sp->id);
                    $programKey = (int) ($sp->id ?? 0);
                    $indicatorCollection = $indicators[$rowKey] ?? $indicators[$programKey] ?? collect();
                    return max($indicatorCollection->count(), 1);
                });
                $firstSubProgram = $subSubActivityGroup->first();
                $showActivityInCell = !filled($firstSubProgram->subactivities) && !filled(isset($firstSubProgram->subsubactivities) ? $firstSubProgram->subsubactivities : null) && !filled(isset($firstSubProgram->level_6) ? $firstSubProgram->level_6 : null) && !filled(isset($firstSubProgram->level_7) ? $firstSubProgram->level_7 : null) && !filled(isset($firstSubProgram->level_8) ? $firstSubProgram->level_8 : null) && filled($firstSubProgram->activities);
                $papLeafLabel = filled(isset($firstSubProgram->level_8) ? $firstSubProgram->level_8 : null)
                    ? $firstSubProgram->level_8
                    : (filled(isset($firstSubProgram->level_7) ? $firstSubProgram->level_7 : null)
                        ? $firstSubProgram->level_7
                        : (filled(isset($firstSubProgram->level_6) ? $firstSubProgram->level_6 : null)
                            ? $firstSubProgram->level_6
                            : (filled(isset($firstSubProgram->subsubactivities) ? $firstSubProgram->subsubactivities : null)
                                ? $firstSubProgram->subsubactivities
                                : (filled($firstSubProgram->subactivities)
                                    ? $firstSubProgram->subactivities
                                    : ''))));
                $fullPapHierarchyLevels = [];
                if (filled($firstSubProgram->subactivities)) $fullPapHierarchyLevels[] = $firstSubProgram->subactivities;
                if (filled(isset($firstSubProgram->subsubactivities) ? $firstSubProgram->subsubactivities : null)) $fullPapHierarchyLevels[] = $firstSubProgram->subsubactivities;
                if (filled(isset($firstSubProgram->level_6) ? $firstSubProgram->level_6 : null)) $fullPapHierarchyLevels[] = $firstSubProgram->level_6;
                if (filled(isset($firstSubProgram->level_7) ? $firstSubProgram->level_7 : null)) $fullPapHierarchyLevels[] = $firstSubProgram->level_7;
                if (filled(isset($firstSubProgram->level_8) ? $firstSubProgram->level_8 : null)) $fullPapHierarchyLevels[] = $firstSubProgram->level_8;

                $promotedHierarchyGroupLabel = null;
                $hierarchyLevelDepth = function ($value) {
                    $value = trim((string) $value);
                    if (preg_match('/^(\d+(?:\.\d+)*)(?:[.)]|\s|$)/', $value, $matches)) {
                        return substr_count($matches[1], '.') + 1;
                    }
                    return null;
                };
                if (!filled($subActivityName) && !empty($fullPapHierarchyLevels)) {
                    $firstDepth = $hierarchyLevelDepth($fullPapHierarchyLevels[0] ?? '');
                    $secondDepth = count($fullPapHierarchyLevels) > 1
                        ? $hierarchyLevelDepth($fullPapHierarchyLevels[1] ?? '')
                        : null;

                    if ($firstDepth === 1 && ($secondDepth === null || $secondDepth > $firstDepth)) {
                        $promotedHierarchyGroupLabel = array_shift($fullPapHierarchyLevels);
                    }
                }

                $firstChangedHierarchyIndex = 0;
                foreach ($fullPapHierarchyLevels as $levelIndex => $levelValue) {
                    if (!isset($previousPapHierarchyLevels[$levelIndex]) || $previousPapHierarchyLevels[$levelIndex] !== $levelValue) {
                        $firstChangedHierarchyIndex = $levelIndex;
                        break;
                    }
                    $firstChangedHierarchyIndex = $levelIndex + 1;
                }
                $hierarchyLevelsToDisplay = array_slice($fullPapHierarchyLevels, $firstChangedHierarchyIndex);
                $hierarchyDisplayStartIndex = $firstChangedHierarchyIndex;
                if (
                    count($hierarchyLevelsToDisplay) > 1
                    && filled($subActivityName)
                    && $normalizeGroupValue($hierarchyLevelsToDisplay[0] ?? '') === $normalizeGroupValue($subActivityName)
                ) {
                    array_shift($hierarchyLevelsToDisplay);
                    $hierarchyDisplayStartIndex++;
                }
                if (empty($hierarchyLevelsToDisplay) && !empty($fullPapHierarchyLevels)) {
                    $hierarchyLevelsToDisplay = [end($fullPapHierarchyLevels)];
                    $hierarchyDisplayStartIndex = count($fullPapHierarchyLevels) - 1;
                }
                $previousPapHierarchyLevels = $fullPapHierarchyLevels;
                $isPapCellRendered = false;
                $renderedEmptyIndicatorPlaceholder = false;
            @endphp
            @php
                $promotedHierarchyGroupKey = $normalizeGroupValue($promotedHierarchyGroupLabel ?? '');
                $shouldRenderPromotedHierarchyGroup = !filled($subActivityName)
                    && filled($promotedHierarchyGroupLabel)
                    && !in_array($promotedHierarchyGroupKey, $renderedPromotedHierarchyGroupLabels, true);
                if ($shouldRenderPromotedHierarchyGroup) {
                    $renderedPromotedHierarchyGroupLabels[] = $promotedHierarchyGroupKey;
                }
            @endphp
            @if($shouldRenderPromotedHierarchyGroup)
                <tr class="data-row sub-activity-label-row" data-core-key="{{ $programCoreKey }}"
                    data-search-text="{{ e($programSearchText . ' ' . $promotedHierarchyGroupLabel) }}" style="display:none;">
                    <td colspan="2" class="px-4 py-2 fw-bold" style="background: linear-gradient(to right, #428882, #5caaa4); color:#ffffff; border-left:5px solid #134e4a; letter-spacing:0.03em; font-size:0.85rem; text-transform:uppercase;">
                        <i class="fa-solid fa-layer-group me-2" style="opacity:0.85;"></i>{{ $promotedHierarchyGroupLabel }}
                    </td>
                </tr>
            @endif
            @foreach($subSubActivityGroup as $subProgram)
                @php
                    $subProgramRowKey = (int) ($subProgram->row_id ?? $subProgram->id);
                    $allowParentActivityIndicator = $isEmptyOrNaHierarchyValue($subProgram->subactivities ?? null);
                    $subProgramIndicatorCollection = $indicators[$subProgramRowKey] ?? collect();
                    if ($subProgramIndicatorCollection->isEmpty()) {
                        $subProgramIndicatorCollection = $indicators[(int) (isset($subProgram->level_9_row_id) ? $subProgram->level_9_row_id : 0)] ?? collect();
                    }
                    if ($subProgramIndicatorCollection->isEmpty()) {
                        $subProgramIndicatorCollection = $indicators[(int) (isset($subProgram->level_8_row_id) ? $subProgram->level_8_row_id : 0)] ?? collect();
                    }
                    if ($subProgramIndicatorCollection->isEmpty()) {
                        $subProgramIndicatorCollection = $indicators[(int) (isset($subProgram->level_7_row_id) ? $subProgram->level_7_row_id : 0)] ?? collect();
                    }
                    if ($subProgramIndicatorCollection->isEmpty()) {
                        $subProgramIndicatorCollection = $indicators[(int) (isset($subProgram->level_6_row_id) ? $subProgram->level_6_row_id : 0)] ?? collect();
                    }
                    if ($subProgramIndicatorCollection->isEmpty()) {
                        $subProgramIndicatorCollection = $indicators[(int) (isset($subProgram->subsubactivities_row_id) ? $subProgram->subsubactivities_row_id : 0)] ?? collect();
                    }
                    if ($subProgramIndicatorCollection->isEmpty()) {
                        $subProgramIndicatorCollection = $indicators[(int) ($subProgram->subactivities_row_id ?? 0)] ?? collect();
                    }
                    if ($subProgramIndicatorCollection->isEmpty() && $allowParentActivityIndicator) {
                        $subProgramIndicatorCollection = $indicators[(int) ($subProgram->activities_row_id ?? 0)] ?? collect();
                    }
                    if ($subProgramIndicatorCollection->isEmpty() && $allowParentActivityIndicator) {
                        $subProgramIndicatorCollection = $indicators[(int) ($subProgram->project_row_id ?? 0)] ?? collect();
                    }
                    if ($subProgramIndicatorCollection->isEmpty() && $allowParentActivityIndicator) {
                        $subProgramIndicatorCollection = $indicators[(int) ($subProgram->program_row_id ?? 0)] ?? collect();
                    }
                    if ($subProgramIndicatorCollection->isEmpty() && $allowParentActivityIndicator) {
                        $subProgramIndicatorCollection = $indicators[(int) $subProgram->id] ?? collect();
                    }
                    $hasIndicatorData = $subProgramIndicatorCollection->count() > 0;
                    $renderCount = 0;
                @endphp
                @if($hasIndicatorData)
                    @php
                        $deleteIndicatorIds = $subSubActivityGroup
                            ->flatMap(function ($row) use ($indicators) {
                                $candidateKeys = [
                                    (int) ($row->row_id ?? $row->id),
                                    (int) (isset($row->level_9_row_id) ? $row->level_9_row_id : 0),
                                    (int) (isset($row->level_8_row_id) ? $row->level_8_row_id : 0),
                                    (int) (isset($row->level_7_row_id) ? $row->level_7_row_id : 0),
                                    (int) (isset($row->level_6_row_id) ? $row->level_6_row_id : 0),
                                    (int) (isset($row->subsubactivities_row_id) ? $row->subsubactivities_row_id : 0),
                                    (int) ($row->subactivities_row_id ?? 0),
                                    (int) ($row->activities_row_id ?? 0),
                                    (int) ($row->project_row_id ?? 0),
                                    (int) ($row->program_row_id ?? 0),
                                    (int) ($row->id ?? 0),
                                ];

                                foreach ($candidateKeys as $candidateKey) {
                                    $indicatorCollection = $indicators[$candidateKey] ?? collect();
                                    if ($indicatorCollection->isNotEmpty()) {
                                        return $indicatorCollection->pluck('id');
                                    }
                                }

                                return collect();
                            })
                            ->map(fn($id) => (int) $id)
                            ->filter()
                            ->unique()
                            ->values()
                            ->all();
                    @endphp
                    @foreach($subProgramIndicatorCollection as $indicator)
                        @php $renderCount++; $isPapCellRendered = false; @endphp
                        @php
                            $resolvedIndicatorType = (string) ($indicator->indicator_type ?? '');
                            if ($resolvedIndicatorType === '') {
                                $resolvedIndicatorType = (string) ($indicatorTypeNameById[(int) ($indicator->indicator_type_id ?? 0)] ?? '');
                            }
                            $indicatorSyncKey = $programCoreKey
                                . '|' . strtolower(trim((string) ($indicator->name ?? '')))
                                . '|' . strtolower(trim($resolvedIndicatorType))
                                . '|row-' . (int) ($subProgram->row_id ?? $subProgram->id);
                            $officeIds = collect($indicator->office_id ?? [])
                                ->map(fn($id) => (int) $id)
                                ->filter()
                                ->values()
                                ->all();
                            $officeSignature = implode(',', $officeIds);
                            $officeMeta = $indicatorOfficeMeta[$officeSignature] ?? [
                                'selected_parent_groups' => [],
                                'input_offices' => [],
                                'office_names_csv' => '',
                                'input_office_ids_csv' => '',
                                'input_office_names_csv' => '',
                                'group_break_indices_csv' => '',
                                'input_group_penro_flags' => '',
                            ];
                            $selectedParentGroups = collect($officeMeta['selected_parent_groups'] ?? []);
                            $inputOffices = collect($officeMeta['input_offices'] ?? []);
                            $rowSearchText = collect([
                                $programSearchText,
                                $subProgram->activities ?? '',
                                $subProgram->subactivities ?? '',
                                $subProgram->subsubactivities ?? '',
                                $subProgram->level_6 ?? '',
                                $subProgram->level_7 ?? '',
                                $subProgram->level_8 ?? '',
                                $indicator->name ?? '',
                                $resolvedIndicatorType,
                                $officeMeta['office_names_csv'] ?? '',
                                $officeMeta['input_office_names_csv'] ?? '',
                            ])->filter()->implode(' ');
                        @endphp
                        <tr class="data-row @if(!$isPapCellRendered) first-indicator-row @endif @if($allowParentActivityIndicator) sub-hierarchy-na-row @endif"
                            data-row-id="{{ $subProgram->row_id ?? $subProgram->id }}" data-program-id="{{ $subProgram->id }}" data-indicator-id="{{ $indicator->id }}"
                            data-core-key="{{ $programCoreKey }}" data-sync-key="{{ $indicatorSyncKey }}"
                            data-search-text="{{ e($rowSearchText) }}"
                            data-indicator-type="{{ $resolvedIndicatorType }}"
                            data-office-ids="{{ implode(',', $officeIds) }}"
                            data-office-names="{{ $officeMeta['office_names_csv'] ?? '' }}"
                            data-input-office-ids="{{ $officeMeta['input_office_ids_csv'] ?? '' }}"
                            data-input-office-names="{{ $officeMeta['input_office_names_csv'] ?? '' }}"
                            data-input-break-indices="{{ $officeMeta['group_break_indices_csv'] ?? '' }}"
                            data-input-group-penro-flags="{{ $officeMeta['group_penro_flags_csv'] ?? '' }}"
                            id="content-{{ $subProgram->id }}-{{ $loop->index }}" style="display:none;">
                            @if(!$isPapCellRendered)
                                @php $isPapCellRendered = true; @endphp
                                <td class="px-4 py-3 pl-5 text-primary fw-medium position-relative" style="vertical-align: middle; padding-left: 3.75rem !important;">
                                    @php
                                        $hierarchyLevels = $hierarchyLevelsToDisplay;
                                    @endphp
                                    <button type="button"
                                        class="btn btn-sm btn-outline-danger delete-physical-row-btn d-inline-flex align-items-center justify-content-center position-absolute"
                                        style="top: 0.35rem; left: 0.75rem;"
                                        title="Delete row"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deletePhysicalRowConfirmModal"
                                        data-row-id="{{ $subProgram->row_id ?? $subProgram->id }}"
                                        data-indicator-id="{{ $indicator->id }}"
                                        data-indicator-ids="{{ implode(',', $deleteIndicatorIds) }}"
                                        data-office-ids="{{ implode(',', $officeIds) }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    @if(count($hierarchyLevels) > 0)
                                        @foreach($hierarchyLevels as $index => $level)
                                            <div class="{{ ($hierarchyDisplayStartIndex + $index) > 0 ? 'ms-4 mt-2 fst-italic text-secondary' : '' }}">{{ $level }}</div>
                                        @endforeach
                                    @else
                                        
                                    @endif
                              </td>
                            @endif
                              <td class="px-4 py-3">
                                  @if($hasIndicatorData ?? false)
                                      @php
                                          $indTypeLower = strtolower(trim((string)($indicator->indicator_type ?? '')));
                                          if ($indTypeLower === '' && isset($indicatorTypeNameById)) {
                                              $indTypeLower = strtolower(trim((string)($indicatorTypeNameById[(int)($indicator->indicator_type_id ?? 0)] ?? '')));
                                          }
                                          $indTypeShort = '';
                                          $indTypeTitle = '';
                                          $indTypeBg = '#6c757d';
                                          if ($indTypeLower === 'cumulative') { $indTypeShort = 'C'; $indTypeTitle = 'Cumulative'; $indTypeBg = '#2563eb'; }
                                          elseif ($indTypeLower === 'non-cumulative') { $indTypeShort = 'NC'; $indTypeTitle = 'Non-cumulative'; $indTypeBg = '#dc2626'; }
                                          elseif ($indTypeLower === 'semi-cumulative') { $indTypeShort = 'SC'; $indTypeTitle = 'Semi-cumulative'; $indTypeBg = '#d97706'; }
                                      @endphp
                                      <div class="d-flex flex-column gap-1">
                                          <span>{{ $indicator->name ?? 'N/A' }}</span>
                                          @if($indTypeShort)
                                              <span title="{{ $indTypeTitle }}" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:{{ $indTypeBg }};color:#fff;font-size:10px;font-weight:700;">{{ $indTypeShort }}</span>
                                          @endif
                                      </div>
                                  @else
                                      N/A
                                  @endif
                              </td>
                              <td class="px-4 py-3 small text-center">
                                @if($inputOffices->isNotEmpty())
                                    <div class="office-lines">
                                        <div class="office-line car-office-line">CAR</div>
                                        @foreach($selectedParentGroups as $group)
                                            @php
                                                $parentNameRaw = (string) ($group['name'] ?? '');
                                                $parentSubtotalLabel = preg_replace('/\b(PENRO|CENRO|TOTAL)\b/i', '', $parentNameRaw);
                                                $parentSubtotalLabel = trim(preg_replace('/\s+/', ' ', (string) $parentSubtotalLabel));
                                                $isProvinceTotal = (int) ($group['office_types_id'] ?? 0) === 2 || preg_match('/\bPENRO\b/i', $parentNameRaw) === 1;
                                                $groupDisplayLabel = $parentSubtotalLabel !== '' ? $parentSubtotalLabel : $parentNameRaw;
                                                $parentOfficeLineLabel = $isProvinceTotal ? 'PENRO' : $groupDisplayLabel;
                                                $selectedChildIds = collect($group['children'] ?? [])
                                                    ->pluck('id')
                                                    ->map(fn($id) => (int) $id)
                                                    ->all();
                                                $groupInputOffices = $inputOffices
                                                    ->filter(function ($office) use ($group, $selectedChildIds) {
                                                        if ((bool) ($office['is_parent'] ?? false)) {
                                                            return (int) ($office['id'] ?? 0) === (int) ($group['id'] ?? 0);
                                                        }
                                                        return in_array((int) ($office['id'] ?? 0), $selectedChildIds, true);
                                                    })
                                                    ->values();
                                            @endphp
                                            @if($groupInputOffices->isEmpty())
                                                @continue
                                            @endif
                                            @if($isProvinceTotal)
                                                <div class="office-line group-total-office-line">
                                                    {{ $groupDisplayLabel }}
                                                </div>
                                            @endif
                                            @foreach($groupInputOffices as $office)
                                                @if($office['is_parent'] ?? false)
                                                    <div class="office-line">
                                                        {{ $parentOfficeLineLabel }}
                                                    </div>
                                                @else
                                                    <div class="office-line">{{ $office['name'] ?? '' }}</div>
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </div>
                                @else
                                    <div class="office-lines">
                                        <div class="office-line car-office-line">CAR</div>
                                        <div class="office-line">N/A</div>
                                    </div>
                                @endif
                                </td>
                        </tr>
                    @endforeach
                @else
                    @if($renderCount === 0)
                        @php 
                            $renderCount++; 
                            if (!$isPapCellRendered) {
                                $renderedEmptyIndicatorPlaceholder = true;
                            }
                            $rowSearchText = collect([
                                $programSearchText,
                                $subProgram->activities ?? '',
                                $subProgram->subactivities ?? '',
                                $subProgram->subsubactivities ?? '',
                                $subProgram->level_6 ?? '',
                                $subProgram->level_7 ?? '',
                                $subProgram->level_8 ?? '',
                            ])->filter()->implode(' ');
                        @endphp
                        <tr class="data-row @if(!$isPapCellRendered) first-indicator-row @endif @if($allowParentActivityIndicator) sub-hierarchy-na-row @endif"
                            data-row-id="{{ $subProgram->row_id ?? $subProgram->id }}"
                            data-program-id="{{ $subProgram->id }}"
                            data-indicator-id=""
                            data-core-key="{{ $programCoreKey }}"
                            data-sync-key="{{ $programCoreKey }}|no-indicator|row-{{ (int) ($subProgram->row_id ?? $subProgram->id) }}"
                            data-search-text="{{ e($rowSearchText) }}"
                            data-indicator-type=""
                            data-office-ids=""
                            data-office-names=""
                            data-input-office-ids=""
                            data-input-office-names=""
                            data-input-break-indices=""
                            data-input-group-penro-flags=""
                            id="content-{{ $subProgram->id }}-0"
                            style="display:none;">
                            @if(!$isPapCellRendered)
                                @php $isPapCellRendered = true; @endphp
                                <td class="px-4 py-3 pl-5 text-primary fw-medium position-relative" style="padding-left: 3.75rem !important;">
                                    @php
                                        $hierarchyLevels = $hierarchyLevelsToDisplay;
                                        $showPlaceholderDeleteButton = count($hierarchyLevels) > 0;
                                    @endphp
                                    @if($showPlaceholderDeleteButton)
                                        <button type="button"
                                            class="btn btn-sm btn-outline-danger delete-physical-row-btn d-inline-flex align-items-center justify-content-center position-absolute"
                                            style="top: 0.35rem; left: 0.75rem;"
                                            title="Delete row"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deletePhysicalRowConfirmModal"
                                            data-row-id="{{ $subProgram->row_id ?? $subProgram->id }}"
                                            data-indicator-id=""
                                            data-indicator-ids=""
                                            data-office-ids="">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endif
                                    @if(count($hierarchyLevels) > 0)
                                        @foreach($hierarchyLevels as $index => $level)
                                            <div class="{{ ($hierarchyDisplayStartIndex + $index) > 0 ? 'ms-4 mt-2 fst-italic text-secondary' : '' }}">{{ $level }}</div>
                                        @endforeach
                                    @else
                                        
                                    @endif
                                        
                                    </td>
                            @endif
                              <td class="px-4 py-3">
                                  @if($hasIndicatorData ?? false)
                                      @php
                                          $indTypeLower = strtolower(trim((string)($indicator->indicator_type ?? '')));
                                          if ($indTypeLower === '' && isset($indicatorTypeNameById)) {
                                              $indTypeLower = strtolower(trim((string)($indicatorTypeNameById[(int)($indicator->indicator_type_id ?? 0)] ?? '')));
                                          }
                                          $indTypeShort = '';
                                          $indTypeTitle = '';
                                          $indTypeBg = '#6c757d';
                                          if ($indTypeLower === 'cumulative') { $indTypeShort = 'C'; $indTypeTitle = 'Cumulative'; $indTypeBg = '#2563eb'; }
                                          elseif ($indTypeLower === 'non-cumulative') { $indTypeShort = 'NC'; $indTypeTitle = 'Non-cumulative'; $indTypeBg = '#dc2626'; }
                                          elseif ($indTypeLower === 'semi-cumulative') { $indTypeShort = 'SC'; $indTypeTitle = 'Semi-cumulative'; $indTypeBg = '#d97706'; }
                                      @endphp
                                      <div class="d-flex flex-column gap-1">
                                          <span>{{ $indicator->name ?? 'N/A' }}</span>
                                          @if($indTypeShort)
                                              <span title="{{ $indTypeTitle }}" style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:{{ $indTypeBg }};color:#fff;font-size:10px;font-weight:700;">{{ $indTypeShort }}</span>
                                          @endif
                                      </div>
                                  @else
                                      N/A
                                  @endif
                              </td>
                              <td class="px-4 py-3 small text-center">
                                <div class="office-lines">
                                    <div class="office-line car-office-line">CAR</div>
                                    <div class="office-line">N/A</div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @endif
            @endforeach
        @endforeach
    @endforeach
@endforeach

@include('components.performance_indicator_pap_transfer')

<!-- Add more rows here as needed -->
