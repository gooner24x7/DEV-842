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
        Schema::table('works_packages', function (Blueprint $table) {
            $table->tinyInteger('high_risk')->nullable(false)->default(0);
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->dateTime('checked_at')->nullable(true)->default(null);
            $table->tinyInteger('has_substitution')->nullable(false)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('works_packages', function (Blueprint $table) {
            $table->dropColumn('high_risk');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->dropColumn('checked_at');
            $table->dropColumn('has_substitution');
        });
    }
};
