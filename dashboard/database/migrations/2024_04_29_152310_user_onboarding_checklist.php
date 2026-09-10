<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UserOnboardingChecklist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_onboarding', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->boolean('account_setup')->default(1);
            $table->boolean('billing_portal')->default(0);
            $table->boolean('preferred_supplier')->default(0);
            $table->boolean('customer_intro')->default(0);
            $table->boolean('first_enquiry')->default(0);
            $table->boolean('line_of_credit')->default(0);
            $table->date('onboarding_end_date')->nullable();
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
        Schema::dropIfExists('user_onboarding');
    }
}
