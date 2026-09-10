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
        Schema::create('activity_tracker', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->bigInteger('quote_id');
            $table->string('item_no')->nullable(true);
            $table->string('item_code')->nullable(true);
            $table->integer('qty')->nullable(true);
            $table->string('unit')->nullable(true);
            $table->string('price_and_terms')->nullable(true);
            $table->string('unit_price')->nullable(true);
            $table->string('disc_percent')->nullable(true);
            $table->string('per_unit')->nullable(true);
            $table->string('amount')->nullable(true);
            $table->string('vat')->nullable(true);
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
        Schema::drop('activity_tracker');
    }
};
