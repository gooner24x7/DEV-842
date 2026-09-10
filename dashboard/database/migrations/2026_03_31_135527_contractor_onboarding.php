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
        Schema::create('contractor_onboarding', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable(false)->unsigned();
            $table->string('company_name')->nullable(true)->default(null);
            $table->string('company_address1')->nullable(true)->default(null);
            $table->string('company_address2')->nullable(true)->default(null);
            $table->string('company_city')->nullable(true)->default(null);
            $table->string('company_postcode')->nullable(true)->default(null);
            $table->string('company_registration_number')->nullable(true)->default(null);
            $table->string('company_vat_number')->nullable(true)->default(null);
            $table->tinyInteger('developer')->nullable(false)->default(0);
            $table->tinyInteger('framework')->nullable(false)->default(0);
            $table->tinyInteger('main_contractor')->nullable(false)->default(0);
            $table->tinyInteger('sub_contractor')->nullable(false)->default(0);
            $table->tinyInteger('merchant')->nullable(false)->default(0);
            $table->tinyInteger('manufacturer')->nullable(false)->default(0);
            $table->tinyInteger('cas_approved')->nullable(false)->default(0);
            $table->tinyInteger('cas_certifying_body')->nullable(false)->default(0);
            $table->tinyInteger('goods_supply')->nullable(false)->default(0);
            $table->date('cas_issue_date')->nullable(true)->default(null);
            $table->date('cas_expiry_date')->nullable(true)->default(null);
            $table->timestamps();
        });

        Schema::table('user_onboarding', function (Blueprint $table) {
           $table->tinyInteger('form_complete')->nullable(false)->default(0)->after('line_of_credit');
           $table->tinyInteger('first_project')->nullable(false)->default(0)->after('form_complete');
           $table->tinyInteger('first_works_package')->nullable(false)->default(0)->after('first_project');
           $table->tinyInteger('first_tender')->nullable(false)->default(0)->after('first_works_package');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contractor_onboarding');

        Schema::table('user_onboarding', function (Blueprint $table) {
            $table->dropColumn('form_complete');
            $table->dropColumn('first_project');
            $table->dropColumn('first_works_package');
            $table->dropColumn('first_tender');
        });
    }
};
