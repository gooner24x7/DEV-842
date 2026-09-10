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
        Schema::create('questions_action_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id')->nullable(false);
            $table->unsignedBigInteger('user_id')->nullable(false);
            $table->string('action')->nullable(false);
            $table->string('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('questions_access', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id')->nullable(false);
            $table->string('token')->nullable(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->boolean('notify_overdue')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('questions_action_log');
        Schema::dropIfExists('questions_access');

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('notify_overdue');
        });
    }
};
