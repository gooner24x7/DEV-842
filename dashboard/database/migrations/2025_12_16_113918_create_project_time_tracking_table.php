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
        Schema::create('project_time_tracking', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('project_id')->nullable(false);
            $table->bigInteger('user_id')->nullable(false);
            $table->float('hours_ap')->nullable(true)->default(null);
            $table->float('hours_se')->nullable(true)->default(null);
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
        Schema::dropIfExists('project_time_tracking');
    }
};
