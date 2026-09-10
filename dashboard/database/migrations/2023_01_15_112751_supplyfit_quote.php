<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SupplyfitQuote extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supply_fit_enquiry_quotes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->bigInteger('enquiry_id');
            $table->text('comment');
            $table->double('price');
            $table->text('offers')->nullable(true);;
            $table->string('supplier_invoice_no')->nullable(true);
            $table->datetime('quote_accepted_at')->nullable(true)->default(null);
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
        Schema::dropIfExists('supply_fit_enquiry_quotes');
    }
}
