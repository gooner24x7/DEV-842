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
        Schema::create('housebuilding', function (Blueprint $table) {
            $table->id();
            $table->string('house_name')->nullable(false);
            $table->string('category')->nullable(false);
            $table->string('subcategory')->nullable(true)->default(null);
            $table->string('product_name')->nullable(false);
            $table->string('product_ref')->nullable(true)->default(null);
            $table->string('uom')->nullable(true)->default(null);
            $table->decimal('quantity')->nullable(true)->default(null);
            $table->decimal('price')->nullable(true)->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('housebuilding');
    }
};
