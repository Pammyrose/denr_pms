<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ppa_details', function (Blueprint $table) {
            $table->string('subsubsubprogram', 150)->nullable();
            $table->string('subsubsubsubprogram', 150)->nullable();
            $table->string('subsubsubsubsubprogram', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ppa_details', function (Blueprint $table) {
            $table->dropColumn(['subsubsubprogram', 'subsubsubsubprogram', 'subsubsubsubsubprogram']);
        });
    }
};
