<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UserProps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('country')->nullable(true);
            $table->string('city')->nullable(true);
            $table->string('addr_line_1')->nullable(true);
            $table->string('addr_line_2')->nullable(true);
            $table->string('phone')->nullable(true);
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
            $table->dropColumn('country');
            $table->dropColumn('city');
            $table->dropColumn('addr_line_1');
            $table->dropColumn('addr_line_2');
            $table->dropColumn('phone');
        });
    }
}
