<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UserInfo extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('locations')->default(0);
            $table->string('head_office_address')->nullable(true);
            $table->string('customer_service')->nullable(true);
            $table->string('description', 500)->nullable(true);
            $table->string('logo_url')->nullable(true);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locations');
            $table->dropColumn('head_office_address');
            $table->dropColumn('customer_service');
            $table->dropColumn('description');
            $table->dropColumn('logo_url');
        });
    }
}
