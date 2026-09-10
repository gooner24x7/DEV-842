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
        Schema::create('review_templates', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable(false);
            $table->string('name')->nullable(false);
            $table->string('description')->nullable(true)->default(null);
            $table->timestamps();
        });

        Schema::create('review_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('review_templates')->cascadeOnDelete();
            $table->string('title')->nullable(false);
            $table->string('description')->nullable(true)->default(null);
        });

        Schema::create('review_template_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('review_template_sections')->cascadeOnDelete();
            $table->text('question');
        });

        Schema::create('review_template_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('review_template_questions')->cascadeOnDelete();
            $table->string('text')->nullable(false);
            $table->tinyInteger('score')->nullable(false)->default(1);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->integer ('user_id')->nullable(false);
            $table->integer('quote_id')->nullable(false);
            $table->integer('template_id')->nullable(false);
            $table->tinyInteger('status')->nullable(false)->default(1);
            $table->text('comment')->nullable(true)->default(null);
            $table->timestamps();
            $table->timestamp('completed_at')->nullable(true)->default(null);
        });

        Schema::create('review_answers', function (Blueprint $table) {
            $table->id();
            $table->integer('review_id')->nullable(false);
            $table->integer('question_id')->nullable(false);
            $table->integer('option_id')->nullable(false);
            $table->text('comment')->nullable(true)->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('review_answers');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('review_template_options');
        Schema::dropIfExists('review_template_questions');
        Schema::dropIfExists('review_template_sections');
        Schema::dropIfExists('review_templates');
    }
};
