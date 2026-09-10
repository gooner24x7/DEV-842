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
        Schema::table('works_packages', function (Blueprint $table) {
            $table->integer('parent_id')->nullable(true)->default(null)->after('id');
            $table->integer('template_id')->nullable(true)->default(null)->after('project_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('works_packages', function (Blueprint $table) {
            $table->dropColumn([
                'parent_id',
                'template_id'
            ]);
        });
    }
};
