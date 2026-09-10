<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tender_notices', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->string('title', 255)->nullable();
            $table->string('organisation', 255)->nullable();
            $table->string('notice_type')->nullable();
            $table->string('notice_id')->unique();
            $table->string('procurement_id')->nullable();
            $table->string('link')->nullable();
            $table->dateTime('date_published')->nullable();
            $table->dateTime('date_closing')->nullable();
            $table->json('content')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tender_notices');
    }
};
