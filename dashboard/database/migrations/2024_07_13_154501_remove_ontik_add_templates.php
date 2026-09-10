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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_sync_ontik_at');
            Schema::create('questionnaire_templates', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->string('name');
                $table->json('questions')->nullable(false);
                $table->timestamps();
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_sync_ontik_at')->nullable(true);
            Schema::drop('questionnaire_templates');
        });
    }
};
