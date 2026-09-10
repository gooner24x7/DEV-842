<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ArchiveEnquiryReferenceId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->bigInteger('ref_archived_enquiry_id')->nullable(true);
        });

        Schema::table('supply_fit_enquiries', function (Blueprint $table) {
            $table->bigInteger('ref_archived_enquiry_id')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('ref_archived_enquiry_id');
        });

        Schema::table('supply_fit_enquiries', function (Blueprint $table) {
            $table->dropColumn('ref_archived_enquiry_id');
        });
    }
}
