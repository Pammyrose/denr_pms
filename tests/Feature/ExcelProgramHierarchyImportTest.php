<?php

namespace Tests\Feature;

use App\Http\Controllers\PhysicalExcelUploadController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use ZipArchive;

class ExcelProgramHierarchyImportTest extends TestCase
{
    use RefreshDatabase;

    private array $workbooks = [];

    protected function tearDown(): void
    {
        foreach ($this->workbooks as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        parent::tearDown();
    }

    public static function sectorsAndFormats(): array
    {
        $cases = [];
        foreach (['gass', 'sto', 'enf', 'pa', 'engp', 'lands', 'soilcon', 'nra', 'paria', 'cobb', 'continuing'] as $sector) {
            foreach (['columns', 'headings'] as $format) {
                $cases["{$sector}/{$format}"] = [$sector, $format];
            }
        }

        return $cases;
    }

    private function fixtures(string $sector): void
    {
        $config = PhysicalExcelUploadController::sectorConfiguration($sector);
        DB::table('types')->updateOrInsert(['code' => $config['type_code']], ['desc' => $sector]);
        foreach (['PROGRAM', 'PROJECT', 'MAIN ACTIVITY', 'SUB-ACTIVITY', 'SUB-SUB-ACTIVITY', 'SUB-SUB-SUB-ACTIVITY', 'LEVEL-7', 'LEVEL-8', 'LEVEL-9'] as $name) {
            DB::table('record_types')->updateOrInsert(['name' => $name]);
        }
        $officeType = DB::table('office_types')->insertGetId(['name' => 'Regional Office']);
        DB::table('offices')->insert(['name' => 'RO', 'office_types_id' => $officeType]);
        $this->actingAs(User::create([
            'name' => 'Excel tester', 'email' => 'excel@example.test', 'password' => 'test-password', 'role' => 'admin',
        ]));
    }

    private function workbook(string $sector, string $format, ?array $body = null): UploadedFile
    {
        $config = PhysicalExcelUploadController::sectorConfiguration($sector);
        $rows = [
            1 => ['A' => $format === 'columns' ? 'Activity' : 'Program/Activity/Project', 'B' => 'Performance Indicator', 'C' => 'Location',
                'I' => 'Physical Target', 'AV' => 'Physical Accomplishment', 'AB' => 'Financial Target', 'BN' => 'Financial Accomplishment'],
            2 => [],
        ];
        $periods = ['Jan', 'Feb', 'Mar', 'Total', 'Apr', 'May', 'Jun', 'Total', 'Jul', 'Aug', 'Sep', 'Total', 'Oct', 'Nov', 'Dec', 'Total', 'Grand Total'];
        foreach (['I', 'AV', 'AB', 'BN'] as $firstColumn) {
            $number = 0;
            foreach (str_split($firstColumn) as $letter) {
                $number = $number * 26 + ord($letter) - 64;
            }
            foreach ($periods as $index => $period) {
                $rows[2][$this->column($number + $index)] = $period;
            }
        }
        $values = ['Watershed project', 'Environment', 'Forest', 'Watersheds', 'Northern', 'Restoration', 'Community'];
        $labels = ['Project', 'Program', 'Sub-program', 'Sub-sub-program', 'Sub-sub-sub-program', 'Sub-sub-sub-sub-program', 'Sub-sub-sub-sub-sub-program'];
        $data = ['A' => 'Survey', 'B' => 'Areas surveyed', 'C' => 'RO', 'I' => 12, 'AV' => 4, 'AB' => 100, 'BN' => 20];
        if ($format === 'columns') {
            foreach ($labels as $index => $label) {
                $rows[1][$this->column(131 + $index)] = $label;
                $data[$this->column(131 + $index)] = $values[$index];
            }
            $rows[3] = $data;
            $rows[4] = array_replace(array_diff_key($data, array_flip(array_map(fn ($i) => $this->column(131 + $i), range(0, 6)))), ['EG' => 'Uplands']);
        } else {
            foreach ($labels as $index => $label) {
                $rows[$index + 3] = ['A' => "{$label}: {$values[$index]}"];
            }
            $rows[10] = $data;
            $rows[11] = ['A' => 'Sub-sub-sub-sub-sub-program: Uplands'];
            $rows[12] = $data;
        }
        if ($body !== null) {
            $rows = array_slice($rows, 0, 2, true);
            foreach ($body as $index => $row) {
                $rows[$index + 3] = $row;
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'pms-hierarchy-');
        $this->workbooks[] = $path;
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path, ZipArchive::OVERWRITE) === true);
        $zip->addFromString('[Content_Types].xml', '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/></Types>');
        $zip->addFromString('xl/workbook.xml', '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.htmlspecialchars($config['sheet_name'], ENT_XML1).'" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Target="worksheets/sheet1.xml" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"/></Relationships>');
        $xml = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach ($rows as $rowNumber => $row) {
            $xml .= '<row r="'.$rowNumber.'">';
            foreach ($row as $column => $value) {
                $xml .= '<c r="'.$column.$rowNumber.'" t="inlineStr"><is><t>'.htmlspecialchars((string) $value, ENT_XML1).'</t></is></c>';
            }
            $xml .= '</row>';
        }
        $zip->addFromString('xl/worksheets/sheet1.xml', $xml.'</sheetData></worksheet>');
        $zip->close();

        return new UploadedFile($path, 'hierarchy.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function column(int $number): string
    {
        $column = '';
        while ($number > 0) {
            $number--;
            $column = chr(65 + $number % 26).$column;
            $number = intdiv($number, 26);
        }

        return $column;
    }

    #[DataProvider('sectorsAndFormats')]
    public function test_preview_import_and_reimport_keep_five_program_levels_and_values(string $sector, string $format): void
    {
        $this->fixtures($sector);
        $file = $this->workbook($sector, $format);
        $payload = ['excel_file' => $file, 'year' => 2026, 'import_type' => 'both'];
        $preview = $this->postJson(route("admin.{$sector}_physical.import_excel.preview"), $payload)
            ->assertOk()->json('preview');
        $this->assertCount(2, $preview['rows']);
        $this->assertSame(['Community', 'Uplands'], array_column($preview['rows'], 'subsubsubsubsubprogram'));
        $this->assertSame('Forest', $preview['rows'][0]['subprogram']);
        $this->assertSame('Watershed project', $preview['rows'][0]['title']);
        $this->assertDatabaseCount('ppa', 0);
        $url = route("admin.{$sector}_physical.import_excel");
        $this->post($url, $payload)->assertRedirect()->assertSessionHas('success')->assertSessionMissing('error');
        foreach (['physical_targets' => 12, 'physical_accomplishments' => 4, 'financial_target' => 100, 'financial_accomplishment' => 20] as $table => $value) {
            $records = DB::table($table)->where('sector', $sector)->get();
            $this->assertCount(2, $records);
            $this->assertSame(2, $records->pluck('row_id')->unique()->count());
            $this->assertEquals($value, $records[0]->jan);
        }
        $nodeCount = DB::table('ppa')->count();
        $this->post($url, $payload)->assertRedirect()->assertSessionHas('success')->assertSessionMissing('error');
        $this->assertDatabaseCount('ppa', $nodeCount);
        $this->assertDatabaseCount('physical_targets', 2);
        $this->assertDatabaseHas('ppa_details', ['subprogram' => 'Forest', 'subsubsubsubsubprogram' => 'Community']);
    }

    public function test_invalid_nested_heading_rejects_preview_and_rolls_back_import(): void
    {
        $this->fixtures('gass');
        $this->post(route('admin.gass_physical.import_excel'), [
            'excel_file' => $this->workbook('gass', 'headings'), 'year' => 2026,
        ])->assertRedirect()->assertSessionHas('success');
        $originalTargets = DB::table('physical_targets')->orderBy('id')->get()->toJson();
        $nodeCount = DB::table('ppa')->count();
        $file = $this->workbook('gass', 'headings', [
            ['A' => 'Project: Watershed project'], ['A' => 'Program: Environment'],
            ['A' => 'Survey', 'B' => 'Areas surveyed', 'C' => 'RO', 'I' => 12],
            ['A' => 'Another survey', 'B' => 'Reports submitted', 'C' => 'RO', 'I' => 3],
            ['A' => 'Sub-sub-sub-sub-sub-program: Missing parents'],
        ]);
        $payload = ['excel_file' => $file, 'year' => 2026];
        $this->postJson(route('admin.gass_physical.import_excel.preview'), $payload)->assertUnprocessable();
        $this->post(route('admin.gass_physical.import_excel'), $payload)->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseCount('ppa', $nodeCount);
        $this->assertSame($originalTargets, DB::table('physical_targets')->orderBy('id')->get()->toJson());
    }

    public function test_legacy_path_does_not_attach_to_a_manual_program_branch(): void
    {
        $this->fixtures('gass');
        $payload = ['title' => 'Project', 'program' => 'Program', 'project' => 'N/A', 'activities' => 'Survey', 'year' => 2026];
        $manual = $this->postJson(route('admin.gass_physical.pap.store'), $payload + ['subprogram' => 'Manual branch'])->assertOk()->json('pap');
        $controller = (new PhysicalExcelUploadController)->forSector('gass');
        $method = new \ReflectionMethod($controller, 'storePapHierarchyInPpa');
        $legacy = $method->invoke($controller, $payload);
        $this->assertNotSame($manual['id'], $legacy->id);
        $this->assertSame($legacy->id, $method->invoke($controller, $payload)->id);
        $this->assertSame($manual['id'], $method->invoke($controller, $payload + ['subprogram' => 'Manual branch'])->id);
    }

    public function test_combined_preview_keeps_distinct_program_branches_on_the_same_source_row(): void
    {
        $controller = (new PhysicalExcelUploadController)->forSector('gass');
        $method = new \ReflectionMethod($controller, 'combinePhysicalImportPreviews');
        $first = ['row' => 10, 'title' => 'Project', 'program' => 'Program', 'subprogram' => 'One', 'indicator' => 'Survey', 'hierarchy' => ['Activity']];
        $second = array_replace($first, ['subprogram' => 'Two']);
        $result = $method->invoke($controller, [
            'target' => ['rows' => [$first, $second]],
            'accomplishment' => ['rows' => [$first, $second]],
        ]);
        $this->assertCount(2, $result['rows']);
    }

    public function test_preview_checks_number_and_letter_sequences_only_within_the_parent_path(): void
    {
        $this->fixtures('gass');
        $base = ['C' => 'RO', 'I' => 1, 'EA' => 'Project', 'EB' => 'Program'];
        $file = $this->workbook('gass', 'columns', [
            $base + ['A' => '1. Survey', 'B' => 'First', 'EC' => '9. Parent branch'],
            $base + ['A' => '3. Survey', 'B' => 'Third', 'EC' => '9. Parent branch'],
            $base + ['A' => '1. Survey', 'B' => 'First in new branch', 'EC' => '1. New branch'],
            $base + ['A' => 'A. Assess', 'B' => 'Alpha', 'EC' => '1. New branch'],
            $base + ['A' => 'C. Assess', 'B' => 'Gamma', 'EC' => '1. New branch'],
        ]);
        $preview = $this->postJson(route('admin.gass_physical.import_excel.preview'), [
            'excel_file' => $file, 'year' => 2026,
        ])->assertOk()->json('preview');
        $this->assertSame([4, 7], array_column($preview['warnings'], 'row'));
        $this->assertStringContainsString('Number sorting jumps from 1. to 3.', $preview['warnings'][0]['message']);
        $this->assertStringContainsString('Letter sorting jumps from A. to C.', $preview['warnings'][1]['message']);
    }

    public function test_preview_and_import_extend_short_execution_limits_and_preserve_larger_limits(): void
    {
        $this->fixtures('gass');
        $file = $this->workbook('gass', 'headings');
        $original = (int) ini_get('max_execution_time');
        try {
            foreach ([60 => 300, 0 => 0, 600 => 600] as $limit => $expected) {
                ini_set('max_execution_time', (string) $limit);
                $this->postJson(route('admin.gass_physical.import_excel.preview'), [
                    'excel_file' => $file, 'year' => 2026,
                ])->assertOk();
                $this->assertSame($expected, (int) ini_get('max_execution_time'));

                ini_set('max_execution_time', (string) $limit);
                $this->post(route('admin.gass_physical.import_excel'), [
                    'excel_file' => $file, 'year' => 2026,
                ])->assertRedirect()->assertSessionHas('success')->assertSessionMissing('error');
                $this->assertSame($expected, (int) ini_get('max_execution_time'));
            }
        } finally {
            set_time_limit($original);
        }
    }

    public function test_missing_worksheet_uses_the_normal_preview_and_import_error_responses(): void
    {
        $this->fixtures('gass');
        $file = $this->workbook('sto', 'headings');
        $payload = ['excel_file' => $file, 'year' => 2026];
        $this->postJson(route('admin.gass_physical.import_excel.preview'), $payload)
            ->assertUnprocessable()->assertJsonPath('success', false);
        $this->post(route('admin.gass_physical.import_excel'), $payload)
            ->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseCount('ppa', 0);
        $this->assertDatabaseCount('physical_targets', 0);
    }
}
