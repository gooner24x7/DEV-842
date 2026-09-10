<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CsaFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('pipeline_of_work')->nullable(true);
            $table->integer('potential_users')->nullable(true);
            $table->integer('turnover')->nullable(true);
            $table->integer('number_of_employees')->nullable(true);
            $table->timestamp('onboarding_call')->nullable(true);
            $table->timestamp('next_call')->nullable(true);
            $table->text('pain_points')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pipeline_of_work');
            $table->dropColumn('potential_users');
            $table->dropColumn('turnover');
            $table->dropColumn('number_of_employees');
            $table->dropColumn('onboarding_call');
            $table->dropColumn('next_call');
            $table->dropColumn('pain_points');
        });
    }
}
