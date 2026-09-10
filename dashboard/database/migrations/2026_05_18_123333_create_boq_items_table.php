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
        Schema::create('boq_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('project_id')->nullable(false);
            $table->string('conquest_ref', 50)->nullable(true)->default(null);
            $table->string('works_package', 100)->nullable(true)->default(null);
            $table->tinyInteger('bill')->nullable(true)->default(null);
            $table->tinyInteger('section')->nullable(true)->default(null);
            $table->tinyInteger('page')->nullable(true)->default(null);
            $table->string('ref', 3)->nullable(true)->default(null);
            $table->text('description')->nullable(true)->default(null);
            $table->float('quantity')->nullable(true)->default(null);
            $table->string('unit', 10)->nullable(true)->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('boq_items');
    }
};
