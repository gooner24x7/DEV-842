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
        Schema::create('activity_tracker_processed_quotes', function (Blueprint $table) {
           $table->id();
           $table->timestamps();
           $table->bigInteger('quote_id');
           $table->string('inquiry_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('activity_tracker_processed_quotes');
    }
};
