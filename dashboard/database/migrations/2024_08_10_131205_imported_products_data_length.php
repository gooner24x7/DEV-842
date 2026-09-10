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
        Schema::table('manufacturer_imported_products', function (Blueprint $table) {
            $table->text('feature1')->nullable(true)->change();
            $table->text('feature2')->nullable(true)->change();
            $table->text('feature3')->nullable(true)->change();
            $table->text('feature4')->nullable(true)->change();
            $table->text('feature5')->nullable(true)->change();
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
            $table->string('feature1')->nullable(true)->change();
            $table->string('feature2')->nullable(true)->change();
            $table->string('feature3')->nullable(true)->change();
            $table->string('feature4')->nullable(true)->change();
            $table->string('feature5')->nullable(true)->change();
        });
    }
};
