<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Companies extends Migration
{
    public function up()
    {
        Schema::create('company_questionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('email');
            $table->bigInteger('user_id');
            $table->timestamp('deleted_at', 0)->nullable();
            $table->bigInteger('project_id');
            $table->boolean('is_answered')->default(false);
            $table->boolean('is_accepted')->default(false);
            $table->boolean('is_declined')->default(false);
            $table->string('hash');
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
        Schema::dropIfExists('company_questionnaire');
    }
}
