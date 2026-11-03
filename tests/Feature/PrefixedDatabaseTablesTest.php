<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PrefixedDatabaseTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_create_prefixed_tables_and_models_use_them(): void
    {
        $this->assertSame('pms_', DB::connection()->getTablePrefix());

        // Inspect physical names directly, bypassing the query builder's prefix.
        $tables = array_column(DB::select("SELECT name FROM sqlite_master WHERE type = 'table'"), 'name');

        foreach (['users', 'offices', 'ppa', 'physical_targets', 'physical_accomplishments', 'financial_target', 'financial_accomplishment', 'migrations', 'sessions', 'cache', 'jobs'] as $table) {
            $this->assertContains('pms_'.$table, $tables);
            $this->assertNotContains($table, $tables);
            $this->assertTrue(Schema::hasTable($table));
        }

        $foreignKeys = DB::select('PRAGMA foreign_key_list("pms_physical_targets")');
        $this->assertContains('pms_users', array_column($foreignKeys, 'table'));
        $this->assertContains('pms_ppa', array_column($foreignKeys, 'table'));

        $user = User::create([
            'name' => 'Prefix Test',
            'email' => 'prefix@example.test',
            'password' => 'test-password',
            'role' => 'admin',
        ]);
        $this->assertSame($user->id, User::where('email', $user->email)->firstOrFail()->id);
        $this->assertSame($user->email, DB::selectOne('SELECT email FROM pms_users WHERE id = ?', [$user->id])->email);
    }
}
