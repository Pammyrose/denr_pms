<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PapProgramExtensionsTest extends TestCase
{
    use RefreshDatabase;

    public static function sectors(): array
    {
        return array_map(fn ($sector) => [$sector], [
            'gass', 'sto', 'enf', 'pa', 'engp', 'lands', 'soilcon', 'nra', 'paria', 'cobb', 'continuing',
        ]);
    }

    private function prepareSector(string $sector): void
    {
        $codes = ['pa' => 'Biodiv', 'lands' => 'Lands', 'soilcon' => 'Soilcon'];
        $code = $codes[$sector] ?? strtoupper($sector);
        if (! DB::table('types')->where('code', $code)->exists()) {
            DB::table('types')->insert(['code' => $code, 'desc' => $code]);
        }
        foreach (['PROGRAM', 'PROJECT', 'MAIN ACTIVITY', 'SUB-ACTIVITY', 'SUB-SUB-ACTIVITY', 'SUB-SUB-SUB-ACTIVITY', 'LEVEL-7', 'LEVEL-8', 'LEVEL-9'] as $name) {
            DB::table('record_types')->updateOrInsert(['name' => $name]);
        }
        $this->actingAs(User::create([
            'name' => 'PAP tester', 'email' => 'pap@example.test',
            'password' => 'test-password', 'role' => 'admin',
        ]));
    }

    #[DataProvider('sectors')]
    public function test_program_extensions_save_reuse_reload_and_edit_in_each_sector(string $sector): void
    {
        $this->prepareSector($sector);
        $payload = [
            'title' => 'Watershed project', 'program' => 'Environment program',
            'subprogram' => 'Forest protection', 'subsubprogram' => 'Watersheds',
            'subsubsubprogram' => 'Northern watersheds', 'subsubsubsubprogram' => 'Restoration',
            'subsubsubsubsubprogram' => 'Community restoration',
            'project' => 'N/A', 'activities' => 'Survey', 'year' => 2026,
        ];
        $url = route("admin.{$sector}_physical.pap.store");
        $pap = $this->postJson($url, $payload)->assertOk()->json('pap');
        $this->postJson($url, $payload)->assertOk()->assertJsonPath('pap.row_id', $pap['row_id']);
        $different = $this->postJson($url, array_replace($payload, ['subsubsubsubsubprogram' => 'Uplands']))->assertOk()->json('pap');
        $this->assertNotEquals($pap['id'], $different['id']);
        $rootDetailId = DB::table('ppa')->where('id', $pap['id'])->value('ppa_details_id');
        $this->assertDatabaseHas('ppa_details', ['id' => $rootDetailId, 'subprogram' => 'Forest protection', 'subsubprogram' => 'Watersheds']);

        $controllerName = 'App\\Http\\Controllers\\'.ucfirst($sector).'Controller';
        $method = new \ReflectionMethod($controllerName, 'get'.ucfirst($sector).'Programs');
        $rows = $method->invoke(app($controllerName), null, '', 2026);
        $reloaded = collect($rows)->firstWhere('id', $pap['id']);
        $this->assertSame('Forest protection', $reloaded->subprogram);
        $this->assertSame('Watersheds', $reloaded->subsubprogram);
        $this->assertSame('Northern watersheds', $reloaded->subsubsubprogram);
        $this->assertSame('Restoration', $reloaded->subsubsubsubprogram);
        $this->assertSame('Community restoration', $reloaded->subsubsubsubsubprogram);

        $page = app($controllerName)->index(\Illuminate\Http\Request::create('/', 'GET', ['year' => 2026]));
        $prefill = collect($page->getData()['papPrefillData']);
        $this->assertCount(2, $prefill);
        $this->assertEqualsCanonicalizing(['Community restoration', 'Uplands'], $prefill->pluck('subsubsubsubsubprogram')->all());
        $html = $page->render();
        $this->assertStringContainsString('aria-label="Add Sub-program"', $html);
        $this->assertStringContainsString('aria-label="Add Sub-sub-program"', $html);
        $this->assertStringContainsString('aria-label="Add Sub-sub-sub-sub-sub-program"', $html);
        $this->assertStringNotContainsString('aria-label="Add Sub-sub-sub-sub-sub-sub-program"', $html);
        $this->assertStringNotContainsString('for="pap_title" class="form-label fw-bold small">Title', $html);
        $this->assertStringContainsString('type="hidden" id="pap_project"', $html);

        $this->patchJson(route('physical_pap.update', ['sector' => $sector, 'row' => $pap['row_id']]), array_replace($payload, [
            'subprogram' => 'Revised program', 'subsubprogram' => '',
            'subsubsubprogram' => '', 'subsubsubsubprogram' => '', 'subsubsubsubsubprogram' => '',
        ]))->assertOk()->assertJsonPath('pap.row_id', $pap['row_id'])->assertJsonPath('pap.subsubprogram', '');
        $this->assertDatabaseHas('ppa_details', ['id' => $rootDetailId, 'subprogram' => 'Revised program', 'subsubprogram' => null]);
        $this->assertDatabaseHas('ppa_details', ['id' => $rootDetailId, 'subsubsubprogram' => null, 'subsubsubsubprogram' => null, 'subsubsubsubsubprogram' => null]);
    }

    public function test_program_extensions_require_their_parent_and_reject_oversized_values(): void
    {
        $this->prepareSector('gass');
        $url = route('admin.gass_physical.pap.store');
        $this->postJson($url, ['title' => 'Project', 'subsubprogram' => 'Child'])
            ->assertUnprocessable()->assertJsonValidationErrors(['program', 'subprogram']);
        $this->postJson($url, ['title' => 'Project', 'program' => 'Program', 'subprogram' => str_repeat('x', 151)])
            ->assertUnprocessable()->assertJsonValidationErrors('subprogram');
        $this->postJson($url, ['title' => 'Project', 'program' => 'Program', 'subsubsubsubsubprogram' => 'Deep child'])
            ->assertUnprocessable()->assertJsonValidationErrors(['subprogram', 'subsubprogram', 'subsubsubprogram', 'subsubsubsubprogram']);
        $this->assertDatabaseCount('ppa', 0);
    }

    #[DataProvider('sectors')]
    public function test_parent_paths_take_precedence_over_numbered_and_lettered_child_labels(string $sector): void
    {
        $this->prepareSector($sector);
        $base = ['title' => 'Project', 'program' => 'Program', 'project' => 'N/A', 'year' => 2026];
        foreach ([
            ['subprogram' => 'Z. Later branch', 'activities' => '1. Later child'],
            ['subprogram' => 'A. Earlier branch', 'activities' => '9. Earlier child'],
            ['subprogram' => 'A. Earlier branch', 'subsubprogram' => 'B. Nested branch', 'activities' => '1. Nested child'],
            ['activities' => '99. Parent activity'],
            ['subprogram' => 'A. Earlier branch', 'activities' => '2. Earlier child'],
        ] as $path) {
            $this->postJson(route("admin.{$sector}_physical.pap.store"), $base + $path)->assertOk();
        }
        $controllerName = 'App\\Http\\Controllers\\'.ucfirst($sector).'Controller';
        $page = app($controllerName)->index(\Illuminate\Http\Request::create('/', 'GET', ['year' => 2026]));
        $this->assertSame([
            '99. Parent activity', '2. Earlier child', '9. Earlier child', '1. Nested child', '1. Later child',
        ], collect($page->getData()['programsRaw'])->pluck('activities')->all());
    }
}
