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
        Schema::create('project_document_categories', function (Blueprint $table) {
            $table->id();
            $table->integer('parent_id')->nullable(true)->default(null);
            $table->string('title')->nullable(false);
            $table->string('slug')->nullable(false);
            $table->string('description')->nullable(true)->default(null);
            $table->boolean('active')->default(true);
        });

        Schema::create('project_document_groups', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable(false);
            $table->integer('project_id')->nullable(false);
            $table->integer('category_id')->nullable(true)->default(null);
            $table->string('name')->nullable(false);
            $table->timestamps();
        });

        Schema::table('project_documents', function (Blueprint $table) {
            $table->integer('group_id')->nullable(false)->after('user_id');
            $table->string('description')->nullable(true)->default(null)->after('filename');
            $table->dropColumn(['category']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_document_categories');
        Schema::dropIfExists('project_document_groups');

        Schema::table('project_documents', function (Blueprint $table) {
            $table->integer('category')->default(0)->after('filename');
            $table->dropColumn(['group_id']);
        });
    }
};
