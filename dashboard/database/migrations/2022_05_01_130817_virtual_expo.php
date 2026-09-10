<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VirtualExpo extends Migration
{
    public function up()
    {
        Schema::create('virtual_expos', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->date('date_start')->default(null);
            $table->date('date_end')->default(null);
            $table->bigInteger('user_id');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('virtual_expo_videos', function (Blueprint $table) {
            $table->id();
            $table->string('banner_image');
            $table->text('video');
            $table->bigInteger('virtual_expo_id');
            $table->bigInteger('user_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('virtual_expos');
        Schema::dropIfExists('virtual_expo_videos');
    }
}
