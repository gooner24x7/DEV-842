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
        Schema::create('users_preferred_subcontractors', function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('subcontractor_id');
            $table->boolean('active')->default(false);
            $table->primary(['user_id', 'subcontractor_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_preferred_subcontractors');
    }
};
