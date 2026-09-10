<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UserRolesInExpo extends Migration
{
    public function up()
    {
        Schema::create('virtual_expo_user_roles', function (Blueprint $table) {
            $table->unsignedInteger('virtual_expo_id');
            $table->unsignedInteger('role_id');
            $table->primary(['virtual_expo_id', 'role_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('virtual_expo_user_roles');
    }
}
