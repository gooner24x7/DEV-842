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
        Schema::table('manufacturer_imported_products', function (Blueprint $table) {
            $table->string('width_m')->nullable(true);
            $table->string('length_m')->nullable(true);
            $table->string('depth_m')->nullable(true);
            $table->string('weight_kg')->nullable(true);
            $table->string('list_price')->nullable(true);
            $table->string('discount_percent')->nullable(true);
            $table->string('direct2_cost_price')->nullable(true);
            $table->string('sku')->nullable(true);
            $table->integer('lead_time_in_days')->nullable(true);
            $table->string('ean')->nullable(true);
            $table->string('product_manufacturer')->nullable(true);
            $table->string('unit_of_measure')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('manufacturer_imported_products', function (Blueprint $table) {
            $table->dropColumn('width_m');
            $table->dropColumn('length_m');
            $table->dropColumn('depth_m');
            $table->dropColumn('weight_kg');
            $table->dropColumn('list_price');
            $table->dropColumn('discount_percent');
            $table->dropColumn('direct2_cost_price');
            $table->dropColumn('sku');
            $table->dropColumn('lead_time_in_days');
            $table->dropColumn('ean');
            $table->dropColumn('product_manufacturer');
            $table->dropColumn('unit_of_measure');
        });
    }
};
