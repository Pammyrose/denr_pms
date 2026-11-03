<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $sectorKeys = [
        'gass',
        'sto',
        'enf',
        'pa',
        'engp',
        'lands',
        'soilcon',
        'nra',
        'paria',
        'cobb',
        'continuing',
    ];

    public function up(): void
    {
        $this->addIndexIfPossible('ppa', ['year', 'types_id', 'record_type_id'], 'ppa_year_type_record_idx');
        $this->addIndexIfPossible('ppa', ['year', 'ppa_details_id'], 'ppa_year_details_idx');
        $this->addIndexIfPossible('ppa', ['year', 'indicator_id'], 'ppa_year_indicator_idx');
        $this->addIndexIfPossible('ppa', ['types_id', 'record_type_id', 'created_at'], 'ppa_type_record_created_idx');

        foreach ($this->sectorKeys as $sector) {
            $this->addIndexIfPossible($sector, ['ppa_id', 'indicator_id'], "{$sector}_ppa_indicator_idx");

            $physicalTable = "{$sector}_physical";
            $this->addIndexIfPossible($physicalTable, ['programs_id', 'year', 'office_id'], "{$physicalTable}_program_year_office_idx");
            $this->addIndexIfPossible($physicalTable, ['year', 'office_id'], "{$physicalTable}_year_office_idx");

            foreach (["{$sector}_targets", "{$sector}_accomplishments"] as $table) {
                $this->addIndexIfPossible($table, ['years', 'office_ids'], "{$table}_year_office_idx");
                $this->addIndexIfPossible($table, ['years', 'created_at'], "{$table}_year_created_idx");
            }
        }
    }

    public function down(): void
    {
        $this->dropIndexIfExists('ppa', 'ppa_year_type_record_idx');
        $this->dropIndexIfExists('ppa', 'ppa_year_details_idx');
        $this->dropIndexIfExists('ppa', 'ppa_year_indicator_idx');
        $this->dropIndexIfExists('ppa', 'ppa_type_record_created_idx');

        foreach ($this->sectorKeys as $sector) {
            $this->dropIndexIfExists($sector, "{$sector}_ppa_indicator_idx");

            $physicalTable = "{$sector}_physical";
            $this->dropIndexIfExists($physicalTable, "{$physicalTable}_program_year_office_idx");
            $this->dropIndexIfExists($physicalTable, "{$physicalTable}_year_office_idx");

            foreach (["{$sector}_targets", "{$sector}_accomplishments"] as $table) {
                $this->dropIndexIfExists($table, "{$table}_year_office_idx");
                $this->dropIndexIfExists($table, "{$table}_year_created_idx");
            }
        }
    }

    private function addIndexIfPossible(string $table, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }

            if ($this->isUnsupportedIndexColumn($table, $column)) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return Schema::hasIndex($table, $indexName);
    }

    private function isUnsupportedIndexColumn(string $table, string $column): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return false;
        }

        $schema = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
            [$schema, DB::connection()->getTablePrefix().$table, $column]
        );

        return in_array(strtolower((string) ($rows[0]->DATA_TYPE ?? '')), [
            'json',
            'text',
            'tinytext',
            'mediumtext',
            'longtext',
            'blob',
            'tinyblob',
            'mediumblob',
            'longblob',
        ], true);
    }
};
