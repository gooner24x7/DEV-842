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
            $table->tinyInteger('stage')->nullable(false)->default(0);
            $table->tinyInteger('status')->nullable(false)->default(0);
            $table->tinyInteger('level')->nullable(false)->default(0);
            $table->string('sector')->nullable(true)->default(null);
            $table->string('region')->nullable(true)->default(null);
            $table->string('frame_type')->nullable(true)->default(null);
            $table->string('procurement_route')->nullable(true)->default(null);
            $table->string('boq_standard')->nullable(true)->default(null);
            $table->decimal('project_value', 14, 2)->nullable(true)->default(null);
            $table->float('area_sqm', 14, 2)->nullable(true)->default(null);
            $table->float('perc_services')->nullable(true)->default(null);
            $table->float('perc_prelim')->nullable(true)->default(null);
            $table->float('perc_labour')->nullable(true)->default(null);
            $table->float('perc_materials')->nullable(true)->default(null);
            $table->decimal('budget_se', 14, 2)->nullable(true)->default(null)->change();
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
            $table->dropColumn('stage');
            $table->dropColumn('status');
            $table->dropColumn('level');
            $table->dropColumn('sector');
            $table->dropColumn('region');
            $table->dropColumn('frame_type');
            $table->dropColumn('procurement_route');
            $table->dropColumn('boq_standard');
            $table->dropColumn('project_value');
            $table->dropColumn('area_sqm');
            $table->dropColumn('perc_services');
            $table->dropColumn('perc_prelim');
            $table->dropColumn('perc_labour');
            $table->dropColumn('perc_materials');
            $table->float('budget_se')->nullable(true)->default(null)->change();
        });
    }
};
