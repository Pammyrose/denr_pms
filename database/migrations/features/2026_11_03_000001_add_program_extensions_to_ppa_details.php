<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ppa_details', function (Blueprint $table) {
            $table->string('subprogram', 150)->nullable();
            $table->string('subsubprogram', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ppa_details', function (Blueprint $table) {
            $table->dropColumn(['subprogram', 'subsubprogram']);
        });
    }
};
