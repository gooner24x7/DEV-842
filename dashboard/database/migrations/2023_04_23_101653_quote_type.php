<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class QuoteType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->string('type')->default('full');
        });
        Schema::table('supply_fit_enquiry_quotes', function (Blueprint $table) {
            $table->string('type')->default('full');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        Schema::table('supply_fit_enquiry_quotes', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
