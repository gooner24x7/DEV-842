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
        Schema::table('messages', function (Blueprint $table) {
            $table->timestamp('viewed_at')->nullable(true)->after('updated_at');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->timestamp('viewed_at')->nullable(true)->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('viewed_at');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->dropColumn('viewed_at');
        });
    }
};
