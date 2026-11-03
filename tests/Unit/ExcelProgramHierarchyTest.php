<?php

namespace Tests\Unit;

use App\Support\ExcelProgramHierarchy;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ExcelProgramHierarchyTest extends TestCase
{
    public function test_heading_labels_normalize_case_spaces_and_hyphens(): void
    {
        $this->assertSame(['title' => 'Watershed'], ExcelProgramHierarchy::heading(' Project: Watershed '));
        $this->assertSame(['subsubsubsubsubprogram' => 'Forest'], ExcelProgramHierarchy::heading('SUB SUB-SUB-SUB-SUB PROGRAM: Forest'));
        $this->assertNull(ExcelProgramHierarchy::heading('Forest protection program'));
    }

    public function test_changing_a_parent_clears_deeper_levels_and_blank_cells_continue_the_path(): void
    {
        $context = [
            'title' => 'Project', 'program' => 'Program', 'subprogram' => 'Forest',
            'subsubprogram' => 'North', 'subsubsubprogram' => 'Restoration',
            'subsubsubsubprogram' => 'Community', 'subsubsubsubsubprogram' => 'Survey',
        ];
        $this->assertSame($context, ExcelProgramHierarchy::merge($context, []));
        $this->assertSame($context, ExcelProgramHierarchy::merge($context, ['program' => 'Program']));
        $this->assertSame(['title' => 'Project', 'program' => 'Program', 'subprogram' => 'South'],
            ExcelProgramHierarchy::merge($context, ['subprogram' => 'South']));
        $this->assertSame(['title' => 'Project', 'program' => 'Program'],
            ExcelProgramHierarchy::merge($context, ['program' => 'Program'], true));
    }

    public function test_sixth_program_level_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        ExcelProgramHierarchy::heading('Sub-sub-sub-sub-sub-sub-program: Too deep');
    }

    public function test_missing_intermediate_parent_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        ExcelProgramHierarchy::merge([], ['program' => 'Program', 'subsubsubsubsubprogram' => 'Child']);
    }

    public function test_oversized_program_label_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        ExcelProgramHierarchy::merge([], ['program' => str_repeat('x', 151)]);
    }
}
