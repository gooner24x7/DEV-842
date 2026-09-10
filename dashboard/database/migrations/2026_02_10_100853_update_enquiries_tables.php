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
        Schema::table('questions', function (Blueprint $table) {
            $table->tinyInteger('status')->nullable(false)->default(0);
            $table->tinyInteger('scope')->nullable(false)->default(0);
        });

        Schema::table('supply_fit_enquiries', function (Blueprint $table) {
            $table->tinyInteger('status')->nullable(false)->default(0);
            $table->tinyInteger('scope')->nullable(false)->default(0);
        });

        Schema::table('logistics_enquiries', function (Blueprint $table) {
            $table->tinyInteger('status')->nullable(false)->default(0);
            $table->tinyInteger('scope')->nullable(false)->default(0);
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
            $table->dropColumn(['status', 'scope']);
        });

        Schema::table('supply_fit_enquiries', function (Blueprint $table) {
            $table->dropColumn(['status', 'scope']);
        });

        Schema::table('logistics_enquiries', function (Blueprint $table) {
            $table->dropColumn(['status', 'scope']);
        });
    }
};
