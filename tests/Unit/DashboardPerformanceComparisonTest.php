<?php

namespace Tests\Unit;

use App\Http\Controllers\DashboardController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class DashboardPerformanceComparisonTest extends TestCase
{
    private array $comparisonTables = [
        'physical_targets',
        'physical_accomplishments',
        'financial_target',
        'financial_accomplishment',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ($this->comparisonTables as $table) {
            Schema::create($table, function (Blueprint $blueprint) {
                $blueprint->id();
                $blueprint->string('sector');
                $blueprint->unsignedBigInteger('office_id');
                $blueprint->unsignedBigInteger('row_id')->default(1);
                $blueprint->unsignedBigInteger('indicator_id')->default(1);
                $blueprint->unsignedInteger('year');

                foreach (['jan', 'feb', 'mar', 'q1', 'apr', 'may', 'jun', 'q2', 'jul', 'aug', 'sep', 'q3', 'oct', 'nov', 'dec', 'q4'] as $period) {
                    $blueprint->decimal($period, 18, 2)->default(0);
                }
            });
        }
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->comparisonTables) as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_sector_lookups_share_queries_without_mixing_offices_or_years(): void
    {
        DB::table('physical_targets')->insert([
            ['sector' => 'gass', 'office_id' => 7, 'row_id' => 1, 'year' => 2026, 'jan' => 10],
            ['sector' => 'sto', 'office_id' => 7, 'row_id' => 1, 'year' => 2026, 'jan' => 20],
            ['sector' => 'gass', 'office_id' => 8, 'row_id' => 1, 'year' => 2026, 'jan' => 900],
            ['sector' => 'gass', 'office_id' => 7, 'row_id' => 1, 'year' => 2025, 'jan' => 800],
        ]);
        $controller = new DashboardController;
        (new \ReflectionProperty(DashboardController::class, 'dashboardSectorKeys'))->setValue($controller, ['gass', 'sto']);
        $monthly = new ReflectionMethod(DashboardController::class, 'physicalMonthlySumsForYear');
        $rows = new ReflectionMethod(DashboardController::class, 'physicalRowsForYear');

        DB::enableQueryLog();
        DB::flushQueryLog();
        $gass = $monthly->invoke($controller, 'physical_targets', 2026, [7], 'gass');
        $sto = $monthly->invoke($controller, 'physical_targets', 2026, [7], 'sto');
        $gassRows = $rows->invoke($controller, 'physical_targets', 2026, [7], 'gass');
        $stoRows = $rows->invoke($controller, 'physical_targets', 2026, [7], 'sto');
        $targetTable = DB::connection()->getQueryGrammar()->wrapTable('physical_targets');
        $reads = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], 'from '.$targetTable));
        DB::disableQueryLog();

        $this->assertCount(1, $reads, 'Monthly summaries and sector lists should reuse the same scoped rows.');
        $this->assertSame(10.0, (float) $gass->sole()->jan);
        $this->assertSame(20.0, (float) $sto->sole()->jan);
        $this->assertSame('gass', $gassRows->sole()->sector);
        $this->assertSame('sto', $stoRows->sole()->sector);
        $this->assertSame(30.0, (float) $monthly->invoke($controller, 'physical_targets', 2026, [7], ['gass', 'sto'])->sole()->jan);
        $this->assertSame(900.0, (float) $monthly->invoke($controller, 'physical_targets', 2026, [8], 'gass')->sole()->jan);
        $this->assertSame(800.0, (float) $monthly->invoke($controller, 'physical_targets', 2025, [7], 'gass')->sole()->jan);
    }

    public function test_comparison_counts_physical_input_cells_and_sums_financial_periods_by_scope(): void
    {
        DB::table('physical_targets')->insert([
            ['sector' => 'gass', 'office_id' => 7, 'row_id' => 1, 'year' => 2026, 'jan' => 100, 'feb' => 50, 'q1' => 150],
            ['sector' => 'gass', 'office_id' => 8, 'row_id' => 1, 'year' => 2026, 'jan' => 900, 'feb' => 0, 'q1' => 900],
            ['sector' => 'sto', 'office_id' => 7, 'row_id' => 2, 'year' => 2026, 'jan' => 200, 'feb' => 0, 'q1' => 200],
            ['sector' => 'gass', 'office_id' => 7, 'row_id' => 1, 'year' => 2025, 'jan' => 800, 'feb' => 0, 'q1' => 800],
        ]);
        DB::table('physical_accomplishments')->insert([
            ['sector' => 'gass', 'office_id' => 7, 'row_id' => 1, 'year' => 2026, 'jan' => 40, 'feb' => 10, 'q1' => 50],
            ['sector' => 'sto', 'office_id' => 7, 'row_id' => 2, 'year' => 2026, 'jan' => 125, 'feb' => 0, 'q1' => 125],
        ]);
        DB::table('financial_target')->insert([
            ['sector' => 'gass', 'office_id' => 7, 'year' => 2026, 'jan' => 1000, 'q1' => 1000],
        ]);
        DB::table('financial_accomplishment')->insert([
            ['sector' => 'gass', 'office_id' => 7, 'year' => 2026, 'jan' => 750, 'q1' => 750],
        ]);

        $method = new ReflectionMethod(DashboardController::class, 'performanceComparisonForYear');
        $fieldConfigs = [
            ['key' => 'gass', 'label' => 'GASS', 'targets' => 'physical_targets', 'accomp' => 'physical_accomplishments'],
            ['key' => 'sto', 'label' => 'STO', 'targets' => 'physical_targets', 'accomp' => 'physical_accomplishments'],
        ];
        $controller = new DashboardController;
        $comparison = $method->invoke($controller, $fieldConfigs, 2026, 7);

        $this->assertSame(1.0, $comparison['physical'][0]['target']['jan']);
        $this->assertSame(1.0, $comparison['physical'][0]['target']['feb']);
        $this->assertSame(1.0, $comparison['physical'][0]['target']['q1']);
        $this->assertSame(1.0, $comparison['physical'][0]['accomplishment']['jan']);
        $this->assertSame(1.0, $comparison['physical'][0]['accomplishment']['q1']);
        $this->assertSame(1.0, $comparison['physical'][1]['target']['jan']);
        $this->assertSame(1.0, $comparison['physical'][1]['accomplishment']['jan']);
        $this->assertSame(1000.0, $comparison['financial'][0]['target']['jan']);
        $this->assertSame(750.0, $comparison['financial'][0]['accomplishment']['jan']);
        $this->assertSame(0.0, $comparison['financial'][1]['target']['jan']);

        $trendMethod = new ReflectionMethod(DashboardController::class, 'progressTrend');
        $trend = $trendMethod->invoke($controller, $fieldConfigs, 2026, 7);

        $this->assertSame(2.0, $trend['monthly'][0]['delay']);
        $this->assertSame(1.0, $trend['monthly'][1]['delay']);
        $this->assertSame(3.0, $trend['quarterly'][0]['delay']);

        $progressMethod = new ReflectionMethod(DashboardController::class, 'physicalInputProgressForYear');
        $financialMethod = new ReflectionMethod(DashboardController::class, 'financialUtilizationForYear');

        $this->assertSame(40.83, $progressMethod->invoke($controller, $fieldConfigs, 2026, 7));
        $this->assertSame(75.0, $financialMethod->invoke($controller, $fieldConfigs, 2026, 7));

        $serviceAreaComparison = $method->invoke($controller, $fieldConfigs, 2026, [7, 8]);

        $this->assertSame(2.0, $serviceAreaComparison['physical'][0]['target']['jan']);
        $this->assertSame(1.0, $serviceAreaComparison['physical'][0]['target']['feb']);
        $this->assertSame(2.0, $serviceAreaComparison['physical'][0]['target']['q1']);
        $this->assertSame(1.0, $serviceAreaComparison['physical'][0]['accomplishment']['jan']);
        $this->assertSame(30.63, $progressMethod->invoke($controller, $fieldConfigs, 2026, [7, 8]));
    }
}
