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
        Schema::table('questionnaire_sessions', function (Blueprint $table) {
            $table->dropColumn('project_tag');
            $table->bigInteger('project_id')->after('inquiry_id')->unsigned()->nullable(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questionnaire_sessions', function (Blueprint $table) {
            $table->dropColumn('project_id');
            $table->string('project_tag')->after('inquiry_id')->nullable(false);
        });
    }
};
