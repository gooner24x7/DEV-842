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
        Schema::create('works_package_questions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('works_package_id')->nullable(false);
            $table->bigInteger('user_id')->nullable(false);
            $table->bigInteger('answer_user_id')->nullable(true)->default(null);
            $table->text('question')->nullable(true)->default(null);
            $table->text('answer')->nullable(true)->default(null);
            $table->timestamps();
            $table->timestamp('answered_at')->nullable(true)->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('works_package_questions');
    }
};
