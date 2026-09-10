<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_groups', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->tinyInteger('status')->nullable(false)->default(1);
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->bigInteger('group_id')->nullable(true)->default(null)->after('user_id');
        });

        DB::statement('ALTER TABLE projects MODIFY stage TINYINT NOT NULL DEFAULT 1');
        DB::statement('ALTER TABLE projects MODIFY status TINYINT NOT NULL DEFAULT 1');
        DB::statement('ALTER TABLE projects MODIFY level TINYINT NOT NULL DEFAULT 1');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_groups');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('group_id');
        });

        DB::statement('ALTER TABLE projects MODIFY stage TINYINT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE projects MODIFY status TINYINT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE projects MODIFY level TINYINT NOT NULL DEFAULT 0');
    }
};
