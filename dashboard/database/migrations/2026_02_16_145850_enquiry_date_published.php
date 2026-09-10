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
            $table->timestamp('published_at')->nullable(true)->default(null);
        });

        Schema::table('supply_fit_enquiries', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable(true)->default(null);
        });

        Schema::table('logistics_enquiries', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable(true)->default(null);
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
            $table->dropColumn('published_at');
        });

        Schema::table('supply_fit_enquiries', function (Blueprint $table) {
            $table->dropColumn('published_at');
        });

        Schema::table('logistics_enquiries', function (Blueprint $table) {
            $table->dropColumn('published_at');
        });
    }
};
