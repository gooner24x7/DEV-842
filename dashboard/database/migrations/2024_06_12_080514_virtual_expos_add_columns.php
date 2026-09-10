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
        Schema::table('virtual_expos', function (Blueprint $table) {
            $table->integer('expo_live')->nullable(false)->default(0);
            $table->integer('product_categories')->nullable(false)->default(0);
            $table->integer('tech_support')->nullable(false)->default(0);
            $table->integer('pim_uploaded')->nullable(false)->default(0);
            $table->integer('epd_info')->nullable(false)->default(0);
            $table->text('description')->after('company_name')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('virtual_expos', function (Blueprint $table) {
            $table->dropColumn('expo_live');
            $table->dropColumn('product_categories');
            $table->dropColumn('tech_support');
            $table->dropColumn('pim_uploaded');
            $table->dropColumn('epd_info');
            $table->dropColumn('description');
        });
    }
};
