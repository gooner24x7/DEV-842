<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ManufacturerProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manufacturer_imported_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->bigInteger('manufacturer_id');
            $table->bigInteger('deal_id');
            $table->string('image_url')->nullable(true);
            $table->string('deal_name')->nullable(true);
            $table->string('category_name')->nullable(true);
            $table->string('subcategory_name')->nullable(true);
            $table->string('supplier_name')->nullable(true);
            $table->string('supplier_product_code')->nullable(true);
            $table->string('mpn')->nullable(true);
            $table->string('supplier_category_name')->nullable(true);
            $table->string('supplier_subcategory_name')->nullable(true);
            $table->string('web_product_name')->nullable(true);
            $table->string('etim_class_no')->nullable(true);
            $table->string('colour')->nullable(true);
            $table->string('material')->nullable(true);
            $table->string('finish')->nullable(true);
            $table->string('attr1_title')->nullable(true);
            $table->string('attr1_value')->nullable(true);
            $table->string('attr2_title')->nullable(true);
            $table->string('attr2_value')->nullable(true);
            $table->string('attr3_title')->nullable(true);
            $table->string('attr3_value')->nullable(true);
            $table->string('attr4_title')->nullable(true);
            $table->string('attr4_value')->nullable(true);
            $table->string('attr5_title')->nullable(true);
            $table->string('attr5_value')->nullable(true);
            $table->string('feature1')->nullable(true);
            $table->string('feature2')->nullable(true);
            $table->string('feature3')->nullable(true);
            $table->string('feature4')->nullable(true);
            $table->string('feature5')->nullable(true);
            $table->text('environmental_text')->nullable(true);
            $table->text('keywords')->nullable(true);
            $table->string('marketing_completeness')->nullable(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manufacturer_imported_products');
    }
}
