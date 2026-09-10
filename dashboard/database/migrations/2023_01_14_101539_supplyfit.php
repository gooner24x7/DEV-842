<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Supplyfit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supply_fit_enquiries', function (Blueprint $table) {
            $table->id();
            $table->text('comment');
            $table->string('days');
            $table->bigInteger('user_id');
            $table->bigInteger('product_id');
            $table->text('job_ref')->nullable(true);
            $table->string('postcode');
            $table->double('lat')->nullable();
            $table->double('long')->nullable();
            $table->timestamp('archived_at')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supply_fit_enquiries');
    }
}
