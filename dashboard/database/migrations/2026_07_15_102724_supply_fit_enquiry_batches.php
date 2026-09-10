<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::create('supply_fit_enquiry_batches', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable(false);
            $table->timestamp('processed_at')->nullable(true)->default(null);
        });

        Schema::table('supply_fit_enquiries', function (Blueprint $table) {
            $table->bigInteger('batch_id')->nullable(true)->default(null)->after('id');
        });

        DB::statement('ALTER TABLE supply_fit_enquiries MODIFY status TINYINT NOT NULL DEFAULT 1');
        DB::statement('ALTER TABLE supply_fit_enquiries MODIFY scope TINYINT NOT NULL DEFAULT 1');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supply_fit_enquiry_batches');

        Schema::table('supply_fit_enquiries', function (Blueprint $table) {
            $table->dropColumn('batch_id');
        });

        DB::statement('ALTER TABLE supply_fit_enquiries MODIFY status TINYINT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE supply_fit_enquiries MODIFY scope TINYINT NOT NULL DEFAULT 0');
    }
};
