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
        Schema::create('national_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(false);
            $table->boolean('active')->default(1);
            $table->timestamps();
        });

        Schema::create('nationals', function (Blueprint $table) {
            $table->id();
            $table->integer('question_id')->unsigned()->nullable(false);
            $table->integer('supplier_id')->unsigned()->nullable(false);
            $table->string('email')->nullable(false);
            $table->string('contact_name');
            $table->string('account_number');
            $table->timestamps();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->boolean('send_to_national')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('national_suppliers');
        Schema::dropIfExists('nationals');

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('send_to_national');
        });
    }
};
