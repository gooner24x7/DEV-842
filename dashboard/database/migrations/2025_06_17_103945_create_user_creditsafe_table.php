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
        Schema::create('user_creditsafe', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unique()->unsigned();
            $table->string('vat_no')->nullable(true);
            $table->string('risk_score')->nullable(true);
            $table->string('international_score', 1)->nullable(true);
            $table->string('credit_limit')->nullable(true);
            $table->string('contract_limit')->nullable(true);
            $table->integer('total_ccjs')->default(0);
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
        Schema::dropIfExists('user_creditsafe');
    }
};
