<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MessageVirtualExpo extends Migration
{
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->bigInteger('virtual_expo_shared_contact_id')->default(null);
            $table->bigInteger('answer_id')->default(null)->change();
        });
    }

    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('virtual_expo_shared_contact_id');
            $table->bigInteger('answer_id')->change();
        });
    }
}
