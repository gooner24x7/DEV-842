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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('postcode')->nullable(true)->default(null);
            $table->float('target_miles_client')->nullable(true)->default(null);
            $table->float('target_miles_framework')->nullable(true)->default(null);
            $table->float('target_hours_ap')->nullable(true)->default(null);
            $table->float('target_hours_se')->nullable(true)->default(null);
            $table->string('client_name')->nullable(true)->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'postcode',
                'target_miles_client',
                'target_miles_framework',
                'target_hours_ap',
                'target_hours_se'
            ]);
            $table->string('client_name')->nullable(false)->change();
        });
    }
};
