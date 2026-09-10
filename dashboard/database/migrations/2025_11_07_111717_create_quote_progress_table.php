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
        Schema::create('quote_progress', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('quote_id')->nullable(false);
            $table->bigInteger('user_id')->nullable(false);
            $table->tinyInteger('type')->nullable(false)->default(0);
            $table->text('comment')->nullable(false)->default('');
            $table->date('date')->nullable(false);
            $table->timestamps();
            $table->dateTime('completed_at')->nullable(true)->default(null);
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('parent_id')->nullable(false);
            $table->bigInteger('user_id')->nullable(false);
            $table->tinyInteger('type')->nullable(false)->default(0);
            $table->text('message')->nullable(false)->default('');
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
        Schema::dropIfExists('quote_progress');
    }
};
