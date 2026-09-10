<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('questionnaire_sessions', function (Blueprint $table) {
            $table->bigInteger('works_package_id');
        });

        Schema::table('questionnaires', static function (Blueprint $table) {
            $table->bigInteger('works_package_id');
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
            $table->dropColumn('works_package_id');
        });

        Schema::table('questionnaires', static function (Blueprint $table) {
            $table->dropColumn('works_package_id');
        });
    }
};
