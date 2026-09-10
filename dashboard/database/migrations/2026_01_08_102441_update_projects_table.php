<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->float('budget_se')->nullable(true)->default(null);
            $table->date('date_start')->nullable(true)->default(null);
            $table->date('date_end')->nullable(true)->default(null);
        });

        Schema::table('project_time_tracking', function (Blueprint $table) {
            $table->float('spend_se')->nullable(true)->default(null);
            $table->string('name_se')->nullable(true)->default(null);
        });

        Schema::table('supply_fit_enquiries', function (Blueprint $table) {
            $table->string('assumed_end_date')->nullable(true)->default(null);
            $table->string('actual_end_date')->nullable(true)->default(null);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('is_sme')->nullable(false)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'budget_se',
                'date_start',
                'date_end'
            ]);
        });

        Schema::table('project_time_tracking', function (Blueprint $table) {
            $table->dropColumn([
                'spend_se',
                'name_se'
            ]);
        });

        Schema::table('supply_fit_enquiries', function (Blueprint $table) {
            $table->dropColumn([
                'assumed_end_date',
                'actual_end_date'
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_sme');
        });
    }
};
