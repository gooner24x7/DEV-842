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
        Schema::table('user_onboarding', function (Blueprint $table) {
            $table->dropColumn('preferred_supplier');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_onboarding', function (Blueprint $table) {
            $table->boolean('preferred_supplier')->default(0);
        });
    }
};
