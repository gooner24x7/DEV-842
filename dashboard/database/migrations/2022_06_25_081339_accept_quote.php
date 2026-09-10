<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AcceptQuote extends Migration
{
    public function up()
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->datetime('quote_accepted_at')->nullable(true)->default(null);
        });
    }

   public function down()
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->dropColumn('quote_accepted_at');
        });
    }
}
