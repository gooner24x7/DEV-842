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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('contractor_score')->nullable(true)->default(null);
        });

        Schema::table('users_preferred_subcontractors', function (Blueprint $table) {
            $table->dropColumn('score');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('contractor_score');
        });

        Schema::table('users_preferred_subcontractors', function (Blueprint $table) {
            $table->decimal('score')->nullable(true)->default(null);
        });
    }
};
