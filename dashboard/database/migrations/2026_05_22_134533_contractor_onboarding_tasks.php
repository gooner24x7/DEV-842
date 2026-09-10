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
        Schema::create('contractor_onboarding_tasks', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('billing_user_id')->nullable(false)->unsigned();
            $table->bigInteger('user_id')->nullable(false)->unsigned();
            $table->string('task')->nullable(false);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('job_title')->nullable(true)->default(null);
            $table->string('alias')->nullable(true)->default(null);
            $table->string('company_type')->nullable(true)->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contractor_onboarding_tasks');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('job_title');
            $table->dropColumn('alias');
            $table->dropColumn('company_type');
        });
    }
};
