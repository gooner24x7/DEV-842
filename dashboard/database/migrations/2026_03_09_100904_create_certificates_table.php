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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable(false)->unsigned();
            $table->string('type')->nullable(false);
            $table->string('cert_no')->nullable(false);
            $table->string('authority')->nullable(false);
            $table->string('scope')->nullable(true)->default(null);
            $table->string('url')->nullable(true)->default(null);
            $table->date('issue_date')->nullable(true);
            $table->date('expiry_date')->nullable(true);
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
        Schema::dropIfExists('certificates');
    }
};
