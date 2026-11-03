<?php

namespace App\Http\Controllers;

use App\Models\FinancialAccomplishment;
use App\Models\FinancialTarget;
use App\Models\Cobb_Indicator;
use App\Models\Continuing_Indicator;
use App\Models\Enf_Indicator;
use App\Models\Engp_Indicator;
use App\Models\Gass_Indicator;
use App\Models\Lands_Indicator;
use App\Models\Nra_Indicator;
use App\Models\Office;
use App\Models\Pa_Indicator;
use App\Models\Paria_Indicator;
use App\Models\PhysicalAccomplishment;
use App\Models\PhysicalTarget;
use App\Models\Soilcon_Indicator;
use App\Models\Sto_Indicator;
use App\Support\SimpleXlsxReader;
use App\Support\ExcelProgramHierarchy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PhysicalExcelUploadController extends Controller
{
    private const BASE_CONFIG = [
        'sheet_name' => '',
        'sheet_aliases' => [],
        'uses_styled_program_headers' => true,
        'uses_styled_section_headers' => false,
        'uses_coded_program_headers' => false,
        'limits_program_title_continuations' => false,
        'program_header_prefixes' => [],
        'merges_car_continuation_headers' => true,
        'merges_activity_continuation_rows' => false,
        'starts_placeholder_from_office_continuation' => false,
        'uses_compact_alpha_headings' => false,
        'unlocated_section_markers' => [],
        'unlocated_section_children_are_rows' => false,
        'root_section_markers' => [],
        'uses_ordered_heading_depths' => false,
        'single_i_is_roman' => false,
        'nested_letter_parent_children' => [],
        'lower_letter_siblings_under_root_number' => false,
        'persists_source_order' => false,
        'pap_header_aliases' => [],
        'location_header_aliases' => [],
        'default_title' => '',
        'sector' => '',
        'type_code' => '',
        'label' => '',
        'indicator_model' => '',
        'start_markers' => [],
        'stop_markers' => [],
    ];

    private const SECTOR_CONFIG = [
        'gass' => [
            'sheet_name' => 'GASS',
            'persists_source_order' => true,
            'default_title' => 'GENERAL ADMINISTRATION AND SUPPORT SERVICES (GASS)',
            'sector' => 'gass',
            'type_code' => 'GASS',
            'label' => 'GASS',
            'indicator_model' => Gass_Indicator::class,
        ],
        'sto' => [
            'sheet_name' => 'STO',
            'persists_source_order' => true,
            'default_title' => 'SUPPORT TO OPERATIONS',
            'sector' => 'sto',
            'type_code' => 'STO',
            'label' => 'STO',
            'indicator_model' => Sto_Indicator::class,
        ],
        'enf' => [
            'sheet_name' => 'ENF',
            'sheet_aliases' => ['NRE&RP'],
            'uses_styled_program_headers' => false,
            'program_header_prefixes' => ['A.03.g.'],
            'merges_car_continuation_headers' => false,
            'starts_placeholder_from_office_continuation' => true,
            'uses_compact_alpha_headings' => true,
            'unlocated_section_markers' => ['TENURES:'],
            'unlocated_section_children_are_rows' => true,
            'root_section_markers' => ['FOREST PROTECTION PROGRAM'],
            'uses_ordered_heading_depths' => true,
            'single_i_is_roman' => true,
            'nested_letter_parent_children' => ['A5' => ['A', 'B', 'C']],
            'persists_source_order' => true,
            'default_title' => 'ENFORCEMENT',
            'sector' => 'enf',
            'type_code' => 'ENF',
            'label' => 'ENF',
            'indicator_model' => Enf_Indicator::class,
            'start_markers' => ['A.03.g.1 Natural Resources'],
        ],
        'pa' => [
            'sheet_name' => 'PA',
            'sheet_aliases' => ['Biodiv'],
            'pap_header_aliases' => ['P/A/P'],
            'location_header_aliases' => ['OFFICE'],
            'merges_car_continuation_headers' => false,
            'merges_activity_continuation_rows' => true,
            'persists_source_order' => true,
            'default_title' => 'PROTECTED AREAS',
            'sector' => 'pa',
            'type_code' => 'Biodiv',
            'label' => 'PA',
            'indicator_model' => Pa_Indicator::class,
        ],
        'engp' => [
            'sheet_name' => 'ENGP',
            'sheet_aliases' => ['E-NGP +Soilcon -rev', 'E-NGP +Soilcon'],
            'merges_car_continuation_headers' => false,
            'merges_activity_continuation_rows' => true,
            'persists_source_order' => true,
            'default_title' => 'FOREST AND WATERSHED MANAGEMENT',
            'sector' => 'engp',
            'type_code' => 'ENGP',
            'label' => 'ENGP',
            'indicator_model' => Engp_Indicator::class,
            'stop_markers' => ['Soil Conservation and Watershed Management'],
        ],
        'lands' => [
            'sheet_name' => 'LANDS',
            'sheet_aliases' => ['Lands (NEP)'],
            'pap_header_aliases' => ['Program/Project/Activity'],
            'merges_car_continuation_headers' => false,
            'uses_compact_alpha_headings' => true,
            'root_section_markers' => ['LAND RECORDS MAINTENANCE'],
            'uses_ordered_heading_depths' => true,
            'single_i_is_roman' => true,
            'lower_letter_siblings_under_root_number' => true,
            'persists_source_order' => true,
            'default_title' => 'LAND MANAGEMENT',
            'sector' => 'lands',
            'type_code' => 'Lands',
            'label' => 'LANDS',
            'indicator_model' => Lands_Indicator::class,
        ],
        'soilcon' => [
            'sheet_name' => 'E-NGP +Soilcon -rev',
            'sheet_aliases' => ['E-NGP +Soilcon'],
            'merges_car_continuation_headers' => false,
            'merges_activity_continuation_rows' => true,
            'persists_source_order' => true,
            'default_title' => 'SOIL CONSERVATION AND WATERSHED MANAGEMENT',
            'sector' => 'soilcon',
            'type_code' => 'Soilcon',
            'label' => 'SOILCON',
            'indicator_model' => Soilcon_Indicator::class,
            'start_markers' => ['Soil Conservation and Watershed Management'],
        ],
        'nra' => [
            'sheet_name' => 'NRA',
            'persists_source_order' => true,
            'default_title' => 'NATURAL RESOURCES ASSESSMENT',
            'sector' => 'nra',
            'type_code' => 'NRA',
            'label' => 'NRA',
            'indicator_model' => Nra_Indicator::class,
        ],
        'paria' => [
            'sheet_name' => 'PARIA',
            'persists_source_order' => true,
            'default_title' => 'PARIA',
            'sector' => 'paria',
            'type_code' => 'PARIA',
            'label' => 'PARIA',
            'indicator_model' => Paria_Indicator::class,
        ],
        'cobb' => [
            'sheet_name' => 'COBB',
            'persists_source_order' => true,
            'default_title' => 'COBB',
            'sector' => 'cobb',
            'type_code' => 'COBB',
            'label' => 'COBB',
            'indicator_model' => Cobb_Indicator::class,
        ],
        'continuing' => [
            'sheet_name' => 'CONTINUING',
            'persists_source_order' => true,
            'default_title' => 'CONTINUING',
            'sector' => 'continuing',
            'type_code' => 'CONTINUING',
            'label' => 'CONTINUING',
            'indicator_model' => Continuing_Indicator::class,
        ],
    ];

    private string $excelSector = 'sto';

    /** @var array<string, array<string, string>|string> */
    private array $excelColumnLayout = [];

    private int $excelDataStartRow = 1;

    private array $excelSortingWarningCache = [];

    /**
     * Sector-specific layout hints are enabled only when the worksheet data
     * actually identifies that layout. A renamed worksheet can therefore be
     * parsed from its rows instead of inheriting rules from its tab name.
     */
    private bool $usesSectorSpecificExcelRules = true;

    public function forSector(string $sector): self
    {
        $sector = strtolower(trim($sector));
        if (! isset(self::SECTOR_CONFIG[$sector])) {
            throw new \InvalidArgumentException("Unsupported physical Excel sector: {$sector}");
        }

        $this->excelSector = $sector;
        $this->usesSectorSpecificExcelRules = true;
        $this->excelColumnLayout = [];
        $this->excelDataStartRow = 1;
        $this->excelSortingWarningCache = [];

        return $this;
    }

    /** @return array<string, mixed> */
    public static function sectorConfiguration(string $sector): array
    {
        $sector = strtolower(trim($sector));
        if (! isset(self::SECTOR_CONFIG[$sector])) {
            throw new \InvalidArgumentException("Unsupported physical Excel sector: {$sector}");
        }

        return array_replace(self::BASE_CONFIG, self::SECTOR_CONFIG[$sector]);
    }

    private function excelConfig(string $key): mixed
    {
        return self::sectorConfiguration($this->excelSector)[$key];
    }

    private function selectSectorFromRequest(Request $request): void
    {
        $this->forSector((string) ($request->route('sector') ?: 'sto'));
    }

    protected function getStoTypeId(): int
    {
        $typeId = DB::table('types')
            ->where('code', $this->excelConfig('type_code'))
            ->value('id');

        if (! $typeId) {
            throw new \RuntimeException($this->excelConfig('label').' type is not configured.');
        }

        return (int) $typeId;
    }

    protected function storePapHierarchyInPpa(array $papData): object
    {
        $typeId = $this->getStoTypeId();
        $recordTypeIds = $this->getPhysicalRecordTypeIds();
        $papYear = isset($papData['year']) ? (int) $papData['year'] : null;
        $sourceOrder = max(0, (int) ($papData['source_order'] ?? 0));
        $forceDuplicateLeaf = ! empty($papData['duplicate_leaf']);

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

        foreach ($levels as $index => $level) {
            if ($level['name'] === '') {
                continue;
            }

            $recordTypeId = $recordTypeIds[$level['record_type']] ?? null;

            if (! $recordTypeId) {
                throw new \RuntimeException("Record type {$level['record_type']} is not configured.");
            }

            $existingNode = null;
            if (! $forceDuplicateLeaf || $index !== $lastLevelIndex) {
                $existingNode = DB::table('ppa_details as details')
                    ->join('ppa', 'ppa.ppa_details_id', '=', 'details.id')
                    ->where('ppa.types_id', $typeId)
                    ->where('ppa.record_type_id', $recordTypeId)
                    ->where('details.column_order', $index + 1)
                    ->when($index === 0, function ($query) use ($papData) {
                        foreach (array_slice(ExcelProgramHierarchy::FIELDS, 2) as $field) {
                            $column = DB::connection()->getQueryGrammar()->wrap('details.'.$field);
                            $query->whereRaw("LOWER(TRIM(COALESCE({$column}, ''))) = ?", [strtolower(trim((string) ($papData[$field] ?? '')))]);
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

                if ($sourceOrder > 0) {
                    DB::table('ppa_details')
                        ->where('id', $detailId)
                        ->where(function ($query) use ($sourceOrder) {
                            $query->whereNull('source_order')
                                ->orWhere('source_order', '>', $sourceOrder);
                        })
                        ->update([
                            'source_order' => $sourceOrder,
                            'updated_at' => now(),
                        ]);
                }
            } else {
                $detailData = [
                    'parent_id' => $parentDetailId,
                    'column_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if ($index === 0) {
                    foreach (array_slice(ExcelProgramHierarchy::FIELDS, 2) as $field) {
                        $detailData[$field] = trim((string) ($papData[$field] ?? '')) ?: null;
                    }
                }
                if ($sourceOrder > 0) {
                    $detailData['source_order'] = $sourceOrder;
                }
                $detailId = DB::table('ppa_details')->insertGetId($detailData);

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
            'project' => (string) ($papData['project'] ?? ''),
            'activities' => (string) ($papData['activities'] ?? ''),
            'subactivities' => (string) ($papData['subactivities'] ?? ''),
        ];
    }

    /** @return array<string, int> */
    private function getPhysicalRecordTypeIds(): array
    {
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
        $recordTypeIds = DB::table('record_types')
            ->whereIn('name', $requiredNames)
            ->pluck('id', 'name')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($requiredNames as $name) {
            if (! isset($recordTypeIds[$name])) {
                throw new \RuntimeException("Record type {$name} is not configured.");
            }
        }

        return $recordTypeIds;
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
                'office_id' => ! empty($normalizedOfficeIds) ? json_encode($normalizedOfficeIds) : null,
                'updated_at' => now(),
            ]);
    }

    protected function hasIndicatorColumn(string $column): bool
    {
        static $columnCache = [];

        if (! array_key_exists($column, $columnCache)) {
            $columnCache[$column] = Schema::hasColumn('indicators', $column);
        }

        return $columnCache[$column];
    }

    protected function prepareExcelProcessing(): void
    {
        $limit = (int) ini_get('max_execution_time');
        if ($limit > 0 && $limit < 300) {
            set_time_limit(300);
        }
    }

    public function importExcel(Request $request)
    {
        $this->selectSectorFromRequest($request);

        $validated = $request->validate([
            'excel_file' => 'required|file|mimes:xlsx|max:51200',
            'year' => 'nullable|integer|min:2000|max:2099',
            'import_type' => 'nullable|in:target,accomplishment,both',
        ]);

        $year = (int) ($validated['year'] ?? $request->input('year', 2026));
        $this->prepareExcelProcessing();
        $filePath = $request->file('excel_file')->getRealPath();

        DB::beginTransaction();

        try {
            $sheetName = $this->resolveExcelSheetName($filePath);
            $importType = $this->resolvePhysicalImportType(
                $filePath,
                $sheetName,
                $validated['import_type'] ?? null
            );
            $results = [];
            foreach ($this->physicalImportTypes($importType) as $index => $physicalImportType) {
                $results[$physicalImportType] = $this->importStoPhysicalRowsFromExcel(
                    $filePath,
                    $year,
                    $physicalImportType,
                    $index === 0,
                    $index === 0
                );
            }
            $result = $this->combinePhysicalImportResults($results);
            DB::commit();

            return redirect()
                ->back()
                ->with('success', sprintf(
                    '%s Excel import complete: %d physical target office row(s), %d physical accomplishment office row(s), %d financial target row(s), and %d financial accomplishment row(s) imported; %d skipped.',
                    $this->excelConfig('label'),
                    $result['target_imported'] ?? 0,
                    $result['accomplishment_imported'] ?? 0,
                    $result['financial_imported'] ?? 0,
                    $result['financial_accomplishment_imported'] ?? 0,
                    $result['skipped'] ?? 0
                ));
        } catch (\Throwable $exception) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', $this->excelConfig('label').' Excel import failed: '.$exception->getMessage());
        }
    }

    public function previewExcelImport(Request $request)
    {
        $this->selectSectorFromRequest($request);

        $validated = $request->validate([
            'excel_file' => 'required|file|mimes:xlsx|max:51200',
            'year' => 'nullable|integer|min:2000|max:2099',
            'import_type' => 'nullable|in:target,accomplishment,both',
        ]);

        $year = (int) ($validated['year'] ?? $request->input('year', 2026));
        $this->prepareExcelProcessing();
        $filePath = $request->file('excel_file')->getRealPath();

        try {
            $sheetName = $this->resolveExcelSheetName($filePath);
            $importType = $this->resolvePhysicalImportType(
                $filePath,
                $sheetName,
                $validated['import_type'] ?? null
            );
            $previews = [];
            foreach ($this->physicalImportTypes($importType) as $physicalImportType) {
                $previews[$physicalImportType] = $this->previewStoPhysicalRowsFromExcel(
                    $filePath,
                    $year,
                    $physicalImportType
                );
            }
            $preview = $this->combinePhysicalImportPreviews($previews);
            $preview['import_type'] = $importType;

            return response()->json([
                'success' => true,
                'preview' => $preview,
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $this->excelConfig('label').' Excel preview failed: '.$exception->getMessage(),
            ], 422);
        }
    }

    private function importStoPhysicalRowsFromExcel(
        string $filePath,
        int $year,
        string $importType = 'target',
        bool $importFinancialRows = true,
        bool $resetHierarchySourceOrder = true
    ): array
    {
        $reader = new SimpleXlsxReader;
        $sheetName = $this->resolveExcelSheetName($filePath, $reader);
        $this->configureExcelColumnLayout($reader, $filePath, $sheetName);
        $this->configureExcelParsingRules($reader, $filePath, $sheetName);
        $this->ensurePhysicalImportColumnsExist($reader, $filePath, $sheetName, $importType);
        $hasFinancialTargetColumns = $importFinancialRows
            && $this->hasFinancialTargetColumns($reader, $filePath, $sheetName);
        $hasFinancialAccomplishmentColumns = $importFinancialRows
            && $this->hasFinancialAccomplishmentColumns($reader, $filePath, $sheetName);
        $officeMap = $this->getOfficeImportMap();
        $staleImportedRowIds = $this->resetImportedPhysicalEntries($year, $importType, $this->excelConfig('sector'));
        if ($resetHierarchySourceOrder) {
            $this->resetImportedHierarchySourceOrder($year);
        }
        $importedPpaRowIds = [];
        $importedOfficeIdsByRowId = [];
        $currentProgram = null;
        $currentProgramMeta = [];
        $currentProgramHierarchy = [];
        $currentHeaders = [];
        $currentBlock = null;
        $standaloneHeaderContext = null;
        $activeUnlocatedSectionParentHeaders = null;
        $currentParentOfficeId = null;
        $imported = 0;
        $financialImported = 0;
        $financialAccomplishmentImported = 0;
        $skipped = 0;
        $placeholders = 0;
        $skipDuplicateSummaryProgram = false;
        $lastProgramHeaderRow = null;
        $lastStyledSectionRow = null;

        $flushBlock = function () use (&$currentBlock, &$imported, &$financialImported, &$financialAccomplishmentImported, &$skipped, &$placeholders, &$importedPpaRowIds, &$importedOfficeIdsByRowId, $year, $importType, $hasFinancialTargetColumns, $hasFinancialAccomplishmentColumns) {
            if ($currentBlock === null) {
                return;
            }

            if (empty($currentBlock['office_rows'])) {
                $skipped++;
                $currentBlock = null;

                return;
            }

            $currentBlock['financial_group_totals'] = $this->financialGroupTotalsFromOfficeRows($currentBlock);
            if (! $this->hasAnyTargetValue($currentBlock['financial_car_totals'] ?? [])) {
                $currentBlock['financial_car_totals'] = $this->sumImportedFinancialCarTotalsForBlock($currentBlock);
            }
            $currentBlock['financial_accomplishment_group_totals'] = $this->financialGroupTotalsFromOfficeRows(
                $currentBlock,
                'financial_accomplishments'
            );
            if (! $this->hasAnyTargetValue($currentBlock['financial_accomplishment_car_totals'] ?? [])) {
                $currentBlock['financial_accomplishment_car_totals'] = $this->sumImportedFinancialCarTotalsForBlock(
                    $currentBlock,
                    'financial_accomplishments'
                );
            }

            $currentBlock = $this->normalizeImportedOfficeRowsForExcelBlock($currentBlock);
            if (empty($currentBlock['office_rows'])) {
                $skipped++;
                $currentBlock = null;

                return;
            }

            $currentBlock = $this->canonicalizeReplicatedPsRequirementsBlock($currentBlock);

            $indicatorName = $this->joinExcelFragments($currentBlock['indicator_parts']);
            if ($indicatorName === '') {
                $skipped++;
                $currentBlock = null;

                return;
            }

            if ($this->shouldSkipAllNaExcelPlaceholderBlock($currentBlock, $indicatorName)) {
                $skipped++;
                $currentBlock = null;

                return;
            }

            if (! empty($currentBlock['placeholder_indicator']) && $this->isExcelNaValue($indicatorName)) {
                $indicatorName = '';
            }

            $papData = $this->papDataFromExcelBlock($currentBlock, $year);
            if ($this->excelPersistsSourceOrder()) {
                $papData['source_order'] = (int) ($currentBlock['source_row'] ?? 0);
            }
            $pap = $this->storePapHierarchyInPpa($papData);
            $indicatorTypeId = $this->inferIndicatorTypeIdFromExcelBlock($currentBlock);
            $indicator = $this->firstOrCreateImportedIndicator($indicatorName, $indicatorTypeId);
            $pap->row_id = $this->resolveImportedIndicatorTargetRowId(
                (int) $pap->row_id,
                $indicatorName,
                (int) $indicator->id
            );
            $importedPpaRowIds[] = (int) $pap->row_id;
            $officeIds = collect($currentBlock['office_rows'])
                ->pluck('office_id')
                ->filter(fn ($officeId) => $officeId !== null)
                ->merge($importedOfficeIdsByRowId[(int) $pap->row_id] ?? [])
                ->unique()
                ->values()
                ->all();
            $importedOfficeIdsByRowId[(int) $pap->row_id] = $officeIds;

            $this->syncProgramIndicatorInPpa((int) $pap->row_id, (int) $indicator->id, $officeIds);

            if (! empty($currentBlock['placeholder_indicator'])) {
                $placeholders++;
            }

            if (! $this->hasAnyTargetValue($currentBlock['car_totals'] ?? [])) {
                $currentBlock['car_totals'] = $this->sumImportedCarTotalsForBlock($currentBlock);
            }

            foreach ($currentBlock['office_rows'] as $officeRow) {
                $this->upsertImportedStoPhysicalEntry(
                    (int) $pap->id,
                    (int) $pap->row_id,
                    (int) $indicator->id,
                    $officeRow['office_id'],
                    $year,
                    $officeRow['targets'],
                    $currentBlock['car_totals'] ?? [],
                    $currentBlock['group_totals'] ?? [],
                    $importType
                );
                $imported++;

                if ($hasFinancialTargetColumns) {
                    $this->upsertImportedStoFinancialTarget(
                        (int) $pap->id,
                        (int) $pap->row_id,
                        (int) $indicator->id,
                        $officeRow['office_id'],
                        $year,
                        $officeRow['financial_targets'] ?? [],
                        $currentBlock['financial_car_totals'] ?? [],
                        $currentBlock['financial_group_totals'] ?? []
                    );
                    $financialImported++;
                }

                if ($hasFinancialAccomplishmentColumns) {
                    $this->upsertImportedStoFinancialAccomplishment(
                        (int) $pap->id,
                        (int) $pap->row_id,
                        (int) $indicator->id,
                        $officeRow['office_id'],
                        $year,
                        $officeRow['financial_accomplishments'] ?? [],
                        $currentBlock['financial_accomplishment_car_totals'] ?? [],
                        $currentBlock['financial_accomplishment_group_totals'] ?? []
                    );
                    $financialAccomplishmentImported++;
                }
            }

            $currentBlock = null;
        };

        $startPlaceholderBlock = function (int $sourceRow, array $targetValues = [], array $financialValues = [], array $financialAccomplishmentValues = []) use (&$currentBlock, &$currentHeaders, &$currentProgram, &$currentProgramMeta, &$currentProgramHierarchy) {
            if (empty($currentHeaders)) {
                return;
            }

            $currentBlock = [
                'source_row' => $sourceRow,
                'title' => $currentProgram ?: $this->excelConfig('default_title'),
                'program_hierarchy' => $currentProgramHierarchy,
                'program' => $currentProgramMeta[0] ?? 'N/A',
                'project' => $currentProgramMeta[1] ?? 'N/A',
                'headers' => $currentHeaders,
                'activity_parts' => [],
                'indicator_parts' => ['N/A'],
                'car_totals' => $targetValues,
                'financial_car_totals' => $financialValues,
                'financial_accomplishment_car_totals' => $financialAccomplishmentValues,
                'group_totals' => [],
                'office_rows' => [],
                'placeholder_indicator' => true,
                'standalone_title' => $this->isStandaloneExcelTitleHeader((string) ($currentHeaders[0] ?? '')),
            ];
        };

        $excelImportStarted = $this->excelStartMarkers() === [];

        foreach ($reader->rowsWithStyles($filePath, $sheetName, true) as $rowNumber => $styledRow) {
            $row = $this->normalizeExcelCoreColumns($styledRow['values'] ?? []);
            $rowStyles = $this->normalizeExcelCoreColumns($styledRow['styles'] ?? []);
            $papText = $this->cleanExcelText($row['A'] ?? '');

            if (! $excelImportStarted) {
                if (! $this->shouldStartExcelImportAtRow($row)) {
                    continue;
                }

                $excelImportStarted = true;
            }

            if ($this->shouldStopExcelImportAtRow($row)) {
                break;
            }

            if ($rowNumber < $this->excelDataStartRow) {
                continue;
            }

            $indicatorText = $this->cleanExcelText($row['B'] ?? '');
            $locationName = $this->cleanExcelText($row['C'] ?? '');
            $targetValues = $this->physicalValuesFromExcelRow($row, $importType);
            $financialValues = $this->financialTargetValuesFromExcelRow($row);
            $financialAccomplishmentValues = $this->financialAccomplishmentValuesFromExcelRow($row);
            $hasTargets = $this->hasAnyTargetValue($targetValues);

            $explicitHeading = $indicatorText === '' && $locationName === '' && ! $hasTargets
                ? ExcelProgramHierarchy::heading($papText)
                : null;
            $explicitValues = $explicitHeading ?? ($row['__program_hierarchy'] ?? []);
            if ($explicitValues !== []) {
                $context = $currentProgramHierarchy ?: [
                    'title' => $currentProgram ?: $this->excelConfig('default_title'),
                    'program' => $currentProgramMeta[0] ?? 'N/A',
                ];
                $nextHierarchy = ExcelProgramHierarchy::merge($context, $explicitValues, $explicitHeading !== null);
                if ($nextHierarchy !== $currentProgramHierarchy) {
                    $flushBlock();
                    $currentProgramHierarchy = $nextHierarchy;
                    $currentProgram = $nextHierarchy['title'] ?? $this->excelConfig('default_title');
                    $currentProgramMeta = [$nextHierarchy['program'] ?? 'N/A', 'N/A'];
                    $currentHeaders = [];
                    $currentParentOfficeId = null;
                    $standaloneHeaderContext = null;
                    $activeUnlocatedSectionParentHeaders = null;
                    $skipDuplicateSummaryProgram = false;
                }
                if ($explicitHeading !== null) {
                    continue;
                }
            }

            if ($papText === '' && $indicatorText === '' && $locationName === '') {
                $skipped++;

                continue;
            }

            $isStyledPapHeader = $this->isStyledStoPapHeaderRow($papText, $row, $rowStyles, $importType);
            if ($currentProgramHierarchy === [] && ($this->isStoProgramHeader($papText, $row) || $isStyledPapHeader)) {
                if ($isStyledPapHeader && $currentProgram !== null && empty($currentHeaders) && $currentBlock === null) {
                    $currentProgram = $this->isStoSheetHeaderText($currentProgram)
                        ? $papText
                        : $this->joinExcelFragments([$currentProgram, $papText]);

                    continue;
                }

                $flushBlock();
                if ($this->isDuplicateStoSummaryProgramHeader($papText)) {
                    $skipDuplicateSummaryProgram = true;
                    $currentProgram = null;
                    $currentProgramMeta = [];
                    $currentHeaders = [];
                    $standaloneHeaderContext = null;
                    $skipped++;

                    continue;
                }

                $skipDuplicateSummaryProgram = false;
                $currentProgram = $papText;
                $currentProgramMeta = [];
                $currentHeaders = [];
                $standaloneHeaderContext = null;
                $activeUnlocatedSectionParentHeaders = null;
                $lastProgramHeaderRow = $rowNumber;
                $lastStyledSectionRow = null;

                continue;
            }

            if ($skipDuplicateSummaryProgram) {
                $skipped++;

                continue;
            }

            if ($indicatorText !== '' && strcasecmp($indicatorText, 'PERFORMANCE INDICATOR') === 0) {
                $skipped++;

                continue;
            }

            if ($locationName === '' && ! $hasTargets && $this->isExcelSignatureFooterRow($papText, $indicatorText, $locationName)) {
                $flushBlock();
                $skipped++;

                continue;
            }

            $officeId = $this->resolveImportedOfficeId($locationName, $officeMap, $currentParentOfficeId);
            if ($importType === 'accomplishment' && $officeId !== null) {
                $hasTargets = true;
            }
            if ($currentProgramHierarchy !== [] && $papText !== '' && $indicatorText === '' && ! $hasTargets && $locationName === '') {
                $flushBlock();
                $this->pushExcelHierarchyHeader($currentHeaders, $papText);
                continue;
            }
            if ($currentProgramHierarchy !== [] && $indicatorText !== '' && $officeId !== null
                && ($currentBlock === null || $papText !== ''
                    || strcasecmp($this->joinExcelFragments($currentBlock['indicator_parts']), $indicatorText) !== 0)) {
                $flushBlock();
                $currentBlock = [
                    'source_row' => $rowNumber,
                    'title' => $currentProgram,
                    'program_hierarchy' => $currentProgramHierarchy,
                    'program' => $currentProgramMeta[0] ?? 'N/A',
                    'project' => 'N/A',
                    'headers' => $currentHeaders,
                    'activity_parts' => $papText !== '' ? [$papText] : [],
                    'indicator_parts' => [$indicatorText],
                    'car_totals' => [],
                    'group_totals' => [],
                    'office_rows' => [[
                        'office_id' => $officeId, 'office_name' => $locationName,
                        'targets' => $targetValues,
                        'financial_targets' => $financialValues ?? [],
                        'financial_accomplishments' => $financialAccomplishmentValues ?? [],
                    ]],
                ];
                $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);
                if ($this->isParentOfficeRow($locationName)) {
                    $currentParentOfficeId = $officeId;
                    $currentBlock['group_totals']['group-1'] = $targetValues;
                }
                continue;
            }

            $isCarRow = strcasecmp($locationName, 'CAR') === 0;
            $isRoRow = $this->isRoOfficeRow($locationName);
            $headingDepth = $this->excelHeadingDepth($papText);

            if (
                $papText !== ''
                && $indicatorText === ''
                && ! $hasTargets
                && $this->isStyledStoSectionHeaderRow($papText, $row, $rowStyles, $importType)
            ) {
                $hadCurrentBlock = $currentBlock !== null;
                $flushBlock();

                if ($this->excelUsesStyledSectionHeaders()) {
                    if (
                        ! $hadCurrentBlock
                        && $lastProgramHeaderRow !== null
                        && $rowNumber <= $lastProgramHeaderRow + 1
                        && $currentProgram !== null
                        && empty($currentHeaders)
                    ) {
                        $currentProgram = $this->joinExcelFragments([$currentProgram, $papText]);
                    } elseif (
                        ! $hadCurrentBlock
                        && $lastStyledSectionRow !== null
                        && $rowNumber <= $lastStyledSectionRow + 2
                        && ! empty($currentHeaders)
                    ) {
                        $this->appendExcelHierarchyLeafFragment($currentHeaders, $papText);
                    } else {
                        $currentHeaders = [$papText];
                    }

                    $lastStyledSectionRow = $rowNumber;
                } else {
                    $this->pushExcelHierarchyHeader($currentHeaders, $papText);
                }

                continue;
            }

            if ($isCarRow || $isRoRow) {
                $currentParentOfficeId = null;
            } elseif ($this->isParentOfficeRow($locationName) && $officeId !== null) {
                $currentParentOfficeId = $officeId;
            }

            if ($isCarRow && $currentBlock !== null && ! empty($currentBlock['office_rows'])) {
                $flushBlock();
            }

            if (
                $this->excelMergesCarContinuationHeaders()
                &&
                $isCarRow
                && $papText !== ''
                && $headingDepth === null
                && ! empty($currentHeaders)
                && $this->isExcelContinuationText($papText)
                && ! $this->isStandaloneExcelTitleHeader($papText)
                && ! $this->isStandaloneExcelSectionHeader($papText)
                && ! $this->isPsRequirementsChild($papText)
            ) {
                $this->appendExcelHierarchyLeafFragment($currentHeaders, $papText);
                $papText = '';

                if ($currentBlock === null) {
                    $startPlaceholderBlock($rowNumber, $targetValues, $financialValues, $financialAccomplishmentValues);

                    continue;
                }
            }

            if (
                $this->excelStartsPlaceholderFromOfficeContinuation()
                && $currentBlock === null
                && $papText !== ''
                && $indicatorText === ''
                && $headingDepth === null
                && $hasTargets
                && ! empty($currentHeaders)
                && $officeId !== null
                && $this->isExcelContinuationText($papText)
            ) {
                $this->appendExcelHierarchyLeafFragment($currentHeaders, $papText);
                $startPlaceholderBlock($rowNumber, [], [], []);

                if ($currentBlock !== null) {
                    $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);
                    $currentBlock['office_rows'][] = [
                        'office_id' => $officeId,
                        'office_name' => $locationName,
                        'targets' => $targetValues,
                        'financial_targets' => $financialValues,
                        'financial_accomplishments' => $financialAccomplishmentValues,
                    ];

                    if ($this->isParentOfficeRow($locationName)) {
                        $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                    }
                }

                continue;
            }

            if (
                $papText !== ''
                && $indicatorText === ''
                && $headingDepth !== null
                && $this->isOfficeBackedExcelLabel($locationName, $officeId)
            ) {
                $flushBlock();
                $this->pushExcelHierarchyHeader($currentHeaders, $papText);
                $startPlaceholderBlock($rowNumber, $targetValues, $financialValues, $financialAccomplishmentValues);

                if ($currentBlock !== null && $officeId !== null) {
                    $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);
                    $currentBlock['office_rows'][] = [
                        'office_id' => $officeId,
                        'office_name' => $locationName,
                        'targets' => $targetValues,
                        'financial_targets' => $financialValues,
                        'financial_accomplishments' => $financialAccomplishmentValues,
                    ];

                    if ($this->isParentOfficeRow($locationName)) {
                        $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                    }
                }

                continue;
            }

            if (! $hasTargets && ! $this->isOfficeBackedExcelLabel($locationName, $officeId) && $this->isExcelSignatureFooterRow($papText, $indicatorText, $locationName)) {
                $flushBlock();
                $skipped++;

                continue;
            }

            if ($papText !== '' && $indicatorText === '' && ! $hasTargets && $isCarRow && $this->isStandaloneExcelTitleHeader($papText)) {
                $flushBlock();
                $currentHeaders = [$papText];
                $standaloneHeaderContext = $papText;

                continue;
            }

            if (
                $papText !== ''
                && $indicatorText === ''
                && ! $hasTargets
                && $isCarRow
                && $standaloneHeaderContext !== null
                && ($this->isPsRequirementsChild($papText) || $this->isStandaloneExcelSectionHeader($papText))
            ) {
                $flushBlock();
                $currentHeaders = [$standaloneHeaderContext, $papText];
                $startPlaceholderBlock($rowNumber, $targetValues, $financialValues, $financialAccomplishmentValues);

                continue;
            }

            if (
                $this->isUnlocatedExcelSectionMarker($papText)
                && $papText !== ''
                && $locationName === ''
            ) {
                if ($currentBlock !== null && $indicatorText !== '') {
                    $currentBlock['indicator_parts'][] = $indicatorText;
                }
                $flushBlock();
                $activeUnlocatedSectionParentHeaders = $currentHeaders;
                $skipped++;

                continue;
            }

            if ($activeUnlocatedSectionParentHeaders !== null && $papText !== '' && $headingDepth !== null) {
                $activeUnlocatedSectionParentHeaders = null;
            }

            if (
                $this->excelUnlocatedSectionChildrenAreRows()
                && $activeUnlocatedSectionParentHeaders !== null
                && $isCarRow
                && $papText !== ''
                && $headingDepth === null
            ) {
                $flushBlock();
                $currentHeaders = array_values(array_merge($activeUnlocatedSectionParentHeaders, [$papText]));

                if ($indicatorText === '') {
                    $startPlaceholderBlock($rowNumber, $targetValues, $financialValues, $financialAccomplishmentValues);

                    continue;
                }

                $papText = '';
            }

            if (
                $currentBlock !== null
                && ! $isCarRow
                && $headingDepth === null
                && ($this->isExcelContinuationText($papText) || $this->isExcelContinuationText($indicatorText))
            ) {
                if ($this->isExcelContinuationText($papText)) {
                    if (! empty($currentBlock['merge_text_only_rows'])) {
                        $currentBlock['activity_parts'][] = $papText;
                    } else {
                        $this->appendExcelHierarchyLeafFragment($currentBlock['headers'], $papText);
                        $this->appendExcelHierarchyLeafFragment($currentHeaders, $papText);
                    }
                }

                if ($this->isExcelContinuationText($indicatorText)) {
                    $currentBlock['indicator_parts'][] = $indicatorText;
                }

                if ($officeId !== null) {
                    $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);

                    $currentBlock['office_rows'][] = [
                        'office_id' => $officeId,
                        'office_name' => $locationName,
                        'targets' => $targetValues,
                        'financial_targets' => $financialValues,
                        'financial_accomplishments' => $financialAccomplishmentValues,
                    ];

                    if ($this->isParentOfficeRow($locationName)) {
                        $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                    }
                }

                continue;
            }

            if ($papText !== '' && $indicatorText !== '' && $headingDepth !== null && ! $isCarRow) {
                $flushBlock();
                $this->pushExcelHierarchyHeader($currentHeaders, $papText);
                $currentBlock = [
                    'source_row' => $rowNumber,
                    'title' => $currentProgram ?: $this->excelConfig('default_title'),
                    'program_hierarchy' => $currentProgramHierarchy,
                    'program' => $currentProgramMeta[0] ?? 'N/A',
                    'project' => $currentProgramMeta[1] ?? 'N/A',
                    'headers' => $currentHeaders,
                    'activity_parts' => [],
                    'duplicate_leaf' => true,
                    'indicator_parts' => [$indicatorText],
                    'car_totals' => [],
                    'financial_car_totals' => [],
                    'financial_accomplishment_car_totals' => [],
                    'group_totals' => [],
                    'office_rows' => [],
                ];

                $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);

                if ($officeId !== null) {
                    $currentBlock['office_rows'][] = [
                        'office_id' => $officeId,
                        'office_name' => $locationName,
                        'targets' => $targetValues,
                        'financial_targets' => $financialValues,
                        'financial_accomplishments' => $financialAccomplishmentValues,
                    ];

                    if ($this->isParentOfficeRow($locationName)) {
                        $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                    }
                }

                continue;
            }

            if ($papText !== '' && $indicatorText !== '' && $isCarRow && $headingDepth !== null) {
                $flushBlock();
                if ($headingDepth === 1 && $this->shouldMergeConstructionLeafFragment($currentProgram, $papText)) {
                    $currentBlock = [
                        'source_row' => $rowNumber,
                        'title' => $currentProgram ?: $this->excelConfig('default_title'),
                        'program_hierarchy' => $currentProgramHierarchy,
                        'program' => $currentProgramMeta[0] ?? 'N/A',
                        'project' => $currentProgramMeta[1] ?? 'N/A',
                        'headers' => $currentHeaders,
                        'activity_parts' => [$papText],
                        'merge_text_only_rows' => true,
                        'indicator_parts' => [$indicatorText],
                        'car_totals' => $targetValues,
                        'financial_car_totals' => $financialValues,
                        'financial_accomplishment_car_totals' => $financialAccomplishmentValues,
                        'group_totals' => [],
                        'office_rows' => [],
                    ];

                    continue;
                }

                $this->pushExcelHierarchyHeader($currentHeaders, $papText);
                $currentBlock = [
                    'source_row' => $rowNumber,
                    'title' => $currentProgram ?: $this->excelConfig('default_title'),
                    'program_hierarchy' => $currentProgramHierarchy,
                    'program' => $currentProgramMeta[0] ?? 'N/A',
                    'project' => $currentProgramMeta[1] ?? 'N/A',
                    'headers' => $currentHeaders,
                    'activity_parts' => [],
                    'duplicate_leaf' => true,
                    'indicator_parts' => [$indicatorText],
                    'car_totals' => $targetValues,
                    'financial_car_totals' => $financialValues,
                    'financial_accomplishment_car_totals' => $financialAccomplishmentValues,
                    'group_totals' => [],
                    'office_rows' => [],
                ];

                continue;
            }

            if (
                $currentBlock !== null
                && $headingDepth === null
                && ($this->isExcelContinuationText($papText) || $this->isExcelContinuationText($indicatorText))
            ) {
                if ($this->isExcelContinuationText($indicatorText)) {
                    $currentBlock['indicator_parts'][] = $indicatorText;
                }

                if ($this->isExcelContinuationText($papText)) {
                    if (! empty($currentBlock['merge_text_only_rows'])) {
                        $currentBlock['activity_parts'][] = $papText;
                    } else {
                        $this->appendExcelHierarchyLeafFragment($currentBlock['headers'], $papText);
                        $this->appendExcelHierarchyLeafFragment($currentHeaders, $papText);
                    }
                }

                if ($officeId !== null) {
                    $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);

                    $currentBlock['office_rows'][] = [
                        'office_id' => $officeId,
                        'office_name' => $locationName,
                        'targets' => $targetValues,
                        'financial_targets' => $financialValues,
                        'financial_accomplishments' => $financialAccomplishmentValues,
                    ];

                    if ($this->isParentOfficeRow($locationName)) {
                        $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                    }
                }

                continue;
            }

            if ($currentBlock === null && $indicatorText !== '' && $officeId !== null && $papText === '') {
                $currentBlock = [
                    'source_row' => $rowNumber,
                    'title' => $currentProgram ?: $this->excelConfig('default_title'),
                    'program_hierarchy' => $currentProgramHierarchy,
                    'program' => $currentProgramMeta[0] ?? 'N/A',
                    'project' => $currentProgramMeta[1] ?? 'N/A',
                    'headers' => $currentHeaders,
                    'activity_parts' => [],
                    'indicator_parts' => [$indicatorText],
                    'car_totals' => [],
                    'financial_car_totals' => [],
                    'financial_accomplishment_car_totals' => [],
                    'group_totals' => [],
                    'office_rows' => [],
                ];

                $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);

                if ($officeId !== null) {
                    $currentBlock['office_rows'][] = [
                        'office_id' => $officeId,
                        'office_name' => $locationName,
                        'targets' => $targetValues,
                        'financial_targets' => $financialValues,
                        'financial_accomplishments' => $financialAccomplishmentValues,
                    ];

                    if ($this->isParentOfficeRow($locationName)) {
                        $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                    }
                }

                continue;
            }

            if ($currentBlock === null && $indicatorText !== '' && $officeId !== null && $papText !== '' && ! empty($currentHeaders) && $headingDepth === null) {
                $currentBlock = [
                    'source_row' => $rowNumber,
                    'title' => $currentProgram ?: $this->excelConfig('default_title'),
                    'program_hierarchy' => $currentProgramHierarchy,
                    'program' => $currentProgramMeta[0] ?? 'N/A',
                    'project' => $currentProgramMeta[1] ?? 'N/A',
                    'headers' => $currentHeaders,
                    'activity_parts' => [$papText],
                    'merge_text_only_rows' => true,
                    'indicator_parts' => [$indicatorText],
                    'car_totals' => [],
                    'financial_car_totals' => [],
                    'financial_accomplishment_car_totals' => [],
                    'group_totals' => [],
                    'office_rows' => [],
                ];

                $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);

                if ($officeId !== null) {
                    $currentBlock['office_rows'][] = [
                        'office_id' => $officeId,
                        'office_name' => $locationName,
                        'targets' => $targetValues,
                        'financial_targets' => $financialValues,
                        'financial_accomplishments' => $financialAccomplishmentValues,
                    ];

                    if ($this->isParentOfficeRow($locationName)) {
                        $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                    }
                }

                continue;
            }

            if ($currentBlock !== null && $isCarRow && empty($currentBlock['car_totals']) && $hasTargets) {
                if ($indicatorText !== '') {
                    $currentBlock['indicator_parts'][] = $indicatorText;
                }
                $currentBlock['car_totals'] = $targetValues;
                $currentBlock['financial_car_totals'] = $financialValues;
                $currentBlock['financial_accomplishment_car_totals'] = $financialAccomplishmentValues;

                continue;
            }

            if ($indicatorText !== '' && $isCarRow) {
                $flushBlock();
                $currentBlock = [
                    'source_row' => $rowNumber,
                    'title' => $currentProgram ?: $this->excelConfig('default_title'),
                    'program_hierarchy' => $currentProgramHierarchy,
                    'program' => $currentProgramMeta[0] ?? 'N/A',
                    'project' => $currentProgramMeta[1] ?? 'N/A',
                    'headers' => $currentHeaders,
                    'activity_parts' => $papText !== '' ? [$papText] : [],
                    'merge_text_only_rows' => $this->excelMergesActivityContinuationRows() && $papText !== '',
                    'duplicate_leaf' => $papText === '' && ! empty($currentHeaders),
                    'indicator_parts' => [$indicatorText],
                    'car_totals' => $targetValues,
                    'financial_car_totals' => $financialValues,
                    'financial_accomplishment_car_totals' => $financialAccomplishmentValues,
                    'group_totals' => [],
                    'office_rows' => [],
                ];

                continue;
            }

            if (
                $currentBlock !== null
                && ! empty($currentBlock['merge_text_only_rows'])
                && $papText !== ''
                && $indicatorText === ''
                && ! $hasTargets
                && $this->isExcelContinuationText($papText)
            ) {
                $currentBlock['activity_parts'][] = $papText;

                continue;
            }

            if (
                $currentBlock !== null
                && $papText !== ''
                && $indicatorText === ''
                && ! $hasTargets
                && $this->isExcelContinuationText($papText)
                && ! $this->isOfficeBackedExcelLabel($locationName, $officeId)
                && preg_match('/^(?!\d+\.\s|[A-Z]\.\s).+/u', $papText) === 1
            ) {
                $this->appendExcelHierarchyLeafFragment($currentBlock['headers'], $papText);
                $this->appendExcelHierarchyLeafFragment($currentHeaders, $papText);

                continue;
            }

            if ($papText !== '' && $indicatorText === '' && ! $hasTargets) {
                if (
                    empty($currentHeaders)
                    && $headingDepth === null
                    && ! $this->isOfficeBackedExcelLabel($locationName, $officeId)
                    && ! $this->isStandaloneExcelTitleHeader($papText)
                    && (
                        ! $this->excelLimitsProgramTitleContinuations()
                        || $lastProgramHeaderRow === null
                        || $rowNumber <= $lastProgramHeaderRow + 1
                    )
                ) {
                    $flushBlock();
                    $currentProgram = $currentProgram === null
                        ? $papText
                        : $this->joinExcelFragments([$currentProgram, $papText]);
                    $currentProgramMeta = [];
                    $standaloneHeaderContext = null;

                    continue;
                }

                if (
                    $this->isStandaloneExcelTitleHeader($papText)
                    && $this->isOfficeBackedExcelLabel($locationName, $officeId)
                ) {
                    $flushBlock();
                    $currentHeaders = [$papText];
                    $standaloneHeaderContext = $papText;

                    continue;
                }

                if (
                    $currentBlock !== null
                    && ! empty($currentBlock['placeholder_indicator'])
                    && $this->isOfficeBackedExcelLabel($locationName, $officeId)
                    && $headingDepth === null
                    && ! $this->isStandaloneExcelTitleHeader($papText)
                ) {
                    $this->appendExcelHierarchyLeafFragment($currentBlock['headers'], $papText);
                    $this->appendExcelHierarchyLeafFragment($currentHeaders, $papText);

                    if ($officeId !== null) {
                        $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);

                        $currentBlock['office_rows'][] = [
                            'office_id' => $officeId,
                            'office_name' => $locationName,
                            'targets' => $targetValues,
                            'financial_targets' => $financialValues,
                            'financial_accomplishments' => $financialAccomplishmentValues,
                        ];

                        if ($this->isParentOfficeRow($locationName)) {
                            $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                        }
                    }

                    continue;
                }

                $flushBlock();
                if ($currentProgram !== null && $this->shouldAppendToProgramTitle($currentProgram, $papText)) {
                    $currentProgram = $this->joinExcelFragments([$currentProgram, $papText]);

                    continue;
                }
                if (
                    $currentProgram !== null
                    && empty($currentHeaders)
                    && $headingDepth === null
                    && ! $this->isStandaloneExcelTitleHeader($papText)
                    && $this->shouldStoreProgramSupplement($papText, true)
                ) {
                    if (count($currentProgramMeta) < 2) {
                        $currentProgramMeta[] = $papText;
                    } else {
                        $lastIndex = count($currentProgramMeta) - 1;
                        $currentProgramMeta[$lastIndex] = $this->joinExcelFragments([$currentProgramMeta[$lastIndex], $papText]);
                    }

                    continue;
                }

                if ($this->isOfficeBackedExcelLabel($locationName, $officeId) && ($headingDepth !== null || strcasecmp($locationName, 'CAR') === 0)) {
                    if ($headingDepth !== null) {
                        if ($standaloneHeaderContext !== null && ! $this->isStandaloneExcelTitleHeader((string) ($currentHeaders[0] ?? ''))) {
                            $currentHeaders = [$standaloneHeaderContext];
                        }
                        $this->pushExcelHierarchyHeader($currentHeaders, $papText);
                    } elseif ($this->isStandaloneExcelTitleHeader($papText)) {
                        $currentHeaders = [$papText];
                        $standaloneHeaderContext = $papText;

                        continue;
                    } else {
                        $this->pushExcelOfficeBackedHierarchyLabel($currentHeaders, $papText);
                    }
                    $startPlaceholderBlock($rowNumber, $targetValues, $financialValues, $financialAccomplishmentValues);

                    if ($currentBlock !== null && $officeId !== null) {
                        $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);

                        $currentBlock['office_rows'][] = [
                            'office_id' => $officeId,
                            'office_name' => $locationName,
                            'targets' => $targetValues,
                            'financial_targets' => $financialValues,
                            'financial_accomplishments' => $financialAccomplishmentValues,
                        ];

                        if ($this->isParentOfficeRow($locationName)) {
                            $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                        }
                    }

                    continue;
                }

                if ($this->isOfficeBackedExcelLabel($locationName, $officeId) && ! empty($currentHeaders)) {
                    $this->appendExcelHierarchyLeafFragment($currentHeaders, $papText);

                    continue;
                }

                $this->pushExcelHierarchyHeader($currentHeaders, $papText);

                continue;
            }

            if ($currentBlock !== null && $this->shouldAppendPerformanceIndicatorFragment($currentBlock, $indicatorText, $officeId)) {
                $currentBlock['indicator_parts'][] = $indicatorText;
            }

            if ($currentBlock !== null && $papText !== '') {
                if (! empty($currentBlock['merge_text_only_rows']) && $this->isExcelContinuationText($papText)) {
                    $currentBlock['activity_parts'][] = $papText;
                } elseif ($this->isExcelContinuationText($papText)) {
                    $this->appendExcelHierarchyLeafFragment($currentBlock['headers'], $papText);
                    $this->appendExcelHierarchyLeafFragment($currentHeaders, $papText);
                } else {
                    $currentBlock['activity_parts'][] = $papText;
                }
            }

            if ($currentBlock !== null && $officeId !== null) {
                $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);

                $currentBlock['office_rows'][] = [
                    'office_id' => $officeId,
                    'office_name' => $locationName,
                    'targets' => $targetValues,
                    'financial_targets' => $financialValues,
                    'financial_accomplishments' => $financialAccomplishmentValues,
                ];

                if ($this->isParentOfficeRow($locationName)) {
                    $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                }

                continue;
            }

            $skipped++;
        }

        $flushBlock();
        $this->deleteObsoleteImportedPpaRows($staleImportedRowIds, $importedPpaRowIds);
        $this->deleteEmptyNaLeafPpaRows($this->excelConfig('type_code'));

        return [
            'imported' => $imported,
            'financial_imported' => $financialImported,
            'financial_accomplishment_imported' => $financialAccomplishmentImported,
            'skipped' => $skipped,
            'placeholders' => $placeholders,
        ];
    }

    private function previewStoPhysicalRowsFromExcel(string $filePath, int $year, string $importType = 'target'): array
    {
        $reader = new SimpleXlsxReader;
        $sheetName = $this->resolveExcelSheetName($filePath, $reader);
        $this->configureExcelColumnLayout($reader, $filePath, $sheetName);
        $this->configureExcelParsingRules($reader, $filePath, $sheetName);
        $this->ensurePhysicalImportColumnsExist($reader, $filePath, $sheetName, $importType);
        $officeMap = $this->getOfficeImportMap();
        $currentProgram = null;
        $currentProgramMeta = [];
        $currentProgramHierarchy = [];
        $currentHeaders = [];
        $currentBlock = null;
        $standaloneHeaderContext = null;
        $activeUnlocatedSectionParentHeaders = null;
        $currentParentOfficeId = null;
        $previewRows = [];
        $warnings = $this->previewExcelSortingWarnings($filePath, $sheetName);
        $imported = 0;
        $skipped = 0;
        $totalParsedRows = 0;
        $placeholders = 0;
        $skipDuplicateSummaryProgram = false;
        $lastProgramHeaderRow = null;
        $lastStyledSectionRow = null;

        $flushBlock = function () use (&$currentBlock, &$previewRows, &$warnings, &$imported, &$skipped, &$totalParsedRows, &$placeholders, $year) {
            if ($currentBlock === null) {
                return;
            }

            $sourceRow = (int) ($currentBlock['source_row'] ?? 0);

            if (empty($currentBlock['office_rows'])) {
                $skipped++;
                if ($sourceRow > 0) {
                    $warnings[] = [
                        'row' => $sourceRow,
                        'level' => 'warning',
                        'message' => 'This parsed item has no matched office rows. Please check the office/location column.',
                    ];
                }
                $currentBlock = null;

                return;
            }

            $currentBlock = $this->normalizeImportedOfficeRowsForExcelBlock($currentBlock);
            if (empty($currentBlock['office_rows'])) {
                $skipped++;
                $currentBlock = null;

                return;
            }

            $currentBlock = $this->canonicalizeReplicatedPsRequirementsBlock($currentBlock);

            $indicatorName = $this->joinExcelFragments($currentBlock['indicator_parts']);
            if ($indicatorName === '') {
                $skipped++;
                if ($sourceRow > 0) {
                    $warnings[] = [
                        'row' => $sourceRow,
                        'level' => 'warning',
                        'message' => 'This parsed item has no performance indicator.',
                    ];
                }
                $currentBlock = null;

                return;
            }

            if ($this->shouldSkipAllNaExcelPlaceholderBlock($currentBlock, $indicatorName)) {
                $skipped++;
                $currentBlock = null;

                return;
            }

            if (! empty($currentBlock['placeholder_indicator']) && $this->isExcelNaValue($indicatorName)) {
                $indicatorName = '';
            }

            $papData = $this->papDataFromExcelBlock($currentBlock, $year);
            $hierarchy = collect([
                $papData['activities'] ?? null,
                $papData['subactivities'] ?? null,
                $papData['subsubactivities'] ?? null,
                $papData['level_6'] ?? null,
                $papData['level_7'] ?? null,
                $papData['level_8'] ?? null,
            ])
                ->filter(fn ($value) => $value !== null && trim((string) $value) !== '' && strtoupper(trim((string) $value)) !== 'N/A')
                ->values()
                ->all();

            $officeNames = collect($currentBlock['office_rows'])
                ->pluck('office_name')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $totalParsedRows++;
            if (count($previewRows) < 120) {
                $previewRows[] = [
                    'row' => $sourceRow,
                    'title' => $papData['title'] ?? '',
                    'program' => $papData['program'] ?? '',
                    'project' => $papData['project'] ?? '',
                    ...array_intersect_key($papData, array_flip(array_slice(ExcelProgramHierarchy::FIELDS, 2))),
                    'hierarchy' => $hierarchy,
                    'indicator' => $indicatorName,
                    'offices' => $officeNames,
                    'office_count' => count($currentBlock['office_rows']),
                    'has_car_total' => ! empty($currentBlock['car_totals']),
                ];
            }

            $imported += count($currentBlock['office_rows']);
            if (! empty($currentBlock['placeholder_indicator'])) {
                $placeholders++;
            }
            $currentBlock = null;
        };

        $startPlaceholderPreviewBlock = function (int $sourceRow, array $targetValues = []) use (&$currentBlock, &$currentHeaders, &$currentProgram, &$currentProgramMeta, &$currentProgramHierarchy) {
            if (empty($currentHeaders)) {
                return;
            }

            $currentBlock = [
                'source_row' => $sourceRow,
                'title' => $currentProgram ?: $this->excelConfig('default_title'),
                'program_hierarchy' => $currentProgramHierarchy,
                'program' => $currentProgramMeta[0] ?? 'N/A',
                'project' => $currentProgramMeta[1] ?? 'N/A',
                'headers' => $currentHeaders,
                'activity_parts' => [],
                'indicator_parts' => ['N/A'],
                'car_totals' => $targetValues,
                'group_totals' => [],
                'office_rows' => [],
                'placeholder_indicator' => true,
                'standalone_title' => $this->isStandaloneExcelTitleHeader((string) ($currentHeaders[0] ?? '')),
            ];
        };

        $excelImportStarted = $this->excelStartMarkers() === [];

        foreach ($reader->rowsWithStyles($filePath, $sheetName, true) as $rowNumber => $styledRow) {
            $row = $this->normalizeExcelCoreColumns($styledRow['values'] ?? []);
            $rowStyles = $this->normalizeExcelCoreColumns($styledRow['styles'] ?? []);
            $papText = $this->cleanExcelText($row['A'] ?? '');

            if (! $excelImportStarted) {
                if (! $this->shouldStartExcelImportAtRow($row)) {
                    continue;
                }

                $excelImportStarted = true;
            }

            if ($this->shouldStopExcelImportAtRow($row)) {
                break;
            }

            if ($rowNumber < $this->excelDataStartRow) {
                continue;
            }

            $indicatorText = $this->cleanExcelText($row['B'] ?? '');
            $locationName = $this->cleanExcelText($row['C'] ?? '');
            $targetValues = $this->physicalValuesFromExcelRow($row, $importType);
            $hasTargets = $this->hasAnyTargetValue($targetValues);

            $explicitHeading = $indicatorText === '' && $locationName === '' && ! $hasTargets
                ? ExcelProgramHierarchy::heading($papText)
                : null;
            $explicitValues = $explicitHeading ?? ($row['__program_hierarchy'] ?? []);
            if ($explicitValues !== []) {
                $context = $currentProgramHierarchy ?: [
                    'title' => $currentProgram ?: $this->excelConfig('default_title'),
                    'program' => $currentProgramMeta[0] ?? 'N/A',
                ];
                $nextHierarchy = ExcelProgramHierarchy::merge($context, $explicitValues, $explicitHeading !== null);
                if ($nextHierarchy !== $currentProgramHierarchy) {
                    $flushBlock();
                    $currentProgramHierarchy = $nextHierarchy;
                    $currentProgram = $nextHierarchy['title'] ?? $this->excelConfig('default_title');
                    $currentProgramMeta = [$nextHierarchy['program'] ?? 'N/A', 'N/A'];
                    $currentHeaders = [];
                    $currentParentOfficeId = null;
                    $standaloneHeaderContext = null;
                    $activeUnlocatedSectionParentHeaders = null;
                    $skipDuplicateSummaryProgram = false;
                }
                if ($explicitHeading !== null) {
                    continue;
                }
            }

            if ($papText === '' && $indicatorText === '' && $locationName === '') {
                $skipped++;

                continue;
            }

            $isStyledPapHeader = $this->isStyledStoPapHeaderRow($papText, $row, $rowStyles, $importType);
            if ($currentProgramHierarchy === [] && ($this->isStoProgramHeader($papText, $row) || $isStyledPapHeader)) {
                if ($isStyledPapHeader && $currentProgram !== null && empty($currentHeaders) && $currentBlock === null) {
                    $currentProgram = $this->isStoSheetHeaderText($currentProgram)
                        ? $papText
                        : $this->joinExcelFragments([$currentProgram, $papText]);

                    continue;
                }

                $flushBlock();
                if ($this->isDuplicateStoSummaryProgramHeader($papText)) {
                    $skipDuplicateSummaryProgram = true;
                    $currentProgram = null;
                    $currentProgramMeta = [];
                    $currentHeaders = [];
                    $standaloneHeaderContext = null;
                    $skipped++;

                    continue;
                }

                $skipDuplicateSummaryProgram = false;
                $currentProgram = $papText;
                $currentProgramMeta = [];
                $currentHeaders = [];
                $standaloneHeaderContext = null;
                $activeUnlocatedSectionParentHeaders = null;
                $lastProgramHeaderRow = $rowNumber;
                $lastStyledSectionRow = null;

                continue;
            }

            if ($skipDuplicateSummaryProgram) {
                $skipped++;

                continue;
            }

            if ($indicatorText !== '' && strcasecmp($indicatorText, 'PERFORMANCE INDICATOR') === 0) {
                $skipped++;

                continue;
            }

            if ($locationName === '' && ! $hasTargets && $this->isExcelSignatureFooterRow($papText, $indicatorText, $locationName)) {
                $flushBlock();
                $skipped++;

                continue;
            }

            $officeId = $this->resolveImportedOfficeId($locationName, $officeMap, $currentParentOfficeId);
            if ($importType === 'accomplishment' && $officeId !== null) {
                $hasTargets = true;
            }
            if ($currentProgramHierarchy !== [] && $papText !== '' && $indicatorText === '' && ! $hasTargets && $locationName === '') {
                $flushBlock();
                $this->pushExcelHierarchyHeader($currentHeaders, $papText);
                continue;
            }
            if ($currentProgramHierarchy !== [] && $indicatorText !== '' && $officeId !== null
                && ($currentBlock === null || $papText !== ''
                    || strcasecmp($this->joinExcelFragments($currentBlock['indicator_parts']), $indicatorText) !== 0)) {
                $flushBlock();
                $currentBlock = [
                    'source_row' => $rowNumber,
                    'title' => $currentProgram,
                    'program_hierarchy' => $currentProgramHierarchy,
                    'program' => $currentProgramMeta[0] ?? 'N/A',
                    'project' => 'N/A',
                    'headers' => $currentHeaders,
                    'activity_parts' => $papText !== '' ? [$papText] : [],
                    'indicator_parts' => [$indicatorText],
                    'car_totals' => [],
                    'group_totals' => [],
                    'office_rows' => [[
                        'office_id' => $officeId, 'office_name' => $locationName,
                        'targets' => $targetValues,
                        'financial_targets' => $financialValues ?? [],
                        'financial_accomplishments' => $financialAccomplishmentValues ?? [],
                    ]],
                ];
                $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);
                if ($this->isParentOfficeRow($locationName)) {
                    $currentParentOfficeId = $officeId;
                    $currentBlock['group_totals']['group-1'] = $targetValues;
                }
                continue;
            }

            $isCarRow = strcasecmp($locationName, 'CAR') === 0;
            $isRoRow = $this->isRoOfficeRow($locationName);
            $headingDepth = $this->excelHeadingDepth($papText);

            if (
                $papText !== ''
                && $indicatorText === ''
                && ! $hasTargets
                && $this->isStyledStoSectionHeaderRow($papText, $row, $rowStyles, $importType)
            ) {
                $hadCurrentBlock = $currentBlock !== null;
                $flushBlock();

                if ($this->excelUsesStyledSectionHeaders()) {
                    if (
                        ! $hadCurrentBlock
                        && $lastProgramHeaderRow !== null
                        && $rowNumber <= $lastProgramHeaderRow + 1
                        && $currentProgram !== null
                        && empty($currentHeaders)
                    ) {
                        $currentProgram = $this->joinExcelFragments([$currentProgram, $papText]);
                    } elseif (
                        ! $hadCurrentBlock
                        && $lastStyledSectionRow !== null
                        && $rowNumber <= $lastStyledSectionRow + 2
                        && ! empty($currentHeaders)
                    ) {
                        $this->appendExcelHierarchyLeafFragment($currentHeaders, $papText);
                    } else {
                        $currentHeaders = [$papText];
                    }

                    $lastStyledSectionRow = $rowNumber;
                } else {
                    $this->pushExcelHierarchyHeader($currentHeaders, $papText);
                }

                continue;
            }

            if ($isCarRow || $isRoRow) {
                $currentParentOfficeId = null;
            } elseif ($this->isParentOfficeRow($locationName) && $officeId !== null) {
                $currentParentOfficeId = $officeId;
            }

            if ($isCarRow && $currentBlock !== null && ! empty($currentBlock['office_rows'])) {
                $flushBlock();
            }

            if (
                $this->excelMergesCarContinuationHeaders()
                &&
                $isCarRow
                && $papText !== ''
                && $headingDepth === null
                && ! empty($currentHeaders)
                && $this->isExcelContinuationText($papText)
                && ! $this->isStandaloneExcelTitleHeader($papText)
                && ! $this->isStandaloneExcelSectionHeader($papText)
                && ! $this->isPsRequirementsChild($papText)
            ) {
                $this->appendExcelHierarchyLeafFragment($currentHeaders, $papText);
                $papText = '';

                if ($currentBlock === null) {
                    $startPlaceholderPreviewBlock($rowNumber, $targetValues);

                    continue;
                }
            }

            if (
                $this->excelStartsPlaceholderFromOfficeContinuation()
                && $currentBlock === null
                && $papText !== ''
                && $indicatorText === ''
                && $headingDepth === null
                && $hasTargets
                && ! empty($currentHeaders)
                && $officeId !== null
                && $this->isExcelContinuationText($papText)
            ) {
                $this->appendExcelHierarchyLeafFragment($currentHeaders, $papText);
                $startPlaceholderPreviewBlock($rowNumber, []);

                if ($currentBlock !== null) {
                    $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);
                    $currentBlock['office_rows'][] = [
                        'office_id' => $officeId,
                        'office_name' => $locationName,
                        'targets' => $targetValues,
                    ];

                    if ($this->isParentOfficeRow($locationName)) {
                        $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                    }
                }

                continue;
            }

            if (
                $papText !== ''
                && $indicatorText === ''
                && $headingDepth !== null
                && $this->isOfficeBackedExcelLabel($locationName, $officeId)
            ) {
                $flushBlock();
                $this->pushExcelHierarchyHeader($currentHeaders, $papText);
                $startPlaceholderPreviewBlock($rowNumber, $targetValues);

                if ($currentBlock !== null && $officeId !== null) {
                    $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);
                    $currentBlock['office_rows'][] = [
                        'office_id' => $officeId,
                        'office_name' => $locationName,
                        'targets' => $targetValues,
                    ];

                    if ($this->isParentOfficeRow($locationName)) {
                        $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                    }
                }

                continue;
            }

            if (! $hasTargets && ! $this->isOfficeBackedExcelLabel($locationName, $officeId) && $this->isExcelSignatureFooterRow($papText, $indicatorText, $locationName)) {
                $flushBlock();
                $skipped++;

                continue;
            }

            if ($papText !== '' && $indicatorText === '' && ! $hasTargets && $isCarRow && $this->isStandaloneExcelTitleHeader($papText)) {
                $flushBlock();
                $currentHeaders = [$papText];
                $standaloneHeaderContext = $papText;

                continue;
            }

            if (
                $papText !== ''
                && $indicatorText === ''
                && ! $hasTargets
                && $isCarRow
                && $standaloneHeaderContext !== null
                && ($this->isPsRequirementsChild($papText) || $this->isStandaloneExcelSectionHeader($papText))
            ) {
                $flushBlock();
                $currentHeaders = [$standaloneHeaderContext, $papText];
                $startPlaceholderPreviewBlock($rowNumber, $targetValues);

                continue;
            }

            if (
                $this->isUnlocatedExcelSectionMarker($papText)
                && $papText !== ''
                && $locationName === ''
            ) {
                if ($currentBlock !== null && $indicatorText !== '') {
                    $currentBlock['indicator_parts'][] = $indicatorText;
                }
                $flushBlock();
                $activeUnlocatedSectionParentHeaders = $currentHeaders;
                $skipped++;

                continue;
            }

            if ($activeUnlocatedSectionParentHeaders !== null && $papText !== '' && $headingDepth !== null) {
                $activeUnlocatedSectionParentHeaders = null;
            }

            if (
                $this->excelUnlocatedSectionChildrenAreRows()
                && $activeUnlocatedSectionParentHeaders !== null
                && $isCarRow
                && $papText !== ''
                && $headingDepth === null
            ) {
                $flushBlock();
                $currentHeaders = array_values(array_merge($activeUnlocatedSectionParentHeaders, [$papText]));

                if ($indicatorText === '') {
                    $startPlaceholderPreviewBlock($rowNumber, $targetValues);

                    continue;
                }

                $papText = '';
            }

            if (
                $currentBlock !== null
                && ! $isCarRow
                && $headingDepth === null
                && ($this->isExcelContinuationText($papText) || $this->isExcelContinuationText($indicatorText))
            ) {
                if ($this->isExcelContinuationText($papText)) {
                    if (! empty($currentBlock['merge_text_only_rows'])) {
                        $currentBlock['activity_parts'][] = $papText;
                    } else {
                        $this->appendExcelHierarchyLeafFragment($currentBlock['headers'], $papText);
                        $this->appendExcelHierarchyLeafFragment($currentHeaders, $papText);
                    }
                }

                if ($this->isExcelContinuationText($indicatorText)) {
                    $currentBlock['indicator_parts'][] = $indicatorText;
                }

                if ($officeId !== null) {
                    $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);

                    $currentBlock['office_rows'][] = [
                        'office_id' => $officeId,
                        'office_name' => $locationName,
                        'targets' => $targetValues,
                    ];

                    if ($this->isParentOfficeRow($locationName)) {
                        $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                    }
                }

                continue;
            }

            if ($papText !== '' && $indicatorText !== '' && $headingDepth !== null && ! $isCarRow) {
                $flushBlock();
                $this->pushExcelHierarchyHeader($currentHeaders, $papText);
                $currentBlock = [
                    'source_row' => $rowNumber,
                    'title' => $currentProgram ?: $this->excelConfig('default_title'),
                    'program_hierarchy' => $currentProgramHierarchy,
                    'program' => $currentProgramMeta[0] ?? 'N/A',
                    'project' => $currentProgramMeta[1] ?? 'N/A',
                    'headers' => $currentHeaders,
                    'activity_parts' => [],
                    'duplicate_leaf' => true,
                    'indicator_parts' => [$indicatorText],
                    'car_totals' => [],
                    'group_totals' => [],
                    'office_rows' => [],
                ];

                $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);

                if ($officeId !== null) {
                    $currentBlock['office_rows'][] = [
                        'office_id' => $officeId,
                        'office_name' => $locationName,
                        'targets' => $targetValues,
                    ];

                    if ($this->isParentOfficeRow($locationName)) {
                        $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                    }
                }

                continue;
            }

            if ($papText !== '' && $indicatorText !== '' && $isCarRow && $headingDepth !== null) {
                $flushBlock();
                if ($headingDepth === 1 && $this->shouldMergeConstructionLeafFragment($currentProgram, $papText)) {
                    $currentBlock = [
                        'source_row' => $rowNumber,
                        'title' => $currentProgram ?: $this->excelConfig('default_title'),
                        'program_hierarchy' => $currentProgramHierarchy,
                        'program' => $currentProgramMeta[0] ?? 'N/A',
                        'project' => $currentProgramMeta[1] ?? 'N/A',
                        'headers' => $currentHeaders,
                        'activity_parts' => [$papText],
                        'merge_text_only_rows' => true,
                        'indicator_parts' => [$indicatorText],
                        'car_totals' => $targetValues,
                        'group_totals' => [],
                        'office_rows' => [],
                    ];

                    continue;
                }

                $this->pushExcelHierarchyHeader($currentHeaders, $papText);
                $currentBlock = [
                    'source_row' => $rowNumber,
                    'title' => $currentProgram ?: $this->excelConfig('default_title'),
                    'program_hierarchy' => $currentProgramHierarchy,
                    'program' => $currentProgramMeta[0] ?? 'N/A',
                    'project' => $currentProgramMeta[1] ?? 'N/A',
                    'headers' => $currentHeaders,
                    'activity_parts' => [],
                    'duplicate_leaf' => true,
                    'indicator_parts' => [$indicatorText],
                    'car_totals' => $targetValues,
                    'group_totals' => [],
                    'office_rows' => [],
                ];

                continue;
            }

            if (
                $currentBlock !== null
                && $headingDepth === null
                && ($this->isExcelContinuationText($papText) || $this->isExcelContinuationText($indicatorText))
            ) {
                if ($this->isExcelContinuationText($indicatorText)) {
                    $currentBlock['indicator_parts'][] = $indicatorText;
                }

                if ($this->isExcelContinuationText($papText)) {
                    if (! empty($currentBlock['merge_text_only_rows'])) {
                        $currentBlock['activity_parts'][] = $papText;
                    } else {
                        $this->appendExcelHierarchyLeafFragment($currentBlock['headers'], $papText);
                        $this->appendExcelHierarchyLeafFragment($currentHeaders, $papText);
                    }
                }

                if ($officeId !== null) {
                    $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);

                    $currentBlock['office_rows'][] = [
                        'office_id' => $officeId,
                        'office_name' => $locationName,
                        'targets' => $targetValues,
                    ];

                    if ($this->isParentOfficeRow($locationName)) {
                        $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                    }
                }

                continue;
            }

            if ($currentBlock === null && $indicatorText !== '' && $officeId !== null && $papText === '') {
                $currentBlock = [
                    'source_row' => $rowNumber,
                    'title' => $currentProgram ?: $this->excelConfig('default_title'),
                    'program_hierarchy' => $currentProgramHierarchy,
                    'program' => $currentProgramMeta[0] ?? 'N/A',
                    'project' => $currentProgramMeta[1] ?? 'N/A',
                    'headers' => $currentHeaders,
                    'activity_parts' => [],
                    'indicator_parts' => [$indicatorText],
                    'car_totals' => [],
                    'group_totals' => [],
                    'office_rows' => [],
                ];

                $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);

                if ($officeId !== null) {
                    $currentBlock['office_rows'][] = [
                        'office_id' => $officeId,
                        'office_name' => $locationName,
                        'targets' => $targetValues,
                    ];

                    if ($this->isParentOfficeRow($locationName)) {
                        $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                    }
                }

                continue;
            }

            if ($currentBlock === null && $indicatorText !== '' && $officeId !== null && $papText !== '' && ! empty($currentHeaders) && $headingDepth === null) {
                $currentBlock = [
                    'source_row' => $rowNumber,
                    'title' => $currentProgram ?: $this->excelConfig('default_title'),
                    'program_hierarchy' => $currentProgramHierarchy,
                    'program' => $currentProgramMeta[0] ?? 'N/A',
                    'project' => $currentProgramMeta[1] ?? 'N/A',
                    'headers' => $currentHeaders,
                    'activity_parts' => [$papText],
                    'merge_text_only_rows' => true,
                    'indicator_parts' => [$indicatorText],
                    'car_totals' => [],
                    'group_totals' => [],
                    'office_rows' => [],
                ];

                $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);

                if ($officeId !== null) {
                    $currentBlock['office_rows'][] = [
                        'office_id' => $officeId,
                        'office_name' => $locationName,
                        'targets' => $targetValues,
                    ];

                    if ($this->isParentOfficeRow($locationName)) {
                        $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                    }
                }

                continue;
            }

            if ($currentBlock !== null && $isCarRow && empty($currentBlock['car_totals']) && $hasTargets) {
                if ($indicatorText !== '') {
                    $currentBlock['indicator_parts'][] = $indicatorText;
                }
                $currentBlock['car_totals'] = $targetValues;

                continue;
            }

            if ($indicatorText !== '' && $isCarRow) {
                $flushBlock();
                $currentBlock = [
                    'source_row' => $rowNumber,
                    'title' => $currentProgram ?: $this->excelConfig('default_title'),
                    'program_hierarchy' => $currentProgramHierarchy,
                    'program' => $currentProgramMeta[0] ?? 'N/A',
                    'project' => $currentProgramMeta[1] ?? 'N/A',
                    'headers' => $currentHeaders,
                    'activity_parts' => $papText !== '' ? [$papText] : [],
                    'merge_text_only_rows' => $this->excelMergesActivityContinuationRows() && $papText !== '',
                    'duplicate_leaf' => $papText === '' && ! empty($currentHeaders),
                    'indicator_parts' => [$indicatorText],
                    'car_totals' => $targetValues,
                    'group_totals' => [],
                    'office_rows' => [],
                ];

                continue;
            }

            if (
                $currentBlock !== null
                && ! empty($currentBlock['merge_text_only_rows'])
                && $papText !== ''
                && $indicatorText === ''
                && ! $hasTargets
                && $this->isExcelContinuationText($papText)
            ) {
                $currentBlock['activity_parts'][] = $papText;

                continue;
            }

            if (
                $currentBlock !== null
                && $papText !== ''
                && $indicatorText === ''
                && ! $hasTargets
                && $this->isExcelContinuationText($papText)
                && ! $this->isOfficeBackedExcelLabel($locationName, $officeId)
                && preg_match('/^(?!\d+\.\s|[A-Z]\.\s).+/u', $papText) === 1
            ) {
                $this->appendExcelHierarchyLeafFragment($currentBlock['headers'], $papText);
                $this->appendExcelHierarchyLeafFragment($currentHeaders, $papText);

                continue;
            }

            if ($papText !== '' && $indicatorText === '' && ! $hasTargets) {
                if (
                    empty($currentHeaders)
                    && $headingDepth === null
                    && ! $this->isOfficeBackedExcelLabel($locationName, $officeId)
                    && ! $this->isStandaloneExcelTitleHeader($papText)
                    && (
                        ! $this->excelLimitsProgramTitleContinuations()
                        || $lastProgramHeaderRow === null
                        || $rowNumber <= $lastProgramHeaderRow + 1
                    )
                ) {
                    $flushBlock();
                    $currentProgram = $currentProgram === null
                        ? $papText
                        : $this->joinExcelFragments([$currentProgram, $papText]);
                    $currentProgramMeta = [];
                    $standaloneHeaderContext = null;

                    continue;
                }

                if (
                    $this->isStandaloneExcelTitleHeader($papText)
                    && $this->isOfficeBackedExcelLabel($locationName, $officeId)
                ) {
                    $flushBlock();
                    $currentHeaders = [$papText];
                    $standaloneHeaderContext = $papText;

                    continue;
                }

                if (
                    $currentBlock !== null
                    && ! empty($currentBlock['placeholder_indicator'])
                    && $this->isOfficeBackedExcelLabel($locationName, $officeId)
                    && $headingDepth === null
                    && ! $this->isStandaloneExcelTitleHeader($papText)
                ) {
                    $this->appendExcelHierarchyLeafFragment($currentBlock['headers'], $papText);
                    $this->appendExcelHierarchyLeafFragment($currentHeaders, $papText);

                    if ($officeId !== null) {
                        $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);

                        $currentBlock['office_rows'][] = [
                            'office_id' => $officeId,
                            'office_name' => $locationName,
                            'targets' => $targetValues,
                        ];

                        if ($this->isParentOfficeRow($locationName)) {
                            $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                        }
                    }

                    continue;
                }

                $flushBlock();
                if ($currentProgram !== null && $this->shouldAppendToProgramTitle($currentProgram, $papText)) {
                    $currentProgram = $this->joinExcelFragments([$currentProgram, $papText]);

                    continue;
                }
                if (
                    $currentProgram !== null
                    && empty($currentHeaders)
                    && $headingDepth === null
                    && ! $this->isStandaloneExcelTitleHeader($papText)
                    && $this->shouldStoreProgramSupplement($papText, true)
                ) {
                    if (count($currentProgramMeta) < 2) {
                        $currentProgramMeta[] = $papText;
                    } else {
                        $lastIndex = count($currentProgramMeta) - 1;
                        $currentProgramMeta[$lastIndex] = $this->joinExcelFragments([$currentProgramMeta[$lastIndex], $papText]);
                    }

                    continue;
                }

                if ($this->isOfficeBackedExcelLabel($locationName, $officeId) && ($headingDepth !== null || strcasecmp($locationName, 'CAR') === 0)) {
                    if ($headingDepth !== null) {
                        if ($standaloneHeaderContext !== null && ! $this->isStandaloneExcelTitleHeader((string) ($currentHeaders[0] ?? ''))) {
                            $currentHeaders = [$standaloneHeaderContext];
                        }
                        $this->pushExcelHierarchyHeader($currentHeaders, $papText);
                    } elseif ($this->isStandaloneExcelTitleHeader($papText)) {
                        $currentHeaders = [$papText];
                        $standaloneHeaderContext = $papText;

                        continue;
                    } else {
                        $this->pushExcelOfficeBackedHierarchyLabel($currentHeaders, $papText);
                    }
                    $startPlaceholderPreviewBlock($rowNumber, $targetValues);

                    if ($currentBlock !== null && $officeId !== null) {
                        $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);

                        $currentBlock['office_rows'][] = [
                            'office_id' => $officeId,
                            'office_name' => $locationName,
                            'targets' => $targetValues,
                        ];

                        if ($this->isParentOfficeRow($locationName)) {
                            $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                        }
                    }

                    continue;
                }

                if ($this->isOfficeBackedExcelLabel($locationName, $officeId) && ! empty($currentHeaders)) {
                    $this->appendExcelHierarchyLeafFragment($currentHeaders, $papText);

                    continue;
                }

                $this->pushExcelHierarchyHeader($currentHeaders, $papText);

                continue;
            }

            if ($currentBlock !== null && $this->shouldAppendPerformanceIndicatorFragment($currentBlock, $indicatorText, $officeId)) {
                $currentBlock['indicator_parts'][] = $indicatorText;
            }

            if ($currentBlock !== null && $papText !== '') {
                if (! empty($currentBlock['merge_text_only_rows']) && $this->isExcelContinuationText($papText)) {
                    $currentBlock['activity_parts'][] = $papText;
                } elseif ($this->isExcelContinuationText($papText)) {
                    $this->appendExcelHierarchyLeafFragment($currentBlock['headers'], $papText);
                    $this->appendExcelHierarchyLeafFragment($currentHeaders, $papText);
                } else {
                    $currentBlock['activity_parts'][] = $papText;
                }
            }

            if ($currentBlock !== null && $officeId !== null) {
                $this->applyRoAsCarTotal($currentBlock, $locationName, $targetValues);

                $currentBlock['office_rows'][] = [
                    'office_id' => $officeId,
                    'office_name' => $locationName,
                    'targets' => $targetValues,
                ];

                if ($this->isParentOfficeRow($locationName)) {
                    $currentBlock['group_totals']['group-'.(count($currentBlock['group_totals']) + 1)] = $targetValues;
                }

                continue;
            }

            $skipped++;
        }

        $flushBlock();

        return [
            'year' => $year,
            'imported' => $imported,
            'skipped' => $skipped,
            'parsed_rows' => $totalParsedRows,
            'shown_rows' => count($previewRows),
            'placeholders' => $placeholders,
            'rows' => $previewRows,
            'warnings' => array_slice($warnings, 0, 80),
            'warning_count' => count($warnings),
        ];
    }

    private function previewExcelSortingWarnings(string $filePath, string $sheetName): array
    {
        $cacheKey = $filePath.'|'.$sheetName;
        if (array_key_exists($cacheKey, $this->excelSortingWarningCache)) {
            return $this->excelSortingWarningCache[$cacheKey];
        }
        $reader = new SimpleXlsxReader;
        $warnings = [];
        $currentProgram = $sheetName;
        $currentProgramHierarchy = [];
        $currentRoman = '';
        $seenNumericRoots = [];
        $lastSequences = [];

        $excelImportStarted = $this->excelStartMarkers() === [];

        foreach ($reader->rowsWithStyles($filePath, $sheetName, true) as $rowNumber => $styledRow) {
            $row = $this->normalizeExcelCoreColumns($styledRow['values'] ?? []);
            $rowStyles = $this->normalizeExcelCoreColumns($styledRow['styles'] ?? []);
            $papText = $this->cleanExcelText($row['A'] ?? '');

            if (! $excelImportStarted) {
                if (! $this->shouldStartExcelImportAtRow($row)) {
                    continue;
                }

                $excelImportStarted = true;
            }

            if ($this->shouldStopExcelImportAtRow($row)) {
                break;
            }

            if ($rowNumber < $this->excelDataStartRow) {
                continue;
            }

            $explicitHeading = $this->cleanExcelText($row['B'] ?? '') === ''
                && $this->cleanExcelText($row['C'] ?? '') === ''
                ? ExcelProgramHierarchy::heading($papText)
                : null;
            $explicitValues = $explicitHeading ?? ($row['__program_hierarchy'] ?? []);
            if ($explicitValues !== []) {
                $context = $currentProgramHierarchy ?: ['title' => $currentProgram];
                $nextHierarchy = ExcelProgramHierarchy::merge($context, $explicitValues, $explicitHeading !== null, false);
                if ($nextHierarchy !== $currentProgramHierarchy || $explicitHeading !== null) {
                    $currentProgramHierarchy = $nextHierarchy;
                    $currentProgram = json_encode($nextHierarchy);
                    $currentRoman = '';
                    $seenNumericRoots = [];
                    $lastSequences = [];
                }
                if ($explicitHeading !== null) {
                    continue;
                }
            }
            if ($papText === '') {
                continue;
            }

            $isStyledPapHeader = $this->isStyledStoPapHeaderRow($papText, $row, $rowStyles);
            if ($currentProgramHierarchy === [] && ($this->isStoProgramHeader($papText, $row) || $isStyledPapHeader)) {
                $currentProgram = $isStyledPapHeader && $currentProgram !== $sheetName && ! $this->isStoSheetHeaderText($currentProgram)
                    ? $this->joinExcelFragments([$currentProgram, $papText])
                    : $papText;
                $currentRoman = '';
                $seenNumericRoots = [];
                $lastSequences = [];

                continue;
            }

            $heading = $this->excelHeadingPreviewInfo($papText);
            if ($heading === null) {
                continue;
            }

            if ($heading['type'] === 'root_section') {
                $currentRoman = '';
                $seenNumericRoots = [];
                $lastSequences = [];

                continue;
            }

            if ($heading['type'] === 'menu') {
                $currentRoman = 'menu:'.$heading['sequence'];
                $seenNumericRoots = [];
                $lastSequences = [];
                $seenNumericRoots[$currentProgram.'|'.$currentRoman.'|number:'.$heading['sequence']] = true;

                continue;
            }

            if ($heading['type'] === 'roman') {
                $key = $currentProgram.'|roman';
                $last = $lastSequences[$key] ?? null;
                if ($last !== null && $heading['sequence'] > $last + 1) {
                    $warnings[] = [
                        'row' => $rowNumber,
                        'level' => 'warning',
                        'message' => "Roman sorting jumps from {$this->integerToRoman($last)}. to {$heading['label']}. Check if a section is missing.",
                    ];
                }

                $lastSequences[$key] = max($last ?? 0, $heading['sequence']);
                $currentRoman = 'roman:'.$heading['sequence'];
                $seenNumericRoots = [];

                continue;
            }

            $context = $currentProgram.'|'.$currentRoman;

            if ($heading['type'] === 'number') {
                $segments = $heading['segments'];
                $depth = count($segments);

                if ($depth === 1) {
                    $key = $context.'|number-root';
                    $last = $lastSequences[$key] ?? null;
                    if ($last !== null && $heading['sequence'] > $last + 1) {
                        $warnings[] = [
                            'row' => $rowNumber,
                            'level' => 'warning',
                            'message' => "Number sorting jumps from {$last}. to {$heading['label']}. Check if a numbered row is missing or mistyped.",
                        ];
                    }

                    $lastSequences[$key] = max($last ?? 0, $heading['sequence']);
                    $seenNumericRoots[$context.'|number:'.$segments[0]] = true;

                    continue;
                }

                $rootKey = $context.'|number:'.$segments[0];
                if (empty($seenNumericRoots[$rootKey])) {
                    $warnings[] = [
                        'row' => $rowNumber,
                        'level' => 'danger',
                        'message' => "{$heading['label']} appears without {$segments[0]}. in this section. It may be attached under the wrong green header.",
                    ];
                }

                $parentSegments = array_slice($segments, 0, -1);
                $key = $context.'|number:'.implode('.', $parentSegments);
                $last = $lastSequences[$key] ?? null;
                if ($last !== null && $heading['sequence'] > $last + 1) {
                    $parentLabel = implode('.', $parentSegments);
                    $warnings[] = [
                        'row' => $rowNumber,
                        'level' => 'warning',
                        'message' => "Number sorting under {$parentLabel} jumps from {$last} to {$heading['sequence']}.",
                    ];
                }

                $lastSequences[$key] = max($last ?? 0, $heading['sequence']);

                continue;
            }

            if ($heading['type'] === 'letter') {
                $key = $context.'|letter-root';
                $last = $lastSequences[$key] ?? null;
                if ($last !== null && $heading['sequence'] > $last + 1) {
                    $warnings[] = [
                        'row' => $rowNumber,
                        'level' => 'warning',
                        'message' => 'Letter sorting jumps from '.chr(64 + $last).". to {$heading['label']}. Check if a letter row is missing.",
                    ];
                }

                $lastSequences[$key] = max($last ?? 0, $heading['sequence']);
            }
        }

        return $this->excelSortingWarningCache[$cacheKey] = $warnings;
    }

    private function excelHeadingPreviewInfo(string $text): ?array
    {
        $heading = $this->excelHeadingInfo($text);

        if ($heading === null) {
            return null;
        }

        return [
            'type' => in_array($heading['type'], ['upper_letter', 'lower_letter'], true) ? 'letter' : $heading['type'],
            'label' => $heading['label'],
            'segments' => $heading['segments'],
            'sequence' => $heading['sequence'],
            'depth' => $heading['depth'],
        ];
    }

    private function integerToRoman(int $number): string
    {
        $map = [
            'M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1,
        ];
        $result = '';

        foreach ($map as $roman => $value) {
            while ($number >= $value) {
                $result .= $roman;
                $number -= $value;
            }
        }

        return $result;
    }

    private function upsertImportedStoPhysicalEntry(int $programId, int $rowId, int $indicatorId, ?int $officeId, int $year, array $values, array $carTotals = [], array $groupTotals = [], string $importType = 'target'): void
    {
        $model = $importType === 'accomplishment'
            ? PhysicalAccomplishment::class
            : PhysicalTarget::class;
        $entry = $model::firstOrNew([
            'sector' => $this->excelConfig('sector'),
            'year' => $year,
            'office_id' => $officeId,
            'row_id' => $rowId,
            'indicator_id' => $indicatorId,
        ]);
        $entry->user_id = Auth::id();
        $entry->program_id = $programId;

        foreach ($values as $key => $value) {
            $entry->{$key} = $value;
        }

        $entry->car_totals = $carTotals;
        $entry->group_totals = $groupTotals;
        $entry->imported_from = 'excel';
        $entry->save();
    }

    private function upsertImportedStoFinancialTarget(int $programId, int $rowId, int $indicatorId, ?int $officeId, int $year, array $values, array $carTotals = [], array $groupTotals = []): void
    {
        $entry = FinancialTarget::firstOrNew([
            'sector' => $this->excelConfig('sector'),
            'year' => $year,
            'office_id' => $officeId,
            'row_id' => $rowId,
            'indicator_id' => $indicatorId,
        ]);
        $entry->user_id = Auth::id();
        $entry->program_id = $programId;

        foreach ($this->targetValueKeys() as $key) {
            $entry->{$key} = $values[$key] ?? 0;
        }

        $entry->car_totals = $carTotals;
        $entry->group_totals = $groupTotals;
        $entry->save();
    }

    private function upsertImportedStoFinancialAccomplishment(int $programId, int $rowId, int $indicatorId, ?int $officeId, int $year, array $values, array $carTotals = [], array $groupTotals = []): void
    {
        $entry = FinancialAccomplishment::firstOrNew([
            'sector' => $this->excelConfig('sector'),
            'year' => $year,
            'office_id' => $officeId,
            'row_id' => $rowId,
            'indicator_id' => $indicatorId,
        ]);
        $entry->user_id = Auth::id();
        $entry->program_id = $programId;

        foreach ($this->targetValueKeys() as $key) {
            $entry->{$key} = $values[$key] ?? 0;
        }

        $entry->car_totals = $carTotals;
        $entry->group_totals = $groupTotals;
        $entry->save();
    }

    private function sumImportedCarTotalsForBlock(array $block): array
    {
        $sumRows = [];

        if (! empty($block['ro_totals'])) {
            $sumRows[] = $block['ro_totals'];
        }

        foreach ($block['group_totals'] ?? [] as $groupTotals) {
            $sumRows[] = $groupTotals;
        }

        if (empty($sumRows) && ! empty($block['car_totals'])) {
            $sumRows[] = $block['car_totals'];
        }

        return $this->sumTargetValueRows($sumRows);
    }

    private function sumImportedFinancialCarTotalsForBlock(array $block, string $valuesKey = 'financial_targets'): array
    {
        $sumRows = [];

        foreach ($block['office_rows'] ?? [] as $officeRow) {
            $officeName = (string) ($officeRow['office_name'] ?? '');
            if ($this->isRoOfficeRow($officeName) || $this->isParentOfficeRow($officeName)) {
                $sumRows[] = $officeRow[$valuesKey] ?? [];
            }
        }

        if (empty($sumRows)) {
            foreach ($block['office_rows'] ?? [] as $officeRow) {
                $sumRows[] = $officeRow[$valuesKey] ?? [];
            }
        }

        return $this->sumTargetValueRows($sumRows);
    }

    private function financialGroupTotalsFromOfficeRows(array $block, string $valuesKey = 'financial_targets'): array
    {
        $groupTotals = [];

        foreach ($block['office_rows'] ?? [] as $officeRow) {
            if (! $this->isParentOfficeRow((string) ($officeRow['office_name'] ?? ''))) {
                continue;
            }

            $groupTotals['group-'.(count($groupTotals) + 1)] = $officeRow[$valuesKey] ?? [];
        }

        return $groupTotals;
    }

    private function sumTargetValueRows(array $rows): array
    {
        $keys = $this->targetValueKeys();

        $totals = array_fill_keys($keys, 0.0);

        foreach ($rows as $row) {
            foreach ($keys as $key) {
                $totals[$key] += $this->excelNumber($row[$key] ?? 0);
            }
        }

        return $totals;
    }

    /** @return array<int, string> */
    private function targetValueKeys(): array
    {
        return [
            'jan', 'feb', 'mar', 'q1',
            'apr', 'may', 'jun', 'q2',
            'jul', 'aug', 'sep', 'q3',
            'oct', 'nov', 'dec', 'q4',
            'annual_total',
        ];
    }

    private function normalizeImportedOfficeRowsForExcelBlock(array $block): array
    {
        $rowsByOffice = [];

        foreach ($block['office_rows'] ?? [] as $officeRow) {
            $officeId = (int) ($officeRow['office_id'] ?? 0);
            if ($officeId <= 0) {
                continue;
            }

            $officeName = (string) ($officeRow['office_name'] ?? '');
            if ($this->isExcelNaValue($officeName)) {
                continue;
            }
            $normalizedOfficeName = $this->normalizeImportedOfficeName($officeName);
            $isPenroRow = $normalizedOfficeName === 'PENRO';

            $hasTargets = $this->hasAnyTargetValue($officeRow['targets'] ?? []);
            $existing = $rowsByOffice[$officeId] ?? null;

            if ($existing === null) {
                $rowsByOffice[$officeId] = $officeRow;

                continue;
            }

            $existingName = $this->normalizeImportedOfficeName((string) ($existing['office_name'] ?? ''));
            $existingIsPenroRow = $existingName === 'PENRO';
            $existingHasTargets = $this->hasAnyTargetValue($existing['targets'] ?? []);

            if ($isPenroRow && ! $existingIsPenroRow) {
                $rowsByOffice[$officeId] = $officeRow;

                continue;
            }

            if ($existingIsPenroRow && ! $isPenroRow) {
                continue;
            }

            if (! $existingHasTargets && $hasTargets) {
                $rowsByOffice[$officeId] = $officeRow;
            }
        }

        $block['office_rows'] = array_values($rowsByOffice);

        return $block;
    }

    private function papDataFromExcelBlock(array $block, int $year): array
    {
        $pap = $this->legacyPapDataFromExcelBlock($block, $year);
        $explicit = $block['program_hierarchy'] ?? [];
        if ($explicit !== []) {
            $explicit = ExcelProgramHierarchy::merge([], $explicit);
            $pap['title'] = $explicit['title'] ?? $pap['title'];
            $pap['program'] = $explicit['program'] ?? 'N/A';
            $pap['project'] = 'N/A';
        }
        foreach (array_slice(ExcelProgramHierarchy::FIELDS, 2) as $field) {
            $pap[$field] = $explicit[$field] ?? null;
        }

        return $pap;
    }

    private function legacyPapDataFromExcelBlock(array $block, int $year): array
    {
        $headers = array_values(array_filter($block['headers'] ?? []));
        $activityName = $this->joinExcelFragments($block['activity_parts'] ?? []);

        if (! empty($block['standalone_title']) && ! empty($headers)) {
            $standaloneTitle = array_shift($headers);

            return [
                'title' => $this->excelPpaName($standaloneTitle ?: ($block['title'] ?: $this->excelConfig('default_title'))),
                'program' => $this->excelPpaName('N/A'),
                'project' => $this->excelPpaName('N/A'),
                'activities' => $this->excelPpaName($headers[0] ?? ($activityName ?: 'N/A')),
                'subactivities' => $this->excelPpaName($headers[1] ?? null),
                'subsubactivities' => $this->excelPpaName($headers[2] ?? null),
                'level_6' => $this->excelPpaName($headers[3] ?? null),
                'level_7' => $this->excelPpaName($headers[4] ?? null),
                'level_8' => $this->excelPpaName($headers[5] ?? null),
                'year' => $year,
                'duplicate_leaf' => false,
            ];
        }

        if ($this->isPsRequirementsChild((string) ($headers[0] ?? $activityName))) {
            return [
                'title' => $this->excelPpaName('PS Requirements'),
                'program' => $this->excelPpaName('N/A'),
                'project' => $this->excelPpaName('N/A'),
                'activities' => $this->excelPpaName($headers[0] ?? ($activityName ?: 'N/A')),
                'subactivities' => $this->excelPpaName($headers[1] ?? null),
                'subsubactivities' => $this->excelPpaName($headers[2] ?? null),
                'level_6' => $this->excelPpaName($headers[3] ?? null),
                'level_7' => $this->excelPpaName($headers[4] ?? null),
                'level_8' => $this->excelPpaName($headers[5] ?? null),
                'year' => $year,
                'duplicate_leaf' => false,
            ];
        }

        $activities = $headers[0] ?? ($activityName ?: 'N/A');
        $subactivities = isset($headers[0]) ? ($headers[1] ?? ($activityName ?: null)) : null;
        $subsubactivities = isset($headers[1]) ? ($headers[2] ?? ($activityName ?: null)) : null;
        $level6 = isset($headers[2]) ? ($headers[3] ?? ($activityName ?: null)) : null;
        $level7 = isset($headers[3]) ? ($headers[4] ?? ($activityName ?: null)) : null;
        $level8 = isset($headers[4]) ? ($headers[5] ?? ($activityName ?: null)) : null;

        return [
            'title' => $this->excelPpaName($block['title'] ?: $this->excelConfig('default_title')),
            'program' => $this->excelPpaName($block['program'] ?? 'N/A'),
            'project' => $this->excelPpaName($block['project'] ?? 'N/A'),
            'activities' => $this->excelPpaName($activities),
            'subactivities' => $this->excelPpaName($subactivities),
            'subsubactivities' => $this->excelPpaName($subsubactivities),
            'level_6' => $this->excelPpaName($level6),
            'level_7' => $this->excelPpaName($level7),
            'level_8' => $this->excelPpaName($level8),
            'year' => $year,
            'duplicate_leaf' => false,
        ];
    }

    private function resolveImportedIndicatorTargetRowId(int $rowId, string $indicatorName, int $indicatorId): int
    {
        $existingRow = DB::table('ppa')->where('id', $rowId)->first();
        if (! $existingRow || empty($existingRow->indicator_id)) {
            return $rowId;
        }

        $normalizedIndicatorName = mb_strtolower(trim($indicatorName));
        if ($normalizedIndicatorName === '') {
            $currentIndicatorName = DB::table('indicators')
                ->where('id', $existingRow->indicator_id)
                ->value('name');

            if ($this->isExcelNaValue((string) $currentIndicatorName)) {
                PhysicalTarget::query()
                    ->where('row_id', $rowId)
                    ->where('indicator_id', $existingRow->indicator_id)
                    ->update(['indicator_id' => $indicatorId]);
                PhysicalAccomplishment::query()
                    ->where('row_id', $rowId)
                    ->where('indicator_id', $existingRow->indicator_id)
                    ->update(['indicator_id' => $indicatorId]);
                FinancialTarget::query()
                    ->where('row_id', $rowId)
                    ->where('indicator_id', $existingRow->indicator_id)
                    ->update(['indicator_id' => $indicatorId]);
                FinancialAccomplishment::query()
                    ->where('row_id', $rowId)
                    ->where('indicator_id', $existingRow->indicator_id)
                    ->update(['indicator_id' => $indicatorId]);

                return $rowId;
            }
        }

        $matchingRowId = DB::table('ppa as candidate')
            ->join('indicators as candidate_indicator', 'candidate_indicator.id', '=', 'candidate.indicator_id')
            ->where('candidate.ppa_details_id', $existingRow->ppa_details_id)
            ->whereRaw('LOWER(TRIM('.DB::connection()->getQueryGrammar()->wrap('candidate_indicator.name').')) = ?', [$normalizedIndicatorName])
            ->orderBy('candidate.id')
            ->value('candidate.id');

        if ($matchingRowId !== null) {
            return (int) $matchingRowId;
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

    private function resetImportedPhysicalEntries(int $year, string $importType, string $sector): array
    {
        $model = $importType === 'accomplishment'
            ? PhysicalAccomplishment::class
            : PhysicalTarget::class;
        $query = $model::query()
            ->where('sector', $sector)
            ->where('year', $year)
            ->where('imported_from', 'excel');
        $rowIds = (clone $query)
            ->pluck('row_id')
            ->map(fn ($rowId) => (int) $rowId)
            ->filter(fn ($rowId) => $rowId > 0)
            ->unique()
            ->values()
            ->all();

        $query->delete();

        return $rowIds;
    }

    private function deleteObsoleteImportedPpaRows(array $staleRowIds, array $importedRowIds): void
    {
        $obsoleteRowIds = array_values(array_diff(
            array_unique(array_map('intval', $staleRowIds)),
            array_unique(array_map('intval', $importedRowIds))
        ));
        if (empty($obsoleteRowIds)) {
            return;
        }

        $referencedRowIds = PhysicalTarget::query()
            ->whereIn('row_id', $obsoleteRowIds)
            ->pluck('row_id')
            ->merge(PhysicalAccomplishment::query()->whereIn('row_id', $obsoleteRowIds)->pluck('row_id'))
            ->map(fn ($rowId) => (int) $rowId)
            ->unique()
            ->all();
        $deletableRowIds = array_values(array_diff($obsoleteRowIds, $referencedRowIds));

        if (! empty($deletableRowIds)) {
            DB::table('ppa')->whereIn('id', $deletableRowIds)->delete();
        }
    }

    private function deleteEmptyNaLeafPpaRows(string $typeCode): void
    {
        $typeId = DB::table('types')->where('code', $typeCode)->value('id');
        if (! $typeId) {
            return;
        }

        $rowIds = DB::table('ppa as candidate')
            ->where('candidate.types_id', $typeId)
            ->whereNull('candidate.indicator_id')
            ->where(function ($query) {
                $query->whereNull('candidate.office_id')
                    ->orWhere('candidate.office_id', '')
                    ->orWhere('candidate.office_id', '[]');
            })
            ->whereRaw("UPPER(TRIM(".DB::connection()->getQueryGrammar()->wrap('candidate.name').")) IN ('N/A', 'NA')")
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('ppa_details as child_details')
                    ->join('ppa as child_ppa', 'child_ppa.ppa_details_id', '=', 'child_details.id')
                    ->whereColumn('child_details.parent_id', 'candidate.ppa_details_id');
            })
            ->pluck('candidate.id')
            ->map(fn ($rowId) => (int) $rowId)
            ->all();

        if (! empty($rowIds)) {
            DB::table('ppa')->whereIn('id', $rowIds)->delete();
        }
    }

    private function firstOrCreateImportedIndicator(string $indicatorName, ?int $indicatorTypeId = null)
    {
        $indicatorModel = $this->excelConfig('indicator_model');
        $indicator = $indicatorModel::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($indicatorName)])
            ->first();

        if ($indicator) {
            if (
                $indicatorTypeId !== null
                && $this->hasIndicatorColumn('indicator_type_id')
                && (empty($indicator->indicator_type_id) || (int) $indicator->indicator_type_id !== $indicatorTypeId)
            ) {
                $indicator->indicator_type_id = $indicatorTypeId;
                $indicator->save();
            }

            return $indicator;
        }

        $indicator = new $indicatorModel;
        $indicator->name = $indicatorName;
        if ($this->hasIndicatorColumn('user_id')) {
            $indicator->user_id = Auth::id();
        }
        if ($indicatorTypeId !== null && $this->hasIndicatorColumn('indicator_type_id')) {
            $indicator->indicator_type_id = $indicatorTypeId;
        }
        $indicator->save();

        return $indicator;
    }

    private function inferIndicatorTypeIdFromExcelBlock(array $block): ?int
    {
        $targetRows = collect($block['office_rows'] ?? [])
            ->pluck('targets')
            ->filter(fn ($values) => is_array($values) && $this->hasAnyTargetValue($values))
            ->values()
            ->all();

        if (isset($block['car_totals']) && is_array($block['car_totals']) && $this->hasAnyTargetValue($block['car_totals'])) {
            array_unshift($targetRows, $block['car_totals']);
        }

        $typeName = $this->inferIndicatorTypeNameFromExcelRows($targetRows)
            ?? $this->inferIndicatorTypeNameFromOfficeHierarchy($block['office_rows'] ?? []);

        return $typeName ? $this->indicatorTypeIdByName($typeName) : null;
    }

    private function inferIndicatorTypeNameFromOfficeHierarchy(array $officeRows): ?string
    {
        $rowsByOfficeId = collect($officeRows)
            ->filter(fn ($row) => isset($row['office_id'], $row['targets']) && is_array($row['targets']))
            ->groupBy(fn ($row) => (int) $row['office_id']);

        $parentChildOfficeIds = $this->provinceChildOfficeIdMap();
        if (empty($parentChildOfficeIds)) {
            return null;
        }

        $periodKeys = [
            'jan', 'feb', 'mar', 'q1',
            'apr', 'may', 'jun', 'q2',
            'jul', 'aug', 'sep', 'q3',
            'oct', 'nov', 'dec', 'q4',
            'annual_total',
        ];

        $sumMatches = 0;
        $maxMatches = 0;

        foreach ($parentChildOfficeIds as $parentOfficeId => $childOfficeIds) {
            $parentRow = $rowsByOfficeId->get((int) $parentOfficeId, collect())->first();
            if (! $parentRow) {
                continue;
            }

            $childRows = collect($childOfficeIds)
                ->flatMap(fn ($childOfficeId) => $rowsByOfficeId->get((int) $childOfficeId, collect()))
                ->values();

            if ($childRows->isEmpty()) {
                continue;
            }

            foreach ($periodKeys as $periodKey) {
                $parentValue = $this->excelNumber($parentRow['targets'][$periodKey] ?? 0);
                $childValues = $childRows
                    ->map(fn ($row) => $this->excelNumber($row['targets'][$periodKey] ?? 0))
                    ->values()
                    ->all();

                $sum = array_sum($childValues);
                $max = empty($childValues) ? 0.0 : max($childValues);

                if ($parentValue === 0.0 && $sum === 0.0) {
                    continue;
                }

                if (abs($sum - $max) < 0.00001) {
                    continue;
                }

                if (abs($parentValue - $sum) < 0.00001) {
                    $sumMatches++;
                }

                if (abs($parentValue - $max) < 0.00001) {
                    $maxMatches++;
                }
            }
        }

        if ($maxMatches > $sumMatches) {
            return 'non-cumulative';
        }

        if ($sumMatches > $maxMatches) {
            return 'cumulative';
        }

        return null;
    }

    private function provinceChildOfficeIdMap(): array
    {
        static $map = null;

        if ($map !== null) {
            return $map;
        }

        $map = [];
        foreach (Office::groupedForUi() as $parent) {
            $childIds = collect($parent->children ?? [])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all();

            if (! empty($childIds)) {
                $map[(int) $parent->id] = $childIds;
            }
        }

        return $map;
    }

    private function inferIndicatorTypeNameFromExcelRows(array $targetRows): ?string
    {
        $sumMatches = 0;
        $maxMatches = 0;
        $semiMatches = 0;
        $multiQuarterMatches = 0;
        $quarterGroups = [
            [['jan', 'feb', 'mar'], 'q1'],
            [['apr', 'may', 'jun'], 'q2'],
            [['jul', 'aug', 'sep'], 'q3'],
            [['oct', 'nov', 'dec'], 'q4'],
        ];

        foreach ($targetRows as $values) {
            $quarterValues = array_map(
                fn ($quarterKey) => $this->excelNumber($values[$quarterKey] ?? 0),
                ['q1', 'q2', 'q3', 'q4']
            );
            $annualTotal = $this->excelNumber($values['annual_total'] ?? 0);
            $maxQuarter = empty($quarterValues) ? 0.0 : max($quarterValues);
            $activeQuarterCount = collect($quarterGroups)
                ->filter(function ($group) use ($values) {
                    [$monthKeys, $quarterKey] = $group;
                    $quarter = $this->excelNumber($values[$quarterKey] ?? 0);
                    $monthTotal = array_sum(array_map(fn ($key) => $this->excelNumber($values[$key] ?? 0), $monthKeys));

                    return abs($quarter) >= 0.00001 || abs($monthTotal) >= 0.00001;
                })
                ->count();

            if ($activeQuarterCount === 1 && $annualTotal !== 0.0 && $maxQuarter !== 0.0 && abs($annualTotal - $maxQuarter) < 0.00001) {
                $semiMatches++;
            }

            if ($activeQuarterCount > 1) {
                $multiQuarterMatches++;
            }

            foreach ($quarterGroups as [$monthKeys, $quarterKey]) {
                $months = array_map(fn ($key) => $this->excelNumber($values[$key] ?? 0), $monthKeys);
                $quarter = $this->excelNumber($values[$quarterKey] ?? 0);
                $sum = array_sum($months);
                $max = max($months);

                if ($quarter === 0.0 && $sum === 0.0) {
                    continue;
                }

                if (abs($sum - $max) < 0.00001) {
                    continue;
                }

                if (abs($quarter - $sum) < 0.00001) {
                    $sumMatches++;
                }

                if (abs($quarter - $max) < 0.00001) {
                    $maxMatches++;
                }
            }
        }

        if ($maxMatches > $sumMatches) {
            return 'non-cumulative';
        }

        if ($sumMatches > 0) {
            return 'cumulative';
        }

        if ($multiQuarterMatches > 0) {
            return 'cumulative';
        }

        if ($semiMatches > 0) {
            return 'semi-cumulative';
        }

        return null;
    }

    private function indicatorTypeIdByName(string $name): ?int
    {
        $id = DB::table('indicator_types')
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
            ->value('id');

        return $id ? (int) $id : null;
    }

    private function joinExcelFragments(array $parts): string
    {
        $cleaned = collect($parts)
            ->map(fn ($part) => $this->cleanExcelText($part))
            ->filter()
            ->values()
            ->all();

        return trim(preg_replace('/\s+/', ' ', implode(' ', $cleaned)));
    }

    private function pushExcelHierarchyHeader(array &$headers, string $text): void
    {
        $heading = $this->excelHeadingInfo($text);
        $headingDepth = $heading['depth'] ?? null;
        $rootKind = $this->excelHeadingInfo((string) ($headers[0] ?? ''))['type'] ?? null;
        $parentKind = $this->excelHeadingInfo((string) ($headers[1] ?? ''))['type'] ?? null;

        if ($heading !== null) {
            if ($this->excelUsesOrderedHeadingDepths()) {
                $headerKinds = array_map(
                    fn (string $header): ?string => $this->excelHeadingInfo($header)['type'] ?? null,
                    $headers
                );
                $lastIndexOf = static function (array $needles) use ($headerKinds): ?int {
                    for ($index = count($headerKinds) - 1; $index >= 0; $index--) {
                        if (in_array($headerKinds[$index], $needles, true)) {
                            return $index;
                        }
                    }

                    return null;
                };

                $menuIndex = $lastIndexOf(['menu']);
                $menuHeading = $menuIndex !== null
                    ? $this->excelHeadingInfo((string) ($headers[$menuIndex] ?? ''))
                    : null;
                $menuSequence = $menuHeading['sequence'] ?? null;
                $anchoredMenuNumberIndex = null;
                if ($menuIndex !== null) {
                    for ($index = count($headers) - 1; $index > $menuIndex; $index--) {
                        $candidate = $this->excelHeadingInfo((string) ($headers[$index] ?? ''));
                        $segments = $candidate['segments'] ?? [];
                        if (
                            ($candidate['type'] ?? null) === 'number'
                            && count($segments) > 1
                            && (int) ($segments[0] ?? -1) === (int) $menuSequence
                        ) {
                            $anchoredMenuNumberIndex = $index;
                            break;
                        }
                    }
                }

                $numberDepth = function () use ($heading, $menuIndex, $menuSequence, $anchoredMenuNumberIndex, $lastIndexOf): int {
                    $segments = $heading['segments'] ?? [];
                    if ($menuIndex !== null) {
                        if (count($segments) > 1 && (int) ($segments[0] ?? -1) === (int) $menuSequence) {
                            return $menuIndex + count($segments);
                        }

                        if ($anchoredMenuNumberIndex !== null) {
                            return $anchoredMenuNumberIndex + 2;
                        }

                        return $menuIndex + 2;
                    }

                    $letterIndex = $lastIndexOf(['upper_letter', 'roman']);

                    if ($letterIndex === null && ($rootIndex = $lastIndexOf(['root_section'])) !== null) {
                        return $rootIndex + max(2, count($segments));
                    }

                    return $letterIndex !== null
                        ? $letterIndex + max(2, count($segments))
                        : max(1, count($segments));
                };

                $alphaNumberParentTypes = (($heading['letter_case'] ?? null) === 'lower')
                    ? ['lower_letter']
                    : ['upper_letter', 'roman'];
                $nestedLetterParentIndex = null;
                if ($heading['type'] === 'upper_letter' && $this->excelNestedLetterParentChildren() !== []) {
                    for ($index = count($headers) - 1; $index >= 0; $index--) {
                        $candidate = $this->excelHeadingInfo((string) ($headers[$index] ?? ''));
                        $candidateLabel = strtoupper(preg_replace('/[^A-Z0-9]+/', '', (string) ($candidate['label'] ?? '')));
                        $configuredChildren = collect($this->excelNestedLetterParentChildren())
                            ->first(function (array $children, string $prefix) use ($candidateLabel): bool {
                                return $candidateLabel === strtoupper(preg_replace('/[^A-Z0-9]+/', '', $prefix));
                            });
                        $isConfiguredParent = is_array($configuredChildren)
                            && in_array(strtoupper((string) ($heading['label'] ?? '')), array_map('strtoupper', $configuredChildren), true);

                        if (($candidate['type'] ?? null) === 'alpha_number' && $isConfiguredParent) {
                            $nestedLetterParentIndex = $index;
                            break;
                        }
                    }
                }

                $alphaNumberSuffixParentIndex = null;
                if ($heading['type'] === 'alpha_number' && ($heading['suffix_letter'] ?? null) !== null) {
                    for ($index = count($headers) - 1; $index >= 0; $index--) {
                        $candidate = $this->excelHeadingInfo((string) ($headers[$index] ?? ''));
                        if (
                            ($candidate['type'] ?? null) === 'alpha_number'
                            && ($candidate['suffix_letter'] ?? null) === null
                            && strtoupper((string) ($candidate['letter'] ?? '')) === strtoupper((string) ($heading['letter'] ?? ''))
                            && ($candidate['segments'] ?? []) === ($heading['segments'] ?? [])
                        ) {
                            $alphaNumberSuffixParentIndex = $index;
                            break;
                        }
                    }
                }

                $headingDepth = match ($heading['type']) {
                    'root_section', 'roman' => 1,
                    'menu' => (($rootIndex = $lastIndexOf(['root_section'])) !== null ? $rootIndex + 2 : 1),
                    'upper_letter' => ($nestedLetterParentIndex !== null
                        ? $nestedLetterParentIndex + 2
                        : (($romanIndex = $lastIndexOf(['roman'])) !== null ? $romanIndex + 2 : 1)),
                    'alpha_number' => ($alphaNumberSuffixParentIndex !== null
                        ? $alphaNumberSuffixParentIndex + 2
                        : (($letterIndex = $lastIndexOf($alphaNumberParentTypes)) !== null ? $letterIndex + 2 : 1)),
                    'number' => $numberDepth(),
                    'number_letter' => (($numberIndex = $lastIndexOf(['number', 'alpha_number'])) !== null
                        ? $numberIndex + 2
                        : (($menuIndex = $lastIndexOf(['menu'])) !== null ? $menuIndex + 2 : 2)),
                    'lower_letter' => ($this->excelKeepsLowerLetterSiblingsUnderRootNumber()
                        && ($headerKinds[0] ?? null) === 'number'
                        ? 2
                        : (! empty($headers) && end($headerKinds) === null
                        ? count($headers) + 1
                        : (($numberIndex = $lastIndexOf(['number', 'number_letter', 'alpha_number'])) !== null
                        ? $numberIndex + 2
                        : (($letterIndex = $lastIndexOf(['upper_letter', 'roman'])) !== null ? $letterIndex + 2 : 1)))),
                    default => $headingDepth,
                };
            } elseif ($heading['type'] === 'number' && in_array($rootKind, ['upper_letter', 'roman'], true)) {
                $segmentDepth = count($heading['segments'] ?? []);
                $headingDepth = $segmentDepth > 1 ? $segmentDepth + 1 : max(2, $headingDepth);
            } elseif (
                $heading['type'] === 'number'
                && $this->isStandaloneExcelTitleHeader((string) ($headers[0] ?? ''))
                && isset($headers[1])
            ) {
                $headingDepth = count($heading['segments'] ?? []) + 2;
            } elseif (
                $heading['type'] === 'lower_letter'
                && in_array($rootKind, ['upper_letter', 'roman'], true)
                && in_array($parentKind, ['number', 'alpha_number'], true)
            ) {
                $headingDepth = 3;
            } elseif (
                in_array($heading['type'], ['upper_letter', 'lower_letter'], true)
                && in_array($rootKind, ['number', 'roman'], true)
            ) {
                $headingDepth = 2;
            }
        }

        if ($headingDepth !== null) {
            $headers = array_slice($headers, 0, max(0, $headingDepth - 1));
            $headers[$headingDepth - 1] = $text;
            $headers = array_values($headers);

            return;
        }

        if (empty($headers)) {
            $headers[] = $text;

            return;
        }

        $lastIndex = count($headers) - 1;
        $headers[$lastIndex] = trim($headers[$lastIndex].' '.$text);
    }

    private function appendExcelHierarchyLeafFragment(array &$headers, string $text): void
    {
        $text = trim($text);

        if ($text === '') {
            return;
        }

        if (empty($headers)) {
            $headers[] = $text;

            return;
        }

        $lastIndex = count($headers) - 1;
        $headers[$lastIndex] = $this->joinExcelFragments([$headers[$lastIndex], $text]);
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

    private function applyRoAsCarTotal(array &$block, string $locationName, array $targetValues): void
    {
        if (! $this->isRoOfficeRow($locationName)) {
            return;
        }

        $block['ro_totals'] = $targetValues;

        if (empty($block['car_totals'])) {
            $block['car_totals'] = $targetValues;
        }
    }

    private function pushExcelOfficeBackedHierarchyLabel(array &$headers, string $text): void
    {
        if (empty($headers)) {
            $headers = [$text];

            return;
        }

        if ($this->excelUsesOrderedHeadingDepths() && count($headers) > 1) {
            $headers = array_values(array_merge(array_slice($headers, 0, -1), [$text]));

            return;
        }

        $headers = array_merge(array_slice($headers, 0, 1), [$text]);
    }

    private function isRoOfficeRow(string $locationName): bool
    {
        return $this->normalizeImportedOfficeName($locationName) === 'RO';
    }

    private function isOfficeBackedExcelLabel(string $locationName, ?int $officeId): bool
    {
        if ($officeId !== null) {
            return true;
        }

        $normalized = $this->normalizeImportedOfficeName($locationName);

        return in_array($normalized, [
            'CAR',
            'PENRO',
            'RO',
            'ABRA',
            'APAYAO',
            'BENGUET',
            'IFUGAO',
            'KALINGA',
            'MOUNTAINPROVINCE',
            'MTPROVINCE',
        ], true);
    }

    private function isStandaloneExcelTitleHeader(string $text): bool
    {
        $normalized = preg_replace('/[^A-Z0-9]+/', '', strtoupper(trim($text)));

        return in_array($normalized, [
            'PSREQUIREMENTS',
            'PMS',
        ], true);
    }

    private function isStandaloneExcelSectionHeader(string $text): bool
    {
        $normalized = preg_replace('/[^A-Z0-9]+/', '', strtoupper(trim($text)));

        return in_array($normalized, [
            'MANDATORIESANDIMPOSITIONS',
        ], true);
    }

    private function isPsRequirementsChild(string $text): bool
    {
        $normalized = preg_replace('/[^A-Z0-9]+/', '', strtoupper(trim($text)));
        $normalized = preg_replace('/^\d+/', '', $normalized);

        return in_array($normalized, [
            'PSREGULAR',
            'RLIP',
        ], true);
    }

    private function canonicalizeReplicatedPsRequirementsBlock(array $block): array
    {
        $hierarchyParts = array_merge(
            array_values(array_filter($block['headers'] ?? [])),
            array_values(array_filter($block['activity_parts'] ?? []))
        );

        $normalizedHierarchy = preg_replace(
            '/[^A-Z0-9]+/',
            '',
            strtoupper($this->joinExcelFragments($hierarchyParts))
        );

        $canonicalActivity = match (true) {
            preg_match('/^(?:PSREQUIREMENTS)?\d*PSREGULAR$/', $normalizedHierarchy) === 1 => '1.PS Regular',
            preg_match('/^(?:PSREQUIREMENTS)?\d*RLIP$/', $normalizedHierarchy) === 1 => '2.RLIP',
            default => null,
        };

        if ($canonicalActivity === null) {
            return $block;
        }

        $block['title'] = 'PS Requirements';
        $block['program'] = 'N/A';
        $block['project'] = 'N/A';
        $block['headers'] = [$canonicalActivity];
        $block['activity_parts'] = [];
        $block['standalone_title'] = false;

        return $block;
    }

    private function shouldSkipAllNaExcelPlaceholderBlock(array $block, string $indicatorName): bool
    {
        if (empty($block['placeholder_indicator']) || ! $this->isExcelNaValue($indicatorName)) {
            return false;
        }

        $headers = collect($block['headers'] ?? [])
            ->map(fn ($header) => $this->cleanExcelText($header))
            ->filter()
            ->values()
            ->all();

        if (! empty($block['standalone_title']) && ! empty($headers)) {
            array_shift($headers);
        }

        $activityName = $this->joinExcelFragments($block['activity_parts'] ?? []);
        $hierarchyParts = array_merge($headers, [$activityName]);

        return collect($hierarchyParts)
            ->filter(fn ($value) => ! $this->isExcelNaValue($value))
            ->isEmpty();
    }

    private function isExcelNaValue($value): bool
    {
        $normalized = strtoupper(trim((string) $value));

        return $normalized === '' || $normalized === 'N/A' || $normalized === 'NA';
    }

    private function isExcelSignatureFooterRow(string ...$cells): bool
    {
        $text = strtoupper($this->joinExcelFragments($cells));

        if ($text === '') {
            return false;
        }

        return preg_match('/\b(RECOMMENDING APPROVAL|APPROVED|PREPARED BY|SUBMITTED BY|CERTIFIED CORRECT|ENGR\.?|ATTY\.?|CESO|REGIONAL EXECUTIVE DIRECTOR)\b/u', $text) === 1;
    }

    private function shouldAppendToProgramTitle(string $currentProgram, string $text): bool
    {
        $currentProgram = trim($currentProgram);
        $text = trim($text);

        if ($currentProgram === '' || $text === '') {
            return false;
        }

        if (preg_match('/^\d+\.\s+/u', $text) === 1 || preg_match('/^(?![IVXLCDM]+\.\s)[A-Z]\.\s+/i', $text) === 1) {
            return false;
        }

        if (strtoupper($text) !== $text) {
            return false;
        }

        return preg_match('/\b(AND|OF|FOR|TO|IN|ON|WITH|INCLUDING|THROUGH|UNDER|FROM|THE|ITS|PROPERTY|MOTOR|VEHICLES|MAINTENANCE|INSURANCE)\s*$/i', $currentProgram) === 1;
    }

    private function shouldStoreProgramSupplement(string $text, bool $allowLeadingLetterHeading = false): bool
    {
        $text = trim($text);

        if ($text === '') {
            return false;
        }

        if ($allowLeadingLetterHeading && preg_match('/^(?![IVXLCDM]+\.\s)[A-Z]\.\s+/i', $text) === 1) {
            return true;
        }

        if ($this->excelHeadingDepth($text) !== null) {
            return false;
        }

        return true;
    }

    private function shouldAppendPerformanceIndicatorFragment(array $currentBlock, string $text, ?int $officeId): bool
    {
        return $this->isExcelContinuationText($text);
    }

    private function isExcelContinuationText(string $text): bool
    {
        $text = trim($text);

        return $text !== '' && $this->excelHeadingDepth($text) === null;
    }

    private function shouldMergeConstructionLeafFragment(?string $currentProgram, string $text): bool
    {
        $currentProgram = trim((string) $currentProgram);
        $text = trim($text);

        if ($currentProgram === '' || $text === '') {
            return false;
        }

        return preg_match('/CONSTRUCTION,\s*REPAIR AND MAINTENANCE AND INSURANCE OF PROPERTY INCLUDING MOTOR VEHICLES/i', $currentProgram) === 1
            && preg_match('/^1\.\s+Repair and Maintenance of Buildings and$/i', $text) === 1;
    }

    private function excelHeadingDepth(string $text): ?int
    {
        return $this->excelHeadingInfo($text)['depth'] ?? null;
    }

    private function excelHeadingInfo(string $text): ?array
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        $normalizedText = strtoupper(trim(preg_replace('/\s+/', ' ', $text)));
        if (collect($this->excelRootSectionMarkers())->contains(
            static fn (string $marker): bool => $normalizedText === strtoupper(trim(preg_replace('/\s+/', ' ', $marker)))
        )) {
            return [
                'type' => 'root_section',
                'label' => $text,
                'segments' => [],
                'sequence' => 0,
                'depth' => 1,
            ];
        }

        if (
            $this->excelUsesOrderedHeadingDepths()
            && preg_match('/^MENU\s+(\d+)\s*[:.]?/iu', $text, $matches)
        ) {
            return [
                'type' => 'menu',
                'label' => 'Menu '.$matches[1],
                'segments' => [(int) $matches[1]],
                'sequence' => (int) $matches[1],
                'depth' => 2,
            ];
        }

        if (
            $this->excelUsesCompactAlphaHeadings()
            && preg_match('/^([A-Za-z])\.?\s*(\d+(?:\.\d+)*)(?:\s*\.?\s*([a-z])(?=[.)]))?(?:[.)]\s*|(?=\s|$))/u', $text, $matches)
        ) {
            $numericSegments = array_map('intval', explode('.', $matches[2]));

            return [
                'type' => 'alpha_number',
                'label' => $matches[1].$matches[2].($matches[3] ?? ''),
                'letter' => $matches[1],
                'letter_case' => ctype_lower($matches[1]) ? 'lower' : 'upper',
                'suffix_letter' => isset($matches[3]) && $matches[3] !== '' ? strtolower($matches[3]) : null,
                'segments' => $numericSegments,
                'sequence' => end($numericSegments),
                'depth' => 2,
            ];
        }

        if (
            $this->excelUsesCompactAlphaHeadings()
            && preg_match('/^(\d+)([a-z])[.)]\s*/u', $text, $matches)
        ) {
            $letterSequence = ord(strtoupper($matches[2])) - 64;

            return [
                'type' => 'number_letter',
                'label' => $matches[1].strtolower($matches[2]),
                'segments' => [(int) $matches[1], $letterSequence],
                'sequence' => $letterSequence,
                'depth' => 2,
            ];
        }

        if (preg_match('/^(\d+(?:\.\d+)*)\.([A-Za-z])(?:(?:[.)-]+)(?=\s|[A-Za-z(]|$)|(?=\s|$))/u', $text, $matches)) {
            $segments = array_map('intval', explode('.', $matches[1]));
            $letterSequence = ord(strtoupper($matches[2])) - 64;
            $segments[] = $letterSequence;

            return [
                'type' => 'number',
                'label' => $matches[1].'.'.strtolower($matches[2]),
                'segments' => $segments,
                'sequence' => $letterSequence,
                'depth' => count($segments),
            ];
        }

        if (
            preg_match('/^(\d+(?:\.\d+)*)\s*([.)-]+)(?=\s|[A-Za-z(]|$)/u', $text, $matches)
            || preg_match('/^(\d+(?:\.\d+)+)(?=\s|[A-Za-z(]|$)/u', $text, $matches)
        ) {
            $segments = array_map('intval', explode('.', rtrim($matches[1], '.')));

            return [
                'type' => 'number',
                'label' => $matches[1],
                'segments' => $segments,
                'sequence' => end($segments),
                'depth' => substr_count($matches[1], '.') + 1,
            ];
        }

        $romanPattern = $this->excelSingleIIsRoman()
            ? '/^([IVX]|[IVXLCDM]{2,})[.)-]\s*/u'
            : '/^([VX]|[IVXLCDM]{2,})[.)-]\s*/u';
        if (preg_match($romanPattern, $text, $matches)) {
            return [
                'type' => 'roman',
                'label' => strtoupper($matches[1]),
                'segments' => [],
                'sequence' => $this->romanToInteger($matches[1]),
                'depth' => 1,
            ];
        }

        if (preg_match('/^([A-Z])[.)-]\s*/u', $text, $matches)) {
            return [
                'type' => 'upper_letter',
                'label' => strtoupper($matches[1]),
                'segments' => [],
                'sequence' => ord($matches[1]) - 64,
                'depth' => 1,
            ];
        }

        if (preg_match('/^([a-z])[.)-]\s*/u', $text, $matches)) {
            return [
                'type' => 'lower_letter',
                'label' => strtoupper($matches[1]),
                'segments' => [],
                'sequence' => ord(strtoupper($matches[1])) - 64,
                'depth' => 1,
            ];
        }

        return null;
    }

    private function isStoProgramHeader(string $text, array $row): bool
    {
        if ($text === '' || isset($row['B']) || ! isset($row['C']) || strcasecmp((string) $row['C'], 'CAR') !== 0) {
            return false;
        }

        if (
            $this->isStandaloneExcelTitleHeader($text)
            || $this->isStandaloneExcelSectionHeader($text)
            || $this->isPsRequirementsChild($text)
        ) {
            return false;
        }

        if (
            $this->excelUsesCodedProgramHeaders()
            && preg_match('/^[A-Z]\.\d+(?:\.[A-Z0-9]+)*\s+\p{L}/iu', $text)
        ) {
            return true;
        }

        if ($this->excelProgramHeaderPrefixes() !== []) {
            $normalized = preg_replace('/[^A-Z0-9]+/', '', strtoupper($text));

            return collect($this->excelProgramHeaderPrefixes())->contains(function (string $prefix) use ($normalized): bool {
                $normalizedPrefix = preg_replace('/[^A-Z0-9]+/', '', strtoupper($prefix));

                return $normalizedPrefix !== '' && str_starts_with($normalized, $normalizedPrefix);
            });
        }

        if ($this->excelHeadingDepth($text) !== null) {
            return false;
        }

        return strtoupper($text) === $text;
    }

    private function isStyledStoPapHeaderRow(string $text, array $row, array $rowStyles, string $importType = 'target'): bool
    {
        if (! $this->excelUsesStyledProgramHeaders() || $text === '' || empty($rowStyles['A']['bold'])) {
            return false;
        }

        if (
            isset($row['B'])
            || $this->hasAnyTargetValue($this->physicalValuesFromExcelRow($row, $importType))
            || $this->excelHeadingDepth($text) !== null
            || $this->isPsRequirementsChild($text)
        ) {
            return false;
        }

        if (isset($row['C']) && strcasecmp((string) $row['C'], 'CAR') !== 0) {
            return false;
        }

        if ($this->isStandaloneExcelTitleHeader($text) || $this->isStandaloneExcelSectionHeader($text)) {
            return false;
        }

        return ! $this->isExcelSignatureFooterRow($text);
    }

    private function isStoSheetHeaderText(?string $text): bool
    {
        $normalized = preg_replace('/[^A-Z0-9]+/', '', strtoupper(trim((string) $text)));
        $defaultTitle = preg_replace('/[^A-Z0-9]+/', '', strtoupper($this->excelConfig('default_title')));
        $sheetNames = array_map(
            static fn (string $name): string => preg_replace('/[^A-Z0-9]+/', '', strtoupper($name)),
            array_merge([$this->excelConfig('sheet_name')], $this->excelConfig('sheet_aliases'))
        );

        return $normalized === $defaultTitle
            || in_array($normalized, array_map(
                static fn (string $sheetName): string => $defaultTitle.$sheetName,
                $sheetNames
            ), true);
    }

    private function resolveExcelSheetName(string $filePath, ?SimpleXlsxReader $reader = null): string
    {
        $reader ??= new SimpleXlsxReader;

        return $reader->resolveSheetName(
            $filePath,
            array_merge([$this->excelConfig('sheet_name')], $this->excelConfig('sheet_aliases'))
        );
    }

    private function configureExcelColumnLayout(
        SimpleXlsxReader $reader,
        string $filePath,
        string $sheetName
    ): void {
        if ($this->excelColumnLayout !== []) {
            return;
        }

        $headerRows = [];
        foreach ($reader->rows($filePath, $sheetName, true) as $rowNumber => $row) {
            if ($rowNumber > 30) {
                break;
            }

            $headerRows[$rowNumber] = $row;
        }

        $this->configureExcelColumnLayoutFromRows($headerRows, $sheetName);
    }

    /** @param array<int, array<string, mixed>> $headerRows */
    private function configureExcelColumnLayoutFromRows(array $headerRows, string $sheetName = 'worksheet'): void
    {
        $coreColumns = [];
        $programColumns = [];
        $activityColumn = null;
        $sectionCandidates = [];
        $lastHeaderRow = 0;

        foreach ($headerRows as $rowNumber => $row) {
            $hasIndicatorHeader = collect($row)->contains(
                fn ($value) => str_contains($this->normalizeExcelHeader($value), 'PERFORMANCEINDICATOR')
            );
            foreach ($row as $column => $value) {
                $header = $this->normalizeExcelHeader($value);
                if ($header === '') {
                    continue;
                }

                if ($hasIndicatorHeader) {
                    $programField = ExcelProgramHierarchy::field((string) $value);
                    if ($programField !== null) {
                        $programColumns[$programField] = $column;
                    } elseif ($header === 'ACTIVITY') {
                        $activityColumn = $column;
                    }
                }

                if ($this->matchesExcelCoreHeader($header, 'PROGRAMACTIVITYPROJECT', 'pap_header_aliases')) {
                    $coreColumns['pap'] = $column;
                    $lastHeaderRow = max($lastHeaderRow, (int) $rowNumber);
                } elseif (str_contains($header, 'PERFORMANCEINDICATOR')) {
                    $coreColumns['indicator'] = $column;
                    $lastHeaderRow = max($lastHeaderRow, (int) $rowNumber);
                } elseif ($this->matchesExcelCoreHeader($header, 'LOCATION', 'location_header_aliases')) {
                    $coreColumns['location'] = $column;
                    $lastHeaderRow = max($lastHeaderRow, (int) $rowNumber);
                }

                $section = $this->excelSectionFromHeader($header);
                if ($section !== null) {
                    $sectionCandidates[] = [
                        'section' => $section,
                        'column' => $column,
                        'row' => (int) $rowNumber,
                    ];
                }
            }
        }

        if ($programColumns !== []) {
            $coreColumns['program_hierarchy'] = $programColumns;
            $coreColumns['pap'] ??= $activityColumn ?? '__empty_pap';
        }

        foreach (['pap', 'indicator', 'location'] as $requiredCoreColumn) {
            if (! isset($coreColumns[$requiredCoreColumn])) {
                throw new \RuntimeException(
                    "The {$sheetName} worksheet is missing a recognizable {$requiredCoreColumn} header."
                );
            }
        }

        usort(
            $sectionCandidates,
            fn (array $left, array $right): int => $this->excelColumnNumber($left['column']) <=> $this->excelColumnNumber($right['column'])
        );

        $sections = [];
        foreach ($sectionCandidates as $candidateIndex => $candidate) {
            if (isset($sections[$candidate['section']])) {
                continue;
            }

            $startNumber = $this->excelColumnNumber($candidate['column']);
            $endNumber = PHP_INT_MAX;
            foreach (array_slice($sectionCandidates, $candidateIndex + 1) as $nextCandidate) {
                $nextNumber = $this->excelColumnNumber($nextCandidate['column']);
                if ($nextNumber > $startNumber) {
                    $endNumber = $nextNumber - 1;
                    break;
                }
            }

            $periodColumns = [];
            $quarter = 0;
            $annualColumn = null;
            foreach ($headerRows as $rowNumber => $row) {
                if ((int) $rowNumber < $candidate['row'] || (int) $rowNumber > $candidate['row'] + 5) {
                    continue;
                }

                foreach ($row as $column => $value) {
                    $columnNumber = $this->excelColumnNumber($column);
                    if ($columnNumber < $startNumber || $columnNumber > $endNumber) {
                        continue;
                    }

                    $header = $this->normalizeExcelHeader($value);
                    $period = match ($header) {
                        'JAN', 'JANUARY' => 'jan',
                        'FEB', 'FEBRUARY' => 'feb',
                        'MAR', 'MARCH' => 'mar',
                        'APR', 'APRIL' => 'apr',
                        'MAY' => 'may',
                        'JUN', 'JUNE' => 'jun',
                        'JUL', 'JULY' => 'jul',
                        'AUG', 'AUGUST' => 'aug',
                        'SEP', 'SEPT', 'SEPTEMBER' => 'sep',
                        'OCT', 'OCTOBER' => 'oct',
                        'NOV', 'NOVEMBER' => 'nov',
                        'DEC', 'DECEMBER' => 'dec',
                        default => null,
                    };

                    if ($period !== null) {
                        $periodColumns[$period] = $column;
                        $lastHeaderRow = max($lastHeaderRow, (int) $rowNumber);
                    } elseif ($header === 'TOTAL' && $columnNumber > $startNumber) {
                        $quarter++;
                        if ($quarter <= 4) {
                            $periodColumns['q'.$quarter] = $column;
                            $lastHeaderRow = max($lastHeaderRow, (int) $rowNumber);
                        }
                    } elseif (str_contains($header, 'GRANDTOTAL')) {
                        $annualColumn = $column;
                    }
                }
            }

            $requiredPeriods = [
                'jan', 'feb', 'mar', 'q1', 'apr', 'may', 'jun', 'q2',
                'jul', 'aug', 'sep', 'q3', 'oct', 'nov', 'dec', 'q4',
            ];
            if (array_diff($requiredPeriods, array_keys($periodColumns)) !== [] || $annualColumn === null) {
                continue;
            }

            $periodColumns['annual_total'] = $annualColumn;
            $sections[$candidate['section']] = $periodColumns;
        }

        if (! isset($sections['physical_target'])) {
            throw new \RuntimeException(
                "The {$sheetName} worksheet is missing a recognizable Physical Target period header."
            );
        }

        $this->excelColumnLayout = array_merge($coreColumns, $sections);
        $this->excelDataStartRow = max(1, $lastHeaderRow + 1);
    }

    private function normalizeExcelHeader(mixed $value): string
    {
        return preg_replace('/[^A-Z]+/', '', strtoupper(trim((string) $value)));
    }

    private function matchesExcelCoreHeader(string $header, string $canonicalNeedle, string $aliasConfigKey): bool
    {
        if (str_contains($header, $canonicalNeedle)) {
            return true;
        }

        foreach ((array) $this->excelConfig($aliasConfigKey) as $alias) {
            if ($header === $this->normalizeExcelHeader($alias)) {
                return true;
            }
        }

        return false;
    }

    private function excelSectionFromHeader(string $header): ?string
    {
        return match (true) {
            str_contains($header, 'PHYSICALACCOMPLISHMENT') => 'physical_accomplishment',
            str_contains($header, 'FINANCIALACCOMPLISHMENT') => 'financial_accomplishment',
            str_contains($header, 'PHYSICALTARGET') => 'physical_target',
            str_contains($header, 'FINANCIALTARGET') => 'financial_target',
            default => null,
        };
    }

    private function excelColumnNumber(string $column): int
    {
        $number = 0;
        foreach (str_split(strtoupper($column)) as $character) {
            $number = ($number * 26) + (ord($character) - 64);
        }

        return $number;
    }

    private function normalizeExcelCoreColumns(array $row): array
    {
        $sourceRow = $row;
        $programValues = [];
        foreach ($this->excelColumnLayout['program_hierarchy'] ?? [] as $field => $column) {
            if (isset($sourceRow[$column]) && is_scalar($sourceRow[$column])) {
                $value = $this->cleanExcelText($sourceRow[$column]);
                if ($value !== '') {
                    $programValues[$field] = $value;
                }
            }
        }
        foreach (['pap' => 'A', 'indicator' => 'B', 'location' => 'C'] as $layoutKey => $canonicalColumn) {
            $sourceColumn = $this->excelColumnLayout[$layoutKey] ?? $canonicalColumn;
            if (array_key_exists($sourceColumn, $sourceRow)) {
                $row[$canonicalColumn] = $sourceRow[$sourceColumn];
            } elseif ($sourceColumn !== $canonicalColumn) {
                unset($row[$canonicalColumn]);
            }
        }
        if ($programValues !== []) {
            $row['__program_hierarchy'] = $programValues;
        }

        return $row;
    }

    /** @return array<string, string> */
    private function excelPeriodColumns(string $section): array
    {
        $columns = $this->excelColumnLayout[$section] ?? null;
        if (! is_array($columns)) {
            return [];
        }

        return $columns;
    }

    private function configureExcelParsingRules(
        SimpleXlsxReader $reader,
        string $filePath,
        string $sheetName
    ): void {
        $startMarkers = $this->excelConfig('start_markers');
        $stopMarkers = $this->excelConfig('stop_markers');
        $layoutMarkers = $startMarkers !== [] ? $startMarkers : $stopMarkers;
        $headerParts = [];

        foreach ($reader->rows($filePath, $sheetName, true) as $rowNumber => $row) {
            if ($layoutMarkers === [] && $rowNumber > 20) {
                break;
            }
            $row = $this->normalizeExcelCoreColumns($row);
            if ($rowNumber <= 20) {
                $headerText = $this->cleanExcelText($row['A'] ?? '');
                if ($headerText !== '') {
                    $headerParts[] = $headerText;
                }
            }

            if ($layoutMarkers !== [] && $this->excelImportRowMatchesMarkers($row, $layoutMarkers)) {
                $this->usesSectorSpecificExcelRules = true;

                return;
            }
        }

        if ($layoutMarkers !== []) {
            $this->usesSectorSpecificExcelRules = false;

            return;
        }

        $normalizedHeader = preg_replace('/[^A-Z0-9]+/', '', strtoupper(implode(' ', $headerParts)));
        $normalizedTitle = preg_replace('/[^A-Z0-9]+/', '', strtoupper($this->excelConfig('default_title')));
        $this->usesSectorSpecificExcelRules = $normalizedTitle !== ''
            && str_contains($normalizedHeader, $normalizedTitle);
    }

    private function excelUsesStyledProgramHeaders(): bool
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('uses_styled_program_headers')
            : true;
    }

    private function excelUsesStyledSectionHeaders(): bool
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('uses_styled_section_headers')
            : false;
    }

    private function excelUsesCodedProgramHeaders(): bool
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('uses_coded_program_headers')
            : false;
    }

    private function excelLimitsProgramTitleContinuations(): bool
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('limits_program_title_continuations')
            : false;
    }

    /** @return array<int, string> */
    private function excelProgramHeaderPrefixes(): array
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('program_header_prefixes')
            : [];
    }

    private function excelMergesCarContinuationHeaders(): bool
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('merges_car_continuation_headers')
            : true;
    }

    private function excelMergesActivityContinuationRows(): bool
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('merges_activity_continuation_rows')
            : false;
    }

    private function excelStartsPlaceholderFromOfficeContinuation(): bool
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('starts_placeholder_from_office_continuation')
            : false;
    }

    private function excelUsesCompactAlphaHeadings(): bool
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('uses_compact_alpha_headings')
            : false;
    }

    /** @return array<int, string> */
    private function excelUnlocatedSectionMarkers(): array
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('unlocated_section_markers')
            : [];
    }

    private function excelUnlocatedSectionChildrenAreRows(): bool
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('unlocated_section_children_are_rows')
            : false;
    }

    /** @return array<int, string> */
    private function excelRootSectionMarkers(): array
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('root_section_markers')
            : [];
    }

    private function excelUsesOrderedHeadingDepths(): bool
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('uses_ordered_heading_depths')
            : false;
    }

    private function excelSingleIIsRoman(): bool
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('single_i_is_roman')
            : false;
    }

    private function excelKeepsLowerLetterSiblingsUnderRootNumber(): bool
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('lower_letter_siblings_under_root_number')
            : false;
    }

    /** @return array<string, array<int, string>> */
    private function excelNestedLetterParentChildren(): array
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('nested_letter_parent_children')
            : [];
    }

    private function excelPersistsSourceOrder(): bool
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('persists_source_order')
            : false;
    }

    /** @return array<int, string> */
    private function excelStartMarkers(): array
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('start_markers')
            : [];
    }

    /** @return array<int, string> */
    private function excelStopMarkers(): array
    {
        return $this->usesSectorSpecificExcelRules
            ? $this->excelConfig('stop_markers')
            : [];
    }

    private function resetImportedHierarchySourceOrder(int $year): void
    {
        if (! $this->excelPersistsSourceOrder()) {
            return;
        }

        $detailIds = DB::table('ppa')
            ->where('types_id', $this->getStoTypeId())
            ->where('year', $year)
            ->pluck('ppa_details_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();
        $frontier = $detailIds;

        while ($frontier->isNotEmpty()) {
            $children = DB::table('ppa_details')
                ->whereIn('parent_id', $frontier->all())
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->diff($detailIds)
                ->unique()
                ->values();

            if ($children->isEmpty()) {
                break;
            }

            $detailIds = $detailIds->merge($children)->unique()->values();
            $frontier = $children;
        }

        if ($detailIds->isNotEmpty()) {
            DB::table('ppa_details')
                ->whereIn('id', $detailIds->all())
                ->update([
                    'source_order' => null,
                    'updated_at' => now(),
                ]);
        }
    }

    private function shouldStopExcelImportAtRow(array $row): bool
    {
        return $this->excelImportRowMatchesMarkers($row, $this->excelStopMarkers());
    }

    private function shouldStartExcelImportAtRow(array $row): bool
    {
        return $this->excelImportRowMatchesMarkers($row, $this->excelStartMarkers());
    }

    private function isUnlocatedExcelSectionMarker(string $text): bool
    {
        $normalized = strtoupper(trim(preg_replace('/\s+/', ' ', $text)));

        return collect($this->excelUnlocatedSectionMarkers())->contains(
            static fn (string $marker): bool => $normalized === strtoupper(trim(preg_replace('/\s+/', ' ', $marker)))
        );
    }

    private function excelImportRowMatchesMarkers(array $row, array $markers): bool
    {
        $papText = $this->cleanExcelText($row['A'] ?? '');
        if ($papText === '') {
            return false;
        }

        $normalizedPap = strtoupper(preg_replace('/\s+/', ' ', $papText));

        foreach ($markers as $marker) {
            $normalizedMarker = strtoupper(preg_replace('/\s+/', ' ', trim((string) $marker)));
            if ($normalizedMarker !== '' && str_starts_with($normalizedPap, $normalizedMarker)) {
                return true;
            }
        }

        return false;
    }

    private function isStyledStoSectionHeaderRow(string $text, array $row, array $rowStyles, string $importType = 'target'): bool
    {
        $heading = $this->excelHeadingInfo($text);
        if (isset($row['B']) || $this->hasAnyTargetValue($this->physicalValuesFromExcelRow($row, $importType))) {
            return false;
        }

        if (($heading['type'] ?? null) === 'upper_letter') {
            return $this->isGreenExcelFill($rowStyles['A']['fill'] ?? null)
                || $this->isGreenExcelFill($rowStyles['A']['fill_type'] ?? null)
                || ! empty($rowStyles['A']['bold']);
        }

        if (! $this->excelUsesStyledSectionHeaders() || empty($rowStyles['A']['bold'])) {
            return false;
        }

        if ($heading !== null || $this->isExcelSignatureFooterRow($text)) {
            return false;
        }

        $isUppercaseLabel = mb_strtoupper($text, 'UTF-8') === $text;
        $isCarLabel = isset($row['C']) && strcasecmp((string) $row['C'], 'CAR') === 0;
        $fill = strtoupper(trim((string) ($rowStyles['A']['fill'] ?? '')));
        $hasSectionFill = in_array($fill, [
            'FFECECEC',
            'FFFFFFFF',
            'FFCFE2F3',
            'FFDEEAF6',
        ], true);
        $isProgramFragment = $hasSectionFill
            && preg_match('/\b(?:SUB-)?PROGRAM\b/i', $text) === 1;

        if (
            isset($row['C'])
            && strcasecmp((string) $row['C'], 'CAR') !== 0
            && ! $isProgramFragment
        ) {
            return false;
        }

        return $isUppercaseLabel || $isCarLabel || $hasSectionFill || $isProgramFragment;
    }

    private function isGreenExcelFill($fill): bool
    {
        $fill = strtoupper(trim((string) $fill));
        if ($fill === '') {
            return false;
        }

        return str_contains($fill, '008000')
            || str_contains($fill, '00B050')
            || str_contains($fill, '70AD47')
            || str_contains($fill, '92D050')
            || str_contains($fill, '548235')
            || str_contains($fill, 'SOLID');
    }

    private function isDuplicateStoSummaryProgramHeader(string $text): bool
    {
        $normalized = preg_replace('/[^A-Z0-9]+/', '', strtoupper(trim($text)));

        return in_array($normalized, [
            'MANDATORIESANDIMPOSITIONS',
        ], true);
    }

    private function isParentOfficeRow(string $locationName): bool
    {
        return in_array($this->normalizeImportedOfficeName($locationName), [
            'ABRA',
            'APAYAO',
            'BENGUET',
            'IFUGAO',
            'KALINGA',
            'MOUNTAINPROVINCE',
            'MTPROVINCE',
        ], true);
    }

    private function physicalValuesFromExcelRow(array $row, string $importType = 'target'): array
    {
        $columns = $this->excelPeriodColumns(
            $importType === 'accomplishment' ? 'physical_accomplishment' : 'physical_target'
        );

        return $this->excelValuesFromPeriodColumns($row, $columns);
    }

    private function financialTargetValuesFromExcelRow(array $row): array
    {
        $columns = $this->excelPeriodColumns('financial_target');
        return $this->excelValuesFromPeriodColumns($row, $columns);
    }

    private function financialAccomplishmentValuesFromExcelRow(array $row): array
    {
        $columns = $this->excelPeriodColumns('financial_accomplishment');
        return $this->excelValuesFromPeriodColumns($row, $columns);
    }

    /** @param array<string, string> $columns */
    private function excelValuesFromPeriodColumns(array $row, array $columns): array
    {
        $values = array_fill_keys($this->targetValueKeys(), 0.0);
        foreach ($columns as $key => $column) {
            if (array_key_exists($key, $values)) {
                $values[$key] = $this->excelNumber($row[$column] ?? 0);
            }
        }

        if ($values['annual_total'] <= 0) {
            $values['annual_total'] = $values['q1'] + $values['q2'] + $values['q3'] + $values['q4'];
        }

        return $values;
    }

    private function hasFinancialTargetColumns(SimpleXlsxReader $reader, string $filePath, string $sheetName): bool
    {
        $this->configureExcelColumnLayout($reader, $filePath, $sheetName);

        return $this->excelPeriodColumns('financial_target') !== [];
    }

    private function hasFinancialAccomplishmentColumns(SimpleXlsxReader $reader, string $filePath, string $sheetName): bool
    {
        $this->configureExcelColumnLayout($reader, $filePath, $sheetName);

        return $this->excelPeriodColumns('financial_accomplishment') !== [];
    }

    private function ensurePhysicalImportColumnsExist(SimpleXlsxReader $reader, string $filePath, string $sheetName, string $importType): void
    {
        if ($importType !== 'accomplishment') {
            return;
        }

        $this->configureExcelColumnLayout($reader, $filePath, $sheetName);
        if ($this->excelPeriodColumns('physical_accomplishment') !== []) {
            return;
        }

        throw new \RuntimeException(
            "The {$sheetName} worksheet does not contain a recognizable Physical Accomplishment period header."
        );
    }

    /** @return array<int, string> */
    private function physicalImportTypes(string $importType): array
    {
        return $importType === 'both'
            ? ['target', 'accomplishment']
            : [$importType];
    }

    /** @param array<string, array<string, int>> $results */
    private function combinePhysicalImportResults(array $results): array
    {
        $target = $results['target'] ?? [];
        $accomplishment = $results['accomplishment'] ?? [];

        return [
            'imported' => (int) ($target['imported'] ?? 0) + (int) ($accomplishment['imported'] ?? 0),
            'target_imported' => (int) ($target['imported'] ?? 0),
            'accomplishment_imported' => (int) ($accomplishment['imported'] ?? 0),
            'financial_imported' => (int) (($target['financial_imported'] ?? null)
                ?? ($accomplishment['financial_imported'] ?? 0)),
            'financial_accomplishment_imported' => (int) (($target['financial_accomplishment_imported'] ?? null)
                ?? ($accomplishment['financial_accomplishment_imported'] ?? 0)),
            'skipped' => (int) ($target['skipped'] ?? 0) + (int) ($accomplishment['skipped'] ?? 0),
            'placeholders' => (int) ($target['placeholders'] ?? 0) + (int) ($accomplishment['placeholders'] ?? 0),
        ];
    }

    /** @param array<string, array<string, mixed>> $previews */
    private function combinePhysicalImportPreviews(array $previews): array
    {
        $target = $previews['target'] ?? [];
        $accomplishment = $previews['accomplishment'] ?? [];
        $rowsByKey = [];

        foreach ($previews as $preview) {
            foreach ($preview['rows'] ?? [] as $row) {
                $key = implode('|', [
                    (string) ($row['row'] ?? ''),
                    (string) ($row['indicator'] ?? ''),
                    json_encode(array_intersect_key($row, array_flip(ExcelProgramHierarchy::FIELDS))),
                    (string) ($row['project'] ?? ''),
                    json_encode($row['hierarchy'] ?? []),
                ]);

                if (! isset($rowsByKey[$key])) {
                    $rowsByKey[$key] = $row;
                    continue;
                }

                $rowsByKey[$key]['offices'] = collect($rowsByKey[$key]['offices'] ?? [])
                    ->merge($row['offices'] ?? [])
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
                $rowsByKey[$key]['office_count'] = max(
                    (int) ($rowsByKey[$key]['office_count'] ?? 0),
                    (int) ($row['office_count'] ?? 0),
                    count($rowsByKey[$key]['offices'])
                );
                $rowsByKey[$key]['has_car_total'] = ! empty($rowsByKey[$key]['has_car_total'])
                    || ! empty($row['has_car_total']);
            }
        }

        $warningsByKey = [];
        foreach ($previews as $preview) {
            foreach ($preview['warnings'] ?? [] as $warning) {
                $key = implode('|', [
                    (string) ($warning['row'] ?? ''),
                    (string) ($warning['level'] ?? ''),
                    (string) ($warning['message'] ?? ''),
                ]);
                $warningsByKey[$key] = $warning;
            }
        }
        $warnings = array_values($warningsByKey);
        $rows = array_slice(array_values($rowsByKey), 0, 120);

        return [
            'year' => (int) (($target['year'] ?? null) ?? ($accomplishment['year'] ?? 0)),
            'imported' => (int) ($target['imported'] ?? 0) + (int) ($accomplishment['imported'] ?? 0),
            'target_imported' => (int) ($target['imported'] ?? 0),
            'accomplishment_imported' => (int) ($accomplishment['imported'] ?? 0),
            'skipped' => (int) ($target['skipped'] ?? 0) + (int) ($accomplishment['skipped'] ?? 0),
            'parsed_rows' => (int) ($target['parsed_rows'] ?? 0) + (int) ($accomplishment['parsed_rows'] ?? 0),
            'shown_rows' => count($rows),
            'placeholders' => (int) ($target['placeholders'] ?? 0) + (int) ($accomplishment['placeholders'] ?? 0),
            'rows' => $rows,
            'warnings' => array_slice($warnings, 0, 80),
            'warning_count' => count($warnings),
        ];
    }

    private function resolvePhysicalImportType(string $filePath, string $sheetName, ?string $requestedImportType): string
    {
        if ($requestedImportType !== null) {
            return $requestedImportType;
        }

        $reader = new SimpleXlsxReader;
        $this->configureExcelColumnLayout($reader, $filePath, $sheetName);
        $accomplishmentColumns = $this->excelPeriodColumns('physical_accomplishment');

        foreach ($reader->rows($filePath, $sheetName, true) as $rowNumber => $row) {
            if ($rowNumber < $this->excelDataStartRow) {
                continue;
            }

            foreach ($accomplishmentColumns as $column) {
                if ($this->excelNumber($row[$column] ?? null) != 0.0) {
                    return 'both';
                }
            }
        }

        return 'target';
    }

    private function hasAnyTargetValue(array $values): bool
    {
        foreach ($values as $value) {
            if ((float) $value !== 0.0) {
                return true;
            }
        }

        return false;
    }

    private function getOfficeImportMap(): array
    {
        return Office::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($office) => [$this->normalizeImportedOfficeName($office->name) => (int) $office->id])
            ->all();
    }

    private function resolveImportedOfficeId(string $name, array $officeMap, ?int $currentParentOfficeId = null): ?int
    {
        $normalized = $this->normalizeImportedOfficeName($name);
        $aliases = [
            'CAR' => null,
            'PENRO' => $currentParentOfficeId,
            'MOUNTAINPROV' => $officeMap['MTPROVINCE'] ?? null,
            'MOUNTAINPROVINCE' => $officeMap['MTPROVINCE'] ?? null,
            'MTPROV' => $officeMap['MTPROVINCE'] ?? null,
            'MT.PROVINCE' => $officeMap['MTPROVINCE'] ?? null,
            'MTPROVINCE' => $officeMap['MTPROVINCE'] ?? null,
            'MPROVINCE' => $officeMap['MTPROVINCE'] ?? null,
            'MTNPROVINCE' => $officeMap['MTPROVINCE'] ?? null,
            'MT' => $officeMap['MTPROVINCE'] ?? null,
            'ALFONSOLISTA' => $officeMap['ALFONSOLISTA'] ?? null,
            'ALISTA' => $officeMap['ALFONSOLISTA'] ?? null,
            'C PARACELIS' => $officeMap['PARACELIS'] ?? null,
            'CPARACELIS' => $officeMap['PARACELIS'] ?? null,
            'C SABANGAN' => $officeMap['SABANGAN'] ?? null,
            'CSABANGAN' => $officeMap['SABANGAN'] ?? null,
        ];

        if (array_key_exists($normalized, $aliases)) {
            return $aliases[$normalized];
        }

        return $officeMap[$normalized] ?? null;
    }

    private function normalizeImportedOfficeName(string $name): string
    {
        return preg_replace('/[^A-Z0-9]+/', '', strtoupper($name));
    }

    private function cleanExcelText($value): string
    {
        $value = trim((string) $value);
        if ($value === '' || str_starts_with($value, '#')) {
            return '';
        }

        return $value;
    }

    private function excelPpaName($value): ?string
    {
        $value = $this->cleanExcelText($value);
        if ($value === '') {
            return null;
        }

        return $value;
    }

    private function excelNumber($value): float
    {
        $value = str_replace(',', '', trim((string) $value));
        if ($value === '' || str_starts_with($value, '#') || ! is_numeric($value)) {
            return 0.0;
        }

        return (float) $value;
    }

    private function isFinancialOnlyExcelRow(array $row): bool
    {
        return ! isset($row['B'])
            && ! $this->hasAnyTargetValue($this->physicalValuesFromExcelRow($row))
            && (
                $this->hasAnyTargetValue($this->financialTargetValuesFromExcelRow($row))
                || $this->hasAnyTargetValue($this->financialAccomplishmentValuesFromExcelRow($row))
            );
    }
}
