<?php

namespace App\Http\Controllers;

use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

abstract class Controller
{
    protected function programHierarchySortValue($value, string $labelSortValue): string
    {
        $normalized = strtolower(trim((string) ($value ?? '')));
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        if (in_array($normalized, ['', 'n/a', 'na', 'not applicable'], true)) {
            return '0|';
        }

        return '1|'.$labelSortValue;
    }

    protected function roleView(string $view): string
    {
        $user = auth()->user();

        if ($user?->isRegionalOffice()) {
            return 'regional.'.$view;
        }

        if ($user?->isPenro()) {
            return 'penro.'.$view;
        }

        if ($user?->isUser()) {
            return 'users.'.$view;
        }

        return 'admin.'.$view;
    }

    protected function officeIdForPhysicalPage(Request $request, int $defaultOfficeId = 1): int
    {
        $user = auth()->user();

        if ($user && ! $user->isAdmin() && ! $user->isRegionalOffice() && (int) ($user->office_id ?? 0) > 0) {
            return (int) $user->office_id;
        }

        return (int) $request->query('office_id', $defaultOfficeId);
    }

    protected function filterIndicatorsForOffice(Collection $indicators, int $officeId): Collection
    {
        if (! $this->shouldScopeToUserOffice()) {
            return $indicators;
        }

        $officeIds = $this->officeIdsForPhysicalPageScope($officeId);

        return $indicators->map(function (Collection $programIndicators) use ($officeIds) {
            return $programIndicators
                ->filter(function ($indicator) use ($officeIds) {
                    return collect($indicator->office_id ?? [])
                        ->map(fn ($id) => (int) $id)
                        ->intersect($officeIds)
                        ->isNotEmpty();
                })
                ->map(function ($indicator) use ($officeIds) {
                    $indicatorClone = clone $indicator;
                    $indicatorClone->office_id = collect($indicator->office_id ?? [])
                        ->map(fn ($id) => (int) $id)
                        ->intersect($officeIds)
                        ->unique()
                        ->values()
                        ->all();

                    return $indicatorClone;
                })
                ->values();
        });
    }

    protected function filterProgramRowsForOffice(Collection $programs, Collection $indicators, int $officeId): Collection
    {
        if (! $this->shouldScopeToUserOffice()) {
            return $programs;
        }

        $allowedRowIds = $indicators
            ->filter(fn (Collection $programIndicators) => $programIndicators->isNotEmpty())
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->flip();

        if ($allowedRowIds->isEmpty()) {
            return collect();
        }

        return $programs
            ->filter(function ($program) use ($allowedRowIds) {
                $rowIds = [
                    (int) ($program->id ?? 0),
                    (int) ($program->row_id ?? 0),
                    (int) ($program->program_row_id ?? 0),
                    (int) ($program->project_row_id ?? 0),
                    (int) ($program->main_activity_row_id ?? 0),
                    (int) ($program->sub_activity_row_id ?? 0),
                    (int) ($program->sub_sub_activity_row_id ?? 0),
                    (int) ($program->sub_sub_sub_activity_row_id ?? 0),
                    (int) ($program->level_7_row_id ?? 0),
                    (int) ($program->level_8_row_id ?? 0),
                    (int) ($program->level_9_row_id ?? 0),
                ];

                foreach ($rowIds as $rowId) {
                    if ($rowId > 0 && $allowedRowIds->has($rowId)) {
                        return true;
                    }
                }

                return false;
            })
            ->values();
    }

    protected function filterSectionDataForOffice(array $sectionData, int $officeId): array
    {
        if (! $this->shouldScopeToUserOffice()) {
            return $sectionData;
        }

        $officeKeys = collect($this->officeIdsForPhysicalPageScope($officeId))
            ->map(fn (int $scopedOfficeId) => (string) $scopedOfficeId)
            ->all();

        foreach ($sectionData as $programId => $indicators) {
            foreach ($indicators as $indicatorId => $officeRows) {
                $officeRows = is_array($officeRows) ? $officeRows : [];

                $scopedOfficeRows = collect($officeRows)
                    ->only($officeKeys)
                    ->all();

                if (! empty($scopedOfficeRows)) {
                    $sectionData[$programId][$indicatorId] = $scopedOfficeRows;

                    continue;
                }

                unset($sectionData[$programId][$indicatorId]);
            }

            if (empty($sectionData[$programId])) {
                unset($sectionData[$programId]);
            }
        }

        return $sectionData;
    }

    protected function officeIdsForPhysicalPageScope(int $officeId): array
    {
        $user = auth()->user();

        if ($user?->isPenro() && (int) ($user->office_id ?? 0) === $officeId) {
            $serviceAreaOfficeIds = Office::serviceAreaOfficeIdsForPenro($officeId);

            if (! empty($serviceAreaOfficeIds)) {
                return $serviceAreaOfficeIds;
            }
        }

        return [$officeId];
    }

    protected function shouldScopeToUserOffice(): bool
    {
        $user = auth()->user();

        return $user !== null
            && ! $user->isAdmin()
            && ! $user->isRegionalOffice()
            && (int) ($user->office_id ?? 0) > 0;
    }
}
