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
    public function up(): void
    {
        // update projects related tables, use project_id as foreign key
        Schema::table('works_packages', function (Blueprint $table) {
            $table->dropColumn('project_tag');
            $table->bigInteger('project_id')->after('id')->unsigned()->nullable(false);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('job_ref');
            $table->dropColumn('project_name');
            $table->bigInteger('project_id')->after('id')->unsigned()->nullable();
            $table->bigInteger('works_package_id')->after('project_id')->unsigned()->nullable();
        });

        Schema::table('supply_fit_enquiries', function (Blueprint $table) {
            $table->dropColumn('job_ref');
            $table->dropColumn('works_package_name');
            $table->bigInteger('project_id')->after('id')->unsigned()->nullable();
            $table->bigInteger('works_package_id')->after('project_id')->unsigned()->nullable();
        });

        Schema::table('questionnaires', function (Blueprint $table) {
            $table->dropColumn('project_tag');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('works_packages', function (Blueprint $table) {
            $table->dropColumn('project_id');
            $table->string('project_tag')->after('name')->nullable(false);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('project_id');
            $table->dropColumn('works_package_id');
            $table->string('job_ref')->after('archived_at')->nullable();
            $table->string('project_name')->after('notify_overdue')->nullable();
        });

        Schema::table('supply_fit_enquiries', function (Blueprint $table) {
            $table->dropColumn('project_id');
            $table->dropColumn('works_package_id');
            $table->string('job_ref')->after('product_id')->nullable();
            $table->string('works_package_name')->after('actual_starting_date')->nullable();
        });

        Schema::table('questionnaires', function (Blueprint $table) {
            $table->string('project_tag')->after('deleted_at')->nullable(false);
        });
    }
};
