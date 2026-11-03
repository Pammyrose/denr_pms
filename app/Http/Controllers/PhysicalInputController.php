<?php

namespace App\Http\Controllers;

use App\Models\PhysicalAccomplishment;
use App\Models\PhysicalTarget;
use App\Services\AccomplishmentPeriodLock;
use App\Services\AccomplishmentSubmissionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PhysicalInputController extends Controller
{
    private const SECTOR_TYPE_CODES = [
        'gass' => 'GASS',
        'sto' => 'STO',
        'enf' => 'ENF',
        'pa' => 'Biodiv',
        'engp' => 'ENGP',
        'lands' => 'Lands',
        'soilcon' => 'Soilcon',
        'nra' => 'NRA',
        'paria' => 'PARIA',
        'cobb' => 'COBB',
        'continuing' => 'CONTINUING',
    ];

    private const PERIODS = [
        'jan', 'feb', 'mar', 'q1',
        'apr', 'may', 'jun', 'q2',
        'jul', 'aug', 'sep', 'q3',
        'oct', 'nov', 'dec', 'q4',
        'annual_total',
    ];

    public function storeTargets(Request $request): JsonResponse
    {
        return $this->store($request, PhysicalTarget::class, false);
    }

    public function storeAccomplishments(Request $request): JsonResponse
    {
        return $this->store($request, PhysicalAccomplishment::class, true);
    }

    public function updatePap(Request $request, string $sector, int $row): JsonResponse
    {
        $sector = strtolower(trim($sector));
        abort_unless(array_key_exists($sector, self::SECTOR_TYPE_CODES), 404);

        $typeId = (int) DB::table('types')
            ->where('code', self::SECTOR_TYPE_CODES[$sector])
            ->value('id');

        abort_if($typeId <= 0, 422, 'The selected physical section is unavailable.');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'program' => ['required_with:subprogram,subsubprogram,subsubsubprogram,subsubsubsubprogram,subsubsubsubsubprogram', 'nullable', 'string', 'max:255'],
            'subprogram' => ['required_with:subsubprogram,subsubsubprogram,subsubsubsubprogram,subsubsubsubsubprogram', 'nullable', 'string', 'max:150'],
            'subsubprogram' => ['required_with:subsubsubprogram,subsubsubsubprogram,subsubsubsubsubprogram', 'nullable', 'string', 'max:150'],
            'subsubsubprogram' => ['required_with:subsubsubsubprogram,subsubsubsubsubprogram', 'nullable', 'string', 'max:150'],
            'subsubsubsubprogram' => ['required_with:subsubsubsubsubprogram', 'nullable', 'string', 'max:150'],
            'subsubsubsubsubprogram' => ['nullable', 'string', 'max:150'],
            'project' => ['nullable', 'string', 'max:255'],
            'activities' => ['nullable', 'string', 'max:255'],
            'subactivities' => ['nullable', 'string', 'max:255'],
            'subsubactivities' => ['nullable', 'string', 'max:255'],
            'level_6' => ['nullable', 'string', 'max:255'],
            'level_7' => ['nullable', 'string', 'max:255'],
            'level_8' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2099'],
        ]);

        $selectedRow = DB::table('ppa')
            ->where('id', $row)
            ->where('types_id', $typeId)
            ->first();

        abort_unless($selectedRow, 404, 'The PAP row to edit was not found.');

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

        $levels = [
            ['record_type' => 'PROGRAM', 'field' => 'title'],
            ['record_type' => 'PROJECT', 'field' => 'program'],
            ['record_type' => 'MAIN ACTIVITY', 'field' => 'project'],
            ['record_type' => 'SUB-ACTIVITY', 'field' => 'activities'],
            ['record_type' => 'SUB-SUB-ACTIVITY', 'field' => 'subactivities'],
            ['record_type' => 'SUB-SUB-SUB-ACTIVITY', 'field' => 'subsubactivities'],
            ['record_type' => 'LEVEL-7', 'field' => 'level_6'],
            ['record_type' => 'LEVEL-8', 'field' => 'level_7'],
            ['record_type' => 'LEVEL-9', 'field' => 'level_8'],
        ];

        $pap = DB::transaction(function () use (
            $validated,
            $levels,
            $recordTypeIds,
            $selectedRow,
            $typeId,
            $row
        ) {
            $detailsByOrder = [];
            $detailId = (int) ($selectedRow->ppa_details_id ?? 0);
            $visitedDetailIds = [];

            while ($detailId > 0 && !isset($visitedDetailIds[$detailId])) {
                $visitedDetailIds[$detailId] = true;
                $detail = DB::table('ppa_details')->where('id', $detailId)->first();
                if (! $detail) {
                    break;
                }

                $detailsByOrder[(int) $detail->column_order] = $detail;
                $detailId = (int) ($detail->parent_id ?? 0);
            }

            abort_unless(isset($detailsByOrder[1]), 422, 'The PAP hierarchy is incomplete and cannot be edited.');

            $programExtensions = [];
            foreach (['subprogram', 'subsubprogram', 'subsubsubprogram', 'subsubsubsubprogram', 'subsubsubsubsubprogram'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $programExtensions[$field] = trim((string) $validated[$field]) ?: null;
                    $detailsByOrder[1]->{$field} = $programExtensions[$field];
                }
            }
            if ($programExtensions !== []) {
                DB::table('ppa_details')->where('id', $detailsByOrder[1]->id)->update($programExtensions);
            }

            ksort($detailsByOrder);
            $parentDetailId = null;
            $rootPpaId = 0;
            $targetRowId = 0;

            foreach ($levels as $index => $level) {
                $columnOrder = $index + 1;
                $name = trim((string) ($validated[$level['field']] ?? ''));
                $recordTypeId = (int) ($recordTypeIds[$level['record_type']] ?? 0);

                abort_if($recordTypeId <= 0, 422, "Record type {$level['record_type']} is not configured.");

                $detail = $detailsByOrder[$columnOrder] ?? null;
                if (! $detail && $name === '') {
                    continue;
                }

                if (! $detail) {
                    $newDetailId = DB::table('ppa_details')->insertGetId([
                        'parent_id' => $parentDetailId,
                        'column_order' => $columnOrder,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $detail = (object) [
                        'id' => $newDetailId,
                        'parent_id' => $parentDetailId,
                        'column_order' => $columnOrder,
                    ];
                    $detailsByOrder[$columnOrder] = $detail;
                }

                $ppaRows = DB::table('ppa')
                    ->where('types_id', $typeId)
                    ->where('record_type_id', $recordTypeId)
                    ->where('ppa_details_id', (int) $detail->id)
                    ->orderBy('id')
                    ->get();

                if ($ppaRows->isEmpty()) {
                    $insert = [
                        'name' => $name,
                        'types_id' => $typeId,
                        'record_type_id' => $recordTypeId,
                        'ppa_details_id' => (int) $detail->id,
                        'indicator_id' => null,
                        'office_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if ($columnOrder === 1 && isset($validated['year'])) {
                        $insert['year'] = (int) $validated['year'];
                    }

                    $ppaId = (int) DB::table('ppa')->insertGetId($insert);
                } else {
                    $update = [
                        'name' => $name,
                        'updated_at' => now(),
                    ];

                    if ($columnOrder === 1 && isset($validated['year'])) {
                        $update['year'] = (int) $validated['year'];
                    }

                    DB::table('ppa')
                        ->whereIn('id', $ppaRows->pluck('id')->all())
                        ->update($update);

                    $selectedLevelRow = $ppaRows->firstWhere('id', $row);
                    $ppaId = (int) ($selectedLevelRow->id ?? $ppaRows->first()->id);
                }

                if ($columnOrder === 1) {
                    $rootPpaId = $ppaId;
                }
                if ($name !== '') {
                    $targetRowId = $ppaId;
                }

                $parentDetailId = (int) $detail->id;
            }

            abort_if($rootPpaId <= 0, 422, 'The PAP root row could not be updated.');

            return (object) [
                'id' => $rootPpaId,
                'row_id' => $targetRowId > 0 ? $targetRowId : $row,
                'title' => (string) ($validated['title'] ?? ''),
                'program' => (string) ($validated['program'] ?? ''),
                'subprogram' => (string) ($detailsByOrder[1]->subprogram ?? ''),
                'subsubprogram' => (string) ($detailsByOrder[1]->subsubprogram ?? ''),
                'subsubsubprogram' => (string) ($detailsByOrder[1]->subsubsubprogram ?? ''),
                'subsubsubsubprogram' => (string) ($detailsByOrder[1]->subsubsubsubprogram ?? ''),
                'subsubsubsubsubprogram' => (string) ($detailsByOrder[1]->subsubsubsubsubprogram ?? ''),
                'project' => (string) ($validated['project'] ?? ''),
                'activities' => (string) ($validated['activities'] ?? ''),
                'subactivities' => (string) ($validated['subactivities'] ?? ''),
                'subsubactivities' => (string) ($validated['subsubactivities'] ?? ''),
                'level_6' => (string) ($validated['level_6'] ?? ''),
                'level_7' => (string) ($validated['level_7'] ?? ''),
                'level_8' => (string) ($validated['level_8'] ?? ''),
                'year' => (int) ($validated['year'] ?? 0),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'PAP updated successfully.',
            'pap' => $pap,
        ]);
    }

    /**
     * @param class-string<Model> $modelClass
     */
    private function store(Request $request, string $modelClass, bool $withRemarks): JsonResponse
    {
        $sector = strtolower(trim((string) $request->route('sector')));
        abort_unless(array_key_exists($sector, self::SECTOR_TYPE_CODES), 404);

        $typeId = DB::table('types')
            ->where('code', self::SECTOR_TYPE_CODES[$sector])
            ->value('id');

        abort_if(! $typeId, 422, 'The selected physical section is unavailable.');

        $ppaRule = Rule::exists('ppa', 'id')->where(
            fn ($query) => $query->where('types_id', $typeId)
        );

        $rules = [
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.program_id' => ['required', 'integer', $ppaRule],
            'entries.*.row_id' => ['nullable', 'integer', $ppaRule],
            'entries.*.indicator_id' => ['required', 'integer', 'exists:indicators,id'],
            'entries.*.office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'entries.*.year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'entries.*.car_totals' => ['nullable', 'array'],
            'entries.*.group_totals' => ['nullable', 'array'],
            'entries.*.imported_from' => ['nullable', 'string', 'max:50'],
            'entries.*.changed_periods' => ['nullable', 'array'],
            'entries.*.changed_periods.*' => ['string', Rule::in(self::PERIODS)],
            'entries.*.change_reason' => ['nullable', 'string', 'max:1000'],
        ];

        if ($withRemarks) {
            $rules['entries.*.remarks'] = ['nullable', 'string'];
        }

        foreach (self::PERIODS as $period) {
            $rules["entries.*.$period"] = ['nullable', 'numeric', 'min:0'];
        }

        $entries = $request->validate($rules)['entries'];
        $userOfficeId = (int) ($request->user()?->office_id ?? 0);

        if ($request->user()?->hasAccomplishmentOnlyAccess() && ! $withRemarks) {
            abort(403, 'Users may enter accomplishments only. Targets are read-only.');
        }

        if ($this->shouldScopeToUserOffice()) {
            foreach ($entries as $entry) {
                abort_if((int) ($entry['office_id'] ?? 0) !== $userOfficeId, 403);
            }
        }

        $createdCount = 0;
        $updatedCount = 0;
        $queuedCount = 0;
        $user = $request->user();
        $canDirectEditLockedChanges = ($user?->isAdmin() ?? false)
            || ($user?->isRegionalOffice() ?? false);

        DB::transaction(function () use ($entries, $sector, $modelClass, $withRemarks, $user, $canDirectEditLockedChanges, &$createdCount, &$updatedCount, &$queuedCount) {
            foreach ($entries as $entry) {
                $programId = (int) $entry['program_id'];
                $identity = [
                    'sector' => $sector,
                    'year' => (int) $entry['year'],
                    'office_id' => filled($entry['office_id'] ?? null) ? (int) $entry['office_id'] : null,
                    'row_id' => (int) ($entry['row_id'] ?? $programId),
                    'indicator_id' => (int) $entry['indicator_id'],
                ];

                /** @var Model $record */
                $record = $modelClass::firstOrNew($identity);
                $wasExisting = $record->exists;

                if ($withRemarks) {
                    $lockedChanges = app(AccomplishmentPeriodLock::class)
                        ->changedLockedPeriods($entry, $wasExisting ? $record : null);

                    if ($lockedChanges !== []) {
                        if (! $canDirectEditLockedChanges) {
                            app(AccomplishmentSubmissionService::class)->queueLockedChange(
                                $user,
                                'physical',
                                $sector,
                                $entry,
                                $lockedChanges,
                                (string) ($entry['change_reason'] ?? '')
                            );
                            $queuedCount++;

                            continue;
                        }
                    }
                }

                $values = [
                    'user_id' => Auth::id(),
                    'program_id' => $programId,
                    'car_totals' => $entry['car_totals'] ?? [],
                    'group_totals' => $entry['group_totals'] ?? [],
                    'imported_from' => $entry['imported_from'] ?? null,
                ];

                if ($withRemarks) {
                    $values['remarks'] = filled($entry['remarks'] ?? null)
                        ? trim((string) $entry['remarks'])
                        : null;
                }

                foreach (self::PERIODS as $period) {
                    $values[$period] = $entry[$period] ?? 0;
                }

                $record->fill($values)->save();
                $wasExisting ? $updatedCount++ : $createdCount++;
            }
        });

        return response()->json([
            'success' => true,
            'pending_approval' => $queuedCount > 0,
            'message' => $queuedCount > 0
                ? "$queuedCount locked-period change request(s) sent for Regional Office/admin approval."
                : "$createdCount created, $updatedCount updated successfully.",
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'queued_count' => $queuedCount,
        ]);
    }
}
