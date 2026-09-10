<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InquiryMerchantCalled extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inquiry_merchant', function (Blueprint $table) {
            $table->id();
            $table->dateTime('called_at')->nullable(true);
            $table->text('comment');
            $table->bigInteger('user_id');
            $table->bigInteger('author_id');
            $table->bigInteger('inquiry_id');
            $table->string('inquiry_type');
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
        Schema::dropIfExists('inquiry_merchant');
    }
}
