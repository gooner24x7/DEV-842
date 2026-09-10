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
            $table->string('contractor_region')->nullable(true)->default(null);
            $table->string('tender_notice_id')->nullable(true)->default(null);
            $table->text('description')->nullable(true)->default(null);
            $table->date('date_end_tender')->nullable(true)->default(null);
        });

        Schema::table('users_preferred_subcontractors', function (Blueprint $table) {
            $table->decimal('score')->nullable(true)->default(null);
        });

        Schema::table('supply_fit_enquiry_quotes', function (Blueprint $table) {
            $table->tinyInteger('resources_available')->nullable(false)->default(0);
            $table->tinyInteger('is_competent')->nullable(false)->default(0);
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
            $table->dropColumn('contractor_region');
            $table->dropColumn('tender_notice_id');
            $table->dropColumn('description');
            $table->dropColumn('date_end_tender');
        });

        Schema::table('users_preferred_subcontractors', function (Blueprint $table) {
            $table->dropColumn('score');
        });

        Schema::table('supply_fit_enquiry_quotes', function (Blueprint $table) {
            $table->dropColumn('resources_available');
            $table->dropColumn('is_competent');
        });
    }
};
