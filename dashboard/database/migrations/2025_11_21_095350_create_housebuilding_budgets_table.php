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
        Schema::create('housebuilding_budgets', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type')->nullable(false)->default(0);
            $table->string('house_name')->nullable(true)->default(null);
            $table->string('category_name')->nullable(true)->default(null);
            $table->decimal('budget')->nullable(true)->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('housebuilding_budgets');
    }
};
