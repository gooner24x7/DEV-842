<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Questionnaires extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', static function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->bigInteger('user_id');
            $table->timestamps();
        });

        Schema::create('questionnaires', static function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->string('type');
            $table->integer('score_yes');
            $table->integer('score_no');
            $table->bigInteger('user_id');
            $table->timestamp('deleted_at', 0)->nullable();
            $table->string('project_tag');
            $table->timestamps();
        });

        Schema::create('questionnaire_sessions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->timestamp('deleted_at', 0)->nullable();
            $table->boolean('is_answered')->default(false);
            $table->boolean('is_accepted')->default(false);
            $table->boolean('is_declined')->default(false);
            $table->string('hash');
            $table->bigInteger('inquiry_id');
            $table->string('project_tag');
            $table->timestamps();
        });

        Schema::create('questionnaire_replies', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('item_id');
            $table->bigInteger('session_id');
            $table->string('text')->nullable()->default(null);
            $table->boolean('is_yes')->nullable()->default(null);
            $table->integer('score')->default(0);
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
        Schema::dropIfExists('projects');
        Schema::dropIfExists('questionnaires');
        Schema::dropIfExists('questionnaire_sessions');
        Schema::dropIfExists('questionnaire_replies');
    }
}
